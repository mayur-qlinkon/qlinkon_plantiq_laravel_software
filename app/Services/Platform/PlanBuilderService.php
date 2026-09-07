<?php

namespace App\Services\Platform;

use App\Models\Platform\Addon;
use App\Models\Platform\PlantProfile;
use App\Models\Platform\ProfileKit;
use App\Services\Promotion\PromotionService;

/**
 * PlanBuilderService — single source of truth for self-serve onboarding pricing.
 *
 * Pure calculation, no DB writes. Used by the plan-builder popup (live quote)
 * and final order creation (server recomputes; never trusts the client total).
 *
 * Formula:
 *   total = Σ(addons) − coupon
 *
 *   The Plant Education add-on is special: its super-admin DB price is IGNORED
 *   and replaced with a DYNAMIC price computed from the chosen plant profile/kit:
 *       plant_education_price = plant_profile.price + (plant_limit × kit.price)
 *       (e.g. ₹5000 profile + 50 plants × ₹50 = ₹7500)
 *   Every other add-on uses its fixed DB price. plant_limit also drives the
 *   tenant's product limit at provisioning.
 */
class PlanBuilderService
{
    /**
     * The Addon slug that makes Plant Profile and Profile Kit relevant.
     *
     * A constant now: this used to sit in config/plan_builder.php alongside
     * limits and billing settings for the self-serve checkout, and it was the
     * only key left standing once that flow was removed.
     */
    public const PLANT_EDUCATION_SLUG = 'plant_education';

    public function __construct(
        protected PromotionService $promotions
    ) {}

    /**
     * @param  array  $selection  [
     *     'plant_profile_id' => int,
     *     'profile_kit_id'   => int,
     *     'addon_ids'        => int[],
     *     'coupon'           => ?string,
     * ]
     */
    public function quote(array $selection): array
    {
        // ── Add-ons first — at least one is mandatory ──
        $addonIds = array_values(array_unique(array_map('intval', $selection['addon_ids'] ?? [])));
        if (empty($addonIds)) {
            return $this->fail('Please select at least one add-on.');
        }

        $addons = Addon::active()->whereIn('id', $addonIds)->get();
        if ($addons->isEmpty()) {
            return $this->fail('Please select at least one valid add-on.');
        }

        // ── Does the selection include the Plant Education add-on? ──
        // Only then are Plant Profile + Profile Kit relevant/required.
        $plantSlug          = self::PLANT_EDUCATION_SLUG;
        $plantEduSelected   = $addons->contains(fn (Addon $a) => $a->slug === $plantSlug);

        $plantProfile = null;
        $profileKit   = null;
        $kitLine      = 0.00;

        if ($plantEduSelected) {
            $plantProfile = PlantProfile::active()->find($selection['plant_profile_id'] ?? null);
            if (! $plantProfile) {
                return $this->fail('Please select a valid plant profile.');
            }

            $profileKit = ProfileKit::active()->find($selection['profile_kit_id'] ?? null);
            if (! $profileKit) {
                return $this->fail('Please select a valid profile kit.');
            }

            // Kit line = plant limit (qty) × kit price. e.g. 50 plants × ₹50 = ₹2500.
            $kitLine = round((int) $plantProfile->plant_limit * (float) $profileKit->price, 2);
        }

        // Dynamic Plant Education price = profile base price + kit line.
        // (e.g. ₹5000 + ₹2500 = ₹7500). Used to OVERRIDE the add-on's DB price.
        $plantEducationPrice = $plantEduSelected
            ? round((float) $plantProfile->price + $kitLine, 2)
            : 0.00;

        // Build add-on lines. For the Plant Education add-on, swap its fixed
        // DB price for the dynamically computed total above.
        $addonLines = $addons->map(function (Addon $a) use ($plantSlug, $plantEducationPrice) {
            $isPlantEdu = $a->slug === $plantSlug;

            return [
                'id'             => $a->id,
                'name'           => $a->name,
                'price'          => $isPlantEdu
                    ? $plantEducationPrice
                    : round((float) $a->price, 2),
                'is_plant_edu'   => $isPlantEdu,
            ];
        })->values()->all();

        $addonsTotal = round(array_sum(array_column($addonLines, 'price')), 2);

        // Subtotal is now just the sum of all add-on lines (Plant Education
        // already contains profile + kit inside it — no separate kit line).
        $subtotal = $addonsTotal;

        // Coupon (platform-level → companyId = null).
        $discount     = 0.00;
        $couponResult = null;
        $couponError  = null;

        $code = trim((string) ($selection['coupon'] ?? ''));
        if ($code !== '') {
            $couponResult = $this->promotions->apply($code, $subtotal, null, null);
            if ($couponResult->valid) {
                $discount = round($couponResult->discount, 2);
            } else {
                $couponError = $couponResult->error;
            }
        }

        $total = round(max(0, $subtotal - $discount), 2);

        return [
            'valid' => true,
            'lines' => [
                'plant_profile' => $plantProfile ? [
                    'id'          => $plantProfile->id,
                    'name'        => $plantProfile->name,
                    'plant_limit' => (int) $plantProfile->plant_limit,
                    'price'       => round((float) $plantProfile->price, 2),
                ] : null,
                'profile_kit' => $profileKit ? [
                    'id'         => $profileKit->id,
                    'name'       => $profileKit->name,
                    'kit_price'  => round((float) $profileKit->price, 2),
                    'multiplier' => (int) $plantProfile->plant_limit,
                    'price'      => $kitLine,
                ] : null,
                'addons' => $addonLines,
            ],
            'addons_total' => $addonsTotal,
            'subtotal'     => $subtotal,
            'coupon'       => $code !== '' ? [
                'code'     => $code,
                'applied'  => $couponResult?->valid ?? false,
                'discount' => $discount,
                'error'    => $couponError,
            ] : null,
            'discount'         => $discount,
            'total'            => $total,
            'plant_education'  => $plantEduSelected,
        ];
    }

    private function fail(string $message): array
    {
        return ['valid' => false, 'message' => $message];
    }
}