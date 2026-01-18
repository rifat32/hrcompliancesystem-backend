<?php

namespace App\Http\Requests;

use App\Rules\SomeTimes;
use Illuminate\Foundation\Http\FormRequest;

class BusinessUpdateRequestPart4 extends FormRequest
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

            'business.id' => 'required|numeric|required|exists:businesses,id',


            'business.trail_end_date' => 'nullable|date',
            'business.is_self_registered_businesses' => 'required|boolean',
            'business.number_of_employees_allowed' => 'nullable|integer',
            'business.service_plan_id' => 'required|numeric|exists:service_plans,id',



        ];



        if (request()->input('business.is_self_registered_businesses')) {
            $rules['business.service_plan_discount_code'] = 'nullable|string';

        }


        return $rules;



    }

    public function messages()
{
    return [

        'business.id.required' => 'The business ID field is required.',
        'business.id.numeric' => 'The business ID must be a numeric value.',
        'business.id.exists' => 'The selected business ID is invalid.',

        'business.name.required' => 'The name field is required.',
        'business.name.string' => 'The name field must be a string.',
        'business.name.max' => 'The name field may not be greater than :max characters.',

        'business.about.string' => 'The about field must be a string.',


        'business.phone.string' => 'The phone field must be a string.',
        // 'business.email.required' => 'The email field is required.',
        'business.email.email' => 'The email must be a valid email address.',
        'business.email.string' => 'The email field must be a string.',
        'business.email.unique' => 'The email has already been taken.',
        'business.email.exists' => 'The selected email is invalid.',
        'business.additional_information.string' => 'The additional information field must be a string.',

        'business.lat.required' => 'The latitude field is required.',
        'business.lat.string' => 'The latitude field must be a string.',


        'business.long.required' => 'The longitude field is required.',
        'business.long.string' => 'The longitude field must be a string.',

        'business.country.required' => 'The country field is required.',
        'business.country.string' => 'The country field must be a string.',

        'business.city.required' => 'The city field is required.',
        'business.city.string' => 'The city field must be a string.',

        'business.currency.required' => 'The currency field is required.',
        'business.currency.string' => 'The currency must be a string.',

        'business.postcode.string' => 'The postcode field must be a string.',

        'business.address_line_1.required' => 'The address line 1 field is required.',
        'business.address_line_1.string' => 'The address line 1 field must be a string.',

        'business.address_line_2.string' => 'The address line 2 field must be a string.',

        'business.logo.string' => 'The logo field must be a string.',
        'business.image.string' => 'The image field must be a string.',

        'business.images.array' => 'The images field must be an array.',
        'business.images.*.string' => 'Each image in the images field must be a string.',






    ];
}

}
