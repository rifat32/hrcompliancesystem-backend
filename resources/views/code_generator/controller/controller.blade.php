<div class="container" id="_controller">
    <h1 class="text-center mt-5">Controller</h1>
    <div class="row justify-content-center">
        <div class="col-md-8">



            <div class="code-snippet">
                <h3>Create Controller Using CLI</h3>
                <pre id="create_controller_command"><code>
                    php artisan make:controller {{ $names['controller_name'] }}
              </code></pre>
                <button class="copy-button" onclick="copyToClipboard('create_controller_command')">Copy</button>
            </div>




            <div class="code-snippet">
                <h3>App/Http/controllers/{{ $names['controller_name'] }}</h3>
                <pre id="controller"><code>



                namespace App\Http\Controllers;

                use App\Http\Requests\{{ $names['singular_model_name'] }}CreateRequest;
                use App\Http\Requests\{{ $names['singular_model_name'] }}UpdateRequest;
                use App\Http\Requests\GetIdRequest;
                use App\Http\Utils\BusinessUtil;
                use App\Http\Utils\ErrorUtil;
                use App\Models\{{ $names['singular_model_name'] }};
                use App\Models\Disabled{{ $names['singular_model_name'] }};
                use App\Models\User;
                use Carbon\Carbon;
                use Exception;
                use Illuminate\Http\Request;
                use Illuminate\Support\Facades\DB;

                class {{ $names['controller_name'] }} extends Controller
                {

                    use ErrorUtil, BusinessUtil;


                    @include('code_generator.controller.createApi')
                    @include('code_generator.controller.updateApi')

@if ($is_active)
    @if ($is_default)
@include('code_generator.controller.toggleApi')
@else
@include('code_generator.controller.toggleApi2')
@endif
@endif



                    @include('code_generator.controller.getApi')
                    @include('code_generator.controller.deleteApi')




                }







  </code></pre>
                <button class="copy-button" onclick="copyToClipboard('controller')">Copy</button>
            </div>
        </div>
    </div>
</div>
