<?php

require_once 'vendor/autoload.php';

use App\Library\SocialManager\XService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== X (Twitter) Detailed Error Analysis ===\n\n";

try {
    $xService = new XService();
    
    echo "✓ X (Twitter) service initialized\n";
    
    // Test 1: Check app rate limits and usage
    echo "\n--- Test 1: Checking App Usage and Limits ---\n";
    
    $usageUrl = 'https://api.twitter.com/2/users/me';
    $usageHeaders = [
        'Authorization' => "Bearer AAAAAAAAAAAAAAAAAAAAAAB3L3wEAAAAA5iCk6BJG6evhyOW%2B5C8j04jJ33w%3DQSNfqwGdzcZN0TltihNaBLoKEklT3oDCfLSvqWyHUerSmZLUiJ"
    ];
    
    echo "Checking app usage at: {$usageUrl}\n";
    
    $usageResponse = \App\Helper::makeHttpRequest('GET', $usageUrl, [], $usageHeaders, false, 'x');
    
    echo "Usage Response Status: {$usageResponse['header_code']}\n";
    
    if ($usageResponse['header_code'] == 200) {
        echo "✓ App usage check successful\n";
        if (is_array($usageResponse['body'])) {
            foreach ($usageResponse['body'] as $key => $value) {
                if (is_array($value)) {
                    echo "  {$key}: " . json_encode($value) . "\n";
                } else {
                    echo "  {$key}: {$value}\n";
                }
            }
        }
    } else {
        echo "✗ App usage check failed\n";
        if (is_array($usageResponse['body'])) {
            foreach ($usageResponse['body'] as $key => $value) {
                if (is_array($value)) {
                    echo "  {$key}: " . json_encode($value) . "\n";
                } else {
                    echo "  {$key}: {$value}\n";
                }
            }
        }
    }
    
    // Test 2: Check if we can get rate limit headers
    echo "\n--- Test 2: Checking Rate Limits ---\n";
    
    $rateLimitUrl = 'https://api.twitter.com/2/tweets/search/recent';
    $rateLimitParams = ['query' => 'test'];
    
    echo "Checking rate limits at: {$rateLimitUrl}\n";
    
    $rateLimitResponse = \App\Helper::makeHttpRequest('GET', $rateLimitUrl, $rateLimitParams, $usageHeaders, false, 'x');
    
    echo "Rate Limit Response Status: {$rateLimitResponse['header_code']}\n";
    
    if ($rateLimitResponse['header_code'] == 200) {
        echo "✓ Rate limit check successful\n";
        echo "Note: Check response headers for x-rate-limit-* values\n";
    } else {
        echo "✗ Rate limit check failed\n";
        if (is_array($rateLimitResponse['body'])) {
            foreach ($rateLimitResponse['body'] as $key => $value) {
                if (is_array($value)) {
                    echo "  {$key}: " . json_encode($value) . "\n";
                } else {
                    echo "  {$key}: {$value}\n";
                }
            }
        }
    }
    
    // Test 3: Check OAuth 2.0 User Context
    echo "\n--- Test 3: Testing OAuth 2.0 User Context ---\n";
    
    echo "Current authentication method: OAuth 1.0a with API Key\n";
    echo "API Key: " . substr(config('services.twitter.api_key'), 0, 10) . "...\n";
    echo "Access Token: " . substr(config('services.twitter.access_token'), 0, 20) . "...\n";
    
    // Test 4: Recommendations based on findings
    echo "\n--- Test 4: Recommendations ---\n";
    
    if ($usageResponse['header_code'] == 200) {
        echo "✓ Your app can access X API v2\n";
        echo "Recommendation: Implement OAuth 2.0 User Context flow\n";
    } else {
        echo "✗ Your app has limited access to X API v2\n";
        echo "Recommendation: Upgrade to Basic tier ($200/month) or use v1.1\n";
    }
    
    echo "\nBased on the X API v2 documentation:\n";
    echo "- Free tier: 500 posts/month (writes) - Very limited\n";
    echo "- Basic tier: 50,000 posts/month (writes) - Recommended\n";
    echo "- Pro tier: 300,000 posts/month (writes) - For businesses\n";
    
} catch (Exception $e) {
    echo "✗ Test failed with error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}
