<?php

namespace App\Models;

use App\Http\Utils\DefaultQueryScopesTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingLeaveType extends Model
{
    use HasFactory, DefaultQueryScopesTrait;
    protected $fillable = [
        'name',
        'type',
        'amount',
        'is_earning_enabled',
        'carry_over_limit',
        'leave_rollover_type',
        "is_active",
        "is_default",
        "business_id",
        "created_by",
        "parent_id"
    ];

    public function disabled()
    {
        return $this->hasMany(DisabledSettingLeaveType::class, 'setting_leave_type_id', 'id');
    }





    public function employment_statuses() {
        return $this->belongsToMany(EmploymentStatus::class, 'leave_type_employment_statuses', 'setting_leave_type_id', 'employment_status_id');
    }


   
}
