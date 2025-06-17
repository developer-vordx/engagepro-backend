<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KYC Status Update</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f9fafb;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .email-container {
            max-width: 600px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background-color: #4f46e5;
            padding: 20px;
            color: #ffffff;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .body {
            padding: 30px;
        }
        .body h2 {
            color: #111827;
            font-size: 20px;
            margin-bottom: 15px;
        }
        .status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            margin-top: 10px;
        }
        .status.approved {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status.rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status.pending {
            background-color: #fef9c3;
            color: #92400e;
        }
        .footer {
            background-color: #f3f4f6;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #6b7280;
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <h1>KYC Status Notification</h1>
    </div>
    <div class="body">
        <h2>Hello {{ $name }},</h2>

        <p>We wanted to let you know that your Know Your Customer (KYC) verification status has been updated:</p>

        <div class="status {{ strtolower($status) }}">
            {{ ucfirst($status) }}
        </div>

        @if($status === 'Approved')
            <p>🎉 Congratulations! Your KYC has been successfully approved. You now have full access to all our services.</p>
        @elseif($status === 'Declined')
            <p>Unfortunately, your KYC submission was rejected. Please <a href="{{ $retryUrl }}">click here</a> to review and resubmit your details.</p>
        @else
            <p>Your KYC is currently under review. We will notify you as soon as it's verified.</p>
        @endif

        <p>Thank you for your patience and cooperation.<br>— The {{ config('app.name') }} Team</p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
    </div>
</div>
</body>
</html>
