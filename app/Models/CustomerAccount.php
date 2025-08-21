<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class CustomerAccount extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'platform_data' => 'array',
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'is_active' => 'boolean',
        'follower_count' => 'integer',
        'following_count' => 'integer',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class, 'social_accounts_id');
    }

    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->whereHas('socialAccount', function($q) use ($platform) {
            $q->where('slug', $platform);
        });
    }

    // Helper Methods
    public function needsReauth(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    public function getPlatformName(): string
    {
        return $this->socialAccount->name ?? 'Unknown';
    }

    public function canPost(): bool
    {
        return $this->is_active && !$this->isTokenExpired();
    }
}
