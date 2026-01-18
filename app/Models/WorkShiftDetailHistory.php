<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkShiftDetailHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'work_shift_id',
        'day',
        // "start_at",
        // 'end_at',
        'is_weekend',
        "shifts",
        "schedule_hour",

    ];

    protected $casts = [
        'shifts' => 'array'
    ];


    protected $hidden = [
        "start_at",
        'end_at',
    ];




    public function work_shift(){
        return $this->belongsTo(WorkShiftHistory::class,'work_shift_id', 'id');
    }


}
