<?php



namespace App\Models;

use App\Http\Utils\DefaultQueryScopesTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetType extends Model
{
    use HasFactory, DefaultQueryScopesTrait;
    protected $fillable = [
        'name',
        "is_active",
        "is_default",
        "business_id",
        "created_by",
        "parent_id",
    ];

    protected $casts = [];



    public function disabled()
    {
        return $this->hasMany(DisabledAssetType::class, 'asset_type_id', 'id');
    }





}
