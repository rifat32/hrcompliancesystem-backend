<?php

namespace App\Http\Requests;

use App\Models\TerminationType;
use App\Rules\ValidateDesignationName;
use App\Rules\ValidateTerminationTypeName;
use Illuminate\Foundation\Http\FormRequest;

class TerminationTypeUpdateRequest extends FormRequest
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

                    $termination_type_query_params = [
                        "id" => $this->id,
                    ];
                    $termination_type = TerminationType::where($termination_type_query_params)
                        ->first();
                    if (!$termination_type) {
                            // $fail($attribute . " is invalid.");
                            $fail("no termination type found");
                            return 0;

                    }
                    if (empty(auth()->user()->business_id)) {

                        if(auth()->user()->hasRole('superadmin')) {
                            if(($termination_type->business_id != NULL )) {
                                // $fail($attribute . " is invalid.");
                                $fail("You do not have permission to update this termination type due to role restrictions.");

                          }

                        } else {
                            if(($termination_type->business_id != NULL || $termination_type->is_default != 0 || $termination_type->created_by != auth()->user()->id)) {
                                // $fail($attribute . " is invalid.");
                                $fail("You do not have permission to update this termination type due to role restrictions.");

                          }
                        }

                    } else {
                        if(($termination_type->business_id != auth()->user()->business_id || $termination_type->is_default != 0)) {
                               // $fail($attribute . " is invalid.");
                            $fail("You do not have permission to update this termination type due to role restrictions.");
                        }
                    }




                },
            ],


            'name' => 'required|string',
            'description' => 'nullable|string',
            'name' => [
                "required",
                'string',
                new   ValidateTerminationTypeName($this->id)
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
