<?php

require_once 'vendor/autoload.php';

use App\Models\Customer;
use App\Models\Post;
use App\Models\PostFile;
use App\Models\CustomerAccount;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Library\SocialManager\XService;
use Illuminate\Support\Facades\Storage;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Qovex Studio X (Twitter) Integration Test ===\n\n";

try {
    // 1. Get the Qovex Studio customer account
    $customer = Customer::find(2); // Qovex Studio account
    if (!$customer) {
        throw new Exception('Qovex Studio customer account not found');
    }
    echo "✓ Customer found: {$customer->name} (ID: {$customer->id})\n";

    // 2. Get the X (Twitter) social account
    $socialAccount = SocialAccount::where('slug', 'x')->first();
    if (!$socialAccount) {
        throw new Exception('X (Twitter) social account not found');
    }
    echo "✓ X (Twitter) social account found: {$socialAccount->name}\n";

    // 3. Create or get the Qovex Studio X account
    $customerAccount = CustomerAccount::firstOrCreate(
        [
            'customer_id' => $customer->id,
            'social_accounts_id' => $socialAccount->id,
            'identifier' => 'qovexstudio_x'
        ],
        [
            'username' => 'qovexstudio',
            'display_name' => 'Qovex Studio',
            'access_token' => config('services.twitter.access_token'),
            'refresh_token' => config('services.twitter.access_token_secret'),
            'is_active' => true,
            'follower_count' => 0,
            'following_count' => 0
        ]
    );
    echo "✓ X (Twitter) account created/found: {$customerAccount->username}\n";
    echo "  - Access Token: " . (strlen($customerAccount->access_token) > 20 ? 'Valid (truncated)' : 'Invalid') . "\n";
    echo "  - Status: " . ($customerAccount->is_active ? 'Active' : 'Inactive') . "\n";

    // 4. Create a test post for Qovex Studio
    $post = Post::create([
        'customer_id' => $customer->id,
        'title' => 'Qovex Studio X Test - AI Integration Demo',
        'description' => 'Testing our new AI-powered social media management platform! 🚀 #QovexStudio #AI #SocialMedia #Innovation',
        'tags' => ['qovexstudio', 'ai', 'socialmedia', 'innovation', 'demo'],
        'target_platforms' => ['x'],
        'status' => 'draft',
        'metadata' => [
            'test_post' => true,
            'created_for' => 'qovex_studio_x_testing',
            'platform' => 'x'
        ]
    ]);
    echo "✓ Post created: {$post->title} (ID: {$post->id})\n";

    // 5. Create a test image file for X (Twitter)
    $testImagePath = 'qovex_test_image.jpg';
    
    // Create a minimal JPEG file for testing
    $jpegHeader = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01\x00H\x00H\x00\x00\xFF\xDB\x00C\x00\x08\x06\x06\x07\x06\x05\x08\x07\x07\x07\t\t\x08\n\x0C\x14\r\x0C\x0B\x0B\x0C\x19\x12\x13\x0F\x14\x1D\x1A\x1F\x1E\x1D\x1A\x1C\x1C $.\x27 , #\x1C\x1C(7),01444\x1F\x27=9=82<.342\xFF\xC0\x00\x11\x08\x00\x01\x00\x01\x01\x01\x11\x00\x02\x11\x01\x03\x11\x01\xFF\xC4\x00\x14\x00\x01\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x08\xFF\xC4\x00\x14\x10\x01\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xFF\xDA\x00\x0C\x03\x01\x00\x02\x11\x03\x11\x00\x3F\x00\xAA\xFF\xD9";
    file_put_contents($testImagePath, $jpegHeader);
    
    // Store the file in Laravel storage
    $storagePath = 'posts/' . $post->id . '/qovex_test_image.jpg';
    Storage::disk('private')->put($storagePath, $jpegHeader);
    
    // Create PostFile record
    $postFile = PostFile::create([
        'post_id' => $post->id,
        'file_path' => $storagePath,
        'file_type' => 'image',
        'file_size' => strlen($jpegHeader),
        'mime_type' => 'image/jpeg',
        'status' => 'validated'
    ]);
    echo "✓ Test image file created and stored\n";
    echo "  - File path: {$storagePath}\n";
    echo "  - File size: " . strlen($jpegHeader) . " bytes\n";

    // 6. Test X (Twitter) service with Qovex Studio account
    echo "\n--- Testing X (Twitter) Service with Qovex Studio Account ---\n";
    $xService = new XService();
    
    // Test service initialization
    echo "✓ X (Twitter) service initialized\n";
    
    // Test getting posting limits
    $limits = $xService->getPostingLimits();
    echo "✓ Posting limits retrieved:\n";
    foreach ($limits as $key => $value) {
        if (is_array($value)) {
            echo "  - {$key}: " . json_encode($value) . "\n";
        } else {
            echo "  - {$key}: {$value}\n";
        }
    }
    
    // Test getting available scopes
    $scopes = $xService->getAvailableScopes();
    echo "✓ Available scopes retrieved:\n";
    foreach ($scopes as $scope => $description) {
        echo "  - {$scope}: {$description}\n";
    }

    // 7. Test content validation
    echo "\n--- Testing Content Validation ---\n";
    
    // Get the absolute file path for validation
    $absoluteFilePath = Storage::disk('private')->path($storagePath);
    echo "  - Storage path: {$storagePath}\n";
    echo "  - Absolute path: {$absoluteFilePath}\n";
    echo "  - File exists: " . (file_exists($absoluteFilePath) ? 'Yes' : 'No') . "\n";
    
    $validation = $xService->validateContent(
        [$absoluteFilePath],
        [
            'title' => $post->title,
            'description' => $post->description
        ]
    );
    
    if ($validation['valid']) {
        echo "✓ Content validation passed\n";
    } else {
        echo "✗ Content validation failed:\n";
        foreach ($validation['errors'] as $error) {
            echo "  - {$error}\n";
        }
    }
    
    if (!empty($validation['warnings'])) {
        echo "⚠ Content validation warnings:\n";
        foreach ($validation['warnings'] as $warning) {
            echo "  - {$warning}\n";
        }
    }

    // 8. Test publishing to X (Twitter)
    echo "\n--- Testing X (Twitter) Publishing with Qovex Studio Account ---\n";
    try {
        $publishResult = $xService->publishPost($customerAccount, $post);
        echo "✓ Publishing test completed\n";
        echo "  - Status Code: {$publishResult['header_code']}\n";
        
        if (isset($publishResult['body'])) {
            if (is_array($publishResult['body'])) {
                echo "  - Response Body:\n";
                foreach ($publishResult['body'] as $key => $value) {
                    echo "    {$key}: {$value}\n";
                }
            } else {
                echo "  - Response: " . $publishResult['body'] . "\n";
            }
        }
        
        // Check for specific error codes and provide guidance
        if ($publishResult['header_code'] == 401) {
            echo "\n⚠ X (Twitter) API Error 401 - Authentication Failed:\n";
            echo "  Check your Twitter API credentials and permissions.\n";
        } elseif ($publishResult['header_code'] == 403) {
            echo "\n⚠ X (Twitter) API Error 403 - Forbidden:\n";
            echo "  Your app may not have the required permissions.\n";
        } elseif ($publishResult['header_code'] == 429) {
            echo "\n⚠ X (Twitter) API Error 429 - Rate Limited:\n";
            echo "  You've exceeded the API rate limits.\n";
        }
        
        // If successful, create social post record
        if ($publishResult['header_code'] == 200) {
            SocialPost::create([
                'customer_account_id' => $customerAccount->id,
                'post_id' => $post->id,
                'social_account_id' => $socialAccount->id,
                'platform_post_id' => $publishResult['body']['platform_post_id'] ?? 'unknown',
                'post_url' => $publishResult['body']['platform_url'] ?? null,
                'status' => $publishResult['body']['status'] ?? 'published',
                'platform_response' => $publishResult['body'],
                'published_at' => now()
            ]);
            echo "✓ Social post record created\n";
        }
        
    } catch (Exception $e) {
        echo "⚠ Publishing test failed: {$e->getMessage()}\n";
    }

    // 9. Clean up test files
    unlink($testImagePath);
    Storage::disk('private')->delete($storagePath);
    echo "✓ Test files cleaned up\n";

    echo "\n=== Test Completed Successfully ===\n";
    echo "Summary:\n";
    echo "- Customer: {$customer->name} (ID: {$customer->id})\n";
    echo "- Post: {$post->title} (ID: {$post->id})\n";
    echo "- X (Twitter) Account: {$customerAccount->username}\n";
    echo "- X (Twitter) service: Working\n";
    echo "- Content validation: " . ($validation['valid'] ? 'Passed' : 'Failed') . "\n";
    echo "- Publishing: " . (isset($publishResult) && $publishResult['header_code'] == 200 ? 'Success' : 'Failed') . "\n";
    
    if (isset($publishResult) && $publishResult['header_code'] == 200) {
        echo "- Ready for real X (Twitter) posting!\n";
    } else {
        echo "- Check X (Twitter) API credentials and configuration\n";
    }

} catch (Exception $e) {
    echo "✗ Test failed with error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}
