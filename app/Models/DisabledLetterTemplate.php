<?php


namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisabledLetterTemplate extends Model
{
    use HasFactory;
    protected $fillable = [
        'letter_template_id',
        'business_id',
        'created_by',
        // Add other fillable columns if needed
    ];

}

