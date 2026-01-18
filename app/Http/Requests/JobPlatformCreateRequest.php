<?php

namespace App\Http\Requests;

use App\Models\JobPlatform;
use App\Rules\ValidateJobPlatformName;
use Illuminate\Foundation\Http\FormRequest;

class JobPlatformCreateRequest extends FormRequest
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
            'name' => [
                "required",
                'string',
                new ValidateJobPlatformName(NULL)
            ],
            'description' => 'nullable|string',
        ];

        // if (!empty(auth()->user()->business_id)) {
        //     $rules['name'] .= '|unique:job_platforms,name,NULL,id,business_id,' . auth()->user()->business_id;
        // } else {
        //     $rules['name'] .= '|unique:job_platforms,name,NULL,id,is_default,' . (auth()->user()->hasRole('superadmin') ? 1 : 0);
        // }

return $rules;
    }
}
