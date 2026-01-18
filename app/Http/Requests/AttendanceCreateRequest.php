<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkLocation;
use App\Rules\UniqueAttendanceDate;
use App\Rules\ValidateProject;
use App\Rules\ValidateUser;

use App\Rules\ValidateWorkLocation;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceCreateRequest extends FormRequest
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


            'user_id' => [
                'required',
                'numeric',
                new ValidateUser($all_manager_department_ids,true),
            ],



            'attendance_records' => 'required|array',
            'attendance_records.*.break_hours' => 'required|numeric',
            'attendance_records.*.is_paid_break' => 'required|boolean',
            'attendance_records.*.note' => 'nullable|string',

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
       'attendance_records.*.out_time' => 'required|date',


            'in_date' => [
                'required',
                'date',
                new UniqueAttendanceDate(NULL, $this->user_id),
            ],


            'does_break_taken' => "required|boolean",
            'consider_overtime' => "required|boolean",



            "project_ids" => "present|array",




        ];


    }
}
