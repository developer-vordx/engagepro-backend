<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Customer extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $guarded = [];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'deleted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // Relationships
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function customerAccounts(): HasMany
    {
        return $this->hasMany(CustomerAccount::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(CustomerSubscription::class)->where('status', 'active');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(CustomerSubscription::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    // Helper Methods
    public function hasActiveSubscription(): bool
    {
        return $this->subscription()->exists();
    }

    public function canConnectMoreAccounts(string $platform): bool
    {
        $subscription = $this->subscription;
        if (!$subscription) return false;

        $currentCount = $this->customerAccounts()
            ->whereHas('socialAccount', function($q) use ($platform) {
                $q->where('slug', $platform);
            })->count();

        return $currentCount < 10; // Default limit, should come from subscription plan features
    }

    public function getMonthlyPostsCount(): int
    {
        return $this->posts()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    public function canCreateMorePosts(): bool
    {
        $subscription = $this->subscription;
        if (!$subscription) return false;

        return $this->getMonthlyPostsCount() < $subscription->subscriptionPlan->max_posts_per_month;
    }
}
