<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'otp',
        'expires_at',
        'is_used',
    ];

    // Defining relationship with the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Check if OTP is expired
    public function isExpired()
    {
        return $this->expires_at < now();
    }

    // Mark OTP as used
    public function markAsUsed()
    {
        $this->update(['is_used' => 1]);
    }


}
