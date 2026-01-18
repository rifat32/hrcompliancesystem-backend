<?php

namespace App\Http\Requests;

use App\Models\EmploymentStatus;
use App\Rules\ValidateEmploymentStatusName;
use Illuminate\Foundation\Http\FormRequest;

class EmploymentStatusUpdateRequest extends FormRequest
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

                    $employment_status_query_params = [
                        "id" => $this->id,
                    ];
                    $employment_status = EmploymentStatus::where($employment_status_query_params)
                        ->first();
                    if (!$employment_status) {
                        // $fail($attribute . " is invalid.");
                        $fail("no employment statuses found");
                        return 0;
                    }
                    if (empty(auth()->user()->business_id)) {

                        if (auth()->user()->hasRole('superadmin')) {
                            if (($employment_status->business_id != NULL )) {
                                // $fail($attribute . " is invalid.");
                                $fail("You do not have permission to update this employment statuses due to role restrictions.");
                            }
                        } else {
                            if (($employment_status->business_id != NULL || $employment_status->is_default != 0 || $employment_status->created_by != auth()->user()->id)) {
                                // $fail($attribute . " is invalid.");
                                $fail("You do not have permission to update this employment statuses due to role restrictions.");
                            }
                        }
                    } else {
                        if (($employment_status->business_id != auth()->user()->business_id || $employment_status->is_default != 0)) {
                            // $fail($attribute . " is invalid.");
                            $fail("You do not have permission to update this employment status due to role restrictions.");
                        }
                    }
                },
            ],

            'name' => [
                "required",
                'string',
                new ValidateEmploymentStatusName($this->id)

            ],
            'description' => 'nullable|string',
            'color' => 'required|string',
        ];

        // if (!empty(auth()->user()->business_id)) {
        //     $rules['name'] .= '|unique:employment_statuses,name,'.$this->id.',id,business_id,' . auth()->user()->business_id;
        // } else {
        //     if(auth()->user()->hasRole('superadmin')){
        //         $rules['name'] .= '|unique:employment_statuses,name,'.$this->id.',id,is_default,' . 1 . ',business_id,' . NULL;
        //     }
        //     else {
        //         $rules['name'] .= '|unique:employment_statuses,name,'.$this->id.',id,is_default,' . 0 . ',business_id,' . NULL;
        //     }

        // }

        return $rules;
    }
}
