<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;
    protected $fillable = [
        'in_time',
        'out_time',
        'attendance_id',
        'break_hours',
        'is_paid_break',
        'note',
        'work_location_id',
        'in_latitude',
        'in_longitude',
        'out_latitude',
        'out_longitude',
        "in_ip_address",
        "out_ip_address",
        'clocked_in_by',
        'clocked_out_by',
        "time_zone"
    ];


   public function work_location()
    {
        return $this->belongsTo(WorkLocation::class, "work_location_id", 'id');
    }

      /**
     * Define the relationship to AttendanceHistory.
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }


    /**
     * Define the relationship to Projects through a pivot table.
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'attendance_record_projects', 'attendance_record_id', 'project_id')
                    ->withTimestamps(); // Assuming this is a pivot table with timestamps
    }

}
