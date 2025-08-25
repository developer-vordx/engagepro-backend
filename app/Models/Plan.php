<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'priority_support' => 'boolean',
        'custom_branding' => 'boolean',
        'api_access' => 'boolean',
        'max_accounts_per_platform' => 'integer',
        'max_posts_per_month' => 'integer',
        'max_file_size_mb' => 'integer',
        'analytics_retention_days' => 'integer',
        'duration' => 'boolean',
    ];

    public function subscriptionFeatures(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function customerSubscriptions(): HasMany
    {
        return $this->hasMany(CustomerPlan::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByBillingCycle($query, string $cycle)
    {
        return $query->where('type', $cycle);
    }

    // Helper Methods
    public function isFree(): bool
    {
        return $this->price == 0;
    }

    public function hasFeature(string $featureName): bool
    {
        return $this->subscriptionFeatures()
            ->where('feature_name', $featureName)
            ->where('is_enabled', true)
            ->exists();
    }

    public function getMonthlyPrice(): float
    {
        return match ($this->type) {
            'yearly' => $this->price / 12,
            default => $this->price,
        };
    }

    public function getYearlyDiscount(): float
    {
        if ($this->type === 'yearly') {
            $monthlyEquivalent = $this->getMonthlyPrice() * 12;
            return (($monthlyEquivalent - $this->price) / $monthlyEquivalent) * 100;
        }
        return 0;
    }
}
