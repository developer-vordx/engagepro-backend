<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reset Your Password</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            background-color: #ffffff;
            margin: 30px auto;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        h1 {
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 20px;
        }
        p {
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .button {
            display: inline-block;
            background-color: #4f46e5;
            color: #ffffff !important;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
        }
        .footer {
            font-size: 13px;
            color: #888888;
            text-align: center;
            margin-top: 40px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Hello {{ $email }},</h1>
    <p>We received a request to reset your password. Click the button below to set a new one:</p>

    <p style="text-align: center;">
        <a href="{{ $resetUrl }}" class="button">Reset Password</a>
    </p>

    <p>If you didn’t request a password reset, you can safely ignore this email. This link will expire shortly for security reasons.</p>

    <div class="footer">
        &copy; {{ date('Y') }} YourAppName. All rights reserved.
    </div>
</div>
</body>
</html>
