<?php

namespace App\Console\Commands;

use App\Http\Components\WorkTimeManagementComponent;
use Illuminate\Console\Command;
use App\Models\Attendance;

use App\Models\SettingAttendance;
use App\Models\User;
use App\Services\FirebaseService;
use Carbon\Carbon;



class NotifyLateEmployeesCommand extends Command
{
    protected $signature = 'notify:late-employees';
    protected $description = 'Notify employees who are late and have not clocked in';

    protected $workTimeManagementComponent;
    protected $firebase;

    public function __construct(WorkTimeManagementComponent $workTimeManagementComponent, FirebaseService $firebase)
    {
        parent::__construct(); // Missing parent constructor call
        $this->workTimeManagementComponent = $workTimeManagementComponent;
        $this->firebase = $firebase;
    }


    public function handle()
    {
        // Open log file
        $logFile = storage_path('logs/notification.log');
        $logHandle = fopen($logFile, 'a');
        fwrite($logHandle, "Notification started at " . now() . "\n");

        $now_utc = Carbon::now('UTC');
        $employees = User::whereNotNull("user_time_zone")
            ->whereNotNull("business_id")
            ->where("users.is_active", 1)
            ->whereDate("users.joining_date", "<=", today())
            ->whereDoesntHave("lastTermination", function ($query) {
                $query->where('terminations.date_of_termination', "<", today())
                    ->whereRaw('terminations.date_of_termination > users.joining_date');
            })
            ->get();

        fwrite($logHandle, "Total employees fetched: " . count($employees) . " at " . now() . "\n");

        foreach ($employees as $employee) {

            $time_zone = $employee->user_time_zone ?? 'UTC';

            $current_local_time = now($time_zone);
            fwrite($logHandle, "\n==============================================\n");
            fwrite($logHandle, "Employee ID: {$employee->id}, Timezone: {$time_zone}\n");
            fwrite($logHandle, "Employee name: {$employee->title} {$employee->first_Name} {$employee->middle_Name} {$employee->last_Name} \n");
            fwrite($logHandle, "Current Local Time: {$current_local_time}\n");
            fwrite($logHandle, "Current UTC Time: {$now_utc}\n");


            $work_shift = $employee->current_work_shift_history;

            if (!$work_shift) {
                fwrite($logHandle, "No work shift found for employee ID: {$employee->id}, skipping.\n");
                continue;
            }

            $work_shift_history_details = $work_shift->details;
            $today_day_number = now($time_zone)->dayOfWeek;

            $work_shift_details = collect($work_shift_history_details)
                ->filter(fn($detail) => $today_day_number === $detail["day"])
                ->first();

            if (!$work_shift_details || empty($work_shift_details['shifts'])) {
                fwrite($logHandle, "No shift details found for employee ID: {$employee->id}, skipping.\n");
                continue;
            }

            $holiday = $this->workTimeManagementComponent->get_holiday_details($now_utc->toDateString(), $employee["id"], $employee["business_id"]);
            $leave_record = $this->workTimeManagementComponent->get_leave_record_details($now_utc->toDateString(), $employee["id"]);

            if (
                $work_shift_details->is_weekend ||
                !empty($holiday) ||
                !empty($leave_record)
            ) {
                fwrite($logHandle, "Holiday/Leave/Weekend for employee ID: {$employee->id}, skipping.\n");

                continue;
            }

            $setting_attendance = SettingAttendance::where([
                "business_id" => $employee->business_id
            ])->first();

            $sorted_shifts = collect($work_shift_details['shifts'])->sortBy(function ($shift) use ($time_zone) {
                return Carbon::parse($shift['start_at'], $time_zone);
            })->values();

            $first_shift = $sorted_shifts->first();
            $last_shift = $sorted_shifts->last();

            $first_shift_start = Carbon::parse($first_shift['start_at'], $time_zone)->setTimezone('UTC');
            $last_shift_end = Carbon::parse($last_shift['end_at'], $time_zone)->setTimezone('UTC');

            fwrite($logHandle, "First Shift Start (Local): " . Carbon::parse($first_shift['start_at'], $time_zone) . "\n");
            fwrite($logHandle, "First Shift Start (UTC): {$first_shift_start}\n");
            fwrite($logHandle, "Last Shift End (Local): " . Carbon::parse($last_shift['end_at'], $time_zone) . "\n");
            fwrite($logHandle, "Last Shift End (UTC): {$last_shift_end}\n");
            fwrite($logHandle, "Punch In Tolerance: {$setting_attendance->punch_in_time_tolerance} minutes\n");


            $attendance = Attendance::where('user_id', $employee->id)
                ->whereHas("attendance_records", function ($query) use ($now_utc) {
                    $query->whereDate('in_time', $now_utc->toDateString());
                })->first();

            $has_attendance = !empty($attendance);


            // Notifications
            if (
                !$has_attendance
            ) {
                if ($now_utc->between($first_shift_start->copy()->subMinutes(10), $first_shift_start,true)) {
                    // Notify before shift starts
                    fwrite($logHandle, "Sending 'Upcoming Shift' notification to employee ID: {$employee->id} at " . now() . "\n");

                    $minutes_left = $now_utc->diffInMinutes($first_shift_start);
                    $this->firebase->sendNotificationToUser(
                        $employee->id,
                      "⏰ Upcoming Shift",
                       "Your scheduled shift will begin in {$minutes_left} minutes. Please ensure you are prepared to start on time."
,
                        ["type" => "shift_soon"]
                    );
                }

                if ($now_utc->between(
                    $first_shift_start->copy()->addMinutes($setting_attendance->punch_in_time_tolerance),
                    $first_shift_start->copy()->addMinutes($setting_attendance->punch_in_time_tolerance * 2,
                    true)
                )) {
                    // Notify for being late
                    fwrite($logHandle, "Sending 'Late Punch In' notification to employee ID: {$employee->id} at " . now() . "\n");

                    $minutes_late = $first_shift_start->diffInMinutes($now_utc);
                    $this->firebase->sendNotificationToUser(
                        $employee->id,
                          "⚠️ Late Punch In",
                        "You are currently {$minutes_late} minutes late for your scheduled shift. Kindly clock in without further delay.",
                        ["type" => "late_entry"]
                    );
                }
            }

            if (
                $has_attendance &&
                $now_utc->between($last_shift_end->copy()->subMinutes(10), $last_shift_end, true)
            ) {
                // Notify near end of shift
                fwrite($logHandle, "Sending 'Shift Ending Soon' notification to employee ID: {$employee->id} at " . now() . "\n");

                $minutes_left = $now_utc->diffInMinutes($last_shift_end);
                $this->firebase->sendNotificationToUser(
                    $employee->id,
                    "📦 Shift Ending Soon",
                   "Your shift is set to end in {$minutes_left} minutes. Please begin wrapping up your tasks.",
                    ["type" => "end_soon"]
                );
            }

            if (
                $has_attendance &&
                $now_utc->between($last_shift_end->copy()->addMinutes(10), $last_shift_end->copy()->addMinutes(20), true)
            ) {
                // Notify if lingering after shift
                fwrite($logHandle, "Finished processing employee ID: {$employee->id} at " . now() . "\n");

                $minutes_since_end = $last_shift_end->diffInMinutes($now_utc);
                $this->firebase->sendNotificationToUser(
                    $employee->id,
                      "📋 Shift Ended",
                   "Our records indicate you are still clocked in. It's been {$minutes_since_end} minutes since your shift ended. Please remember to clock out if your work is complete.",
                    ["type" => "still_clocked_in"]
                );
            }
        }
        fwrite($logHandle, "notification finished at " . now() . "\n\n");
        fclose($logHandle);

        return 0;
    }
}
