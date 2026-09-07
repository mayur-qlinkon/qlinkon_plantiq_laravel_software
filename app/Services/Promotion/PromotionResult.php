<?php

namespace App\Services\Promotion;

use App\Models\Platform\Promotion;

/**
 * Immutable outcome of a promotion check.
 *
 * One predictable shape for every caller (storefront cart, invoice,
 * subscription checkout). Either it's valid (with a monetary discount
 * and/or a non-monetary reward), or it's invalid (with a user-safe error).
 */
final class PromotionResult
{
    public function __construct(
        public readonly bool $valid,
        public readonly ?Promotion $promotion = null,
        public readonly float $discount = 0.0,
        public readonly ?array $reward = null,
        public readonly ?string $error = null,
    ) {}

    public static function success(
        Promotion $promotion,
        float $discount = 0.0,
        ?array $reward = null
    ): self {
        return new self(
            valid: true,
            promotion: $promotion,
            discount: $discount,
            reward: $reward,
        );
    }

    public static function fail(string $error): self
    {
        return new self(valid: false, error: $error);
    }

    /** A non-monetary benefit (free trial / module / limit) was resolved. */
    public function isBenefit(): bool
    {
        return $this->reward !== null;
    }

    /** A monetary discount was computed. */
    public function isMonetary(): bool
    {
        return $this->discount > 0;
    }

    /** JSON-friendly shape for AJAX responses to the checkout/cart frontend. */
    public function toArray(): array
    {
        return [
            'valid'    => $this->valid,
            'error'    => $this->error,
            'code'     => $this->promotion?->code,
            'name'     => $this->promotion?->name,
            'discount' => round($this->discount, 2),
            'reward'   => $this->reward,
        ];
    }
}