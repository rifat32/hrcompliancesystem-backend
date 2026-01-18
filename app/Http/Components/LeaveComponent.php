<?php

namespace App\Http\Components;

use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Models\Attendance;
use App\Models\Business;
use App\Models\EmployeeLeaveAllowance;
use App\Models\Leave;
use App\Models\LeaveApproval;
use App\Models\LeaveRecord;
use App\Models\SettingLeave;
use App\Models\SettingLeaveType;
use App\Models\User;
use Carbon\Carbon;
use Exception;


class LeaveComponent
{

    use BasicUtil, BusinessUtil;

    protected $authorizationComponent;
    protected $departmentComponent;

    protected $attendanceComponent;
    protected $workTimeManagementComponent;

    public function __construct(AuthorizationComponent $authorizationComponent,  DepartmentComponent $departmentComponent,  AttendanceComponent $attendanceComponent, WorkTimeManagementComponent $workTimeManagementComponent)
    {
        $this->authorizationComponent = $authorizationComponent;
        $this->departmentComponent = $departmentComponent;

        $this->attendanceComponent = $attendanceComponent;
        $this->workTimeManagementComponent = $workTimeManagementComponent;

    }

    public function processLeaveApproval($leave, $is_approved = 0)
    {

        if (auth()->user()->hasRole("business_owner")) {
            if ($is_approved) {
                $leave->status = "approved";
            } else {
                $leave->status = "rejected";
            }
            $leave->save();
            return;
        }
        $user = auth()->user();
        $leave = Leave::where([
            "id" => $leave->id,
            "business_id" => auth()->user()->business_id
        ])
            ->first();


        if (!$leave->employee) {

            throw new Exception("No Employee for the leave found", 400);
        }




        $setting_leave = SettingLeave::where([
            "business_id" => auth()->user()->business_id,
            "is_default" => 0
        ])->first();





        if ($setting_leave->approval_level == "single") {



            if ($is_approved) {
                $leave->status = "approved";
            } else {
                $leave->status = "rejected";
            }



        }
        if ($setting_leave->approval_level == "multiple") {



            $all_parent_departments_manager_of_user   = $this->all_parent_departments_manager_of_user($leave->user_id, $user->busines_id);

            $not_approved_manager_found =   LeaveApproval::where([
                'leave_id' => $leave->id,
                'is_approved' => 0,

            ])
                ->whereIn("created_by", $all_parent_departments_manager_of_user)
                ->exists();

            if (!$not_approved_manager_found) {
                $leave->status = "rejected";
            } else {
                if ($is_approved) {
                    $leave->status = "approved";
                } else {
                    $leave->status = "rejected";
                }
            }



            if (auth()->user()->hasRole("business_owner")) {
                if ($is_approved) {
                    $leave->status = "approved";
                } else {
                    $leave->status = "rejected";
                }
            }
        }

        $leave->save();
    }

    public function prepare_data_on_leave_create($raw_data, $user_id)
    {
        $raw_data["user_id"] = $user_id;
        $raw_data["business_id"] = auth()->user()->business_id;
        $raw_data["is_active"] = true;
        $raw_data["created_by"] = auth()->user()->id;
        $raw_data["status"] = (auth()->user()->hasRole("business_owner") ? "approved" : "pending_approval");

        return $raw_data;
    }

    public function get_leave_start_date($raw_data)
    {
        if ($raw_data["leave_duration"] == "multiple_day") {
            $work_shift_start_date = $raw_data["start_date"];
        } else {
            $work_shift_start_date = $raw_data["date"];
        }
    }

    public function findLeave($leave_id = NULL, $user_id, $date)
    {
        $leave =    Leave::where([
            "user_id" => $user_id
        ])
            ->when(!empty($leave_id), function ($query) use ($leave_id) {
                $query->whereNotIn("id", [$leave_id]);
            })
            ->whereHas('records', function ($query) use ($date) {
                $query->where('leave_records.date', ($date));
            })->first();
        return $leave;
    }


