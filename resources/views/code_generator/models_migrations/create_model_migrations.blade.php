


<div class="code-snippet">
    <h3>Create Controller Using CLI</h3>
    <pre id="create_model_command"><code>
        php artisan make:model -m {{ $names["singular_model_name"] }}
  </code></pre>
    <button class="copy-button" onclick="copyToClipboard('create_model_command')">Copy</button>
  </div>



  @if ($is_active && $is_default)
  <div class="code-snippet">
    <h3>Create Controller Using CLI</h3>
    <pre id="create_disabled_model_command"><code>
        php artisan make:model -m Disabled{{ $names["singular_model_name"] }}
  </code></pre>
    <button class="copy-button" onclick="copyToClipboard('create_disabled_model_command')">Copy</button>
  </div>

  @endif
