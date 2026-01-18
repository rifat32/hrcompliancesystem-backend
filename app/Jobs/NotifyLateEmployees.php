<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\EmployeeInformation;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyLateEmployees implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $now_utc = Carbon::now('UTC');

        $employees = EmployeeInformation::with('user', 'user.workShift')->get();

        foreach ($employees as $employee) {
            $time_zone = $employee->employee_time_zone ?? 'UTC';

            $work_shift = $employee->user->workShift ?? null;

            if (!$work_shift) continue;

            $shift_start = Carbon::parse($work_shift->start_time, $time_zone);
            $shift_end = Carbon::parse($work_shift->end_time, $time_zone);

            // Convert shift start to UTC
            $shift_start_utc = $shift_start->copy()->setTimezone('UTC');

            // If now is more than 5 minutes after shift start and employee has not clocked in
            if ($now_utc->greaterThan($shift_start_utc->copy()->addMinutes(5))) {
                $has_attendance = Attendance::where('user_id', $employee->user_id)
                    ->whereDate('clock_in_time', $now_utc->toDateString())
                    ->exists();

                if (!$has_attendance) {
                    // Send notification
                    Log::info("User {$employee->user_id} is late and has not clocked in.");
                    // You can replace this with your notification logic, e.g. Notification::send()
                }
            }
        }
    }
}
