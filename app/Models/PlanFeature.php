<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class PlanFeature extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Relationships

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    // Scopes
    public function scopeByPlan($query, int $planId)
    {
        return $query->where('plan_id', $planId);
    }

    // Helper Methods
    public function getFeaturesList(): array
    {
        return explode(',', $this->features);
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->getFeaturesList());
    }
}
