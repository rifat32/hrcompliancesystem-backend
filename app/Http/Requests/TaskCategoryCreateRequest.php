<?php

namespace App\Http\Requests;

use App\Rules\ValidateJobPlatformName;
use App\Rules\ValidateTaskCategoryName;
use Illuminate\Foundation\Http\FormRequest;

class TaskCategoryCreateRequest extends FormRequest
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
                 new ValidateTaskCategoryName(NULL)
            ],
            'description' => 'nullable|string',
            'color' => 'nullable|string',

        ];

return $rules;

    }




}
