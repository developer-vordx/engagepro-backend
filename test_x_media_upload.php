<?php

require_once 'vendor/autoload.php';

use App\Library\SocialManager\XService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== X (Twitter) Media Upload Test ===\n\n";

try {
    $xService = new XService();
    
    echo "✓ X (Twitter) service initialized\n";
    
    // Create a test image file
    $testImagePath = 'test_image.jpg';
    $jpegHeader = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01\x00H\x00H\x00\x00\xFF\xDB\x00C\x00\x08\x06\x06\x07\x06\x05\x08\x07\x07\x07\t\t\x08\n\x0C\x14\r\x0C\x0B\x0B\x0C\x19\x12\x13\x0F\x14\x1D\x1A\x1F\x1E\x1D\x1A\x1C\x1C $.\x27 , #\x1C\x1C(7),01444\x1F\x27=9=82<.342\xFF\xC0\x00\x11\x08\x00\x01\x00\x01\x01\x01\x11\x00\x02\x11\x01\x03\x11\x01\xFF\xC4\x00\x14\x00\x01\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x08\xFF\xC4\x00\x14\x10\x01\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xFF\xDA\x00\x0C\x03\x01\x00\x02\x11\x03\x11\x00\x3F\x00\xAA\xFF\xD9";
    file_put_contents($testImagePath, $jpegHeader);
    
    echo "✓ Test image file created: {$testImagePath}\n";
    echo "  - File size: " . strlen($jpegHeader) . " bytes\n";
    echo "  - MIME type: " . mime_content_type($testImagePath) . "\n";
    
    // Test media upload
    echo "\n--- Testing Media Upload ---\n";
    
    $url = 'https://upload.twitter.com/1.1/media/upload.json';
    $data = [
        'media_category' => 'tweet_image',
        'media_data' => base64_encode(file_get_contents($testImagePath))
    ];
    
    echo "Upload URL: {$url}\n";
    echo "Data keys: " . implode(', ', array_keys($data)) . "\n";
    echo "Media data length: " . strlen($data['media_data']) . " characters\n";
    
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
    
    // Make the media upload request
    echo "\n--- Making Media Upload Request ---\n";
    
    $response = \App\Helper::makeHttpRequest('POST', $url, $data, $headers, true, 'x');
    
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
        echo "\n✓ Media upload successful!\n";
        if (isset($response['body']['media_id_string'])) {
            echo "✓ Media ID: {$response['body']['media_id_string']}\n";
        }
    } else {
        echo "\n✗ Media upload failed\n";
        
        if ($response['header_code'] == 401) {
            echo "Error 401: Authentication failed\n";
        } elseif ($response['header_code'] == 403) {
            echo "Error 403: Forbidden - Check app permissions\n";
        } elseif ($response['header_code'] == 400) {
            echo "Error 400: Bad Request - Check parameters\n";
        }
    }
    
    // Clean up
    unlink($testImagePath);
    echo "\n✓ Test image file cleaned up\n";
    
} catch (Exception $e) {
    echo "✗ Test failed with error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}
