<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        "is_self_clocked_in",
        "is_clocked_in",

        "attendance_id",
        "actor_id",
        "action",

        "attendance_created_at",
        "attendance_updated_at",

        'contractual_hours',
        'note',
        "in_geolocation",
        "out_geolocation",
        'user_id',
        'in_date',
        'does_break_taken',
        "consider_overtime",
        "behavior",
        "capacity_hours",
        "work_hours_delta",
        "break_type",
        "break_hours",
        "total_paid_hours",
        "regular_work_hours",
        // "work_shift_start_at",
        // "work_shift_end_at",
        "work_shift_history_id",
        "holiday_id",
        "leave_record_id",
        "is_weekend",

        "overtime_hours",
        "leave_hours",
        "punch_in_time_tolerance",
        "tolerance_time",
        "status",
        // 'work_location_id',
        "is_active",
        "business_id",
        "created_by",
        "regular_hours_salary",
        "overtime_hours_salary",
        // "attendance_records",

    ];



     // Define the relationship with AttendanceHistoryRecord
     public function attendance_records()
     {
         return $this->hasMany(AttendanceHistoryRecord::class, 'attendance_id');
     }


    // public function projects()
    // {
    //     return $this->hasManyThrough(
    //         Project::class, // The target model (Project)
    //         AttendanceHistoryRecord::class, // The intermediate model (AttendanceHistoryRecord)
    //         'attendance_id', // The foreign key on the intermediate model (AttendanceHistoryRecord)
    //         'id', // The foreign key on the target model (Project)
    //         'id', // The local key on the current model (AttendanceHistory)
    //         'project_id' // The local key on the intermediate model (AttendanceHistoryRecord)
    //     );
    // }




    public function employee(){
        return $this->hasOne(User::class,'id', 'user_id');
    }

    public function actor(){
        return $this->hasOne(User::class,'id', 'actor_id');
    }

    // public function work_location()
    // {
    //     return $this->belongsTo(WorkLocation::class, "work_location_id" ,'id');
    // }


    // public function projects() {
    //     return $this->belongsToMany(Project::class, 'attendance_history_projects', 'attendance_id', 'project_id');
    // }




    public function approved_by_users(){
        return $this->hasMany(AttendanceHistory::class,'attendance_id', 'attendance_id')
        ->where([
            "action" => "approve"
        ]);
    }
    public function rejected_by_users(){
        return $this->hasMany(AttendanceHistory::class,'attendance_id', 'attendance_id')
        ->where([
            "action" => "reject"
        ]);
    }



}
