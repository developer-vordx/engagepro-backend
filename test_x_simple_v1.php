<?php

require_once 'vendor/autoload.php';

use App\Library\SocialManager\XService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== X (Twitter) Simple Tweet Test (API v1.1) ===\n\n";

try {
    $xService = new XService();
    
    echo "✓ X (Twitter) service initialized\n";
    
    // Test posting a simple tweet using Twitter API v1.1 with OAuth 1.0a
    echo "\n--- Testing Simple Tweet Post (API v1.1) ---\n";
    
    $tweetUrl = 'https://api.twitter.com/1.1/statuses/update.json';
    $tweetData = [
        'status' => 'Test tweet from Qovex Studio - ' . date('Y-m-d H:i:s') . ' #Test #QovexStudio #APIv1.1'
    ];
    
    // Use reflection to access private method
    $reflection = new ReflectionClass($xService);
    $method = $reflection->getMethod('generateOAuth1Signature');
    $method->setAccessible(true);
    
    $tweetHeaders = $method->invoke($xService, 'POST', $tweetUrl, $tweetData);
    $tweetHeaders['Content-Type'] = 'application/x-www-form-urlencoded';
    
    echo "Tweet URL: {$tweetUrl}\n";
    echo "Tweet content: {$tweetData['status']}\n";
    echo "Using OAuth 1.0a with API Key\n";
    
    // Make the tweet request
    echo "\n--- Making Tweet Request ---\n";
    
    $tweetResponse = \App\Helper::makeHttpRequest('POST', $tweetUrl, $tweetData, $tweetHeaders, true, 'x');
    
    echo "Response Status: {$tweetResponse['header_code']}\n";
    
    if ($tweetResponse['header_code'] == 200) {
        echo "🎉 SUCCESS! Tweet posted successfully!\n";
        if (isset($tweetResponse['body']['id_str'])) {
            echo "Tweet ID: {$tweetResponse['body']['id_str']}\n";
            echo "Tweet URL: https://twitter.com/user/status/{$tweetResponse['body']['id_str']}\n";
        }
        if (isset($tweetResponse['body']['text'])) {
            echo "Tweet Text: {$tweetResponse['body']['text']}\n";
        }
    } else {
        echo "✗ Tweet posting failed\n";
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
        
        // Check for specific error codes
        if ($tweetResponse['header_code'] == 401) {
            echo "\n⚠ Error 401: Authentication failed\n";
            echo "This usually means the Access Token/Secret are incorrect\n";
        } elseif ($tweetResponse['header_code'] == 403) {
            echo "\n⚠ Error 403: Forbidden - Check app permissions\n";
        } elseif ($tweetResponse['header_code'] == 400) {
            echo "\n⚠ Error 400: Bad Request - Check parameters\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Test failed with error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}
