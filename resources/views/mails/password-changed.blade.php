<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Updated Successfully</title>
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
            background: linear-gradient(135deg, #111827, #1E293B);
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
            color: #10B981;
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

        .success-message {
            background-color: rgba(16, 185, 129, 0.1);
            border-left: 4px solid #10B981;
            padding: 20px;
            margin: 25px 0;
            border-radius: 0 8px 8px 0;
            display: flex;
            align-items: center;
        }

        .success-message svg {
            width: 24px;
            height: 24px;
            fill: #10B981;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .info-box {
            background-color: #F9FAFB;
            border-left: 4px solid #8B5CF6;
            padding: 20px;
            margin: 25px 0;
            border-radius: 0 8px 8px 0;
        }

        .info-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #1F2937;
        }

        .info-content {
            color: #1F2937;
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
        <h1>Password Updated Successfully</h1>

        <p>Hello <span class="highlight">{{ $name }}</span>,</p>

        <div class="success-message">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M12 2C6.5 2 2 6.5 2 12S6.5 22 12 22 22 17.5 22 12 17.5 2 12 2M10 17L5 12L6.41 10.59L10 14.17L17.59 6.58L19 8L10 17Z" />
            </svg>
            <div>
                This is to confirm that your password was successfully updated on {{ now()->format('F j, Y \\a\\t g:i A') }}.
            </div>
        </div>

        <p>If you initiated this change, no further action is required. Your account security is now enhanced with your new password.</p>

        <div class="info-box">
            <div class="info-title">Security Information:</div>
            <div class="info-content">
                Device: Windows 11 · Chrome Browser<br>
                Location: New York, NY, USA (Approximate)
            </div>
        </div>

        <div class="security-tips">
            <h3>Keep Your Account Secure</h3>

            <div class="tip">
                <span class="tip-bullet">•</span>
                <span>Use a unique password that you don't use on other websites</span>
            </div>

            <div class="tip">
                <span class="tip-bullet">•</span>
                <span>Enable two-factor authentication for extra security</span>
            </div>

            <div class="tip">
                <span class="tip-bullet">•</span>
                <span>Regularly update your password (every 3-6 months)</span>
            </div>

            <div class="tip">
                <span class="tip-bullet">•</span>
                <span>Never share your password with anyone</span>
            </div>
        </div>

        <p>If you did NOT make this change, please contact our support team immediately to secure your account.</p>
    </div>

    <div class="footer">
        <p>© {{ now()->format('Y') }} Meedyo. All rights reserved.</p>
        <p>123 Lahore 54321</p>
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
