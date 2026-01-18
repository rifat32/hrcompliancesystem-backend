<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;

use App\Models\WorkShift;

use App\Rules\ValidateUser;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdateWorkShiftRequest extends FormRequest
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
                "required",
                "numeric",
                new ValidateUser($all_manager_department_ids),
            ],


            'work_shift_id' => [
                "nullable",
                'numeric',
                function ($attribute, $value, $fail) {


                    if(!empty($value)){


                        $exists = WorkShift::where('id', $value)
                        ->where([
                            "work_shifts.business_id" => auth()->user()->business_id
                        ])


                        ->exists();

                    if (!$exists) {
                        $fail($attribute . " is invalid.");
                    }
                    }

                },
            ],

       "from_date" => "required|date",


        ];
    }

    public function messages()
    {
        return [


            // 'sponsorship_details.status.in' => 'Invalid value for status. Valid values are: pending,approved,denied,visa_granted.',
            'sponsorship_details.current_certificate_status.in' => 'Invalid value for status. Valid values are: unassigned,assigned,visa_applied,visa_rejected,visa_grantes,withdrawal.',

            // ... other custom messages
        ];
    }
}
