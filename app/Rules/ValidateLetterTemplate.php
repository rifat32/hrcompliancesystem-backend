<?php

namespace App\Rules;


use App\Models\LetterTemplate;
use Illuminate\Contracts\Validation\Rule;

class ValidateLetterTemplate implements Rule
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

        $exists = LetterTemplate::where("letter_templates.id",$value)
        ->when(empty(auth()->user()->business_id), function ($query) use ( $created_by) {
            $query->when(auth()->user()->hasRole('superadmin'), function ($query)  {
                $query->forSuperAdmin('letter_templates');
            }, function ($query) use ($created_by) {
                $query->forNonSuperAdmin('letter_templates', $created_by);
            });
        })
        ->when(!empty(auth()->user()->business_id), function ($query)  {
            $query->forBusiness('letter_templates');
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
        return 'The :attribute is invalid.';
    }


}
