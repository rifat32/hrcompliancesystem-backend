<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisabledTerminationReason extends Model
{
    use HasFactory;

    protected $fillable = [
        'termination_reason_id',
        'business_id',
        'created_by',
        // Add other fillable columns if needed
    ];

}
