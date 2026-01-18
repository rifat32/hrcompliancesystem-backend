<?php



namespace App\Http\Requests;

use App\Models\LetterTemplate;
use App\Rules\ValidateLetterTemplateName;
use Illuminate\Foundation\Http\FormRequest;

class LetterTemplateUpdateRequest extends FormRequest
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

                  $letter_template_query_params = [
                      "id" => $this->id,
                  ];
                  $letter_template = LetterTemplate::where($letter_template_query_params)
                      ->first();
                  if (!$letter_template) {
                      // $fail($attribute . " is invalid.");
                      $fail("no letter template found");
                      return 0;
                  }
                  if (empty(auth()->user()->business_id)) {

                      if (auth()->user()->hasRole('superadmin')) {
                          if (($letter_template->business_id != NULL )) {
                              // $fail($attribute . " is invalid.");
                              $fail("You do not have permission to update this letter template due to role restrictions.");
                          }
                      } else {
                          if (($letter_template->business_id != NULL || $letter_template->is_default != 0 || $letter_template->created_by != auth()->user()->id)) {
                              // $fail($attribute . " is invalid.");
                              $fail("You do not have permission to update this letter template due to role restrictions.");
                          }
                      }
                  } else {
                      if (($letter_template->business_id != auth()->user()->business_id || $letter_template->is_default != 0)) {
                          // $fail($attribute . " is invalid.");
                          $fail("You do not have permission to update this letter template due to role restrictions.");
                      }
                  }
              },
          ],

          'name' => [
              "required",
              'string',
              new ValidateLetterTemplateName($this->id)

          ],
          'description' => 'nullable|string',
          'template' => 'required|string',
      ];



      return $rules;
  }
}



