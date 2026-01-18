<?php

namespace App\Rules;

use App\Models\WorkShift;
use Illuminate\Contracts\Validation\Rule;

class ValidateWorkShiftName implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */

     protected $errMessage;
    public function __construct()
    {
        $this->errMessage = "";
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
        $data = WorkShift::where([
            "business_id" => auth()->user()->business_id,
            "name" => $value
        ])
         ->when(!empty(request()->id), function ($query) use ($value) {
            $query->whereNotIn("id", [request()->id]);
         })
        ->first();

        if(!empty($data)) {
            if ($data->is_active) {
                $this->errMessage = "A work shift with the same name already exists.";
            } else {
                $this->errMessage = "A work shift with the same name exists but is deactivated. Please activate it to use.";
         }
         return 0;

        }

return 1;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->errMessage;
    }
}
