<?php

namespace App\Models;

use App\Http\Utils\DefaultQueryScopesTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkLocation extends Model
{
    use HasFactory, DefaultQueryScopesTrait;
    protected $fillable = [
        'name',
        'description',
        'is_location_enabled',

        "is_geo_location_enabled",
        "is_ip_enabled",

        "max_radius",

        "address",
        "ip_address",

        'latitude',
        'longitude',

        "is_active",
        "is_default",
        "business_id",
        "created_by",
        "parent_id",
    ];

    public function disabled()
    {
        return $this->hasMany(DisabledWorkLocation::class, 'work_location_id', 'id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_work_locations', 'work_location_id', 'user_id');
    }






}
