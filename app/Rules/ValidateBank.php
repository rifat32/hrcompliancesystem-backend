<?php

namespace App\Rules;

use App\Models\Bank;
use Illuminate\Contracts\Validation\Rule;

class ValidateBank implements Rule
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

            if(!empty($value)){
                $created_by  = NULL;
                if(auth()->user()->business) {
                    $created_by = auth()->user()->business->created_by;
                }

                $exists = Bank::where("banks.id",$value)
                ->when(empty(auth()->user()->business_id), function ($query) use ( $created_by) {
                    $query->when(auth()->user()->hasRole('superadmin'), function ($query)  {
                        $query->forSuperAdmin('banks');
                    }, function ($query) use ($created_by) {
                        $query->forNonSuperAdmin('banks', $created_by);
                    });
                })
                ->when(!empty(auth()->user()->business_id), function ($query) {
                    $query->forBusiness('banks');
                })
                ->exists();

                return $exists;



            }


    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The validation error message.';
    }
}
