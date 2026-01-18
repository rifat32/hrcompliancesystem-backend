<?php

namespace App\Http\Requests;

use App\Models\Designation;
use App\Rules\ValidateDesignationName;
use Illuminate\Foundation\Http\FormRequest;

class DesignationCreateRequest extends FormRequest
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

            'description' => 'nullable|string',
            'name' => [
                "required",
                'string',
             new   ValidateDesignationName(NULL)
            ],
        ];

        // if (!empty(auth()->user()->business_id)) {
        //     $rules['name'] .= '|unique:designations,name,NULL,id,business_id,' . auth()->user()->business_id;
        // } else {
        //     $rules['name'] .= '|unique:designations,name,NULL,id,is_default,' . (auth()->user()->hasRole('superadmin') ? 1 : 0);
        // }

return $rules;

    }








}
