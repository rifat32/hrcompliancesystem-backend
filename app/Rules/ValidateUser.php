<?php

namespace App\Rules;

use App\Models\User;
use Illuminate\Contracts\Validation\Rule;

class ValidateUser implements Rule
{
    private $all_manager_department_ids;
    private $customMessage;
    private $allow_dissabled_employee;
    private $allow_self;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($all_manager_department_ids, $allow_self = false, $allow_dissabled_employee = false)
    {
        $this->all_manager_department_ids = $all_manager_department_ids;
        $this->allow_dissabled_employee = $allow_dissabled_employee;
        $this->allow_self = $allow_self;
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
        // Retrieve the user with department check if necessary
        $user = User::where('users.id', $value)
            ->where('users.business_id', auth()->user()->business_id)
            ->when(
                $this->allow_self && $value == auth()->user()->id,
                function ($query) {
                    // Skip the department check if the user is allowed to select themselves
                },
                function ($query) {
                    // Apply department check for all other cases
                    $query->whereHas('departments', function ($query) {
                        $query->whereIn('departments.id', $this->all_manager_department_ids);
                    });
                }
            )
            ->first();

        // If no user is found, return invalid
        if (!$user) {
            $this->customMessage = 'The selected user is invalid or not in the correct department.';
            return false;
        }

        // Check if disabled employee is allowed
        if (!$this->allow_dissabled_employee && !($user->is_active ?? 0)) {
            $this->customMessage = 'The selected user is currently deactivated.';
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->customMessage ?: 'The :attribute is invalid.';
    }
}
