<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\WorkShiftHistory;
use App\Rules\ValidateUser;
use App\Rules\ValidateWorkLocation;
use Illuminate\Foundation\Http\FormRequest;

class WorkShiftHistoryUpdateRequest extends FormRequest
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
                function ($attribute, $value, $fail) use($all_manager_department_ids) {
                    $work_shift = WorkShiftHistory::where('id', $value)
                        ->where('work_shift_histories.business_id', '=', auth()->user()->business_id)
                        ->whereHas("users.departments", function ($query) use ($all_manager_department_ids) {
                            $query->whereIn("departments.id", $all_manager_department_ids);
                        })
                        ->first();
                    if (!$work_shift) {
                        $fail($attribute . " is invalid.");
                        return;
                    }
                    if($work_shift->is_default == 1 && $work_shift->business_id == NULL && !auth()->user()->hasRole("superadmin")){

                            $fail($attribute . " is invalid. you are not a super admin.");
                            return;

                    }
                },
            ],

            'name' => [
                'required',
                'string',
            ],
            'description' => 'nullable|string',
            'is_personal' => 'required|boolean',

            'break_type' => 'required|string|in:paid,unpaid',
            'break_hours' => 'required|numeric',


            'type' => 'required|string|in:regular,scheduled,flexible',


            // 'start_date' => 'nullable|date',
            // 'end_date' => 'nullable|date|after_or_equal:start_date',




            'work_locations' => [
                "present",
                'array',
            ],

            "work_locations.*" =>[
                "numeric",
            new ValidateWorkLocation()
        ],


            'user_id' => [
                "required",
                "numeric",
                new ValidateUser($all_manager_department_ids)
            ],

            "from_date" => "required|date",
            // "to_date" => "required|date",

            'details' => 'required|array|min:7|max:7',
            'details.*.day' => 'required|numeric|between:0,6',
            'details.*.is_weekend' => 'required|boolean',
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


        ];
    }
    public function messages()
    {
        return [
            'type.in' => 'The :attribute field must be either "regular" or "scheduled".',
            'break_type.in' => 'The :attribute field must be either "paid" or "unpaid".',
        ];
    }
}
