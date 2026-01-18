<?php





namespace App\Models;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLetter extends Model
{
    use HasFactory;
    protected $fillable = [
        'issue_date',
        'status',
        'letter_content',
        'sign_required',
        'attachments',
        "letter_view_required",
        // "letter_viewed",
        'user_id',
        "email_sent",
        "business_id",
        "created_by"
    ];







    protected $casts = [
        'attachments' => 'array',

    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