    // it will give empty for multiday leave and throw error for single day.
    public function getLeaveRecordDataItem(
        $work_shift_history,
        $work_shift_details,
        $holiday,
        $previous_leave,
        $previous_attendance,
        $date,
        $leave_duration,
        $day_type = "",
        $start_time = "",
        $end_time = "",
        $leave_data,
        $user_joining_date,
        $termination

    ) {

        // check termination
        $terminationCheck = $this->checkJoinAndTerminationDate($user_joining_date, $date, $termination);

        // Check if it's feasible to take leave
        if (!empty($terminationCheck["success"]) && (empty($work_shift_details->is_weekend) && (empty($holiday) || empty($holiday->is_active)) && empty($previous_leave))) {

            // Convert shift times to Carbon instances
            // $leave_start_at = Carbon::createFromFormat('H:i:s', $work_shift_details->start_at);
            // $leave_end_at = Carbon::createFromFormat('H:i:s', $work_shift_details->end_at);

            $leave_start_at = NULL;
            $leave_end_at = NULL;

            $leave_hours = $work_shift_details->schedule_hour;

            $capacity_hours = (!empty($work_shift_details->is_weekend))
                ? 0
                : (
                    ($work_shift_history->break_type != "paid")
                    ? ($work_shift_details->schedule_hour - $work_shift_history->break_hours)
                    : $work_shift_details->schedule_hour
                );



            // Calculate capacity hours based on work shift details




            if ($leave_duration == "hours") {
                // Use specified start and end times for leave
                $leave_start_at = Carbon::createFromFormat('H:i:s', $start_time);
                $leave_end_at = Carbon::createFromFormat('H:i:s', $end_time);
                // Calculate leave hours based on adjusted start and end times
                $leave_seconds = $leave_end_at->diffInSeconds($leave_start_at);
                $leave_hours = round($leave_seconds / 3600, 2);
            }


            $leave_record_data["work_shift_history_id"] = $work_shift_details->work_shift_id;

            // Prepare leave record data
            $leave_record_data["leave_hours"] =  $leave_hours;
            $leave_record_data["capacity_hours"] =  $capacity_hours;
            $leave_record_data["start_time"] = $leave_start_at;
            $leave_record_data["end_time"] = $leave_end_at;
            $leave_record_data["date"] = $date;
            $leave_record_data["id"] = !empty($leave_data["id"]) ? $leave_data["id"] : NULL;


            return $leave_record_data;
        }
        // Check for conditions preventing leave
        if ($leave_duration != "multiple_day") {
            $formatted_date = Carbon::parse($date)->format('d/m/Y');
            if (empty($terminationCheck)) {
                throw new Exception(("there is a termination date mismatch on date " . $formatted_date), 400);
            }

            if ($work_shift_details->is_weekend) {
                throw new Exception(("there is a weekend on date " . $formatted_date), 400);
            }
            if ($holiday && $holiday->is_active) {
                throw new Exception(("there is a holiday on date " . $formatted_date), 400);
            }
            if ($previous_leave) {
                throw new Exception(("there is a leave exists on date " . $formatted_date . json_encode($previous_leave)), 400);
            }

            // if ($previous_attendance) {
            //     throw new Exception(("there is an attendance exists on date " . $date), 400);
            // }

        }

        return [];
    }



    public function validateLeaveTimes($workShiftDetails, $start_time, $end_time)
    {

        $start_time = Carbon::parse($start_time);
        $end_time = Carbon::parse($end_time);



        $matched = false;  // Initialize a flag

        foreach ($workShiftDetails->shifts as $shift) {
            $start_at = Carbon::parse($shift["start_at"]);
            $end_at = Carbon::parse($shift["end_at"]);

            // If the shift matches, set $matched to true and break out of the loop
            if ($start_time->gte($start_at) && $end_time->lte($end_at)) {
                $matched = true;
                break;
            }
        }

        // If no shift matched, throw an error
        if (!$matched) {
            throw new Exception(
                "Employee's working hours (" .
                    $start_time->format('l, d F Y h:i A') .  // Example: Monday, 21 August 2023 09:00 AM
                    " - " .
                    $end_time->format('l, d F Y h:i A') .
                    ") do not match any available shifts.",
                400
            );
        }
    }








