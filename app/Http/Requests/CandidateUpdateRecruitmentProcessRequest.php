<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Rules\ValidateCandidateRecruitmentProcesses;
use App\Rules\ValidateRecruitmentProcess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class CandidateUpdateRecruitmentProcessRequest extends FormRequest
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

        return [

            'candidate_id' => [
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

            'recruitment_processes' => "present|array",

            'recruitment_processes.*.id' => [
                "required",
                "numeric",
                new ValidateCandidateRecruitmentProcesses($this->candidate_id),
            ],

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
