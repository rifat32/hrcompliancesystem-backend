<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Label extends Model
{
    use HasFactory;

    protected $fillable = [

        'name',
        'color',
        "unique_identifier",
        'project_id',

        "is_active",
        "business_id",
        "created_by"
    ];

}
