<?php



namespace App\Models;

use App\Http\Utils\DefaultQueryScopesTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LetterTemplate extends Model
{
    use HasFactory, DefaultQueryScopesTrait;
    protected $fillable = [
        'name',
        'description',
        'template',
        "is_active",
        "is_default",
        "business_id",
        "created_by",
        "parent_id",
    ];

    public function disabled()
    {
        return $this->hasMany(DisabledLetterTemplate::class, 'letter_template_id', 'id');
    }

    // public function getTemplateAttribute($value)
    // {
    //     return json_decode($value);
    // }




}

