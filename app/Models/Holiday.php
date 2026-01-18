<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $appends = ['attendance_exists'];

    protected $fillable = [
        'name', 'description', 'start_date', 'end_date', 'is_paid_holiday','repeats_annually',  'is_active', 'business_id', "status", "created_by","is_holiday_for_all",
    ];


    public function getAttendanceExistsAttribute($value)
    {
       return  Attendance::where("holiday_id",$this->id)->exists();
    }

    public function attendances() {
        return $this->hasMany(Attendance::class, 'holiday_id', 'id');
    }


    public function business() {
        return $this->belongsTo(Business::class, "business_id","id");
    }


    public function creator() {
        return $this->belongsTo(User::class, "created_by","id");
    }

    public function payroll_holiday()
    {
        return $this->hasOne(PayrollHoliday::class, "holiday_id" ,'id');
    }



    public function all_users()
    {
        return $this->hasMany(User::class, 'business_id', 'business_id');
    }



    public function departments()
    {
        return $this->belongsToMany(Department::class, 'holiday_departments',"holiday_id","department_id");
    }

    public function employees()
    {
        return $this->belongsToMany(User::class, 'holiday_employees',"holiday_id","user_id");
    }






}
