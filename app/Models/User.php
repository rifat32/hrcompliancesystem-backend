<?php

namespace App\Models;

use App\Http\Utils\BasicUtil;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;
use Exception;

class User extends Authenticatable
{
    use   Billable, HasApiTokens, HasFactory, Notifiable, HasRoles, HasPermissions, BasicUtil;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $connection = 'mysql';


    protected $appends = ['has_this_project', "manages_department", "will_exit"];

    protected $guard_name = "api";

    protected $fillable = [
        'title',
        'first_Name',
        'middle_Name',
        'last_Name',
        "NI_number",
        "pension_eligible",
        "color_theme_name",
        'emergency_contact_details',
        'gender',
        'is_in_employee',
        'designation_id',
        'employment_status_id',
        'joining_date',
        'salary_per_annum',
        'weekly_contractual_hours',
        'minimum_working_days_per_week',
        'overtime_rate',
        'phone',
        'image',
        'address_line_1',
        'address_line_2',
        'country',
        'city',
        'postcode',
        "lat",
        "long",
        'email',
        'user_time_zone',
        'password',
        'is_sponsorship_offered',
        "date_of_birth",
        "immigration_status",
        "is_active_visa_details",
        "is_active_right_to_works",
        'bank_id',
        'sort_code',
        'account_number',
        'account_name',
        'business_id',
        'user_id',
        "created_by",
        'is_active'
    ];

    public function getFullNameAttribute()
    {
        return trim("{$this->title} {$this->first_Name} {$this->middle_Name} {$this->last_Name}");
    }

    public function getWillExitAttribute($value)
    {
        $terminationExists = $this->lastTermination()
            ->whereDate('date_of_termination', '>=', now()->toDateString())
            ->exists();

        return $terminationExists ? 1 : 0;
    }

    public function getIsActiveAttribute($value)
    {
        $last_termination = $this->lastTermination()
            ->whereDate('date_of_termination', '<', today())
            ->first();

        if (!empty($last_termination) && !empty($this->joining_date)) {
            $current_joining_date = Carbon::parse($this->joining_date);
            $last_date_of_termination = Carbon::parse($last_termination->date_of_termination);

            if ($current_joining_date->lte($last_date_of_termination)) {
                return false;
            }

            if ($current_joining_date->gt(today())) {
                return false;
            }
        }

        return $value;
    }



    public function terminations()
    {
        return $this->hasMany(Termination::class);
    }

    public function lastTermination()
    {
        return $this->hasOne(Termination::class)->orderByDesc("id");
    }

    // Relationships
    public function exitInterviews()
    {
        return $this->hasMany(ExitInterview::class);
    }



    public function getHasThisProjectAttribute($value)
    {
        $request = request();
        // You can now use $currentRequest as the request object
        $has_this_project = $request->input('has_this_project');


        if (empty($has_this_project)) {
            return NULL;
        }
        $project = Project::whereHas("users", function ($query) {
            $query->where("users.id", $this->id);
        })
            ->where([
                "id" => $has_this_project
            ])
            ->first();

        return $project ? 1 : 0;
    }


    public function getManagesDepartmentAttribute($value)
    {

        $all_departments = $this->get_all_departments_of_manager();

        return count($all_departments) > 0;
    }


    public function bank()
    {
        return $this->hasOne(Bank::class, "id", 'bank_id');
    }

