<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;
    protected $fillable = [
        "payroll_name",

        'user_id',
        "payrun_id",

        'total_holiday_hours',
        'total_paid_leave_hours',
        'total_regular_attendance_hours',
        'total_overtime_attendance_hours',
        'regular_hours',
        'overtime_hours',
        'holiday_hours_salary',
        'leave_hours_salary',
        'regular_attendance_hours_salary',
        'overtime_attendance_hours_salary',


        'regular_hours_salary',
        'overtime_hours_salary',

        "start_date",
        "end_date",

        'status',
        'is_active',
        'business_id',
        'created_by',
    ];










    protected $casts = [
        'is_active' => 'boolean',
    ];



    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function payrun()
    {
        return $this->belongsTo(Payrun::class, 'payrun_id');
    }

    public function payroll_attendances()
    {
        return $this->hasMany(PayrollAttendance::class, "payroll_id" ,'id');
    }

    public function attendances()
    {
        return $this->belongsToMany(Attendance::class, 'payroll_attendances', 'payroll_id', 'attendance_id');
    }

    public function payroll_leave_records()
    {
        return $this->hasMany(PayrollLeaveRecord::class, "payroll_id" ,'id');
    }

     public function payroll_holidays()
    {
        return $this->hasMany(PayrollHoliday::class, "payroll_id" ,'id');
    }



    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    protected static function booted()
    {
        static::deleting(function ($payroll) {
            // Delete attendance arrears linked by payroll_id if not completed
            AttendanceArrear::where('payroll_id', $payroll->id)
                ->whereNotIn('status', ['completed'])
                ->delete();

            AttendanceArrear::where('payroll_id', $payroll->id)
            ->update([
                'payroll_id' => NULL,
            ]);

            // Update status to pending_approval for generated_payroll_id match
            AttendanceArrear::where('generated_payroll_id', $payroll->id)
            ->whereIn('status', ['completed'])
                ->update([
                    'status' => 'pending_approval',
                    'generated_payroll_id' => NULL,
            ]);
            AttendanceArrear::whereNull('generated_payroll_id', $payroll->id)
            ->orWhereNull('payroll_id', $payroll->id)
            ->delete();


            // ##########
        LeaveRecordArrear::where('payroll_id', $payroll->id)
            ->whereNotIn('status', ['completed'])
            ->delete();

        LeaveRecordArrear::where('payroll_id', $payroll->id)
        ->update([
            'payroll_id' => NULL,
        ]);

        // Update status to pending_approval for generated_payroll_id match
        LeaveRecordArrear::where('generated_payroll_id', $payroll->id)
        ->whereIn('status', ['completed'])
            ->update([
                'status' => 'pending_approval',
                'generated_payroll_id' => NULL,
        ]);
        LeaveRecordArrear::whereNull('generated_payroll_id', $payroll->id)
        ->orWhereNull('payroll_id', $payroll->id)
        ->delete();


        });


    }



}
