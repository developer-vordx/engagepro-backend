{{--<!-- resources/views/emails/driver_rejected.blade.php -->--}}
{{--<!DOCTYPE html>--}}
{{--<html>--}}
{{--<head>--}}
{{--    <meta charset="UTF-8">--}}
{{--    --}}{{-- <title>Driver Application Rejected</title> --}}
{{--    <style>--}}
{{--        body {--}}
{{--            font-family: Arial, sans-serif;--}}
{{--            background-color: #fff6f6;--}}
{{--            padding: 20px;--}}
{{--        }--}}
{{--        .email-container {--}}
{{--            background-color: #ffffff;--}}
{{--            border: 1px solid #f5c6cb;--}}
{{--            padding: 30px;--}}
{{--            max-width: 600px;--}}
{{--            margin: auto;--}}
{{--            border-radius: 8px;--}}
{{--        }--}}
{{--        .status {--}}
{{--            color: #d32f2f;--}}
{{--            font-size: 20px;--}}
{{--            margin-bottom: 20px;--}}
{{--        }--}}
{{--        .contact {--}}
{{--            margin-top: 15px;--}}
{{--            font-size: 14px;--}}
{{--            color: #555;--}}
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
{{--        <p class="status">We're sorry, but your driver application has been <strong>rejected</strong>.</p>--}}
{{--        <p>If you believe this was a mistake or have any questions, please contact us.</p>--}}
{{--        <p class="broker-info">--}}
{{--            Reviewed by: <strong>{{ $broker }}</strong>--}}
{{--        </p>--}}
{{--        --}}{{-- <p class="contact">Support Email: <a href="mailto:support@example.com">support@example.com</a></p> --}}
{{--        <p>Best regards,<br>The Admin Team</p>--}}
{{--    </div>--}}
{{--</body>--}}
{{--</html>--}}


    <!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>KYC & Onboarding Rejected</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #fdf2f2; padding: 20px; color: #333;">
<div style="max-width: 600px; margin: auto; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <h2 style="color: #e63946;">KYC & Onboarding Rejected</h2>

    <p>Dear {{ $driver_name }},</p>

    <p>We regret to inform you that your <strong>KYC and onboarding request</strong> has been rejected under the Broker/Company:</p>

    <p style="margin-left: 20px; font-weight: bold;">{{ $broker }}</p>

    @if(!empty($rejection_reason))
        <p><strong>Reason:</strong> {{ $rejection_reason }}</p>
    @else
        <p>Unfortunately, your submission did not meet the required verification standards.</p>
    @endif

    <p>Please review your documents and make the necessary corrections before resubmitting your application.</p>

    <p>If you need help or have any questions, feel free to contact our support team.</p>

    <br>
    <p>Thank you,</p>
    <p><strong>Driver Assure Team</strong></p>

    <hr style="margin-top: 40px;">
    <p style="font-size: 12px; color: #999;">This is an automated message. Please do not reply to this email.</p>
</div>
</body>
</html>