    public function processLeave(
        $leave_data,
        $leave_date,
        &$leave_record_data_list,
        $user_joining_date,
        $termination
    ) {
        // Retrieve work shift history for the user and date
        $work_shift_history =  $this->workTimeManagementComponent->get_work_shift_history($leave_date, $leave_data["user_id"]);

        // if ($work_shift_history->type == "flexible") {
        //     throw new Exception("Leave request can not be created for flexible rota.", 401);
        // }

        // Retrieve work shift details based on work shift history and date
        $work_shift_details =  $this->workTimeManagementComponent->get_work_shift_details($work_shift_history, $leave_date,TRUE);


        $holiday = $this->workTimeManagementComponent->get_holiday_details($leave_date, $leave_data["user_id"]);

        $previous_leave = $this->findLeave(
            (!empty($leave_data["id"]) ? $leave_data["id"] : NULL),
            $leave_data["user_id"],
            $leave_date,

        );

        $previous_attendance = $this->attendanceComponent->checkAttendanceExists(NULL, $leave_data["user_id"], $leave_date);

        if ($leave_data["leave_duration"] == "hours") {
            $this->validateLeaveTimes($work_shift_details, $leave_data["start_time"], $leave_data["end_time"]);
        }

        $leave_record_data_item = $this->getLeaveRecordDataItem(
            $work_shift_history,
            $work_shift_details,
            $holiday,
            $previous_leave,
            $previous_attendance,
            $leave_date,
            $leave_data["leave_duration"],
            $leave_data["day_type"],
            $leave_data["start_time"] ?? NULL,
            $leave_data["end_time"] ?? NULL,
            $leave_data,
            $user_joining_date,
            $termination
        );
        if (!empty($leave_record_data_item)) {
            array_push($leave_record_data_list, $leave_record_data_item);
        }
    }


    public function processLeaveRequest($raw_data)
    {
        $leave_data =  !empty($raw_data["id"]) ? $raw_data : $this->prepare_data_on_leave_create($raw_data, $raw_data["user_id"]);
        $leave_record_data_list = [];


        $user = User::with("lastTermination")->where([
            "id" => $raw_data["user_id"]
        ])
            ->select("id", "joining_date")
            ->first();


        switch ($leave_data["leave_duration"]) {
            case "multiple_day":
                $leave_dates = $this->workTimeManagementComponent->generateDateRange($leave_data["start_date"], $leave_data["end_date"]);
                foreach ($leave_dates as $leave_date) {
                    $this->processLeave($leave_data, $leave_date, $leave_record_data_list, $user->joining_date, $user->lastTermination);
                }
                break;

            case "single_day":
            case "hours":
                $leave_data["start_date"] = Carbon::parse($leave_data["date"]);
                $leave_data["end_date"] = Carbon::parse($leave_data["date"]);
                $this->processLeave($leave_data, $leave_data["date"], $leave_record_data_list, $user->joining_date, $user->lastTermination);
                break;

            default:
                // Handle unsupported leave duration type
                break;
        }





        return [
            "leave_data" => $leave_data,
            "leave_record_data_list" => $leave_record_data_list,


        ];
    }










    public function getEmployeeLeaveAllowance($user_id, $leave_type_id, $year)
    {
        $firstDayOfYear = Carbon::createFromDate($year, 1, 1)->startOfDay();
        $firstDayOfNextYear = Carbon::createFromDate($year + 1, 1, 1)->startOfDay();


        $employeeLeaveAllowance =   EmployeeLeaveAllowance::where([
            "user_id" => $user_id,
            "setting_leave_type_id" => $leave_type_id
        ])
            ->whereDate("leave_start_date", ">=", $firstDayOfYear)
            ->whereDate("leave_start_date", "<", $firstDayOfNextYear)
            ->first();


          return $employeeLeaveAllowance;
    }

    public function getSettingLeave()
    {
        $setting_leave = SettingLeave::where('business_id', auth()->user()->business_id)
            ->where('is_default', 0)
            ->first();

        if (empty($setting_leave)) {
            $this->loadDefaultSettingLeave(Business::find(auth()->user()->business_id));
            $setting_leave = SettingLeave::where('business_id', auth()->user()->business_id)
                ->where('is_default', 0)
                ->first();
        }

        return $setting_leave;
    }

    public function getLeaveDates($setting_leave, $year)
    {
        // Use the passed year to set the start month and first day of that month
        $leave_start_date = Carbon::create($year, $setting_leave->start_month, 1);

        // Calculate leave expiry date (1 year after the start of leave)
        $leave_expiry_date = $leave_start_date->copy()->addYear()->subDay();

        return [$leave_start_date, $leave_expiry_date];
    }

    public function createEmployeeLeaveAllowance($user_id, $leave_type, $leave_start_date, $leave_expiry_date)
    {

        return EmployeeLeaveAllowance::create([
            'user_id' => $user_id,
            'setting_leave_type_id' => $leave_type->id,
            'total_leave_hours' => $leave_type->amount,
            'used_leave_hours' => 0,
            'carry_over_hours' => 0,
            'leave_start_date' => $leave_start_date,
            'leave_expiry_date' => $leave_expiry_date,
        ]);
    }





