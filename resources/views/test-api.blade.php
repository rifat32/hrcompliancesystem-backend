<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Call Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .form-container {
            max-width: 80%;
            margin: 0 auto;
        }
        .result {
            margin-top: 20px;
            font-weight: bold;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input, textarea, select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        button {
            padding: 10px 15px;
        }
          #response {
      margin-top: 20px;
      padding: 10px;
      background-color: #f0f0f0;
      border: 1px solid #ddd;
      white-space: pre-wrap;
    }
    </style>
       <style>
        .form-group {
            margin: 20px 0;
        }
        textarea {
            width: 100%;
            height: 900px; /* Adjust as needed */
            font-family: monospace; /* For better readability */
            font-size: 16px; /* Increase font size */
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>API Call Form </h1>
        <form id="apiForm">
            <div class="form-group">
                <label for="apiUrl">API URL:</label>
                <input type="text" id="apiUrl" name="apiUrl" required value="{{$activity_log->api_url}}">
            </div>
            <div class="form-group">
                <label for="method">Request Method:</label>
                <select id="method" name="method" required>
                    <option value="GET" {{ $activity_log->request_method == "GET" ? 'selected' : '' }}>GET</option>
                    <option value="POST" {{ $activity_log->request_method == "POST" ? 'selected' : '' }}>POST</option>
                    <option value="PUT" {{ $activity_log->request_method == "PUT" ? 'selected' : '' }}>PUT</option>
                    <option value="DELETE" {{ $activity_log->request_method == "DELETE" ? 'selected' : '' }}>DELETE</option>
                </select>
            </div>


            <div class="form-group">
                <label for="bearerToken">JWT Token:</label>
                <input type="text" id="bearerToken" name="bearerToken" required value="{{$activity_log->token}}">
            </div>

            <div class="form-group">
                <label for="payload">JSON Body:</label>
                <textarea id="payload" name="payload" required rows="20" cols="80">{{!empty($activity_log->fields) ? json_encode(json_decode($activity_log->fields), JSON_PRETTY_PRINT) : '[]'}}</textarea>
                <button type="button" onclick="formatJSON()">Format JSON</button>
            </div>

            <script>
                function formatJSON() {
                    const textarea = document.getElementById('jsonBody');
                    try {
                        const json = JSON.parse(textarea.value);
                        textarea.value = JSON.stringify(json, null, 2); // Indent with 2 spaces
                    } catch (e) {
                        alert('Invalid JSON. Please check your input.');
                    }
                }
            </script>


            <button type="submit">Hit API</button>
        </form>
      <div id="response"></div>
    </div>



<script>
  const form = document.getElementById('apiForm');
  const responseDiv = document.getElementById('response');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const apiUrl = document.getElementById('apiUrl').value.trim();
    const bearerToken = document.getElementById('bearerToken').value.trim();
    const payloadRaw = document.getElementById('payload').value.trim();
    const method = document.getElementById('method').value.trim().toUpperCase();

    try {
      const headers = {
        'Authorization': `Bearer ${bearerToken}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };

      let fullUrl = apiUrl;
      let body = undefined;

      if (payloadRaw) {
        const payloadObj = JSON.parse(payloadRaw);

        if (method === 'GET') {
          const queryParams = new URLSearchParams(payloadObj).toString();
          fullUrl += (fullUrl.includes('?') ? '&' : '?') + queryParams;
        } else {
          body = JSON.stringify(payloadObj);
        }
      }

      const response = await fetch(fullUrl, {
        method,
        headers,
        body: method !== 'GET' ? body : undefined
      });

      const contentType = response.headers.get('content-type');
      let responseData;

      if (contentType && contentType.includes('application/json')) {
        responseData = await response.json();
        responseDiv.innerText = JSON.stringify(responseData, null, 2);
      } else {
        responseData = await response.text();
        responseDiv.innerText = responseData;
      }

    } catch (error) {
      responseDiv.innerText = 'Error: ' + error.message;
    }
  });
</script>
</body>
</html>
