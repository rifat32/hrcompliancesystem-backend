<?php

namespace App\Http\Requests;

use App\Rules\ValidateJobPlatformName;
use App\Rules\ValidateJobTypeName;
use App\Rules\ValidateRecruitmentProcessName;
use Illuminate\Foundation\Http\FormRequest;

class RecruitmentProcessSetupCreateRequest extends FormRequest
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

            "job_platforms" => "present|array",
            'job_platforms.*.description' => 'nullable|string',
            'job_platforms.*.name' => [
                "required",
                'string',
                new   ValidateJobPlatformName(NULL)
            ],

            "job_types" => "present|array",
            'job_types.*.description' => 'nullable|string',
            'job_types.*.name' => [
                "required",
                'string',
                new   ValidateJobTypeName(NULL)
            ],

            "recruitment_processes" => "present|array",
            'recruitment_processes.*.description' => 'nullable|string',
            'recruitment_processes.*.name' => [
                "required",
                'string',
                new   ValidateRecruitmentProcessName(NULL)
            ],
            "recruitment_processes.*.use_in_recruitment" => "required|boolean",
            "recruitment_processes.*.use_in_on_boarding" => "required|boolean",
            "recruitment_processes.*.use_in_termination" => "required|boolean",
"recruitment_processes.*.is_required" => "required|boolean",



        ];
    }
}
