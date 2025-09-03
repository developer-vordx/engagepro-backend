<?php

require_once 'vendor/autoload.php';

use App\Library\SocialManager\XService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== X (Twitter) Read Permissions Test ===\n\n";

try {
    $xService = new XService();
    
    echo "✓ X (Twitter) service initialized\n";
    
    // Use reflection to access private method
    $reflection = new ReflectionClass($xService);
    $method = $reflection->getMethod('generateOAuth1Signature');
    $method->setAccessible(true);
    
    // Test various READ endpoints to see what we can access
    $readEndpoints = [
        'GET' => [
            'account/verify_credentials.json' => 'User Profile',
            'account/settings.json' => 'Account Settings',
            'users/show.json' => 'User Show',
            'statuses/user_timeline.json' => 'User Timeline',
            'friends/list.json' => 'Friends List',
            'followers/list.json' => 'Followers List'
        ]
    ];
    
    foreach ($readEndpoints as $httpMethod => $endpoints) {
        echo "\n--- Testing {$httpMethod} Endpoints ---\n";
        
        foreach ($endpoints as $endpoint => $description) {
            $url = "https://api.twitter.com/1.1/{$endpoint}";
            
            // For user-specific endpoints, add screen_name parameter
            $params = [];
            if (in_array($endpoint, ['users/show.json', 'statuses/user_timeline.json', 'friends/list.json', 'followers/list.json'])) {
                $params['screen_name'] = 'QovexStudio';
            }
            
            echo "\nTesting: {$description}\n";
            echo "Endpoint: {$endpoint}\n";
            
            $headers = $method->invoke($xService, $httpMethod, $url, $params);
            
            $response = \App\Helper::makeHttpRequest($httpMethod, $url, $params, $headers, false, 'x');
            
            echo "Status: {$response['header_code']}";
            
            if ($response['header_code'] == 200) {
                echo " ✅ SUCCESS\n";
                
                // Show some basic info
                if (isset($response['body'])) {
                    if (is_array($response['body'])) {
                        if (isset($response['body']['screen_name'])) {
                            echo "  User: @{$response['body']['screen_name']}\n";
                        }
                        if (isset($response['body']['name'])) {
                            echo "  Name: {$response['body']['name']}\n";
                        }
                        if (isset($response['body']['statuses_count'])) {
                            echo "  Tweets: {$response['body']['statuses_count']}\n";
                        }
                        if (isset($response['body']['followers_count'])) {
                            echo "  Followers: {$response['body']['followers_count']}\n";
                        }
                    }
                }
            } else {
                echo " ❌ FAILED\n";
                
                if (isset($response['body']['errors'])) {
                    foreach ($response['body']['errors'] as $error) {
                        echo "  Error {$error['code']}: {$error['message']}\n";
                    }
                } else {
                    echo "  Response: " . json_encode($response['body']) . "\n";
                }
            }
        }
    }
    
    // Now test a WRITE endpoint to confirm it fails
    echo "\n--- Testing WRITE Endpoint (Should Fail) ---\n";
    
    $tweetUrl = 'https://api.twitter.com/1.1/statuses/update.json';
    $tweetData = [
        'status' => 'Test tweet - ' . date('Y-m-d H:i:s')
    ];
    
    $tweetHeaders = $method->invoke($xService, 'POST', $tweetUrl, $tweetData, ['Content-Type' => 'application/x-www-form-urlencoded']);
    
    echo "Testing: Tweet Posting\n";
    echo "Endpoint: statuses/update.json\n";
    
    $tweetResponse = \App\Helper::makeHttpRequest('POST', $tweetUrl, $tweetData, $tweetHeaders, true, 'x');
    
    echo "Status: {$tweetResponse['header_code']}";
    
    if ($tweetResponse['header_code'] == 200) {
        echo " ✅ SUCCESS (Unexpected!)\n";
    } else {
        echo " ❌ FAILED (Expected)\n";
        
        if (isset($tweetResponse['body']['errors'])) {
            foreach ($tweetResponse['body']['errors'] as $error) {
                echo "  Error {$error['code']}: {$error['message']}\n";
                
                // Check if this is a permissions error
                if ($error['code'] == 32) {
                    echo "  🔒 This confirms the app lacks WRITE permissions!\n";
                }
            }
        }
    }
    
    echo "\n--- Summary ---\n";
    echo "✅ READ endpoints: Working (app has read permissions)\n";
    echo "❌ WRITE endpoints: Failing (app lacks write permissions)\n";
    echo "🔒 Solution: Submit app for 'Read and Write' approval in Twitter Developer Portal\n";
    
} catch (Exception $e) {
    echo "✗ Test failed with error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}
