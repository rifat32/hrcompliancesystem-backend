<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\EmployeeLeaveAllowance;
use App\Models\SettingLeaveType;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateEmployeeLeaveAllowances extends Command
{
    protected $signature = 'leave:generate-allowances';
    protected $description = 'Generate employee leave allowances based on the previous year and setting definitions';

    public function handle()
{
    $currentMonth = Carbon::now()->month;
    $currentYear = Carbon::now()->year;
    $lastYear = $currentYear - 1;



    // Retrieve businesses that have an active SettingLeave with the current start_month
    $businesses = Business::whereHas('settingLeave', function ($query) use ($currentMonth) {
        $query->where('start_month', $currentMonth)
              ->where('is_active', true);
    })->get();

    foreach ($businesses as $business) {

        $newStartDate = Carbon::create(Carbon::now()->year, $business->settingLeave->start_month, 1);
        $newExpiryDate = $newStartDate->copy()->addYear()->subDay();

        $this->info("Processing Business ID: {$business->id}");

        // Retrieve employees with leave allowances in this business (Eager load leaveType)
        $employeeLeaveAllowances = EmployeeLeaveAllowance::with('leaveType') // Eager loading leaveType
            ->whereHas('user', function ($query) use ($business) {
                $query
                ->where('users.business_id', $business->id)
                ->where("users.is_active", 1)
                ->whereDate("users.joining_date", "<=", today())
                ->whereDoesntHave("lastTermination", function ($query) {
                    $query->where('terminations.date_of_termination', "<", today())
                        ->whereRaw('terminations.date_of_termination > users.joining_date');
                });
            })
            ->whereYear('leave_expiry_date', $currentYear) // This filters for current year
            ->orWhereYear('leave_expiry_date', $lastYear)
            ->get();

        foreach ($employeeLeaveAllowances as $employeeLeaveAllowance) {
            $leaveType = $employeeLeaveAllowance->leaveType;

            $carryOverHours = 0;

            // Calculate carry-over based on the percentage (carry_over_limit)
            if ($leaveType->leave_rollover_type === 'full') {
                // Full carry over: all unused leave is carried over
                $carryOverHours = $employeeLeaveAllowance->total_leave_hours - $employeeLeaveAllowance->used_leave_hours;
            } elseif ($leaveType->leave_rollover_type === 'partial') {
                // Partial carry over: calculate using the percentage of unused leave
                $carryOverHours = ($leaveType->carry_over_limit / 100) * ($employeeLeaveAllowance->total_leave_hours - $employeeLeaveAllowance->used_leave_hours);
            }



            // Create new EmployeeLeaveAllowance for the new year
            EmployeeLeaveAllowance::create([
                'user_id' => $employeeLeaveAllowance->user_id,
                'setting_leave_type_id' => $employeeLeaveAllowance->setting_leave_type_id,
                'total_leave_hours' => $leaveType->carry_over_limit, // New leave allocation
                'used_leave_hours' => 0,
                'carry_over_hours' => $carryOverHours,
                'leave_start_date' => $newStartDate,
                'leave_expiry_date' => $newExpiryDate,
            ]);



            $this->info("New leave allowance created for User ID: {$employeeLeaveAllowance->user_id}");
        }

        EmployeeLeaveAllowance::whereHas('user', function ($query) use ($business) {
            $query->where('users.business_id', $business->id)
            ->where("users.is_active", 1)
            ->whereDate("users.joining_date", "<=", today())
            ->whereDoesntHave("lastTermination", function ($query) {
                $query->where('terminations.date_of_termination', "<", today())
                    ->whereRaw('terminations.date_of_termination > users.joining_date');
            });
        })
        ->whereDate('leave_expiry_date', '>=', $newStartDate)  // Only update for records expiring after the new start date
        ->update([
            'leave_expiry_date' => $newStartDate->subDay() // Set the new expiry date
        ]);



    }

    $this->info('Leave allowance generation completed.');
}




}
