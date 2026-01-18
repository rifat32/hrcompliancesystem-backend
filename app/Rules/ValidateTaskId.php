<?php

namespace App\Rules;

use App\Models\Task;
use Illuminate\Contracts\Validation\Rule;

class ValidateTaskId implements Rule
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

        $task = Task::
        where('id', $value)
      ->where('tasks.business_id', '=', auth()->user()->business_id)
      ->first();
      return $task?1:0;


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