    public function payrun_users()
    {
        return $this->hasMany(PayrunUser::class, "user_id", 'id');
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class, "user_id", 'id');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'user_projects', 'user_id', 'project_id');
    }

    public function work_locations()
    {
        return $this->belongsToMany(WorkLocation::class, 'user_work_locations', 'user_id', 'work_location_id');
    }

    public function holidays()
    {
        return $this->hasMany(Holiday::class, 'business_id', 'business_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'id');
    }

    public function resold_businesses()
    {
        return $this->hasMany(Business::class, 'reseller_id', 'id');
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_users', 'user_id', 'department_id');
    }

    public function manager_departments()
    {
        return $this->hasMany(Department::class, 'manager_id', 'id');
    }

    public function recursive_department_users()
    {
        return $this->hasMany(Department::class, 'manager_id', 'id')
            ->whereNotNull("manager_id")
            ->with([
                "manager",
                "recursive_manager_users",
            ]);
    }

    public function department_user()
    {
        return $this->belongsTo(DepartmentUser::class,  'id', 'user_id');
    }

    public function recruitment_processes()
    {
        return $this->hasMany(UserRecruitmentProcess::class, 'user_id', 'id');
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class, 'designation_id', 'id');
    }

    public function employment_status()
    {
        return $this->belongsTo(EmploymentStatus::class, 'employment_status_id', 'id');
    }

    public function work_shifts()
    {
        return $this->belongsToMany(WorkShift::class, 'user_work_shifts', 'user_id', 'work_shift_id');
    }

    public function work_shift_histories()
    {
        return $this->hasMany(WorkShiftHistory::class, 'user_id', 'id');
    }


    public function current_work_shift_history()
    {
        return $this->hasOne(WorkShiftHistory::class, 'user_id', 'id')
            ->where(function ($query) {
                $query->where("from_date", "<=", today())
                    ->where(function ($query) {
                        $query->where("to_date", ">=", today())
                            ->orWhereNull("to_date");
                    });
            })
            ->orderByDesc("work_shift_histories.id");
    }



    public function employee_rota()
    {
        return $this->hasOne(EmployeeRota::class, 'user_id', 'id');
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class, 'user_id', 'id');
    }
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'user_id', 'id');
    }

    public function attendance_records()
    {
        return $this->hasManyThrough(AttendanceRecord::class, Attendance::class, 'user_id', 'attendance_id', 'id', 'id');
    }
    public function last_attendance_record()
    {
        return $this->hasOneThrough(
            AttendanceRecord::class,
            Attendance::class,
            'user_id',         // Foreign key on attendances table
            'attendance_id',   // Foreign key on attendance_records table
            'id',              // Local key on users table
            'id'               // Local key on attendances table
        )
            ->orderByDesc("attendance_records.id"); // Or 'recorded_at' if that’s your column
    }

    public function attendance_histories()
    {
        return $this->hasMany(AttendanceHistory::class, 'user_id', 'id');
    }



    public function sponsorship_details()
    {
        return $this->hasOne(EmployeeSponsorshipHistory::class, 'user_id', 'id')
            ->where('employee_sponsorship_histories.is_current', 1);
    }

    public function all_sponsorship_details()
    {

        return $this->hasMany(EmployeeSponsorshipHistory::class, 'user_id', 'id');
    }

    public function employee_informations()
    {
        return $this->hasOne(EmployeeInformation::class, 'user_id', 'id');
    }

    public function pension_details()
    {
        return $this->hasOne(EmployeePensionHistory::class, 'user_id', 'id')
            ->where('employee_pension_histories.is_current', 1);
    }


    public function all_pension_details()
    {
        return $this->hasMany(EmployeePensionHistory::class, 'user_id', 'id');
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class, 'user_id', 'id');
    }



    public function passport_details()
    {
        return $this->hasOne(EmployeePassportDetailHistory::class, 'user_id', 'id')
            ->where('employee_passport_detail_histories.is_current', 1);
    }


    public function all_passport_details()
    {
        return $this->hasMany(EmployeePassportDetailHistory::class, 'user_id', 'id');
    }





    public function visa_details()
    {
        return $this->hasOne(EmployeeVisaDetailHistory::class, 'user_id', 'id')
            ->where('employee_visa_detail_histories.is_current', 1);
    }

    public function all_visa_details()
    {
        return $this->hasMany(EmployeeVisaDetailHistory::class, 'user_id', 'id');
    }



    public function right_to_works()
    {
        return $this->hasOne(EmployeeRightToWorkHistory::class, 'user_id', 'id')
            ->where('employee_right_to_work_histories.is_current', 1)
        ;
    }


    public function all_right_to_works()
    {
        return $this->hasMany(EmployeeRightToWorkHistory::class, 'user_id', 'id');
    }


    public function assets()
    {
        return $this->hasMany(UserAsset::class, 'user_id', 'id');
    }
    public function documents()
    {
        return $this->hasMany(UserDocument::class, 'user_id', 'id');
    }
    public function education_histories()
    {
        return $this->hasMany(UserEducationHistory::class, 'user_id', 'id');
    }
    public function job_histories()
    {
        return $this->hasMany(UserJobHistory::class, 'user_id', 'id');
    }

    public function notes()
    {
        return $this->hasMany(UserNote::class, 'user_id', 'id');
    }

    public function letters()
    {
        return $this->hasMany(UserLetter::class, 'user_id', 'id');
    }

    public function social_links()
    {
        return $this->hasMany(UserSocialSite::class, 'user_id', 'id');
    }

    public function scopeFilterUser($query, $all_manager_department_ids)
    {

        $today = today();
        return $query
            ->when(
                !request()->boolean("allow_self"),
                function ($query) use ($today) {
                    $query->whereNotIn('id', [auth()->user()->id]);
                },
            )


            ->when(empty(auth()->user()->business_id), function ($query) {
                if (auth()->user()->hasRole("superadmin")) {
                    return  $query->where(function ($query) {
                        return   $query->where('business_id', NULL)
                            ->orWhere(function ($query) {
                                return $query
                                    ->whereNotNull("business_id")
                                    ->whereHas("roles", function ($query) {
                                        return $query->where("roles.name", "business_owner");
                                    });
                            });
                    });
                } else {
                    return  $query->where(function ($query) {
                        return   $query->where('created_by', auth()->user()->id);
                    });
                }
            })


            ->when(!empty(auth()->user()->business_id), function ($query) use ($all_manager_department_ids) {
                return $query
                    ->when(
                        empty(request()->project_id) && empty(request()->boolean("project_wise")),
                        function ($query) use ($all_manager_department_ids) {
                            return $query->where(function ($query) use ($all_manager_department_ids) {
                                return  $query->where('business_id', auth()->user()->business_id)
                                    ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                                        $query->whereIn("departments.id", $all_manager_department_ids);
                                    })
                                    ->when(request()->boolean("allow_self"), function ($query) {
                                        $query->orWhereIn("users.id", [auth()->user()->id]);
                                    });
                            });
                        }
                    );
            })
            ->when(!empty(request()->role), function ($query) {
                $rolesArray = explode(',', request()->role);
                return   $query->whereHas("roles", function ($q) use ($rolesArray) {
                    return $q->whereIn("name", $rolesArray);
                });
            })

            ->when(!empty(request()->not_in_rota), function ($query) {
                $query->whereDoesntHave("employee_rota");
            })


            ->when(request()->filled("work_shift_assignable_date"), function ($query) {
                $query
                    ->whereDoesntHave("attendances", function ($query) {
                        $query->whereDate("in_date", ">=", request()->input("work_shift_assignable_date"));
                    })
                    ->whereDoesntHave("leaves.records", function ($query) {
                        $query->whereDate("leave_records.date", ">=", request()->input("work_shift_assignable_date"));
                    });
            })

            ->when(!empty(request()->full_name), function ($query) {
                // Replace spaces with commas and create an array
                $searchTerms = explode(',', str_replace(' ', ',', request()->full_name));

                $query->where(function ($query) use ($searchTerms) {
                    foreach ($searchTerms as $term) {
                        $query->orWhere(function ($subquery) use ($term) {
                            $subquery->where("first_Name", "like", "%" . $term . "%")
                                ->orWhere("title", "like", "%" . $term . "%")
                                ->orWhere("last_Name", "like", "%" . $term . "%")
                                ->orWhere("middle_Name", "like", "%" . $term . "%");
                        });
                    }
                });
            })
            ->when(!empty(request()->user_id), function ($query) {
                return   $query->where([
                    "user_id" => request()->user_id
                ]);
            })
            ->when(!empty(request()->employee_id), function ($query) {
                $employee_id = explode(',', request()->employee_id);
                return   $query->whereIn(
                    "users.user_id",
                    $employee_id
                );
            })
            ->when(!empty(request()->ids), function ($query) {
                $idsArray = explode(',', request()->ids);
                return   $query->whereIn(
                    "id",
                    $idsArray
                );
            })


            ->when(!empty(request()->email), function ($query) {
                return   $query->where([
                    "email" => request()->email
                ]);
            })
            ->when(!empty(request()->NI_number), function ($query) {
                return   $query->where([
                    "NI_number" => request()->NI_number
                ]);
            })
            ->when(!empty(request()->gender), function ($query) {
                return   $query->where([
                    "gender" => request()->gender
                ]);
            })




            ->when(request()->filled('salary_per_annum'), function ($query) {
                // Split the input into an array of numbers, replacing spaces with commas
                $numbers = explode(',', request()->input('salary_per_annum'));

                // Define start and end values
                $startValue = isset($numbers[0]) && trim($numbers[0]) !== '' ? trim($numbers[0]) : null;
                $endValue = isset($numbers[1]) && trim($numbers[1]) !== '' ? trim($numbers[1]) : null;

                // Apply conditions based on which values are available
                if ($startValue) {
                    $query->where(
                        "salary_per_annum",
                        ">=",
                        $startValue
                    );
                }

                if ($endValue) {
                    $query->where(
                        "salary_per_annum",
                        "<=",
                        $endValue
                    );
                }

                return $query;
            })



            ->when(!empty(request()->designation_id), function ($query) {
                $idsArray = explode(',', request()->designation_id);
                return $query->whereIn('designation_id', $idsArray);
            })
            ->when(!empty(request()->designation_ids), function ($query) {
                $idsArray = explode(',', request()->designation_ids);
                return $query->whereIn('designation_id', $idsArray);
            })



            ->when(request()->filled('weekly_contractual_hours'), function ($query) {
                // Split the input into an array of numbers, replacing spaces with commas
                $numbers = explode(',', request()->input('weekly_contractual_hours'));

                // Define start and end values
                $startValue = isset($numbers[0]) && trim($numbers[0]) !== '' ? trim($numbers[0]) : null;
                $endValue = isset($numbers[1]) && trim($numbers[1]) !== '' ? trim($numbers[1]) : null;

                // Apply conditions based on which values are available
                if ($startValue) {
                    $query->where(
                        "weekly_contractual_hours",
                        ">=",
                        $startValue
                    );
                }

                if ($endValue) {
                    $query->where(
                        "weekly_contractual_hours",
                        "<=",
                        $endValue
                    );
                }

                return $query;
            })


            ->when(!empty(request()->employment_status_id), function ($query) {
                $idsArray = explode(',', request()->employment_status_id);
                return $query->whereIn('employment_status_id', ($idsArray));
            })

            ->when(!empty(request()->search_key), function ($query) {
                $searchKey = request()->search_key;
                $searchTerms = explode(',', str_replace(' ', ',', $searchKey));

                return $query->where(function ($subquery) use ($searchKey, $searchTerms) {
                    // Search by email and phone
                    $subquery->where(function ($query) use ($searchKey) {
                        $query->where("email", "like", "%" . $searchKey . "%")
                            ->orWhere("phone", "like", "%" . $searchKey . "%");
                    })
                        ->orWhere(function ($query) use ($searchTerms) {
                            foreach ($searchTerms as $term) {
                                $term = trim($term); // Trim whitespace around each term
                                if (!empty($term)) { // Avoid empty terms
                                    $query->orWhere(function ($subquery) use ($term) {
                                        $subquery->where("first_Name", "like", "%" . $term . "%")
                                            ->orWhere("title", "like", "%" . $term . "%")
                                            ->orWhere("last_Name", "like", "%" . $term . "%")
                                            ->orWhere("middle_Name", "like", "%" . $term . "%");
                                    });
                                }
                            }
                        });
                });
            })




            ->when(!empty(request()->upcoming_expiries), function ($query) {

                if (request()->upcoming_expiries == "passport") {
                    return  $query->whereHas("passport_details", function ($query) {
                        $query->where("employee_passport_detail_histories.passport_expiry_date", ">=", today());
                    });
                } else if (request()->upcoming_expiries == "visa") {
                    return $query->whereHas("visa_details", function ($query) {
                        $query->where("employee_visa_detail_histories.visa_expiry_date", ">=", today());
                    });
                } else if (request()->upcoming_expiries == "right_to_work") {
                    return  $query->whereHas("right_to_works", function ($query) {
                        $query->where("employee_right_to_work_histories.right_to_work_expiry_date", ">=", today());
                    });
                } else if (request()->upcoming_expiries == "sponsorship") {
                    return  $query->whereHas("sponsorship_details", function ($query) {
                        $query->where("employee_sponsorship_histories.expiry_date", ">=", today());
                    });
                } else if (request()->upcoming_expiries == "pension") {
                    return $query->whereHas("pension_details", function ($query) {
                        $query->where("employee_pensions.pension_re_enrollment_due_date", ">=", today());
                    });
                }
            })


            ->when(!empty(request()->immigration_status), function ($query) {
                $immigration_statuses = explode(',', request()->immigration_status);
                return $query->whereIn('immigration_status', $immigration_statuses);
            })



            ->when(!empty(request()->project_id), function ($query) {
                return $query->whereHas("projects", function ($query) {
                    $idsArray = explode(',', request()->project_id);
                    $query->whereIn("projects.id", $idsArray);
                });
            })
            ->when((request()->filled("department_id")), function ($query) {
                return $query->whereHas("departments", function ($query) {
                    $idsArray = explode(',', request()->department_id);
                    $query->whereIn("departments.id", $idsArray);
                });
            })



            ->when(!empty(request()->work_location_ids), function ($query) {
                $work_location_ids = explode(',', request()->work_location_ids);
                return   $query->whereHas("work_locations", function ($q) use ($work_location_ids) {
                    return $q->whereIn("work_locations.id", $work_location_ids);
                });
            })

            ->when(request()->filled("is_active"), function ($query) {
                return $query->where('is_active', request()->boolean("is_active"));
            })
            ->when(
                request()->boolean("is_terminated"),
                function ($query) {

                    return $query
                        ->whereHas("lastTermination", function ($query) {
                            $query
                                // ->where('terminations.date_of_termination', "<", today())
                                ->where(function ($query) {
                                    $query->whereRaw('terminations.date_of_termination > users.joining_date')
                                        ->orWhere("users.joining_date", ">", today());
                                });
                        });
                },
                function ($query) {

                    return $query
                        //    ->where("users.joining_date","<=",today())
                        ->whereDoesntHave("lastTermination", function ($query) {
                            $query->where('terminations.date_of_termination', "<", today())
                                ->whereRaw('terminations.date_of_termination > users.joining_date');
                        });
                },

            )

            ->when(request()->filled("joining_date"), function ($query) {

                // Split the date range string into start and end dates
                $dates = explode(',', request()->input("joining_date"));
                $startDate = !empty(($dates[0])) ? Carbon::parse(trim($dates[0])) : "";
                $endDate = !empty(($dates[1])) ? Carbon::parse(trim($dates[1])) : "";

                // Apply conditions based on which dates are available
                if ($startDate) {
                    $query->where('joining_date', '>=', $startDate);
                }

                if ($endDate) {
                    $query->where('joining_date', '<=', $endDate);
                }
                return $query;
            })

            ->when((
                request()->filled("pension_scheme_status")
                ||

                request()->filled("pension_issue_date")
                ||
                request()->filled("pension_re_enrollment_date")
                ||
                request()->filled("pension_re_enrollment_due_date_in_day")


            ), function ($query) use ($today) {
                return $query->whereHas("pension_details", function ($query) use ($today) {
                    $query
                        ->when(!empty(request()->pension_scheme_status), function ($query) {

                            $query->where("employee_pension_histories.pension_scheme_status", request()->pension_scheme_status);
                        })

                        ->when(request()->filled("pension_issue_date"), function ($query) {
                            // Split the date range string into start and end dates
                            $dates = explode(',', request()->input("pension_issue_date"));
                            $startDate = !empty(($dates[0])) ? Carbon::parse(trim($dates[0]))->format('Y-m-d') : "";
                            $endDate = !empty(($dates[1])) ? Carbon::parse(trim($dates[1]))->format('Y-m-d') : "";

                            return $query
                                ->when(!empty($startDate), function ($query) use ($startDate) {
                                    $query->whereDate('employee_pension_histories.pension_enrollment_issue_date', '>=', $startDate);
                                })
                                ->when(!empty($endDate), function ($query) use ($endDate) {
                                    $query->whereDate('employee_pension_histories.pension_enrollment_issue_date', '<=', $endDate);
                                });
                        })
                        ->when(request()->filled("pension_re_enrollment_date"), function ($query) {
                            // Split the date range string into start and end dates
                            $dates = explode(',', request()->input("pension_re_enrollment_date"));
                            $startDate = !empty(($dates[0])) ? Carbon::parse(trim($dates[0]))->format('Y-m-d') : "";
                            $endDate = !empty(($dates[1])) ? Carbon::parse(trim($dates[1]))->format('Y-m-d') : "";

                            return $query
                                ->when(!empty($startDate), function ($query) use ($startDate) {
                                    $query->whereDate('employee_pension_histories.pension_re_enrollment_due_date', '>=', $startDate);
                                })
                                ->when(!empty($endDate), function ($query) use ($endDate) {
                                    $query->whereDate('employee_pension_histories.pension_re_enrollment_due_date', '<=', $endDate);
                                });
                        })
                        ->when(!empty(request()->pension_pension_re_enrollment_due_date_in_day), function ($query) use ($today) {
                            $query_day = Carbon::now()->addDays(request()->pension_re_enrollment_due_date_in_day);
                            $query->whereBetween("employee_pension_histories.pension_re_enrollment_due_date", [$today, ($query_day->endOfDay() . ' 23:59:59')]);
                        });
                });
            })


            ->when((
                request()->filled("BRP_number")
                ||

                request()->filled("visa_expiry_date")
                ||
                request()->filled("visa_issue_date")
                ||
                request()->filled("visa_expires_in_day")


            ), function ($query) use ($today) {
                return $query->whereHas("visa_details", function ($query) use ($today) {
                    $query
                        ->when(!empty(request()->BRP_number), function ($query) {
                            return $query->where("employee_visa_detail_histories.BRP_number", request()->BRP_number);
                        })

                        ->when(request()->filled("visa_issue_date"), function ($query) {
                            // Split the date range string into start and end dates
                            $dates = explode(',', request()->input("visa_issue_date"));
                            $startDate = !empty(($dates[0])) ? Carbon::parse(trim($dates[0]))->format('Y-m-d') : "";
                            $endDate = !empty(($dates[1])) ? Carbon::parse(trim($dates[1]))->format('Y-m-d') : "";

                            return $query
                                ->when(!empty($startDate), function ($query) use ($startDate) {
                                    $query->whereDate('employee_visa_detail_histories.visa_issue_date', '>=', $startDate);
                                })
                                ->when(!empty($endDate), function ($query) use ($endDate) {
                                    $query->whereDate('employee_visa_detail_histories.visa_issue_date', '<=', $endDate);
                                });
                        })
                        ->when(request()->filled("visa_expiry_date"), function ($query) {
                            // Split the date range string into start and end dates
                            $dates = explode(',', request()->input("visa_expiry_date"));
                            $startDate = !empty(($dates[0])) ? Carbon::parse(trim($dates[0]))->format('Y-m-d') : "";
                            $endDate = !empty(($dates[1])) ? Carbon::parse(trim($dates[1]))->format('Y-m-d') : "";

                            return $query
                                ->when(!empty($startDate), function ($query) use ($startDate) {
                                    $query->whereDate('employee_visa_detail_histories.visa_expiry_date', '>=', $startDate);
                                })
                                ->when(!empty($endDate), function ($query) use ($endDate) {
                                    $query->whereDate('employee_visa_detail_histories.visa_expiry_date', '<=', $endDate);
                                });
                        })
                        ->when(!empty(request()->visa_expires_in_day), function ($query) use ($today) {
                            $query_day = Carbon::now()->addDays(request()->visa_expires_in_day);
                            return $query->whereBetween("employee_visa_detail_histories.visa_expiry_date", [$today, ($query_day->endOfDay() . ' 23:59:59')]);
                        });
                });
            })

            ->when((
                request()->filled("right_to_work_code")
                ||
                request()->filled("right_to_work_check_date")
                ||
                request()->filled("right_to_work_expiry_date")
                ||
                request()->filled("right_to_work_expires_in_day")

            ), function ($query) use ($today) {
                return $query->whereHas("right_to_works", function ($query) use ($today) {

                    $query
                        ->when(request()->filled("right_to_work_code"), function ($query) {
                            $query->where("employee_right_to_work_histories.right_to_work_code", request()->right_to_work_code);
                        })
                        ->when(request()->filled("right_to_work_check_date"), function ($query) {
                            // Split the date range string into start and end dates
                            $dates = explode(',', request()->input("right_to_work_check_date"));
                            $startDate = !empty(($dates[0])) ? Carbon::parse(trim($dates[0]))->format('Y-m-d') : "";
                            $endDate = !empty(($dates[1])) ? Carbon::parse(trim($dates[1]))->format('Y-m-d') : "";

                            return $query
                                ->when(!empty($startDate), function ($query) use ($startDate) {
                                    $query->whereDate('employee_right_to_work_histories.right_to_work_check_date', '>=', $startDate);
                                })
                                ->when(!empty($endDate), function ($query) use ($endDate) {
                                    $query->whereDate('employee_right_to_work_histories.right_to_work_check_date', '<=', $endDate);
                                });
                        })
                        ->when(request()->filled("right_to_work_expiry_date"), function ($query) {
                            // Split the date range string into start and end dates
                            $dates = explode(',', request()->input("right_to_work_expiry_date"));
                            $startDate = !empty(($dates[0])) ? Carbon::parse(trim($dates[0]))->format('Y-m-d') : "";
                            $endDate = !empty(($dates[1])) ? Carbon::parse(trim($dates[1]))->format('Y-m-d') : "";
                            
                           

                            return $query
                                ->when(!empty($startDate), function ($query) use ($startDate) {
                                    $query->whereDate('employee_right_to_work_histories.right_to_work_expiry_date', '>=', $startDate);
                                })
                                ->when(!empty($endDate), function ($query) use ($endDate) {
                                    $query->whereDate('employee_right_to_work_histories.right_to_work_expiry_date', '<=', $endDate);
                                });
                        })




                        ->when(!empty(request()->right_to_work_expires_in_day), function ($query) use ($today) {

                            $query_day = Carbon::now()->addDays(request()->right_to_work_expires_in_day);
                            $query->whereBetween("employee_right_to_work_histories.right_to_work_expiry_date", [$today, ($query_day->endOfDay() . ' 23:59:59')]);
                        })

                    ;
                });
            })

            ->when((
                request()->filled("passport_number")
                ||
                request()->filled("passport_issue_date")
                ||
                request()->filled("passport_expiry_date")
                ||
                request()->filled("passport_expires_in_day")

            ), function ($query) use ($today) {
                return $query->whereHas("passport_details", function ($query) use ($today) {

                    $query
                        ->when(request()->filled("passport_number"), function ($query) {
                            $query->where("employee_passport_detail_histories.passport_number", request()->passport_number);
                        })
                        ->when(request()->filled("passport_issue_date"), function ($query) {
                            // Split the date range string into start and end dates
                            $dates = explode(',', request()->input("passport_issue_date"));
                            $startDate = !empty(($dates[0])) ? Carbon::parse(trim($dates[0]))->format('Y-m-d') : "";
                            $endDate = !empty(($dates[1])) ? Carbon::parse(trim($dates[1]))->format('Y-m-d') : "";

                            return $query
                                ->when(!empty($startDate), function ($query) use ($startDate) {
                                    $query->whereDate('employee_passport_detail_histories.passport_issue_date', '>=', $startDate);
                                })
                                ->when(!empty($endDate), function ($query) use ($endDate) {
                                    $query->whereDate('employee_passport_detail_histories.passport_issue_date', '<=', $endDate);
                                });
                        })
                        ->when(request()->filled("passport_expiry_date"), function ($query) {
                            // Split the date range string into start and end dates
                            $dates = explode(',', request()->input("passport_expiry_date"));
                            $startDate = !empty(($dates[0])) ? Carbon::parse(trim($dates[0]))->format('Y-m-d') : "";

                            $endDate = !empty(($dates[1])) ? Carbon::parse(trim($dates[1]))->format('Y-m-d') : "";

                            return $query
                                ->when(!empty($startDate), function ($query) use ($startDate) {
                                    $query->whereDate('employee_passport_detail_histories.passport_expiry_date', '>=', $startDate);
                                })
                                ->when(!empty($endDate), function ($query) use ($endDate) {
                                    $query->whereDate('employee_passport_detail_histories.passport_expiry_date', '<=', $endDate);
                                });
                        })


                        ->when(!empty(request()->passport_expires_in_day), function ($query) use ($today) {

                            $query_day = Carbon::now()->addDays(request()->passport_expires_in_day);
                            $query->whereBetween("employee_passport_detail_histories.passport_expiry_date", [$today, ($query_day->endOfDay() . ' 23:59:59')]);
                        })

                    ;
                });
            })



            ->when((
                request()->filled("sponsorship_status")
                ||
                request()->filled("sponsorship_note")
                ||
                request()->filled("sponsorship_certificate_number")
                ||
                request()->filled("sponsorship_current_certificate_status")
                ||
                request()->filled("sponsorship_is_sponsorship_withdrawn")
                ||

                request()->filled("sponsorship_date_assigned")
                ||
                request()->filled("sponsorship_expiry_date")
                ||
                request()->filled("sponsorship_expires_in_day")

            ), function ($query) use ($today) {
                return $query->whereHas("sponsorship_details", function ($query) use ($today) {

                    $query
                        ->when(!empty(request()->sponsorship_status), function ($query) {
                            $query->where("employee_sponsorship_histories.status", request()->sponsorship_status);
                        })

                        ->when(!empty(request()->sponsorship_note), function ($query) {

                            $query->where("employee_sponsorship_histories.note", request()->sponsorship_note);
                        })

                        ->when(!empty(request()->sponsorship_certificate_number), function ($query) {

                            $query->where("employee_sponsorship_histories.certificate_number", request()->sponsorship_certificate_number);
                        })
                        ->when(!empty(request()->sponsorship_current_certificate_status), function ($query) {

                            $query->where("employee_sponsorship_histories.current_certificate_status", request()->sponsorship_current_certificate_status);
                        })
                        ->when(isset(request()->sponsorship_is_sponsorship_withdrawn), function ($query) {

                            $query->where("employee_sponsorship_histories.is_sponsorship_withdrawn", intval(request()->sponsorship_is_sponsorship_withdrawn));
                        })

                        ->when(request()->filled("sponsorship_date_assigned"), function ($query) {
                            // Split the date range string into start and end dates
                            $dates = explode(',', request()->input("sponsorship_date_assigned"));
                            $startDate = !empty(($dates[0])) ? Carbon::parse(trim($dates[0]))->format('Y-m-d') : "";
                            $endDate = !empty(($dates[1])) ? Carbon::parse(trim($dates[1]))->format('Y-m-d') : "";

                            return $query
                                ->when(!empty($startDate), function ($query) use ($startDate) {
                                    $query->whereDate('employee_sponsorship_histories.date_assigned', '>=', $startDate);
                                })
                                ->when(!empty($endDate), function ($query) use ($endDate) {
                                    $query->whereDate('employee_sponsorship_histories.date_assigned', '<=', $endDate);
                                });
                        })
                        ->when(request()->filled("sponsorship_expiry_date"), function ($query) {
                            // Split the date range string into start and end dates
                            $dates = explode(',', request()->input("sponsorship_expiry_date"));
                            $startDate = !empty(($dates[0])) ? Carbon::parse(trim($dates[0]))->format('Y-m-d') : "";
                            $endDate = !empty(($dates[1])) ? Carbon::parse(trim($dates[1]))->format('Y-m-d') : "";

                            return $query
                                ->when(!empty($startDate), function ($query) use ($startDate) {
                                    $query->whereDate('employee_sponsorship_histories.expiry_date', '>=', $startDate);
                                })
                                ->when(!empty($endDate), function ($query) use ($endDate) {
                                    $query->whereDate('employee_sponsorship_histories.expiry_date', '<=', $endDate);
                                });
                        })


                        ->when(!empty(request()->sponsorship_expires_in_day), function ($query) use ($today) {
                            $query_day = Carbon::now()->addDays(request()->sponsorship_expires_in_day);
                            $query->whereBetween("employee_sponsorship_histories.expiry_date", [$today, ($query_day->endOfDay() . ' 23:59:59')]);
                        })

                    ;
                });
            })
            ->when((

                    request()->filled("onboarding_process_ids")
                    ||
                    request()->filled("onboarding_task_owner_ids")
                    ||
                    request()->filled("onboarding_task_statuses")
                    ||
                    request()->filled("onboarding_task_assigned_date")
                    ||
                    request()->filled("onboarding_task_due_date")

                    ||
                    request()->filled("onboarding_task_completion_date")
                ),
                function ($query) {
                    $query->whereHas("recruitment_processes", function ($query) {
                        $query->when(request()->filled("onboarding_process_ids"), function ($query) {
                            $idsArray = explode(',', request()->onboarding_process_ids);
                            $query->whereIn("user_recruitment_processes.recruitment_process_id", $idsArray);
                        })

                            ->when((
                                request()->filled("onboarding_task_owner_ids")
                                ||
                                request()->filled("onboarding_task_statuses")
                                ||
                                request()->filled("onboarding_task_assigned_date")
                                ||
                                request()->filled("onboarding_task_due_date")

                                ||
                                request()->filled("onboarding_task_completion_date")

                            ), function ($query) {
                                $query->whereHas("tasks", function ($query) {

                                    $query
                                        ->when(request()->filled("onboarding_task_owner_ids"), function ($query) {
                                            $onboarding_task_owner_ids = explode(',', request()->onboarding_task_owner_ids);
                                            $query->whereIn("onboarding_tasks.task_owner_id", $onboarding_task_owner_ids);
                                        })
                                        ->when(request()->filled("onboarding_task_statuses"), function ($query) {
                                            $onboarding_task_statuses = explode(',', request()->onboarding_task_statuses);
                                            $query->whereIn("onboarding_tasks.task_status", $onboarding_task_statuses);
                                        })
                                        ->when(request()->filled("onboarding_task_assigned_date"), function ($query) {
                                            // Split the date range string into start and end dates
                                            $dates = explode(',', request()->input("onboarding_task_assigned_date"));
                                            $startDate = !empty(($dates[0])) ? Carbon::parse(trim($dates[0]))->format('Y-m-d') : "";
                                            $endDate = !empty(($dates[1])) ? Carbon::parse(trim($dates[1]))->format('Y-m-d') : "";
                                            $query
                                                ->when(!empty($startDate), function ($query) use ($startDate) {
                                                    $query->whereDate('onboarding_tasks.assigned_date', '>=', $startDate);
                                                })
                                                ->when(!empty($endDate), function ($query) use ($endDate) {
                                                    $query->whereDate('onboarding_tasks.assigned_date', '<=', $endDate);
                                                });
                                        })

                                        ->when(request()->filled("onboarding_task_due_date"), function ($query) {

                                            $dates = explode(',', request()->input("onboarding_task_due_date"));
                                            $startDate = !empty(($dates[0])) ? Carbon::parse(trim($dates[0]))->format('Y-m-d') : "";
                                            $endDate = !empty(($dates[1])) ? Carbon::parse(trim($dates[1]))->format('Y-m-d') : "";
                                            $query
                                                ->when(!empty($startDate), function ($query) use ($startDate) {
                                                    $query->whereDate('onboarding_tasks.due_date', '>=', $startDate);
                                                })
                                                ->when(!empty($endDate), function ($query) use ($endDate) {
                                                    $query->whereDate('onboarding_tasks.due_date', '<=', $endDate);
                                                });
                                        })
                                        ->when(request()->filled("onboarding_task_completion_date"), function ($query) {
                                            $dates = explode(',', request()->input("onboarding_task_completion_date"));
                                            $startDate = !empty(($dates[0])) ? Carbon::parse(trim($dates[0]))->format('Y-m-d') : "";
                                            $endDate = !empty(($dates[1])) ? Carbon::parse(trim($dates[1]))->format('Y-m-d') : "";
                                            $query
                                                ->when(!empty($startDate), function ($query) use ($startDate) {
                                                    $query->whereDate('onboarding_tasks.completion_date', '>=', $startDate);
                                                })
                                                ->when(!empty($endDate), function ($query) use ($endDate) {
                                                    $query->whereDate('onboarding_tasks.completion_date', '<=', $endDate);
                                                });
                                        });
                                });
                            })
                        ;
                    });
                }
            )

            ->when((

                    request()->filled("termination_process_ids")
                    ||
                    request()->filled("termination_task_owner_ids")
                    ||
                    request()->filled("termination_task_statuses")
                    ||
                    request()->filled("termination_task_assigned_date")
                    ||
                    request()->filled("termination_task_due_date")

                    ||
                    request()->filled("termination_task_completion_date")
                ),
                function ($query) {
                    $query->whereHas("lastTermination.termination_processes", function ($query) {
                        $query->when(request()->filled("termination_process_ids"), function ($query) {
                            $idsArray = explode(',', request()->termination_process_ids);
                            $query->whereIn("termination_processes.recruitment_process_id", $idsArray);
                        })

                            ->when((
                                request()->filled("termination_task_owner_ids")
                                ||
                                request()->filled("termination_task_statuses")
                                ||
                                request()->filled("termination_task_assigned_date")
                                ||
                                request()->filled("termination_task_due_date")

                                ||
                                request()->filled("termination_task_completion_date")

                            ), function ($query) {
                                $query->whereHas("tasks", function ($query) {

                                    $query
                                        ->when(request()->filled("termination_task_owner_ids"), function ($query) {
                                            $termination_task_owner_ids = explode(',', request()->termination_task_owner_ids);
                                            $query->whereIn("termination_tasks.task_owner_id", $termination_task_owner_ids);
                                        })
                                        ->when(request()->filled("termination_task_statuses"), function ($query) {
                                            $termination_task_statuses = explode(',', request()->termination_task_statuses);
                                            $query->whereIn("termination_tasks.task_status", $termination_task_statuses);
                                        })
                                        ->when(request()->filled("termination_task_assigned_date"), function ($query) {
                                            // Split the date range string into start and end dates
                                            $dates = explode(',', request()->input("termination_task_assigned_date"));
                                            $startDate = !empty(($dates[0])) ? Carbon::parse(trim($dates[0]))->format('Y-m-d') : "";
                                            $endDate = !empty(($dates[1])) ? Carbon::parse(trim($dates[1]))->format('Y-m-d') : "";
                                            $query
                                                ->when(!empty($startDate), function ($query) use ($startDate) {
                                                    $query->whereDate('termination_tasks.assigned_date', '>=', $startDate);
                                                })
                                                ->when(!empty($endDate), function ($query) use ($endDate) {
                                                    $query->whereDate('termination_tasks.assigned_date', '<=', $endDate);
                                                });
                                        })

                                        ->when(request()->filled("termination_task_due_date"), function ($query) {

                                            $dates = explode(',', request()->input("termination_task_due_date"));
                                            $startDate = !empty(($dates[0])) ? Carbon::parse(trim($dates[0]))->format('Y-m-d') : "";
                                            $endDate = !empty(($dates[1])) ? Carbon::parse(trim($dates[1]))->format('Y-m-d') : "";
                                            $query
                                                ->when(!empty($startDate), function ($query) use ($startDate) {
                                                    $query->whereDate('termination_tasks.due_date', '>=', $startDate);
                                                })
                                                ->when(!empty($endDate), function ($query) use ($endDate) {
                                                    $query->whereDate('termination_tasks.due_date', '<=', $endDate);
                                                });
                                        })
                                        ->when(request()->filled("termination_task_completion_date"), function ($query) {
                                            $dates = explode(',', request()->input("termination_task_completion_date"));
                                            $startDate = !empty(($dates[0])) ? Carbon::parse(trim($dates[0]))->format('Y-m-d') : "";
                                            $endDate = !empty(($dates[1])) ? Carbon::parse(trim($dates[1]))->format('Y-m-d') : "";
                                            $query
                                                ->when(!empty($startDate), function ($query) use ($startDate) {
                                                    $query->whereDate('termination_tasks.completion_date', '>=', $startDate);
                                                })
                                                ->when(!empty($endDate), function ($query) use ($endDate) {
                                                    $query->whereDate('termination_tasks.completion_date', '<=', $endDate);
                                                });
                                        });
                                });
                            })
                        ;
                    });
                }
            )
            ->when(isset(request()->doesnt_have_payrun), function ($query) {
                if (intval(request()->doesnt_have_payrun)) {
                    return $query->whereDoesntHave("payrun_users");
                } else {
                    return $query;
                }
            })


            ->when((!empty(request()->leave_statuses) || !empty(request()->leave_date)), function ($query) {
                return $query->whereHas("leaves", function ($query) {
                    $leave_statuses = explode(',', request()->leave_statuses);
                    $query
                        ->when(!empty(request()->leave_date), function ($query) {
                            $data_pairs = explode(',', request()->leave_date);

                            $start_leave_date = !empty($data_pairs[0]) ? Carbon::parse($data_pairs[0])->format('Y-m-d') : "";
                            $end_leave_date = !empty($data_pairs[1]) ? Carbon::parse($data_pairs[1])->format('Y-m-d') : "";

                            $query->whereHas("records", function ($query) use ($start_leave_date, $end_leave_date) {
                                $query->when(!empty($start_leave_date), function ($query) use ($start_leave_date) {

                                    $query->whereDate('leave_records.date', '>=', $start_leave_date);
                                })
                                    ->when(!empty($end_leave_date), function ($query) use ($end_leave_date) {
                                        $query
                                            ->whereDate(
                                                'leave_records.date',
                                                '<=',
                                                $end_leave_date
                                            );
                                    });
                            });
                        })
                        ->when(!empty(request()->leave_status), function ($query) {
                            return $query->where("leaves.status", request()->leave_status);
                        });
                });
            });

        return $usersQuery;
    }



    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        "site_redirect_token",

        "email_verify_token",
        "email_verify_token_expires",
        "resetPasswordToken",
        "resetPasswordExpires"
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'emergency_contact_details' => 'array',
    ];
}
