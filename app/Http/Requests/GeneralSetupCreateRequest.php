<?php

namespace App\Http\Requests;

use App\Rules\ValidateAssetTypeName;
use App\Rules\ValidateTerminationReasonName;
use App\Rules\ValidateTerminationTypeName;
use Illuminate\Foundation\Http\FormRequest;

class GeneralSetupCreateRequest extends FormRequest
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
            "asset_types" => "present|array",
            'asset_types.*.description' => 'nullable|string',
            'asset_types.*.name' => [
                "required",
                'string',
             new   ValidateAssetTypeName(NULL)
            ],

            "termination_reasons" => "present|array",
            'termination_reasons.*.description' => 'nullable|string',
            'termination_reasons.*.name' => [
                "required",
                'string',
             new   ValidateTerminationReasonName(NULL)
            ],


            "termination_types" => "present|array",
            'termination_types.*.description' => 'nullable|string',
            'termination_types.*.name' => [
                "required",
                'string',
             new   ValidateTerminationTypeName(NULL)
            ],


        ];
    }
}
