<div class="code-snippet">
    <h3>App/Models/{{$names["singular_model_name"]}}.php</h3>
    <pre id="model"><code>

      namespace App\Models;

      use App\Http\Utils\DefaultQueryScopesTrait;
      use Carbon\Carbon;
      use Illuminate\Database\Eloquent\Factories\HasFactory;
      use Illuminate\Database\Eloquent\Model;

      class {{$names["singular_model_name"]}} extends Model
      {
          use HasFactory, DefaultQueryScopesTrait;
          protected $fillable = [
            @foreach ($fields->toArray() as $field)
              '{{$field['name']}}',
            @endforeach

            @if ($is_active)
            "is_active",
            @if ($is_default)

            "is_default",
            @endif
            @endif



              "business_id",
              "created_by"
          ];

          protected $casts = [
        @foreach ($fields->toArray() as $field)@if ($field['type'] == 'array')'{{$field["name"]}}' => 'array',@endif @endforeach
        ];



          @foreach ($fields->toArray() as $field)
          @if ($field['is_foreign_key'])

          @php
        $relation["table_name"] = $field['relationship_table_name'];
        $relation["singular_table_name"] = Str::singular($relation["table_name"]);

        $relation["singular_model_name"] = Str::studly($relation["singular_table_name"]);

        $relation["plural_model_name"] = Str::plural($relation["singular_model_name"]);

        $relation["api_name"] = str_replace('_', '-', $relation["table_name"]);
        $relation["controller_name"] = $relation["singular_model_name"] . 'Controller';

        $relation["singular_comment_name"] = Str::singular(str_replace('_', ' ', $relation["table_name"]));
        $relation["plural_comment_name"] = str_replace('_', ' ', $relation["table_name"]);

          @endphp

          public function {{$relation['singular_table_name']}}()
          {
              return $this->belongsTo({{$relation['singular_model_name']}}::class, '{{$field['name']}}','id');
          }

          @endif

            @endforeach





            @if ($is_active && $is_default)
            public function disabled()
            {
                return $this->hasMany(Disabled{{$names["singular_model_name"]}}::class, '{{$names["singular_table_name"]}}_id', 'id');
            }

            @endif



public function {{"scopeFilter". $names['singular_model_name'] . "($query)" }} {

$created_by = NULL;
if(auth()->user()->business) {
$created_by = auth()->user()->business->created_by;
}

return $query->@if ($is_active && $is_default)
    when(empty(auth()->user()->business_id), function ($query) use ( $created_by) {
    $query->when(auth()->user()->hasRole('superadmin'), function ($query) {
    $query->forSuperAdmin('{{ $names['table_name'] }}');
    }, function ($query) use ($created_by) {
    $query->forNonSuperAdmin('{{ $names['table_name'] }}',  $created_by);
    });
    })
@else
    where('{{ $names['table_name'] }}.business_id', auth()->user()->business_id)
@endif

@foreach ($fields->toArray() as $field)
@if ($field['is_foreign_key'])
    @php
    $relation["table_name"] = $field['relationship_table_name'];
    $relation["singular_table_name"] = Str::singular($relation["table_name"]);

    $relation["singular_model_name"] = Str::studly($relation["singular_table_name"]);

    $relation["plural_model_name"] = Str::plural($relation["singular_model_name"]);

    $relation["api_name"] = str_replace('_', '-', $relation["table_name"]);
    $relation["controller_name"] = $relation["singular_model_name"] . 'Controller';

    $relation["singular_comment_name"] = Str::singular(str_replace('_', ' ', $relation["table_name"]));
    $relation["plural_comment_name"] = str_replace('_', ' ', $relation["table_name"]);
      @endphp

    ->when(request()->filled("{{ $field['name'] }}s"), function ($query) {
        return $query->whereHas('{{ $relation['singular_table_name'] }}', function ($q) {
            ${{$field['name'] }}s = explode(',', request()->input("{{$field['name'] }}s"));
            $q->whereIn('{{$field['relationship_table_name']}}.id', ${{$field['name'] }}s);
        });
    })
    @elseif  ($field['type'] == 'string')
        @if ($field['request_validation_type'] !== 'date')
            ->when(request()->filled("{{ $field['name'] }}"), function ($query) {
            return $query->where('{{ $names['table_name'] }}.{{ $field['name'] }}',
            request()->input("{{ $field['name'] }}"));
            })
        @else
            ->when(request()->filled("start_{{ $field['name'] }}"), function ($query) {
            return $query->whereDate('{{ $names['table_name'] }}.{{ $field['name'] }}', ">=",
            request()->input("start_{{ $field['name'] }}"));
            })
            ->when(request()->filled("end_{{ $field['name'] }}"), function ($query)
             {
            return $query->whereDate('{{$names['table_name']}}.{{ $field['name'] }}', "<=", request()->input("end_{{$field['name']}}"));
            })
        @endif
    @endif
@endforeach

->when(request()->filled("search_key"), function ($query){
return $query->where(function ($query) {
$term = request()->input("search_key");
$query

@foreach ($fields->toArray() as $index => $field)
    @if ($field['type'] == 'string' && $field['request_validation_type'] != 'date')
        @if ($index == 1)
            ->where("{{ $names['table_name'] }}.{{ $field['name'] }}", "like", "%" . $term . "%")
        @else
            ->orWhere("{{ $names['table_name'] }}.{{ $field['name'] }}", "like", "%" . $term . "%")
        @endif
    @endif
@endforeach
;
});


})


->when(request()->filled("start_date"), function ($query)  {
return $query->whereDate('{{ $names['table_name'] }}.created_at', ">=", request()->input("start_date"));
})
->when(request()->filled("end_date"), function ($query)  {
return $query->whereDate('{{ $names['table_name'] }}.created_at', "<=", request()->input("end_date"));
    });



    }





      }

</code></pre>
    <button class="copy-button" onclick="copyToClipboard('model')">Copy</button>
</div>
