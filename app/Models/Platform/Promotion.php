<?php

namespace App\Models\Platform;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\Tenantable;
use Carbon\Carbon;

class Promotion extends Model
{
    use HasFactory, Tenantable;

    /**
     * Define Discount Types as Constants for safe, typo-free usage across the app.
     */
    public const TYPE_FIXED = 'fixed';
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FREE_TRIAL = 'free_trial';
    public const TYPE_FREE_MODULE = 'free_module';
    public const TYPE_FREE_LIMIT = 'free_limit';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'is_active',
        'auto_apply',
        'discount_type',
        'discount_value',
        'max_discount',
        'minimum_order_amount',
        'maximum_order_amount',
        'usage_limit',
        'usage_per_customer',
        'times_used',
        'starts_at',
        'expires_at',
        'priority',
        'conditions',
        'reward',
    ];

    /**
     * Get the attributes that should be cast.
     * Using Laravel 11's method-based casting (fallback to $casts property if on Laravel < 11).
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'auto_apply' => 'boolean',
            'discount_value' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'minimum_order_amount' => 'decimal:2',
            'maximum_order_amount' => 'decimal:2',
            'usage_limit' => 'integer',
            'usage_per_customer' => 'integer',
            'times_used' => 'integer',
            'priority' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            // Automatically serializes/deserializes JSON columns
            'conditions' => 'array', 
            'reward' => 'array',     
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PromotionUsage::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes (For DRY Eloquent Queries)
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAutoApply(Builder $query): Builder
    {
        return $query->where('auto_apply', true);
    }

    /**
     * Filters promotions that are currently valid based on dates and usage limits.
     */
    public function scopeValidAt(Builder $query, ?Carbon $date = null): Builder
    {
        $date = $date ?? now();

        return $query->active()
            ->where(function ($q) use ($date) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', $date);
            })
            ->where(function ($q) use ($date) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>=', $date);
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                  ->orWhereColumn('times_used', '<', 'usage_limit');
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Business Logic & Helpers (Write once, use everywhere)
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the promotion is valid right now.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $this->starts_at->isAfter($now)) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->usage_limit !== null && $this->times_used >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Check if a specific cart/invoice amount meets the min/max requirements.
     */
    public function meetsOrderAmountRequirements(float $orderAmount): bool
    {
        if ($this->minimum_order_amount !== null && $orderAmount < (float) $this->minimum_order_amount) {
            return false;
        }

        if ($this->maximum_order_amount !== null && $orderAmount > (float) $this->maximum_order_amount) {
            return false;
        }

        return true;
    }

    /**
     * Standardized discount calculation logic.
     * Use this directly in your Quotation, Purchase, or Invoice modules.
     */
    public function calculateDiscount(float $orderAmount): float
    {
        if (!$this->isValid() || !$this->meetsOrderAmountRequirements($orderAmount)) {
            return 0.00;
        }

        // Benefit-type promotions (free trial / module / limit) are NOT monetary
        // discounts — they're resolved via resolveReward() at provisioning time.
        if (! $this->isMonetaryDiscount()) {
            return 0.00;
        }

        $discount = 0.00;

        if ($this->discount_type === self::TYPE_FIXED) {
            $discount = (float) $this->discount_value;
        } elseif ($this->discount_type === self::TYPE_PERCENTAGE) {
            $discount = $orderAmount * ((float) $this->discount_value / 100);
        }

        // Apply maximum discount cap if it exists
        if ($this->max_discount !== null && $discount > (float) $this->max_discount) {
            $discount = (float) $this->max_discount;
        }

        // Ensure the discount never exceeds the total order amount
        return min($discount, $orderAmount);
    }

    /**
     * Whether this promotion reduces a monetary amount (cart / invoice / subscription price).
     */
    public function isMonetaryDiscount(): bool
    {
        return in_array($this->discount_type, [
            self::TYPE_FIXED,
            self::TYPE_PERCENTAGE,
        ], true);
    }

    /**
     * Whether this promotion grants a non-monetary benefit
     * (free trial days, free module unlock, or a limit bump).
     */
    public function isBenefit(): bool
    {
        return in_array($this->discount_type, [
            self::TYPE_FREE_TRIAL,
            self::TYPE_FREE_MODULE,
            self::TYPE_FREE_LIMIT,
        ], true);
    }

    /**
     * Resolve the non-monetary benefit into a structured, predictable shape that
     * the subscription / checkout layer can consume directly. Reads the `reward`
     * JSON column, with a sensible fallback to `discount_value` for trial days.
     *
     * Returns null for monetary promotions.
     *
     *   free_trial  → ['type' => 'free_trial',  'trial_days'   => int]
     *   free_module → ['type' => 'free_module', 'module_slugs' => string[]]
     *   free_limit  → ['type' => 'free_limit',  'limits'       => array<string,int>]
     */
    public function resolveReward(): ?array
    {
        if (! $this->isBenefit()) {
            return null;
        }

        $reward = $this->reward ?? [];

        return match ($this->discount_type) {
            self::TYPE_FREE_TRIAL => [
                'type'       => self::TYPE_FREE_TRIAL,
                'trial_days' => (int) ($reward['trial_days'] ?? $this->discount_value ?? 0),
            ],
            self::TYPE_FREE_MODULE => [
                'type'         => self::TYPE_FREE_MODULE,
                'module_slugs' => array_values(array_filter((array) ($reward['module_slugs'] ?? []))),
            ],
            self::TYPE_FREE_LIMIT => [
                'type'   => self::TYPE_FREE_LIMIT,
                'limits' => array_map('intval', (array) ($reward['limits'] ?? [])),
            ],
            default => null,
        };
    }

    /**
     * How many times a specific client has already redeemed this promotion.
     */
    public function usageCountForClient(?int $clientId): int
    {
        if (! $clientId) {
            return 0;
        }

        return $this->usages()->where('client_id', $clientId)->count();
    }

    /**
     * Whether a specific client is still allowed to use this promotion
     * (respects the per-customer limit; null limit = unlimited).
     */
    public function clientCanUse(?int $clientId): bool
    {
        if ($this->usage_per_customer === null) {
            return true;
        }

        return $this->usageCountForClient($clientId) < $this->usage_per_customer;
    }

    /**
     * Atomically record a redemption: bump the global counter AND write an
     * audit row. Wrap the caller in a DB transaction for full safety.
     *
     * @param  Model|null  $usable  Order/Invoice/Subscription it applied to
     */
    public function recordUsage(
        ?int $clientId = null,
        float $discountAmount = 0,
        ?Model $usable = null,
        ?int $companyId = null
    ): PromotionUsage {
        // Atomic global counter bump (avoids race conditions under concurrency).
        $this->increment('times_used');

        return $this->usages()->create([
            // The company that REDEEMED the coupon (the tenant), not the coupon's
            // owning company. Falls back to the coupon's own company_id for
            // tenant-scoped coupons where the redeemer isn't passed explicitly.
            'company_id'      => $companyId ?? $this->company_id,
            'client_id'       => $clientId,
            'usable_type'     => $usable?->getMorphClass(),
            'usable_id'       => $usable?->getKey(),
            'discount_amount' => $discountAmount,
        ]);
    }
}