<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Rules\ValidateLeave;
use App\Rules\ValidateLeaveRecord;
use Illuminate\Foundation\Http\FormRequest;

class LeaveArrearApproveRequest extends FormRequest
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
                new ValidateLeave($all_manager_department_ids),
            ]
        ];
    }
}
