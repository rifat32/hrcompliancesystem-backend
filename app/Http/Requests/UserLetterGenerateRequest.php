<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Rules\ValidateLetterTemplate;
use App\Rules\ValidateUser;
use Illuminate\Foundation\Http\FormRequest;

class UserLetterGenerateRequest extends FormRequest
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
                'required',
                'numeric',
                    new ValidateUser($all_manager_department_ids)
            ],
            'letter_template_id' => [
                'required',
                'numeric',
                new ValidateLetterTemplate()
            ],
        ];
    }
}
