<div class="code-snippet">
    <h3>App/http/requests/{{ $names['singular_model_name'] }}UpdateRequest</h3>
    <pre id="update_form_request"><code>


namespace App\Http\Requests;

use App\Models\{{ $names['singular_model_name'] }};
@foreach ($fields->toArray() as $field)
@if ($field['is_unique'] == 1)
@php
      $relation["field_name"] = $field['name'];
      $relation["singular_field_name"] = Str::studly($relation["field_name"]);
@endphp
        use App\Rules\Validate{{ $names['singular_model_name'] }}{{$relation['singular_field_name']}};
        @endif
@endforeach
use Illuminate\Foundation\Http\FormRequest;

class {{ $names['singular_model_name'] }}UpdateRequest extends FormRequest
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

      ${{ $names['singular_table_name'] }}_query_params = [
          "id" => $this->id,
      ];
      ${{ $names['singular_table_name'] }} = {{ $names['singular_model_name'] }}::where(${{ $names['singular_table_name'] }}_query_params)
          ->first();
      if (!${{ $names['singular_table_name'] }}) {
          // $fail($attribute . " is invalid.");
          $fail("no {{ $names['singular_comment_name'] }} found");
          return 0;
      }



      @if ($is_default)
      if (empty(auth()->user()->business_id)) {

        if (auth()->user()->hasRole('superadmin')) {
            if ((${{ $names['singular_table_name'] }}->business_id != NULL )) {
                // $fail($attribute . " is invalid.");
                $fail("You do not have permission to update this {{ $names['singular_comment_name'] }} due to role restrictions.");
            }
        } else {
            if ((${{ $names['singular_table_name'] }}->business_id != NULL || ${{ $names['singular_table_name'] }}->is_default != 0 || ${{ $names['singular_table_name'] }}->created_by != auth()->user()->id)) {
                // $fail($attribute . " is invalid.");
                $fail("You do not have permission to update this {{ $names['singular_comment_name'] }} due to role restrictions.");
            }
        }
    } else {
        if ((${{ $names['singular_table_name'] }}->business_id != auth()->user()->business_id || ${{ $names['singular_table_name'] }}->is_default != 0)) {
            // $fail($attribute . " is invalid.");
            $fail("You do not have permission to update this {{ $names['singular_comment_name'] }} due to role restrictions.");
        }
    }
    @else
    if (${{ $names['singular_table_name'] }}->business_id != auth()->user()->business_id) {
        // $fail($attribute . " is invalid.");
        $fail("You do not have permission to update this {{ $names['singular_comment_name'] }} due to role restrictions.");
    }
      @endif





  },
],


@foreach ($fields->toArray() as $field)
@php
    $relation["field_name"] = $field['name'];
    $relation["singular_field_name"] = Str::studly($relation["field_name"]);

        // Remove '_Id' from the string
    $relation["singular_model_name"] = Str::replaceLast('_id', '', $relation["field_name"]);

    // Remove the last underscore if it exists
    $relation["singular_model_name"] = rtrim($relation["singular_model_name"], '_');

$relation["singular_model_name"] = Str::studly($relation["singular_model_name"]);
@endphp

    '{{$field['name']}}' => [
    '{{$field['basic_validation_rule']}}',
    '{{$field['request_validation_type']}}',



    @if ($field['is_unique'] == 1)
    new Validate{{$names['singular_model_name']}}{{$relation['singular_field_name']}}(NULL)
    @endif


    @if ($field['is_foreign_key'] ==  1)
    new Validate{{$relation['singular_model_name']}}(NULL)
    @endif


],
@endforeach







];



return $rules;
}
}


</code>
</pre>
    <button class="copy-button" onclick="copyToClipboard('update_form_request')">Copy</button>
</div>
