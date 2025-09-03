<?php

require_once 'vendor/autoload.php';

use App\Library\SocialManager\XService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== X (Twitter) Credentials Check ===\n\n";

try {
    $xService = new XService();
    
    echo "✓ X (Twitter) service initialized\n";
    
    // Check what credentials the service is using
    echo "\n--- Checking Service Credentials ---\n";
    
    // Use reflection to access private properties
    $reflection = new ReflectionClass($xService);
    
    $apiKey = $reflection->getProperty('apiKey')->getValue($xService);
    $apiSecret = $reflection->getProperty('apiSecret')->getValue($xService);
    $clientId = $reflection->getProperty('clientId')->getValue($xService);
    $clientSecret = $reflection->getProperty('clientSecret')->getValue($xService);
    $accessToken = $reflection->getProperty('accessToken')->getValue($xService);
    $accessTokenSecret = $reflection->getProperty('accessTokenSecret')->getValue($xService);
    
    echo "Service is using:\n";
    echo "  API Key (OAuth 1.0a): {$apiKey}\n";
    echo "  API Secret (OAuth 1.0a): " . substr($apiSecret, 0, 10) . "...\n";
    echo "  Client ID (OAuth 2.0): {$clientId}\n";
    echo "  Client Secret (OAuth 2.0): " . substr($clientSecret, 0, 10) . "...\n";
    echo "  Access Token: {$accessToken}\n";
    echo "  Access Token Secret: " . substr($accessTokenSecret, 0, 10) . "...\n";
    
    // Check what's in the config
    echo "\n--- Checking Config Values ---\n";
    echo "Config contains:\n";
    echo "  API Key: " . config('services.twitter.api_key') . "\n";
    echo "  API Secret: " . substr(config('services.twitter.api_secret'), 0, 10) . "...\n";
    echo "  Client ID: " . config('services.twitter.client_id') . "\n";
    echo "  Client Secret: " . substr(config('services.twitter.client_secret'), 0, 10) . "...\n";
    echo "  Access Token: " . config('services.twitter.access_token') . "\n";
    echo "  Access Token Secret: " . substr(config('services.twitter.access_token_secret'), 0, 10) . "...\n";
    
    // Check if they match
    echo "\n--- Credential Match Check ---\n";
    if ($apiKey === config('services.twitter.api_key')) {
        echo "✅ API Key: Matches\n";
    } else {
        echo "❌ API Key: MISMATCH!\n";
        echo "  Service: {$apiKey}\n";
        echo "  Config: " . config('services.twitter.api_key') . "\n";
    }
    
    if ($clientId === config('services.twitter.client_id')) {
        echo "✅ Client ID: Matches\n";
    } else {
        echo "❌ Client ID: MISMATCH!\n";
        echo "  Service: {$clientId}\n";
        echo "  Config: " . config('services.twitter.client_id') . "\n";
    }
    
    if ($accessToken === config('services.twitter.access_token')) {
        echo "✅ Access Token: Matches\n";
    } else {
        echo "❌ Access Token: MISMATCH!\n";
        echo "  Service: {$accessToken}\n";
        echo "  Config: " . config('services.twitter.access_token') . "\n";
    }
    
    if ($accessTokenSecret === config('services.twitter.access_token_secret')) {
        echo "✅ Access Token Secret: Matches\n";
    } else {
        echo "❌ Access Token Secret: MISMATCH!\n";
        echo "  Service: " . substr($accessTokenSecret, 0, 10) . "...\n";
        echo "  Config: " . substr(config('services.twitter.access_token_secret'), 0, 10) . "...\n";
    }
    
} catch (Exception $e) {
    echo "✗ Test failed with error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}
