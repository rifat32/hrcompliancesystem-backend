<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessRevocation extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'email_access_revoked',
        'system_access_revoked_date',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
