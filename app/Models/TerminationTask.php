<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TerminationTask extends Model
{
    use HasFactory;

    protected $fillable = [

        'task_owner_id',
        "termination_process_id",
        'task_status',
        'assigned_date',
        'due_date',
        'completion_date',
        'remarks',
    ];

    /**
     * Get the owner of the task (linked to users table).
     */
    public function task_owner()
    {
        return $this->belongsTo(User::class, 'task_owner_id');
    }

    public function termination_process()
    {
        return $this->belongsTo(TerminationProcess::class, 'termination_process_id');
    }



}