    public function manipulateLeaveAllowance($user_id, $leave_type, $year)
    {

        $setting_leave = $this->getSettingLeave();

        // Get employee's leave allowance or default to setting leave if not found

        $employee_leave_allowance = $this->getEmployeeLeaveAllowance($user_id, $leave_type->id,$year);


        // If no allowance, fetch the setting leave or apply default settings
        if (empty($employee_leave_allowance)) {

            // If still no setting found, return response with an error
            if (empty($setting_leave)) {
                return response()->json(["message" => "No leave setting found."]);
            }

            // Set the default start month if not already set
            $setting_leave->start_month = $setting_leave->start_month ?? 1;

            // Get leave year start and expiry date
            list($leave_start_date, $leave_expiry_date) = $this->getLeaveDates($setting_leave, $year);

            // Create a new employee leave allowance entry
            $employee_leave_allowance = $this->createEmployeeLeaveAllowance($user_id, $leave_type, $leave_start_date, $leave_expiry_date);

        }


        return  $employee_leave_allowance;
        // You may want to use $employee_leave_allowance for further actions.
    }

    public function addLeaveAvailability($leave, $total_recorded_hours)
    {
        $leave_allowance = $leave->employee_leave_allowance;

        $user = $leave->employee;
        $remainingLeaveHours = $this->calculateProportionalLeave($leave_allowance, $user->joining_date) - $leave_allowance->used_leave_hours;

        if ($total_recorded_hours > $remainingLeaveHours) {
            $errorMessage = "You have exceeded the available leave hours. Recorded hours: $total_recorded_hours, Available hours: $remainingLeaveHours";
            throw new Exception($errorMessage, 409);
        }

        $leave_allowance->used_leave_hours = $leave_allowance->used_leave_hours + $total_recorded_hours;
        $leave_allowance->save();
    }

    public function deleteLeaveAvailability($leave, $total_leave_hours)
    {
        $leave_allowance = $leave->employee_leave_allowance;
        $leave_allowance->used_leave_hours = $leave_allowance->used_leave_hours - $total_leave_hours;
        $leave_allowance->save();




    }


    public function createLeaveAvailability($leave, $leave_records, $year)
    {

        // Calculate total recorded leave hours
        $total_recorded_hours = $leave_records->sum('leave_hours');
        $user = $leave->employee;


        $leave_allowance = $this->manipulateLeaveAllowance($user->id, $leave->leave_type, $year);



        $remainingLeaveHours = $this->calculateProportionalLeave($leave_allowance, $user->joining_date) - $leave_allowance->used_leave_hours;

        if ($total_recorded_hours > $remainingLeaveHours) {
            $errorMessage = "You have exceeded the available leave hours. Recorded hours: $total_recorded_hours, Available hours: $remainingLeaveHours";
            throw new Exception($errorMessage, 409);
        }

        $leave_allowance->used_leave_hours = $leave_allowance->used_leave_hours + $total_recorded_hours;
        $leave_allowance->save();

        $leave->total_leave_hours = $total_recorded_hours;
        $leave->employee_leave_allowance_id = $leave_allowance->id;
        $leave->save();

        // You may want to use $employee_leave_allowance for further actions.
    }

    public function calculateLeaveDates($year)
    {

        $setting_leave = $this->getSettingLeave();
        list($leave_start_date, $leave_expiry_date) = $this->getLeaveDates($setting_leave, $year);


        return [
           "leave_start_date" => $leave_start_date,
           "leave_expiry_date" => $leave_expiry_date
        ];
    }

    public function getLeaveDetailsByUserIdfunc($id,$year, $all_manager_department_ids)
    {
        // get appropriate use if auth user have access
        $user = $this->validateUserQuery($id, $all_manager_department_ids);

        $created_by  = NULL;
        if (auth()->user()->business) {
            $created_by = auth()->user()->business->created_by;
        }

        $leave_types =   SettingLeaveType::where(function ($query) use ($user, $created_by) {
            $query->where('setting_leave_types.business_id', auth()->user()->business_id)
                ->where('setting_leave_types.is_default', 0)
                ->where('setting_leave_types.is_active', 1)

                ->where(function ($query) use ($user) {
                    $query->whereHas("employment_statuses", function ($query) use ($user) {
                        if ($user->employment_status && $user->employment_status->id) {
                            $query->whereIn("employment_statuses.id", [$user->employment_status->id]);
                        }
                    })
                        ->orWhereDoesntHave("employment_statuses");
                });
        })
            ->get();



        foreach ($leave_types as $key => $leave_type) {

        $leave_allowance = $this->manipulateLeaveAllowance($user->id, $leave_type, $year);


        $leave_types[$key]->already_taken_hours = $leave_allowance->used_leave_hours;

        $leave_types[$key]->proportional_entitlement = $this->calculateProportionalLeave($leave_allowance, $user->joining_date);
        $leave_types[$key]->leave_allowance = $leave_allowance;

        }

        return $leave_types;
    }

