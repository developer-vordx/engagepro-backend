<?php

require_once 'vendor/autoload.php';

use App\Library\SocialManager\XService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== X (Twitter) OAuth Signature Debug ===\n\n";

try {
    $xService = new XService();
    
    echo "✓ X (Twitter) service initialized\n";
    
    // Test OAuth signature generation for different endpoints
    echo "\n--- Testing OAuth Signature for Different Endpoints ---\n";
    
    // 1. GET request (working)
    $getUrl = 'https://api.twitter.com/1.1/account/verify_credentials.json';
    $getParams = [];
    
    // 2. POST request (failing)
    $postUrl = 'https://api.twitter.com/1.1/statuses/update.json';
    $postParams = [
        'status' => 'Test tweet'
    ];
    
    // Use reflection to access private method
    $reflection = new ReflectionClass($xService);
    $method = $reflection->getMethod('generateOAuth1Signature');
    $method->setAccessible(true);
    
    echo "1. GET Request Signature:\n";
    $getHeaders = $method->invoke($xService, 'GET', $getUrl, $getParams);
    echo "  URL: {$getUrl}\n";
    echo "  Method: GET\n";
    echo "  Params: " . json_encode($getParams) . "\n";
    echo "  Authorization: " . substr($getHeaders['Authorization'], 0, 100) . "...\n";
    
    echo "\n2. POST Request Signature:\n";
    $postHeaders = $method->invoke($xService, 'POST', $postUrl, $postParams);
    echo "  URL: {$postUrl}\n";
    echo "  Method: POST\n";
    echo "  Params: " . json_encode($postParams) . "\n";
    echo "  Authorization: " . substr($postHeaders['Authorization'], 0, 100) . "...\n";
    
    // Let's also check what properties the service has
    echo "\n--- Service Properties ---\n";
    $reflection = new ReflectionClass($xService);
    $properties = $reflection->getProperties();
    
    foreach ($properties as $property) {
        $property->setAccessible(true);
        $value = $property->getValue($xService);
        
        if (is_string($value) && strlen($value) > 20) {
            echo "  {$property->getName()}: " . substr($value, 0, 20) . "...\n";
        } else {
            echo "  {$property->getName()}: " . (is_array($value) ? json_encode($value) : $value) . "\n";
        }
    }
    
    // Test the actual signature generation step by step
    echo "\n--- OAuth Signature Generation Steps ---\n";
    
    $timestamp = time();
    $nonce = uniqid();
    
    echo "  Timestamp: {$timestamp}\n";
    echo "  Nonce: {$nonce}\n";
    
    // Get the actual values from the service
    $clientId = $reflection->getProperty('clientId')->getValue($xService);
    $clientSecret = $reflection->getProperty('clientSecret')->getValue($xService);
    $accessToken = $reflection->getProperty('accessToken')->getValue($xService);
    $accessTokenSecret = $reflection->getProperty('accessTokenSecret')->getValue($xService);
    
    echo "  Client ID: {$clientId}\n";
    echo "  Client Secret: " . substr($clientSecret, 0, 10) . "...\n";
    echo "  Access Token: " . substr($accessToken, 0, 20) . "...\n";
    echo "  Access Token Secret: " . substr($accessTokenSecret, 0, 10) . "...\n";
    
    // Generate OAuth params manually
    $oauthParams = [
        'oauth_consumer_key' => $clientId,
        'oauth_nonce' => $nonce,
        'oauth_signature_method' => 'HMAC-SHA1',
        'oauth_timestamp' => $timestamp,
        'oauth_token' => $accessToken,
        'oauth_version' => '1.0'
    ];
    
    echo "\n  OAuth Params:\n";
    foreach ($oauthParams as $key => $value) {
        echo "    {$key}: {$value}\n";
    }
    
    // Merge with request params
    $allParams = array_merge($postParams, $oauthParams);
    ksort($allParams);
    
    echo "\n  All Params (sorted):\n";
    foreach ($allParams as $key => $value) {
        echo "    {$key}: {$value}\n";
    }
    
    // Create parameter string
    $paramString = '';
    foreach ($allParams as $key => $value) {
        $paramString .= "{$key}={$value}&";
    }
    $paramString = rtrim($paramString, '&');
    
    echo "\n  Parameter String: {$paramString}\n";
    
    // Create signature base string
    $signatureBase = strtoupper('POST') . '&' . rawurlencode($postUrl) . '&' . rawurlencode($paramString);
    
    echo "\n  Signature Base String:\n";
    echo "    " . strtoupper('POST') . "&" . rawurlencode($postUrl) . "&" . rawurlencode($paramString) . "\n";
    
    // Create signing key
    $signingKey = rawurlencode($clientSecret) . '&' . rawurlencode($accessTokenSecret);
    
    echo "\n  Signing Key: " . rawurlencode($clientSecret) . "&" . rawurlencode($accessTokenSecret) . "\n";
    
    // Generate signature
    $signature = base64_encode(hash_hmac('sha1', $signatureBase, $signingKey, true));
    
    echo "\n  Generated Signature: {$signature}\n";
    
} catch (Exception $e) {
    echo "✗ Test failed with error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}
