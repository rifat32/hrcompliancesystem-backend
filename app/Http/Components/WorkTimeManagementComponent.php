<?php

namespace App\Http\Components;

use App\Http\Utils\BasicUtil;
use App\Models\Holiday;
use App\Models\LeaveRecord;
use App\Models\WorkShift;
use App\Models\WorkShiftHistory;
use Carbon\Carbon;
use Exception;

class WorkTimeManagementComponent
{
    use BasicUtil;

    public function getWorkShiftByName($name)
    {
        $work_shift = WorkShift::where([
            "name" => $name,
            "business_id" => auth()->user()->business_id,
        ])
            ->first();

        if (empty($work_shift)) {
            throw new Exception("Work shift not found", 409);
        }
        if (empty($work_shift->is_active)) {
            throw new Exception("Work shift not active", 409);
        }
        return $work_shift;
    }


    public function get_work_shift_history($in_date, $user_id, $throwError = true)
    {

        $work_shift_history =  WorkShiftHistory::where(function ($query) use ($in_date, $user_id) {
            $query->whereDate("from_date", "<=", $in_date)
                ->where(function ($query) use ($in_date) {
                    $query->whereDate("to_date", ">=", $in_date)
                        ->orWhereNull("to_date");
                })
                ->where("user_id", $user_id);
        })

            ->orderByDesc("work_shift_histories.id")
            ->first();

        if (!$work_shift_history && $throwError) {
            throw new Exception("Please define workshift first", 401);
        }

        return $work_shift_history;
    }

    public function get_work_shift_histories($start_date, $end_date, $user_id, $throwError = false)
    {
        $work_shift_histories =   WorkShiftHistory::with("details")
            ->whereDate("from_date", "<=", $end_date)
            ->where(function ($query) use ($start_date) {
                $query->whereDate("to_date", ">=", $start_date)
                    ->orWhereNull("to_date");
            })
            ->where("user_id", $user_id)
            ->orderByDesc("work_shift_histories.id")
            ->get();

        if ($work_shift_histories->isEmpty()) {
            if ($throwError) {
                throw new Exception("Please define workshift first", 401);
            } else {
                return false;
            }
        }

        return $work_shift_histories;
    }
    public function get_work_shift_details($work_shift_history, $date, $thowError = false)
    {
        $day_number = Carbon::parse($date)->dayOfWeek;
        $work_shift_details =  $work_shift_history->details()->where([
            "day" => $day_number
        ])
            ->first();


        if (empty($work_shift_details) && $thowError) {
            throw new Exception(("No work shift details found  day " . $day_number . " work shift id:" .  $work_shift_history->id), 400);
        }

        return $work_shift_details;
    }

    public function generateDateRange($start_date, $end_date)
    {
        $start_date = Carbon::parse($start_date);
        $end_date = Carbon::parse($end_date);
        $leave_dates = [];
        for ($date = $start_date; $date->lte($end_date); $date->addDay()) {
            $leave_dates[] = $date->format('Y-m-d');
        }
        return $leave_dates;
    }

    public function get_holiday_details($in_date,$user_id,$business_id=NULL)
    {

        if(empty($business_id)) {
             $business_id = auth()->user()->business_id;
        }

        $holiday = Holiday::where([
            "business_id" => $business_id
        ])
        ->where(function($query) use($user_id) {
            $query->whereHas("employees", function($query) use($user_id) {
                $query->where("users.id",$user_id);
            })
            ->orWhere("holidays.is_holiday_for_all",1);
       })
            ->where('is_active', 1)
            ->whereNotIn('status', ['rejected'])
            ->whereDate('holidays.start_date', "<=", $in_date)
            ->whereDate('holidays.end_date', ">=", $in_date)

            ->first();



        return $holiday;
    }




    public function get_leave_record_details($date, $user_id, $exclude_rejected_leave = TRUE)
    {
        $leave_record = LeaveRecord::whereHas('leave',    function ($query) use ($user_id, $exclude_rejected_leave) {
            $query->whereIn("leaves.user_id",  [$user_id])
                ->when($exclude_rejected_leave, function ($query) {
                    $query->whereNotIn("leaves.status", ["rejected"]);
                });
        })
            ->whereDate('date', '>=', $date)
            ->whereDate('date', '<=', $date)
            ->first();

        return $leave_record;
    }















