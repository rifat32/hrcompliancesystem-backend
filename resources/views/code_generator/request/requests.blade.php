<div class="container" id="request">
    <h1 class="text-center mt-5">Form Requests</h1>
    <div class="row justify-content-center">
        <div class="col-md-8">



            @include('code_generator.request.create_request_commands')

            @include('code_generator.request.create_request')

            @include('code_generator.request.update_request')



        </div>
    </div>
</div>
