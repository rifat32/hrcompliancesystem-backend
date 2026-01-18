<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLetterEmailHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_letter_id',
        'sent_at',
        'recipient_email',
        'email_content',
        'status',
        'error_message',
    ];

    // Define the relationship with UserLetter
    public function user_letters()
    {
        return $this->belongsTo(UserLetter::class, 'user_letter_id');
    }
}
