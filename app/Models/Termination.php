<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Termination extends Model
{
    use HasFactory;
    protected $fillable = [

        'user_id',
        'termination_type_id',
        'termination_reason_id',
        'date_of_termination',
        'joining_date',
        'final_paycheck_date',
        'final_paycheck_amount',

   
        'severance_pay_amount',
        'benefits_termination_date',

    ];

    public function termination_processes() {
        return $this->hasMany(TerminationProcess::class, 'termination_id', 'id');
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exit_interview() {
        return $this->hasOne(ExitInterview::class,"termination_id","id");
    }

    public function terminationType()
    {
        return $this->belongsTo(TerminationType::class);
    }

    public function terminationReason()
    {
        return $this->belongsTo(TerminationReason::class);
    }


}
