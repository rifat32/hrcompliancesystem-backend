<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResellerModule extends Model
{
    use HasFactory;


    protected $fillable = [
        "is_enabled",
        "reseller_id",
        "module_id",
        'created_by'
    ];


    public function reseller_id(){
        return $this->belongsTo(ServicePlan::class,'reseller_id', 'id');
    }


}