    public function calculateProportionalLeave($leave_allowance, $joining_date)
    {
        $joining_date = Carbon::parse($joining_date);
        $leave_start_date = Carbon::parse($leave_allowance->leave_start_date);

        $yearly_entitlement = ($leave_allowance->total_leave_hours + $leave_allowance->carry_over_hours);

        if ($joining_date->lt($leave_start_date)) {
            return $yearly_entitlement;
        }

        // Calculate proportional leave entitlement based on the join date
        $day_of_year = $leave_start_date->diffInDays($joining_date);
        $remaining_days_in_year = 366 - $day_of_year;

        // Calculate per day entitlement and proportional entitlement
        $per_day_entitlement = $yearly_entitlement / 365;
        $proportional_entitlement = round($remaining_days_in_year * $per_day_entitlement, 2);

        return round($proportional_entitlement);
    }


    public function validateLeaveAvailability($leave)
    {

        $user = $leave->employee;

        $setting_leave = SettingLeave::where('setting_leaves.business_id', auth()->user()->business_id)
            ->where('setting_leaves.is_default', 0)
            ->first();
        if (empty($setting_leave)) {
            $this->loadDefaultSettingLeave(Business::where(["id" => auth()->user()->business_id])->first());
            $setting_leave = SettingLeave::where('setting_leaves.business_id', auth()->user()->business_id)
                ->where('setting_leaves.is_default', 0)
                ->first();
            if (empty($setting_leave)) {
                return response()->json(
                    ["message" => "No leave setting found."]
                );
            }
        }
        if (empty($setting_leave->start_month)) {
            $setting_leave->start_month = 1;
        }
        $startOfMonth = Carbon::create(null, $setting_leave->start_month, 1, 0, 0, 0)->subYear();
        // $paid_leave_available = in_array($user->employment_status_id, $setting_leave->paid_leave_employment_statuses()->pluck("employment_statuses.id")->toArray());

        $join_date = Carbon::parse($user->joining_date);

        $leave_type =   SettingLeaveType::where([
            "id" => $leave->leave_type_id
        ])

            ->first();

        if (empty($leave_type)) {
            throw new Exception("No leave type found");
        }

        $yearly_entitlement = $leave_type->amount ?? 0;
        if ($join_date->lt($startOfMonth)) {
            $proportional_entitlement = $yearly_entitlement;
        } else {
            // Calculate proportional leave entitlement
            // Calculate proportional leave entitlement based on the join date
            $day_of_year = $startOfMonth->diffInDays($join_date); // Days between join date and start of leave year
            $remaining_days_in_year = 365 - $day_of_year;
            $per_day_entitlement = $yearly_entitlement / 365;

            $proportional_entitlement = round($remaining_days_in_year * $per_day_entitlement, 2); // Round to 2 decimal places
            $proportional_entitlement = number_format($proportional_entitlement, 2, '.', ''); // Ensure it's formatted to 2 decimals

        }


        // Calculate already taken hours
        $leave_records = LeaveRecord::whereHas('leave', function ($query) use ($user, $leave_type) {
            $query->where([
                'user_id' => $user->id,
                'leave_type_id' => $leave_type->id,
            ]);
        })
            ->whereDate('leave_records.date', '>=', $startOfMonth)
            ->get();



        $total_recorded_hours = $leave_records->sum('leave_hours');



        if ($total_recorded_hours > $proportional_entitlement) {
            $total_hours = floor($total_recorded_hours);
            $total_minutes = round(($total_recorded_hours - $total_hours) * 60);
            $formatted_time = sprintf("%02d:%02d", $total_hours, $total_minutes);

            throw new Exception("You cannot take leave hours more than available. Currently {$formatted_time} hours available.", 403);
        }
    }










