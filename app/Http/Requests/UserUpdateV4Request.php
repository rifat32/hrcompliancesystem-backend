<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkShift;
use App\Rules\ValidateDepartment;
use App\Rules\ValidateDesignation;
use App\Rules\ValidateEmploymentStatus;
use App\Rules\ValidateUser;

use App\Rules\ValidateWorkLocation;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UserUpdateV4Request extends FormRequest
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

        $rule = [
            'id' => [
                "required",
                "numeric",
                new ValidateUser($all_manager_department_ids,true),
            ],
            'title' => 'required|string|max:255',
            'first_Name' => 'required|string|max:255',
            'middle_Name' => 'nullable|string|max:255',
            'last_Name' => 'required|string|max:255',
            'email' => 'required|string|unique:users,email,' . $this->id . ',id',
            'phone' => 'nullable|string',
            'date_of_birth' => "nullable|date",
            // "NI_number" => "required|string",
            'gender' => 'nullable|string|in:male,female,other',

        ];



        return $rule;
    }


}
