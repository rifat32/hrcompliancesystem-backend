<?php

namespace App\Http\Utils;

use App\Models\Attendance;
use App\Models\Coupon;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\LeaveRecord;
use App\Models\Role;
use App\Models\SettingAttendance;
use App\Models\WorkLocation;
use App\Models\WorkShiftHistory;
use Carbon\Carbon;
use Exception;

trait AttendanceUtil
{
    use PayrunUtil, BasicUtil, ModuleUtil;





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








    public function validateWorkLocation($work_location_id, $latitude, $longitude)
    {

      $work_location = WorkLocation::find($work_location_id);

      if (empty($work_location)) {
        throw new Exception(("No work location found by id:". $work_location_id));
    }

        $moduleEnabled = $this->isModuleEnabled("employee_location_attendance", false);
        if (!$moduleEnabled) {
            return true;
        }


        if (!empty($work_location->is_geo_location_enabled)) {
            if (empty($latitude) || empty($longitude)) {
                return true;
                throw new Exception("Geo location mismatch: Latitude or longitude is missing for verification.", 403);
            }

            $isWithin = $this->isLocationWithinBounds($latitude, $longitude, $work_location->latitude, $work_location->longitude, $work_location->max_radius);

            if (!$isWithin) {

                throw new Exception("Geo location mismatch: The provided latitude and longitude do not fall within the expected boundaries for this work location.", 403);
            }
        }


        if (!empty($work_location->is_ip_enabled)) {
            if ($work_location->ip_address != request()->ip()) {
                throw new Exception("IP address mismatch: The provided IP address does not match the expected IP address for this work location.", 403);
            }
        }





        return true;
    }

