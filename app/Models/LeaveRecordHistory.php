<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRecordHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'leave_id',
        'date',
        'start_time',
        'end_time',
        "capacity_hours",
        "leave_hours",
        "work_shift_history_id"

    ];
    public function getDurationAttribute()
    {
        $startTime = Carbon::parse($this->start_time);
        $endTime = Carbon::parse($this->end_time);

        // Calculate the difference in hours
        $total_seconds = $startTime->diffInSeconds($endTime);
        return round($total_seconds / 3600, 2); // Converts seconds to hours and rounds to 2 decimals

    }
    public function leave(){
        return $this->belongsTo(LeaveHistory::class,'leave_id', 'id');
    }

   


}
