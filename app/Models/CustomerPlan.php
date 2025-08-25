<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class CustomerPlan extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'posts_count_reset_date' => 'date',
        'posts_this_month' => 'integer',
        'metadata' => 'array',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Helper Methods
    public function isActive(): bool
    {
        return $this->status === 'active' &&
            (!$this->ends_at || $this->ends_at->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' ||
            ($this->ends_at && $this->ends_at->isPast());
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canCreatePost(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        // Check if we need to reset monthly count
        if ($this->posts_count_reset_date && $this->posts_count_reset_date->isPast()) {
            $this->resetMonthlyUsage();
        }

        return $this->posts_this_month < $this->subscriptionPlan->max_posts_per_month;
    }

    public function incrementPostUsage(): void
    {
        $this->increment('posts_this_month');
    }

    public function resetMonthlyUsage(): void
    {
        $this->update([
            'posts_this_month' => 0,
            'posts_count_reset_date' => now()->addMonth()->startOfMonth(),
        ]);
    }

    public function getRemainingPosts(): int
    {
        return max(0, $this->subscriptionPlan->max_posts_per_month - $this->posts_this_month);
    }

    public function getDaysUntilExpiry(): int
    {
        if (!$this->ends_at) return -1;
        return max(0, now()->diffInDays($this->ends_at, false));
    }
}
