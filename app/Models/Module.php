<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "is_enabled",
        'created_by'
    ];

    public function reseller_modules(){
        return $this->hasMany(ResellerModule::class,"module_id","id");
    }







    
}
