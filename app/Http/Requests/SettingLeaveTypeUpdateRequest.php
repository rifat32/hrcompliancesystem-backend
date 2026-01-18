<?php

namespace App\Http\Requests;

use App\Models\SettingLeaveType;
use App\Rules\UniqueSettingLeaveTypeName;
use App\Rules\ValidateEmploymentStatus;
use Illuminate\Foundation\Http\FormRequest;

class SettingLeaveTypeUpdateRequest extends FormRequest
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

                    $setting_leave_type_query_params = [
                        "id" => $this->id,
                    ];
                    $setting_leave_type = SettingLeaveType::where($setting_leave_type_query_params)
                        ->first();
                    if (!$setting_leave_type) {
                            // $fail($attribute . " is invalid.");
                            $fail("no leave type found");
                            return 0;

                    }
                    if (empty(auth()->user()->business_id)) {

                        if(auth()->user()->hasRole('superadmin')) {
                            if(($setting_leave_type->business_id != NULL )) {
                                // $fail($attribute . " is invalid.");
                                $fail("You do not have permission to update this leave type due to role restrictions.");

                          }

                        } else {
                            if(($setting_leave_type->business_id != NULL || $setting_leave_type->is_default != 0 || $setting_leave_type->created_by != auth()->user()->id)) {
                                // $fail($attribute . " is invalid.");
                                $fail("You do not have permission to update this leave type due to role restrictions.");

                          }
                        }

                    } else {
                        if(($setting_leave_type->business_id != auth()->user()->business_id || $setting_leave_type->is_default != 0)) {
                               // $fail($attribute . " is invalid.");
                            $fail("You do not have permission to update this leave type status due to role restrictions.");
                        }
                    }




                },
            ],
            'carry_over_limit' => 'required|integer',
            'leave_rollover_type' => 'required|string|in:none,partial,full',

            'name' => [
                "required",
                'string',
                new UniqueSettingLeaveTypeName($this->id),
            ],

            'is_active' => 'required|boolean',


            'type' => 'required|string|in:paid,unpaid',
            'amount' => 'required|numeric',

            "employment_statuses" => "present|array",
            'employment_statuses.*' => [
                'numeric',
                new ValidateEmploymentStatus()
            ],

        ];

        // if (!empty(auth()->user()->business_id)) {
        //     $rules['name'] .= '|unique:setting_leave_types,name,'.$this->id.',id,business_id,' . auth()->user()->business_id;
        // } else {
        //     if(auth()->user()->hasRole('superadmin')){
        //         $rules['name'] .= '|unique:setting_leave_types,name,'.$this->id.',id,is_default,' . 1 . ',business_id,' . NULL;
        //     }
        //     else {
        //         $rules['name'] .= '|unique:setting_leave_types,name,'.$this->id.',id,is_default,' . 0 . ',business_id,' . NULL;
        //     }

        // }

        return $rules;
    }
    public function messages()
    {
        return [
            'type.in' => 'The :attribute field must be either "paid" or "unpaid".',
            'leave_rollover_type.in' => 'The :attribute field must be either "none", "partial" or "full".',
        ];
    }
}
