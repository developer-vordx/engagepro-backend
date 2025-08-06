<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Two-Factor Verification Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f6f6f6;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            background-color: #ffffff;
            margin: 30px auto;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .code {
            font-size: 32px;
            font-weight: bold;
            color: #2d3748;
            background-color: #edf2f7;
            padding: 15px 25px;
            display: inline-block;
            border-radius: 5px;
            letter-spacing: 4px;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #888;
            margin-top: 30px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Hello {{ $name }},</h2>

    <p>To complete your login, please use the following click the button below to verify you email:</p>

    <div class="code">{{ env('APP_URL') . "/verify-email/{$email}/{$token}" }}</div>

    {{--    <p>If you did not attempt to log in, please secure your account immediately.</p>--}}

    <p>Thank you,<br>

    <div class="footer">
        {{ now() }}
    </div>
</div>
</body>
</html>
