<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySubscription extends Model
{
    protected $fillable = [
        'company_id',
        'plan_id',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * How long a resolved subscription may be served from cache.
     *
     * Only a safety net. Subscription writes invalidate through booted() below,
     * and PlanService / CompanyOnboardingService invalidate every affected
     * company when a plan's modules change. This bounds anything neither of
     * those covers.
     */
    public const CACHE_TTL = 300;

    /** Per-request resolved subscriptions, keyed by company id. */
    protected static array $memo = [];

    /** The one cache key for this company's subscription state. */
    public static function cacheKey(int $companyId): string
    {
        return 'tenant_sub_'.$companyId;
    }

        /**
     * The active subscription for a company, or null.
     *
     * Takes a company id rather than reading Auth because CheckSubscription
     * also runs for storefront visitors, where there is no authenticated user.
     *
     * Two layers: a per-request static array, because has_module() is called
     * twenty-plus times while rendering the sidebar alone and each one would
     * otherwise be a cache driver round-trip; and the cache driver behind it,
     * so the database is rarely touched at all.
     */
    public static function activeFor(int $companyId): ?self
    {
        if (! array_key_exists($companyId, static::$memo)) {
            static::$memo[$companyId] = cache()->remember(
                static::cacheKey($companyId),
                static::CACHE_TTL,
                fn () => static::with('plan.modules')
                    ->where('company_id', $companyId)
                    ->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    })->first()
            );
        }

        return static::$memo[$companyId];
    }

    /**
     * Drop the cached subscription for a company.
     *
     * The static memo is cleared too: a subscription saved partway through a
     * request would otherwise keep serving the pre-save value for the rest of
     * it, which is exactly the kind of disagreement this whole change exists
     * to remove.
     */
    public static function forgetCache(int $companyId): void
    {
        unset(static::$memo[$companyId]);
        cache()->forget(static::cacheKey($companyId));
    }

    /**
     * Invalidate the moment a subscription row changes — created, upgraded,
     * renewed, or deleted. Hooked at the model level rather than scattered
     * across services so no future update path can forget to do it.
     */
    protected static function booted(): void
    {
        static::saved(fn (self $subscription) => static::forgetCache($subscription->company_id));
        static::deleted(fn (self $subscription) => static::forgetCache($subscription->company_id));
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
