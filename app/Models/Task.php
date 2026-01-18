<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;


    protected $fillable = [
        "unique_identifier",
        'name',
        'description',

        'assets',
        'cover',

        'start_date',
        'due_date',
        'end_date',
        'status',
        'project_id',
        'parent_task_id',
        "task_category_id",

        'order_no',

        'assigned_by',
        "assigned_to",

        "is_active",
        "business_id",
        "created_by"
    ];



    protected $casts = [
        'assets' => 'array',

    ];


    public function labels() {
        return $this->belongsToMany(Label::class, 'task_labels', 'task_id', 'label_id');
    }



    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    public function task_category()
    {
        return $this->belongsTo(TaskCategory::class);
    }

    public function parent_task()
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function assigned_by()
    {
        return $this->belongsTo(User::class, 'assigned_by','id');
    }

    public function assigned_to()
    {
        return $this->belongsTo(User::class, 'assigned_to','id');
    }

    public function assignees() {
        return $this->belongsToMany(User::class, 'task_assignees', 'task_id', 'assignee_id');
    }







    



}
