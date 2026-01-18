<?php

namespace App\Models;

use App\Http\Utils\DefaultQueryScopesTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecruitmentProcess extends Model
{
    use HasFactory, DefaultQueryScopesTrait;
    protected $fillable = [
        'name',
        'description',
        "is_active",
        "is_default",
        "business_id",
        "use_in_recruitment",
        "use_in_on_boarding",
        "use_in_termination",
        "is_required",

        "employee_order_no",
        "candidate_order_no",
        "termination_order_no",
        "created_by",
        "parent_id",
    ];







    public function disabled()
    {
        return $this->hasMany(DisabledRecruitmentProcess::class, 'recruitment_process_id', 'id');
    }


    public function candidate()
    {
        return $this->belongsToMany(Candidate::class, 'candidate_recruitment_processes',"recruitment_process_id","candidate_id");
    }

    public function employee()
    {
        return $this->belongsToMany(User::class, 'user_recruitment_processes',"recruitment_process_id","user_id");
    }

    public function user_recruitment_processes()
    {
        return $this->hasMany(UserRecruitmentProcess::class, "recruitment_process_id","id");
    }

    public function termination() {
        return $this->belongsToMany(Termination::class, 'termination_processes','recruitment_process_id', 'termination_id');
    }





}