    public function validateAttendanceRecords($attendance_records, $throwError = true)
    {

 // Convert to array if it's an Eloquent Collection
 if ($attendance_records instanceof \Illuminate\Support\Collection) {
    $attendance_records = $attendance_records->toArray();
}

// Sort the array by 'in_time' for easier comparison
usort($attendance_records, function ($a, $b) {
    return strtotime($a['in_time']) - strtotime($b['in_time']);
});



        // Loop through records and compare consecutive entries
        for ($i = 0; $i < count($attendance_records) - 1; $i++) {
            $currentOutTime = strtotime($attendance_records[$i]['out_time']);
            $nextInTime = strtotime($attendance_records[$i + 1]['in_time']);

            // Check if current out_time is greater than next in_time

            if ($throwError) {
                if ($currentOutTime > $nextInTime) {
                    throw new \Exception("Time overlap detected between record " . ($i + 1) . " and record " . ($i + 2), 409);
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

            $this->validateAttendanceRecords($raw_data["attendance_records"]);

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


    public function get_work_shift_history($in_date, $user_id, $throwError = true)
    {
        $work_shift_history =  WorkShiftHistory::where(function ($query) use ($in_date, $user_id) {
            $query->where("from_date", "<=", $in_date)
                ->where(function ($query) use ($in_date) {
                    $query->where("to_date", ">=", $in_date)
                        ->orWhereNull("to_date");
                })

                ->where("user_id", $user_id);
        })
            // @@@ confusion
            // ->orWhere(function ($query) {
            //     $query->where([
            //         "business_id" => NULL,
            //         "is_active" => 1,
            //         "is_default" => 1
            //     ]);
            // })
            ->orderByDesc("work_shift_histories.id")
            ->first();

        if (!$work_shift_history && $throwError) {
            throw new Exception("Please define workshift first", 401);
        }

        return $work_shift_history;
    }


    public function get_work_shift_histories($start_date, $end_date, $user_id, $notInTypes = [])
    {
        $work_shift_history =  WorkShiftHistory::when(!empty($notInTypes), function ($query) use ($notInTypes) {
            $query->whereNotIn("work_shift_histories.type", $notInTypes);
        })

            ->where(function ($query) use ($start_date, $end_date, $user_id) {
                $query->where("from_date", "<=", $end_date)
                    ->where(function ($query) use ($start_date) {
                        $query->where("to_date", ">=", $start_date)
                            ->orWhereNull("to_date");
                    })
                    ->where("user_id", $user_id);
            })
            // @@@ confusion

            ->with([
                "details"
            ])

            ->orderByDesc("work_shift_histories.id")
            ->get();

        return $work_shift_history;
    }

    public function get_work_shift_details($work_shift_history, $in_date)
    {
        $day_number = Carbon::parse($in_date)->dayOfWeek;
        $work_shift_details =  $work_shift_history->details()->where([
            "day" => $day_number
        ])
            ->first();

        if (!$work_shift_details) {
            throw new Exception(("No work shift details found  day " . $day_number), 400);
        }

        // if ($work_shift_details->is_weekend && !auth()->user()->hasRole("business_owner")) {
        //     throw new Exception(("there is a weekend on date " . $in_date), 400);
        // }
        return $work_shift_details;
    }

    public function get_work_shift_detailsV2($work_shift_history, $in_date)
    {
        $day_number = Carbon::parse($in_date)->dayOfWeek;
        $work_shift_details =  $work_shift_history->details()->where([
            "day" => $day_number,
            "is_weekend" => 0
        ])

            ->first();



        // if ($work_shift_details->is_weekend && !auth()->user()->hasRole("business_owner")) {
        //     throw new Exception(("there is a weekend on date " . $in_date), 400);
        // }
        return $work_shift_details;
    }

    public function get_work_shift_detailsV3($work_shift_history, $in_date)
    {
        $day_number = Carbon::parse($in_date)->dayOfWeek;

        $work_shift_details = collect($work_shift_history->details)->first(function ($detail) use ($day_number) {
            return $detail->day == $day_number;
        });

        return $work_shift_details;
    }


    public function get_holiday_details($in_date, $user_id, $all_parent_department_ids)
    {

        $holiday =   Holiday::where([
            "business_id" => auth()->user()->business_id
        ])
            ->where('status', 'approved')
            ->whereDate('holidays.start_date', "<=", Carbon::parse($in_date)->startOfDay())
            ->whereDate('holidays.end_date', ">=", Carbon::parse($in_date)->endOfDay())
            ->where(function ($query) use ($user_id, $all_parent_department_ids) {
                $query->whereHas("users", function ($query) use ($user_id) {
                    $query->where([
                        "users.id" => $user_id
                    ]);
                })
                    ->orWhereHas("departments", function ($query) use ($all_parent_department_ids) {
                        $query->whereIn("departments.id", $all_parent_department_ids);
                    })
                    ->orWhere(function ($query) {
                        $query->whereDoesntHave("users")
                            ->whereDoesntHave("departments");
                    });
            })
            ->first();

        // if ($holiday && $holiday->is_active && !auth()->user()->hasRole("business_owner")) {
        //         throw new Exception(("there is a holiday on date" . $in_date), 400);
        // }

        return $holiday;
    }



    public function get_leave_record_details($in_date, $user_id, $attendance_records, $return_leave = false)
    {
        $leave_record = LeaveRecord::whereHas('leave',    function ($query) use ($in_date, $user_id) {
            $query->whereIn("leaves.user_id",  [$user_id])
                ->where("leaves.status", "approved");
        })
            ->where('date', '>=', $in_date . ' 00:00:00')
            ->where('date', '<=', ($in_date . ' 23:59:59'))
            ->first();

        if ($return_leave) {
            return $leave_record;
        }


        if ($leave_record) {
            if (!in_array($leave_record->leave->leave_duration, ['single_day', 'multiple_day'])) {

                $leave_start_time = Carbon::parse($leave_record->start_time);
                $leave_end_time = Carbon::parse($leave_record->end_time);

                foreach ($attendance_records as $attendance_record) {
                    $attendance_in_time = Carbon::parse($attendance_record["in_time"]);
                    $attendance_out_time = Carbon::parse($attendance_record["out_time"]);



                    $balance_start_time = $attendance_in_time->max($leave_start_time);
                    $balance_end_time = $attendance_out_time->min($leave_end_time);

                    if ($balance_start_time < $balance_end_time) {

                        throw new Exception(("there is an hourly leave on date" . $in_date), 400);
                    }
                }
            } else {
                throw new Exception(("there is a leave on date " . $in_date), 400);
            }
        }

        return $leave_record;
    }



    public function get_existing_attendance($in_date, $user_id)
    {
        $attendance = Attendance::where([
            "user_id" => $user_id,

        ])
            ->where('in_date', '>=', $in_date . ' 00:00:00')
            ->where('in_date', '<=', ($in_date . ' 23:59:59'))
            ->first();


        return $attendance;
    }
    public function get_existing_attendanceDates($start_date, $end_date, $user_id)
    {
        $attendance_dates = Attendance::where('user_id', $user_id)
            ->whereBetween('in_date', [$start_date, $end_date])
            ->pluck('in_date')
            ->toArray();

        return $attendance_dates;
    }





    public function calculate_total_present_hours($attendance_records)
    {

        $total_present_seconds = 0;

        collect($attendance_records)->each(function ($attendance_record) use (&$total_present_seconds) {
            $in_time = Carbon::createFromFormat('H:i:s', $attendance_record["in_time"]);
            $out_time = Carbon::createFromFormat('H:i:s', $attendance_record["out_time"]);
            $total_present_seconds += $out_time->diffInSeconds($in_time);
        });

        return round($total_present_seconds / 3600, 2);
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

        $work_shift_start_at = Carbon::parse($lowest_start_time);
        $in_time = Carbon::parse($in_time);

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
            return  "regular";
        } else {
            if ($tolerance_time > $setting_attendance->punch_in_time_tolerance) {
                return "late";
            } else if ($tolerance_time < $setting_attendance->punch_in_time_tolerance) {
                return "early";
            } else {
                return "regular";
            }
        }
    }




    public function calculate_overtime($is_weekend, $work_hours_delta, $total_paid_hours, $leave_record, $holiday, $attendance_records)
    {

        $overtime_hours = 0;

        if ($is_weekend || $holiday) {
            $overtime_hours += $total_paid_hours;
        } else if ($leave_record) {
            $leave_start_time = Carbon::parse($leave_record->start_time);
            $leave_end_time = Carbon::parse($leave_record->end_time);

            foreach ($attendance_records as $attendance_record) {
                $attendance_in_time = Carbon::parse($attendance_record["in_time"]);
                $attendance_out_time = Carbon::parse($attendance_record["out_time"]);

                $balance_start_time = $attendance_in_time->max($leave_start_time);
                $balance_end_time = $attendance_out_time->min($leave_end_time);

                if ($balance_start_time < $balance_end_time) {
                    $overtime_seconds = $balance_start_time->diffInSeconds($balance_end_time);
                    $overtime_hours += round($overtime_seconds / 3600, 2); // Converts seconds to hours and adds it to overtime_hours

                }
            }
        } else if ($work_hours_delta > 0) {
            $overtime_hours = $work_hours_delta;
        }

        return [
            "overtime_hours" => $overtime_hours
        ];
    }

    function calculate_regular_work_hours($total_paid_hours, $result_balance_hours)
    {
        return $total_paid_hours - $result_balance_hours;
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
            ||
            auth()->user()->hasRole("business_owner")

        ) {
            $attendance_data["status"] = "approved";
        }

        // Retrieve salary information for the user and date
        $user_salary_info = $this->get_salary_info($user->id, $attendance_data["in_date"]);

        // Retrieve work shift history for the user and date
        $work_shift_history =  $this->get_work_shift_history($attendance_data["in_date"], $user->id, false);

        if (!empty($work_shift_history)) {
            // Retrieve work shift details based on work shift history and date
            $work_shift_details =  $this->get_work_shift_details($work_shift_history, $attendance_data["in_date"]);
        }


        // Retrieve holiday details for the user and date
        $all_parent_departments_of_user = $this->all_parent_departments_of_user($user->id);

        $holiday = $this->get_holiday_details($attendance_data["in_date"], $user->id, $all_parent_departments_of_user);


        // Retrieve leave record details for the user and date

        $leave_record = $this->get_leave_record_details($attendance_data["in_date"], $user->id, $attendance_data["attendance_records"], true);


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



        // Calculate total present hours based on in and out times
        $total_present_hours = $this->calculate_total_present_hours($attendance_data["attendance_records"]);

        // Calculate tolerance time based on in time and work shift details
        if (!empty($work_shift_details)) {
            $tolerance_time = $this->calculate_tolerance_time($attendance_data["attendance_records"][0]["in_time"], $work_shift_details);
        } else {
            $tolerance_time = 0;
        }


        // Determine behavior based on tolerance time and attendance setting
        $behavior = $this->determine_behavior($tolerance_time, $setting_attendance);

        // Adjust paid hours based on break taken and work shift history
        $total_paid_hours = $total_present_hours - ($attendance_data["unpaid_break_hours"] ?? 0);

        // Calculate work hours delta
        $work_hours_delta = $total_present_hours - $capacity_hours;


        // Calculate overtime information

        if (!empty($work_shift_details)) {
            $overtime_information = $this->calculate_overtime($work_shift_details->is_weekend, $work_hours_delta, $total_paid_hours, $leave_record, $holiday, $attendance_data["attendance_records"]);
        } else {
            $overtime_information["overtime_hours"] = $total_paid_hours;
        }




        // Calculate regular work hours
        $regular_work_hours = $this->calculate_regular_work_hours($total_paid_hours, $overtime_information["overtime_hours"]);

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
        $attendance_data["punch_in_time_tolerance"] = $setting_attendance->punch_in_time_tolerance;
        $attendance_data["tolerance_time"] = $tolerance_time;

        $attendance_data["regular_hours_salary"] =   $regular_work_hours * $user_salary_info["hourly_salary"];
        $attendance_data["contractual_hours"] =  $user_salary_info["holiday_considered_hours"];

        $attendance_data["overtime_hours_salary"] =   $overtime_information["overtime_hours"] * $user_salary_info["overtime_salary_per_hour"];

        return $attendance_data;
    }

    public function calculateOvertime($attendance)
    {


        // Retrieve work shift history for the user and date
        $work_shift_history =  $this->get_work_shift_history($attendance->in_date, $attendance->user_id, false);

        if (empty($work_shift_history)) {
            return 0;
        }
        // Retrieve work shift details based on work shift history and date
        $work_shift_details =  $this->get_work_shift_details($work_shift_history, $attendance->in_date);

        // Retrieve holiday details for the user and date
        $all_parent_departments_of_user = $this->all_parent_departments_of_user($attendance->user_id);

        $holiday = $this->get_holiday_details($attendance->in_date, $attendance->user_id, $all_parent_departments_of_user);

        // Retrieve leave record details for the user and date


        $leave_record = $this->get_leave_record_details($attendance->in_date, $attendance->user_id, $attendance->attendance_records, true);


        // Calculate capacity hours based on work shift details
        $capacity_hours = $work_shift_details->schedule_hour;

        // Calculate total present hours based on in and out times
        $total_present_hours = $this->calculate_total_present_hours($attendance->attendance_records);


        // Adjust paid hours based on break taken and work shift history
        $total_paid_hours = $total_present_hours - ($attendance->unpaid_break_hours ?? 0);

        // Calculate work hours delta
        $work_hours_delta = $total_present_hours - $capacity_hours;


        // Calculate overtime information
        $overtime_information = $this->calculate_overtime($work_shift_details->is_weekend, $work_hours_delta, $total_paid_hours, $leave_record, $holiday, $attendance->attendance_records)["overtime_hours"];


        return $overtime_information;
    }


    public function is_special_user($user, $setting_attendance)
    {
        return $setting_attendance
            ->special_users()
            ->where(["setting_attendance_special_users.user_id" => $user->id])
            ->first();
    }

    public function is_special_role($user, $setting_attendance)
    {
        $role_names = $user->getRoleNames()->toArray();
        $roles = Role::whereIn("name", $role_names)->get();

        foreach ($roles as $role) {
            $special_role = $setting_attendance->special_roles()->where(["role_id" => $role->id])->first();
            if ($special_role) {
                return true;
            }
        }
        return false;
    }

    public function find_attendance($attendance_query_params)
    {
        $attendance =  Attendance::where($attendance_query_params)->first();
        if (!$attendance) {
            throw new Exception("Some thing went wrong");
        }
        return $attendance;
    }

    public function calculate_behavior_counts($attendances)
    {
        return [
            'absent' => $attendances->filter(fn($attendance) => $attendance->behavior === 'absent')->count(),
            'regular' => $attendances->filter(fn($attendance) => $attendance->behavior === 'regular')->count(),
            'early' => $attendances->filter(fn($attendance) => $attendance->behavior === 'early')->count(),
            'late' => $attendances->filter(fn($attendance) => $attendance->behavior === 'late')->count(),
        ];
    }

    public function calculate_max_behavior($behaviorCounts)
    {
        return max($behaviorCounts);
    }
}
