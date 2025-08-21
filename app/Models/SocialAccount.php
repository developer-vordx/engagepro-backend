<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class SocialAccount extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'required_scopes' => 'array',
        'supported_media_types' => 'array',
        'media_requirements' => 'array',
        'supports_scheduling' => 'boolean',
    ];

    // Relationships
    public function customerAccounts(): HasMany
    {
        return $this->hasMany(CustomerAccount::class, 'social_accounts_id');
    }

    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class, 'social_account_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('slug', $platform);
    }

    // Helper Methods
    public function supportsMediaType(string $mediaType): bool
    {
        return in_array($mediaType, $this->supported_media_types ?? []);
    }

    public function isFileSizeAllowed(int $fileSize): bool
    {
        return $fileSize <= ($this->media_requirements['max_file_size'] ?? 0);
    }

    public function isVideoDurationAllowed(int $duration): bool
    {
        return $duration <= ($this->media_requirements['max_video_duration'] ?? PHP_INT_MAX);
    }
}
