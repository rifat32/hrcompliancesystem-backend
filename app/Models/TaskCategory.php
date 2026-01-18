<?php

namespace App\Models;

use App\Http\Utils\DefaultQueryScopesTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskCategory extends Model
{
    use HasFactory, DefaultQueryScopesTrait;
    protected $fillable = [
        'name',
        'color',
        'description',
        "is_active",
        "is_default",
        "business_id",
        "project_id",
        "order_no",
        "created_by",
        "parent_id",
    ];
    public function getOrderNoAttribute($value)
    {
        $user = auth()->user();

        if (empty($user)) {
            return $value;
        }

      $task_category_order = TaskCategoryOrder::where([
        "task_category_id" => $this->id,
        'project_id'  => request()->project_id,
        "business_id"  => $user->business_id

        ])->first();

        if (!empty($task_category_order)) {
            return $task_category_order->order_no;
        }


        return $value;
    }

    public function disabled()
    {
        return $this->hasMany(DisabledTaskCategory::class, 'task_category_id', 'id');
    }

    public function order()
    {
        return $this->hasMany(TaskCategoryOrder::class, 'task_category_id', 'id');
    }


    public function tasks() {
        return $this->hasMany(Task::class, 'task_category_id', 'id');
    }


}
