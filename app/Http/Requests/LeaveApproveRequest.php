<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\Department;
use App\Models\Leave;
use App\Rules\ValidateLeave;
use Illuminate\Foundation\Http\FormRequest;

class LeaveApproveRequest extends FormRequest
{

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

        return [
            'leave_id' => [
                'required',
                'numeric',
            ],

            "is_approved" => "required|boolean",
            "add_in_next_payroll" => "required|boolean"
        ];
    }
}
