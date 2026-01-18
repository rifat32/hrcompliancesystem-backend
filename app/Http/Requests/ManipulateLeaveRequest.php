<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Rules\ValidateSettingLeaveType;
use App\Rules\ValidateUser;
use Illuminate\Foundation\Http\FormRequest;

class ManipulateLeaveRequest extends FormRequest
{
    use BasicUtil;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $all_manager_department_ids = $this->get_all_departments_of_manager();

        return [
            'user_id' => [
                'required',
                'numeric',
                new ValidateUser($all_manager_department_ids,true),
            ],
            'start_date' => 'nullable|required|date',
            'end_date' => 'nullable|required|date|after_or_equal:start_date',
        ];
    }

    public function messages()
    {
        return [
            'leave_duration.required' => 'The leave duration field is required.',
            'leave_duration.in' => 'Invalid value for leave duration. Valid values are: single_day, multiple_day, hours.',
            'day_type.in' => 'Invalid value for day type. Valid values are: first_half, last_half.'
        ];
    }

    // ✅ Correct method signature
    public function validationData(): array
    {
        return $this->query();
    }
}

