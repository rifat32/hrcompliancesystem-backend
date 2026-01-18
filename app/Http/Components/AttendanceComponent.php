<?php

namespace App\Http\Components;


use App\Http\Utils\BasicNotificationUtil;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\ModuleUtil;
use App\Http\Utils\PayrunUtil;
use App\Models\Attendance;
use App\Models\AttendanceRecord;
use App\Models\Holiday;
use App\Models\SettingAttendance;
use App\Models\WorkLocation;
use App\Models\WorkShiftHistory;
use App\Observers\AttendanceObserver;
use Carbon\Carbon;
use Exception;

class AttendanceComponent
{

    use BasicUtil, BasicNotificationUtil, PayrunUtil, ModuleUtil;



    protected $workTimeManagementComponent;

    public function __construct(WorkTimeManagementComponent $workTimeManagementComponent)
    {

        $this->workTimeManagementComponent = $workTimeManagementComponent;
    }

    public function storeAttendanceRecords($attendanceId, $records)
    {
        foreach ($records as $record) {
            AttendanceRecord::create([
                'attendance_id' => $attendanceId,
                'in_time' => $record["in_time"],
                'out_time' => $record["out_time"],
                'break_hours' => $record["break_hours"],
                'is_paid_break' => $record["is_paid_break"],
                'note' => $record->note ?? null,
                'work_location_id' => $record["work_location_id"],
                'in_latitude' => $record["in_latitude"] ?? "",
                'in_longitude' => $record["in_longitude"] ?? "",
                'out_latitude' => $record["out_latitude"] ?? "",
                'out_longitude' => $record["out_longitude"] ?? "",
                'in_ip_address' => $record["in_ip_address"] ?? "",
                'clocked_in_by' => $record["clocked_in_by"] ?? NULL,
                'clocked_out_by' => $record["clocked_out_by"] ?? NULL,

                'out_ip_address' => $record["out_ip_address"] ?? "",
                'time_zone'  => $record["time_zone"] ?? "",
            ])->projects()->sync($record["project_ids"] ?? []);
        }
    }

    public function updateAttendanceSplit($attendance, $records, $settings, $user, $termination)
    {
        $attendanceData = $attendance->toArray();
        $attendance_records = $attendance->attendance_records;
        foreach ($attendance_records as $attendance_record) {
            $attendance_record->project_ids = $attendance_record->projects->pluck("id")->toArray();
        }
        $attendanceData['attendance_records'] = array_merge($records, $attendance_records->toArray());
        $attendanceData["is_present"] = $this->calculate_total_present_hours($attendanceData["attendance_records"]) > 0;
        $attendanceData = $this->process_attendance_data($attendanceData, $settings, $user, $termination);

        $attendance->update($attendanceData);
        $attendance->attendance_records()->delete();
        $this->storeAttendanceRecords($attendance->id, $attendanceData['attendance_records']);

        (new AttendanceObserver())->updated_action($attendance, 'update');
        $this->adjust_payroll_on_attendance_update($attendance, 0);
        $this->send_notification($attendance, $attendance->employee, "Attendance updated", "update", "attendance");
    }

    public function createAttendanceSplit($attendanceData, $records, $settings, $user, $termination, $date)
    {



        $attendanceData['attendance_records'] = $records;
        $attendanceData["is_present"] = $this->calculate_total_present_hours($records) > 0;

        $attendanceData["in_date"] = $date;

        $attendanceData = $this->process_attendance_data($attendanceData, $settings, $user, $termination);



        $newAttendance = Attendance::create($attendanceData);
        $this->storeAttendanceRecords($newAttendance->id, $records);

        (new AttendanceObserver())->updated_action($newAttendance, 'create');
        $this->adjust_payroll_on_attendance_update($newAttendance, 0);
        $this->send_notification($newAttendance, $newAttendance->employee, "Attendance Taken", "create", "attendance");
    }




    public function split_attendance_records($attendance_id)
    {
        // Get all attendance records for the specific attendance_id, ordered by in_time
        $attendance_records = AttendanceRecord::where('attendance_id', $attendance_id)
            ->orderBy('in_time')
            ->get();

        // Initialize the arrays for first and second day records
        $first_day_data = [
            'date' => null,
            'records' => [],
        ];
        $second_day_data = [
            'date' => null,
            'records' => [],
        ];

        // Loop through all the records
        foreach ($attendance_records as $current) {
            $current->project_ids = $current->projects->pluck("id")->toArray();

            $in_time = Carbon::parse($current->in_time);
            $out_time = Carbon::parse($current->out_time);

            // Case 1: If the in_time and out_time are on different days
            if ($in_time->toDateString() !== $out_time->toDateString()) {
                $in_date = $in_time->toDateString();
                $out_date = $out_time->toDateString();

                // Case 2: If the in_time and out_time span across three or more days, throw an error
                $diff_in_days = $in_time->diffInDays($out_time);

                // If the difference is greater than 1 day, it spans more than two days
                if ($diff_in_days > 1) {
                    throw new Exception("Attendance record spans more than two days: from $in_date to $out_date.", 409);
                }

                // For first day (set the out_time to the end of the day)
                $first_day_data['date'] = $in_date;
                $first_day_data['records'][] = $current->replicate()
                    ->setAttribute('out_time', $in_time->copy()->endOfDay()->toDateTimeString());

                // For second day (set the in_time to the start of the day)
                $second_day_data['date'] = $out_date;
                $second_day_data['records'][] = $current->replicate()
                    ->setAttribute('in_time', $out_time->copy()->startOfDay()->toDateTimeString());
            } else {
                // Case 3: If the in_time and out_time are on the same day, add it to first_day_data
                if (is_null($first_day_data['date'])) {
                    $first_day_data['date'] = $in_time->toDateString();
                }
                $first_day_data['records'][] = $current;
            }
        }

        // Return the updated structure
        return [
            'first_day_data' => $first_day_data,
            'second_day_data' => $second_day_data,
        ];
    }


