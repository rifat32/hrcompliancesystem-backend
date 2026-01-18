<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeLeaveAllowance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'setting_leave_type_id',
        'total_leave_hours',
        'used_leave_hours',
        'carry_over_hours',
        'leave_start_date',
        'leave_expiry_date',
    ];

    /**
     * Relationship with User model
     */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with SettingLeaveType model
     */
    public function leaveType()
    {
        return $this->belongsTo(SettingLeaveType::class, 'setting_leave_type_id');
    }


}
