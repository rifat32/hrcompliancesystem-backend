<?php

namespace App\Http\Components;


use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Models\Attendance;

use App\Models\Department;
use App\Models\LeaveRecord;
use App\Models\User;
use App\Models\UserAssetHistory;
use App\Models\UserRecruitmentProcess;
use Carbon\Carbon;
use Exception;

class UserManagementComponent
{

    use BasicUtil, BusinessUtil;


    protected $leaveComponent;
    protected $attendanceComponent;
    protected $workTimeManagementComponent;

    public function __construct(LeaveComponent $leaveComponent, AttendanceComponent $attendanceComponent, WorkTimeManagementComponent $workTimeManagementComponent)
    {



        $this->leaveComponent = $leaveComponent;
        $this->attendanceComponent = $attendanceComponent;
        $this->workTimeManagementComponent = $workTimeManagementComponent;
    }





    public function getRecruitmentProcessesByUserIdFunc($id, $all_manager_department_ids)
    {
        $user = $this->validateUserQuery($id, $all_manager_department_ids);

        $user_recruitment_processes = UserRecruitmentProcess::with("recruitment_process", "tasks")
            ->where([
                "user_id" => $user->id
            ])

            ->get();

        return $user_recruitment_processes;
    }





    public function getTotalPresentHours($user_id, $start_date, $end_date)
    {

        $attendances = Attendance::where([
            "user_id" => $user_id
        ])
            ->whereDate('in_date', '>=', $start_date . ' 00:00:00')
            ->whereDate('in_date', '<=', ($end_date . ' 23:59:59'))
            ->get();


        $total_regular_hours = 0;
        $total_overtime_hours = 0;

        foreach ($attendances as $attendance) {
            $present_hours = $this->attendanceComponent->calculate_total_present_hours($attendance->attendance_records);
            $overtime_hours = $this->attendanceComponent->calculateOvertime($attendance);
            $regular_hours = $present_hours - $overtime_hours;

            $total_regular_hours += $regular_hours;
            $total_overtime_hours += $overtime_hours;
        }

        return [
            "total_regular_hours" => number_format($total_regular_hours, 2),
            "total_overtime_hours" => number_format($total_overtime_hours, 2)
        ];
    }







    public function getRotaData($user, $joining_date, $date_of_termination)
    {



           $week_dates = $this->getWeekDates();

        $start_date_of_this_week = Carbon::parse($week_dates["start_date_of_this_week"]);
        $end_date_of_this_week = Carbon::parse($week_dates["end_date_of_this_week"]);

        $week_dates = $this->manipulateJoiningDateTerminationDate($user->joining_date, $date_of_termination, $start_date_of_this_week, $end_date_of_this_week);
        $start_date_of_this_week = Carbon::parse($week_dates["start_date"]);
        $end_date_of_this_week = Carbon::parse($week_dates["end_date"]);


        $today_dates = $this->manipulateJoiningDateTerminationDate($user->joining_date, $date_of_termination, Carbon::today(), Carbon::today());
        $startOfToday = Carbon::parse($today_dates["start_date"]);
        $endOfToday = Carbon::parse($today_dates["end_date"]);

        $month_dates = $this->manipulateJoiningDateTerminationDate($user->joining_date, $date_of_termination, Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());

        $startOfMonth = Carbon::parse($month_dates["start_date"]);
        $endOfMonth = Carbon::parse($month_dates["end_date"]);


        $data["today"]["total_capacity_hours"] = $this->workTimeManagementComponent->getScheduleInformationData($user->id, $joining_date, $date_of_termination, $startOfToday, $endOfToday)["total_capacity_hours"];
        $data["today"]["total_present_hours"] = $this->getTotalPresentHours($user->id, $startOfToday, $endOfToday);

        $data["this_week"]["start_date"] = $start_date_of_this_week;
        $data["this_week"]["end_date"] = $end_date_of_this_week;

        $data["this_week"]["total_capacity_hours"] = $this->workTimeManagementComponent->getScheduleInformationData($user->id, $joining_date, $date_of_termination, $start_date_of_this_week, $end_date_of_this_week)["total_capacity_hours"];
        $data["this_week"]["total_present_hours"] = $this->getTotalPresentHours($user->id, $start_date_of_this_week, $end_date_of_this_week);

        $data["this_month"]["start_date"] = $startOfMonth;
        $data["this_month"]["end_date"] = $endOfMonth;

        $data["this_month"]["total_capacity_hours"] = $this->workTimeManagementComponent->getScheduleInformationData($user->id, $joining_date, $date_of_termination, $startOfMonth, $endOfMonth)["total_capacity_hours"];
        $data["this_month"]["total_present_hours"] = $this->getTotalPresentHours($user->id, $startOfMonth, $endOfMonth);

        return $data;
    }


