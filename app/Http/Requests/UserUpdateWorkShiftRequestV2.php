<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;

use App\Models\WorkShift;
use App\Models\WorkShiftHistory;
use App\Rules\ValidateUser;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdateWorkShiftRequestV2 extends FormRequest
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
            'work_shift_history_id' => [
                "required",
                'numeric',
                function ($attribute, $value, $fail) {


                    if(!empty($value)){


                        $exists = WorkShiftHistory::where('id', $value)
                        ->where([
                            "work_shift_histories.business_id" => auth()->user()->business_id
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


}
