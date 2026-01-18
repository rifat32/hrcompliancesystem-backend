<div class="container" id="_route">
    <h1 class="text-center mt-5">Route</h1>
    <div class="row justify-content-center">
        <div class="col-md-8">


            <div class="code-snippet">
                <h3>import in route/api.php</h3>
                <pre id="import_controller"><code>
              use App\Http\Controllers\{{ $names["controller_name"] }};
  </code></pre>
                <button class="copy-button" onclick="copyToClipboard('import_controller')">Copy</button>
            </div>


            <div class="code-snippet">
                <h3>route/api.php</h3>
                <pre id="route"><code>

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// {{ $names["plural_comment_name"] }} management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/{{ $names["api_name"] }}', [{{ $names["controller_name"] }}::class, "create{{ $names["singular_model_name"] }}"]);
Route::put('/v1.0/{{ $names["api_name"] }}', [{{ $names["controller_name"] }}::class, "update{{ $names["singular_model_name"] }}"]);



@if ($is_active)
Route::put('/v1.0/{{ $names["api_name"] }}/toggle-active', [{{ $names["controller_name"] }}::class, "toggleActive{{ $names["singular_model_name"] }}"]);
@endif

Route::get('/v1.0/{{ $names["api_name"] }}', [{{ $names["controller_name"] }}::class, "get{{ $names["plural_model_name"] }}"]);
Route::delete('/v1.0/{{ $names["api_name"] }}/{ids}', [{{ $names["controller_name"] }}::class, "delete{{ $names["plural_model_name"] }}ByIds"]);



// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end {{ $names["plural_comment_name"] }} management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
      </code></pre>
                <button class="copy-button" onclick="copyToClipboard('route')">Copy</button>
            </div>


        </div>
    </div>
</div>
