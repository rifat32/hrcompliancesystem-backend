<?php

namespace App\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;
    protected $appends = ['is_in_arrears', 'i_approved'];

    protected $fillable = [
        'leave_duration',
        'day_type',
        'leave_type_id',
        'user_id',
        'date',
        'note',
        'start_date',
        'end_date',
        'attachments',
        "hourly_rate",
        "total_leave_hours",
        "employee_leave_allowance_id",
        "work_shift_history_detail_id",
        "status",
        "is_active",
        "business_id",
        "created_by",
    ];

    protected $casts = [
        'attachments' => 'array',
    ];


    public function getIApprovedAttribute($value)
    {
        $leave_approval = LeaveApproval::where([
            "created_by" => auth()->user()->id,
            "leave_id" => $this->id
        ])
            ->first();


        if (!empty($leave_approval)) {
            return $leave_approval->is_approved;
        } else {
            return -1;
        }
    }

    public function getIsInArrearsAttribute($value)
    {
        $leave_record_ids = $this->records->pluck("id")->toArray();
        if(LeaveRecordArrear::
            where([
                "status" => "pending_approval"
            ])
            ->whereIn("leave_record_id",$leave_record_ids)
            ->exists()){
            return true;
        }
        return false;
    }





    public function records()
    {
        return $this->hasMany(LeaveRecord::class, 'leave_id', 'id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, "user_id", "id");
    }

    public function leave_type()
    {
        return $this->belongsTo(SettingLeaveType::class, "leave_type_id", "id");
    }

    public function employee_leave_allowance()
    {
        return $this->belongsTo(EmployeeLeaveAllowance::class, "employee_leave_allowance_id", "id");
    }












}
