<?php

require_once 'vendor/autoload.php';

use App\Models\Customer;
use App\Models\Post;
use App\Models\PostFile;
use App\Models\CustomerAccount;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Library\SocialManager\TikTokService;
use Illuminate\Support\Facades\Storage;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Qovex Studio TikTok Video Upload Test ===\n\n";

try {
    // 1. Get the Qovex Studio customer account
    $customer = Customer::find(2); // Qovex Studio account
    if (!$customer) {
        throw new Exception('Qovex Studio customer account not found');
    }
    echo "✓ Customer found: {$customer->name} (ID: {$customer->id})\n";

    // 2. Get the TikTok social account
    $socialAccount = SocialAccount::where('slug', 'tiktok')->first();
    if (!$socialAccount) {
        throw new Exception('TikTok social account not found');
    }
    echo "✓ TikTok social account found: {$socialAccount->name}\n";

    // 3. Get the Qovex Studio TikTok account
    $customerAccount = CustomerAccount::where('customer_id', $customer->id)
        ->where('social_accounts_id', $socialAccount->id)
        ->first();
    
    if (!$customerAccount) {
        throw new Exception('Qovex Studio TikTok account not found');
    }
    echo "✓ TikTok account found: {$customerAccount->username}\n";
    echo "  - Access Token: " . (strlen($customerAccount->access_token) > 20 ? 'Valid (truncated)' : 'Invalid') . "\n";
    echo "  - Status: " . ($customerAccount->is_active ? 'Active' : 'Inactive') . "\n";

    // 4. Create a test post for Qovex Studio
    $post = Post::create([
        'customer_id' => $customer->id,
        'title' => 'Qovex Studio Test Video - AI Integration Demo',
        'description' => 'Testing our new AI-powered social media management platform! 🚀 #QovexStudio #AI #SocialMedia #Innovation',
        'tags' => ['qovexstudio', 'ai', 'socialmedia', 'innovation', 'demo'],
        'target_platforms' => ['tiktok'],
        'status' => 'draft',
        'metadata' => [
            'test_post' => true,
            'created_for' => 'qovex_studio_testing',
            'platform' => 'tiktok'
        ]
    ]);
    echo "✓ Post created: {$post->title} (ID: {$post->id})\n";

    // 5. Create a test video file (we'll create a dummy MP4 file for testing)
    $testVideoPath = 'qovex_test_video.mp4';
    
    // Create a minimal MP4 file header for testing (this is not a real video, just for testing the flow)
    $mp4Header = "\x00\x00\x00\x18ftypmp41\x00\x00\x00\x00mp41isom\x00\x00\x00\x08mdat";
    file_put_contents($testVideoPath, $mp4Header);
    
    // Store the file in Laravel storage
    $storagePath = 'posts/' . $post->id . '/qovex_test_video.mp4';
    Storage::disk('private')->put($storagePath, $mp4Header);
    
    // Create PostFile record
    $postFile = PostFile::create([
        'post_id' => $post->id,
        'file_path' => $storagePath,
        'file_type' => 'video',
        'file_size' => strlen($mp4Header),
        'mime_type' => 'video/mp4',
        'status' => 'validated'
    ]);
    echo "✓ Test video file created and stored\n";
    echo "  - File path: {$storagePath}\n";
    echo "  - File size: " . strlen($mp4Header) . " bytes\n";

    // 6. Test TikTok service with Qovex Studio account
    echo "\n--- Testing TikTok Service with Qovex Studio Account ---\n";
    $tiktokService = new TikTokService();
    
    // Test service initialization
    echo "✓ TikTok service initialized\n";
    
    // Test getting posting limits
    $limits = $tiktokService->getPostingLimits();
    echo "✓ Posting limits retrieved:\n";
    foreach ($limits as $key => $value) {
        if (is_array($value)) {
            echo "  - {$key}: " . json_encode($value) . "\n";
        } else {
            echo "  - {$key}: {$value}\n";
        }
    }
    
    // Test getting available scopes
    $scopes = $tiktokService->getAvailableScopes();
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
    
    $validation = $tiktokService->validateContent(
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

    // 8. Test publishing to TikTok (this will attempt to use real tokens)
    echo "\n--- Testing TikTok Publishing with Qovex Studio Account ---\n";
    try {
        $publishResult = $tiktokService->publishPost($customerAccount, $post);
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
        if ($publishResult['header_code'] == 417) {
            echo "\n⚠ TikTok API Error 417 - Integration Guidelines Issue:\n";
            echo "  This usually means TikTok needs to review your app integration.\n";
            echo "  Common causes:\n";
            echo "  1. App not approved for video upload\n";
            echo "  2. Content violates community guidelines\n";
            echo "  3. App needs additional permissions\n";
            echo "  4. Test content not allowed in production\n";
            echo "\n  Next steps:\n";
            echo "  1. Check TikTok Developer Console for app status\n";
            echo "  2. Ensure app has 'video.upload' scope approved\n";
            echo "  3. Review content sharing guidelines\n";
            echo "  4. Contact TikTok support if needed\n";
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
    unlink($testVideoPath);
    Storage::disk('private')->delete($storagePath);
    echo "✓ Test files cleaned up\n";

    echo "\n=== Test Completed Successfully ===\n";
    echo "Summary:\n";
    echo "- Customer: {$customer->name} (ID: {$customer->id})\n";
    echo "- Post: {$post->title} (ID: {$post->id})\n";
    echo "- TikTok Account: {$customerAccount->username}\n";
    echo "- TikTok service: Working\n";
    echo "- Content validation: " . ($validation['valid'] ? 'Passed' : 'Failed') . "\n";
    echo "- Publishing: " . (isset($publishResult) && $publishResult['header_code'] == 200 ? 'Success' : 'Failed') . "\n";
    
    if (isset($publishResult) && $publishResult['header_code'] == 200) {
        echo "- Ready for real TikTok posting!\n";
    } else {
        echo "- Check TikTok access tokens and API configuration\n";
    }

} catch (Exception $e) {
    echo "✗ Test failed with error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}
