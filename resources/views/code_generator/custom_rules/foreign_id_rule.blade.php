@foreach ($fields->toArray() as $field)
@php
    $relation["field_name"] = $field['name'];
    $relation["singular_field_name"] = Str::studly($relation["field_name"]);

     // Remove '_Id' from the string
     $relation["singular_model_name"] = Str::replaceLast('_id', '', $relation["field_name"]);

    // Remove the last underscore if it exists
  // Remove the last underscore if it exists
$relation["singular_model_name"] = rtrim($relation["singular_model_name"], '_');

$relation["singular_model_name"] = Str::studly($relation["singular_model_name"]);
@endphp


    @if ($field['is_foreign_key'])

    <div class="code-snippet">
        <h3>Create Rule Validate{{$relation['singular_field_name']}}</h3>
        <pre id="create_validate_{{$relation['field_name']}}"><code>
    php artisan make:rule Validate{{$relation['singular_model_name']}}
    </code></pre>
        <button class="copy-button" onclick="copyToClipboard('create_validate_{{$relation['singular_model_name']}}')">Copy</button>
    </div>

    <div class="code-snippet">
      <h3>App/rules/Validate{{$relation['singular_model_name']}}</h3>
      <pre id="validate_{{$names["singular_model_name"]}}"><code>

        namespace App\Rules;

        use Illuminate\Contracts\Validation\Rule;

        class Validate{{$relation['singular_model_name']}} implements Rule
        {
            /**
            * Create a new rule instance.
            *
            * @return void
            */

            protected $id;
           protected $errMessage;

           public function __construct($id)
           {
               $this->id = $id;
               $this->errMessage = "";
           }


        }

    </code></pre>
      <button class="copy-button" onclick="copyToClipboard('validate_{{$relation['singular_model_name']}}')">Copy</button>
    </div>


    @endif





@endforeach


