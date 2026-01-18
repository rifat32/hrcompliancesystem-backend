<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "description",
        'set_up_amount',
        'number_of_employees_allowed',
        'duration_months',
        'price',
        'business_tier_id',
        "created_by"
    ];


    public function getNumberOfEmployeesAllowedAttribute($value)
    {
        $auth_user = auth()->user();
        if ($auth_user) {
            $business = $auth_user->business;
            if (!empty($business)) {
                if ($business->number_of_employees_allowed) {
                    return $business->number_of_employees_allowed;
                }
            }
        }
        return $value;
    }

    public function getEmployeesAllowedByBusinessId($business_id)
    {
        $business = Business::find($business_id);

        return $business?->getRawOriginal('number_of_employees_allowed')
            ?? $this->number_of_employees_allowed;
    }



    public function active_modules()
    {
        return $this->hasMany(ServicePlanModule::class, 'service_plan_id', 'id');
    }

    public function service_plan_modules()
    {
        return $this->hasMany(ServicePlanModule::class, 'service_plan_id', 'id');
    }

    public function modules()
    {
        return $this->belongsToMany(Module::class, 'service_plan_modules', 'service_plan_id', 'module_id');
    }




    public function business_tier()
    {
        return $this->belongsTo(BusinessTier::class, 'business_tier_id', 'id');
    }






    public function discount_codes()
    {
        return $this->hasMany(ServicePlanDiscountCode::class, "service_plan_id", "id");
    }
}
