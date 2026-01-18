<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckoutRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'attendance_record_id',
        'note',
        'out_time',
        'out_latitude',
        'out_longitude',
    ];
}
