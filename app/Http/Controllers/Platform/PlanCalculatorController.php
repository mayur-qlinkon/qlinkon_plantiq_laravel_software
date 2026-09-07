<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Platform\Addon;
use App\Models\Platform\PlantProfile;
use App\Models\Platform\ProfileKit;
use App\Services\Platform\PlanBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only pricing calculator for the super admin.
 *
 * Writes nothing. It exists so that a price can be worked out before a client
 * is onboarded — what that client actually receives is still decided on the
 * client screen, which is the only place a plan or subscription is created.
 *
 * This replaces the self-serve checkout that used to drive the same
 * calculation straight into a payment and an auto-provisioned tenant.
 */
class PlanCalculatorController extends Controller
{
    public function __construct(
        protected PlanBuilderService $planBuilder
    ) {}

    public function index(): View
    {
        return view('platform.plan-calculator', [
            'addons'        => Addon::active()->ordered()->get(['id', 'name', 'slug', 'price']),
            'plantProfiles' => PlantProfile::active()->ordered()->get(['id', 'name', 'plant_limit', 'price']),
            'profileKits'   => ProfileKit::active()->ordered()->get(['id', 'name', 'price']),
            'plantSlug'     => PlanBuilderService::PLANT_EDUCATION_SLUG,
        ]);
    }

    /**
     * Recalculate on every change. The figures are never read back from the
     * browser, so there is nothing here a wrong client-side total could break —
     * but keeping the arithmetic server-side means this screen and any future
     * one that charges for real can never drift apart.
     */
    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'addon_ids'        => ['required', 'array', 'min:1'],
            'addon_ids.*'      => ['integer'],
            'plant_profile_id' => ['nullable', 'integer'],
            'profile_kit_id'   => ['nullable', 'integer'],
            'coupon'           => ['nullable', 'string', 'max:50'],
        ]);

        return response()->json($this->planBuilder->quote($validated));
    }
}