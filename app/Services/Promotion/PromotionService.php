<?php

namespace App\Services\Promotion;

use App\Exceptions\PromotionException;
use App\Models\Platform\Promotion;
use App\Models\Platform\PromotionUsage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * PromotionService — the single source of truth for validating, applying,
 * and redeeming promotions anywhere in the software.
 *
 * Context-agnostic by design:
 *   $companyId = null  → platform-level promotions  (subscription checkout)
 *   $companyId = X     → that tenant's promotions    (storefront / invoices)
 *
 * Write once, use anywhere:
 *   $result = $promotions->apply('LAUNCH50', $amount, $clientId, $companyId);
 *   if ($result->valid) { $payable = $amount - $result->discount; }
 */
class PromotionService
{
    // ══════════════════════════════════════════════════════════
    //  LOOKUP
    // ══════════════════════════════════════════════════════════

    /**
     * Find a promotion by its coupon code within the given scope.
     * Bypasses the Tenantable global scope so it works in guest/public
     * contexts (where no tenant is resolved) and for platform coupons.
     */
    public function findByCode(string $code, ?int $companyId = null): ?Promotion
    {
        $code = strtoupper(trim($code));

        if ($code === '') {
            return null;
        }

        return Promotion::withoutGlobalScope('tenant')
            ->whereRaw('UPPER(code) = ?', [$code])
            ->where(fn ($q) => $this->scopeCompany($q, $companyId))
            ->first();
    }

    // ══════════════════════════════════════════════════════════
    //  VALIDATE  — all eligibility checks, with granular messages
    // ══════════════════════════════════════════════════════════

    public function validate(
        Promotion $promotion,
        float $amount = 0,
        ?int $clientId = null
    ): PromotionResult {
        $now = now();

        if (! $promotion->is_active) {
            return PromotionResult::fail('This coupon is not available.');
        }

        if ($promotion->starts_at && $promotion->starts_at->isAfter($now)) {
            return PromotionResult::fail('This coupon is not active yet.');
        }

        if ($promotion->expires_at && $promotion->expires_at->isBefore($now)) {
            return PromotionResult::fail('This coupon has expired.');
        }

        if ($promotion->usage_limit !== null
            && $promotion->times_used >= $promotion->usage_limit) {
            return PromotionResult::fail('This coupon has reached its usage limit.');
        }

        if (! $promotion->clientCanUse($clientId)) {
            return PromotionResult::fail('You have already used this coupon.');
        }

        if ($promotion->minimum_order_amount !== null
            && $amount < (float) $promotion->minimum_order_amount) {
            return PromotionResult::fail(
                'A minimum amount of ₹'
                . number_format((float) $promotion->minimum_order_amount, 2)
                . ' is required for this coupon.'
            );
        }

        if ($promotion->maximum_order_amount !== null
            && $amount > (float) $promotion->maximum_order_amount) {
            return PromotionResult::fail('This coupon is not valid for this amount.');
        }

        // Passed every gate → compute the outcome (monetary discount and/or benefit).
        return PromotionResult::success(
            promotion: $promotion,
            discount: $promotion->calculateDiscount($amount),
            reward: $promotion->resolveReward(),
        );
    }

    // ══════════════════════════════════════════════════════════
    //  APPLY  — find by code + validate (the main entry point)
    // ══════════════════════════════════════════════════════════

    public function apply(
        string $code,
        float $amount = 0,
        ?int $clientId = null,
        ?int $companyId = null
    ): PromotionResult {
        $promotion = $this->findByCode($code, $companyId);

        if (! $promotion) {
            return PromotionResult::fail('Invalid coupon code.');
        }

        return $this->validate($promotion, $amount, $clientId);
    }

    // ══════════════════════════════════════════════════════════
    //  AUTO-APPLY  — pick the best valid auto-apply promotion
    // ══════════════════════════════════════════════════════════

    public function bestAutoApply(
        float $amount = 0,
        ?int $clientId = null,
        ?int $companyId = null
    ): PromotionResult {
        $candidates = Promotion::withoutGlobalScope('tenant')
            ->where('is_active', true)
            ->where('auto_apply', true)
            ->where(fn ($q) => $this->scopeCompany($q, $companyId))
            ->get();

        $valid = [];

        foreach ($candidates as $promotion) {
            $result = $this->validate($promotion, $amount, $clientId);
            if ($result->valid) {
                $valid[] = $result;
            }
        }

        if (empty($valid)) {
            return PromotionResult::fail('No applicable offer.');
        }

        // Highest priority wins; ties broken by the larger discount.
        usort($valid, fn ($a, $b) =>
            [$b->promotion->priority, $b->discount]
            <=> [$a->promotion->priority, $a->discount]
        );

        return $valid[0];
    }

    // ══════════════════════════════════════════════════════════
    //  REDEEM  — atomic: re-check under lock, then record usage
    // ══════════════════════════════════════════════════════════

    /**
     * Redeem a promotion. Call this ONLY after payment/order success.
     * Re-validates limits under a row lock to stay safe under concurrency.
     *
     * @throws PromotionException if the promotion is no longer redeemable.
     */
    public function redeem(
        Promotion $promotion,
        float $discount = 0,
        ?int $clientId = null,
        ?Model $usable = null
    ): PromotionUsage {
        return DB::transaction(function () use ($promotion, $discount, $clientId, $usable) {
            // Lock this promotion row to serialise concurrent redemptions.
            $locked = Promotion::withoutGlobalScope('tenant')
                ->whereKey($promotion->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Re-check limits — state may have changed since validation.
            if ($locked->usage_limit !== null
                && $locked->times_used >= $locked->usage_limit) {
                throw new PromotionException('This coupon has reached its usage limit.');
            }

            if (! $locked->clientCanUse($clientId)) {
                throw new PromotionException('You have already used this coupon.');
            }

            return $locked->recordUsage($clientId, $discount, $usable);
        });
    }

    // ══════════════════════════════════════════════════════════
    //  INTERNAL
    // ══════════════════════════════════════════════════════════

    /**
     * Apply the company scope: null → platform-level (company_id IS NULL),
     * otherwise → that specific tenant.
     */
    private function scopeCompany($query, ?int $companyId): void
    {
        $companyId === null
            ? $query->whereNull('company_id')
            : $query->where('company_id', $companyId);
    }
}