    function calculate_tolerance_time($in_time, $work_shift_details)
    {
        if (empty($work_shift_details->shifts)) {
            return 0;
        }

        // Filter out invalid shifts
        $shifts = collect($work_shift_details->shifts)->filter(function ($shift) {
            return !empty($shift['start_at']) && !empty($shift['end_at']);
        })->values();

        // Find the lowest start time
        $lowest_start_time = $shifts->pluck('start_at')
            ->map(fn($time) => Carbon::parse($time))
            ->min();

        // Get today's date and set the start time to today's date
        $work_shift_start_at = Carbon::parse($lowest_start_time)->setDate(Carbon::today()->year, Carbon::today()->month, Carbon::today()->day);

        // Set $in_time to today's date as well
        $in_time = Carbon::parse($in_time)->setDate(Carbon::today()->year, Carbon::today()->month, Carbon::today()->day);

        $differenceInSeconds = $in_time->diffInSeconds($work_shift_start_at);

        // Convert seconds to fractional hours (1 hour = 3600 seconds)
        $tolerance_time = $differenceInSeconds / 3600;



        // Return negative value if the in_time is earlier (i.e., employee is early)
        if ($in_time->lessThan($work_shift_start_at)) {

            return -$tolerance_time;
        }

        // Return positive value if the in_time is later (i.e., employee is late)
        return $tolerance_time;
    }

    public function determine_behavior($tolerance_time, $setting_attendance)
    {
        if (empty($setting_attendance->punch_in_time_tolerance)) {
            return "regular";
        }

        if ($tolerance_time < 0) {
            return "early"; // Before on time
        } elseif ($tolerance_time <= $setting_attendance->punch_in_time_tolerance) {
            return "regular"; // On time to tolerance late
        } else {
            return "late"; // After tolerance
        }
    }



    public function calculate_overtime($is_weekend, $capacity_hours, $total_paid_hours, $leave_record, $holiday, $attendance)
    {


        $leave_hours = 0;

        if ($is_weekend) {
            return [
                "overtime_hours" => $total_paid_hours,
                "leave_hours" => $total_paid_hours,
                "is_weekend" => 1
            ];
        }
        if (!empty($holiday_id)) {

            $user_id = $attendance["user_id"];

            $holiday = Holiday::where(
                [
                    "holidays.id" => $holiday_id
                ]
            )
                ->where(function ($query) use ($user_id) {
                    $query->whereHas("employees", function ($query) use ($user_id) {
                        $query->where("users.id", $user_id);
                    })
                        ->orWhere("holidays.is_holiday_for_all", 1);
                })
                ->first();

            if (!empty($holiday)) {
                if ($holiday->status === "rejected" || !(Carbon::parse(
                    $attendance instanceof \App\Models\Attendance ? $attendance->in_date : $attendance["in_date"]
                ))->betweenIncluded(
                    Carbon::parse($holiday->start_date),
                    Carbon::parse($holiday->end_date)
                )) {
                    if ($attendance instanceof \App\Models\Attendance) {
                        $attendance->holiday_id = NULL;
                        $attendance->save();
                    }
                } else {
                    return [
                        "overtime_hours" => $total_paid_hours,
                        "leave_hours" => $capacity_hours,
                        "holiday" => 1
                    ];
                }
            } else {
                if ($attendance instanceof \App\Models\Attendance) {
                    $attendance->holiday_id = NULL;
                    $attendance->save();
                }
            }
        }


        if ($leave_record) {
            if ($leave_record->leave->status !== "rejected") {
                $leave_hours = $leave_record->leave_hours;
            } else {
                if ($attendance instanceof \App\Models\Attendance) {
                    $attendance->leave_record_id = NULL;
                    $attendance->save();
                }
            }
        }

        $capacity_hours = $capacity_hours - $leave_hours;

        return [
            "overtime_hours" => max(0, $total_paid_hours - $capacity_hours),
            "leave_hours" => $leave_hours

        ];
    }


    function calculate_regular_work_hours($total_paid_hours, $result_balance_hours)
    {
        return $total_paid_hours - $result_balance_hours;
    }

