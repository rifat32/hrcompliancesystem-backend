<?php

namespace App\Http\Requests;

use App\Rules\ValidateDepartment;
use App\Rules\ValidateDesignationName;
use App\Rules\ValidateEmploymentStatusName;
use App\Rules\ValidateWorkLocationName;
use Illuminate\Foundation\Http\FormRequest;

class EmployeeSetupCreateRequest extends FormRequest
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

            "designations" => "present|array",
            'designations.*.description' => 'nullable|string',
            'designations.*.name' => [
                "required",
                'string',
                new   ValidateDesignationName(NULL)
            ],


            //             "work_locations" => "present|array",
            //             'work_locations.*.description' => 'nullable|string',
            //             'work_locations.*.name' => [
            //                 "required",
            //                 'string',
            //              new   ValidateWorkLocationName(NULL)
            //             ],
            //             'work_locations.*.address' => 'nullable|string',
            //             'work_locations.*.is_location_enabled' => 'required|boolean',
            //             "work_locations.*.is_geo_location_enabled" => 'required|boolean',
            //             "work_locations.*.is_ip_enabled" => 'required|boolean',
            //             "work_locations.*.max_radius" => "nullable|numeric",
            //             "work_locations.*.ip_address" => "nullable|string",
            //         'work_locations.*.latitude' => 'nullable|required_if:work_locations.*.is_location_enabled,true|numeric',
            // 'work_locations.*.longitude' => 'nullable|required_if:work_locations.*.is_location_enabled,true|numeric',


            "projects" => "present|array",
            'projects.*.name' => 'required|string',
            'projects.*.description' => 'nullable|string',
            'projects.*.start_date' => 'required|date',
            'projects.*.end_date' => 'required|date|after_or_equal:start_date',
            'projects.*.status' => 'required|in:pending,in_progress,completed',

            "employment_statuses" => "present|array",
            'employment_statuses.*.name' => [
                "required",
                'string',
                new ValidateEmploymentStatusName(NULL)
            ],
            'employment_statuses.*.description' => 'nullable|string',
            'employment_statuses.*.color' => 'required|string',

        ];
    }
}
