<?php

require_once 'vendor/autoload.php';

use App\Library\SocialManager\XService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== X (Twitter) OAuth Signature Debug v2 ===\n\n";

try {
    $xService = new XService();
    
    echo "✓ X (Twitter) service initialized\n";
    
    // Test OAuth signature generation for tweet posting
    echo "\n--- Testing OAuth Signature for Tweet Posting ---\n";
    
    $tweetUrl = 'https://api.twitter.com/1.1/statuses/update.json';
    $tweetData = [
        'status' => 'Test tweet from Qovex Studio - ' . date('Y-m-d H:i:s') . ' #Test #QovexStudio #Debug'
    ];
    
    // Use reflection to access private method
    $reflection = new ReflectionClass($xService);
    $method = $reflection->getMethod('generateOAuth1Signature');
    $method->setAccessible(true);
    
    $tweetHeaders = $method->invoke($xService, 'POST', $tweetUrl, $tweetData);
    
    echo "Tweet URL: {$tweetUrl}\n";
    echo "Tweet data: " . json_encode($tweetData) . "\n";
    echo "Generated Headers:\n";
    
    foreach ($tweetHeaders as $key => $value) {
        if ($key === 'Authorization') {
            echo "  {$key}: " . substr($value, 0, 100) . "...\n";
        } else {
            echo "  {$key}: {$value}\n";
        }
    }
    
    // Let's also test the OAuth signature manually
    echo "\n--- Manual OAuth Signature Test ---\n";
    
    // Get the actual values from the service
    $clientId = $reflection->getProperty('clientId')->getValue($xService);
    $clientSecret = $reflection->getProperty('clientSecret')->getValue($xService);
    $accessToken = $reflection->getProperty('accessToken')->getValue($xService);
    $accessTokenSecret = $reflection->getProperty('accessTokenSecret')->getValue($xService);
    
    echo "Using credentials:\n";
    echo "  Client ID: {$clientId}\n";
    echo "  Client Secret: " . substr($clientSecret, 0, 10) . "...\n";
    echo "  Access Token: {$accessToken}\n";
    echo "  Access Token Secret: " . substr($accessTokenSecret, 0, 10) . "...\n";
    
    // Test a simple GET request first (which was working before)
    echo "\n--- Testing Simple GET Request (should work) ---\n";
    
    $verifyUrl = 'https://api.twitter.com/1.1/account/verify_credentials.json';
    $verifyHeaders = $method->invoke($xService, 'GET', $verifyUrl, []);
    
    echo "Verify credentials URL: {$verifyUrl}\n";
    
    $verifyResponse = \App\Helper::makeHttpRequest('GET', $verifyUrl, [], $verifyHeaders, false, 'x');
    
    echo "Verify Response Status: {$verifyResponse['header_code']}\n";
    
    if ($verifyResponse['header_code'] == 200) {
        echo "✅ User verification successful\n";
        if (isset($verifyResponse['body']['screen_name'])) {
            echo "User: @{$verifyResponse['body']['screen_name']}\n";
        }
    } else {
        echo "❌ User verification failed\n";
        if (is_array($verifyResponse['body'])) {
            foreach ($verifyResponse['body'] as $key => $value) {
                if (is_array($value)) {
                    echo "  {$key}: " . json_encode($value) . "\n";
                } else {
                    echo "  {$key}: {$value}\n";
                }
            }
        }
    }
    
    // Now test the tweet posting
    echo "\n--- Testing Tweet Posting ---\n";
    
    $tweetResponse = \App\Helper::makeHttpRequest('POST', $tweetUrl, $tweetData, $tweetHeaders, true, 'x');
    
    echo "Tweet Response Status: {$tweetResponse['header_code']}\n";
    
    if ($tweetResponse['header_code'] == 200) {
        echo "🎉 SUCCESS! Tweet posted!\n";
        if (isset($tweetResponse['body']['id_str'])) {
            echo "Tweet ID: {$tweetResponse['body']['id_str']}\n";
            echo "Tweet URL: https://twitter.com/user/status/{$tweetResponse['body']['id_str']}\n";
        }
    } else {
        echo "❌ Tweet posting failed\n";
        echo "Response Body:\n";
        
        if (is_array($tweetResponse['body'])) {
            foreach ($tweetResponse['body'] as $key => $value) {
                if (is_array($value)) {
                    echo "  {$key}: " . json_encode($value) . "\n";
                } else {
                    echo "  {$key}: {$value}\n";
                }
            }
        } else {
            echo "  {$tweetResponse['body']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Test failed with error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}