    public function updateAttendanceOverTime($attendances)
    {

        $setting_attendance = $this->get_attendance_setting();

        foreach ($attendances as $attendance) {
            $work_shift_history = $attendance->work_shift_history;
            $work_shift_details = collect($work_shift_history->details)
                ->filter(function ($detail) use ($attendance) {
                    $day_number = Carbon::parse($attendance->in_date)->dayOfWeek;
                    return $day_number === $detail["day"];
                })
                ->first();

            $tolerance_time = $this->calculate_tolerance_time($attendance->attendance_records->toArray()[0]["in_time"], $work_shift_details);
            // Determine behavior based on tolerance time and attendance setting
            $behavior = $this->determine_behavior($tolerance_time, $setting_attendance);

            $leave_record = $attendance->leave_record;

            $capacity_hours = (!empty($work_shift_details->is_weekend))
                ? 0
                : (
                    ($work_shift_history->break_type != "paid")
                    ? ($work_shift_details->schedule_hour - $work_shift_history->break_hours)
                    : $work_shift_details->schedule_hour
                );

            $total_paid_hours = $attendance->total_paid_hours;
            $work_hours_delta = $total_paid_hours - $capacity_hours;

            $overtime_information = $this->calculate_overtime($work_shift_details->is_weekend, $capacity_hours, $total_paid_hours, $leave_record, $attendance->holiday_id ?? NULL, $attendance);

            $regular_work_hours = $this->calculate_regular_work_hours($total_paid_hours, $overtime_information["overtime_hours"]);

            $attendance_data["break_type"] = $work_shift_history->break_type ?? "";
            $attendance_data["behavior"] = $behavior;
            $attendance_data["capacity_hours"] = $capacity_hours;
            $attendance_data["work_hours_delta"] = $work_hours_delta;
            $attendance_data["total_paid_hours"] = $total_paid_hours;
            $attendance_data["regular_work_hours"] = $regular_work_hours;
            $attendance_data["is_weekend"] = $work_shift_details->is_weekend ?? NULL;
            $attendance_data["overtime_hours"] = $overtime_information["overtime_hours"];
            $attendance_data["leave_hours"] = $overtime_information["leave_hours"];
            $attendance_data["punch_in_time_tolerance"] = $setting_attendance->punch_in_time_tolerance;
            $attendance_data["tolerance_time"] = $tolerance_time;
            $attendance->update($attendance_data);
        }
    }

    public function checkAttendanceExists($id = "", $user_id, $date)
    {

        $exists  = Attendance::when(!empty($id), function ($query) use ($id) {
            $query->whereNotIn('id', [$id]);
        })
            ->where('attendances.user_id', $user_id)
            ->where('attendances.in_date', $date)
            ->where('attendances.business_id', auth()->user()->business_id)
            ->exists();
        return $exists;
    }



    public function get_already_taken_attendance_dates($user_id, $start_date, $end_date, $exclude_absent_days = 0)
    {
        $already_taken_attendances =  Attendance::where([
            "user_id" => $user_id
        ])
            ->when(!empty($exclude_absent_days), function ($query) {
                $query->where('attendances.is_present', 1);
            })

            ->where('attendances.in_date', '>=', $start_date)
            ->whereDate('attendances.in_date', '<=', $end_date)
            ->get();


        $already_taken_attendance_dates = $already_taken_attendances->map(function ($attendance) {
            return Carbon::parse($attendance->in_date);
        });

        return $already_taken_attendance_dates;
    }






    public function getAttendanceV2Data()
    {

        // Retrieve all department IDs managed by the current user.
        $all_manager_department_ids = $this->get_all_departments_of_manager();


        // Query for attendance records with related data.
        $attendancesQuery = Attendance::with([
            "employee" => function ($query) {
                $query->select(
                    'users.id',
                    "users.title",
                    'users.first_Name',
                    'users.middle_Name',
                    'users.last_Name'
                );
            },
            "employee.departments" => function ($query) {
                $query->select('departments.id', 'departments.name');
            },
            // "work_location",
            // "projects",
            "work_shift_history" => function ($query) {
                $query->select(
                    'work_shift_histories.id',
                    'work_shift_histories.name',
                    'work_shift_histories.break_type',
                    'work_shift_histories.break_hours',
                    'work_shift_histories.type'
                );
            },
            "attendance_records",
            "attendance_records.projects",
            "attendance_records.work_location",
        ])
            ->filterAttendance($all_manager_department_ids);



        // Retrieve attendance data.
        $attendances = $this->retrieveData($attendancesQuery, "in_date", "attendances");

        foreach ($attendances as $attendance) {
            if (!empty($attendance->work_shift_history)) {
                // Filter the details array to only keep the elements where day matches
                $details = $attendance->work_shift_history->details;
                unset($attendance->work_shift_history->details);
                $attendance->work_shift_history->details = collect($details)
                    ->filter(function ($detail) use ($attendance) {
                        $day_number = Carbon::parse($attendance->in_date)->dayOfWeek;
                        return $day_number === $detail["day"];
                    })
                    ->values()  // Re-index the array ~~
                    ->all();
            }
        }

        $data['data'] = $attendances;



        return $data;
    }





