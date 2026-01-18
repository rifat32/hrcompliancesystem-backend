<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisabledTaskCategory extends Model
{
    use HasFactory;







    protected $fillable = [
        'task_category_id',
        'business_id',
        'created_by',
        // Add other fillable columns if needed
    ];


























}