    public function get_work_shift_detailsV3uselaterref($work_shift_history, $in_date)
    {
        $day_number = Carbon::parse($in_date)->dayOfWeek;

        $work_shift_details = collect($work_shift_history->details)->first(function ($detail) use ($day_number) {
            return $detail->day == $day_number;
        });

        return $work_shift_details;
    }
    public function get_holiday_dates($start_date, $end_date,$user_id)
    {

        // Fetch holidays
        $holidays = Holiday::where('business_id', auth()->user()->business_id)

            ->whereDate('holidays.start_date', '<=', $end_date)  // Holidays can start before or on the end date
            ->whereDate('holidays.end_date', '>=', $start_date)  // Holidays can end after or on the start date
            ->where(function($query) use($user_id) {
                $query->whereHas("employees", function($query) use($user_id) {
                    $query->where("users.id",$user_id);
                })
                ->orWhere("holidays.is_holiday_for_all",1);
           })
            ->where('is_active', 1)
            ->whereNotIn('status', ['rejected'])

            ->select('id', 'start_date', 'end_date')
            ->get();

        // Collect holiday dates within the range
        $holiday_dates = [];

        foreach ($holidays as $holiday) {
            // Parse start and end dates
            $start_holiday_date = Carbon::parse($holiday->start_date)->startOfDay();
            $end_holiday_date = Carbon::parse($holiday->end_date)->endOfDay();

            // Adjust the holiday start and end dates based on the given range
            if ($start_holiday_date->lt($start_date)) {
                $start_holiday_date = $start_date;
            }
            if ($end_holiday_date->gt($end_date)) {
                $end_holiday_date = $end_date;
            }

            // Collect the dates within the adjusted range
            for ($date = $start_holiday_date->copy(); $date->lte($end_holiday_date); $date->addDay()) {
                $holiday_dates[] = $date->toDateString(); // Collect as date strings to avoid time part
            }
        }

        return collect($holiday_dates)->unique()->values();
    }

    public function get_weekend_dates($start_date, $end_date, $work_shift_histories)
    {
        $weekend_dates = collect(); // Initialize an empty collection to store weekend dates

        $work_shift_histories->each(function ($work_shift) use ($start_date, $end_date, &$weekend_dates) {
            $weekends = $work_shift->details()->where("is_weekend", 1)->get();

            $weekends->each(function ($weekend) use ($start_date, $end_date, &$weekend_dates, $work_shift) {
                $day_of_week = $weekend->day;

                // Determine the end date for the loop
                $user_to_date = $work_shift->to_date ?? null;

                if (!empty($user_to_date)) {
                    $end_date_loop =  Carbon::parse($user_to_date)->gt($end_date) ? $end_date : $user_to_date;
                } else {
                    $end_date_loop = $end_date;
                }


                $user_from_date = $work_shift->from_date;
                $start_date_loop = Carbon::parse($user_from_date)->gt($start_date) ? $user_from_date : $start_date;

                // Check if the start date is a weekend and falls within range
                if (
                    Carbon::parse($start_date_loop)->dayOfWeek == $day_of_week &&
                    Carbon::parse($start_date_loop)->between($start_date, $end_date)
                ) {
                    $weekend_dates->push(Carbon::parse($start_date_loop)->format('Y-m-d'));
                }

                // Find the next occurrence of the specified day of the week
                $next_day = Carbon::parse($start_date_loop)->copy()->next($day_of_week);

                // Loop through the days and ensure they are within range
                while ($next_day->between($start_date, $end_date_loop)) {
                    $weekend_dates->push($next_day->format('Y-m-d'));
                    $next_day->addWeek(); // Move to the next week
                }
            });
        });

        return $weekend_dates;
    }

    public function get_already_taken_leave_record_dates($start_date, $end_date, $user_id, $exclude_partial_leave = false)
    {

        $already_taken_leave_records =  LeaveRecord::whereHas("leave", function ($query) use ($user_id, $exclude_partial_leave) {

            $query->where("leaves.user_id", $user_id)
                ->whereNotIn("leaves.status", ["rejected"])
                ->when(!empty($exclude_partial_leave), function ($query) {
                    $query->whereNotIn("leave_duration", ["hours"]);
                });
        })

            ->whereDate('leave_records.date', '>=', $start_date)
            ->whereDate('leave_records.date', '<=', Carbon::parse($end_date)->endOfDay())

            ->pluck("leave_records.date");


        return $already_taken_leave_records;
    }


