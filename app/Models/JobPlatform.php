<?php

namespace App\Models;

use App\Http\Utils\DefaultQueryScopesTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPlatform extends Model
{
    use HasFactory, DefaultQueryScopesTrait;
    protected $fillable = [
        'name',
        'description',
        "is_active",
        "is_default",
        "business_id",
        "created_by",
        "parent_id",
    ];

    public function disabled()
    {
        return $this->hasMany(DisabledJobPlatform::class, 'job_platform_id', 'id');
    }




    public function job_listings() {
        return $this->belongsToMany(JobListing::class, 'job_listing_job_platforms', 'job_platform_id', 'job_listing_id');
    }

   

}
