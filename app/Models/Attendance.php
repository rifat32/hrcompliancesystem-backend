<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $appends = ['is_in_arrears','has_multiday_presence'];

    protected $fillable = [
        "is_self_clocked_in",
        "is_clocked_in",
        'contractual_hours',
        'note',
        "in_geolocation",
        "out_geolocation",
        'user_id',
        'in_date',
        'does_break_taken',
        "consider_overtime",
        "behavior",
        "capacity_hours",
        "work_hours_delta",
        "break_type",
        "break_hours",
        "paid_break_hours",
        "unpaid_break_hours",
        "total_paid_hours",
        "regular_work_hours",
        "work_shift_history_id",
        "holiday_id",
        "leave_record_id",
        "is_weekend",
        "overtime_hours",
        "leave_hours",
        "punch_in_time_tolerance",
        "tolerance_time",
        "status",
        // 'work_location_id',
        "is_active",
        "business_id",
        "created_by",
        "regular_hours_salary",
        "overtime_hours_salary",
        // "attendance_records",

        "is_present",



    ];





    public function holiday()
    {
        return $this->hasOne(Holiday::class, 'id', 'holiday_id');
    }

    public function leave_record()
    {
        return $this->hasOne(LeaveRecord::class, 'id', 'leave_record_id');
    }


   // Define the relationship with AttendanceHistoryRecord
         public function attendance_records()
         {
             return $this->hasMany(AttendanceRecord::class, 'attendance_id');
         }




    public function getCapacityHoursAttribute($value)
    {
        if(!empty($this->holiday_id)) {
              return 0;
        }
            return $value;
    }


    public function getIsPresentAttribute($value)
    {
        if($this->status == "rejected") {
              return 0;
        }
            return $value;

    }

    public function getHasMultidayPresenceAttribute($value)
    {
        return AttendanceRecord::where("attendance_id", $this->id)
            ->where(function ($query) {
                $query->whereDate("in_time", "!=", $this->in_date)
                      ->orWhereDate("out_time", "!=", $this->in_date);
            })
            ->exists();
    }

    public function getIsInArrearsAttribute($value)
    {
        if($this->arrear()->where([
            "status" => "pending_approval"
        ])
        ->exists()){
            return true;
        }
        return false;
    }

    public function arrear()
    {
        return $this->hasOne(AttendanceArrear::class, 'attendance_id', 'id');
    }

    public function payroll_attendance()
    {
        return $this->hasOne(PayrollAttendance::class, "attendance_id", 'id');
    }

    public function employee()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }


    public function work_shift_history()
    {
        return $this->hasOne(WorkShiftHistory::class, 'id', 'work_shift_history_id');
    }


    public function scopeFilterAttendance($query,$all_manager_department_ids)
    {

        $attendancesQuery = $query
            ->where(
                [
                    "attendances.business_id" => auth()->user()->business_id
                ]
            )

            ->when(!empty(request()->ids), function ($query) {
                $idsArray = explode(',', request()->ids);
                return $query->whereIn('attendances.id', $idsArray);
            })
            ->when(!empty(request()->work_shift_id), function ($query) {
                $work_shift_ids = explode(',', request()->work_shift_id);
                $work_shift_history_ids = WorkShiftHistory::whereIn("work_shift_id",$work_shift_ids)
                    ->get()
                    ->pluck("id")
                    ->toArray();
                return $query->whereIn('attendances.work_shift_history_id', $work_shift_history_ids);
            })

            ->when(!empty(request()->search_key), function ($query) {
                return $query->where(function ($query) {
                    $term = request()->search_key;
                    // $query->where("attendances.name", "like", "%" . $term . "%")
                    //     ->orWhere("attendances.description", "like", "%" . $term . "%");
                });
            })
            ->when(!empty(request()->user_id), function ($query) {
                $idsArray = explode(',', request()->user_id);
                return $query->whereIn('attendances.user_id', $idsArray);
            })
            ->when(request()->boolean("is_clocked_in"), function ($query) {
                return $query->where('attendances.is_clocked_in', 1);
            })




            ->when(!empty(request()->overtime), function ($query) {
                $number_query = explode(',', str_replace(' ', ',', request()->overtime));
                return $query->where('attendances.overtime_hours', $number_query);
            })

            ->when(request()->boolean("is_late_attendance"), function ($query) {
                return $query->where('attendances.tolerance_time',">", 0);
            })

            ->when(!empty(request()->schedule_hour), function ($query) {
                $number_query = explode(',', str_replace(' ', ',', request()->schedule_hour));
                return $query->where('attendances.capacity_hours', $number_query);
            })

            ->when(!empty(request()->break_hour), function ($query) {
                $number_query = explode(',', str_replace(' ', ',', request()->break_hour));

                // Filter based on all break fields: break_hours, paid_break_hours, and unpaid_break_hours

                return $query->where(function ($subQuery) use ($number_query) {
                    $subQuery->whereIn('attendances.break_hours', $number_query)
                        ->orWhereIn('attendances.paid_break_hours', $number_query)
                        ->orWhereIn('attendances.unpaid_break_hours', $number_query);
                });
            })

            ->when(!empty(request()->worked_hour), function ($query) {
                $number_query = explode(',', str_replace(' ', ',', request()->worked_hour));
                return $query->where('attendances.total_paid_hours', $number_query[0], $number_query[1]);
            })




            ->when(!empty(request()->project_id), function ($query) {
                $idsArray = explode(',', request()->project_id);
                return $query->whereHas('attendance_records.projects', function ($query) use ($idsArray) {
                    $query->whereIn("projects.id", $idsArray);
                });
            })

            ->when((request()->filled("work_location_id")), function ($query) {
                return $query->whereHas('attendance_records', function ($query) {
                    $query->where('attendance_records.work_location_id', request()->work_location_id);
                });
            })



            ->when(!empty(request()->status), function ($query) {
                return $query->where('attendances.status', request()->status);
            })

            ->when((request()->filled("department_id"))||!empty(request()->designation_ids), function ($query) {
                return $query->whereHas("employee", function ($query) {
                    $query->when((request()->filled("department_id")), function ($query) {
                        return $query->whereHas("departments", function ($query) {
                            $department_id = explode(',', request()->department_id);
                            $query->whereIn("departments.id", $department_id);
                        });
                    })
                    ->when(!empty(request()->designation_ids), function ($query) {
                        $designation_ids = explode(',', request()->designation_ids);
                        return  $query->whereIn("users.designation_id", $designation_ids);

                    });

                });
            })

            ->when(
                request()->boolean('show_all_data'),
                function ($query) use ($all_manager_department_ids) {
                    return $query->where(function ($query) use ($all_manager_department_ids) {
                        $query->where('attendances.user_id', auth()->user()->id)
                            ->orWhere(function ($query) use ($all_manager_department_ids) {
                                $query->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                                    $query->whereIn("departments.id", $all_manager_department_ids);
                                })
                                    ->whereNotIn('attendances.user_id', [auth()->user()->id]);
                            });
                    });
                },
                function ($query) use ($all_manager_department_ids) {
                    return $query->when(
                        (request()->has('show_my_data') && intval(request()->show_my_data) == 1),
                        function ($query) {
                            $query->where('attendances.user_id', auth()->user()->id);
                        },
                        function ($query) use ($all_manager_department_ids) {

                            $query->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
                                $query->whereIn("departments.id", $all_manager_department_ids);
                            })
                                ->whereNotIn('attendances.user_id', [auth()->user()->id]);
                        }
                    );
                }
            )


            ->when(!empty(request()->start_date), function ($query) {
                return $query->whereDate('attendances.in_date', ">=", request()->start_date);
            })
            ->when(!empty(request()->end_date), function ($query) {
                $endDate = Carbon::parse(request()->end_date);

                // Check if end_date is in the future and set to today's date if true
                if ($endDate->isFuture()) {
                    $endDate = now()->toDateString();
                } else {
                    $endDate = $endDate->toDateString();
                }
                return $query->whereDate('attendances.in_date', "<=", $endDate);
            });

        return $attendancesQuery;
    }


}