    public function validateWorkLocation($work_location_id, $latitude, $longitude)
    {

        $work_location = WorkLocation::find($work_location_id);

        if (empty($work_location)) {
            throw new Exception(("No work location found by id:" . $work_location_id));
        }

        $moduleEnabled = $this->isModuleEnabled("employee_location_attendance", false);
        if (!$moduleEnabled) {
            return true;
        }


        if (!empty($work_location->is_geo_location_enabled)) {
            if (empty($latitude) || empty($longitude)) {
                return true;
                // throw new Exception("Geo location mismatch: Latitude or longitude is missing for verification.", 403);
            }

            $isWithin = $this->isLocationWithinBounds($latitude, $longitude, $work_location->latitude, $work_location->longitude, $work_location->max_radius);

            if (!$isWithin) {

                // throw new Exception("Geo location mismatch: The provided latitude and longitude do not fall within the expected boundaries for this work location.", 403);
            }
        }


        if (!empty($work_location->is_ip_enabled)) {
            $expected_ip = $work_location->ip_address;
            $actual_ip = request()->ip();

            if ($expected_ip != $actual_ip) {
                throw new Exception("IP address mismatch: Expected IP is " . $expected_ip . ", but received {$actual_ip}.", 403);
            }
        }





        return true;
    }

    public function get_existing_attendanceDates($start_date, $end_date, $user_id)
    {
        $attendance_dates = Attendance::where('user_id', $user_id)
            ->whereDate('in_date', ">=", $start_date)
            ->whereDate('in_date', "<=", $end_date)
            ->pluck('in_date')
            ->toArray();

        return $attendance_dates;
    }

    public function validateAttendanceRecords($in_date, $attendance_records, $throwError = true)
    {

        // Convert to array if it's an Eloquent Collection
        if ($attendance_records instanceof \Illuminate\Support\Collection) {
            $attendance_records = $attendance_records->toArray();
        }


        // Validate individual records first (before sorting)
        foreach ($attendance_records as $index => $record) {
            $record_in_datetime = Carbon::parse($record['in_time']);
            $record_out_datetime = Carbon::parse($record['out_time']);

            if ($record_out_datetime->lt($record_in_datetime)) {
                throw new Exception("Invalid record at entry " . ($index + 1) . ": Out time ({$record_out_datetime->format('d/m/Y h:i A')}) is earlier than In time ({$record_in_datetime->format('d/m/Y h:i A')}). Ensure correct date and time entries.", 409);
            }
        }

        // Sort the array by 'in_time' for easier comparison
        usort($attendance_records, function ($a, $b) {
            return strtotime($a['in_time']) - strtotime($b['in_time']);
        });


        // Loop through records and compare consecutive entries
        for ($i = 0; $i < count($attendance_records) - 1; $i++) {
            // Use Carbon to parse and compare the datetimes
            $currentOutTime = Carbon::parse($attendance_records[$i]['out_time']);
            $nextInTime = Carbon::parse($attendance_records[$i + 1]['in_time']);
            $attendanceDate = Carbon::parse($in_date);


            if ($throwError) {

                $attendance_date_plus_one = $attendanceDate->copy()->addDay(); // Preserve original attendance date

                if (
                    !$currentOutTime->isSameDay($attendanceDate) &&
                    !$currentOutTime->isSameDay($attendance_date_plus_one)
                ) {
                    throw new Exception(
                        "Out date ({$currentOutTime->format('d/m/Y')}) must be the same as attendance date ({$attendanceDate->format('d/m/Y')}) or the next date ({$attendance_date_plus_one->format('d/m/Y')}).",
                        409
                    );
                }




                if ($currentOutTime->gt($nextInTime)) {
                    $currentOutTimeFormatted = $currentOutTime->format('h:i A');
                    $nextInTimeFormatted = $nextInTime->format('h:i A');

                    throw new Exception("Time overlap detected between Record " . ($i + 1) . " (End Time: $currentOutTimeFormatted) and Record " . ($i + 2) . " (Start Time: $nextInTimeFormatted). The current entry ends at $currentOutTimeFormatted while the next one starts at $nextInTimeFormatted. Correct the entry to avoid overlap.", 409);
                }
            } else {
                return false;
            }
        }

        return true;
    }

    public function prepare_data_on_attendance_create($raw_data, $user_id)
    {

        $raw_data["user_id"] = $user_id;
        $raw_data["business_id"] = auth()->user()->business_id;
        $raw_data["is_active"] = true;
        $raw_data["created_by"] = auth()->user()->id;
        $raw_data["status"] = (auth()->user()->hasRole("business_owner") ? "approved" : "pending_approval");



        // Initialize only if not already set
        $raw_data["break_hours"] = $raw_data["break_hours"] ?? 0;
        $raw_data["paid_break_hours"] = $raw_data["paid_break_hours"] ?? 0;
        $raw_data["unpaid_break_hours"] = $raw_data["unpaid_break_hours"] ?? 0;

        // Check if "attendance_records" is a valid array
        if (!empty($raw_data["attendance_records"]) && is_array($raw_data["attendance_records"])) {

            $this->validateAttendanceRecords($raw_data["in_date"], $raw_data["attendance_records"]);

            foreach ($raw_data["attendance_records"] as $attendance_record) {
                $break_hours = $attendance_record["break_hours"] ?? 0;
                if (!is_numeric($break_hours)) {
                    $break_hours = 0;
                }
                $raw_data["break_hours"] += $break_hours;

                if (!empty($attendance_record["is_paid_break"])) {
                    $raw_data["paid_break_hours"] += $break_hours;
                } else {
                    $raw_data["unpaid_break_hours"] += $break_hours;
                }
            }
        }




        return $raw_data;
    }

