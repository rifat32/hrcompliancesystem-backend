<?php

namespace App\Http\Requests;


use App\Rules\ValidateJobListing;
use App\Rules\ValidateJobPlatform;
use App\Rules\ValidateRecruitmentProcess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class CandidateUpdateRequest extends FormRequest
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
        return [
            'id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    $exists = DB::table('candidates')
                        ->where('id', $value)
                        ->where('candidates.business_id', '=', auth()->user()->business_id)
                        ->exists();

                    if (!$exists) {
                        $fail($attribute . " is invalid.");
                    }
                },
            ],
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'experience_years' => 'required|integer',

           'education_level' => 'nullable|string|in:no_formal_education,primary_education,secondary_education_or_high_school,ged,vocational_qualification,bachelor_degree,master_degree,doctorate_or_higher',




           'job_platforms' => 'required|array',
           'job_platforms.*' => [
               "required",
               'numeric',
               new ValidateJobPlatform(),
           ],



            'cover_letter' => 'nullable|string',
            'application_date' => 'required|date',

            'feedback' => 'nullable|string',
            'status' => 'required|in:applied,in_progress,interview_stage_1,interview_stage_2,final_interview,rejected,job_offered,hired',

            'job_listing_id' => [
                'required',
                'numeric',
                new ValidateJobListing()

            ],
            'attachments' => 'present|array',
            'attachments.*' => 'string',


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

    public function messages()
    {
        return [

            'status.in' => 'Invalid value for status. Valid values are: applied,in_progress, interview_stage_1, interview_stage_2, final_interview, rejected, job_offered, hired.',
            // ... other custom messages
        ];
    }
}
