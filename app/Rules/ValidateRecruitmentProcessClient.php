<?php

namespace App\Rules;

use App\Models\Business;
use App\Models\RecruitmentProcess;
use Illuminate\Contracts\Validation\Rule;

class ValidateRecruitmentProcessClient implements Rule
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

        $exists = RecruitmentProcess::where("recruitment_processes.id",$value)
            ->where(function($query) use($created_by, $business) {
                    $query->where('recruitment_processes.business_id', NULL)
                    ->where('recruitment_processes.is_default', 1)
                    ->where('recruitment_processes.is_active', 1)
                    ->whereDoesntHave("disabled", function($q) use($created_by) {
                        $q->whereIn("disabled_recruitment_processes.created_by", [$created_by]);
                    })
                    ->whereDoesntHave("disabled", function($q) use($business)  {
                        $q->whereIn("disabled_recruitment_processes.business_id",[$business->id]);
                    })

                    ->orWhere(function ($query) use( $created_by, $business){
                        $query->where('recruitment_processes.business_id', NULL)
                            ->where('recruitment_processes.is_default', 0)
                            ->where('recruitment_processes.created_by', $created_by)
                            ->where('recruitment_processes.is_active', 1)
                            ->whereDoesntHave("disabled", function($q) use($business) {
                                $q->whereIn("disabled_recruitment_processes.business_id",[$business->id]);
                            });
                    })
                    ->orWhere(function ($query) use($business)  {
                        $query->where('recruitment_processes.business_id', $business->id)
                            ->where('recruitment_processes.is_default', 0)
                            ->where('recruitment_processes.is_active', 1);

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
