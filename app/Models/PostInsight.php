<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class PostInsight extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'views' => 'integer',
        'likes' => 'integer',
        'shares' => 'integer',
        'comments' => 'integer',
        'saves' => 'integer',
        'engagement_rate' => 'decimal:2',
        'additional_metrics' => 'array',
        'last_updated_at' => 'datetime',
    ];

    // Relationships
    public function socialPost(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class);
    }

    // Scopes
    public function scopeLatest($query)
    {
        return $query->orderBy('last_updated_at', 'desc');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('last_updated_at', [$startDate, $endDate]);
    }

    // Helper Methods
    public function getTotalEngagement(): int
    {
        return $this->likes + $this->comments + $this->shares + $this->saves;
    }

    public function calculateEngagementRate(): float
    {
        if ($this->views > 0) {
            return ($this->getTotalEngagement() / $this->views) * 100;
        }
        return 0;
    }

    public function getPerformanceScore(): string
    {
        $rate = $this->engagement_rate;

        if ($rate >= 5) return 'excellent';
        if ($rate >= 3) return 'good';
        if ($rate >= 1) return 'average';
        return 'poor';
    }
}
