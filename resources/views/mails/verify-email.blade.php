<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
            background-color: #F9FAFB;
            color: #1F2937;
            line-height: 1.6;
            padding: 20px;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #FFFFFF;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .header {
            background-color: #111827;
            color: #FFFFFF;
            padding: 30px 40px;
            text-align: center;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #FFFFFF;
            margin-bottom: 10px;
        }

        .tagline {
            opacity: 0.8;
            font-size: 14px;
        }

        .content {
            padding: 40px;
        }

        h1 {
            color: #4F46E5;
            font-size: 24px;
            margin-bottom: 20px;
        }

        h2 {
            font-size: 20px;
            margin: 30px 0 15px;
            color: #1F2937;
        }

        p {
            margin-bottom: 20px;
            color: #1F2937;
        }

        .highlight {
            font-weight: 600;
            color: #4F46E5;
        }

        .verification-box {
            background-color: #F9FAFB;
            border-left: 4px solid #8B5CF6;
            padding: 20px;
            margin: 25px 0;
            border-radius: 0 8px 8px 0;
        }

        .verification-link {
            word-break: break-word;
            color: #4F46E5;
        }

        .btn {
            display: inline-block;
            background-color: #4F46E5;
            color: #FFFFFF;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            margin: 25px 0;
            transition: background-color 0.3s;
            width: 100%;
        }

        .btn:hover {
            background-color: #4338CA;
        }

        .footer {
            background-color: #F9FAFB;
            padding: 30px 40px;
            text-align: center;
            border-top: 1px solid #E5E7EB;
            color: #1F2937;
            font-size: 14px;
        }

        .contact-link {
            color: #4F46E5;
            text-decoration: none;
        }

        .contact-link:hover {
            text-decoration: underline;
        }

        .note {
            margin-top: 20px;
            font-size: 13px;
            color: #6B7280;
        }

        @media (max-width: 600px) {
            .header, .content, .footer {
                padding: 25px;
            }

            h1 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <div class="logo">Meedyo</div>
        <div class="tagline">Securely manage your account</div>
    </div>

    <div class="content">
        <h1>Verify Your Email Address</h1>

        <p>Hello <span class="highlight">{{ $name }}</span>,</p>

        <p>Thank you for creating an account with us! To complete your registration and ensure the security of your
            account, please verify your email address by clicking the button below:</p>

        <a href="{{ env('APP_URL') . '/verify-email/' . $email . '/' . $token }}" class="btn">Verify Email Address</a>

        <p>If the button above doesn't work, copy and paste the following URL into your browser:</p>

        <div class="verification-box">
            <div class="verification-link">
                {{ env('APP_URL') . '/verify-email/' . $email . '/' . $token }}
            </div>
        </div>

        <p>For security reasons, this verification link will expire in 24 hours. If you did not create this account,
            please disregard this email.</p>

        <p>If you have any questions or need assistance, feel free to contact our support team.</p>

        <div class="note">
            <p><strong>Note:</strong> This is an automated message. Please do not reply directly to this email.</p>
        </div>
    </div>

    <div class="footer">
        <p>© {{ now()->format('Y') }} Meedyo. All rights reserved.</p>
        <p>123 Lahore 54321</p>
        <p>
            <a href="#" class="contact-link">Contact Support</a> |
            <a href="#" class="contact-link">Privacy Policy</a> |
            <a href="#" class="contact-link">Terms of Service</a>
        </p>
        <p class="note">This email was sent to {{ $email }} because you created an account with Meedyo.</p>
    </div>
</div>
</body>
</html>
