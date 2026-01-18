<div class="container">
    <h1 class="text-center mt-5">Permissions</h1>
    <div class="row justify-content-center">
        <div class="col-md-8">



            <div class="code-snippet">
                <h3>Permissions</h3>
                <pre id="permissions"><code>
                    "{{$names['singular_table_name']}}_create",
                    "{{$names['singular_table_name']}}_update",
                    @if ($is_active)
                    "{{$names['singular_table_name']}}_activate",
                    @endif

                    "{{$names['singular_table_name']}}_view",
                    "{{$names['singular_table_name']}}_delete",

      </code></pre>
                <button class="copy-button" onclick="copyToClipboard('permissions')">Copy</button>
            </div>



        </div>
    </div>
</div>

