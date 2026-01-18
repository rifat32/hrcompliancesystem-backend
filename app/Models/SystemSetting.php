<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'self_registration_enabled',
        'STRIPE_KEY',
        "STRIPE_SECRET",
        "is_frontend_setup",
        "reseller_id"
    ];

    protected $hidden = [
        'STRIPE_KEY',
        "STRIPE_SECRET"
    ];


}
