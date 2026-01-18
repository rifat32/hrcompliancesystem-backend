<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Rules\ValidateUser;
use Illuminate\Foundation\Http\FormRequest;

class UserUpdateV5Request extends FormRequest
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
            'employee_informations.employee_time_zone' => 'required|string|max:255',

        ];

        return $rule;
    }


}
