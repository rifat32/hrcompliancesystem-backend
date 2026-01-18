<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Models\AttendanceHistory;
use App\Models\AttendanceHistoryRecord;
use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\Log;

class AttendanceObserver
{
    /**
     * Handle the Attendance "created" event.
     *
     * @param  \App\Models\Attendance  $attendance
     * @return void
     */
    public function created(Attendance $attendance)
    {



        // $attendance_history_data = $attendance->toArray();
        // $attendance_history_data['attendance_id'] = $attendance->id;
        // $attendance_history_data['actor_id'] = auth()->user()->id;
        // $attendance_history_data['action'] = "create";
        // $attendance_history_data['attendance_created_at'] = $attendance->created_at;
        // $attendance_history_data['attendance_updated_at'] = $attendance->updated_at;

        // // Create the attendance history record
        // AttendanceHistory::create($attendance_history_data);
    }

    /**
     * Handle the Attendance "updated" event.
     *
     * @param  \App\Models\Attendance  $attendance
     * @return void
     */

    public function updated_action(Attendance $attendance, $action)
    {

        if (!empty($action)) {

            $attendance->refresh();

            $attendance_history_data = $attendance->toArray();
            $attendance_history_data = $attendance->toArray();
            $attendance_history_data['attendance_id'] = $attendance->id;
            $attendance_history_data['actor_id'] = auth()->user()->id;
            $attendance_history_data['action'] = $action;
            $attendance_history_data['attendance_created_at'] = $attendance->created_at;
            $attendance_history_data['attendance_updated_at'] = $attendance->updated_at;

            $attendance_history = AttendanceHistory::create($attendance_history_data);

            $attendance_records = AttendanceRecord::where([
                "attendance_id" => $attendance->id
            ])->get();


            // Now, add new attendance history records
            foreach ($attendance_records as $attendanceRecord) {
                Log::info("attendanceRecord: " . json_encode($attendanceRecord));
                $attendanceHistoryRecord = AttendanceHistoryRecord::create([
                    'attendance_id' => $attendance_history->id,
                    'in_time' => $attendanceRecord['in_time'],
                    'out_time' => $attendanceRecord['out_time'],
                    'break_hours' => $attendanceRecord['break_hours'],
                    'is_paid_break' => $attendanceRecord['is_paid_break'],
                    'note' => $attendanceRecord['note'] ?? null,
                    'work_location_id' => $attendanceRecord['work_location_id'],
                    'in_latitude' => $attendanceRecord['in_latitude'] ?? "",
                    'in_longitude' => $attendanceRecord['in_longitude'] ?? "",
                    'out_latitude' => $attendanceRecord['out_latitude'] ?? "",
                    'out_longitude' => $attendanceRecord['out_longitude'] ?? "",
                    'in_ip_address' => $attendanceRecord['in_ip_address'] ?? "",
                    'out_ip_address' => $attendanceRecord['out_ip_address'] ?? "",
                    'clocked_in_by' => $attendanceRecord['clocked_in_by'] ?? NULL,
                    'clocked_out_by' => $attendanceRecord['clocked_out_by'] ?? NULL,

                    'time_zone' => $attendanceRecord['time_zone'] ?? "",
                ]);

                // Sync the projects for each attendance history record

                $attendanceHistoryRecord->projects()->sync($attendanceRecord->projects->pluck("id"));
            }



            //   $attendance_history->projects()->sync($attendance->projects()->pluck("projects.id")->toArray());

        }
    }



    /**
     * Handle the Attendance "deleted" event.
     *
     * @param  \App\Models\Attendance  $attendance
     * @return void
     */
    public function deleted(Attendance $attendance)
    {

        $attendance_history_data = $attendance->toArray();
        $attendance_history_data['attendance_id'] = NULL;
        $attendance_history_data['actor_id'] = auth()->user()->id;
        $attendance_history_data['action'] = "delete";
        $attendance_history_data['attendance_created_at'] = $attendance->created_at;
        $attendance_history_data['attendance_updated_at'] = $attendance->updated_at;

        // Create the attendance history record
        AttendanceHistory::create($attendance_history_data);
    }

    /**
     * Handle the Attendance "restored" event.
     *
     * @param  \App\Models\Attendance  $attendance
     * @return void
     */
    public function restored(Attendance $attendance)
    {
        //
    }

    /**
     * Handle the Attendance "force deleted" event.
     *
     * @param  \App\Models\Attendance  $attendance
     * @return void
     */
    public function forceDeleted(Attendance $attendance)
    {
        //
    }
}
