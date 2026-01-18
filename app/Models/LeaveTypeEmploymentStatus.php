<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveTypeEmploymentStatus extends Model
{
    use HasFactory;
    protected $fillable = [
        'setting_leave_type_id', 'employment_status_id'
    ];



    public function setting_leave_type()
    {
        return $this->belongsTo(SettingLeaveType::class,"setting_leave_type_id","id");
    }


}
