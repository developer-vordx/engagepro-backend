<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
    <style>
        /* Core Color Scheme */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
            background-color: #4338CA;
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
            background: linear-gradient(135deg, #4338CA, #1E293B);
            color: #FFFFFF;
            padding: 40px 40px 30px;
            text-align: center;
            position: relative;
        }

        .header-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header-icon svg {
            width: 40px;
            height: 40px;
            fill: #FFFFFF;
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
            margin-bottom: 10px;
        }

        .content {
            padding: 40px;
        }

        h1 {
            color: #4F46E5;
            font-size: 28px;
            margin-bottom: 25px;
            text-align: center;
        }

        h2 {
            font-size: 20px;
            margin: 30px 0 15px;
            color: #1F2937;
        }

        p {
            margin-bottom: 20px;
            color: #1F2937;
            font-size: 16px;
        }

        .highlight {
            font-weight: 600;
            color: #4F46E5;
        }

        .alert-box {
            background-color: rgba(239, 68, 68, 0.1);
            border-left: 4px solid #EF4444;
            padding: 20px;
            margin: 25px 0;
            border-radius: 0 8px 8px 0;
            display: flex;
            align-items: center;
        }

        .alert-box svg {
            width: 24px;
            height: 24px;
            fill: #EF4444;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .info-box {
            background-color: #4338CA;
            border-left: 4px solid #8B5CF6;
            padding: 20px;
            margin: 25px 0;
            border-radius: 0 8px 8px 0;
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
            font-size: 16px;
        }

        .btn:hover {
            background-color: #4338CA;
        }

        .verification-link {
            word-break: break-all;
            color: #4F46E5;
            font-size: 14px;
        }

        .footer {
            background-color: #4338CA;
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

        .security-tips {
            margin-top: 30px;
            border-top: 1px dashed #E5E7EB;
            padding-top: 20px;
        }

        .security-tips h3 {
            color: #4F46E5;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .tip {
            display: flex;
            margin-bottom: 10px;
        }

        .tip-bullet {
            color: #4F46E5;
            font-weight: bold;
            margin-right: 10px;
        }

        /* Responsive design */
        @media (max-width: 600px) {
            .header, .content, .footer {
                padding: 25px;
            }

            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <div class="header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M12,17A2,2 0 0,0 14,15C14,13.89 13.1,13 12,13A2,2 0 0,0 10,15A2,2 0 0,0 12,17M18,8A2,2 0 0,1 20,10V20A2,2 0 0,1 18,22H6A2,2 0 0,1 4,20V10C4,8.89 4.9,8 6,8H7V6A5,5 0 0,1 12,1A5,5 0 0,1 17,6V8H18M12,3A3,3 0 0,0 9,6V8H15V6A3,3 0 0,0 12,3Z" />
            </svg>
        </div>
        <div class="logo">Meedyo</div>
        <div class="tagline">Your security is our priority</div>
    </div>

    <div class="content">
        <h1>Reset Your Password</h1>

        <p>Hello <span class="highlight">{{ $name }}</span>,</p>

        <p>We received a request to reset your password. If you didn't make this request, you can safely ignore this email.</p>

        <div class="alert-box">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M11,15H13V17H11V15M11,7H13V13H11V7M12,2C6.47,2 2,6.5 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20Z" />
            </svg>
            <div>
                This password reset link will expire in 1 hour for security reasons.
            </div>
        </div>

        <a href="{{ env('APP_URL') . '/forgot-password/' . $email . '/' . $token }}" class="btn">Reset Password</a>

        <p>If you have trouble with the button above, copy and paste the following URL into your browser:</p>

        <div class="info-box">
            <div class="verification-link">
                {{ env('APP_URL') . '/forgot-password/' . $email . '/' . $token }}
            </div>
        </div>

        <div class="security-tips">
            <h3>Password Security Tips</h3>

            <div class="tip">
                <span class="tip-bullet">•</span>
                <span>Create a strong password with a mix of letters, numbers, and symbols</span>
            </div>

            <div class="tip">
                <span class="tip-bullet">•</span>
                <span>Avoid using personal information like birthdays or names</span>
            </div>

            <div class="tip">
                <span class="tip-bullet">•</span>
                <span>Never share your password with anyone</span>
            </div>

            <div class="tip">
                <span class="tip-bullet">•</span>
                <span>Consider using a password manager to store your passwords securely</span>
            </div>
        </div>

        <p>If you didn't request a password reset, please contact our support team immediately.</p>
    </div>

    <div class="footer">
        <p>© {{ now()->format('Y') }} Meedyo. All rights reserved.</p>
        <p>123 Security Avenue, Cyber City, CC 54321</p>
        <p>
            <a href="#" class="contact-link">Contact Support</a> |
            <a href="#" class="contact-link">Security Center</a> |
            <a href="#" class="contact-link">Privacy Policy</a>
        </p>
        <p class="note">This email was sent to {{ $email }} regarding your Meedyo account.</p>
        <p class="note">Please do not reply to this automated message.</p>
    </div>
</div>
</body>
</html>
