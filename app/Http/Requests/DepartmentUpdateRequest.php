<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkLocation;
use App\Rules\ValidateDepartment;
use App\Rules\ValidateDepartmentName;
use App\Rules\ValidateParentDepartment;
use App\Rules\ValidateUser;

use App\Rules\ValidateWorkLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentUpdateRequest extends FormRequest
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
            'id' => [
                'required',
                'numeric',
            new   ValidateDepartment($all_manager_department_ids)

            ],
            'name' => [
                "required",
                "string",
                new ValidateDepartmentName($this->id)


            ],
            'work_location_id' => [
                "nullable",
                'numeric',
                new ValidateWorkLocation()
            ],
            'description' => 'nullable|string',
            'manager_id' => 'nullable|numeric',
            'manager_id' => [
                'nullable',
                'numeric',
                new ValidateUser($all_manager_department_ids,true),
            ],
            'parent_id' => [
                'nullable',
                'numeric',
                new ValidateParentDepartment($all_manager_department_ids)

            ],

        ];
    }
}
