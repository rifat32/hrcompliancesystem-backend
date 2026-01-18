
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bootstrap Demo with Code Snippet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        /* Custom CSS */
        body {
            background-color: #f8f9fa;
            /* Light gray background */
        }

        .code-snippet {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 30px;
            position: relative;
            /* Necessary for absolute positioning of the button */
        }

        .copy-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #f0f0f0;
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            cursor: pointer;
        }

        .select2-container {
  width: 100%!important;
}

.select2-selection {
  border: 1px solid #ccc;
  padding: 6px 12px;
  font-size: 14px;
  line-height: 1.42857143;
  color: #555;
  background-color: #fff;
  background-image: none;
  border-radius: 4px;
  box-shadow: inset 0 1px 1px rgba(0,0,0,.075);
}

.select2-selection__rendered {
  padding-left: 12px;
}

.select2-selection__arrow {
  border-color: #ccc transparent transparent transparent;
  border-style: solid;
  border-width: 5px 5px 0 5px;
  height: 0;
  margin-top: -2px;
  width: 0;
}

.navbar-fixed-top {
  top: 0;
  position: fixed;
}
    </style>
</head>

<body>





    @if (!empty($names))

@include("code_generator.navbar")

    @include("code_generator.routes")
    @include("code_generator.controller.controller")
    @include("code_generator.models_migrations.models_migrations")
    @include("code_generator.request.requests")
    @include("code_generator.custom_rules.custom_rules")
    @include("code_generator.permissions")

    @else

    @include("code_generator.code-generator-form")


    @endif









    {{-- <div class="container">
      <h1 class="text-center mt-5">Sample</h1>
      <div class="row justify-content-center">
          <div class="col-md-8">



              <div class="code-snippet">
                  <h3>Sample Code</h3>
                  <pre id="sample"><code>

// ddd
        </code></pre>
                  <button class="copy-button" onclick="copyToClipboard('sample')">Copy</button>
              </div>


          </div>
      </div>
  </div> --}}



    <script>
        function copyToClipboard(id) {
            const codeElement = document.getElementById(id);
            const codeText = codeElement.textContent;
            const tempElement = document.createElement('textarea');
            tempElement.value = codeText;
            document.body.appendChild(tempElement);
            tempElement.select();
            document.execCommand('copy');
            document.body.removeChild(tempElement);
            alert('Code copied to clipboard!');
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
</body>

</html>
