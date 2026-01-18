<?php




namespace App\Http\Requests;

use App\Models\HrmLocalizedField;
use App\Rules\ValidateHrmLocalizedFieldCountryCode;
use Illuminate\Foundation\Http\FormRequest;

class HrmLocalizedFieldUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return  bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return  array
     */
    public function rules()
    {

        $rules = [

            'id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {

                    $hrm_localized_field_query_params = [
                        "id" => $this->id,
                    ];
                    $hrm_localized_field = HrmLocalizedField::where($hrm_localized_field_query_params)
                        ->first();
                    if (!$hrm_localized_field) {
                        // $fail($attribute . " is invalid.");
                        $fail("no hrm localized field found");
                        return 0;
                    }

                    if ($hrm_localized_field->reseller_id != auth()->user()->id) {
                        // $fail($attribute . " is invalid.");
                        $fail("You do not have permission to update this hrm localized field due to role restrictions.");
                    }
                },
            ],



            'country_code' => [
                'required',
                'string',
                new ValidateHrmLocalizedFieldCountryCode(NULL),
            ],

            'fields_json' => [
                'required',
                'array',

            ],



        ];



        return $rules;
    }
}
