<?php

require_once 'vendor/autoload.php';

use App\Library\SocialManager\XService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== X (Twitter) Account Verification Test ===\n\n";

try {
    $xService = new XService();
    
    echo "✓ X (Twitter) service initialized\n";
    
    // Test account verification with current credentials
    echo "\n--- Testing Account Verification ---\n";
    
    $verifyUrl = 'https://api.twitter.com/1.1/account/verify_credentials.json';
    
    // Use reflection to access private method
    $reflection = new ReflectionClass($xService);
    $method = $reflection->getMethod('generateOAuth1Signature');
    $method->setAccessible(true);
    
    $verifyHeaders = $method->invoke($xService, 'GET', $verifyUrl, []);
    
    echo "Verify credentials URL: {$verifyUrl}\n";
    
    $verifyResponse = \App\Helper::makeHttpRequest('GET', $verifyUrl, [], $verifyHeaders, false, 'x');
    
    echo "Verify Response Status: {$verifyResponse['header_code']}\n";
    
    if ($verifyResponse['header_code'] == 200) {
        echo "✅ User verification successful\n";
        
        $userData = $verifyResponse['body'];
        echo "\n--- User Account Details ---\n";
        echo "User ID: {$userData['id_str']}\n";
        echo "Screen Name: @{$userData['screen_name']}\n";
        echo "Name: {$userData['name']}\n";
        echo "Description: " . substr($userData['description'] ?? 'No description', 0, 100) . "...\n";
        echo "Followers Count: {$userData['followers_count']}\n";
        echo "Following Count: {$userData['friends_count']}\n";
        echo "Statuses Count: {$userData['statuses_count']}\n";
        echo "Created At: {$userData['created_at']}\n";
        echo "Location: {$userData['location']}\n";
        echo "Verified: " . ($userData['verified'] ? 'Yes' : 'No') . "\n";
        
        // Check if this matches what we expect
        echo "\n--- Account Match Check ---\n";
        if ($userData['screen_name'] === 'QovexStudio') {
            echo "✅ Account matches expected: @QovexStudio\n";
        } else {
            echo "❌ Account mismatch!\n";
            echo "  Expected: @QovexStudio\n";
            echo "  Actual: @{$userData['screen_name']}\n";
            echo "  This explains the authentication failure!\n";
        }
        
        // Check the Access Token format
        echo "\n--- Access Token Analysis ---\n";
        $accessToken = $reflection->getProperty('accessToken')->getValue($xService);
        $accessTokenParts = explode('-', $accessToken);
        
        if (count($accessTokenParts) === 2) {
            $tokenUserId = $accessTokenParts[0];
            $tokenSecret = $accessTokenParts[1];
            
            echo "Access Token User ID: {$tokenUserId}\n";
            echo "Actual User ID: {$userData['id_str']}\n";
            
            if ($tokenUserId === $userData['id_str']) {
                echo "✅ Access Token User ID matches actual user ID\n";
            } else {
                echo "❌ Access Token User ID mismatch!\n";
                echo "  Token belongs to user: {$tokenUserId}\n";
                echo "  But we're trying to post as: {$userData['id_str']}\n";
                echo "  This is the root cause of the authentication failure!\n";
            }
        } else {
            echo "⚠ Access Token format is unexpected: {$accessToken}\n";
        }
        
    } else {
        echo "❌ User verification failed\n";
        echo "Response Body:\n";
        
        if (is_array($verifyResponse['body'])) {
            foreach ($verifyResponse['body'] as $key => $value) {
                if (is_array($value)) {
                    echo "  {$key}: " . json_encode($value) . "\n";
                } else {
                    echo "  {$key}: {$value}\n";
                }
            }
        } else {
            echo "  {$verifyResponse['body']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Test failed with error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}
