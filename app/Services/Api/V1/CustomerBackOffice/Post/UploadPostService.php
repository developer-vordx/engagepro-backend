<?php

namespace App\Services\Api\V1\CustomerBackOffice\Post;

use App\Models\Post;
use App\Models\PostFile;
use App\Models\CustomerPlan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use App\Helper;
use Symfony\Component\HttpFoundation\Response;

class UploadPostService
{
    /**
     * Handle post upload
     *
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(array $data)
    {
        try {
            DB::beginTransaction();

            $customer = Auth::guard('customer')->user();
            $subscription = $data['subscription'] ?? null;

            // Validate subscription limits
            if ($subscription && !$subscription->canCreatePost()) {
                return Helper::response('Monthly post limit reached. Upgrade your subscription.', Response::HTTP_FORBIDDEN);
            }

            // Create the post
            $post = Post::create([
                'customer_id' => $customer->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'tags' => $data['tags'] ?? [],
                'target_platforms' => $data['target_platforms'] ?? [],
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'metadata' => $data['metadata'] ?? [],
                'status' => 'draft',
            ]);

            // Handle file uploads
            $uploadedFiles = [];
            if (isset($data['files']) && is_array($data['files'])) {
                foreach ($data['files'] as $file) {
                    if ($file instanceof UploadedFile) {
                        $uploadedFile = $this->storeFile($file, $customer->id, $post->id);
                        if ($uploadedFile) {
                            $uploadedFiles[] = $uploadedFile;
                        }
                    }
                }
            }

            // Validate file requirements based on subscription
            if ($subscription) {
                $maxFileSize = $subscription->subscriptionPlan->max_file_size_mb * 1024 * 1024; // Convert MB to bytes
                foreach ($uploadedFiles as $uploadedFile) {
                    if ($uploadedFile->file_size > $maxFileSize) {
                        DB::rollBack();
                        return Helper::response("File size exceeds your plan limit of {$subscription->subscriptionPlan->max_file_size_mb}MB", Response::HTTP_BAD_REQUEST);
                    }
                }
            }

            // Update subscription usage
            if ($subscription) {
                $subscription->incrementPostUsage();
            }

            DB::commit();

            return Helper::response([
                'message' => 'Post uploaded successfully',
                'post' => $post->load('postFiles'),
                'uploaded_files' => $uploadedFiles
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();
            return Helper::errors($e);
        }
    }

    /**
     * Store uploaded file
     *
     * @param UploadedFile $file
     * @param int $customerId
     * @param int $postId
     * @return PostFile|null
     */
    private function storeFile(UploadedFile $file, int $customerId, int $postId): ?PostFile
    {
        try {
            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = "posts/{$customerId}/{$postId}/{$filename}";

            // Store file in private disk
            $storedPath = $file->storeAs("posts/{$customerId}/{$postId}", $filename, 'private');

            // Get file info
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();
            $fileType = $this->getFileType($mimeType);

            // Create post file record
            $postFile = PostFile::create([
                'post_id' => $postId,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $storedPath,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'file_type' => $fileType,
                'metadata' => [
                    'original_name' => $file->getClientOriginalName(),
                    'extension' => $file->getClientOriginalExtension(),
                    'uploaded_at' => now()->toISOString(),
                ]
            ]);

            return $postFile;

        } catch (\Exception $e) {
            \Log::error('File upload failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Determine file type from MIME type
     *
     * @param string $mimeType
     * @return string
     */
    private function getFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        } else {
            return 'document';
        }
    }
}



