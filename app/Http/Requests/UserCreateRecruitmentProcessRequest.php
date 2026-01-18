<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\RecruitmentProcess;
use App\Rules\ValidateRecruitmentProcess;
use App\Rules\ValidateRecruitmentProcessName;
use App\Rules\ValidateUser;
use Illuminate\Foundation\Http\FormRequest;

class UserCreateRecruitmentProcessRequest extends FormRequest
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
            'recruitment_processes' => "present|array",

            'recruitment_processes.*.recruitment_process_id' => [
                "required",
                'numeric',
                new ValidateRecruitmentProcess()

            ],
            'recruitment_processes.*.description' => "nullable|string",
            'recruitment_processes.*.attachments' => "present|array",

            'recruitment_processes.*.tasks' => "present|array",

            'recruitment_processes.*.tasks.*.task_owner_id' => 'nullable|exists:users,id',
            'recruitment_processes.*.tasks.*.task_status' => 'required|in:not_started,in_progress,completed',
            'recruitment_processes.*.tasks.*.assigned_date' => 'nullable|date',
            'recruitment_processes.*.tasks.*.due_date' => 'nullable|date|after_or_equal:assigned_date',
            'recruitment_processes.*.tasks.*.completion_date' => 'nullable|date|after_or_equal:assigned_date',
            'recruitment_processes.*.tasks.*.remarks' => 'nullable|string|max:500',


        ];
    }
}