    public function getHolodayDetails($userId, $start_date = NULL, $end_date = NULL, $is_including_attendance = false, $throwErr = true)
    {
        // Retrieve the user based on the provided ID, ensuring it belongs to one of the managed departments
        $user = User::where([
            "id" => $userId
        ])
            ->first();

        // If no user found, return 404 error
        if (!$user) {
            return response()->json([
                "message" => "no user found"
            ], 404);
        }



        // Set start and end date for the holiday period
        $start_date = !empty($start_date) ? $start_date : Carbon::now()->startOfYear()->format('Y-m-d');
        $end_date = !empty($end_date) ? $end_date : Carbon::now()->endOfYear()->format('Y-m-d');


        $date_of_termination = $user->lastTermination->date_of_termination ?? NULL;
        $dates = $this->manipulateJoiningDateTerminationDate($user->joining_date, $date_of_termination, $start_date, $end_date);
        $holiday_start_date = Carbon::parse($dates["start_date"]);
        $holiday_end_date = Carbon::parse($dates["end_date"]);

        // Process holiday dates
        $holiday_dates =  $this->workTimeManagementComponent->get_holiday_dates($holiday_start_date, $holiday_end_date, $user->id);

        // Retrieve work shift histories for the user within the specified period
        $work_shift_histories = $this->workTimeManagementComponent->get_work_shift_histories($start_date, $end_date, $user->id, $throwErr);

        // Initialize an empty collection to store weekend dates

        if (empty($work_shift_histories)) {
            $weekend_dates = [];
        } else {
            $weekend_dates = $this->workTimeManagementComponent->get_weekend_dates($start_date, $end_date, $work_shift_histories);
        }

        // Process already taken leave dates
        $already_taken_leave_dates = $this->workTimeManagementComponent->get_already_taken_leave_record_dates($start_date, $end_date, $user->id);

        $result_collection = collect($holiday_dates)->merge($weekend_dates)->merge($already_taken_leave_dates);


        if (isset($is_including_attendance)) {
            // Process already taken attendance dates
            $already_taken_attendance_dates = $this->attendanceComponent->get_already_taken_attendance_dates($user->id, $start_date, $end_date);
            if (intval($is_including_attendance) == 1) {
                $result_collection = $result_collection->merge($already_taken_attendance_dates);
            }
        }


        $unique_result_collection = $result_collection->unique();

        $result_array = $unique_result_collection->values()->all();

        return $result_array;
    }

    public function getLastActivityDate($user) {
            $lastAttendanceDate = Attendance::where([
                "user_id" => $user->id
            ])->orderBy("in_date", "desc")->first();

            $lastLeaveDate = LeaveRecord::whereHas("leave", function ($query) use ($user) {
                $query->where("leaves.user_id", $user->id);
            })->orderBy("leave_records.date", "desc")->first();

            $lastAssetAssignDate = UserAssetHistory::where([
                "user_id" => $user->id
            ])->orderBy("from_date", "desc")->first();

            // Convert the dates to Carbon instances for comparison
            $lastAttendanceDate = $lastAttendanceDate ? Carbon::parse($lastAttendanceDate->in_date) : null;
            $lastLeaveDate = $lastLeaveDate ? Carbon::parse($lastLeaveDate->date) : null;
            $lastAssetAssignDate = $lastAssetAssignDate ? Carbon::parse($lastAssetAssignDate->from_date) : null;

            // Find the latest date
            $latestDate = null;

            if ($lastAttendanceDate && (!$latestDate || $lastAttendanceDate->gt($latestDate))) {
                $latestDate = $lastAttendanceDate;
            }

            if ($lastLeaveDate && (!$latestDate || $lastLeaveDate->gt($latestDate))) {
                $latestDate = $lastLeaveDate;
            }

            if ($lastAssetAssignDate && (!$latestDate || $lastAssetAssignDate->gt($latestDate))) {
                $latestDate = $lastAssetAssignDate;
            }

            return $latestDate;
    }


}
