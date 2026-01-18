<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Http\FormRequest;

class SelfAttendanceCheckOutRequestCreateRequest extends FormRequest
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
       $attendance = Attendance::where('id', $this->attendance_id)
        ->where("user_id",auth()->user()->id)
        ->whereHas("attendance_records", function($query) {
             $query->where("attendance_records.id",$this->attendance_record_id);
        })
            ->first();

        return [
            'attendance_id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) use($attendance) {

                    if (empty($attendance)) {
                        $fail($attribute . " is invalid.");
                    }
                },
            ],
           'attendance_record_id' => [
                'required',
                'numeric'
            ],
            'note' => 'nullable|string',
            'out_time' => 'nullable|date',
            'out_latitude' => 'nullable|string',
            'out_longitude' => 'nullable|string'

        ];

    }
}
