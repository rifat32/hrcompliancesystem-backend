<?php

namespace App\Http\Controllers;

use App\Http\Components\DepartmentComponent;
use App\Http\Components\WorkTimeManagementComponent;

use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Business;
use App\Models\Department;
use App\Models\EmployeePassportDetailHistory;
use App\Models\EmployeePensionHistory;
use App\Models\EmployeeRightToWorkHistory;
use App\Models\EmployeeSponsorshipHistory;
use App\Models\EmployeeVisaDetailHistory;
use App\Models\EmploymentStatus;
use App\Models\Holiday;
use App\Models\JobListing;
use App\Models\LeaveRecord;
use App\Models\Notification;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkShiftDetailHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardManagementController extends Controller
{
    use ErrorUtil, BusinessUtil, BasicUtil;

    protected $workTimeManagementComponent;
    protected $departmentComponent;
    public function __construct(
        WorkTimeManagementComponent $workTimeManagementComponent,
        DepartmentComponent $departmentComponent
    ) {

        $this->workTimeManagementComponent = $workTimeManagementComponent;
        $this->departmentComponent = $departmentComponent;
    }



    public function presentHours($date_ranges)
    {
        $authUserId = auth()->user()->id;
        $authUser = auth()->user();

        if (request()->input("duration") == "this_week") {

            $week_dates = $this->getWeekDates();
            $start_date_of_this_week = Carbon::parse($week_dates["start_date_of_this_week"]);
            $end_date_of_this_week = Carbon::parse($week_dates["end_date_of_this_week"]);

            if (!empty($authUser->joining_date)) {
                $date_of_termination = $authUser->lastTermination->date_of_termination ?? NULL;
                $dates = $this->manipulateJoiningDateTerminationDate($authUser->joining_date, $date_of_termination, $start_date_of_this_week, $end_date_of_this_week);
                $start_date_of_this_week = Carbon::parse($dates["start_date"]);
                $end_date_of_this_week = Carbon::parse($dates["end_date"]);
            }



            $weeklyAttendance = Attendance::where('is_present', 1)
                ->where('user_id', $authUserId)
                ->whereBetween('in_date', [$start_date_of_this_week->startOfDay(), $end_date_of_this_week->endOfDay()])
                ->select('id', 'total_paid_hours', 'break_hours', 'paid_break_hours', 'unpaid_break_hours', 'in_date', "regular_work_hours")
                ->get();

            $weekData = [];
            $attendance_dates = $this->workTimeManagementComponent->generateDateRange($start_date_of_this_week, $end_date_of_this_week);
            foreach ($attendance_dates as $date) {

                $date = Carbon::parse($date);
                $dateTitle = $date->format('Y-m-d');

                $attendanceRecord = $weeklyAttendance->firstWhere('in_date', $date->toDateString());

                $workingHours = $attendanceRecord->total_paid_hours ?? 0;
                $regularWorkingHours = $attendanceRecord->regular_work_hours ?? 0;
                $breakHours = $attendanceRecord->break_hours ?? 0;

                $total_schedule_hours = $this->workTimeManagementComponent
                    ->getScheduleInformationData(
                        $authUserId,
                        $authUser->joining_date,
                        $authUser->lastTermination->date_of_termination ?? null,
                        $date,
                        $date
                    )['total_capacity_hours'];

                $data = [
                    'name' => $date->format('D'),
                    'working_hours' => $workingHours,
                    'regular_working_hours' => $regularWorkingHours,
                    'break_hours' => -number_format($breakHours, 2),
                    'date_title' => $dateTitle,
                    'total_absent_hours' => $date->isPast() ? ($total_schedule_hours - $regularWorkingHours) : 0,
                    'total_shift_hours' => $total_schedule_hours,
                ];

                if ($date->isFuture()) {
                    $data["total_schedule_hours"] = $total_schedule_hours;
                }

                $weekData[] = $data;
            }

            return $weekData;
        }

        if (request()->input("duration") == "this_month") {
           $start_date_of_this_month = $date_ranges["start_date_of_this_month"];
            $end_date_of_this_month = $date_ranges["end_date_of_this_month"];

            if (!empty($authUser->joining_date)) {
                $date_of_termination = $authUser->lastTermination->date_of_termination ?? NULL;
                $dates = $this->manipulateJoiningDateTerminationDate($authUser->joining_date, $date_of_termination, $start_date_of_this_month, $end_date_of_this_month);
                $start_date_of_this_month = Carbon::parse($dates["start_date"]);
                $end_date_of_this_month = Carbon::parse($dates["end_date"]);
            }

            $monthlyAttendance = Attendance::where('is_present', 1)
                ->where('user_id', $authUserId)
                ->whereBetween('in_date', [$start_date_of_this_month, $end_date_of_this_month->endOfDay()])
                ->select('id', 'total_paid_hours', 'break_hours', 'paid_break_hours', 'unpaid_break_hours', 'in_date', "regular_work_hours")
                ->get();

            $monthData = [];

            for ($date = clone $start_date_of_this_month; $date->lte($end_date_of_this_month); $date->addDay()) {
                $dateTitle = $date->format('Y-m-d');
                $dayName = $date->format('d');

                $attendanceRecord = $monthlyAttendance->firstWhere('in_date', $date->toDateString());

                $workingHours = $attendanceRecord->total_paid_hours ?? 0;
                $regularWorkingHours = $attendanceRecord->regular_work_hours ?? 0;
                $breakHours = $attendanceRecord->break_hours ?? 0;

                $total_schedule_hours = $this->workTimeManagementComponent
                    ->getScheduleInformationData(
                        $authUserId,
                        $authUser->joining_date,
                        $authUser->lastTermination->date_of_termination ?? null,
                        $date,
                        $date
                    )['total_capacity_hours'];

                $data = [
                    'name' => $dayName,
                    'working_hours' => $workingHours,
                    'regular_working_hours' => $regularWorkingHours,
                    'break_hours' => -number_format($breakHours, 2),
                    'date_title' => $dateTitle,
                    'total_absent_hours' => $date->isPast() ? ($total_schedule_hours - $regularWorkingHours) : 0,
                    'total_schedule_hours_v2' => $total_schedule_hours,
                ];



                if ($date->isFuture()) {
                    $data["total_schedule_hours"] = $total_schedule_hours;
                }

                $monthData[] = $data;
            }

            return $monthData;
        }

        if (request()->input("duration") == "this_year") {
            if (empty(request()->input("year"))) {
                throw new Exception("year is required", 400);
            }

            $last12MonthsDates = $this->getLast12MonthsDates(request()->input("year"));
            $data = [];

            foreach ($last12MonthsDates as $month) {
                $start_date = Carbon::parse($month['start_date']);
                $end_date = Carbon::parse($month['end_date']);

                if (!empty($authUser->joining_date)) {
                    $date_of_termination = $authUser->lastTermination->date_of_termination ?? NULL;
                    $dates = $this->manipulateJoiningDateTerminationDate($authUser->joining_date, $date_of_termination, $start_date, $end_date);
                    $start_date = Carbon::parse($dates["start_date"]);
                    $end_date = Carbon::parse($dates["end_date"]);

                    if ($end_date->isSameMonth(Carbon::now())) {
                        $end_date = Carbon::today();
                    }
                }



                $monthlyAttendance = Attendance::where('is_present', 1)
                    ->where('user_id', $authUserId)
                    ->whereBetween('in_date', [$start_date, $end_date])
                    ->select('id', 'total_paid_hours', 'break_hours', 'paid_break_hours', 'unpaid_break_hours', 'in_date', "regular_work_hours")
                    ->get();

                $total_paid_hours = $monthlyAttendance->sum('total_paid_hours');
                $break_hours = $monthlyAttendance->sum('break_hours');
                $regular_work_hours = $monthlyAttendance->sum('regular_work_hours');


                $total_schedule_hours = $this->workTimeManagementComponent
                    ->getScheduleInformationData(
                        $authUserId,
                        $authUser->joining_date,
                        $authUser->lastTermination->date_of_termination ?? null,
                        $start_date,
                        $end_date
                    )['total_capacity_hours'];


                $attendanceData = [
                    "start_date" => $start_date,
                    "end_date" => $end_date,
                    "working_hours" => $total_paid_hours,
                    "regular_working_hours" => $regular_work_hours,
                    'total_absent_hours' => $total_schedule_hours - $regular_work_hours,
                    'break_hours' => -number_format($break_hours, 2),
                    'total_absent_hours' => $end_date->lte(today()) ? ($total_schedule_hours - $regular_work_hours) : 0,
                    'total_schedule_hours_v2' => $total_schedule_hours,
                ];


                if ($start_date->isFuture()) {
                    $attendanceData["total_schedule_hours"] = $total_schedule_hours;
                }



                $data[] = array_merge(["name" => $month['month']], $attendanceData);
            }

            return $data;
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-employee-dashboard/present-hours",
     *      operationId="getBusinessEmployeeDashboardDataPresentHours",
     *      tags={"dashboard_management.business_user"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="duration",
     *         in="query",
     *         description="total,today, this_month, this_week... ",
     *         required=true,
     *  example="query"
     *      ),
     *      *              @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="total,today, this_month, this_week... ",
     *         required=true,
     *  example="year"
     *      ),

     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessEmployeeDashboardDataPresentHours(Request $request)
    {

        try {


            $business_id = auth()->user()->business_id;
            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }

            $date_ranges = $this->dateRanges();
            $data = $this->presentHours($date_ranges);

            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getLeaveData($data_query, $start_date = "", $end_date = "")
    {
        $updated_data_query_old = clone $data_query;
        $updated_data_query = $updated_data_query_old->when(
            (!empty($start_date) && !empty($end_date)),
            function ($query) use ($start_date, $end_date) {
                $query->whereBetween("leave_records.date", [$start_date, $end_date . ' 23:59:59']);
            }
        );

        $data["total_requested"] = clone $updated_data_query;
        $data["total_requested"] = $data["total_requested"]
            ->count();




        $data["total_pending"] = clone $updated_data_query;
        $data["total_pending"] = $data["total_pending"]
            ->whereHas("leave", function ($query) {
                $query->where([
                    "leaves.status" => "pending_approval"
                ]);
            })
            ->count();

        $data["total_approved"] = clone $updated_data_query;
        $data["total_approved"] = $data["total_approved"]
            ->whereHas("leave", function ($query) {
                $query->where([
                    "leaves.status" => "approved"
                ]);
            })
            ->count();

        $data["total_rejected"] = clone $updated_data_query;
        $data["total_rejected"] = $data["total_rejected"]
            ->whereHas("leave", function ($query) {
                $query->where([
                    "leaves.status" => "rejected"
                ]);
            })


            ->count();




        return $data;
    }


    public function getHolidayDataQueryBuilding($data_query, $start_date = "", $end_date = "")
    {
        $updated_data_query_old = clone $data_query;
        $updated_data_query = $updated_data_query_old->when(
            (!empty($start_date) && !empty($end_date)),
            function ($query) use ($start_date, $end_date) {
                $query->whereDate("holidays.end_date", ">=", $start_date)
                    ->whereDate("holidays.start_date", "<=", $end_date);
            }
        );

        $data["total_requested"] = clone $updated_data_query;
        $data["total_requested"] = $data["total_requested"]
            ->count();

        $data["total_pending"] = clone $updated_data_query;
        $data["total_pending"] = $data["total_pending"]
            ->where([
                "holidays.status" => "pending_approval"
            ])
            ->count();

        $data["total_approved"] = clone $updated_data_query;
        $data["total_approved"] = $data["total_approved"]
            ->where([
                "leaves.status" => "approved"
            ])

            ->count();

        $data["total_rejected"] = clone $updated_data_query;
        $data["total_rejected"] = $data["total_rejected"]

            ->where([
                "leaves.status" => "rejected"
            ])


            ->count();




        return $data;
    }


    public function leaves(
        $all_manager_department_ids,
    ) {

        $data_query  = LeaveRecord::whereHas("leave", function ($query) {
            $query->where([
                "leaves.business_id" => auth()->user()->business_id,
            ]);
        })
            ->whereHas("leave.employee.departments", function ($query) use ($all_manager_department_ids) {
                $query->whereIn("departments.id", $all_manager_department_ids);
            });


        $data["individual_total"] = $this->getLeaveData($data_query);

        $last12MonthsDates = $this->getLast12MonthsDates(request()->input('year'));

        foreach ($last12MonthsDates as $month) {
            $leaveData =  $this->getLeaveData($data_query, $month['start_date'], $month['end_date']);
            $data["data"][] = array_merge(
                ["month" => $month['month']],
                $leaveData
            );
        }

        return $data;
    }
    public function employeeLeaves(
    ) {
        $year = request()->input("year");
        if (!$year) {
            throw new Exception("year is required", 400);
        }

        $data_query  = LeaveRecord::whereHas("leave", function ($query) {
            $query->where([
                "leaves.business_id" => auth()->user()->business_id,
            ])->whereIn("leaves.user_id", [auth()->user()->id]);
        })
            ->whereYear("leave_records.date", $year);


        $data["individual_total"] = $this->getLeaveData($data_query);

        $last12MonthsDates = $this->getLast12MonthsDates(request()->input("year"));

        foreach ($last12MonthsDates as $month) {
            $leaveData =  $this->getLeaveData($data_query, $month['start_date'], $month['end_date']);
            $data["data"][] = array_merge(
                ["month" => $month['month']],
                $leaveData
            );
        }

        return $data;
    }



    public function holidays($all_manager_department_ids)
    {

        $data_query  = Holiday::where([
            "holidays.business_id" => auth()->user()->business_id,
        ])
            ->where(function ($query) use ($all_manager_department_ids) {
                $query->whereHas("employees.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                    ->orWhere("holidays.is_holiday_for_all", 1);
            });

        $data["individual_total"] = $this->getHolidayDataQueryBuilding($data_query);

        if (!request()->input("year")) {
            throw new Exception("year is required", 400);
        }

        $last12MonthsDates = $this->getLast12MonthsDates(request()->input("year"));

        foreach ($last12MonthsDates as $month) {
            $leaveData =  $this->getLeaveData($data_query, $month['start_date'], $month['end_date']);
            $data["data"][] = array_merge(
                ["month" => $month['month']],
                $leaveData
            );
        }

        foreach ($last12MonthsDates as $month) {
            $leaveData =  $this->getHolidayDataQueryBuilding($data_query, $month['start_date'], $month['end_date']);
            $data["data"][] = array_merge(
                ["month" => $month['month']],
                $leaveData
            );
        }

        return $data;
    }


    public function leavesStructure2(
        $date_ranges,
        $all_manager_department_ids
    ) {

        $leaves_query  = LeaveRecord::whereHas("leave", function ($query) {
            $query->where([
                "leaves.business_id" => auth()->user()->business_id,
            ]);
        })
            ->whereHas("leave.employee.departments", function ($query) use ($all_manager_department_ids) {
                $query->whereIn("departments.id", $all_manager_department_ids);
            });

        $leave_statuses = ['pending_approval', 'in_progress', 'approved', 'rejected'];
        foreach ($leave_statuses as $leave_status) {

            $updated_query = clone $leaves_query;
            $updated_query = $updated_query->whereHas("leave", function ($query) use ($leave_status) {
                $query->where([
                    "leaves.status" => $leave_status
                ]);
            });
            $data[($leave_status . "_leaves")]["total"] = $updated_query->count();


            $data[($leave_status . "_leaves")]["monthly"] = $this->getData(
                $updated_query,
                "date",
                [
                    "start_date" => $date_ranges["start_date_of_this_month"],
                    "end_date" => $date_ranges["end_date_of_this_month"],
                    "previous_start_date" => $date_ranges["start_date_of_previous_month"],
                    "previous_end_date" => $date_ranges["end_date_of_previous_month"],
                ]
            );

            $data[($leave_status . "_leaves")]["weekly"] = $this->getData(
                $updated_query,
                "date",
                [
                    "start_date" => $date_ranges["start_date_of_this_week"],
                    "end_date" => $date_ranges["end_date_of_this_week"],
                    "previous_start_date" => $date_ranges["start_date_of_previous_week"],
                    "previous_end_date" => $date_ranges["end_date_of_previous_week"],
                ]
            );
        }

        return $data;
    }

    public function getLeavesStructure3(
        $date_ranges,
        $all_manager_department_ids,
        $leave_status,
        $duration
    ) {

        $leave_statuses = ['pending_approval', 'in_progress', 'approved', 'rejected'];
        if (!in_array($leave_status, $leave_statuses)) {
            $error =  [
                "message" => "The given data was invalid.",
                "errors" => ["status" => ["Valid Statuses are 'pending_approval','in_progress', 'approved','rejected' "]]
            ];
            throw new Exception(json_encode($error), 422);
        }

        $leaves_query  = LeaveRecord::whereHas("leave", function ($query) {
            $query->where([
                "leaves.business_id" => auth()->user()->business_id,
            ]);
        })
            ->whereHas("leave.employee.departments", function ($query) use ($all_manager_department_ids) {
                $query->whereIn("departments.id", $all_manager_department_ids);
            });

        $leaves_query = $leaves_query->whereHas("leave", function ($query) use ($leave_status) {
            $query->where([
                "leaves.status" => $leave_status
            ]);
        });

        if ($duration == "total") {
            $data["total"] = $leaves_query->count();
        }

        if ($duration == "today") {
            $data["total"] = $leaves_query->where("date", today())->count();
        }

        if ($duration == "this_month") {
            $data["data"] = $this->getData(
                $leaves_query,
                "date",
                [
                    "start_date" => $date_ranges["start_date_of_this_month"],
                    "end_date" => $date_ranges["end_date_of_this_month"],
                    "previous_start_date" => $date_ranges["start_date_of_previous_month"],
                    "previous_end_date" => $date_ranges["end_date_of_previous_month"],
                ]
            );
            $data["total"] = $data["data"]["current_amount"];
        }

        if ($duration == "this_week") {
            $data["data"] = $this->getData(
                $leaves_query,
                "date",
                [
                    "start_date" => $date_ranges["start_date_of_this_week"],
                    "end_date" => $date_ranges["end_date_of_this_week"],
                    "previous_start_date" => $date_ranges["start_date_of_previous_week"],
                    "previous_end_date" => $date_ranges["end_date_of_previous_week"],
                ]
            );
            $data["total"] = $data["data"]["current_amount"];
        }



        return $data;
    }


    public function getLeavesStructure4(
        $all_manager_department_ids
    ) {

        $leaves_query  = LeaveRecord::whereHas("leave", function ($query) {
            $query->where([
                "leaves.business_id" => auth()->user()->business_id,
            ]);
        })
            ->whereHas("leave.employee.departments", function ($query) use ($all_manager_department_ids) {
                $query->whereIn("departments.id", $all_manager_department_ids);
            });
        $leave_statuses = ['pending_approval', 'in_progress', 'approved', 'rejected'];

        foreach ($leave_statuses as $leave_status) {
            $updated_query = clone $leaves_query;
            $updated_query = $updated_query->whereHas("leave", function ($query) use ($leave_status) {
                $query->where([
                    "leaves.status" => $leave_status
                ]);
            });
            $data[("total_" . $leave_status)] = $updated_query->count();
        }





        return $data;
    }


    public function getHolidaysStructure4($all_manager_department_ids)
    {

        $holiday_query  = Holiday::where([
            "holidays.business_id" => auth()->user()->business_id,
        ])
            ->where(function ($query) use ($all_manager_department_ids) {
                $query->whereHas("employees.departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                    ->orWhere("holidays.is_holiday_for_all", 1);
            });


        $holiday_statuses = ['pending_approval', 'in_progress', 'approved', 'rejected'];

        foreach ($holiday_statuses as $holiday_status) {
            $updated_query = clone $holiday_query;
            $updated_query = $updated_query->where([
                "holidays.status" => $holiday_status
            ]);
            $data[("total_" . $holiday_status)] = $updated_query->count();
        }


        return $data;
    }




    public function pensionsStructure2(
        $date_ranges,
        $all_manager_department_ids,
    ) {


        $issue_date_column = 'pension_enrollment_issue_date';
        $expiry_date_column = 'pension_re_enrollment_due_date';







        $pension_query  = EmployeePensionHistory::whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
            ->where("is_current", 1)
            ->whereHas("employee", function ($query) {
                $query
                    ->where("users.is_active", 1)
                    ->whereNotIn('users.id', [auth()->user()->id])
                    ->whereDate("users.joining_date", "<=", today())
                    ->whereDoesntHave("lastTermination", function ($query) {
                        $query->where('terminations.date_of_termination', "<", today())
                            ->whereRaw('terminations.date_of_termination > users.joining_date');
                    });
            })
            ->whereNotNull($expiry_date_column)
            ->where($expiry_date_column, ">=", today());

        $pension_statuses = ["opt_in", "opt_out"];
        foreach ($pension_statuses as $pension_status) {

            $updated_query = clone $pension_query;
            $updated_query = $updated_query->where("pension_scheme_status", $pension_status);
            $data[($pension_status . "_pension")]["total"] = $updated_query->count();


            $data[($pension_status . "_pension")]["monthly"] = $this->getData(
                $updated_query,
                "pension_enrollment_issue_date",
                [
                    "start_date" => $date_ranges["start_date_of_this_month"],
                    "end_date" => $date_ranges["end_date_of_this_month"],
                    "previous_start_date" => $date_ranges["start_date_of_previous_month"],
                    "previous_end_date" => $date_ranges["end_date_of_previous_month"],
                ]
            );

            $data[($pension_status . "_pension")]["weekly"] = $this->getData(
                $updated_query,
                "pension_enrollment_issue_date",
                [
                    "start_date" => $date_ranges["start_date_of_this_week"],
                    "end_date" => $date_ranges["end_date_of_this_week"],
                    "previous_start_date" => $date_ranges["start_date_of_previous_week"],
                    "previous_end_date" => $date_ranges["end_date_of_previous_week"],
                ]
            );
        }

        return $data;
    }


    public function getPensionsStructure3(
       $date_ranges,
        $all_manager_department_ids,
        $pension_status,
        $duration
    ) {
        $pension_statuses = ["opt_in", "opt_out"];
        if (!in_array($pension_status, $pension_statuses)) {
            $error =  [
                "message" => "The given data was invalid.",
                "errors" => ["status" => ["Valid Statuses are \"\opt_in\"\, \"\opt_out\"\ "]]
            ];
            throw new Exception(json_encode($error), 422);
        }

        $issue_date_column = 'pension_enrollment_issue_date';
        $expiry_date_column = 'pension_re_enrollment_due_date';

        $pension_query  = EmployeePensionHistory::whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
            ->where("is_current", 1)
            ->whereHas("employee", function ($query) {
                $query
                    ->where("users.is_active", 1)
                    ->whereNotIn('users.id', [auth()->user()->id])
                    ->whereDate("users.joining_date", "<=", today())
                    ->whereDoesntHave("lastTermination", function ($query) {
                        $query->where('terminations.date_of_termination', "<", today())
                            ->whereRaw('terminations.date_of_termination > users.joining_date');
                    });
            });




        $pension_query = $pension_query->where("pension_scheme_status", $pension_status);


        if ($duration == "total") {
            $data["total"] = $pension_query->count();
        }

        if ($duration == "today") {
            $data["total"] = $pension_query->where("pension_enrollment_issue_date", today())->count();
        }

        if ($duration == "this_month") {
            $data["data"] = $this->getData(
                $pension_query,
                "pension_enrollment_issue_date",
                [
                    "start_date" => $date_ranges["start_date_of_this_month"],
                    "end_date" => $date_ranges["end_date_of_this_month"],
                    "previous_start_date" => $date_ranges["start_date_of_previous_month"],
                    "previous_end_date" => $date_ranges["end_date_of_previous_month"],
                ]
            );
            $data["total"] = $data["data"]["current_amount"];
        }

        if ($duration == "this_week") {
            $data["data"] = $this->getData(
                $pension_query,
                "pension_enrollment_issue_date",
                [
                    "start_date" => $date_ranges["start_date_of_this_week"],
                    "end_date" => $date_ranges["end_date_of_this_week"],
                    "previous_start_date" => $date_ranges["start_date_of_previous_week"],
                    "previous_end_date" => $date_ranges["end_date_of_previous_week"],
                ]
            );
            $data["total"] = $data["data"]["current_amount"];
        }








        return $data;
    }

    public function getPensionsStructure4(

        $all_manager_department_ids,

    ) {

        $pension_statuses = ["opt_in", "opt_out"];

        $issue_date_column = 'pension_enrollment_issue_date';
        $expiry_date_column = 'pension_re_enrollment_due_date';


        $pension_query  = EmployeePensionHistory::whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
            ->where("is_current", 1)
            ->whereHas("employee", function ($query) use ($all_manager_department_ids) {
                $query
                    ->where("users.is_active", 1)
                    ->whereNotIn('users.id', [auth()->user()->id])
                    ->whereDate("users.joining_date", "<=", today())
                    ->whereDoesntHave("lastTermination", function ($query) {
                        $query->where('terminations.date_of_termination', "<", today())
                            ->whereRaw('terminations.date_of_termination > users.joining_date');
                    });
            });

        foreach ($pension_statuses as $pension_status) {
            $updated_query = clone $pension_query;
            $updated_query = $updated_query->where("pension_scheme_status", $pension_status);
            $data[("total_" . $pension_status)] = $updated_query->count();
        }

        return $data;
    }


    public function getPensionExpiries(
        $date_ranges,
        $all_manager_department_ids,
        $duration,
        $expires_in_days = 0


    ) {

        $issue_date_column = 'pension_enrollment_issue_date';
        $expiry_date_column = 'pension_re_enrollment_due_date';



        $data_query  = EmployeePensionHistory::whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
            ->where("is_current", 1)
            ->whereHas("employee", function ($query) use ($all_manager_department_ids) {
                $query
                    ->where("users.is_active", 1)
                    ->whereNotIn('users.id', [auth()->user()->id])
                    ->whereDate("users.joining_date", "<=", today())
                    ->whereDoesntHave("lastTermination", function ($query) {
                        $query->where('terminations.date_of_termination', "<", today())
                            ->whereRaw('terminations.date_of_termination > users.joining_date');
                    });
            })
            ->whereNotNull($expiry_date_column)
            ->where($expiry_date_column, ">=", today());

        if ($duration == "today") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [today()->startOfDay(), today()->endOfDay()])->count();
        }

        if ($duration == "next_week") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_next_week"], ($date_ranges["end_date_of_next_week"] . ' 23:59:59')])->count();
        }

        if ($duration == "this_week") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_this_week"], ($date_ranges["end_date_of_this_week"] . ' 23:59:59')])->count();
        }

        if ($duration == "previous_week") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_previous_week"], ($date_ranges["end_date_of_previous_week"] . ' 23:59:59')])->count();
        }


        if ($duration == "next_month") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_next_month"], ($date_ranges["end_date_of_next_month"] . ' 23:59:59')])->count();
        }


        if ($duration == "this_month") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_this_month"], ($date_ranges["end_date_of_this_month"] . ' 23:59:59')])->count();
        }


        if ($duration == "previous_month") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_previous_month"], ($date_ranges["end_date_of_previous_month"] . ' 23:59:59')])->count();
        }



        if ($expires_in_days) {
            $ranges = [
                'expires_in_15_days' => [today()->addDays(0), today()->addDays(15)],
                'expires_in_30_days' => [today()->addDays(16), today()->addDays(30)],
                'expires_in_60_days' => [today()->addDays(31), today()->addDays(60)],
            ];
            foreach ($ranges as $key => [$start, $end]) {
                $data[$key] = clone $data_query;

                $data[$key] = $data[$key]
                    ->whereDate(
                        $expiry_date_column,
                        ">=",
                        $start
                    )
                    ->whereDate(
                        $expiry_date_column,
                        "<=",
                        $end
                    )
                    ->count();
            }
        }


        return $data;
    }


    public function getPassportExpiries(
        $date_ranges,
        $all_manager_department_ids,
        $duration,
        $expires_in_days = 0
    ) {

        $issue_date_column = 'passport_issue_date';
        $expiry_date_column = 'passport_expiry_date';






        $data_query  = EmployeePassportDetailHistory::whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
            ->where("is_current", 1)
            ->whereHas("employee", function ($query) use ($all_manager_department_ids) {
                $query
                    ->where("users.is_active", 1)
                    ->whereNotIn('users.id', [auth()->user()->id])
                    ->whereDate("users.joining_date", "<=", today())
                    ->whereDoesntHave("lastTermination", function ($query) {
                        $query->where('terminations.date_of_termination', "<", today())
                            ->whereRaw('terminations.date_of_termination > users.joining_date');
                    });
            })
            ->whereNotNull($expiry_date_column)
            ->where($expiry_date_column, ">=", today());





        if ($duration == "today") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [today()->startOfDay(), today()->endOfDay()])->count();
        }

        if ($duration == "next_week") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_next_week"], ($date_ranges["end_date_of_next_week"] . ' 23:59:59')])->count();
        }

        if ($duration == "this_week") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_this_week"], ($date_ranges["end_date_of_this_week"] . ' 23:59:59')])->count();
        }

        if ($duration == "previous_week") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_previous_week"], ($date_ranges["end_date_of_previous_week"] . ' 23:59:59')])->count();
        }


        if ($duration == "next_month") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_next_month"], ($date_ranges["end_date_of_next_month"] . ' 23:59:59')])->count();
        }


        if ($duration == "this_month") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_this_month"], ($date_ranges["end_date_of_this_month"] . ' 23:59:59')])->count();
        }


        if ($duration == "previous_month") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_previous_month"], ($date_ranges["end_date_of_previous_month"] . ' 23:59:59')])->count();
        }



        if ($expires_in_days) {
            $ranges = [
                'expires_in_15_days' => [today()->addDays(0), today()->addDays(15)],
                'expires_in_30_days' => [today()->addDays(16), today()->addDays(30)],
                'expires_in_60_days' => [today()->addDays(31), today()->addDays(60)],
            ];
            foreach ($ranges as $key => [$start, $end]) {
                $data[$key] = clone $data_query;
                $data[$key . "_start"] = $start;
                $data[$key . "_end"] = $end;
                $data[$key] = $data[$key]
                    ->whereDate(
                        $expiry_date_column,
                        ">=",
                        $start
                    )
                    ->whereDate(
                        $expiry_date_column,
                        "<=",
                        $end
                    )
                    ->count();
            }
        }

        return $data;
    }


    public function getVisaExpiries(
        $date_ranges,
        $all_manager_department_ids,
        $duration,
        $expires_in_days = 0

    ) {

        $issue_date_column = 'visa_issue_date';
        $expiry_date_column = 'visa_expiry_date';





        $data_query  = EmployeeVisaDetailHistory::whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
            ->where("is_current", 1)
            ->whereHas("employee", function ($query) use ($all_manager_department_ids) {
                $query
                    ->where("users.is_active", 1)
                    ->whereNotIn('users.id', [auth()->user()->id])
                    ->whereDate("users.joining_date", "<=", today())
                    ->whereDoesntHave("lastTermination", function ($query) {
                        $query->where('terminations.date_of_termination', "<", today())
                            ->whereRaw('terminations.date_of_termination > users.joining_date');
                    });
            })
            ->whereNotNull($expiry_date_column)
            ->where($expiry_date_column, ">=", today());




        if ($duration == "today") {
            $data_query_clone = clone $data_query;
            $data["today"] = $data_query_clone->whereBetween($expiry_date_column, [today()->startOfDay(), today()->endOfDay()])->count();
        }

        if ($duration == "next_week") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_next_week"], ($date_ranges["end_date_of_next_week"] . ' 23:59:59')])->count();
        }

        if ($duration == "this_week") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_this_week"], ($date_ranges["end_date_of_this_week"] . ' 23:59:59')])->count();
        }

        if ($duration == "previous_week") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_previous_week"], ($date_ranges["end_date_of_previous_week"] . ' 23:59:59')])->count();
        }


        if ($duration == "next_month") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_next_month"], ($date_ranges["end_date_of_next_month"] . ' 23:59:59')])->count();
        }


        if ($duration == "this_month") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_this_month"], ($date_ranges["end_date_of_this_month"] . ' 23:59:59')])->count();
        }


        if ($duration == "previous_month") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_previous_month"], ($date_ranges["end_date_of_previous_month"] . ' 23:59:59')])->count();
        }

        if ($expires_in_days) {
            $ranges = [
                'expires_in_15_days' => [today()->addDays(0), today()->addDays(15)],
                'expires_in_30_days' => [today()->addDays(16), today()->addDays(30)],
                'expires_in_60_days' => [today()->addDays(31), today()->addDays(60)],
            ];
            foreach ($ranges as $key => [$start, $end]) {
                $data[$key] = clone $data_query;
                $data[$key . "_start"] = $start;
                $data[$key . "_end"] = $end;
                $data[$key] = $data[$key]
                    ->whereDate(
                        $expiry_date_column,
                        ">=",
                        $start
                    )
                    ->whereDate(
                        $expiry_date_column,
                        "<=",
                        $end
                    )
                    ->count();
            }
        }




        return $data;
    }



    public function getRightToWorkExpiries(
        $date_ranges,
        $all_manager_department_ids,
        $duration,
        $expires_in_days = 0
    ) {

        $issue_date_column = 'right_to_work_check_date';
        $expiry_date_column = 'right_to_work_expiry_date';






        $data_query  = EmployeeRightToWorkHistory::whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
            ->where("is_current", 1)
            ->whereHas("employee", function ($query) use ($all_manager_department_ids) {
                $query
                    ->where("users.is_active", 1)
                    ->whereNotIn('users.id', [auth()->user()->id])
                    ->whereDate("users.joining_date", "<=", today())
                    ->whereDoesntHave("lastTermination", function ($query) {
                        $query->where('terminations.date_of_termination', "<", today())
                            ->whereRaw('terminations.date_of_termination > users.joining_date');
                    });
            })
            ->whereNotNull($expiry_date_column)
            ->where($expiry_date_column, ">=", today());




        if ($duration == "today") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [today()->startOfDay(), today()->endOfDay()])->count();
        }

        if ($duration == "next_week") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_next_week"], ($date_ranges["end_date_of_next_week"] . ' 23:59:59')])->count();
        }

        if ($duration == "this_week") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_this_week"], ($date_ranges["end_date_of_this_week"] . ' 23:59:59')])->count();
        }

        if ($duration == "previous_week") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_previous_week"], ($date_ranges["end_date_of_previous_week"] . ' 23:59:59')])->count();
        }


        if ($duration == "next_month") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_next_month"], ($date_ranges["end_date_of_next_month"] . ' 23:59:59')])->count();
        }


        if ($duration == "this_month") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_this_month"], ($date_ranges["end_date_of_this_month"] . ' 23:59:59')])->count();
        }


        if ($duration == "previous_month") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_previous_month"], ($date_ranges["end_date_of_previous_month"] . ' 23:59:59')])->count();
        }


        if ($expires_in_days) {
            $ranges = [
                'expires_in_15_days' => [today()->addDays(0), today()->addDays(15)],
                'expires_in_30_days' => [today()->addDays(16), today()->addDays(30)],
                'expires_in_60_days' => [today()->addDays(31), today()->addDays(60)],
            ];
            foreach ($ranges as $key => [$start, $end]) {
                $data[$key] = clone $data_query;
                $data[$key . "_start"] = $start;
                $data[$key . "_end"] = $end;
                $data[$key] = $data[$key]
                    ->whereDate(
                        $expiry_date_column,
                        ">=",
                        $start
                    )
                    ->whereDate(
                        $expiry_date_column,
                        "<=",
                        $end
                    )
                    ->count();
            }
        }





        return $data;
    }


    public function getSponsorshipExpiries(
        $date_ranges,
        $all_manager_department_ids,
        $duration,
        $expires_in_days = 0
    ) {

        $issue_date_column = 'date_assigned';
        $expiry_date_column = 'expiry_date';






        $data_query  = EmployeeSponsorshipHistory::whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
            ->where("is_current", 1)
            ->whereHas("employee", function ($query) use ($all_manager_department_ids) {
                $query
                    ->where("users.is_active", 1)
                    ->whereNotIn('users.id', [auth()->user()->id])
                    ->whereDate("users.joining_date", "<=", today())
                    ->whereDoesntHave("lastTermination", function ($query) {
                        $query->where('terminations.date_of_termination', "<", today())
                            ->whereRaw('terminations.date_of_termination > users.joining_date');
                    });
            })
            ->whereNotNull($expiry_date_column)
            ->where($expiry_date_column, ">=", today());



        if ($duration == "today") {
            $data_query_clone = clone $data_query;
            $data["today"] = $data_query_clone->whereBetween($expiry_date_column, [today()->startOfDay(), today()->endOfDay()])->count();
        }

        if ($duration == "next_week") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_next_week"], ($date_ranges["end_date_of_next_week"] . ' 23:59:59')])->count();
        }

        if ($duration == "this_week") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_this_week"], ($date_ranges["end_date_of_this_week"] . ' 23:59:59')])->count();
        }

        if ($duration == "previous_week") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_previous_week"], ($date_ranges["end_date_of_previous_week"] . ' 23:59:59')])->count();
        }


        if ($duration == "next_month") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_next_month"], ($date_ranges["end_date_of_next_month"] . ' 23:59:59')])->count();
        }


        if ($duration == "this_month") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_this_month"], ($date_ranges["end_date_of_this_month"] . ' 23:59:59')])->count();
        }


        if ($duration == "previous_month") {
            $data_query_clone = clone $data_query;
            $data["total"] = $data_query_clone->whereBetween($expiry_date_column, [$date_ranges["start_date_of_previous_month"], ($date_ranges["end_date_of_previous_month"] . ' 23:59:59')])->count();
        }




        if ($expires_in_days) {
            $ranges = [
                'expires_in_15_days' => [today()->addDays(0), today()->addDays(15)],
                'expires_in_30_days' => [today()->addDays(16), today()->addDays(30)],
                'expires_in_60_days' => [today()->addDays(31), today()->addDays(60)],
            ];
            foreach ($ranges as $key => [$start, $end]) {
                $data[$key] = clone $data_query;
                $data[$key . "_start"] = $start;
                $data[$key . "_end"] = $end;
                $data[$key] = $data[$key]
                    ->whereDate(
                        $expiry_date_column,
                        ">=",
                        $start
                    )
                    ->whereDate(
                        $expiry_date_column,
                        "<=",
                        $end
                    )
                    ->count();
            }
        }





        return $data;
    }

    public function expiries(
        $date_ranges,
        $all_manager_department_ids,

    ) {

        $data["pension"] = $this->getPensionExpiries(
            $date_ranges,
            $all_manager_department_ids,
            "today"
        );

        $data["passport"] = $this->getPassportExpiries(
            $date_ranges,
            $all_manager_department_ids,
            "today"
        );

        $data["visa"] = $this->getVisaExpiries(
            $date_ranges,
            $all_manager_department_ids,
            "today"
        );

        $data["right_to_work"] = $this->getRightToWorkExpiries(
            $date_ranges,
            $all_manager_department_ids,
            "today"
        );

        $data["sponsorship"] = $this->getSponsorshipExpiries(
            $date_ranges,
            $all_manager_department_ids,
            "today"
        );

        return $data;
    }



    public function getData($data_query, $dateField, $dates)
    {

        $data["current_amount"] = clone $data_query;
        $data["current_amount"] = $data["current_amount"]->whereBetween($dateField, [$dates["start_date"], ($dates["end_date"] . ' 23:59:59')])->count();


        $data["last_amount"] = clone $data_query;
        $data["last_amount"] = $data["last_amount"]->whereBetween($dateField, [$dates["previous_start_date"], ($dates["previous_end_date"] . ' 23:59:59')])->count();



        $all_data = clone $data_query;
        $all_data = $all_data->whereBetween($dateField, [$dates["start_date"], ($dates["end_date"] . ' 23:59:59')])->get();

        $start_date = Carbon::parse($dates["start_date"]);
        $end_date = Carbon::parse(($dates["end_date"]));
        // Initialize an array to hold the counts for each date
        $data["data"] = [];

        // Loop through each day in the date range
        for ($date = $start_date->copy(); $date->lte($end_date); $date->addDay()) {
            // Filter the data for the current date
            $filtered_data = $all_data->filter(function ($item) use ($dateField, $date) {
                return Carbon::parse($item[$dateField])->isSameDay($date);
            });

            // Store the count of records for the current date
            $data["data"][] = [
                "date" => $date->toDateString(),
                "total" => $filtered_data->count()
            ];
        }
        return $data;
    }


    public function getDataV2($data_query, $startDateField, $endDateField, $dates)
    {

        $data["current_amount"] = clone $data_query;
        $data["current_amount"] = $data["current_amount"]
            ->where($startDateField, ">", $dates["start_date"])
            ->where($endDateField, "<=", $dates["end_date"])
            ->count();

        $data["last_amount"] = clone $data_query;
        $data["last_amount"] = $data["last_amount"]
            ->where($startDateField, ">", $dates["previous_start_date"])
            ->where($endDateField, "<=", $dates["previous_end_date"])->count();

        $all_data = clone $data_query;
        $all_data = $all_data
            ->where($startDateField, ">", $dates["start_date"])
            ->where($endDateField, "<=", $dates["end_date"])
            ->select("id",)
            ->get();

        $start_date = Carbon::parse($dates["start_date"]);
        $end_date = Carbon::parse(($dates["end_date"]));
        // Initialize an array to hold the counts for each date
        $data["data"] = [];

        // Loop through each day in the date range
        for ($date = $start_date->copy(); $date->lte($end_date); $date->addDay()) {
            // Filter the data for the current date
            $filtered_data = $all_data->filter(function ($item) use ($date, $startDateField, $endDateField) {
                return Carbon::parse($item[$startDateField])->greaterThan($date) &&
                    Carbon::parse($item[$endDateField])->lessThanOrEqualTo($date);
            });

            // Store the count of records for the current date
            $data["data"][] = [
                "date" => $date->toDateString(),
                "total" => $filtered_data->count()
            ];
        }
        return $data;
    }








    public function total_employee(
    $date_ranges,
        $all_manager_department_ids,
        $duration
    ) {

        $data_query  = User::whereHas("departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
            ->whereDate("users.joining_date", "<=", today())
            ->whereDoesntHave("lastTermination", function ($query) {
                $query->where('terminations.date_of_termination', "<", today())
                    ->whereRaw('terminations.date_of_termination > users.joining_date');
            })
            ->whereNotIn('id', [auth()->user()->id])

            ->where('is_active', 1);

        if ($duration == "total") {
            $data["total"] = $data_query
                ->count();
        }

        if ($duration == "today") {
            $data["total"] = $data_query
                ->where("joining_date", today())
                ->count();
        }

        if ($duration == "this_month") {
            $data["data"] = $this->getData(
                $data_query,
                "joining_date",
                [
                    "start_date" => $date_ranges["start_date_of_this_month"],
                    "end_date" => $date_ranges["end_date_of_this_month"],
                    "previous_start_date" => $date_ranges["start_date_of_previous_month"],
                    "previous_end_date" => $date_ranges["end_date_of_previous_month"],
                ]
            );
            $data["total"] = $data["data"]["current_amount"];
        }

        if ($duration == "this_week") {
            $data["data"] = $this->getData(
                $data_query,
                "joining_date",
                [
                    "start_date" => $date_ranges["start_date_of_this_week"],
                    "end_date" => $date_ranges["end_date_of_this_week"],
                    "previous_start_date" => $date_ranges["start_date_of_previous_week"],
                    "previous_end_date" => $date_ranges["end_date_of_previous_week"],
                ]
            );
            $data["total"] = $data["data"]["current_amount"];
        }



        return $data;
    }


    public function open_roles(
       $date_ranges,
        $duration
    ) {

        $data_query  = JobListing::where("business_id", auth()->user()->business_id);

        if ($duration == "today") {
            $data["total"] = $data_query
                ->where("application_deadline", ">", today())
                ->where("posted_on", "<=", today())
                ->count();
        }

        if ($duration == "this_month") {
            $data["data"] = $this->getDataV2(
                $data_query,
                "posted_on",
                "application_deadline",
                [
                    "start_date" => $date_ranges["start_date_of_this_month"],
                    "end_date" => $date_ranges["end_date_of_this_month"],
                    "previous_start_date" => $date_ranges["start_date_of_previous_month"],
                    "previous_end_date" => $date_ranges["end_date_of_previous_month"],
                ]
            );
            $data["total"] = $data["data"]["current_amount"];
        }

        if ($duration == "this_week") {
            $data["data"] = $this->getDataV2(
                $data_query,
                "posted_on",
                "application_deadline",
                [
                    "start_date" => $date_ranges["start_date_of_this_week"],
                    "end_date" => $date_ranges["end_date_of_this_week"],
                    "previous_start_date" => $date_ranges["start_date_of_previous_week"],
                    "previous_end_date" => $date_ranges["end_date_of_previous_week"],
                ]
            );

            $data["total"] = $data["data"]["current_amount"];
        }

        return $data;
    }

    public function checkHoliday($date, $user_id)
    {

        // Retrieve work shift history for the user and date
        $work_shift_history =  $this->workTimeManagementComponent->get_work_shift_history($date, $user_id);
        // Retrieve work shift details based on work shift history and date
        $work_shift_details =  $this->workTimeManagementComponent->get_work_shift_details($work_shift_history, $date, TRUE);

        if (

            !$work_shift_details->schedule_hour

            ||

            $work_shift_details->is_weekend

        ) {
            return true;
        }

        // Retrieve holiday details for the user and date
        $holiday = $this->workTimeManagementComponent->get_holiday_details($date, $user_id);

        if (!empty($holiday) && $holiday->is_active) {
            return true;
        }
        // Retrieve leave record details for the user and date
        $leave_record = $this->workTimeManagementComponent->get_leave_record_details($date, $user_id);

        if (!empty($leave_record)) {
            return true;
        }


        return false;
    }


    public function calculateAbsent($all_manager_user_ids, $date, $data_query)
    {

        $current_date = Carbon::parse($date);
        $users = User::whereIn("id", $all_manager_user_ids)
            ->where("users.is_active", 1)
            ->whereDate("users.joining_date", "<=", today())
            ->whereDoesntHave("lastTermination", function ($query) use ($date) {
                $query->where('terminations.date_of_termination', "<", $date)
                    ->whereRaw('terminations.date_of_termination > users.joining_date');
            })

            ->select("id", "joining_date")->get();
        $absent_count = 0;
        foreach ($users as $user) {

            $joining_date = Carbon::parse($user->joining_date);

            if ($joining_date->gt($current_date)) {
                continue;
            }


            if (!$this->checkHoliday($date, $user->id)) {
                $data_query = clone $data_query;
                $attendance = $data_query->where("in_date", $date)->first();
                if (empty($attendance)) {
                    $absent_count++;
                }
            }
        }
        return $absent_count;
    }

    public function getAbsentData($all_manager_user_ids, $data_query, $dates)
    {

        $data["current_amount"] = 0;
        $data["last_amount"] = 0;
        $data["data"] = [];

        $start_date = Carbon::parse($dates["start_date"]);
        $end_date = Carbon::parse(($dates["end_date"]));
        // Loop through each day in the date range
        for ($date = $start_date->copy(); $date->lte($end_date); $date->addDay()) {


            $absent_count = $this->calculateAbsent($all_manager_user_ids, $date, $data_query);


            $data["data"][] = [
                "date" => $date->toDateString(),
                "total" => $absent_count
            ];

            $data["current_amount"] = $data["current_amount"] + $absent_count;
        }


        $previous_start_date = Carbon::parse($dates["previous_start_date"]);
        $previous_end_date = Carbon::parse(($dates["previous_end_date"]));

        // Loop through each day in the date range
        for ($date = $previous_start_date->copy(); $date->lte($previous_end_date); $date->addDay()) {
            // Store the count of records for the current date
            $previous_data = $this->calculateAbsent($all_manager_user_ids, $date, $data_query);
            $data["last_amount"] = $data["last_amount"] + $previous_data;
        }

        return $data;
    }


    public function absent(
        $date_ranges,
        $all_manager_department_ids
    ) {

        $all_manager_user_ids = $this->get_all_user_of_manager($all_manager_department_ids);


        $data_query  = Attendance::where([
            "is_present" => 1
        ]);


        $data["total"] = $this->calculateAbsent($all_manager_user_ids, $date_ranges["today"], $data_query);

        $data["monthly"] = $this->getAbsentData(
            $all_manager_user_ids,
            $data_query,
            [
                "start_date" => $date_ranges["start_date_of_this_month"],
                "end_date" => $date_ranges["end_date_of_this_month"],
                "previous_start_date" => $date_ranges["start_date_of_previous_month"],
                "previous_end_date" => $date_ranges["end_date_of_previous_month"],
            ]
        );

        $data["weekly"] = $this->getAbsentData(
            $all_manager_user_ids,
            $data_query,
            [
                "start_date" => $date_ranges["start_date_of_this_week"],
                "end_date" => $date_ranges["end_date_of_this_week"],
                "previous_start_date" => $date_ranges["start_date_of_previous_week"],
                "previous_end_date" => $date_ranges["end_date_of_previous_week"],
            ]
        );


        return $data;
    }

    public function absentToday(
        $all_manager_department_ids
    ) {

        $all_manager_user_ids = $this->get_all_user_of_manager($all_manager_department_ids);



        $users = User::whereIn("id", $all_manager_user_ids)
            ->where("users.is_active", 1)
            ->whereDate("users.joining_date", "<=", today())
            ->whereDoesntHave("lastTermination", function ($query) {
                $query->where('terminations.date_of_termination', "<", today())
                    ->whereRaw('terminations.date_of_termination > users.joining_date');
            })

            ->select("id", "joining_date")->get();

        $data["total"] = 0;


        foreach ($users as $employee) {


            $work_shift_history = $this->workTimeManagementComponent->get_work_shift_history(today(), $employee->id, FALSE);

            $attendance = Attendance::where([
                "user_id" => $employee["id"],
                "is_present" => 1
            ])
                ->whereDate("in_date", today())
                ->first();

            $holiday = $this->workTimeManagementComponent->get_holiday_details(today(), $employee["id"]);


            $leave_record = LeaveRecord::whereHas("leave", function ($query) use ($employee) {
                $query->where("leaves.user_id", $employee["id"]);
            })
                ->whereDate('leave_records.date', '>=', today())
                ->whereDate('leave_records.date', '<=', today())
                ->first();


            if (!empty($work_shift_history)) {
                $work_shift_details = $this->workTimeManagementComponent->get_work_shift_details($work_shift_history, today());
            }

            if (
                !empty($work_shift_details) &&
                !$work_shift_details->is_weekend &&
                (
                    empty($holiday)
                    ||
                    (!empty($holiday) && !$holiday->is_active)
                )
                &&
                (empty($leave_record) || !in_array($leave_record->leave->leave_duration, ['single_day', 'multiple_day'])) &&
                empty($attendance)
            ) {
                $data["total"] += 1;
            }
        }


        return $data;
    }
    public function businesses(
        $is_active = null
    ) {
        // Initial query with optional filtering by active status
        $data_query = Business::where("created_by", auth()->user()->id)
            ->activeStatus($is_active);
        return $data_query->get();
    }



    public function subscription_enabled_businesses(

    ) {
        // Initial query with optional filtering by active status
        $data_query = Business::where("created_by", auth()->user()->id)
            ->where('is_self_registered_businesses', 1);

        return $data_query->get();
    }
    public function businesses_expiries(

    ) {
        // Define expiration intervals in days
        $expires_in_days = [0, 15, 30, 60];
        $today = Carbon::now()->startOfDay(); // Get today's date at the start of the day

        // Initialize an array to hold the counts
        $data = [];

        foreach ($expires_in_days as $expires_in_day) {
            // Calculate the query day based on the current day plus the expiration period
            $query_day = Carbon::now()->addDays($expires_in_day)->endOfDay(); // Get the end of the day for the query day

            $data[("expires_in_" . $expires_in_day . "_days")] = Business::where("created_by", auth()->user()->id)
                ->join('business_subscriptions', 'business_subscriptions.business_id', '=', 'businesses.id') // Move join outside of the where clauses
                ->where(function ($subQuery) use ($today, $query_day) {
                    // For active or subscribed businesses
                    $subQuery->where(function ($q) use ($today, $query_day) {
                        $q->where(function ($innerQuery) use ($today) {
                            // For businesses that are not self-registered
                            $innerQuery->where('is_self_registered_businesses', 0)
                                ->whereNotNull('trail_end_date')
                                ->whereDate('trail_end_date', '>=', $today); // Directly compare trail end date
                        })
                            ->orWhere(function ($q) use ($today, $query_day) {
                                // Subquery for self-registered businesses
                                $q->where('is_self_registered_businesses', 1)
                                    ->whereRaw('DATE(GREATEST(trail_end_date, COALESCE(business_subscriptions.end_date, "1970-01-01"))) BETWEEN ? AND ?', [$today->toDateString(), $query_day->toDateString()]); // Apply date comparison with GREATEST
                            });
                    });
                })


                ->count();
        }

        return $data;
    }



   public function presentAbsentHours($all_manager_department_ids)
{
    $all_manager_user_ids = $this->get_all_user_of_manager($all_manager_department_ids);

    $total_present_expectation = 0;
    $total_present = 0;

    $present_users = collect();
    $absent_users = collect();

    $users = User::whereIn("id", $all_manager_user_ids)
        ->where("users.is_active", 1)
        ->whereDate("users.joining_date", "<=", today())
        ->whereDoesntHave("lastTermination", function ($query) {
            $query->where('terminations.date_of_termination', "<", today())
                  ->whereRaw('terminations.date_of_termination > users.joining_date');
        })
        ->select(
        "id",
        'title',
        'first_Name',
        'middle_Name',
        'last_Name',
        "email",
        'image',

        "joining_date"
        ) // add other fields if needed
        ->get();

    foreach ($users as $user) {
        // Schedule info for today
        $schedule_info = $this->workTimeManagementComponent
            ->getScheduleInformationData(
                $user->id,
                $user->joining_date,
                $user->lastTermination->date_of_termination ?? null,
                today(),
                today()
            );

        $day_schedule_hours = $schedule_info['total_capacity_hours'] ?? 0;
        $total_present_expectation += $day_schedule_hours;

        if ($day_schedule_hours <= 0) {
            continue; // not scheduled to work today
        }

        // Attendance for this day
        $active_hours = Attendance::where([
                'is_present' => 1,
                'user_id' => $user->id,
                'business_id' => auth()->user()->business_id,
            ])
            ->whereNotIn("status", ["rejected"])
            ->whereDate('in_date', today())
            ->sum(DB::raw('total_paid_hours - overtime_hours'));

        $total_present += $active_hours;

        if ($active_hours > 0) {
            $present_users->push($user);
        } else {
            $absent_users->push($user);
        }
    }

    return [
        'total_present_expectation' => $total_present_expectation,
        'total_present' => $total_present,
        'total_absent' => max(0, $total_present_expectation - $total_present),
        'present_users' => $present_users,
        'absent_users' => $absent_users,
        'start_date' => today(),
        'end_date' => today(),
    ];
}



    public function presentAbsentDays(
        $all_manager_department_ids
    ) {

        $all_manager_user_ids = $this->get_all_user_of_manager($all_manager_department_ids);

        $total_present_expectation_days = 0;
        $total_present_days = 0;

    $present_users = collect();
    $absent_users = collect();

        $users = User::
        with(["last_attendance_record"])
        ->whereIn("id", $all_manager_user_ids)
            ->where("users.is_active", 1)
            ->whereDate("users.joining_date", "<=", today())
            ->whereDoesntHave("lastTermination", function ($query) {
                $query->where('terminations.date_of_termination', "<", today())
                    ->whereRaw('terminations.date_of_termination > users.joining_date');
            })
            ->select(
                 "id",
        'title',
        'first_Name',
        'middle_Name',
        'last_Name',
        "email",
        'image',
        "joining_date"


            )->get();

        foreach ($users as $user) {
            // Attendance for this day
            $active_hours = Attendance::where([
                'is_present' => 1,
                'user_id' => $user->id,
                'business_id' => auth()->user()->business_id,
                "status" => "approved"
            ])
                ->whereDate('in_date', today())
                ->sum(DB::raw('total_paid_hours - overtime_hours'));

            // Schedule info for this day
            $schedule_info = $this->workTimeManagementComponent
                ->getScheduleInformationData($user->id, $user->joining_date, $user->lastTermination->date_of_termination ?? NULL, today(), today());

            $day_schedule_hours = $schedule_info['total_capacity_hours'];




            if ($day_schedule_hours > 0) {
                $total_present_expectation_days += 1;
            }


           $in_time = $user->last_attendance_record?->in_time;

if ($in_time && Carbon::parse($in_time)->between(now()->subDays(1), now()->addDays(1))) {
    $total_present_days += 1;
    $present_users->push($user);
} elseif ($day_schedule_hours > 0) {
    $absent_users->push($user);
}




        }


  $online_employees = Attendance::with([
                "employee" => function ($query) {
                    $query
                    ->select(
                        'users.id',
                        'users.title',
                        'users.first_Name',
                        'users.middle_Name',
                        'users.last_Name',
                        "users.designation_id",
                        "users.image"
                    );
                },
                 "employee.designation" => function ($query) {
                    $query->select('designations.id', 'designations.name');
                },
              "attendance_records"

            ])
            ->where('attendances.is_clocked_in', 1)
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
            ->whereDate('attendances.in_date', ">=", now()->copy()->subDays(1))
            ->whereDate('attendances.in_date', "<=", now()->copy()->addDays(1))
            ->get();

        return [
            'total_present_expectation' => $total_present_expectation_days,
            'total_present' => $total_present_days,
            'total_absent' => max(0, $total_present_expectation_days - $total_present_days),
            "online_employees" => $online_employees,
            "present_users" => $present_users,
            "absent_users" => $absent_users,
            "total_employees" => $users->count(),
            'start_date' => today(),
            'end_date' => today(),
        ];
    }


    public function calculatePresent($all_manager_user_ids, $date, $data_query)
    {

        $present_count = 0;
        foreach ($all_manager_user_ids as $user_id) {

            if (!$this->checkHoliday($date, $user_id)) {

                $data_query = clone $data_query;
                $attendance = $data_query->where("in_date", $date)->first();
                if (!empty($attendance)) {
                    $present_count++;
                }
            }
        }
        return $present_count;
    }
    public function getPresentData($all_manager_user_ids, $data_query, $dates)
    {
        $data["current_amount"] = 0;
        $data["last_amount"] = 0;
        $data["data"] = [];

        $start_date = Carbon::parse($dates["start_date"]);
        $end_date = Carbon::parse(($dates["end_date"]));
        // Loop through each day in the date range
        for ($date = $start_date->copy(); $date->lte($end_date); $date->addDay()) {


            $present_count = $this->calculatePresent($all_manager_user_ids, $date, $data_query);
            $data["data"][] = [
                "date" => $date->toDateString(),
                "total" => $present_count
            ];

            $data["current_amount"] = $data["current_amount"] + $present_count;
        }


        $previous_start_date = Carbon::parse($dates["previous_start_date"]);
        $previous_end_date = Carbon::parse(($dates["previous_end_date"]));

        // Loop through each day in the date range
        for ($date = $previous_start_date->copy(); $date->lte($previous_end_date); $date->addDay()) {
            // Store the count of records for the current date
            $previous_data = $this->calculatePresent($all_manager_user_ids, $date, $data_query);
            $data["last_amount"] = $data["last_amount"] + $previous_data;
        }

        return $data;
    }
    public function present(
        $date_ranges,
        $all_manager_department_ids
    ) {

        $all_manager_user_ids = $this->get_all_user_of_manager($all_manager_department_ids);

        $data_query  = Attendance::where([
            "is_present" => 1
        ]);

        $data["total"] = $this->calculatePresent($all_manager_user_ids, today(), $data_query);

        $data["monthly"] = $this->getPresentData(
            $all_manager_user_ids,
            $data_query,
            [
                "start_date" => $date_ranges["start_date_of_this_week"],
                "end_date" => $date_ranges["end_date_of_this_week"],
                "previous_start_date" => $date_ranges["start_date_of_previous_week"],
                "previous_end_date" => $date_ranges["end_date_of_previous_week"],
            ]
        );

        $data["weekly"] = $this->getPresentData(
            $all_manager_user_ids,
            $data_query,
            [
                "start_date" => $date_ranges["start_date_of_this_week"],
                "end_date" => $date_ranges["end_date_of_this_week"],
                "previous_start_date" => $date_ranges["start_date_of_previous_week"],
                "previous_end_date" => $date_ranges["end_date_of_previous_week"],
            ]
        );

        return $data;
    }


    public function employee_on_holiday(
       $date_ranges,
        $all_manager_department_ids

    ) {
        $data_query  = User::whereHas("departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
            ->whereNotIn('id', [auth()->user()->id])

            ->whereDate("users.joining_date", "<=", today())
            ->whereDoesntHave("lastTermination", function ($query) {
                $query->where('terminations.date_of_termination', "<", today())
                    ->whereRaw('terminations.date_of_termination > users.joining_date');
            })

            ->where('is_active', 1)
            ->where("business_id", auth()->user()->id);



        $data["today"] = clone $data_query;
        $data["today"] = $data["today"]->whereHas('holidays', function ($query) {
            $query->whereDate('holidays.start_date', "<=",  today())
                ->whereDate('holidays.end_date', ">=",  today());
        })
            ->count();

        $data["date_ranges"] = $date_ranges;

        return $data;
    }
    public function upcoming_passport_expiries(
        $date_ranges,
        $all_manager_department_ids
    ) {

        $issue_date_column = 'passport_issue_date';
        $expiry_date_column = 'passport_expiry_date';





        $data_query  = EmployeePassportDetailHistory::whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
            ->where("is_current", 1)
            ->whereHas("employee", function ($query) use ($all_manager_department_ids) {
                $query
                    ->where("users.is_active", 1)
                    ->whereNotIn('users.id', [auth()->user()->id])
                    ->whereDate("users.joining_date", "<=", today())
                    ->whereDoesntHave("lastTermination", function ($query) {
                        $query->where('terminations.date_of_termination', "<", today())
                            ->whereRaw('terminations.date_of_termination > users.joining_date');
                    });
            })
            ->whereNotNull($expiry_date_column)
            ->where($expiry_date_column, ">=", today());



        $data["total_data_count"] = $data_query->count();

        $data["today"] = clone $data_query;
        $data["today"] = $data["today"]->whereBetween('passport_expiry_date', [today()->copy()->startOfDay(), today()->copy()->endOfDay()])->count();

        $data["yesterday_data_count"] = clone $data_query;
        $data["yesterday_data_count"] = $data["yesterday_data_count"]->whereBetween('passport_expiry_date', [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()])->count();

        $data["next_week"] = clone $data_query;
        $data["next_week"] = $data["next_week"]->whereBetween('passport_expiry_date', [$date_ranges["start_date_of_next_week"], ($date_ranges["end_date_of_next_week"] . ' 23:59:59')])->count();

        $data["this_week"] = clone $data_query;
        $data["this_week"] = $data["this_week"]->whereBetween('passport_expiry_date', [$date_ranges["start_date_of_this_week"], ($date_ranges["end_date_of_this_week"] . ' 23:59:59')])->count();



        $data["next_month"] = clone $data_query;
        $data["next_month"] = $data["next_month"]->whereBetween('passport_expiry_date', [$date_ranges["start_date_of_next_month"], ($date_ranges["end_date_of_next_month"] . ' 23:59:59')])->count();

        $data["this_month"] = clone $data_query;
        $data["this_month"] = $data["this_month"]->whereBetween('passport_expiry_date', [$date_ranges["start_date_of_this_month"], ($date_ranges["end_date_of_this_month"] . ' 23:59:59')])->count();


        $expires_in_days = [15, 30, 60];
        foreach ($expires_in_days as $expires_in_day) {
            $query_day = Carbon::now()->addDays($expires_in_day);
            $data[("expires_in_" . $expires_in_day . "_days")] = clone $data_query;
            $data[("expires_in_" . $expires_in_day . "_days")] = $data[("expires_in_" . $expires_in_day . "_days")]->whereBetween('passport_expiry_date', [today(), ($query_day->endOfDay() . ' 23:59:59')])->count();
        }
        $data["date_ranges"] = $date_ranges;

        return $data;
    }

    public function upcoming_visa_expiries(
        $date_ranges,
        $all_manager_department_ids
    ) {

        $issue_date_column = 'visa_issue_date';
        $expiry_date_column = 'visa_expiry_date';

        $data_query  = EmployeeVisaDetailHistory::whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
            ->where("is_current", 1)
            ->whereHas("employee", function ($query) use ($all_manager_department_ids) {
                $query
                    ->where("users.is_active", 1)
                    ->whereNotIn('users.id', [auth()->user()->id])
                    ->whereDate("users.joining_date", "<=", today())
                    ->whereDoesntHave("lastTermination", function ($query) {
                        $query->where('terminations.date_of_termination', "<", today())
                            ->whereRaw('terminations.date_of_termination > users.joining_date');
                    });
            })
            ->whereNotNull($expiry_date_column)
            ->where($expiry_date_column, ">=", today());

        $data["total_data_count"] = $data_query->count();

        $data["today"] = clone $data_query;
        $data["today"] = $data["today"]->whereBetween('visa_expiry_date', [today()->copy()->startOfDay(), today()->copy()->endOfDay()])->count();

        $data["yesterday_data_count"] = clone $data_query;
        $data["yesterday_data_count"] = $data["yesterday_data_count"]->whereBetween('visa_expiry_date', [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()])->count();

        $data["next_week"] = clone $data_query;
        $data["next_week"] = $data["next_week"]->whereBetween('visa_expiry_date', [$date_ranges["start_date_of_next_week"], ($date_ranges["end_date_of_next_week"] . ' 23:59:59')])->count();

        $data["this_week"] = clone $data_query;
        $data["this_week"] = $data["this_week"]->whereBetween('visa_expiry_date', [$date_ranges["start_date_of_this_week"], ($date_ranges["end_date_of_this_week"] . ' 23:59:59')])->count();


        $data["next_month"] = clone $data_query;
        $data["next_month"] = $data["next_month"]->whereBetween('visa_expiry_date', [$date_ranges["start_date_of_next_month"], ($date_ranges["end_date_of_next_month"] . ' 23:59:59')])->count();

        $data["this_month"] = clone $data_query;
        $data["this_month"] = $data["this_month"]->whereBetween('visa_expiry_date', [$date_ranges["start_date_of_this_month"], ($date_ranges["end_date_of_this_month"] . ' 23:59:59')])->count();


        $expires_in_days = [15, 30, 60];
        foreach ($expires_in_days as $expires_in_day) {
            $query_day = Carbon::now()->addDays($expires_in_day);
            $data[("expires_in_" . $expires_in_day . "_days")] = clone $data_query;
            $data[("expires_in_" . $expires_in_day . "_days")] = $data[("expires_in_" . $expires_in_day . "_days")]->whereBetween('visa_expiry_date', [today(), ($query_day->endOfDay() . ' 23:59:59')])->count();
        }

        $data["date_ranges"] = $date_ranges;

        return $data;
    }

    public function upcoming_right_to_work_expiries(
        $date_ranges,
        $all_manager_department_ids
    ) {

        $issue_date_column = 'right_to_work_check_date';
        $expiry_date_column = 'right_to_work_expiry_date';






        $data_query  = EmployeeRightToWorkHistory::whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
            ->where("is_current", 1)
            ->whereHas("employee", function ($query) use ($all_manager_department_ids) {
                $query
                    ->where("users.is_active", 1)
                    ->whereNotIn('users.id', [auth()->user()->id])
                    ->whereDate("users.joining_date", "<=", today())
                    ->whereDoesntHave("lastTermination", function ($query) {
                        $query->where('terminations.date_of_termination', "<", today())
                            ->whereRaw('terminations.date_of_termination > users.joining_date');
                    });
            })
            ->whereNotNull($expiry_date_column)
            ->where($expiry_date_column, ">=", today());


        $data["total_data_count"] = $data_query->count();

        $data["today"] = clone $data_query;
        $data["today"] = $data["today"]->whereBetween('right_to_work_expiry_date', [today()->copy()->startOfDay(), today()->copy()->endOfDay()])->count();

        $data["yesterday_data_count"] = clone $data_query;
        $data["yesterday_data_count"] = $data["yesterday_data_count"]->whereBetween('right_to_work_expiry_date', [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()])->count();

        $data["next_week"] = clone $data_query;
        $data["next_week"] = $data["next_week"]->whereBetween('right_to_work_expiry_date', [$date_ranges["start_date_of_next_week"], ($date_ranges["end_date_of_next_week"] . ' 23:59:59')])->count();

        $data["this_week"] = clone $data_query;
        $data["this_week"] = $data["this_week"]->whereBetween('right_to_work_expiry_date', [$date_ranges["start_date_of_this_week"], ($date_ranges["end_date_of_this_week"] . ' 23:59:59')])->count();



        $data["next_month"] = clone $data_query;
        $data["next_month"] = $data["next_month"]->whereBetween('right_to_work_expiry_date', [$date_ranges["start_date_of_next_month"], ($date_ranges["end_date_of_next_month"] . ' 23:59:59')])->count();

        $data["this_month"] = clone $data_query;
        $data["this_month"] = $data["this_month"]->whereBetween('right_to_work_expiry_date', [$date_ranges["start_date_of_this_month"], ($date_ranges["end_date_of_this_month"] . ' 23:59:59')])->count();


        $expires_in_days = [15, 30, 60];
        foreach ($expires_in_days as $expires_in_day) {
            $query_day = Carbon::now()->addDays($expires_in_day);
            $data[("expires_in_" . $expires_in_day . "_days")] = clone $data_query;
            $data[("expires_in_" . $expires_in_day . "_days")] = $data[("expires_in_" . $expires_in_day . "_days")]->whereBetween('right_to_work_expiry_date', [today(), ($query_day->endOfDay() . ' 23:59:59')])->count();
        }

        $data["date_ranges"] = $date_ranges;

        return $data;
    }

    public function upcoming_sponsorship_expiries(
        $date_ranges,
        $all_manager_department_ids
    ) {

        $issue_date_column = 'date_assigned';
        $expiry_date_column = 'expiry_date';

        $data_query  = EmployeeSponsorshipHistory::whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
            ->where("is_current", 1)
            ->whereHas("employee", function ($query) use ($all_manager_department_ids) {
                $query
                    ->where("users.is_active", 1)
                    ->whereNotIn('users.id', [auth()->user()->id])
                    ->whereDate("users.joining_date", "<=", today())
                    ->whereDoesntHave("lastTermination", function ($query) {
                        $query->where('terminations.date_of_termination', "<", today())
                            ->whereRaw('terminations.date_of_termination > users.joining_date');
                    });
            })
            ->whereNotNull($expiry_date_column)
            ->where($expiry_date_column, ">=", today());


        $data["total_data_count"] = $data_query->count();

        $data["today"] = clone $data_query;
        $data["today"] = $data["today"]->whereBetween('expiry_date', [today()->copy()->startOfDay(), today()->copy()->endOfDay()])->count();

        $data["yesterday_data_count"] = clone $data_query;
        $data["yesterday_data_count"] = $data["yesterday_data_count"]->whereBetween('expiry_date', [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()])->count();

        $data["next_week"] = clone $data_query;
        $data["next_week"] = $data["next_week"]->whereBetween('expiry_date', [$date_ranges["start_date_of_next_week"], ($date_ranges["end_date_of_next_week"] . ' 23:59:59')])->count();

        $data["this_week"] = clone $data_query;
        $data["this_week"] = $data["this_week"]->whereBetween('expiry_date', [$date_ranges["start_date_of_this_week"], ($date_ranges["end_date_of_this_week"] . ' 23:59:59')])->count();



        $data["next_month"] = clone $data_query;
        $data["next_month"] = $data["next_month"]->whereBetween('expiry_date', [$date_ranges["start_date_of_next_month"], ($date_ranges["end_date_of_next_month"] . ' 23:59:59')])->count();

        $data["this_month"] = clone $data_query;
        $data["this_month"] = $data["this_month"]->whereBetween('expiry_date', [$date_ranges["start_date_of_this_month"], ($date_ranges["end_date_of_this_month"] . ' 23:59:59')])->count();


        $expires_in_days = [15, 30, 60];
        foreach ($expires_in_days as $expires_in_day) {
            $query_day = Carbon::now()->addDays($expires_in_day);
            $data[("expires_in_" . $expires_in_day . "_days")] = clone $data_query;
            $data[("expires_in_" . $expires_in_day . "_days")] = $data[("expires_in_" . $expires_in_day . "_days")]->whereBetween('expiry_date', [today(), ($query_day->endOfDay() . ' 23:59:59')])->count();
        }

        $data["date_ranges"] = $date_ranges;

        return $data;
    }

    public function sponsorships(
        $date_ranges,
        $all_manager_department_ids,
        $current_certificate_status
    ) {

        $issue_date_column = 'date_assigned';
        $expiry_date_column = 'expiry_date';

        $data_query  = EmployeeSponsorshipHistory::whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
            ->where("is_current", 1)
            ->whereHas("employee", function ($query) use ($all_manager_department_ids) {
                $query
                    ->where("users.is_active", 1)
                    ->whereNotIn('users.id', [auth()->user()->id])
                    ->whereDate("users.joining_date", "<=", today())
                    ->whereDoesntHave("lastTermination", function ($query) {
                        $query->where('terminations.date_of_termination', "<", today())
                            ->whereRaw('terminations.date_of_termination > users.joining_date');
                    });
            })
            ->whereNotNull($expiry_date_column)
            ->where([
                "current_certificate_status" => $current_certificate_status,
                "business_id" => auth()->user()->business_id
            ]);

        $data["total_data_count"] = $data_query->count();

        $data["today"] = clone $data_query;
        $data["today"] = $data["today"]->whereBetween('expiry_date', [today()->copy()->startOfDay(), today()->copy()->endOfDay()])->count();

        $data["next_week"] = clone $data_query;
        $data["next_week"] = $data["next_week"]->whereBetween('expiry_date', [$date_ranges["start_date_of_next_week"], ($date_ranges["end_date_of_next_week"] . ' 23:59:59')])->count();

        $data["this_week"] = clone $data_query;
        $data["this_week"] = $data["this_week"]->whereBetween('expiry_date', [$date_ranges["start_date_of_this_week"], ($date_ranges["end_date_of_this_week"] . ' 23:59:59')])->count();

        $data["previous_week_data_count"] = clone $data_query;
        $data["previous_week_data_count"] = $data["previous_week_data_count"]->whereBetween('expiry_date', [$date_ranges["start_date_of_previous_week"], ($date_ranges["end_date_of_previous_week"] . ' 23:59:59')])->count();

        $data["next_month"] = clone $data_query;
        $data["next_month"] = $data["next_month"]->whereBetween('expiry_date', [$date_ranges["start_date_of_next_month"], ($date_ranges["end_date_of_next_month"] . ' 23:59:59')])->count();

        $data["this_month"] = clone $data_query;
        $data["this_month"] = $data["this_month"]->whereBetween('expiry_date', [$date_ranges["start_date_of_this_month"], ($date_ranges["end_date_of_this_month"] . ' 23:59:59')])->count();

        $data["previous_month_data_count"] = clone $data_query;
        $data["previous_month_data_count"] = $data["previous_month_data_count"]->whereBetween('expiry_date', [$date_ranges["start_date_of_previous_month"], ($date_ranges["end_date_of_previous_month"] . ' 23:59:59')])->count();
        $data["date_ranges"] = $date_ranges;

        return $data;
    }


    public function upcoming_pension_expiries(
        $date_ranges,
        $all_manager_department_ids
    ) {


        $issue_date_column = 'pension_enrollment_issue_date';
        $expiry_date_column = 'pension_re_enrollment_due_date';

        $data_query  = EmployeePensionHistory::whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
            ->where("is_current", 1)
            ->whereHas("employee", function ($query) use ($all_manager_department_ids) {
                $query
                    ->where("users.is_active", 1)
                    ->whereNotIn('users.id', [auth()->user()->id])
                    ->whereDate("users.joining_date", "<=", today())
                    ->whereDoesntHave("lastTermination", function ($query) {
                        $query->where('terminations.date_of_termination', "<", today())
                            ->whereRaw('terminations.date_of_termination > users.joining_date');
                    });
            })
            ->whereNotNull($expiry_date_column)
            ->where($expiry_date_column, ">=", today());


        $data["total_data_count"] = $data_query->count();

        $data["today"] = clone $data_query;
        $data["today"] = $data["today"]->whereBetween($expiry_date_column, [today()->copy()->startOfDay(), today()->copy()->endOfDay()])->count();

        $data["yesterday_data_count"] = clone $data_query;
        $data["yesterday_data_count"] = $data["yesterday_data_count"]->whereBetween($expiry_date_column, [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()])->count();

        $data["next_week"] = clone $data_query;
        $data["next_week"] = $data["next_week"]->whereBetween($expiry_date_column, [$date_ranges["start_date_of_next_week"], ($date_ranges["end_date_of_next_week"] . ' 23:59:59')])->count();

        $data["this_week"] = clone $data_query;
        $data["this_week"] = $data["this_week"]->whereBetween($expiry_date_column, [$date_ranges["start_date_of_this_week"], ($date_ranges["end_date_of_this_week"] . ' 23:59:59')])->count();

        $data["next_month"] = clone $data_query;
        $data["next_month"] = $data["next_month"]->whereBetween($expiry_date_column, [$date_ranges["start_date_of_next_month"], ($date_ranges["end_date_of_next_month"] . ' 23:59:59')])->count();

        $data["this_month"] = clone $data_query;
        $data["this_month"] = $data["this_month"]->whereBetween($expiry_date_column, [$date_ranges["start_date_of_this_month"], ($date_ranges["end_date_of_this_month"] . ' 23:59:59')])->count();


        $expires_in_days = [15, 30, 60];
        foreach ($expires_in_days as $expires_in_day) {
            $query_day = Carbon::now()->addDays($expires_in_day);
            $data[("expires_in_" . $expires_in_day . "_days")] = clone $data_query;
            $data[("expires_in_" . $expires_in_day . "_days")] = $data[("expires_in_" . $expires_in_day . "_days")]->whereBetween($expiry_date_column, [today(), ($query_day->endOfDay() . ' 23:59:59')])->count();
        }


        $data["date_ranges"] = $date_ranges;

        return $data;
    }
    public function pensions(
        $date_ranges,
        $all_manager_department_ids,
        $status_column,
        $status_value
    ) {

        $issue_date_column = 'pension_enrollment_issue_date';
        $expiry_date_column = 'pension_re_enrollment_due_date';

        $data_query  = EmployeePensionHistory::whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
            ->where("is_current", 1)
            ->whereHas("employee", function ($query) use ($all_manager_department_ids) {
                $query
                    ->where("users.is_active", 1)
                    ->whereNotIn('users.id', [auth()->user()->id])
                    ->whereDate("users.joining_date", "<=", today())
                    ->whereDoesntHave("lastTermination", function ($query) {
                        $query->where('terminations.date_of_termination', "<", today())
                            ->whereRaw('terminations.date_of_termination > users.joining_date');
                    });
            })
            ->whereNotNull($expiry_date_column)
            ->when(!empty($status_column), function ($query) use ($status_column, $status_value) {
                $query->where($status_column, $status_value);
            });

        $data["total_data_count"] = $data_query->count();

        $data["today"] = clone $data_query;
        $data["today"] = $data["today"]->whereBetween($expiry_date_column, [today()->copy()->startOfDay(), today()->copy()->endOfDay()])->count();

        $data["next_week"] = clone $data_query;
        $data["next_week"] = $data["next_week"]->whereBetween($expiry_date_column, [$date_ranges["start_date_of_next_week"], ($date_ranges["end_date_of_next_week"] . ' 23:59:59')])->count();

        $data["this_week"] = clone $data_query;
        $data["this_week"] = $data["this_week"]->whereBetween($expiry_date_column, [$date_ranges["start_date_of_this_week"], ($date_ranges["end_date_of_this_week"] . ' 23:59:59')])->count();

        $data["previous_week_data_count"] = clone $data_query;
        $data["previous_week_data_count"] = $data["previous_week_data_count"]->whereBetween($expiry_date_column, [$date_ranges["start_date_of_previous_week"], ($date_ranges["end_date_of_previous_week"] . ' 23:59:59')])->count();

        $data["next_month"] = clone $data_query;
        $data["next_month"] = $data["next_month"]->whereBetween($expiry_date_column, [$date_ranges["start_date_of_next_month"], ($date_ranges["end_date_of_next_month"] . ' 23:59:59')])->count();

        $data["this_month"] = clone $data_query;
        $data["this_month"] = $data["this_month"]->whereBetween($expiry_date_column, [$date_ranges["start_date_of_this_month"], ($date_ranges["end_date_of_this_month"] . ' 23:59:59')])->count();

        $data["previous_month_data_count"] = clone $data_query;
        $data["previous_month_data_count"] = $data["previous_month_data_count"]->whereBetween($expiry_date_column, [$date_ranges["start_date_of_previous_month"], ($date_ranges["end_date_of_previous_month"] . ' 23:59:59')])->count();

        $data["date_ranges"] = $date_ranges;


        return $data;
    }


    public function getEmploymentStatuses()
    {
        $created_by  = NULL;
        if (auth()->user()->business) {
            $created_by = auth()->user()->business->created_by;
        }
        $employmentStatuses = EmploymentStatus::when(empty(auth()->user()->business_id), function ($query) {
            if (auth()->user()->hasRole('superadmin')) {
                return $query->where('employment_statuses.business_id', NULL)
                    ->where('employment_statuses.is_default', 1)
                    ->where('employment_statuses.is_active', 1);
            } else {
                return $query->where('employment_statuses.business_id', NULL)
                    ->where('employment_statuses.is_default', 1)
                    ->where('employment_statuses.is_active', 1)
                    ->whereDoesntHave("disabled", function ($q) {
                        $q->whereIn("disabled_employment_statuses.created_by", [auth()->user()->id]);
                    })

                    ->orWhere(function ($query) {
                        $query->where('employment_statuses.business_id', NULL)
                            ->where('employment_statuses.is_default', 0)
                            ->where('employment_statuses.created_by', auth()->user()->id)
                            ->where('employment_statuses.is_active', 1);
                    });
            }
        })
            ->when(!empty(auth()->user()->business_id), function ($query) use ($created_by) {
                return $query->where('employment_statuses.business_id', NULL)
                    ->where('employment_statuses.is_default', 1)
                    ->where('employment_statuses.is_active', 1)
                    ->whereDoesntHave("disabled", function ($q) use ($created_by) {
                        $q->whereIn("disabled_employment_statuses.created_by", [$created_by]);
                    })
                    ->whereDoesntHave("disabled", function ($q) {
                        $q->whereIn("disabled_employment_statuses.business_id", [auth()->user()->business_id]);
                    })

                    ->orWhere(function ($query) use ($created_by) {
                        $query->where('employment_statuses.business_id', NULL)
                            ->where('employment_statuses.is_default', 0)
                            ->where('employment_statuses.created_by', $created_by)
                            ->where('employment_statuses.is_active', 1)
                            ->whereDoesntHave("disabled", function ($q) {
                                $q->whereIn("disabled_employment_statuses.business_id", [auth()->user()->business_id]);
                            });
                    })
                    ->orWhere(function ($query) {
                        $query->where('employment_statuses.business_id', auth()->user()->business_id)
                            ->where('employment_statuses.is_default', 0)
                            ->where('employment_statuses.is_active', 1);
                    });
            })->get();

        return $employmentStatuses;
    }

    public function employees_by_employment_status(
       $date_ranges,
        $all_manager_department_ids,
        $employment_status_id
    ) {

        $data_query  = User::whereHas("departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
            ->whereNotIn('id', [auth()->user()->id])
            ->where([
                "employment_status_id" => $employment_status_id
            ]);
        $data["total_data"] = $data_query->get();

        $data["total_data_count"] = $data_query->count();

        $data["today"] = clone $data_query;
        $data["today"] = $data["today"]->whereBetween('users.created_at', [today()->copy()->startOfDay(), today()->copy()->endOfDay()])->count();


        $data["yesterday_data_count"] = clone $data_query;
        $data["yesterday_data_count"] = $data["yesterday_data_count"]->whereBetween('users.created_at', [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()])->count();


        $data["this_week"] = clone $data_query;
        $data["this_week"] = $data["this_week"]->whereBetween('created_at', [$date_ranges["start_date_of_this_week"], ($date_ranges["end_date_of_this_week"] . ' 23:59:59')])->count();


        $data["previous_week_data_count"] = clone $data_query;
        $data["previous_week_data_count"] = $data["previous_week_data_count"]->whereBetween('created_at', [$date_ranges["start_date_of_previous_week"], ($date_ranges["end_date_of_previous_week"] . ' 23:59:59')])->count();


        $data["this_month"] = clone $data_query;
        $data["this_month"] = $data["this_month"]->whereBetween('created_at', [$date_ranges["start_date_of_this_month"], ($date_ranges["end_date_of_this_month"] . ' 23:59:59')])->count();


        $data["previous_month_data_count"] = clone $data_query;
        $data["previous_month_data_count"] = $data["previous_month_data_count"]->whereBetween('created_at', [$date_ranges["start_date_of_previous_month"], ($date_ranges["end_date_of_previous_month"] . ' 23:59:59')])->count();


        $data["date_ranges"] = $date_ranges;

        return $data;
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/reseller-dashboard",
     *      operationId="getResellerDashboardData",
     *      tags={"dashboard_management.reseller"},
     *       security={
     *           {"bearerAuth": {}}
     *       },


     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getResellerDashboardData(Request $request)
    {

        try {


            $data["subscription_enabled_businesses"] = $this->subscription_enabled_businesses(
            )->count();

            $data["businesses"] = $this->businesses()->count();

            $data["active_businesses"] = $this->businesses(1)->count();

            $data["deactive_businesses"] = $this->businesses(0)->count();

            $data["expiries"] = $this->businesses_expiries( );

            // Get last 12 months dates
            $months = $this->getLast12MonthsDates(request()->input("year"));

            // Fetch businesses for each month directly
            $chart_data = [];
            foreach ($months as $month) {
                $chart_data['month'] = $month['month'];
                $chart_data['data_count'] = Business::where('created_by', auth()->user()->id)
                    ->whereBetween('created_at', [$month['start_date'], $month['end_date']])
                    ->count();
            }

            // Add chart_data to response
            $data["chart_data"] = $chart_data;

            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-manager-dashboard",
     *      operationId="getBusinessManagerDashboardData",
     *      tags={"dashboard_management.business_manager"},
     *       security={
     *           {"bearerAuth": {}}
     *       },


     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessManagerDashboardData(Request $request)
    {

        try {


            $business_id = auth()->user()->business_id;


            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }

            $date_ranges = $this->dateRanges();


            $all_manager_department_ids = $this->get_all_departments_of_manager();



            $data["absent"] = $this->absent(
              $date_ranges,
                $all_manager_department_ids,

            );

            $data["present"] = $this->present(
               $date_ranges,
                $all_manager_department_ids,

            );

            $data["leaves"] = $this->leaves(
                $all_manager_department_ids,
            );

            $data =    array_merge($data, $this->leavesStructure2(
                $date_ranges,
                $all_manager_department_ids
            ));


            $data =    array_merge($data, $this->pensionsStructure2(
                $date_ranges,
                $all_manager_department_ids
            ));


            $data["holidays"] = $this->holidays($all_manager_department_ids);


            $data["expiries"] = $this->expiries(
                $date_ranges,
                $all_manager_department_ids,
            );

            $data["widgets"]["employee_on_holiday"] = $this->employee_on_holiday(
                $date_ranges,
                $all_manager_department_ids,
            );

            $data["widgets"]["employee_on_holiday"]["id"] = 2;

            $data["widgets"]["employee_on_holiday"]["widget_name"] = "employee_on_holiday";
            $data["widgets"]["employee_on_holiday"]["widget_type"] = "default";
            $data["widgets"]["employee_on_holiday"]["route"] =  '/employee/all-employees';

            $data["widgets"]["upcoming_passport_expiries"] = $this->upcoming_passport_expiries(
               $date_ranges,
                $all_manager_department_ids
            );

            $data["widgets"]["upcoming_passport_expiries"]["widget_name"] = "upcoming_passport_expiries";
            $data["widgets"]["upcoming_passport_expiries"]["widget_type"] = "multiple_upcoming_days";
            $data["widgets"]["upcoming_passport_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=passport&";


            $data["widgets"]["upcoming_visa_expiries"] = $this->upcoming_visa_expiries(
                $date_ranges,
                $all_manager_department_ids
            );

            $data["widgets"]["upcoming_visa_expiries"]["widget_name"] = "upcoming_visa_expiries";
            $data["widgets"]["upcoming_visa_expiries"]["widget_type"] = "multiple_upcoming_days";
            $data["widgets"]["upcoming_visa_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=visa&";

            $data["widgets"]["upcoming_right_to_work_expiries"] = $this->upcoming_right_to_work_expiries(
                $date_ranges,
                $all_manager_department_ids
            );


            $data["widgets"]["upcoming_right_to_work_expiries"]["widget_name"] = "upcoming_right_to_work_expiries";
            $data["widgets"]["upcoming_right_to_work_expiries"]["widget_type"] = "multiple_upcoming_days";
            $data["widgets"]["upcoming_right_to_work_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=right_to_work&";

            $data["widgets"]["upcoming_sponsorship_expiries"] = $this->upcoming_sponsorship_expiries(
                $date_ranges,
                $all_manager_department_ids
            );

            $data["widgets"]["upcoming_sponsorship_expiries"]["widget_name"] = "upcoming_sponsorship_expiries";
            $data["widgets"]["upcoming_sponsorship_expiries"]["widget_type"] = "multiple_upcoming_days";
            $data["widgets"]["upcoming_sponsorship_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=sponsorship&";

            $sponsorship_statuses = ['unassigned', 'assigned', 'visa_applied', 'visa_rejected', 'visa_grantes', 'withdrawal'];
            foreach ($sponsorship_statuses as $sponsorship_status) {
                $data["widgets"][($sponsorship_status . "_sponsorships")] = $this->sponsorships(
                   $date_ranges,
                    $all_manager_department_ids,
                    $sponsorship_status
                );

                $data["widgets"][($sponsorship_status . "_sponsorships")]["widget_name"] = ($sponsorship_status . "_sponsorships");
                $data["widgets"][($sponsorship_status . "_sponsorships")]["widget_type"] = "default";
                $data["widgets"][($sponsorship_status . "_sponsorships")]["route"] = '/employee/all-employees?sponsorship_status=' . $sponsorship_status . "&";
            }




            $data["widgets"]["upcoming_pension_expiries"] = $this->upcoming_pension_expiries(
               $date_ranges,
                $all_manager_department_ids,

            );

            $data["widgets"]["upcoming_pension_expiries"]["widget_name"] = "upcoming_pension_expiries";

            $data["widgets"]["upcoming_pension_expiries"]["widget_type"] = "multiple_upcoming_days";

            $data["widgets"]["upcoming_pension_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=pension&";


            $pension_statuses = ["opt_in", "opt_out"];
            foreach ($pension_statuses as $pension_status) {
                $data["widgets"][($pension_status . "_pensions")] = $this->pensions(
                  $date_ranges,
                    $all_manager_department_ids,
                    "pension_scheme_status",
                    $pension_status
                );

                $data["widgets"][($pension_status . "_pensions")]["widget_name"] = ($pension_status . "_pensions");
                $data["widgets"][($pension_status . "_pensions")]["widget_type"] = "default";
                $data["widgets"][($pension_status . "_pensions")]["route"] = '/employee/all-employees?pension_scheme_status=' . $pension_status . "&";
            }

            $employment_statuses = $this->getEmploymentStatuses();

            foreach ($employment_statuses as $employment_status) {
                $data["widgets"]["emplooyment_status_wise"]["data"][($employment_status->name . "_employees")] = $this->employees_by_employment_status(
                    $date_ranges,
                    $all_manager_department_ids,
                    $employment_status->id
                );


                $data["widgets"]["emplooyment_status_wise"]["widget_name"] = "employment_status_wise_employee";
                $data["widgets"]["emplooyment_status_wise"]["widget_type"] = "graph";

                $data["widgets"]["emplooyment_status_wise"]["route"] = ('/employee/?status=' . $employment_status->name . "&");
            }


            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }




    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-manager-dashboard/total-employee/{duration}",
     *      operationId="getBusinessManagerDashboardDataTotalEmployee",
     *      tags={"dashboard_management.business_manager"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *              @OA\Parameter(
     *         name="duration",
     *         in="path",
     *         description="total,today, this_month, this_week... ",
     *         required=true,
     *  example="duration"
     *      ),
     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessManagerDashboardDataTotalEmployee($duration, Request $request)
    {

        try {


            $durations = ['total', 'today', 'this_month', 'this_week'];

            if (!in_array($duration, $durations)) {
                $error =  [
                    "message" => "The given data was invalid.",
                    "errors" => ["duration" => ["Valid Durations are 'total',today,'this_month', 'this_week' "]]
                ];
                throw new Exception(json_encode($error), 422);
            }

            $business_id = auth()->user()->business_id;

            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }
$date_ranges =$this->dateRanges();

            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $data["total_employee"] = $this->total_employee(
                $date_ranges,
                $all_manager_department_ids,
                $duration
            );


            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }




    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-manager-dashboard/open-roles/{duration}",
     *      operationId="getBusinessManagerDashboardDataOpenRoles",
     *      tags={"dashboard_management.business_manager"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="duration",
     *         in="path",
     *         description="today, this_month, this_week... ",
     *         required=true,
     *  example="duration"
     *      ),

     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessManagerDashboardDataOpenRoles($duration, Request $request)
    {

        try {


            $business_id = auth()->user()->business_id;

            $durations = ['today', 'this_month', 'this_week'];
            if (!in_array($duration, $durations)) {
                $error =  [
                    "message" => "The given data was invalid.",
                    "errors" => ["duration" => ["Valid Durations are 'total',today,'this_month', 'this_week' "]]
                ];
                throw new Exception(json_encode($error), 422);
            }


            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }

            $date_ranges = $this->dateRanges();

            $data["open_roles"] = $this->open_roles(
                $date_ranges,
                $duration
            );









            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-manager-dashboard/absent",
     *      operationId="getBusinessManagerDashboardDataAbsent",
     *      tags={"dashboard_management.business_manager"},
     *       security={
     *           {"bearerAuth": {}}
     *       },


     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessManagerDashboardDataAbsent(Request $request)
    {

        try {


            $business_id = auth()->user()->business_id;


            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }

$date_ranges = $this->dateRanges();
            $all_manager_department_ids = $this->get_all_departments_of_manager();


            $data["absent"] = $this->absent(
              $date_ranges,
                $all_manager_department_ids,

            );



            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }






    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-manager-dashboard/present",
     *      operationId="getBusinessManagerDashboardDataPresent",
     *      tags={"dashboard_management.business_manager"},
     *       security={
     *           {"bearerAuth": {}}
     *       },


     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessManagerDashboardDataPresent(Request $request)
    {

        try {


            $business_id = auth()->user()->business_id;


            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }
            $date_ranges = $this->dateRanges();

            $all_manager_department_ids = $this->get_all_departments_of_manager();


            $data["present"] = $this->present(
               $date_ranges,
                $all_manager_department_ids
            );

            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-manager-dashboard/leaves/{status}/{duration}",
     *      operationId="getBusinessManagerDashboardDataLeavesByStatus",
     *      tags={"dashboard_management.business_manager"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="status",
     *         in="path",
     *         description="rejected, pending_approval... ",
     *         required=true,
     *  example="status"
     *      ),
     *              @OA\Parameter(
     *         name="duration",
     *         in="path",
     *         description="today, this_month, this_week",
     *         required=true,
     *  example="duration"
     *      ),
     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessManagerDashboardDataLeavesByStatus($status, $duration, Request $request)
    {

        try {


            $business_id = auth()->user()->business_id;



            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }
            $today = today();

            $durations = ['today', 'this_month', 'this_week'];
            if (!in_array($duration, $durations)) {
                $error =  [
                    "message" => "The given data was invalid.",
                    "errors" => ["duration" => ["Valid Durations are 'total',today,'this_month', 'this_week' "]]
                ];
                throw new Exception(json_encode($error), 422);
            }

  $date_ranges = $this->dateRanges();

            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $data =    $this->getLeavesStructure3(
                $date_ranges,
                $all_manager_department_ids,
                $status,
                $duration


            );


            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-manager-dashboard/pensions/{status}/{duration}",
     *      operationId="getBusinessManagerDashboardDataPensionsByStatus",
     *      tags={"dashboard_management.business_manager"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="status",
     *         in="path",
     *         description="opt_in, opt_out",
     *         required=true,
     *  example="status"
     *      ),
     *    *              @OA\Parameter(
     *         name="duration",
     *         in="path",
     *         description="today, this_month, this_week",
     *         required=true,
     *  example="duration"
     *      ),

     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessManagerDashboardDataPensionsByStatus($status, $duration, Request $request)
    {

        try {


            $business_id = auth()->user()->business_id;

            $durations = ['today', 'this_month', 'this_week'];
            if (!in_array($duration, $durations)) {
                $error =  [
                    "message" => "The given data was invalid.",
                    "errors" => ["duration" => ["Valid Durations are 'total',today,'this_month', 'this_week' "]]
                ];
                throw new Exception(json_encode($error), 422);
            }

            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }
  $date_ranges = $this->dateRanges();
            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $data = [];
            $data =    $this->getPensionsStructure3(
                $date_ranges,
                $all_manager_department_ids,
                $status,
                $duration
            );


            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-manager-dashboard/pensions",
     *      operationId="getBusinessManagerDashboardDataPensions",
     *      tags={"dashboard_management.business_manager"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessManagerDashboardDataPensions(Request $request)
    {

        try {

            $business_id = auth()->user()->business_id;
            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $data = [];

            $data = $this->getPensionsStructure4(
                $all_manager_department_ids
            );

            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }




    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-manager-dashboard/holidays",
     *      operationId="getBusinessManagerDashboardDataHolidays",
     *      tags={"dashboard_management.business_manager"},
     *       security={
     *           {"bearerAuth": {}}
     *       },


     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessManagerDashboardDataHolidays(Request $request)
    {

        try {


            $business_id = auth()->user()->business_id;


            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }


            $all_manager_department_ids = $this->get_all_departments_of_manager();



            $data["holidays"] = $this->holidays($all_manager_department_ids);



            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-manager-dashboard/leaves",
     *      operationId="getBusinessManagerDashboardDataLeaves",
     *      tags={"dashboard_management.business_manager"},
     *       security={
     *           {"bearerAuth": {}}
     *       },


     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessManagerDashboardDataLeaves(Request $request)
    {

        try {


            $business_id = auth()->user()->business_id;


            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();


            $data["leaves"] = $this->leaves(
                $all_manager_department_ids,
            );



            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-employee-dashboard/leaves",
     *      operationId="getBusinessEmployeeDashboardDataLeaves",
     *      tags={"dashboard_management.business_user"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      *              @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="total,today, this_month, this_week... ",
     *         required=true,
     *  example="year"
     *      ),


     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessEmployeeDashboardDataLeaves(Request $request)
    {

        try {


            $business_id = auth()->user()->business_id;


            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }

            $data["leaves"] = $this->employeeLeaves();


            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-manager-dashboard/leaves-holidays",
     *      operationId="getBusinessManagerDashboardDataLeavesAndHolidays",
     *      tags={"dashboard_management.business_manager"},
     *       security={
     *           {"bearerAuth": {}}
     *       },


     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessManagerDashboardDataLeavesAndHolidays(Request $request)
    {

        try {


            $business_id = auth()->user()->business_id;

            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();


            $data["leaves"] = $this->getLeavesStructure4(
                $all_manager_department_ids,
            );

            $data["holidays"] = $this->getHolidaysStructure4($all_manager_department_ids);



            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }





    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-manager-dashboard/pension-expiries/{duration}",
     *      operationId="getBusinessManagerDashboardDataPensionExpiries",
     *      tags={"dashboard_management.business_manager"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *              @OA\Parameter(
     *         name="duration",
     *         in="path",
     *         description="today, this_month, this_week, previous_month, next_month, previous_week, next_week... ",
     *         required=true,
     *  example="duration"
     *      ),

     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessManagerDashboardDataPensionExpiries($duration, Request $request)
    {

        try {


            $business_id = auth()->user()->business_id;

            $durations = ['today', 'this_month', 'previous_month', 'next_month', 'this_week', 'previous_week', 'next_week'];
            if (!in_array($duration, $durations)) {
                $error =  [
                    "message" => "The given data was invalid.",
                    "errors" => ["duration" => ["Valid Durations are 'today','this_month', 'previous_month', 'next_month' ,'this_week', 'previous_week','next_week' "]]
                ];
                throw new Exception(json_encode($error), 422);
            }



            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }

            $date_ranges = $this->dateRanges();

            $all_manager_department_ids = $this->get_all_departments_of_manager();



            $data["pension"] = $this->getPensionExpiries(
                $date_ranges,
                $all_manager_department_ids,
                $duration

            );






            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-manager-dashboard/combined-expiries",
     *      operationId="getBusinessManagerDashboardDataCombinedExpiries",
     *      tags={"dashboard_management.business_manager"},
     *       security={
     *           {"bearerAuth": {}}
     *       },


     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessManagerDashboardDataCombinedExpiries(Request $request)
    {

        try {


            $business_id = auth()->user()->business_id;


            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }

            $date_ranges = $this->dateRanges();







            $all_manager_department_ids = $this->get_all_departments_of_manager();




            $data["pension"] = $this->getPensionExpiries(
                $date_ranges,
                $all_manager_department_ids,
                "today",
                1
            );

            $data["passport"] = $this->getPassportExpiries(
                $date_ranges,
                $all_manager_department_ids,
                "today",
                1

            );



            $data["visa"] = $this->getVisaExpiries(
                $date_ranges,
                $all_manager_department_ids,
                "today",
                1
            );



            $data["right_to_work"] = $this->getRightToWorkExpiries(
                $date_ranges,
                $all_manager_department_ids,
                "today",
                1
            );

            $data["sponsorship"] = $this->getSponsorshipExpiries(
                $date_ranges,
                $all_manager_department_ids,
                "today",
                1
            );


            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }





    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-manager-dashboard/passport-expiries/{duration}",
     *      operationId="getBusinessManagerDashboardDataPassportExpiries",
     *      tags={"dashboard_management.business_manager"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *
     *  *              @OA\Parameter(
     *         name="duration",
     *         in="path",
     *         description="today, this_month, this_week, previous_month, next_month, previous_week, next_week... ",
     *         required=true,
     *  example="duration"
     *      ),


     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessManagerDashboardDataPassportExpiries($duration, Request $request)
    {

        try {



            $durations = ['today', 'this_month', 'previous_month', 'next_month', 'this_week', 'previous_week', 'next_week'];
            if (!in_array($duration, $durations)) {
                $error =  [
                    "message" => "The given data was invalid.",
                    "errors" => ["duration" => ["Valid Durations are 'today','this_month', 'previous_month', 'next_month' ,'this_week', 'previous_week','next_week' "]]
                ];
                throw new Exception(json_encode($error), 422);
            }

            $business_id = auth()->user()->business_id;


            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }

            $date_ranges = $this->dateRanges();

            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $data["passport"] = $this->getPassportExpiries(
                $date_ranges,
                $all_manager_department_ids,
                $duration
            );






            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-manager-dashboard/visa-expiries/{duration}",
     *      operationId="getBusinessManagerDashboardDataVisaExpiries",
     *      tags={"dashboard_management.business_manager"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="duration",
     *         in="path",
     *         description="today, this_month, this_week, previous_month, next_month, previous_week, next_week... ",
     *         required=true,
     *  example="duration"
     *      ),

     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessManagerDashboardDataVisaExpiries($duration, Request $request)
    {

        try {


            $business_id = auth()->user()->business_id;

            $durations = ['today', 'this_month', 'previous_month', 'next_month', 'this_week', 'previous_week', 'next_week'];
            if (!in_array($duration, $durations)) {
                $error =  [
                    "message" => "The given data was invalid.",
                    "errors" => ["duration" => ["Valid Durations are 'today','this_month', 'previous_month', 'next_month' ,'this_week', 'previous_week','next_week' "]]
                ];
                throw new Exception(json_encode($error), 422);
            }



            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }

            $date_ranges = $this->dateRanges();
            $all_manager_department_ids = $this->get_all_departments_of_manager();



            $data["visa"] = $this->getVisaExpiries(
                $date_ranges,
                $all_manager_department_ids,
                $duration

            );




            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-manager-dashboard/right-to-work-expiries/{duration}",
     *      operationId="getBusinessManagerDashboardDataRightToWorkExpiries",
     *      tags={"dashboard_management.business_manager"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *              @OA\Parameter(
     *         name="duration",
     *         in="path",
     *         description="today, this_month, this_week, previous_month, next_month, previous_week, next_week... ",
     *         required=true,
     *  example="duration"
     *      ),

     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessManagerDashboardDataRightToWorkExpiries($duration, Request $request)
    {

        try {


            $business_id = auth()->user()->business_id;


            $durations = ['today', 'this_month', 'previous_month', 'next_month', 'this_week', 'previous_week', 'next_week'];
            if (!in_array($duration, $durations)) {
                $error =  [
                    "message" => "The given data was invalid.",
                    "errors" => ["duration" => ["Valid Durations are 'today','this_month', 'previous_month', 'next_month' ,'this_week', 'previous_week','next_week' "]]
                ];
                throw new Exception(json_encode($error), 422);
            }


            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }

       $date_ranges = $this->dateRanges();
       $all_manager_department_ids = $this->get_all_departments_of_manager();


            $data["right_to_work"] = $this->getRightToWorkExpiries(
                $date_ranges,
                $all_manager_department_ids,
                $duration
            );

            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-manager-dashboard/sponsorship-expiries/{duration}",
     *      operationId="getBusinessManagerDashboardDataSponsorshipExpiries",
     *      tags={"dashboard_management.business_manager"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *              @OA\Parameter(
     *         name="duration",
     *         in="path",
     *         description="today, this_month, this_week, previous_month, next_month, previous_week, next_week... ",
     *         required=true,
     *  example="duration"
     *      ),

     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessManagerDashboardDataSponsorshipExpiries($duration, Request $request)
    {

        try {


            $business_id = auth()->user()->business_id;

            $durations = ['today', 'this_month', 'previous_month', 'next_month', 'this_week', 'previous_week', 'next_week'];
            if (!in_array($duration, $durations)) {
                $error =  [
                    "message" => "The given data was invalid.",
                    "errors" => ["duration" => ["Valid Durations are 'today','this_month', 'previous_month', 'next_month' ,'this_week', 'previous_week','next_week' "]]
                ];
                throw new Exception(json_encode($error), 422);
            }


            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }
           $date_ranges = $this->dateRanges();

            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $data["sponsorship"] = $this->getSponsorshipExpiries(
                $date_ranges,
                $all_manager_department_ids,
                $duration
            );




            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }











    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-manager-dashboard/present-absent-hours",
     *      operationId="getBusinessManagerDashboardDataPresentAbsentHours",
     *      tags={"dashboard_management.business_manager"},
     *       security={
     *           {"bearerAuth": {}}
     *       },


     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessManagerDashboardDataPresentAbsentHours(Request $request)
    {

        try {


            $business_id = auth()->user()->business_id;


            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }




            $all_manager_department_ids = $this->get_all_departments_of_manager();



            $data["present_absent"] = $this->presentAbsentHours(
                $all_manager_department_ids,

            );



            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-manager-dashboard/present-absent-days",
     *      operationId="getBusinessManagerDashboardDataPresentAbsentDays",
     *      tags={"dashboard_management.business_manager"},
     *       security={
     *           {"bearerAuth": {}}
     *       },


     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessManagerDashboardDataPresentAbsentDays(Request $request)
    {

        try {


            $business_id = auth()->user()->business_id;


            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();


            $data["present_absent"] = $this->presentAbsentDays(
                $all_manager_department_ids,
            );





            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-manager-dashboard/other-widgets",
     *      operationId="getBusinessManagerDashboardDataOtherWidgets",
     *      tags={"dashboard_management.business_manager"},
     *       security={
     *           {"bearerAuth": {}}
     *       },


     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessManagerDashboardDataOtherWidgets(Request $request)
    {

        try {


            $business_id = auth()->user()->business_id;


            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }

$date_ranges = $this->dateRanges();
            $all_manager_department_ids = $this->get_all_departments_of_manager();


            $data["widgets"]["employee_on_holiday"] = $this->employee_on_holiday(
               $date_ranges,
                $all_manager_department_ids,

            );


            $data["widgets"]["employee_on_holiday"]["id"] = 2;


            $data["widgets"]["employee_on_holiday"]["widget_name"] = "employee_on_holiday";
            $data["widgets"]["employee_on_holiday"]["widget_type"] = "default";
            $data["widgets"]["employee_on_holiday"]["route"] =  '/employee/all-employees';



            $data["widgets"]["upcoming_passport_expiries"] = $this->upcoming_passport_expiries(
               $date_ranges,
                $all_manager_department_ids
            );


            $data["widgets"]["upcoming_passport_expiries"]["widget_name"] = "upcoming_passport_expiries";
            $data["widgets"]["upcoming_passport_expiries"]["widget_type"] = "multiple_upcoming_days";
            $data["widgets"]["upcoming_passport_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=passport&";

            $data["widgets"]["upcoming_visa_expiries"] = $this->upcoming_visa_expiries(
               $date_ranges,
                $all_manager_department_ids
            );


            $data["widgets"]["upcoming_visa_expiries"]["widget_name"] = "upcoming_visa_expiries";
            $data["widgets"]["upcoming_visa_expiries"]["widget_type"] = "multiple_upcoming_days";
            $data["widgets"]["upcoming_visa_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=visa&";





            $data["widgets"]["upcoming_right_to_work_expiries"] = $this->upcoming_right_to_work_expiries(
              $date_ranges,
                $all_manager_department_ids
            );




            $data["widgets"]["upcoming_right_to_work_expiries"]["widget_name"] = "upcoming_right_to_work_expiries";
            $data["widgets"]["upcoming_right_to_work_expiries"]["widget_type"] = "multiple_upcoming_days";
            $data["widgets"]["upcoming_right_to_work_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=right_to_work&";



            $data["widgets"]["upcoming_sponsorship_expiries"] = $this->upcoming_sponsorship_expiries(
               $date_ranges,
                $all_manager_department_ids
            );




            $data["widgets"]["upcoming_sponsorship_expiries"]["widget_name"] = "upcoming_sponsorship_expiries";
            $data["widgets"]["upcoming_sponsorship_expiries"]["widget_type"] = "multiple_upcoming_days";
            $data["widgets"]["upcoming_sponsorship_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=sponsorship&";



            $sponsorship_statuses = ['unassigned', 'assigned', 'visa_applied', 'visa_rejected', 'visa_grantes', 'withdrawal'];
            foreach ($sponsorship_statuses as $sponsorship_status) {
                $data["widgets"][($sponsorship_status . "_sponsorships")] = $this->sponsorships(
                   $date_ranges,
                    $all_manager_department_ids,
                    $sponsorship_status
                );





                $data["widgets"][($sponsorship_status . "_sponsorships")]["widget_name"] = ($sponsorship_status . "_sponsorships");

                $data["widgets"][($sponsorship_status . "_sponsorships")]["widget_type"] = "default";
                $data["widgets"][($sponsorship_status . "_sponsorships")]["route"] = '/employee/all-employees?sponsorship_status=' . $sponsorship_status . "&";
            }


            $data["widgets"]["upcoming_pension_expiries"] = $this->upcoming_pension_expiries(
              $date_ranges,
                $all_manager_department_ids,

            );

            $data["widgets"]["upcoming_pension_expiries"]["widget_name"] = "upcoming_pension_expiries";

            $data["widgets"]["upcoming_pension_expiries"]["widget_type"] = "multiple_upcoming_days";

            $data["widgets"]["upcoming_pension_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=pension&";


            $pension_statuses = ["opt_in", "opt_out"];
            foreach ($pension_statuses as $pension_status) {
                $data["widgets"][($pension_status . "_pensions")] = $this->pensions(
                   $date_ranges,
                    $all_manager_department_ids,
                    "pension_scheme_status",
                    $pension_status
                );


                $data["widgets"][($pension_status . "_pensions")]["widget_name"] = ($pension_status . "_pensions");
                $data["widgets"][($pension_status . "_pensions")]["widget_type"] = "default";
                $data["widgets"][($pension_status . "_pensions")]["route"] = '/employee/all-employees?pension_scheme_status=' . $pension_status . "&";
            }

            $employment_statuses = $this->getEmploymentStatuses();

            foreach ($employment_statuses as $employment_status) {
                $data["widgets"]["emplooyment_status_wise"]["data"][($employment_status->name . "_employees")] = $this->employees_by_employment_status(
                   $date_ranges,
                    $all_manager_department_ids,
                    $employment_status->id
                );

                $data["widgets"]["emplooyment_status_wise"]["widget_name"] = "employment_status_wise_employee";
                $data["widgets"]["emplooyment_status_wise"]["widget_type"] = "graph";

                $data["widgets"]["emplooyment_status_wise"]["route"] = ('/employee/?status=' . $employment_status->name . "&");
            }


            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }




    public function getHolidayData($user_id = NULL)
    {

        $data =  Holiday::where(
            [
                "holidays.business_id" => auth()->user()->business_id
            ]
        )
            ->when(!empty($user_id), function ($query) use ($user_id) {
                $query->where(function ($query) use ($user_id) {
                    $query->whereHas("employees", function ($query) use ($user_id) {
                        $query->where("users.id", $user_id);
                    })
                        ->orWhere("holidays.is_holiday_for_all", 1);
                });
            })

            ->where("start_date", ">", today())
            ->orderBy('start_date', "ASC")


            ->first();
        return $data;
    }
 public function getHolidayDataV2($user_id = NULL)
    {

        $data =  Holiday::where(
            [
                "holidays.business_id" => auth()->user()->business_id
            ]
        )
            ->when(!empty($user_id), function ($query) use ($user_id) {
                $query->where(function ($query) use ($user_id) {
                    $query->whereHas("employees", function ($query) use ($user_id) {
                        $query->where("users.id", $user_id);
                    })
                        ->orWhere("holidays.is_holiday_for_all", 1);
                });
            })

            ->where("start_date", ">", today())
            ->orderBy('start_date', "ASC")

            ->select("holidays.id","holidays.name","holidays.start_date","holidays.end_date",)

            ->first();
        return $data;
    }

    public function getNotifications($status = "")
    {
        $data = Notification::with("sender", "business")
            ->where(
                [
                    "receiver_id" => auth()->user()->id
                ]
            )
            ->when(!empty($status), function ($query) use ($status) {
                $query->where("notifications.status", $status);
            })

            ->orderBy("notifications.id", "DESC")
            ->take(6)->get();

        return $data;
    }
  public function getNotificationsV2($status = "")
    {
        $data = Notification::
            where(
                [
                    "receiver_id" => auth()->user()->id
                ]
            )
            ->when(!empty($status), function ($query) use ($status) {
                $query->where("notifications.status", $status);
            })
            ->orderBy("notifications.id", "DESC")
            ->select(
                "notifications.id",
                "notifications.status",
                "notifications.type",
                "notifications.notification_link",
        "notifications.entity_name",
        "notifications.entity_ids",
        "notifications.start_date",
        "notifications.end_date",
        "notifications.notification_title",
        "notifications.notification_description",
        "notifications.created_at"
        )


            ->take(6)->get();

        return $data;
    }

    public function getAnnouncements($all_parent_department_ids)
    {

        $this->addAnnouncementIfMissing($all_parent_department_ids);

        $data = Announcement::with([
            "creator" => function ($query) {
                $query->select(
                    'users.id',
                    'users.title',
                    'users.first_Name',
                    'users.middle_Name',
                    'users.last_Name'
                );
            },
            "departments" => function ($query) {
                $query->select('departments.id', 'departments.name'); // Specify the fields for the creator relationship
            },
        ])
            ->where(
                [
                    "announcements.business_id" => auth()->user()->business_id
                ]
            )
            ->whereHas("users", function ($query) {
                $query->where("user_announcements.user_id", auth()->user()->id);
            })
            ->orderBy("created_at", "DESC")
            ->take(7)->get();


        return $data;
    }

  public function getAnnouncementsV2($all_parent_department_ids)
    {

        $this->addAnnouncementIfMissing($all_parent_department_ids);

        $data = Announcement::
            where(
                [
                    "announcements.business_id" => auth()->user()->business_id
                ]
            )
            ->whereHas("users", function ($query) {
                $query->where("user_announcements.user_id", auth()->user()->id);
            })
            ->select("announcements.id","announcements.name","announcements.end_date","announcements.start_date")

            ->orderBy("created_at", "DESC")
            ->take(7)->get();


        return $data;
    }

    public function getOngoingProjects()
    {

      $data = Project::with([
        "creator" => function ($query) {
            $query->select(
                'users.id',
                'users.title',
                'users.first_Name',
                'users.middle_Name',
                'users.last_Name'
            );
        },
        "users" => function ($query) {
            $query->select(
                'users.id',
                'users.title',
                'users.first_Name',
                'users.middle_Name',
                'users.last_Name'
            );
        },
    ])
    ->withCount(['tasks as total_task', 'completed_tasks as total_completed'])
    ->where("business_id", auth()->user()->business_id)
    ->whereHas('users', function ($query) {
        $query->where("users.id", auth()->user()->id);
    })
    ->get();

        return $data;
    }
public function getOngoingProjectsV2()
    {
        $data = Project::with([
        "creator" => function ($query) {
            $query->select('users.id', 'users.title', 'users.first_Name', 'users.middle_Name', 'users.last_Name');
        },
        "users" => function ($query) {
            $query->select('users.id', 'users.title', 'users.first_Name', 'users.middle_Name', 'users.last_Name');
        },
    ])
    ->where("business_id", auth()->user()->business_id)
    ->whereHas('users', function ($query) {
        $query->where("users.id", auth()->user()->id);
    })
    ->selectRaw("
        projects.id,
        projects.name,
        projects.end_date,
        projects.status,
        (SELECT COUNT(*) FROM tasks WHERE tasks.project_id = projects.id) AS tasks_count,
        (SELECT COUNT(*) FROM tasks WHERE tasks.project_id = projects.id AND tasks.status = 'done') AS completed_tasks_count
    ")
    ->get();

        return $data;
    }

  /**
     *
     * @OA\Get(
     *      path="/v1.0/business-employee-dashboard",
     *      operationId="getBusinessEmployeeDashboardData",
     *      tags={"dashboard_management.business_user"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessEmployeeDashboardData(Request $request)
    {

        try {


            $business_id = auth()->user()->business_id;
            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }

            $all_parent_department_ids = $this->departmentComponent->all_parent_departments_of_user(auth()->user()->id);

            $data["upcoming_holiday"] = $this->getHolidayData(auth()->user()->id);
            $data["notifications"] = $this->getNotifications("unread");
            $data["announcements"] = $this->getAnnouncements($all_parent_department_ids);
            $data["on_going_projects"] = $this->getOngoingProjects();

            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v2.0/business-employee-dashboard",
     *      operationId="getBusinessEmployeeDashboardDataV2",
     *      tags={"dashboard_management.business_user"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
     *
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessEmployeeDashboardDataV2(Request $request)
    {

        try {


            $business_id = auth()->user()->business_id;
            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }

            $all_parent_department_ids = $this->departmentComponent->all_parent_departments_of_user(auth()->user()->id);

            $data["upcoming_holiday"] = $this->getHolidayDataV2(auth()->user()->id);
            $data["notifications"] = $this->getNotificationsV2("unread");
            $data["announcements"] = $this->getAnnouncementsV2($all_parent_department_ids);
            $data["on_going_projects"] = $this->getOngoingProjectsV2();

            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}
