<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceArrear extends Model
{
    use HasFactory;
    protected $fillable = [
        "generated_payroll_id",
        "payroll_id",
        'attendance_id',
        "status",
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class, "attendance_id" ,'id');
    }



    public function payroll()
    {
        return $this->hasOne(Payroll::class, "id" ,'payroll_id');
    }


}
