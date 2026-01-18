<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\Department;
use App\Models\EmploymentStatus;
use App\Models\Role;
use App\Models\User;
use App\Rules\ValidateEmploymentStatus;
use App\Rules\ValidateUser;
use Illuminate\Foundation\Http\FormRequest;

class SettingLeaveCreateRequest extends FormRequest
{
    use BasicUtil;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $all_manager_department_ids = $this->get_all_departments_of_manager();
        return [
            'start_month' => 'required|integer|min:1|max:12',
            'approval_level' => 'required|string|in:single,multiple', // Adjust the valid values as needed


            'allow_bypass' => 'required|boolean',
            'special_users' => 'present|array',
            'special_users.*' => [
                "numeric",
                new ValidateUser($all_manager_department_ids)

            ],


            'special_roles' => 'present|array',

            'special_roles.*' => [
                'numeric',
                function ($attribute, $value, $fail) {
                    $role = Role::where("id", $value)
                        ->first();


                    if (!$role) {
                        // $fail($attribute . " is invalid.");
                        $fail("Role does not exists.");
                    }
                    if (empty(auth()->user()->business_id)) {
                        if (!(empty($role->business_id) || $role->is_default == 1)) {
                            // $fail($attribute . " is invalid.");
                            $fail("User belongs to another business.");
                        }
                    } else {
                        if ($role->business_id != auth()->user()->business_id) {
                            // $fail($attribute . " is invalid.");
                            $fail("User belongs to another business.");
                        }
                    }
                },
            ],
            'paid_leave_employment_statuses' => 'present|array',

            'paid_leave_employment_statuses.*' => [
                'numeric',
                new ValidateEmploymentStatus()

            ],
            'unpaid_leave_employment_statuses' => 'present|array',
            'unpaid_leave_employment_statuses.*' => [
                'numeric',
                new ValidateEmploymentStatus()
            ],

        ];

    }
    public function messages()
    {
        return [
            'allow_bypass.in' => 'The :attribute field must be either "single" or "multiple".',

         


        ];
    }
}
