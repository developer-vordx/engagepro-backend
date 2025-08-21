<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class SocialPost extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'media_urls' => 'array',
        'published_at' => 'datetime',
        'platform_response' => 'array',
    ];

    // Relationships

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function customerAccount(): BelongsTo
    {
        return $this->belongsTo(CustomerAccount::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function postInsights(): HasMany
    {
        return $this->hasMany(PostInsight::class, 'social_post_id');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->whereHas('socialAccount', function($q) use ($platform) {
            $q->where('slug', $platform);
        });
    }

    // Helper Methods
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getLatestInsights(): ?PostInsight
    {
        return $this->postInsights()->latest()->first();
    }

    public function getPlatformUrl(): ?string
    {
        $platform = $this->socialAccount->slug;
        $username = $this->customerAccount->username;
        $postId = $this->platform_post_id;

        return match ($platform) {
            'tiktok' => "https://www.tiktok.com/@{$username}/video/{$postId}",
            'instagram' => "https://www.instagram.com/p/{$postId}/",
            'facebook' => "https://www.facebook.com/{$postId}",
            'youtube' => "https://www.youtube.com/watch?v={$postId}",
            'linkedin' => "https://www.linkedin.com/posts/{$postId}",
            default => null,
        };
    }
}
