<!DOCTYPE html>
<html>
<head>
    <title>{{ $reminder->title }}</title>
</head>
<body>
    <p>Hello,</p>
    <p>{{ $notification_description }}</p>
    <p><a href="{{ $notification_link }}">Click here</a> to check details.</p>
    <p>Best regards,</p>
    <p>{{ config('app.name') }}</p>
</body>
</html>
