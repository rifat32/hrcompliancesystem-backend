<?php

namespace App\Http\Requests;

use App\Models\RecruitmentProcess;
use App\Rules\ValidateRecruitmentProcessName;
use Illuminate\Foundation\Http\FormRequest;

class RecruitmentProcessCreateRequest extends FormRequest
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
        $rules = [
            'description' => 'nullable|string',
            'name' => [
                "required",
                'string',
                new   ValidateRecruitmentProcessName(NULL)

            ],
            "use_in_recruitment" => "required|boolean",
            "use_in_on_boarding" => "required|boolean",
            "use_in_termination" => "required|boolean",
            "is_required" => "required|boolean",







        ];


return $rules;
    }
}
