<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingPaidLeaveEmploymentStatus extends Model
{
    use HasFactory;
    protected $fillable = [
        'setting_leave_id', 'employment_status_id'
    ];
    protected $table = "paid_leave_employment_statuses";


    public function setting_leave()
    {
        return $this->belongsTo(SettingLeave::class,"setting_leave_id","id");
    }

   

}
