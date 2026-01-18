<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessEmailSetting extends Model
{
    use HasFactory;
    protected $fillable = [
        'business_id',
        'mail_driver',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
