<?php

namespace App\Http\Components;

use App\Models\WorkShift;
use App\Models\WorkShiftHistory;
use Carbon\Carbon;
use Exception;

class WorkShiftHistoryComponent
{



    public function adjustScheduleTimesAndHours($request_data)
    {
        $total_schedule_hours = 0;
        // Loop through details and create an updated array
        $updated_details = array_map(function ($detail) use(&$total_schedule_hours) {

            // Filter out invalid shifts
            $shifts = collect($detail['shifts'])->filter(function ($shift) {
                return !empty($shift['start_at']) && !empty($shift['end_at']);
            })->values();

           // Skip weekend shifts (assuming 'is_weekend' is a flag that should be true for weekend)

        if ($detail['is_weekend'] || $shifts->isEmpty()) {
            $detail["start_time"] = NULL;
            $detail["end_time"] = NULL;
            $detail["schedule_hour"] = 0;
            return $detail;
        }
            // Find the lowest start time
            $lowest_start_time = $shifts->pluck('start_at')
                ->map(fn($time) => Carbon::parse($time))
                ->min();

            // Calculate the total duration of all valid shifts
            $total_duration = $shifts->reduce(function ($carry, $shift) {
                $start_time = Carbon::parse($shift['start_at']);
                $end_time = Carbon::parse($shift['end_at']);
                return $carry + $end_time->diffInSeconds($start_time);
            }, 0);

            // Set the updated start and end times
            $detail['start_time'] = $lowest_start_time->format('H:i:s');
            $detail['end_time'] = $lowest_start_time->copy()->addSeconds($total_duration)->format('H:i:s');

            $detail['schedule_hour'] = $total_duration / 3600;

            $total_schedule_hours += $detail['schedule_hour'];

            return $detail;
        }, $request_data['details']);

        // Update the original request_data with modified details
        $request_data['details'] = $updated_details;

        $request_data['total_schedule_hours'] = $total_schedule_hours;


        return $request_data;
    }


    public function updateWorkShiftsQuery($all_manager_department_ids,$query) {
    $query = $query->when(!empty(auth()->user()->business_id), function ($query) use ( $all_manager_department_ids) {
        return $query
        ->where(function($query) use($all_manager_department_ids) {
          return  $query->where(function($query) use($all_manager_department_ids) {
                $query
                ->where([
                    "work_shifts.business_id" => auth()->user()->business_id
                ])
                ->where(function($query) use($all_manager_department_ids) {
                    $query->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                        $query->whereIn("departments.id", $all_manager_department_ids);
                    })
                    ->when(auth()->user()->hasRole("business_owner"), function($query) {
                        $query->orWhereDoesntHave("departments");
                    });
                });



            });

            // ->orWhere(function($query)  {
            //     $query->where([
            //         "is_active" => 1,
            //         "business_id" => NULL,
            //         "is_default" => 1
            //     ]) ;

            // });
        });

    })

    ->when(empty(auth()->user()->business_id), function ($query)  {
        return $query->where([
            "work_shifts.is_default" => 1,
            "work_shifts.business_id" => NULL
        ]);
    })
        ->when(!empty(request()->search_key), function ($query) {
            return $query->where(function ($query) {
                $term = request()->search_key;
                $query->where("work_shifts.name", "like", "%" . $term . "%")
                    ->orWhere("work_shifts.description", "like", "%" . $term . "%");
            });
        })


        ->when(!empty(request()->name), function ($query)  {
            $term = request()->name;
            return $query->where("work_shifts.name", "like", "%" . $term . "%");
        })
        ->when(!empty(request()->description), function ($query)  {
            $term = request()->description;
            return $query->where("work_shifts.description", "like", "%" . $term . "%");
        })

        ->when(!empty(request()->type), function ($query)  {
            return $query->where('work_shifts.type', request()->type);
        })

        ->when(request()->filled("is_active"), function ($query)  {
            return $query->where('work_shifts.is_active', request()->boolean("is_active"));
         })


        ->when(request()->filled("is_personal"), function ($query)  {
            return $query->where('work_shifts.is_personal', request()->boolean("is_personal"));
        })
        ->when(!isset(request()->is_personal), function ($query)  {
            return $query->where('work_shifts.is_personal', 0);
        })
        ->when(request()->filled("is_default"), function ($query)  {
            return $query->where('work_shifts.is_default', request()->boolean("is_default"));
        })
      
        ->when(!empty(request()->start_date), function ($query)  {
            return $query->where('work_shifts.created_at', ">=", request()->start_date);
        })

        ->when(!empty(request()->end_date), function ($query)  {
            return $query->where('work_shifts.created_at', "<=", (request()->end_date . ' 23:59:59'));
        });
        return $query;
    }





    public function getWorkShiftByUserId ($user_id) {

        $work_shift =   WorkShift::with("details")
        ->where(function($query) use($user_id) {
            $query->where([
                "business_id" => auth()->user()->business_id
            ])->whereHas('users', function ($query) use ($user_id) {
                $query->where('users.id', $user_id);
            });
        })

        ->first();

         if (empty($work_shift)) {
            throw new Exception("no work shift found for the user",404);
         }
         return $work_shift;
    }



    public function getWorkShiftById($work_shift_id) {
      $work_shift =  WorkShift::where([
            "id" => $work_shift_id,
        ])
            ->where(function ($query) {
                $query->where([

                    "business_id" => auth()->user()->business_id
                ]);
            })
            ->orderByDesc("id")
            ->first();

        if (empty($work_shift)) {
            throw new Exception("no work shift found", 403);
        }

        if (empty($work_shift->is_active)) {
            throw new Exception("Please activate the work shift named '" . $work_shift->name . "'", 400);
        }

        return $work_shift;
    }

    public function getCurrentOverlappingWorkShift($user_id, $from_date,$history_id=NULL) {
        $overlapped_work_shift_history =  WorkShiftHistory::
            when(!empty($history_id), function($query) use($history_id) {
                $query->whereNotIn("id",[$history_id]);
            })
            ->where("from_date", "=", $from_date )
            ->where("user_id",$user_id)
            ->first();
        return $overlapped_work_shift_history;
    }

    public function getFutureWorkShift($user_id, $from_date,$history_id=NULL) {
        $future_work_shift_history =  WorkShiftHistory::
            when(!empty($history_id), function($query) use($history_id) {
                $query->whereNotIn("id",[$history_id]);
            })
            ->where("from_date", ">", $from_date )
        ->where("user_id",$user_id)
        ->orderBy("from_date","ASC")
        ->first();

        return $future_work_shift_history;
    }
    public function getPastWorkShift($user_id, $from_date,$history_id=NULL) {
        $past_work_shift_history =  WorkShiftHistory::
            when(!empty($history_id), function($query) use($history_id) {
                $query->whereNotIn("id",[$history_id]);
            })
        ->where("to_date", "<", $from_date )
        ->where("from_date", "<", $from_date)
        ->where("user_id",$user_id)
        ->orderByDesc("from_date")
        ->first();

        return $past_work_shift_history;
    }

    public function getInnerWorkShift($user_id, $from_date,$history_id=NULL) {
        $inner_work_shift_history =  WorkShiftHistory::
            when(!empty($history_id), function($query) use($history_id) {
                $query->whereNotIn("id",[$history_id]);
            })
           ->whereDate("from_date", "<=", $from_date)
        ->where(function ($query) use ($from_date) {
            $query->whereDate("to_date", ">=", $from_date)
                ->orWhereNull("to_date");
        })
        ->where("user_id",$user_id)
        ->first();

        return $inner_work_shift_history;
    }


}
