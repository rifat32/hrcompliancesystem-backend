<div class="container" id="model_and_migration">
    <h1 class="text-center mt-5">Create Model and Migration</h1>
    <div class="row justify-content-center">
        <div class="col-md-8">


            @include("code_generator.models_migrations.create_model_migrations")


            @include("code_generator.models_migrations.main_migration")

            @include("code_generator.models_migrations.main_model")



            @if ($is_active && $is_default)

            @include("code_generator.models_migrations.disabled_migration")

            @include("code_generator.models_migrations.disabled_model")
            
            @endif





        </div>
    </div>
</div>
