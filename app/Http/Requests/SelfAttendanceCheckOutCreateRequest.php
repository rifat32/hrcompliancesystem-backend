<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use App\Rules\UniqueAttendanceDate;
use App\Rules\ValidateProject;
use App\Rules\ValidateWorkLocation;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class SelfAttendanceCheckOutCreateRequest extends FormRequest
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
       $attendance = Attendance::where('id', $this->id)
        ->where("user_id",auth()->user()->id)
            ->first();
        return [
            'id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) use($attendance) {

                    if (empty($attendance)) {
                        $fail($attribute . " is invalid.");
                    }
                },
            ],



            'note' => 'nullable|string',

            'attendance_records' => 'required|array',

            'attendance_records.*.note' => 'nullable|string',

            'attendance_records.*.out_latitude' => 'nullable|string',
            'attendance_records.*.out_longitude' => 'nullable|string',

            'attendance_records.*.in_latitude' => 'nullable|string',
            'attendance_records.*.in_longitude' => 'nullable|string',

            "attendance_records.*.project_ids" => "present|array",
            'attendance_records.*.project_ids.*' => [
                'numeric',
                new ValidateProject,
            ],

            'attendance_records.*.work_location_id' => [
                "required",
                'numeric',
                new ValidateWorkLocation
            ],

            'attendance_records.*.in_time' => 'required|date',
            'attendance_records.*.out_time' => 'nullable|date',

            'attendance_records.*.time_zone' => 'nullable|string',






        ];

    }
}
