<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Rules\ValidateDepartment;
use App\Rules\ValidateDuplicateRotaDepartment;
use App\Rules\ValidateDuplicateRotaUser;
use App\Rules\ValidateUser;
use Illuminate\Foundation\Http\FormRequest;

class EmployeeRotaCreateRequest extends FormRequest
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
            'name' => 'required|string',
            'description' => 'nullable|string',





            'departments' => 'present|array',

            'departments.*' => [

                'numeric',

                // new ValidateDepartment($all_manager_department_ids),

                // new ValidateDuplicateRotaDepartment(NULL)

            ],
            'users' => 'present|array',
            'users.*' => [

                "numeric",

                // new ValidateUser($all_manager_department_ids),

                // new ValidateDuplicateRotaUser(NULL)

            ],



            'details' => 'required|array',
            'details.*.day' => 'required|numeric|between:0,6',


            'details.*.break_type' => 'required|string|in:paid,unpaid',
            'details.*.break_hours' => 'required|numeric',


            'details.*.start_at' => [
                'nullable',
                'date_format:H:i:s',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1]; // Extract the index from the attribute name
                    $isWeekend = request('details')[$index]['is_weekend'] ?? false;

                    if (request('type') === 'scheduled' && $isWeekend == 0 && empty($value)) {
                        $fail("The $attribute field is required when type is scheduled and is_weekend is 0.");
                    }
                },
            ],


            'details.*.end_at' => [
                'nullable',
                'date_format:H:i:s',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1]; // Extract the index from the attribute name
                    $isWeekend = request('details')[$index]['is_weekend'] ?? false;

                    if (request('type') === 'scheduled' && $isWeekend == 0 && empty($value)) {
                        $fail("The $attribute field is required when type is scheduled and is_weekend is 0.");
                    }
                },
            ],




        ];
    }
    public function messages()
{
    return [
        'type.in' => 'The :attribute field must be either "regular" or "scheduled".',

    ];
}
}
