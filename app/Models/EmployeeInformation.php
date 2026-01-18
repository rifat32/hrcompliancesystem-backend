<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeInformation extends Model
{
    use HasFactory;

      protected $fillable = [
        "user_id",
        "employee_time_zone",
    ];

    public function user(){
        return $this->hasOne(User::class,'id', 'user_id');
    }

}
