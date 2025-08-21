<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected $casts = [
        'metadata' => 'array',
        'tags' => 'array',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function postFiles(): HasMany
    {
        return $this->hasMany(PostFile::class);
    }

    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    // Scopes
    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeWithFiles($query)
    {
        return $query->with('postFiles');
    }

    // Helper Methods
    public function hasFiles(): bool
    {
        return $this->postFiles()->exists();
    }

    public function getValidatedFiles()
    {
        return $this->postFiles()->where('status', 'validated');
    }

    public function getTotalEngagement(): int
    {
        return $this->socialPosts->sum(function($socialPost) {
            $insights = $socialPost->postInsights->last();
            return $insights ? ($insights->likes + $insights->comments + $insights->shares) : 0;
        });
    }
}
