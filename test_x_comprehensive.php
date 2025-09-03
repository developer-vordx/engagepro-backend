<?php

require_once 'vendor/autoload.php';

use App\Library\SocialManager\XService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== X (Twitter) Comprehensive Authentication Test ===\n\n";

try {
    $xService = new XService();
    
    echo "✓ X (Twitter) service initialized\n";
    
    // Test 1: Check app permissions via x-access-level header
    echo "\n--- Test 1: Checking App Permissions ---\n";
    
    $verifyUrl = 'https://api.twitter.com/1.1/account/verify_credentials.json';
    
    // Use reflection to access private method
    $reflection = new ReflectionClass($xService);
    $method = $reflection->getMethod('generateOAuth1Signature');
    $method->setAccessible(true);
    
    $verifyHeaders = $method->invoke($xService, 'GET', $verifyUrl, []);
    
    echo "Making request to: {$verifyUrl}\n";
    
    $response = \App\Helper::makeHttpRequest('GET', $verifyUrl, [], $verifyHeaders, false, 'x');
    
    echo "Response Status: {$response['header_code']}\n";
    
    if ($response['header_code'] == 200) {
        echo "✓ User verification successful\n";
        echo "User: {$response['body']['name']} (@{$response['body']['screen_name']})\n";
        echo "User ID: {$response['body']['id_str']}\n";
        
        // Check if we can get the x-access-level header (this would be in the actual HTTP response)
        echo "Note: Check the x-access-level header in the response to see app permissions\n";
        echo "Expected values: read, read-write, or read-write-directmessages\n";
    } else {
        echo "✗ User verification failed\n";
        if (is_array($response['body'])) {
            foreach ($response['body'] as $key => $value) {
                if (is_array($value)) {
                    echo "  {$key}: " . json_encode($value) . "\n";
                } else {
                    echo "  {$key}: {$value}\n";
                }
            }
        }
    }
    
    // Test 2: Try to get user's timeline (read permission test)
    echo "\n--- Test 2: Testing Read Permission ---\n";
    
    $timelineUrl = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
    $timelineParams = ['count' => '1'];
    
    $timelineHeaders = $method->invoke($xService, 'GET', $timelineUrl, $timelineParams);
    
    echo "Making request to: {$timelineUrl}\n";
    
    $timelineResponse = \App\Helper::makeHttpRequest('GET', $timelineUrl, $timelineParams, $timelineHeaders, false, 'x');
    
    echo "Timeline Response Status: {$timelineResponse['header_code']}\n";
    
    if ($timelineResponse['header_code'] == 200) {
        echo "✓ Read permission confirmed\n";
        if (isset($timelineResponse['body'][0])) {
            echo "Latest tweet: " . substr($timelineResponse['body'][0]['text'], 0, 100) . "...\n";
        }
    } else {
        echo "✗ Read permission failed\n";
        if (is_array($timelineResponse['body'])) {
            foreach ($timelineResponse['body'] as $key => $value) {
                if (is_array($value)) {
                    echo "  {$key}: " . json_encode($value) . "\n";
                } else {
                    echo "  {$key}: {$value}\n";
                }
            }
        }
    }
    
    // Test 3: Try to post a simple tweet without media (write permission test)
    echo "\n--- Test 3: Testing Write Permission (Simple Tweet) ---\n";
    
    $tweetUrl = 'https://api.twitter.com/1.1/statuses/update.json';
    $tweetData = [
        'status' => 'Test tweet from Qovex Studio - ' . date('Y-m-d H:i:s') . ' #Test #QovexStudio'
    ];
    
    $tweetHeaders = $method->invoke($xService, 'POST', $tweetUrl, $tweetData);
    $tweetHeaders['Content-Type'] = 'application/x-www-form-urlencoded';
    
    echo "Making request to: {$tweetUrl}\n";
    echo "Tweet content: {$tweetData['status']}\n";
    
    $tweetResponse = \App\Helper::makeHttpRequest('POST', $tweetUrl, $tweetData, $tweetHeaders, true, 'x');
    
    echo "Tweet Response Status: {$tweetResponse['header_code']}\n";
    
    if ($tweetResponse['header_code'] == 200) {
        echo "✓ Write permission confirmed!\n";
        echo "Tweet posted successfully!\n";
        echo "Tweet ID: {$tweetResponse['body']['id_str']}\n";
        echo "Tweet URL: https://twitter.com/user/status/{$tweetResponse['body']['id_str']}\n";
    } else {
        echo "✗ Write permission failed\n";
        echo "This confirms the app only has READ permissions\n";
        
        if (is_array($tweetResponse['body'])) {
            foreach ($tweetResponse['body'] as $key => $value) {
                if (is_array($value)) {
                    echo "  {$key}: " . json_encode($value) . "\n";
                } else {
                    echo "  {$key}: {$value}\n";
                }
            }
        }
    }
    
    // Summary and recommendations
    echo "\n=== Test Summary ===\n";
    
    if ($response['header_code'] == 200 && $timelineResponse['header_code'] == 200) {
        echo "✓ READ permissions: Working\n";
        
        if (isset($tweetResponse) && $tweetResponse['header_code'] == 200) {
            echo "✓ WRITE permissions: Working\n";
            echo "🎉 X (Twitter) integration is fully functional!\n";
        } else {
            echo "✗ WRITE permissions: Failed\n";
            echo "\n🔧 To fix this issue:\n";
            echo "1. Go to https://developer.x.com/apps\n";
            echo "2. Select your app\n";
            echo "3. Go to 'App permissions'\n";
            echo "4. Change from 'Read only' to 'Read and write'\n";
            echo "5. Regenerate your access tokens\n";
            echo "6. Update your config/services.php with new tokens\n";
        }
    } else {
        echo "✗ READ permissions: Failed\n";
        echo "✗ WRITE permissions: Failed\n";
        echo "\n🔧 To fix this issue:\n";
        echo "1. Check your API keys and tokens in config/services.php\n";
        echo "2. Verify your app is approved and active\n";
        echo "3. Ensure you have the correct OAuth 1.0a setup\n";
    }
    
} catch (Exception $e) {
    echo "✗ Test failed with error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}
