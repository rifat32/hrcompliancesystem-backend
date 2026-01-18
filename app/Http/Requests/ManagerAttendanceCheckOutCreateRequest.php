<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\Attendance;
use App\Rules\ValidateUser;
use Illuminate\Foundation\Http\FormRequest;

class ManagerAttendanceCheckOutCreateRequest extends FormRequest
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
                function ($attribute, $value, $fail) {
                    $exists = Attendance::where('id', $value)
                    ->where("user_id",$this->user_id)
                        ->exists();
                    if (!$exists) {
                        $fail($attribute . " is invalid.");
                    }
                },
            ],
            'user_id' => [
                'required',
                'numeric',
                new ValidateUser($all_manager_department_ids),
            ],
            'out_time' => 'required|date',
            'note' => 'nullable|string',

        ];

    }
}
