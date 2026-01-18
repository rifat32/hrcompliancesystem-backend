<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkShiftLocation extends Model
{
    use HasFactory;
    protected $fillable = [
        'work_location_id',
        'work_shift_id',
    ];

    public function work_shift(){
        return $this->belongsTo(WorkShift::class,'work_shift_id', 'id');
    }
}
