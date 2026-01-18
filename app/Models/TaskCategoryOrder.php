<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskCategoryOrder extends Model
{
    use HasFactory;

    protected $fillable = [

        'task_category_id',
        'order_no',
        'project_id',
        "business_id"

    ];
}
