<?php

namespace App\Rules;

use App\Models\Business;
use App\Models\JobPlatform;
use Illuminate\Contracts\Validation\Rule;

class ValidateJobPlatformClient implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $business = Business::where(
            [
                "id" => request()->business_id
            ]
        )
        ->first();

        if(empty($business)) {
           return 0;
        }
        $created_by = $business->created_by;

        $exists = JobPlatform::where("job_platforms.id",$value)
                ->where(function($query) use($created_by, $business) {
                    $query->where('job_platforms.business_id', NULL)
                    ->where('job_platforms.is_default', 1)
                    ->where('job_platforms.is_active', 1)
                    ->whereDoesntHave("disabled", function($q) use($created_by, $business) {
                        $q->whereIn("disabled_job_platforms.created_by", [$created_by]);
                    })
                    ->whereDoesntHave("disabled", function($q) use($business)  {
                        $q->whereIn("disabled_job_platforms.business_id",[$business->id]);
                    })

                    ->orWhere(function ($query) use( $created_by, $business){
                        $query->where('job_platforms.business_id', NULL)
                            ->where('job_platforms.is_default', 0)
                            ->where('job_platforms.created_by', $created_by)
                            ->where('job_platforms.is_active', 1)
                            ->whereDoesntHave("disabled", function($q) use($business) {
                                $q->whereIn("disabled_job_platforms.business_id",[$business->id]);
                            });
                    })
                    ->orWhere(function ($query) use($business)  {
                        $query->where('job_platforms.business_id', $business->id)
                            ->where('job_platforms.is_default', 0)
                            ->where('job_platforms.is_active', 1);

                    });
                })

        ->exists();

        return $exists;

    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The selected :attribute is invalid.';
    }
}
