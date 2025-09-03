<?php

namespace App\Http\Controllers\Api\V1\CustomerBackOffice\Post;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CustomerBackOffice\Post\CreatePostRequest;
use App\Http\Requests\Api\V1\CustomerBackOffice\Post\UpdatePostRequest;
use App\Http\Requests\Api\V1\CustomerBackOffice\Post\PublishPostRequest;
use App\Models\Post;
use App\Models\PostFile;
use App\Models\CustomerAccount;
use App\Models\SocialPost;
use App\Library\SocialManager\SocialMediaManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Helper;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class PostController extends Controller
{
    private SocialMediaManager $socialMediaManager;

    public function __construct(SocialMediaManager $socialMediaManager)
    {
        $this->socialMediaManager = $socialMediaManager;
    }

    /**
     * Get all posts for the authenticated customer
     */
    public function index(Request $request)
    {
        try {
            $customer = Auth::guard('customer')->user();
            $query = Post::with(['postFiles', 'socialPosts.socialAccount'])
                ->where('customer_id', $customer->id);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by platform
            if ($request->has('platform')) {
                $query->whereHas('socialPosts.socialAccount', function($q) use ($request) {
                    $q->where('slug', $request->platform);
                });
            }

            // Search by title or description
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $posts = $query->orderBy('created_at', 'desc')
                          ->paginate($request->get('per_page', 15));

            return Helper::response($posts, ResponseAlias::HTTP_OK);

        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }

    /**
     * Get a specific post
     */
    public function show($id)
    {
        try {
            $customer = Auth::guard('customer')->user();
            $post = Post::with(['postFiles', 'socialPosts.socialAccount', 'socialPosts.customerAccount'])
                ->where('customer_id', $customer->id)
                ->findOrFail($id);

            return Helper::response($post, ResponseAlias::HTTP_OK);

        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }

    /**
     * Create a new post
     */
    public function store(CreatePostRequest $request)
    {
        try {
            DB::beginTransaction();

            $customer = Auth::guard('customer')->user();
            
            // Create the post
            $post = Post::create([
                'customer_id' => $customer->id,
                'title' => $request->title,
                'description' => $request->description,
                'tags' => $request->tags,
                'target_platforms' => $request->target_platforms,
                'scheduled_at' => $request->scheduled_at,
                'status' => 'draft',
                'metadata' => $request->metadata ?? []
            ]);

            // Handle file uploads
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $this->processFileUpload($post, $file);
                }
            }

            DB::commit();

            $post->load(['postFiles', 'socialPosts']);

            return Helper::response([
                'message' => 'Post created successfully',
                'post' => $post
            ], ResponseAlias::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();
            return Helper::errors($e);
        }
    }

    /**
     * Update a post
     */
    public function update(UpdatePostRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $customer = Auth::guard('customer')->user();
            $post = Post::where('customer_id', $customer->id)->findOrFail($id);

            // Check if post can be edited
            if ($post->status === 'published') {
                return Helper::response('Published posts cannot be edited', ResponseAlias::HTTP_FORBIDDEN);
            }

            $post->update([
                'title' => $request->title,
                'description' => $request->description,
                'tags' => $request->tags,
                'target_platforms' => $request->target_platforms,
                'scheduled_at' => $request->scheduled_at,
                'metadata' => $request->metadata ?? $post->metadata
            ]);

            // Handle new file uploads
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $this->processFileUpload($post, $file);
                }
            }

            // Handle file deletions
            if ($request->has('delete_files')) {
                foreach ($request->delete_files as $fileId) {
                    $file = $post->postFiles()->find($fileId);
                    if ($file) {
                        Storage::delete($file->file_path);
                        $file->delete();
                    }
                }
            }

            DB::commit();

            $post->load(['postFiles', 'socialPosts']);

            return Helper::response([
                'message' => 'Post updated successfully',
                'post' => $post
            ], ResponseAlias::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack();
            return Helper::errors($e);
        }
    }

    /**
     * Delete a post
     */
    public function destroy($id)
    {
        try {
            $customer = Auth::guard('customer')->user();
            $post = Post::where('customer_id', $customer->id)->findOrFail($id);

            // Check if post can be deleted
            if ($post->status === 'published') {
                return Helper::response('Published posts cannot be deleted', ResponseAlias::HTTP_FORBIDDEN);
            }

            // Delete associated files
            foreach ($post->postFiles as $file) {
                Storage::delete($file->file_path);
            }

            $post->delete();

            return Helper::response('Post deleted successfully', ResponseAlias::HTTP_OK);

        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }

    /**
     * Publish a post to social media platforms
     */
    public function publish(PublishPostRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $customer = Auth::guard('customer')->user();
            $post = Post::where('customer_id', $customer->id)->findOrFail($id);

            // Check if post has files
            if ($post->postFiles->count() === 0) {
                return Helper::response('Post must have at least one file to publish', ResponseAlias::HTTP_BAD_REQUEST);
            }

            // Check subscription limits
            $subscription = $customer->subscriptionPlan;
            if ($subscription && $subscription->posts_this_month >= $subscription->max_posts_per_month) {
                return Helper::response('Monthly post limit reached. Upgrade your subscription.', ResponseAlias::HTTP_FORBIDDEN);
            }

            $platforms = $request->platforms ?? $post->target_platforms ?? [];
            $results = [];

            foreach ($platforms as $platform) {
                // Get customer's account for this platform
                $customerAccount = CustomerAccount::where('customer_id', $customer->id)
                    ->whereHas('socialAccount', function($q) use ($platform) {
                        $q->where('slug', $platform);
                    })
                    ->where('is_active', true)
                    ->first();

                if (!$customerAccount) {
                    $results[$platform] = [
                        'success' => false,
                        'error' => 'No active account found for this platform'
                    ];
                    continue;
                }

                // Get the service for this platform
                $service = $this->socialMediaManager->getService($platform);
                if (!$service) {
                    $results[$platform] = [
                        'success' => false,
                        'error' => 'Platform not supported'
                    ];
                    continue;
                }

                // Validate content for this platform
                $validation = $service->validateContent(
                    $post->postFiles->pluck('file_path')->map(function($path) {
                        return storage_path('app/' . $path);
                    })->toArray(),
                    [
                        'title' => $post->title,
                        'description' => $post->description
                    ]
                );

                if (!$validation['valid']) {
                    $results[$platform] = [
                        'success' => false,
                        'error' => 'Content validation failed',
                        'details' => $validation['errors']
                    ];
                    continue;
                }

                // Publish to platform
                $publishResult = $service->publishPost($customerAccount, $post);

                if ($publishResult['header_code'] == ResponseAlias::HTTP_OK) {
                    // Create social post record
                    SocialPost::create([
                        'customer_account_id' => $customerAccount->id,
                        'post_id' => $post->id,
                        'social_account_id' => $customerAccount->social_accounts_id,
                        'platform_post_id' => $publishResult['body']['platform_post_id'],
                        'post_url' => $publishResult['body']['platform_url'] ?? null,
                        'status' => $publishResult['body']['status'],
                        'platform_response' => $publishResult['body'],
                        'published_at' => now()
                    ]);

                    $results[$platform] = [
                        'success' => true,
                        'platform_post_id' => $publishResult['body']['platform_post_id'],
                        'platform_url' => $publishResult['body']['platform_url'] ?? null,
                        'status' => $publishResult['body']['status']
                    ];
                } else {
                    $results[$platform] = [
                        'success' => false,
                        'error' => $publishResult['body'] ?? 'Unknown error'
                    ];
                }
            }

            // Update post status
            $hasSuccessfulPublishes = collect($results)->contains('success', true);
            $post->update([
                'status' => $hasSuccessfulPublishes ? 'published' : 'failed'
            ]);

            // Update subscription post count
            if ($hasSuccessfulPublishes && $subscription) {
                $subscription->increment('posts_this_month');
            }

            DB::commit();

            return Helper::response([
                'message' => 'Post publishing completed',
                'results' => $results,
                'post' => $post->load(['postFiles', 'socialPosts'])
            ], ResponseAlias::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack();
            return Helper::errors($e);
        }
    }

    /**
     * Process file upload for a post
     */
    private function processFileUpload(Post $post, $file)
    {
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('posts/' . $post->id, $fileName, 'private');
        
        $fileType = str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image';
        
        PostFile::create([
            'post_id' => $post->id,
            'file_path' => $filePath,
            'file_type' => $fileType,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'status' => 'pending'
        ]);
    }

    /**
     * Get post analytics
     */
    public function analytics($id)
    {
        try {
            $customer = Auth::guard('customer')->user();
            $post = Post::where('customer_id', $customer->id)->findOrFail($id);

            $analytics = [];
            foreach ($post->socialPosts as $socialPost) {
                $service = $this->socialMediaManager->getService($socialPost->socialAccount->slug);
                if ($service) {
                    $analytics[$socialPost->socialAccount->slug] = $service->getPostAnalytics(
                        $socialPost->customerAccount,
                        $socialPost->platform_post_id
                    );
                }
            }

            return Helper::response([
                'post_id' => $post->id,
                'analytics' => $analytics
            ], ResponseAlias::HTTP_OK);

        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }
}
