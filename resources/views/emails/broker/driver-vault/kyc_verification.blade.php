<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Driver Assure – KYC Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            margin: 0;
            padding: 40px;
        }
        .email-container {
            max-width: 600px;
            margin: auto;
            background: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .header {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
        }
        .footer {
            margin-top: 40px;
            font-size: 12px;
            color: #888;
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">You're Invited to Join Driver Assure</div>

    <p>Hi {{ $driver->first_name ?? 'Driver' }},</p>

    <p><strong>{{ $broker->first_name }}</strong> has invited you to join their dispatch panel on <strong>Driver Assure</strong> for managing and receiving loads.</p>

    <p>To get started, please complete your KYC verification process by clicking the button below:</p>

    <a href="{{ $link ?? '' }}" class="button">Verify and Onboard</a>

    <p>If the button doesn't work, copy and paste this link into your browser:</p>
    <p>{{ $link ?? ''}}</p>

    <p>If you have any questions or believe this was sent in error, please contact our support team.</p>

    <div class="footer">
        &copy; {{ date('Y') }} Driver Assure. All rights reserved.
    </div>
</div>
</body>
</html>
