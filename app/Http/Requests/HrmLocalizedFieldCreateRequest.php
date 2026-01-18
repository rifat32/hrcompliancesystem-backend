<?php


namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;


        use App\Rules\ValidateHrmLocalizedFieldCountryCode;
use App\Rules\ValidateUser;

class HrmLocalizedFieldCreateRequest extends FormRequest
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

        'country_code' => [
        'required',
        'string',
         new ValidateHrmLocalizedFieldCountryCode(NULL)
    ],

        'fields_json' => [
        'required',
        'array',
    ],




];



return $rules;
}
}


