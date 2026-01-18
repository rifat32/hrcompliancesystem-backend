<?php



namespace App\Models;

use App\Http\Utils\DefaultQueryScopesTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrmLocalizedField extends Model
{
    use HasFactory, DefaultQueryScopesTrait;
    protected $fillable = [
        'country_code',
        'fields_json',
        'reseller_id',
        "business_id",
        "created_by"
    ];

    protected $casts = [ 'attachments' => 'array',];

    public function reseller()
    {
        return $this->belongsTo(User::class, 'reseller_id', 'id');
    }
}
