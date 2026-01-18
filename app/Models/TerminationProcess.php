<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Terminal;

class TerminationProcess extends Model
{
    use HasFactory;

    protected $fillable = [
        'termination_id',
        'recruitment_process_id',
        'description',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',

    ];

    public function termination()
    {
        return $this->belongsTo(Termination::class, 'termination_id','id');
    }



    public function termination_process()
    {
        return $this->hasOne(RecruitmentProcess::class, 'id','recruitment_process_id');
    }


    public function tasks()
    {
        return $this->hasMany(TerminationTask::class, 'termination_process_id', 'id');
    }


}
