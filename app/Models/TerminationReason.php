<?php

namespace App\Models;

use App\Http\Utils\DefaultQueryScopesTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TerminationReason extends Model
{
    use HasFactory, DefaultQueryScopesTrait;
    protected $fillable = [
        'name',
        'description',
        "is_active",
        "is_default",
        "business_id",
        "created_by",
        "parent_id",
    ];

    public function terminations()
    {
        return $this->hasMany(Termination::class);
    }

    public function disabled()
    {
        return $this->hasMany(DisabledTerminationReason::class, 'termination_reason_id', 'id');
    }


   

}