    public function get_attendance_setting()
    {
        $setting_attendance = SettingAttendance::where([
            "business_id" => auth()->user()->business_id
        ])
            ->first();
        if (empty($setting_attendance)) {
            throw new Exception("Please define attendance setting first", 400);
        }
        return $setting_attendance;
    }

    public function process_attendance_data($raw_data, $setting_attendance, $user, $termination)
    {


        // Prepare data for attendance creation
        $attendance_data = $this->prepare_data_on_attendance_create($raw_data, $user->id);


        $this->checkJoinAndTerminationDate($user->joining_date, $attendance_data["in_date"], $termination, true);






        // Automatically approve attendance if auto-approval is enabled in settings
        if (
            (
                isset($setting_attendance->auto_approval) && $setting_attendance->auto_approval
            )
            && (
                // Check if the authenticated user is a special user or has a special role
                $this->is_special_user($setting_attendance)
                || $this->is_special_role($setting_attendance)
            )
            || auth()->user()->hasRole("business_owner")
        ) {
            $attendance_data["status"] = "approved";
        }



        // Retrieve work shift history for the user and date
        $work_shift_history =  $this->workTimeManagementComponent->get_work_shift_history($attendance_data["in_date"], $user->id, false);

        if (!empty($work_shift_history)) {
            // Retrieve work shift details based on work shift history and date
            $work_shift_details =  $this->workTimeManagementComponent->get_work_shift_details($work_shift_history, $attendance_data["in_date"], TRUE);
        }

        if (!empty($work_shift_details)) {
            // Calculate capacity hours based on work shift details
            $capacity_hours = (!empty($work_shift_details->is_weekend))
                ? 0
                : (
                    ($work_shift_history->break_type != "paid")
                    ? ($work_shift_details->schedule_hour - $work_shift_history->break_hours)
                    : $work_shift_details->schedule_hour
                );
        } else {
            $capacity_hours = 0;
        }

        // Calculate tolerance time based on in time and work shift details
        if (!empty($work_shift_details)) {
            $tolerance_time = $this->calculate_tolerance_time($attendance_data["attendance_records"][0]["in_time"], $work_shift_details);
        } else {
            $tolerance_time = 0;
        }

        // Determine behavior based on tolerance time and attendance setting
        $behavior = $this->determine_behavior($tolerance_time, $setting_attendance);



        $holiday = $this->workTimeManagementComponent->get_holiday_details($attendance_data["in_date"], $user->id);

        // Retrieve leave record details for the user and date

        $leave_record = $this->workTimeManagementComponent->get_leave_record_details($attendance_data["in_date"], $user->id);






        // Calculate total present hours based on in and out times
        $total_present_hours = $this->calculate_total_present_hours($attendance_data["attendance_records"]);


        // Adjust paid hours based on break taken and work shift history
        $total_paid_hours = $total_present_hours - ($attendance_data["unpaid_break_hours"] ?? 0);

        // Calculate work hours delta
        $work_hours_delta = $total_paid_hours - $capacity_hours;

        // Calculate overtime information
        if (!empty($work_shift_details)) {

            if ($attendance_data["consider_overtime"]) {
                // Calculate overtime information
                $overtime_information = $this->calculate_overtime($work_shift_details->is_weekend, $capacity_hours, $total_paid_hours, $leave_record, $holiday, $attendance_data);
            } else {
                $overtime_information["overtime_hours"] = 0;
            }
        } else {
            $overtime_information["overtime_hours"] = $total_paid_hours;
        }


        // Calculate regular work hours
        $regular_work_hours = $this->calculate_regular_work_hours($total_paid_hours, $overtime_information["overtime_hours"]);

        // Retrieve salary information for the user and date
        $user_salary_info = $this->get_salary_info($user->id, $attendance_data["in_date"]);

        $attendance_data["break_type"] = $work_shift_history->break_type ?? "";
        $attendance_data["behavior"] = $behavior;
        $attendance_data["capacity_hours"] = $capacity_hours;
        $attendance_data["work_hours_delta"] = $work_hours_delta;
        $attendance_data["total_paid_hours"] = $total_paid_hours;
        $attendance_data["regular_work_hours"] = $regular_work_hours;
        // $attendance_data["work_shift_start_at"] = $work_shift_details->start_at;
        // $attendance_data["work_shift_end_at"] =  $work_shift_details->end_at;
        $attendance_data["work_shift_history_id"] = $work_shift_history->id ?? NULL;

        $attendance_data["holiday_id"] = $holiday ? $holiday->id : NULL;

        $attendance_data["leave_record_id"] = $leave_record ? $leave_record->id : NULL;
        $attendance_data["is_weekend"] = $work_shift_details->is_weekend ?? NULL;

        $attendance_data["overtime_hours"] = $overtime_information["overtime_hours"];
        $attendance_data["leave_hours"] = $overtime_information["leave_hours"] ?? 0;


        $attendance_data["punch_in_time_tolerance"] = $setting_attendance->punch_in_time_tolerance;
        $attendance_data["tolerance_time"] = $tolerance_time;

        $attendance_data["regular_hours_salary"] =   $regular_work_hours * $user_salary_info["hourly_salary"];
        $attendance_data["contractual_hours"] =  $user_salary_info["holiday_considered_hours"];

        $attendance_data["overtime_hours_salary"] =   $overtime_information["overtime_hours"] * $user_salary_info["overtime_salary_per_hour"];



        return $attendance_data;
    }

