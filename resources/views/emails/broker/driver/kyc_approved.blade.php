{{--<!-- resources/views/emails/driver_approved.blade.php -->--}}
{{--<!DOCTYPE html>--}}
{{--<html>--}}
{{--<head>--}}
{{--    <meta charset="UTF-8">--}}
{{--    --}}{{-- <title>Driver Approved</title> --}}
{{--    <style>--}}
{{--        body {--}}
{{--            font-family: Arial, sans-serif;--}}
{{--            background-color: #f6fff9;--}}
{{--            padding: 20px;--}}
{{--        }--}}
{{--        .email-container {--}}
{{--            background-color: #ffffff;--}}
{{--            border: 1px solid #cde8d4;--}}
{{--            padding: 30px;--}}
{{--            max-width: 600px;--}}
{{--            margin: auto;--}}
{{--            border-radius: 8px;--}}
{{--        }--}}
{{--        .status {--}}
{{--            color: #2e7d32;--}}
{{--            font-size: 20px;--}}
{{--            margin-bottom: 20px;--}}
{{--        }--}}
{{--        .button {--}}
{{--            background-color: #4caf50;--}}
{{--            color: #ffffff;--}}
{{--            padding: 12px 20px;--}}
{{--            text-decoration: none;--}}
{{--            border-radius: 5px;--}}
{{--            display: inline-block;--}}
{{--        }--}}
{{--        .broker-info {--}}
{{--            margin-top: 20px;--}}
{{--            font-size: 14px;--}}
{{--            color: #444;--}}
{{--        }--}}
{{--    </style>--}}
{{--</head>--}}
{{--<body>--}}
{{--    <div class="email-container">--}}
{{--        <h2>Hello {{ $driver_name }},</h2>--}}
{{--        <p class="status">Congratulations! Your driver account has been <strong>approved</strong>.</p>--}}
{{--        <p>You can now log in and start accepting rides.</p>--}}
{{--        --}}{{-- <p><a href="/login" class="button">Login Now</a></p> --}}
{{--        <p class="broker-info">--}}
{{--            Approved by: <strong>{{ $broker }}</strong>--}}
{{--        </p>--}}
{{--        <p>Thank you,<br>The Admin Team</p>--}}
{{--    </div>--}}
{{--</body>--}}
{{--</html>--}}

    <!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>KYC & Onboarding Approved</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 20px; color: #333;">
<div style="max-width: 600px; margin: auto; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <h2 style="color: #2a9d8f;">KYC & Onboarding Approved</h2>

    <p>Dear {{ $driver_name }},</p>

    <p>We're pleased to inform you that your <strong>KYC and onboarding documents</strong> have been successfully verified and approved.</p>

    <p>Your profile is now officially approved under the Broker/Company:</p>

    <p style="margin-left: 20px; font-weight: bold;">{{ $broker }}</p>

    <p>You can now begin accepting loads and enjoy the full benefits of our platform.</p>

    <p>If you have any questions, feel free to reach out to our support team.</p>

    <br>
    <p>Thank you,</p>
    <p><strong>Driver Assure Team</strong></p>

    <hr style="margin-top: 40px;">
    <p style="font-size: 12px; color: #999;">This is an automated message. Please do not reply to this email.</p>
</div>
</body>
</html>
