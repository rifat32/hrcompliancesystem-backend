<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\Bank;
use App\Models\Department;
use App\Models\User;
use App\Rules\ValidateBank;
use App\Rules\ValidateUser;
use Illuminate\Foundation\Http\FormRequest;

class UserUpdateBankDetailsRequest extends FormRequest
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
                new ValidateUser($all_manager_department_ids)
            ],

            'bank_id' => [
                "nullable",
                'numeric',
                new ValidateBank()

            ],

        'sort_code' => "required|string",
        'account_number' => "required|string",
        'account_name' => "required|string",
        ];
    }
}
