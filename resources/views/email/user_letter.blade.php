<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Letter</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 20px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>User Letter</h1>
        </div>
        <div class="content">
            <p>
                Dear User,<br><br>
                Please find the attached letter. If you have any questions or need further assistance, do not hesitate to contact us.<br><br>
                Best regards,<br>
                {{auth()->user()->business->name}}
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }}  {{auth()->user()->business->name}}. All rights reserved.
        </div>
    </div>
</body>
</html>