    public function get_already_taken_leave_records($start_date, $end_date, $user_id, $leave_durations = [])
    {

        $already_taken_leave_records =  LeaveRecord::whereHas("leave", function ($query) use ($user_id, $leave_durations) {

            $query->where("leaves.user_id", $user_id)
                ->whereNotIn("leaves.status", ["rejected"])
                ->when(count($leave_durations), function ($query) use ($leave_durations) {
                    $query->whereIn("leave_duration", $leave_durations);
                });
        })

            ->whereDate('leave_records.date', '>=', $start_date)
            ->whereDate('leave_records.date', '<=', Carbon::parse($end_date)->endOfDay())
            ->get();


        return $already_taken_leave_records;
    }

    public function getScheduleInformationData($user_id, $joining_date, $date_of_termination, $start_date, $end_date, $exclude_leave_hours = true)
    {


        $work_shift_histories = $this->get_work_shift_histories($start_date, $end_date, $user_id, false);

        if (empty($work_shift_histories) || !$work_shift_histories) {
            return [
                "schedule_data" => [],
                "total_capacity_hours" => 0,
                "total_leave_hours" => 0
            ];
        }


        $dates = $this->manipulateJoiningDateTerminationDate($joining_date, $date_of_termination, $start_date, $end_date);
        $holiday_start_date = Carbon::parse($dates["start_date"]);
        $holiday_end_date = Carbon::parse($dates["end_date"]);

        $holiday_dates =  $this->get_holiday_dates($holiday_start_date, $holiday_end_date,$user_id);

        $already_taken_leave_dates = $this->get_already_taken_leave_record_dates($start_date, $end_date, $user_id, true);

        $already_taken_partial_leave_records = $this->get_already_taken_leave_records($start_date, $end_date, $user_id, ["hours"]);


        $weekend_dates = $this->get_weekend_dates($start_date, $end_date, $work_shift_histories);

        // Merge the collections and remove duplicates
        $all_leaves_collection = collect($holiday_dates)->merge($weekend_dates)->merge($already_taken_leave_dates)->unique();


        // $result_collection now contains all unique dates from holidays and weekends
        $all_leaves_array = $all_leaves_collection->values()->all();



        $start_date = Carbon::parse($start_date)->toDateString();
        $end_date = Carbon::parse($end_date)->toDateString();

        $all_dates = collect(range(strtotime($start_date), strtotime($end_date), 86400)) // 86400 seconds in a day
            ->map(function ($timestamp) {
                return date('Y-m-d', $timestamp);
            });



        if ($exclude_leave_hours) {
            $all_scheduled_dates = $all_dates->reject(fn($date) => in_array($date, $all_leaves_array));
        } else {
            $all_scheduled_dates = $all_dates;
        }





        $schedule_data = [];
        $total_capacity_hours = 0;
        $total_leave_hours = 0;
        collect($all_scheduled_dates)->map(function ($date) use (&$schedule_data, &$total_capacity_hours, $total_leave_hours , $user_id, $already_taken_partial_leave_records, $exclude_leave_hours) {

            $work_shift_history =  $this->get_work_shift_history($date, $user_id, false);
            // Skip this iteration if work_shift_history is empty
            if (empty($work_shift_history)) {
                return false;
            }

            $work_shift_details =  $this->get_work_shift_details($work_shift_history, $date);

            if (!empty($work_shift_details)) {

                if (!$work_shift_details->schedule_hour) {
                    return false;
                }

                $capacity_hours = $work_shift_details->schedule_hour;
                $leave_hours = 0;

                $already_taken_partial_leave_record = $already_taken_partial_leave_records->first(function ($record) use ($date) {
                    return Carbon::parse($date)->isSameDay(Carbon::parse($record->date));
                });

                if (!empty($already_taken_partial_leave_record)) {
                    $leave_hours = $already_taken_partial_leave_record->leave_hours;
                    if ($exclude_leave_hours) {
                        $capacity_hours -= $leave_hours;
                    }
                }


                $schedule_data[] = [
                    "date" => $date,
                    "leave_hours" => $leave_hours,
                    "capacity_hours" => $capacity_hours,
                    "break_type" => $work_shift_history->break_type,
                    "break_hours" => $work_shift_history->break_hours,
                    "shifts" => $work_shift_details->shifts,
                    // "start_at" => $work_shift_details->start_at,
                    // 'end_at' => $work_shift_details->end_at,
                    'is_weekend' => $work_shift_details->is_weekend,
                ];
                $total_capacity_hours += $capacity_hours;
                $total_leave_hours += $leave_hours;
            }
        });

        return [
            "schedule_data" => $schedule_data,
            "total_capacity_hours" => $total_capacity_hours,
            "total_leave_hours" => $total_leave_hours
        ];
    }
}