    public function calculate_total_present_hours($attendance_records)
    {

        $total_present_seconds = 0;

        collect($attendance_records)->each(function ($attendance_record) use (&$total_present_seconds) {
            $in_time = Carbon::parse($attendance_record["in_time"]);
            $out_time = Carbon::parse($attendance_record["out_time"]);
            $total_present_seconds += $out_time->diffInSeconds($in_time);
        });

        return round($total_present_seconds / 3600, 2);
    }

    public function calculateOvertime($attendance)
    {
        // Retrieve work shift history for the user and date
        $work_shift_history =  $this->workTimeManagementComponent->get_work_shift_history($attendance->in_date, $attendance->user_id, false);

        if (empty($work_shift_history)) {
            return 0;
        }
        // Retrieve work shift details based on work shift history and date
        $work_shift_details =  $this->workTimeManagementComponent->get_work_shift_details($work_shift_history, $attendance->in_date, TRUE);



        $holiday = $this->workTimeManagementComponent->get_holiday_details($attendance->in_date, $attendance->user_id);

        // Retrieve leave record details for the user and date


        $leave_record = $this->workTimeManagementComponent->get_leave_record_details($attendance->in_date, $attendance->user_id);


        // Calculate capacity hours based on work shift details
        $capacity_hours = $work_shift_details->schedule_hour;

        // Calculate total present hours based on in and out times
        $total_present_hours = $this->calculate_total_present_hours($attendance->attendance_records);


        // Adjust paid hours based on break taken and work shift history
        $total_paid_hours = $total_present_hours - ($attendance->unpaid_break_hours ?? 0);



        if ($attendance->consider_overtime) {
            // Calculate overtime information
            $overtime_information = $this->calculate_overtime($work_shift_details->is_weekend, $capacity_hours, $total_paid_hours, $leave_record, $holiday, $attendance)["overtime_hours"];
        } else {
            $overtime_information = 0;
        }




        return $overtime_information;
    }


    public function is_special_user($setting_attendance)
    {
        // Check if the authenticated user is in the special users collection using pluck and contains
        return $setting_attendance->special_users->pluck('id')->contains(auth()->user()->id);
    }

    public function is_special_role($setting_attendance)
    {

        // Use hasAnyRole directly with pluck to check if the authenticated user has any special role
        return auth()->user()->hasAnyRole($setting_attendance->special_roles->pluck('name')->toArray());
    }




    public function find_attendance($attendance_query_params)
    {
        $attendance =  Attendance::where($attendance_query_params)->first();
        if (!$attendance) {
            throw new Exception("Some thing went wrong");
        }
        return $attendance;
    }


    public  function isLocationWithinBounds($lat, $lon, $centerLat, $centerLon, $radiusInMeters)
    {
        // Earth's radius in meters
        $earthRadiusInMeters = 6371000;

        // Convert the radius from meters to radians
        $radiusInRadians = $radiusInMeters / $earthRadiusInMeters;

        // Convert latitudes and longitudes from degrees to radians
        $lat = deg2rad($lat);
        $lon = deg2rad($lon);
        $centerLat = deg2rad($centerLat);
        $centerLon = deg2rad($centerLon);

        // Calculate the bounds
        $deltaLat = $radiusInRadians;
        $deltaLon = $radiusInRadians / cos($centerLat);

        $minLat = $centerLat - $deltaLat;
        $maxLat = $centerLat + $deltaLat;
        $minLon = $centerLon - $deltaLon;
        $maxLon = $centerLon + $deltaLon;

        // Convert back to degrees for comparison
        $minLat = rad2deg($minLat);
        $maxLat = rad2deg($maxLat);
        $minLon = rad2deg($minLon);
        $maxLon = rad2deg($maxLon);

        // Check if the location is within the bounds
        return $lat >= deg2rad($minLat) && $lat <= deg2rad($maxLat) && $lon >= deg2rad($minLon) && $lon <= deg2rad($maxLon);
    }



