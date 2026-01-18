<?php

namespace App\Jobs;

use App\Http\Utils\ErrorUtil;
use App\Http\Utils\PayrunUtil;
use App\Models\Attendance;
use App\Models\AttendanceArrear;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\LeaveRecord;
use App\Models\LeaveRecordArrear;
use App\Models\Payroll;
use App\Models\Payrun;
use App\Models\User;
use App\Models\WorkShift;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, PayrunUtil,ErrorUtil;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {




        // DB::transaction(function () {




            try {
                // Your job logic here



            $payruns = Payrun::where('is_active', true)->get();


            $payruns->each(function ($payrun)  {
                $employees = User::where([
                    "business_id" => $payrun->business_id,
                    "is_active" => 1
                ])


                // ->where(function($query) use($payrun) {
                //     $query->whereHas("departments.payrun_department",function($query) use($payrun) {
                //         $query->where("payrun_departments.payrun_id", $payrun->id);
                //     })
                //     ->orWhereHas("payrun_user", function($query) use($payrun)  {
                //         $query->where("payrun_users.payrun_id", $payrun->id);
                //     });
                // })

                    ->get();
                $this->process_payrun($payrun,$employees,$payrun->start_date,$payrun->end_date,false,true);
            });

        } catch (Exception $e) {
            // Log the exception to the database
            $this->storeError($e, 422, $e->getLine(), $e->getFile());
        }

            // foreach ($payruns as $payrun) {



            //     if (!$payrun->business_id) {
            //         continue;
            //     }

            //     $start_date = $payrun->start_date;
            //     $end_date = $payrun->end_date;
            //     // Set end_date based on period_type
            //     switch ($payrun->period_type) {
            //         case 'weekly':
            //             $start_date = Carbon::now()->startOfWeek()->subWeek(1);
            //             $end_date = Carbon::now()->startOfWeek();
            //             break;
            //         case 'monthly':
            //             $start_date = Carbon::now()->startOfMonth()->addMonth(1);
            //             $end_date = Carbon::now()->startOfMonth();
            //             break;
            //     }
            //     if (!$start_date || !$end_date) {
            //         continue; // Skip to the next iteration
            //     }

            //     // Convert end_date to Carbon instance
            //     $end_date = Carbon::parse($end_date);

            //     // Check if end_date is today
            //     if (!$end_date->isToday()) {
            //         continue; // Skip to the next iteration
            //     }

            //     $employees = User::where([
            //         "business_id" => $payrun->business_id,
            //         "is_active" => 1
            //     ])
            //         ->get();

            //     foreach ($employees as $employee) {
            //         $work_shift =   WorkShift::whereHas('users', function ($query) use ($employee) {
            //             $query->where('users.id', $employee->id);
            //         })->first();

            //         if (!$work_shift) {
            //            continue;
            //         }

            //         if (!$work_shift->is_active) {
            //             continue;
            //         }
            //         $work_shift_details = $work_shift->details()->get()->keyBy('day');


            //         $all_parent_department_ids = [];
            //         $assigned_departments = Department::whereHas("users", function ($query) use ($employee) {
            //             $query->where("users.id", $employee->id);
            //         })->get();
            //         foreach ($assigned_departments as $assigned_department) {
            //             $all_parent_department_ids = array_merge($all_parent_department_ids, $assigned_department->getAllParentIds());
            //         }
            //         $salary_per_annum = $employee->salary_per_annum; // in euros
            //         $weekly_contractual_hours = $employee->weekly_contractual_hours;
            //         $weeks_per_year = 52;
            //         $hourly_salary = $salary_per_annum / ($weeks_per_year * $weekly_contractual_hours);
            //         $overtime_salary = $employee->overtime_rate ? $employee->overtime_rate : $hourly_salary;

            //         $holiday_hours = $employee->weekly_contractual_hours / $employee->minimum_working_days_per_week;



            //         $attendance_arrears = Attendance::whereDoesntHave("payroll_attendance")
            //             ->where('attendances.user_id', $employee->id)

            //             ->where(function ($query) use ($start_date) {
            //                 $query->where(function ($query) use ($start_date) {
            //                     $query->whereNotIn("attendances.status", ["approved"])
            //                         ->where('attendances.in_date', '<=', today()->endOfDay())
            //                         ->where('attendances.in_date', '>=', $start_date);
            //                 })
            //                     ->orWhere(function ($query) use ($start_date) {
            //                         $query->whereDoesntHave("arrear")
            //                             ->where('attendances.in_date', '<=', $start_date);
            //                     });
            //             })
            //             ->get();

            //         foreach ($attendance_arrears as $attendance_arrear) {
            //             AttendanceArrear::create([
            //                 "status" => "pending_approval",
            //                 "attendance_id" => $attendance_arrear->id
            //             ]);
            //         }


            //         $leave_arrears = LeaveRecord::whereDoesntHave("payroll_leave_record")
            //             ->whereHas('leave',    function ($query) use ($employee) {
            //                 $query->where("leaves.user_id",  $employee->id);
            //             })

            //             ->where(function ($query) use ($start_date) {
            //                 $query->where(function ($query) use ($start_date) {
            //                     $query
            //                         ->whereHas('leave',    function ($query) {
            //                             $query->whereNotIn("leaves.status", ["approved"]);
            //                         })
            //                         ->where('leave_records.date', '<=', today()->endOfDay())
            //                         ->where('leave_records.date', '>=', $start_date);
            //                 })
            //                     ->orWhere(function ($query) use ($start_date) {
            //                         $query->whereDoesntHave("arrear")
            //                             ->where('leave_records.date', '<=', $start_date);
            //                     });
            //             })
            //             ->get();



            //         foreach ($leave_arrears as $leave_arrear) {
            //             LeaveRecordArrear::create([
            //                 "status" => "pending_approval",
            //                 "leave_record_id" => $leave_arrear->id
            //             ]);
            //         }



            //         $approved_attendances = Attendance::whereDoesntHave("payroll_attendance")
            //             ->where('attendances.user_id', $employee->id)
            //             ->where(function ($query) use ($start_date) {
            //                 $query->where(function ($query) use ($start_date) {
            //                     $query
            //                         ->where("attendances.status", "approved")
            //                         ->where('attendances.in_date', '<=', today()->endOfDay())
            //                         ->where('attendances.in_date', '>=', $start_date);
            //                 })
            //                     ->orWhere(function ($query) {
            //                         $query->whereHas("arrear", function ($query) {
            //                             $query->where("attendance_arrears.status", "approved");
            //                         });
            //                     });
            //             })
            //             ->get();



            //         $approved_leave_records = LeaveRecord::whereDoesntHave("payroll_leave_record")
            //             ->whereHas('leave',    function ($query) use ($employee) {
            //                 $query->where("leaves.user_id",  $employee->id);
            //             })
            //             ->where(function ($query) use ($start_date) {
            //                 $query->where(function ($query) use ($start_date) {
            //                     $query
            //                         ->whereHas('leave',    function ($query) {
            //                             $query
            //                                 ->where("leaves.status", "approved");
            //                         })
            //                         ->where('leave_records.date', '<=', today()->endOfDay())
            //                         ->where('leave_records.date', '>=', $start_date);
            //                 })
            //                     ->orWhere(function ($query) {
            //                         $query->whereHas("arrear", function ($query) {
            //                             $query->where("leave_record_arrears.status", "approved");
            //                         });
            //                     });
            //             })
            //             ->get();





            //         $holidays = Holiday::where([
            //             "business_id" => auth()->user()->business_id
            //         ])
            //             ->where('holidays.end_date', '<=', today()->endOfDay())
            //             ->where('holidays.end_date', '>=', $start_date)
            //             ->where([
            //                 "is_active" => 1
            //             ])

            //             ->where(function ($query) use ($employee, $all_parent_department_ids) {
            //                 $query->whereHas("users", function ($query) use ($employee) {
            //                     $query->where([
            //                         "users.id" => $employee->id
            //                     ]);
            //                 })
            //                     ->orWhereHas("department_user.department", function ($query) use ($all_parent_department_ids) {
            //                         $query->whereIn("departments.id", $all_parent_department_ids);
            //                     })

            //                     ->orWhere(function ($query) {
            //                         $query->whereDoesntHave("users")
            //                             ->whereDoesntHave("departments");
            //                     });
            //             })

            //             ->get();


            //         $total_paid_hours = 0;
            //         $total_balance_hours = 0;





            //         $payroll_attendances_data = collect();
            //         $payroll_leave_records_data = collect();
            //         $payroll_holidays_data = collect();

            //         $approved_attendances->each(function ($approved_attendance) use (&$total_paid_hours, &$total_balance_hours, $work_shift_details, $approved_leave_records, $holidays, &$payroll_attendances_data) {
            //             $payroll_attendances_data->push(["attendances" => $approved_attendance->id]);


            //             $attendance_in_date = Carbon::parse($approved_attendance->in_date)->format("Y-m-d");
            //             $day_number = Carbon::parse($attendance_in_date)->dayOfWeek;
            //             $work_shift_detail = $work_shift_details->get($day_number);
            //             $is_weekend = 1;
            //             $capacity_hours = 0;
            //             if ($work_shift_detail) {
            //                 $is_weekend = $work_shift_detail->is_weekend;
            //                 $work_shift_start_at = Carbon::createFromFormat('H:i:s', $work_shift_detail->start_at);
            //                 $work_shift_end_at = Carbon::createFromFormat('H:i:s', $work_shift_detail->end_at);
            //                 $capacity_hours = $work_shift_end_at->diffInHours($work_shift_start_at);
            //             }

            //             $leave_record = $approved_leave_records->first(function ($leave_record) use ($attendance_in_date) {
            //                 $leave_date = Carbon::parse($leave_record->date)->format("Y-m-d");
            //                 return $attendance_in_date == $leave_date;
            //             });
            //             $holiday = $holidays->first(function ($holiday) use ($attendance_in_date) {
            //                 $start_date = Carbon::parse($holiday->start_date);
            //                 $end_date = Carbon::parse($holiday->end_date);
            //                 $in_date = Carbon::parse($attendance_in_date);

            //                 // Check if $in_date is within the range of start_date and end_date
            //                 return $in_date->between($start_date, $end_date, true);
            //             });

            //             if ($approved_attendance->total_paid_hours > 0) {
            //                 $total_attendance_hours = $approved_attendance->total_paid_hours;
            //                 if ($leave_record || $holiday || $is_weekend) {
            //                     $result_balance_hours = $total_attendance_hours;
            //                 } elseif ($approved_attendance->work_hours_delta > 0) {
            //                     $result_balance_hours = $approved_attendance->work_hours_delta;
            //                 }
            //                 $total_paid_hours += $total_attendance_hours;
            //                 $total_balance_hours += $result_balance_hours;
            //             }
            //         });




            //         $approved_leave_records->each(function ($approved_leave_record) use (&$total_paid_hours, &$payroll_leave_records_data) {
            //             if ($approved_leave_record->leave->leave_type->type == "paid") {
            //                 $payroll_leave_records_data->push(["leave_record_id" => $approved_leave_record->id]);
            //                 $total_paid_hours += $approved_leave_record->leave_hours;
            //             }
            //         });


            //         $date_range = collect();

            //         $holidays->each(function ($holiday) use (&$date_range, $payroll_holidays_data) {
            //             $start_date = Carbon::parse($holiday->start_date);
            //             $end_date = Carbon::parse($holiday->end_date);

            //             while ($start_date->lte($end_date)) {
            //                 $current_date = $start_date->format("Y-m-d");
            //                 // Check if the date is not already in the collection before adding
            //                 if (!$date_range->contains($current_date)) {
            //                     $payroll_holidays_data->push(["holiday_id" => $holiday->id]);
            //                     $date_range->push($current_date);
            //                 }

            //                 $start_date->addDay();
            //             }
            //         });

            //         $total_paid_hours += $date_range->count() *  $holiday_hours;



            //         $payroll_data =  [
            //             'user_id' => $employee->id,
            //             "payrun_id" => $payrun->id,
            //             'regular_hours' => $total_paid_hours - $total_balance_hours,
            //             'overtime_hours' => $total_balance_hours,
            //             'regular_hours_salary' => ($total_paid_hours - $total_balance_hours) * $hourly_salary,
            //             'overtime_hours_salary' => $total_balance_hours * $overtime_salary,
            //             'status' => "pending_approval",
            //             'is_active' => 1,
            //             'business_id' => $employee->business_id,
            //         ];


            //         $payroll = Payroll::create($payroll_data);
            //         $payroll->payroll_holidays()->create($payroll_holidays_data);
            //         $payroll->payroll_leave_records()->create($payroll_leave_records_data);
            //         $payroll->payroll_attendances()->create($payroll_attendances_data);
            //     }
            // }










        // });

    }
}
