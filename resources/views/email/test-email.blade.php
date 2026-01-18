<!-- resources/views/emails/test-email.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px;">

    <div style="background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); max-width: 600px; margin: auto;">
        <h2 style="color: #333333; text-align: center;">Test Email Configuration</h2>

        <p>Hello, {{ $user->title . " " . $user->first_Name . " " . $user->middle_Name . " " . $user->last_Name }}!</p>

        <p>This is a test email to verify your email settings. If you're receiving this email, your configuration is working correctly!</p>

        <p>Thank you for using our system.</p>

        <p style="text-align: center; color: #777777; margin-top: 30px;">&copy; {{ date('Y') }} {{$user->business->name}}. All rights reserved.</p>
    </div>

</body>
</html>