    public function processAttendanceSummaryData($employee,  $start_date, $end_date)
    {

        $start_date = Carbon::parse($start_date);
        $end_date = Carbon::parse($end_date);

        // Clone the dates to avoid modifying the originals
        $loop_start = $start_date->copy();
        $loop_end = $end_date->copy();

        $dateArray = [];
        for ($date = $loop_start; $date->lte($loop_end); $date->addDay()) {
            $dateArray[] = $date->format('Y-m-d');
        }


        $employee["start_date"] = $start_date;
        $employee["end_date"] = $end_date;



        // Retrieve attendance settings.
        $setting_attendance = $this->get_attendance_setting();

        $work_shift_histories =  $this->workTimeManagementComponent->get_work_shift_histories($start_date, $end_date, $employee["id"], false);


        $attendances = Attendance::with([
            "work_shift_history",
            "work_shift_history.details",
            "attendance_records",
            "attendance_records.projects",
            "attendance_records.work_location",

        ])
            ->whereNotIn("status", ["rejected"])
            ->where("attendances.user_id", $employee["id"])
            ->whereDate('attendances.in_date', ">=", $start_date)
            ->whereDate('attendances.in_date', "<=", $end_date)
              ->when(request()->filled("project_id"), function ($query) {

                $idsArray = explode(',', request()->input("project_id"));

                return $query->whereHas('attendance_records.projects', function ($query) use ($idsArray) {
                    $query->whereIn("projects.id", $idsArray);
                });
            })
                        ->when(request()->filled("employee_work_shift_id"), function ($query) {
                $work_shift_ids = explode(',', request()->employee_work_shift_id);
                $work_shift_history_ids = WorkShiftHistory::whereIn("work_shift_id",$work_shift_ids)
                    ->get()
                    ->pluck("id")
                    ->toArray();
                return $query->whereIn('attendances.work_shift_history_id', $work_shift_history_ids);
            })
             ->when((request()->filled("work_location_id")), function ($query) {
                return $query->whereHas('attendance_records', function ($query) {
                    $query->where('attendance_records.work_location_id', request()->work_location_id);
                });
            })

            ->get();



        foreach ($attendances as $attendance) {
            if (!empty($attendance->work_shift_history)) {
                // Filter the details array to only keep the elements where day matches
                $attendance->work_shift_history->detail = collect($attendance->work_shift_history->details)
                    ->filter(function ($detail) use ($attendance) {
                        $day_number = Carbon::parse($attendance->in_date)->dayOfWeek;
                        return $day_number === $detail["day"];
                    })
                    ->values()  // Re-index the array
                    ->all();
            }
            $attendance["type"] = "attendance";
            $data['data'][] = $attendance;
        }


        $total_schedule_hours = 0;
        $total_absent_days = 0;
        $total_absent_hours = 0;
        $total_leave_days = 0;
        $total_holiday_days = 0;
        $total_schedule_days = 0;
        $total_leave_hours = 0;

        foreach ($dateArray as $date) {

            $current_date =  Carbon::parse($date);

            if (!empty($work_shift_histories)) {
                $work_shift_history = $work_shift_histories->first(function ($history) use ($current_date) {
                    $fromDate = Carbon::parse($history->from_date);
                    $toDate = $history->to_date ? Carbon::parse($history->to_date) : $current_date->copy()->addDay();
                    return $current_date->greaterThanOrEqualTo($fromDate)
                        && ($toDate === null || $current_date->lessThanOrEqualTo($toDate));
                });
            }


            if (!empty($work_shift_history)) {
                // Retrieve work shift details based on work shift history and date
                $work_shift_details =  $this->workTimeManagementComponent->get_work_shift_details($work_shift_history, $current_date);
            }


            $attendance = collect($attendances)->first(function ($attendance) use ($current_date) {
                $in_date = Carbon::parse($attendance->in_date);
                return $in_date->equalTo($current_date);
            });



            $holiday = $this->workTimeManagementComponent->get_holiday_details($current_date, $employee["id"]);

            if (!empty($holiday)) {
                $total_holiday_days += 1;
                $work_shift_details = $work_shift_details ?? null;
                $capacity_hours = $work_shift_details && $work_shift_details->is_weekend
                    ? 0
                    : ($work_shift_details->schedule_hour ?? 0);


                $data['data'][] = (object)[
                    "id" => $holiday->id,
                    "type" => "holiday",
                    "in_date" => $current_date,
                    "is_on_holiday" => 1,
                    "day" => $current_date->dayOfWeek,
                    "capacity_hours" => $capacity_hours

                ];
            }

            $leave_record =  $this->workTimeManagementComponent->get_leave_record_details($date, $employee["id"]);


            if (!empty($leave_record)) {
                $total_leave_hours += $leave_record->leave_hours;
                $total_leave_days += 1;
                $data['data'][] = (object)[
                    "id" => $leave_record->id,
                    "type" => "leave_record",
                    "tolerance_time" => 0,
                    "behavior" => "regular",
                    "is_present" => 0,
                    "in_date" => $current_date,
                    "is_on_leave" => 1,
                    "leave_id" => $leave_record->leave_id,
                    "leave_hours" => $leave_record->leave_hours,
                    "capacity_hours" => $leave_record->capacity_hours,
                    "day" =>  $current_date->dayOfWeek,
                ];
            }



            if ((
                    !empty($work_shift_details)
                    &&
                    !$work_shift_details->is_weekend
                )
                &&
                empty($holiday)
                &&
                empty($leave)
            ) {
                $total_schedule_days += 1;
                $total_schedule_hours += $work_shift_details->schedule_hour;
            }



            if (
                (
                    !empty($work_shift_details)
                    &&
                    !$work_shift_details->is_weekend
                )
                &&
                empty($holiday)
                &&
                empty($leave_record)
                &&
                empty($attendance)

            ) {

                $total_absent_days += 1;
                $total_absent_hours += $work_shift_details->schedule_hour;

                $data['data'][] = (object)[
                    "id" => $work_shift_details->id,
                    "type" => "work_shift_detail",
                    "in_date" => $current_date,
                    "schedule_flag" => 1,
                    "tolerance_time" => 0,
                    "behavior" => "regular",
                    "is_present" => 0,
                    "shifts" => $work_shift_details->shifts,
                    "day" => $work_shift_details->day,
                    "is_weekend" => $work_shift_details->is_weekend,
                    "capacity_hours" => $work_shift_details->schedule_hour
                ];
            }
        }

        // Calculate total active hours.
        $data['data_highlights']['total_active_hours'] = $attendances->sum('total_paid_hours');

        $data['data_highlights']['total_pending_approval_hours'] = $attendances
            ->filter(fn($item) => $item->status === 'pending_approval')
            ->sum('total_paid_hours');

        // Calculate total extra hours.
        $data['data_highlights']['total_extra_hours'] = $attendances->sum('overtime_hours');

        $total_late_days = $attendances->filter(function ($attendance) {
            // Count only attendances with positive tolerance_time
            return $attendance->behavior == "late";
        })->count();

        // Calculate total late hours
        $total_late_hours = $attendances->sum(function ($attendance) {
            return $attendance->behavior === "late" ? $attendance->tolerance_time : 0;
        });

        // Calculate total early hours
        $total_early_hours = $attendances->sum(function ($attendance) {
            // Sum only negative tolerance_time values (early) converted to positive
            return $attendance->behavior === "early" ? abs($attendance->tolerance_time) : 0;
        });

        // Assign to data highlights
        $data['data_highlights']['total_late_hours'] = $total_late_hours;
        $data['data_highlights']['total_early_hours'] = $total_early_hours;
        $data['data_highlights']['total_working_days'] = $attendances->filter(function ($attendance) {
            return $attendance->is_present == 1;
        })->count();
        $data['data_highlights']['total_absent_days'] = $total_absent_days;
        $data['data_highlights']['total_absent_hours'] = $total_absent_hours;
        $data['data_highlights']['total_schedule_hours'] = $total_schedule_hours;
        $data['data_highlights']['total_leave_days'] = $total_leave_days;
        $data['data_highlights']['total_late_days'] = $total_late_days;
        $data['data_highlights']['total_holiday_days'] = $total_holiday_days;
        $data['data_highlights']['total_schedule_days'] = $total_schedule_days;
        $data['data_highlights']['total_leave_hours'] = $total_leave_hours;



        // Calculate behavior counts.
        $behavior_counts = [
            'regular' => $attendances->filter(fn($attendance) => $attendance->behavior === 'regular')->count(),
            'early' => $attendances->filter(fn($attendance) => $attendance->behavior === 'early')->count(),
            'late' => $attendances->filter(fn($attendance) => $attendance->behavior === 'late')->count(),
        ];

        // Determine the most frequent behavior.
        $max_behavior = max($behavior_counts);
        if ($attendances->isEmpty()) {
            $data['data_highlights']['behavior'] = $behavior_counts;
            $data['data_highlights']['average_behavior'] = "no data";
        } else {
            $data['data_highlights']['behavior'] = $behavior_counts;
            $data['data_highlights']['average_behavior'] = array_search($max_behavior, $behavior_counts);
        }

        $data['data_highlights']["total_available_hours"] = $data['data_highlights']['total_active_hours'] - $data['data_highlights']['total_extra_hours'];


        // Calculate work availability percentage.
        if ($data['data_highlights']["total_available_hours"] == 0 || $data['data_highlights']['total_schedule_hours'] == 0) {
            $data['data_highlights']['total_work_availability_per_centum'] = 0;
        } else {
            $data['data_highlights']['total_work_availability_per_centum'] = ($data['data_highlights']["total_available_hours"] / $data['data_highlights']['total_schedule_hours']) * 100;
        }

        // Determine work availability status based on settings.
        if (!empty($setting_attendance->work_availability_definition)) {
            if (empty($attendances)) {
                $data['data_highlights']['work_availability'] = 'no data';
            } elseif ($data['data_highlights']['total_work_availability_per_centum'] >= $setting_attendance->work_availability_definition) {
                $data['data_highlights']['work_availability'] = 'good';
            } else {
                $data['data_highlights']['work_availability'] = 'bad';
            }
        } else {
            $data['data_highlights']['work_availability'] = 'good';
        }


        if (isset($data['data']) && is_array($data['data'])) {
            usort($data['data'], function ($a, $b) {
                return strtotime($a->in_date) - strtotime($b->in_date);
            });
        } else {
            // Handle the case where 'data' key does not exist or is not an array
            // You could initialize it as an empty array or log an error
            $data['data'] = [];
        }

        $employee["data"] = $data;
        return $employee;
    }
}
