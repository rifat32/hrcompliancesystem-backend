<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Rules\ValidateUser;
use App\Rules\ValidateWorkLocation;
use App\Rules\ValidateWorkShiftName;
use Illuminate\Foundation\Http\FormRequest;

class AddUpdateWorkShiftRequest extends FormRequest
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
            'user_data' => [
                "required",
                "array"
            ],
            'user_data.*.id' => [
                "required",
                "numeric",
                new ValidateUser($all_manager_department_ids)
            ],

            'user_data.*.start_date' => [
              'required',
              "date"
            ],

            'name' => [
                'required',
                'string',
                new  ValidateWorkShiftName()
            ],
            'description' => 'nullable|string',
            'is_personal' => 'required|boolean',

            'break_type' => 'required|string|in:paid,unpaid',
            'break_hours' => 'required|numeric',
            'type' => 'required|string|in:regular,scheduled,flexible',

            'work_locations' => [
                "present",
                'array',
            ],
            "work_locations.*" => [
                "numeric",
                new ValidateWorkLocation()
            ],
            'details' => 'required|array|min:7|max:7',
            'details.*.day' => 'required|numeric|between:0,6',
            'details.*.is_weekend' => 'required|boolean',
            'details.*.shifts' => 'present|array',
            'details.*.shifts.*.start_at' => [
                'nullable',
                'date_format:H:i:s',
                function ($attribute, $value, $fail) {
                    $detailIndex = explode('.', $attribute)[1];
                    $shiftIndex = explode('.', $attribute)[3]; // Extract the index of 'shifts.*'

                    $isWeekend = request("details.$detailIndex.is_weekend");
                    $endAt = request("details.$detailIndex.shifts.$shiftIndex.end_at");

                    if (!$isWeekend && !$value) {
                        $fail('The ' . $attribute . ' field is required if it is not the weekend.');
                    }

                    // Check if start_at and end_at are the same
                    if ($value === $endAt) {
                        $fail('The start time and end time cannot be the same.');
                    }
                }
            ],
            'details.*.shifts.*.end_at' => [
                'nullable',
                'date_format:H:i:s',
                function ($attribute, $value, $fail) {
                    $detailIndex = explode('.', $attribute)[1];
                    $shiftIndex = explode('.', $attribute)[3];

                    $isWeekend = request("details.$detailIndex.is_weekend");
                    $startAt = request("details.$detailIndex.shifts.$shiftIndex.start_at");

                    if (!$isWeekend && !$value) {
                        $fail('The ' . $attribute . ' field is required if it is not the weekend.');
                    }

                    // Check if start_at and end_at are the same
                    if ($value === $startAt) {
                        $fail('The start time and end time cannot be the same.');
                    }
                }
            ],
              'details.*.shifts.*.work_location_id' => [
                'nullable',
                'numeric',
                 new ValidateWorkLocation()
            ],


        ];
    }
}
