<?php

require_once 'vendor/autoload.php';

use App\Library\SocialManager\YouTubeService;
use App\Models\Post;
use App\Models\PostFile;
use Illuminate\Support\Facades\Storage;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🎬 Testing YouTube Video Upload with New Video File\n";
echo "================================================\n\n";

try {
    // Initialize YouTube service
    $youtubeService = new YouTubeService();
    
    // Check if we have access token
    $accessToken = config('services.youtube.access_token');
    if (!$accessToken) {
        echo "❌ No access token found in config\n";
        exit(1);
    }
    
    echo "✅ Access token found\n";
    
    // Check if the new video file exists
    $videoPath = 'video.mp4';
    if (!file_exists($videoPath)) {
        echo "❌ Video file not found: {$videoPath}\n";
        exit(1);
    }
    
    echo "✅ Video file found: {$videoPath}\n";
    
    // Get file info
    $fileSize = filesize($videoPath);
    $mimeType = mime_content_type($videoPath);
    
    echo "📁 File Info:\n";
    echo "   Size: " . number_format($fileSize / 1024 / 1024, 2) . " MB\n";
    echo "   MIME Type: {$mimeType}\n";
    
    // Validate video type
    $isValid = $youtubeService->isValidVideoType($mimeType);
    echo "   Valid video type: " . ($isValid ? "✅ YES" : "❌ NO") . "\n";
    
    if (!$isValid) {
        echo "❌ Invalid video type. YouTube supports: MP4, MOV, AVI, WMV, FLV, WebM\n";
        exit(1);
    }
    
    // Store video in private disk
    $storagePath = 'videos/' . basename($videoPath);
    Storage::disk('private')->put($storagePath, file_get_contents($videoPath));
    
    echo "✅ Video stored in private disk: {$storagePath}\n";
    
    // Create a Post object first
    $post = new Post();
    $post->title = 'Test Video Upload - ' . date('Y-m-d H:i:s');
    $post->description = 'This is a test video upload from our platform.';
    $post->tags = ['test', 'video', 'upload'];
    
    // Create a PostFile object with the storage path
    $postFile = new PostFile();
    $postFile->file_path = $storagePath;
    $postFile->file_name = basename($videoPath);
    $postFile->file_size = $fileSize;
    $postFile->mime_type = $mimeType;
    $postFile->file_type = 'video';
    
    echo "\n🚀 Starting video upload...\n";
    
    // Upload video using the correct method signature
    $result = $youtubeService->uploadVideo(
        $postFile,
        $post,
        $accessToken
    );
    
    echo "\n📤 Upload Result:\n";
    echo "Status Code: " . $result['header_code'] . "\n";
    
    if ($result['header_code'] == 200) {
        echo "✅ Upload successful!\n";
        echo "Video ID: " . $result['body']['video_id'] . "\n";
        echo "Platform URL: " . $result['body']['platform_url'] . "\n";
        echo "Status: " . $result['body']['status'] . "\n";
        
        // Try to get video details
        echo "\n🔍 Getting video details...\n";
        $videoDetails = $youtubeService->getVideoDetails($result['body']['video_id'], $accessToken);
        
        if ($videoDetails['header_code'] == 200) {
            echo "✅ Video details retrieved\n";
            $videoData = $videoDetails['body']['items'][0] ?? [];
            if (isset($videoData['contentDetails']['duration'])) {
                echo "Duration: " . $videoData['contentDetails']['duration'] . "\n";
            }
            if (isset($videoData['statistics']['viewCount'])) {
                echo "View Count: " . $videoData['statistics']['viewCount'] . "\n";
            }
            if (isset($videoData['snippet']['title'])) {
                echo "Title: " . $videoData['snippet']['title'] . "\n";
            }
        } else {
            echo "❌ Failed to get video details: " . ($videoDetails['body']['error']['message'] ?? 'Unknown error') . "\n";
        }
        
    } else {
        echo "❌ Upload failed!\n";
        echo "Error: " . ($result['body']['error'] ?? 'Unknown error') . "\n";
        
        if (isset($result['body'])) {
            echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception occurred: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n✨ Test completed!\n";
