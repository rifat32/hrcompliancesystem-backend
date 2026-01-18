
<div class="code-snippet">
    <h3>App/http/requests/{{ $names['singular_model_name'] }}CreateRequest</h3>
    <pre id="create_form_request"><code>

namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;

@foreach ($fields->toArray() as $field)
@if ($field['is_unique'] == 1)

@php
      $relation["field_name"] = $field['name'];
      $relation["singular_field_name"] = Str::studly($relation["field_name"]);
@endphp
        use App\Rules\Validate{{ $names['singular_model_name'] }}{{$relation['singular_field_name']}};
        @endif
@endforeach


class {{ $names['singular_model_name'] }}CreateRequest extends FormRequest
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
    <button class="copy-button" onclick="copyToClipboard('create_form_request')">Copy</button>
</div>
