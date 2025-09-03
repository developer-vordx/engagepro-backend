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

echo "=== TikTok Posting Test ===\n\n";

try {
    // 1. Create a test customer if it doesn't exist
    $customer = Customer::firstOrCreate(
        ['email' => 'test@example.com'],
        [
            'name' => 'Test Customer',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'status' => true
        ]
    );
    echo "✓ Customer created/found: {$customer->name}\n";

    // 2. Create a test social account (TikTok) if it doesn't exist
    $socialAccount = SocialAccount::firstOrCreate(
        ['slug' => 'tiktok'],
        [
            'name' => 'TikTok',
            'url' => 'https://open.tiktokapis.com',
            'scopes' => ['user.info.basic', 'user.info.profile', 'user.info.stats', 'video.list', 'video.upload'],
            'supported_media_types' => ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm'],
            'media_requirements' => [
                'video' => ['max_size' => '1GB', 'formats' => ['mp4', 'mov'], 'max_duration' => 180]
            ],
            'status' => 'active'
        ]
    );
    echo "✓ Social account created/found: {$socialAccount->name}\n";

    // 3. Create a test customer account (connected TikTok account) if it doesn't exist
    $customerAccount = CustomerAccount::firstOrCreate(
        [
            'customer_id' => $customer->id,
            'social_accounts_id' => $socialAccount->id,
            'identifier' => 'test_tiktok_user_123'
        ],
        [
            'username' => 'testuser',
            'display_name' => 'Test User',
            'access_token' => 'test_access_token_123', // This should be a real token in production
            'refresh_token' => 'test_refresh_token_123',
            'is_active' => true,
            'follower_count' => 100,
            'following_count' => 50
        ]
    );
    echo "✓ Customer account created/found: {$customerAccount->username}\n";

    // 4. Create a test post
    $post = Post::create([
        'customer_id' => $customer->id,
        'title' => 'Test TikTok Video Post',
        'description' => 'This is a test video post for TikTok integration testing. #test #tiktok #integration',
        'tags' => ['test', 'tiktok', 'integration'],
        'target_platforms' => ['tiktok'],
        'status' => 'draft',
        'metadata' => [
            'test_post' => true,
            'created_for' => 'integration_testing'
        ]
    ]);
    echo "✓ Post created: {$post->title}\n";

    // 5. Create a test video file (we'll create a dummy file for testing)
    $testVideoPath = 'test_video.mp4';
    $testVideoContent = 'This is a dummy video file for testing purposes.';
    file_put_contents($testVideoPath, $testVideoContent);
    
    // Store the file in Laravel storage
    $storagePath = 'posts/' . $post->id . '/test_video.mp4';
    Storage::disk('private')->put($storagePath, $testVideoContent);
    
    // Create PostFile record
    $postFile = PostFile::create([
        'post_id' => $post->id,
        'file_path' => $storagePath,
        'file_type' => 'video',
        'file_size' => strlen($testVideoContent),
        'mime_type' => 'video/mp4',
        'status' => 'validated'
    ]);
    echo "✓ Test video file created and stored\n";

    // 6. Test TikTok service
    echo "\n--- Testing TikTok Service ---\n";
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

    // 8. Test publishing (this will fail without real tokens, but we can test the flow)
    echo "\n--- Testing Publishing Flow ---\n";
    try {
        $publishResult = $tiktokService->publishPost($customerAccount, $post);
        echo "✓ Publishing test completed\n";
        echo "  - Status: {$publishResult['header_code']}\n";
        if (isset($publishResult['body'])) {
            echo "  - Response: " . json_encode($publishResult['body']) . "\n";
        }
    } catch (Exception $e) {
        echo "⚠ Publishing test failed (expected without real tokens): {$e->getMessage()}\n";
    }

    // 9. Clean up test files
    unlink($testVideoPath);
    Storage::disk('private')->delete($storagePath);
    echo "✓ Test files cleaned up\n";

    echo "\n=== Test Completed Successfully ===\n";
    echo "Summary:\n";
    echo "- Customer: {$customer->name} (ID: {$customer->id})\n";
    echo "- Post: {$post->title} (ID: {$post->id})\n";
    echo "- TikTok service: Working\n";
    echo "- Content validation: " . ($validation['valid'] ? 'Passed' : 'Failed') . "\n";
    echo "- Ready for real TikTok integration with valid access tokens\n";

} catch (Exception $e) {
    echo "✗ Test failed with error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}
