<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Rules\ValidateRecruitmentProcess;
use App\Rules\ValidateTerminationReason;
use App\Rules\ValidateTerminationType;
use App\Rules\ValidateUser;
use Illuminate\Foundation\Http\FormRequest;


class UserExitRequest extends FormRequest
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
                new ValidateUser($all_manager_department_ids)
            ],


            'termination.termination_type_id' => [
                'required',
                'numeric',
                new ValidateTerminationType()
            ],
            'termination.termination_reason_id' => [
                'required',
                'numeric',
                new ValidateTerminationReason()
            ],
            'termination.date_of_termination' => 'required|date',
            'termination.final_paycheck_date' => [
                'required',
                'string',
            ],
                'termination.final_paycheck_amount' => [
                'required',
                'numeric',
            ],


                'termination.severance_pay_amount' => [
                'required',
                'numeric',
            ],

                'termination.benefits_termination_date' => [
                'required',
                'string',
            ],


            'exit_interview.date_of_exit_interview' => 'nullable|date',
            'exit_interview.interviewer_name' => 'nullable|string|max:255',
            'exit_interview.key_feedback_points' => 'nullable|string',
            'exit_interview.attachments' => 'present|array',


            'termination_processes' => "present|array",
            'termination_processes.*.recruitment_process_id' => [
                "required",
                'numeric',
                new ValidateRecruitmentProcess()
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

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'id.required' => 'The ID is required.',
            'id.numeric' => 'The ID must be a number.',
            'termination.termination_type_id.required' => 'The termination type is required.',
            'termination.termination_type_id.exists' => 'The selected termination type is invalid.',
            'termination.termination_reason_id.required' => 'The termination reason is required.',
            'termination.termination_reason_id.exists' => 'The selected termination reason is invalid.',
            'termination.date_of_termination.required' => 'The date of termination is required.',
            'termination.date_of_termination.date' => 'The date of termination must be a valid date.',
            'termination.date_of_termination.after_or_equal' => 'The date of termination must be after or equal to the joining date.',


            'exit_interview.date_of_exit_interview.date' => 'The date of exit interview must be a valid date.',
            'exit_interview.interviewer_name.string' => 'The interviewer name must be a string.',
            'exit_interview.interviewer_name.max' => 'The interviewer name may not be greater than 255 characters.',
            'exit_interview.key_feedback_points.string' => 'The key feedback points must be a string.',
            'exit_interview.assets_returned.required' => 'The assets returned field is required.',
            'exit_interview.assets_returned.boolean' => 'The assets returned field must be true or false.',
            'exit_interview.attachments.present' => 'The attachments field must be present.',
            'exit_interview.attachments.array' => 'The attachments field must be an array.',


        ];
    }


}
