<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\Termination;
use App\Rules\ValidateRecruitmentProcess;
use App\Rules\ValidateUser;
use App\Rules\ValidateUserRecruitmentProcesses;
use App\Rules\ValidateUserTerminationProcesses;
use Illuminate\Foundation\Http\FormRequest;

class UserUpdateTerminationRequest extends FormRequest
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
                "required",
                "numeric",
                new ValidateUser($all_manager_department_ids),
            ],

            'termination_id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $exists = Termination::where('id', $value)
                        ->where('terminations.user_id', '=', $this->user_id)
                        ->exists();

                    if (!$exists) {
                        $fail($attribute . " is invalid.");
                    }
                },
            ],



            'termination_processes' => "present|array",
            'termination_processes.*.recruitment_process_id' => [
                "required",
                'numeric',
                new ValidateRecruitmentProcess()
            ],
            'termination_processes.*.id' => [
                "required",
                "numeric",
                new ValidateUserTerminationProcesses($this->user_id),
            ],
            'termination_processes.*.description' => "nullable|string",
            'termination_processes.*.attachments' => "present|array",
            'termination_processes.*.tasks' => "present|array",

            'termination_processes.*.tasks.*.task_owner_id' => 'nullable|exists:users,id',
            'termination_processes.*.tasks.*.task_status' => 'required|in:not_started,in_progress,completed',
            'termination_processes.*.tasks.*.assigned_date' => 'nullable|date',
            'termination_processes.*.tasks.*.due_date' => 'nullable|date|after_or_equal:assigned_date',
            'termination_processes.*.tasks.*.completion_date' => 'nullable|date|after_or_equal:assigned_date',
            'termination_processes.*.tasks.*.remarks' => 'nullable|string|max:500',



        ];
    }
}
