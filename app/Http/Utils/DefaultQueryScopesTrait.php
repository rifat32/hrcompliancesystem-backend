<?php

namespace App\Http\Utils;


use Exception;

use Illuminate\Database\Eloquent\Builder;

trait DefaultQueryScopesTrait
{




    public function scopeForSuperAdmin(Builder $query, $table)
    {
        return $query->where($table . '.business_id', NULL)
                     ->where($table . '.is_default', 1)
                     ->when(request()->filled("is_active"), function ($query) use ($table) {
                         return $query->where($table . '.is_active', request()->boolean('is_active'));
                     });
    }

    public function scopeForNonSuperAdmin(Builder $query, $table, $created_by)
    {
        return $query
            ->where($table . '.business_id', NULL)
            ->where($table . '.is_default', 0)
            ->where($table . '.created_by', $created_by)
            ->when(request()->has('is_active'), function ($query) use ($table) {
                return $query->where($table . '.is_active', request()->boolean('is_active'));
            });



    }


    public function scopeForBusiness(Builder $query, $table,$activeData = false)
    {

        return $query
        ->where($table . '.business_id', auth()->user()->business_id)
        ->where($table . '.is_default', 0)
        ->when($activeData || request()->boolean('is_active'), function ($query) use ($table) {
                        return $query->where($table . '.is_active', 1);
        });




    }






}
