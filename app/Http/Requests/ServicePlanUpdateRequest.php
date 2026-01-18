<?php

namespace App\Http\Requests;

use App\Rules\ValidateDiscountCode;
use App\Rules\ValidateModuleIds;
use Illuminate\Foundation\Http\FormRequest;

class ServicePlanUpdateRequest extends FormRequest
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
            "id" => "required|numeric|exists:service_plans,id",
            "name" => "required|string",
            "description" => "nullable|string",
            'set_up_amount' => 'required|numeric',
            'number_of_employees_allowed' => 'required|numeric',
            'duration_months' => 'required|numeric',
            'price' => 'required|numeric',
            'business_tier_id' => 'required|exists:business_tiers,id',

            "discount_codes" => "present|array",

            "discount_codes.*.code" => [
                "required",
                new ValidateDiscountCode()
                            ],


            "discount_codes.*.discount_amount" => "required|numeric",

            "active_module_ids" => "present|array",
            "active_module_ids.*" => [
                "numeric",
                new ValidateModuleIds()
            ],

        ];

    }
}
