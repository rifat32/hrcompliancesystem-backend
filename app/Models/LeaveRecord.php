<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_id',
        'date',
        'start_time',
        'end_time',
        "capacity_hours",
        "leave_hours",
        "work_shift_history_id",
    ];


    public function work_shift_history()
    {
        return $this->hasOne(WorkShiftHistory::class, 'id', 'work_shift_history_id');
    }

    public function attendance(){
        return $this->hasOne(Attendance::class,'leave_record_id', 'id');
    }

    public function getDurationAttribute()
    {
        $startTime = Carbon::parse($this->start_time);
        $endTime = Carbon::parse($this->end_time);

        // Calculate the difference in hours
        $total_seconds = $startTime->diffInSeconds($endTime);
        return round($total_seconds / 3600, 2); // Converts seconds to hours and rounds to 2 decimals

    }

    public function leave(){
        return $this->belongsTo(Leave::class,'leave_id', 'id');
    }

    public function arrear(){
        return $this->hasOne(LeaveRecordArrear::class,'leave_record_id', 'id');
    }
    public function payroll_leave_record()
    {
        return $this->hasOne(PayrollLeaveRecord::class, "leave_record_id" ,'id');
    }





















}
