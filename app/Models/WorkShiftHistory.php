<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkShiftHistory extends Model
{
    use HasFactory;
    protected $appends = ['is_current',"attendance_exists"];

    protected $fillable = [
        'name',
        "break_type",
        "break_hours",
        "total_schedule_hours",


        'type',
        "description",

        'is_business_default',
        'is_personal',

        "is_default",
        "is_active",
        "business_id",
        "created_by",

        "from_date",
        "to_date",
        "work_shift_id",
        "user_id",

    ];



    protected $dates = ['start_date',
    'end_date'];

    public function getFromDateAttribute($value)
    {
        if (is_null($this->user) || is_null($this->user->joining_date)) {
            return $value; // No comparison if user or joining_date is null
        }

        return Carbon::parse($this->user->joining_date)->max(Carbon::parse($value));
    }

    public function getAttendanceExistsAttribute()
    {
       return Attendance::where([
        "work_shift_history_id" => $this->id
       ])
       ->exists();

    }


    public function getIsCurrentAttribute()
    {

        $today = Carbon::today();
        $from_date = Carbon::parse($this->from_date);
        $to_date = Carbon::parse($this->to_date);

        return $today->between($from_date, $to_date);
    }



    public function attendances(){
        return $this->hasMany(Attendance::class,'work_shift_history_id', 'id');
    }

    public function details(){
        return $this->hasMany(WorkShiftDetailHistory::class,'work_shift_id', 'id');
    }

    public function departments() {
        return $this->belongsToMany(Department::class, 'employee_department_work_shift_histories', 'work_shift_id', 'department_id');
    }

    public function work_locations() {
        return $this->belongsToMany(WorkLocation::class, 'work_shift_locations', 'work_shift_id', 'work_location_id');
    }

  

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function user_work_shift(){
        return $this->hasMany(EmployeeUserWorkShiftHistory::class,'work_shift_id', 'id');
    }




}
