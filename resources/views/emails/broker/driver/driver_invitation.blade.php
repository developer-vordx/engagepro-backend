<html>
<body>
<p>Dear {{ $driver['name'] }},</p>

<p>We are excited to invite you to join Driver Assure, our platform designed to enhance your driving experience...</p>

<h3>Your login details:</h3>
<p><strong>Username:</strong> {{ $loginDetails['username'] }}</p>
<p><strong>Password:</strong> {{ $loginDetails['password'] }}</p>

<p>Login Link: <a href="{{ $loginDetails['login_url'] }}">Login here</a></p>

<p><strong>Step 1:</strong> Download the Driver Assure App:</p>
<ul>
    <li>For Android: <a href="{{ $loginDetails['android_link'] }}">Google Play Store</a></li>
    <li>For iOS: <a href="{{ $loginDetails['ios_link'] }}">App Store</a></li>
</ul>

<p><strong>Step 2:</strong> Log in using the credentials provided above.</p>

<p><strong>Step 3:</strong> Complete the onboarding process.</p>

<p>If you have any questions, please feel free to reach out to our support team.</p>

<p>Best regards,<br>
    The Driver Assure Team</p>
</body>
</html>
