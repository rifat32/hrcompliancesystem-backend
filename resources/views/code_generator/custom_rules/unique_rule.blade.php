@foreach ($fields->toArray() as $field)
@php
    $relation["field_name"] = $field['name'];
    $relation["singular_field_name"] = Str::studly($relation["field_name"]);
@endphp


    @if ($field['is_unique'] == 1)

    <div class="code-snippet">
        <h3>Create Rule Validate{{$names["singular_model_name"]}}{{$relation['singular_field_name']}}</h3>
        <pre id="create_validate_{{$names["singular_table_name"]}}_{{$relation['field_name']}}"><code>
    php artisan make:rule Validate{{$names["singular_model_name"]}}{{$relation['singular_field_name']}}
    </code></pre>
        <button class="copy-button" onclick="copyToClipboard('create_validate_{{$names['singular_table_name']}}_{{$relation['field_name']}}')">Copy</button>
    </div>

    <div class="code-snippet">
      <h3>App/rules/Validate{{$names["singular_model_name"]}}Name</h3>
      <pre id="validate_{{$names["singular_table_name"]}}_name"><code>

        namespace App\Rules;

        use App\Models\{{$names["singular_model_name"].{{$relation['field_name']}}}};
        use Illuminate\Contracts\Validation\Rule;

        class Validate{{$names["singular_model_name"]}}Name implements Rule
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


            /**
             * Determine if the validation rule passes.
             *
             * @param  string  $attribute
             * @param  mixed  $value
             * @return bool
             */
            public function passes($attribute, $value)
            {
                $created_by  = NULL;
                if(auth()->user()->business) {
                    $created_by = auth()->user()->business->created_by;
                }

                $data = {{$names["singular_model_name"]}}::where("{{$names["table_name"]}}.name",$value)
                ->when(!empty($this->id),function($query) {
                    $query->whereNotIn("id",[$this->id]);
                })
                @if ($is_active && $is_default)
                ->when(empty(auth()->user()->business_id), function ($query) use ( $created_by) {
                    $query->when(auth()->user()->hasRole('superadmin'), function ($query)  {
                        $query->forSuperAdmin('{{$names["table_name"]}}');
                    }, function ($query) use ($created_by) {
                        $query->forNonSuperAdmin('{{$names["table_name"]}}', $created_by);
                    });
                })
                ->when(!empty(auth()->user()->business_id), function ($query) {
                    $query->forBusiness('{{$names["table_name"]}}');
                })
                    @else
                    ->where('{{$names["table_name"]}}.business_id', auth()->user()->business_id)

                @endif

                ->first();

                if(!empty($data)){


                    if ($data->is_active) {
                        $this->errMessage = "A {{$names["singular_comment_name"]}} with the same name already exists.";
                    } else {
                        $this->errMessage = "A {{$names["singular_comment_name"]}} with the same name exists but is deactivated. Please activate it to use.";
                    }


                    return 0;

                }
             return 1;
            }

            /**
             * Get the validation error message.
             *
             * @return string
             */
            public function message()
            {
                return $this->errMessage;
            }

        }

    </code></pre>
      <button class="copy-button" onclick="copyToClipboard('validate_{{$names['singular_table_name']}}_name')">Copy</button>
    </div>


    @endif





@endforeach


