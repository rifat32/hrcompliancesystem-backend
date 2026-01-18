<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecruitmentProcessOrder extends Model
{
    use HasFactory;
    protected $fillable = [
        'recruitment_process_id',
        'employee_order_no',
        'candidate_order_no',
        "termination_order_no",
        'business_id',
    ];

    public function recruitmentProcess()
    {
        return $this->belongsTo(RecruitmentProcess::class, 'recruitment_process_id');
    }
}
