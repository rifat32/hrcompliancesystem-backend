<?php

namespace App\Http\Requests;

use App\Models\TerminationReason;
use App\Rules\ValidateTerminationReasonName;
use Illuminate\Foundation\Http\FormRequest;

class TerminationReasonUpdateRequest extends FormRequest
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

                    $termination_reason_query_params = [
                        "id" => $this->id,
                    ];
                    $termination_reason = TerminationReason::where($termination_reason_query_params)
                        ->first();
                    if (!$termination_reason) {
                            // $fail($attribute . " is invalid.");
                            $fail("no termination reason found");
                            return 0;

                    }
                    if (empty(auth()->user()->business_id)) {

                        if(auth()->user()->hasRole('superadmin')) {
                            if(($termination_reason->business_id != NULL )) {
                                // $fail($attribute . " is invalid.");
                                $fail("You do not have permission to update this termination reason due to role restrictions.");

                          }

                        } else {
                            if(($termination_reason->business_id != NULL || $termination_reason->is_default != 0 || $termination_reason->created_by != auth()->user()->id)) {
                                // $fail($attribute . " is invalid.");
                                $fail("You do not have permission to update this termination reason due to role restrictions.");

                          }
                        }

                    } else {
                        if(($termination_reason->business_id != auth()->user()->business_id || $termination_reason->is_default != 0)) {
                               // $fail($attribute . " is invalid.");
                            $fail("You do not have permission to update this termination reason due to role restrictions.");
                        }
                    }




                },
            ],


            'name' => 'required|string',
            'description' => 'nullable|string',
            'name' => [
                "required",
                'string',
                new   ValidateTerminationReasonName($this->id)
            ],
        ];

        // if (!empty(auth()->user()->business_id)) {
        //     $rules['name'] .= '|unique:designations,name,'.$this->id.',id,business_id,' . auth()->user()->business_id;
        // } else {
        //     if(auth()->user()->hasRole('superadmin')){
        //         $rules['name'] .= '|unique:designations,name,'.$this->id.',id,is_default,' . 1 . ',business_id,' . NULL;
        //     }
        //     else {
        //         $rules['name'] .= '|unique:designations,name,'.$this->id.',id,is_default,' . 0 . ',business_id,' . NULL;
        //     }

        // }

return $rules;
    }
}
