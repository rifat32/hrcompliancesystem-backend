<?php

namespace App\Http\Requests;

use App\Rules\ValidateLabel;
use App\Rules\ValidateTaskId;
use Illuminate\Foundation\Http\FormRequest;

class LabelAssignRequest extends FormRequest
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

            'task_id' => [
                'nullable',
                'numeric',
                new ValidateTaskId(),
            ],


            'label_ids' => "required|array",
            'label_ids.*' => [
                'nullable',
                'numeric',
                new ValidateLabel(),
            ],



        ];
    }
}
