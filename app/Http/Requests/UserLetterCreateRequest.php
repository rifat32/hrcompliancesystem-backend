<?php




namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Rules\ValidateUser;
use Illuminate\Foundation\Http\FormRequest;

class UserLetterCreateRequest extends FormRequest
{
    use BasicUtil;
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

    $all_manager_department_ids = $this->get_all_departments_of_manager();

$rules = [

        'issue_date' => [
        'required',
        'string',

    ],

        'status' => [
        'required',
        'string',
    ],

        'letter_content' => [
        'required',
        'string',
    ],

        'sign_required' => [
        'required',
        'boolean',
    ],
    'letter_view_required' => [
        'required',
        'boolean',
    ],


        'attachments' => [
        'present',
        'array',

    ],

        'user_id' => [
        'required',
        'numeric',

                new ValidateUser($all_manager_department_ids)

    ],


];



return $rules;
}





}


