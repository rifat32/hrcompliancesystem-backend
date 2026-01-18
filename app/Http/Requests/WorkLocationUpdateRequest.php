<?php

namespace App\Http\Requests;

use App\Models\WorkLocation;
use App\Rules\ValidateWorkLocationName;
use Illuminate\Foundation\Http\FormRequest;

class WorkLocationUpdateRequest extends FormRequest
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

            'id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {

                    $work_location_query_params = [
                        "id" => $this->id,
                    ];
                    $work_location = WorkLocation::where($work_location_query_params)
                        ->first();
                    if (!$work_location) {
                            // $fail($attribute . " is invalid.");
                            $fail("no work location found");
                            return 0;

                    }
                    if (empty(auth()->user()->business_id)) {

                        if(auth()->user()->hasRole('superadmin')) {
                            if(($work_location->business_id != NULL )) {
                                // $fail($attribute . " is invalid.");
                                $fail("You do not have permission to update this work location due to role restrictions.");

                          }

                        } else {
                            if(($work_location->business_id != NULL || $work_location->is_default != 0 || $work_location->created_by != auth()->user()->id)) {
                                // $fail($attribute . " is invalid.");
                                $fail("You do not have permission to update this work location due to role restrictions.");

                          }
                        }

                    } else {
                        if(($work_location->business_id != auth()->user()->business_id || $work_location->is_default != 0)) {
                               // $fail($attribute . " is invalid.");
                            $fail("You do not have permission to update this work location due to role restrictions.");
                        }
                    }




                },
            ],


            'address' => 'nullable|string',

            'is_location_enabled' => 'required|boolean',
            'latitude' => 'nullable|required_if:is_location_enabled,1|numeric',
            'longitude' => 'nullable|required_if:is_location_enabled,1|numeric',
            "max_radius" => "nullable|numeric",

            "is_geo_location_enabled" => 'required|boolean',
            "is_ip_enabled" => 'required|boolean',

            "ip_address" => "nullable|string",


            'description' => 'nullable|string',

            'name' => [
                "required",
                'string',
               new ValidateWorkLocationName($this->id)
            ],
        ];


return $rules;
    }
}
