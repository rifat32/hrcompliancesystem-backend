<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\User;
use App\Rules\ValidateDepartment;
use App\Rules\ValidateHolidayDate;
use App\Rules\ValidateUser;
use Illuminate\Foundation\Http\FormRequest;

class HolidayUpdateRequest extends FormRequest
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
                function ($attribute, $value, $fail){
                    $exists = Holiday::where('id', $value)
                    ->where('business_id', auth()->user()->business_id)


                    ->exists();

                    if (!$exists) {
                        $fail($attribute . " is invalid.");
                        return;
                    }
                },
            ],




             'name' => 'required|string',
            'description' => 'nullable|string',

            'start_date' => [
                'required',
                'date',
                new ValidateHolidayDate($this->id)
            ],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
                new ValidateHolidayDate($this->id)
            ],

            'is_paid_holiday' => 'required|boolean',
            'repeats_annually' => 'required|boolean',
            'is_holiday_for_all' => 'required|boolean',
            "user_ids" => "present|array",
            "user_ids.*" => [
                "numeric",
                new ValidateUser($all_manager_department_ids)
            ],
            'department_ids' => 'present|array',
            'department_ids.*' => [
                'numeric',
                new ValidateDepartment($all_manager_department_ids)
            ],

        ];
    }
}
