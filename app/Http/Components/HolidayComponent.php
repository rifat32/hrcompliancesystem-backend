<?php
namespace App\Http\Components;

use App\Models\Holiday;
use Carbon\Carbon;

class HolidayComponent {






    public function get_holiday_datesV2($start_date, $end_date, $user_id, $all_parent_department_ids)
 {
    // Convert start and end dates to Carbon instances
    $range_start = Carbon::parse($start_date)->startOfDay(); // Start of the day for range start
    $range_end = Carbon::parse($end_date)->endOfDay(); // End of the day for range end

      // Fetch holidays
      $holidays = Holiday::where('business_id', auth()->user()->business_id)
      ->whereDate('holidays.start_date', '<=', $range_end)  // Holidays can start before or on the end date
      ->whereDate('holidays.end_date', '>=', $range_start)  // Holidays can end after or on the start date
      ->where('is_active', 1)
      ->whereNotIn('status', ['rejected'])
      ->where(function ($query) use ($user_id, $all_parent_department_ids) {
          $query->whereHas('users', function ($query) use ($user_id) {
              $query->where('users.id', $user_id);
          })
          ->orWhereHas('departments', function ($query) use ($all_parent_department_ids) {
              $query->whereIn('departments.id', $all_parent_department_ids);
          })
          ->orWhere(function ($query) {
              $query->whereDoesntHave('users')
                  ->whereDoesntHave('departments');
          });
      })
      ->select('id', 'start_date', 'end_date')
      ->get();

    // Collect holiday dates within the range
    $holiday_dates = [];

    foreach ($holidays as $holiday) {
        // Parse start and end dates
        $start_holiday_date = Carbon::parse($holiday->start_date)->startOfDay();
        $end_holiday_date = Carbon::parse($holiday->end_date)->endOfDay();

        // Adjust the holiday start and end dates based on the given range
        if ($start_holiday_date->lt($range_start)) {
            $start_holiday_date = $range_start;
        }
        if ($end_holiday_date->gt($range_end)) {
            $end_holiday_date = $range_end;
        }

        // Collect the dates within the adjusted range with the holiday id
        for ($date = $start_holiday_date->copy(); $date->lte($end_holiday_date); $date->addDay()) {
            $holiday_dates[] = [
                'date' => $date->toDateString(),
                'holiday_id' => $holiday->id,
                'is_active' => $holiday->is_active
            ];
        }
    }

    return collect($holiday_dates)->unique(function ($item) {
        return $item['date'] . '_' . $item['holiday_id']; // Ensures unique date and holiday_id combinations
    })->values();
}










}
