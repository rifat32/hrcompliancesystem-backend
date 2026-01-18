<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisabledTerminationType extends Model
{
    use HasFactory;

    protected $fillable = [
        'termination_type_id',
        'business_id',
        'created_by',
        // Add other fillable columns if needed
    ];

}
