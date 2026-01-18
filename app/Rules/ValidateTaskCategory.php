<?php

namespace App\Rules;

use App\Models\TaskCategory;
use Illuminate\Contracts\Validation\Rule;

class ValidateTaskCategory implements Rule
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
        $created_by  = NULL;
        if(auth()->user()->business) {
            $created_by = auth()->user()->business->created_by;
        }

        $exists = TaskCategory::where("task_categories.id",$value)
        ->when(empty(auth()->user()->business_id), function ($query) use ( $created_by) {
            $query->when(auth()->user()->hasRole('superadmin'), function ($query)  {
                $query->forSuperAdmin('task_categories');
            }, function ($query) use ($created_by) {
                $query->forNonSuperAdmin('task_categories',  $created_by);
            });
        })
        ->when(!empty(auth()->user()->business_id), function ($query)  {
            $query->forBusiness('task_categories');
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
