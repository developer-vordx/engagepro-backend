<?php

require_once 'vendor/autoload.php';

use App\Library\SocialManager\XService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== X (Twitter) OAuth Debug Test ===\n\n";

try {
    $xService = new XService();
    
    echo "✓ X (Twitter) service initialized\n";
    
    // Test OAuth signature generation
    echo "\n--- Testing OAuth Signature Generation ---\n";
    
    $url = 'https://upload.twitter.com/1.1/media/upload.json';
    $data = [
        'media_category' => 'tweet_image',
        'media_data' => 'test_base64_data'
    ];
    
    echo "URL: {$url}\n";
    echo "Data: " . json_encode($data) . "\n";
    
    // Use reflection to access private method
    $reflection = new ReflectionClass($xService);
    $method = $reflection->getMethod('generateOAuth1Signature');
    $method->setAccessible(true);
    
    $headers = $method->invoke($xService, 'POST', $url, $data);
    
    echo "Generated Headers:\n";
    foreach ($headers as $key => $value) {
        if ($key === 'Authorization') {
            echo "  {$key}: " . substr($value, 0, 100) . "...\n";
        } else {
            echo "  {$key}: {$value}\n";
        }
    }
    
    // Test a simple API call to verify credentials
    echo "\n--- Testing Twitter API Credentials ---\n";
    
    // Test with a simple GET request to verify credentials
    $verifyUrl = 'https://api.twitter.com/1.1/account/verify_credentials.json';
    $verifyHeaders = $method->invoke($xService, 'GET', $verifyUrl, []);
    
    echo "Verify Credentials URL: {$verifyUrl}\n";
    echo "Verify Headers:\n";
    foreach ($verifyHeaders as $key => $value) {
        if ($key === 'Authorization') {
            echo "  {$key}: " . substr($value, 0, 100) . "...\n";
        } else {
            echo "  {$key}: {$value}\n";
        }
    }
    
    // Make the actual API call
    echo "\n--- Making API Call ---\n";
    
    $response = \App\Helper::makeHttpRequest('GET', $verifyUrl, [], $verifyHeaders, false, 'x');
    
    echo "Response Status: {$response['header_code']}\n";
    echo "Response Body:\n";
    
    if (is_array($response['body'])) {
        foreach ($response['body'] as $key => $value) {
            if (is_array($value)) {
                echo "  {$key}: " . json_encode($value) . "\n";
            } else {
                echo "  {$key}: {$value}\n";
            }
        }
    } else {
        echo "  {$response['body']}\n";
    }
    
    if ($response['header_code'] == 200) {
        echo "\n✓ Twitter API credentials are working!\n";
        echo "✓ User verified: {$response['body']['name']} (@{$response['body']['screen_name']})\n";
        echo "✓ User ID: {$response['body']['id_str']}\n";
    } else {
        echo "\n✗ Twitter API credentials issue detected\n";
        
        if ($response['header_code'] == 401) {
            echo "Error 401: Authentication failed. Check your API keys and tokens.\n";
        } elseif ($response['header_code'] == 403) {
            echo "Error 403: Forbidden. Your app may not have the required permissions.\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Test failed with error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}
