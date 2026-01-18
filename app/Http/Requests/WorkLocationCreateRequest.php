<?php

namespace App\Http\Requests;

use App\Models\WorkLocation;
use App\Rules\ValidateWorkLocationName;
use Illuminate\Foundation\Http\FormRequest;

class WorkLocationCreateRequest extends FormRequest
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

            'address' => 'nullable|string',
            'description' => 'nullable|string',
            'is_location_enabled' => 'required|boolean',

            "is_geo_location_enabled" => 'required|boolean',
            "is_ip_enabled" => 'required|boolean',
            "max_radius" => "nullable|numeric",
            "ip_address" => "nullable|string",




            'latitude' => 'nullable|required_if:is_location_enabled,1|numeric',
            'longitude' => 'nullable|required_if:is_location_enabled,1|numeric',

            'name' => [
                "required",
                'string',
                 new ValidateWorkLocationName(NULL)
            ],
        ];


return $rules;

    }
}
