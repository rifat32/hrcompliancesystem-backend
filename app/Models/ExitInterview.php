<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExitInterview extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'termination_id',
     
        'date_of_exit_interview',
        'interviewer_name',
        'key_feedback_points',
        'assets_returned',
        'attachments',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'attachments' => 'array',

    ];
}