    public function updateLeavesQuery($all_manager_department_ids, $query)
    {

        $query = $query
            ->when(!empty(request()->ids), function ($query) {
                $idsArray = explode(',', request()->ids);
                $query->whereIn('leaves.id', $idsArray);
            })
            ->when(!empty(request()->search_key), function ($query) {
                $query->where(function ($query) {
                    $term = request()->search_key;
                    // $query->where("leaves.name", "like", "%" . $term . "%")
                    //     ->orWhere("leaves.description", "like", "%" . $term . "%");
                });
            })
          
            ->when(!empty(request()->user_id), function ($query) {
                $query->where('leaves.user_id', request()->user_id);
            })


            ->when(
                request()->boolean('show_all_data'),
                function ($query) use ($all_manager_department_ids) {
                    $query->where(function ($query) use ($all_manager_department_ids) {

                        $query->where('leaves.user_id', auth()->user()->id)
                            ->orWhere(function ($query) use ($all_manager_department_ids) {
                                $query->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                                    $query->whereIn("departments.id", $all_manager_department_ids);
                                })
                                    ->whereNotIn('leaves.user_id', [auth()->user()->id]);
                            });
                    });
                },
                function ($query) use ($all_manager_department_ids) {
                    $query->when(
                        (request()->boolean('show_my_data')),
                        function ($query) {
                            $query->where('leaves.user_id', auth()->user()->id);
                        },
                        function ($query) use ($all_manager_department_ids,) {

                            $query->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                                $query->whereIn("departments.id", $all_manager_department_ids);
                            })
                                ->whereNotIn('leaves.user_id', [auth()->user()->id]);;
                        }
                    );
                }
            )




            // ->when(empty(request()->user_id), function ($query)  {
            //     return $query->whereHas("employee", function ($query) {
            //         $query->whereNotIn("users.id", [auth()->user()->id]);
            //     });
            // })
            ->when(!empty(request()->leave_type_id), function ($query) {
                $query->where('leaves.leave_type_id', request()->leave_type_id);
            })
            ->when(!empty(request()->status), function ($query) {
                $query->where('leaves.status', request()->status);
            })
            ->when((request()->filled("department_id")), function ($query) {
                $query->whereHas("employee.departments", function ($query) {
                    $query->where("departments.id", request()->department_id);
                });
            })

            ->whereHas('records', function ($query) {
                $query
                    ->when(!empty(request()->start_date), function ($query) {
                        $query->whereDate('leave_records.date', '>=', request()->start_date);
                    })
                    ->when(!empty(request()->end_date), function ($query) {
                        $query->whereDate('leave_records.date', '<=', request()->end_date);
                    });
            });

        return $query;
    }


    public function getLeaveV4Func()
    {
        $all_manager_department_ids = $this->departmentComponent->get_all_departments_of_manager();



        $leavesQuery =  Leave::with([
            "employee" => function ($query) {
                $query->select(
                    'users.id',
                    "users.title",
                    'users.first_Name',
                    'users.middle_Name',
                    'users.last_Name',
                    'users.image'
                );
            },
            "employee.departments" => function ($query) {
                // You can select specific fields from the departments table if needed
                $query->select(
                    'departments.id',
                    'departments.name',
                    //  "departments.location",
                    "departments.description"
                );
            },
            "leave_type" => function ($query) {
                $query->select(
                    'setting_leave_types.id',
                    'setting_leave_types.name',
                    'setting_leave_types.type',
                    'setting_leave_types.amount',

                );
            },

        ]);
        $leavesQuery =   $this->updateLeavesQuery($all_manager_department_ids, $leavesQuery);
        $leaves = $this->retrieveData($leavesQuery, "id", "leaves");




        foreach ($leaves as $leave) {

            $leave_record_ids = $leave->records->pluck("id");

            $leave->is_attedance_exists = Attendance::whereIn("leave_record_id",$leave_record_ids)->exists();
        }

        $data["data"] = $leaves;

        $data["data_highlights"] = [];



        $data["data_highlights"]["leave_approved_hours"] = $leaves->filter(function ($leave) {
            return ($leave->status == "approved");
        })->sum('total_leave_hours');

        $data["data_highlights"]["leave_approved_total_individual_days"] = $leaves->filter(function ($leave) {

            return ($leave->status == "approved");
        })->sum(function ($leave) {
            return $leave->records->count();
        });


        $data["data_highlights"]["pending_leaves"] = $leaves->filter(function ($leave) {
            return ($leave->status == "pending_approval");
        })->count();

        return $data;

    }
}
