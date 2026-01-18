<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceHistoryRecordProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'in_time',
        'out_time',
        'attendance_id',
        'break_hours',
        'is_paid_break',
        'note',
        'project_ids', // assuming project IDs are stored as JSON
        'work_location_id',
    ];

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
