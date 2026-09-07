<?php

namespace App\Services\Admin;

use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * AiContextBuilder — Builds a rich context snapshot for every AI request.
 *
 * Collects in one place:
 *   • User identity & role
 *   • Active store + allowed store IDs (uses existing helpers — no new logic)
 *   • All company store names (so AI can resolve "Junagadh store" → store_id)
 *   • Enabled/disabled modules (plan-based, uses has_module())
 *   • Language preference
 *
 * This is READ-ONLY. No writes, no DB mutations.
 * Used by AiChatbotService as the single source of truth per request.
 */
class AiContextBuilder
{
    /**
     * All module slugs that exist in this system.
     * Matches exactly what is used in has_module() calls across blade files.
     */
    private const MODULE_SLUGS = [
        'invoicing', 'pos', 'purchases', 'inventory',
        'crm', 'hrm', 'challan', 'reports',
        'storefront', 'projects', 'inquiry', 'ocr_scanner',
        'production',
    ];

    /**
     * Human-friendly labels shown in AI denial messages.
     * e.g. "CRM & Leads module is not in your plan."
     */
    private const MODULE_LABELS = [
        'invoicing'   => 'Invoices & Quotations',
        'pos'         => 'POS Billing',
        'purchases'   => 'Purchases',
        'inventory'   => 'Inventory & Warehouses',
        'crm'         => 'CRM & Leads',
        'hrm'         => 'HRM & Attendance',
        'challan'     => 'Challans',
        'reports'     => 'Reports',
        'storefront'  => 'Online Storefront',
        'projects'    => 'Projects',
        'inquiry'     => 'Inquiries',
        'ocr_scanner' => 'OCR Scanner',
        'production'  => 'Production & Nursery',
    ];

    /**
     * Build the full AI context for the current authenticated request.
     *
     * @param  string  $language  en|hi|gu|hinglish — from UI selection
     * @return array
     */
    public function build(string $language = 'en'): array
    {
        $user = Auth::user();

        if (! $user) {
            return $this->emptyContext($language);
        }

        return [
            'user'     => $this->buildUserContext($user),
            'store'    => $this->buildStoreContext($user),
            'modules'  => $this->buildModuleContext(),
            'language' => $language,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE BUILDERS
    // ─────────────────────────────────────────────────────────────────────────

    private function buildUserContext($user): array
    {
        // Determine primary role string — used in AI prompt context
        $primaryRole = 'staff';

        if ($user->isSuperAdmin()) {
            $primaryRole = 'super_admin';
        } elseif ($user->isCompanyAdmin()) {
            $primaryRole = 'owner';
        } elseif ($user->relationLoaded('roles') && $user->roles->isNotEmpty()) {
            $primaryRole = $user->roles->first()->slug ?? 'staff';
        }

        return [
            'id'       => $user->id,
            'name'     => $user->name,
            'role'     => $primaryRole,
            'is_owner' => $user->isCompanyAdmin() || $user->isSuperAdmin(),
        ];
    }

    private function buildStoreContext($user): array
    {
        // active_store() — existing helper, resolves from session with auto-heal
        $activeStore = active_store($user);

        // auth_store_ids() — existing helper
        // Returns null  → owner/admin, no restriction (sees all company stores)
        // Returns array → only these store IDs (assigned stores for staff)
        $allowedIds = auth_store_ids();

        // Cache the store list per company for 5 minutes.
        // Store names/IDs rarely change — no need to query on every AI message.
        $companyId = $user->company_id;

        // Cache key strategy:
        //   Owner/admin (allowedIds === null) → shared company-level key (all stores)
        //   Staff (allowedIds = array)        → user-level key (only their stores)
        // This prevents staff User A from seeing Staff User B's stores.
        $cacheKey = is_null($allowedIds)
            ? "ai_store_ctx_{$companyId}_all"
            : "ai_store_ctx_{$companyId}_u{$user->id}";

        $allStores = Cache::remember($cacheKey, 300, function () use ($allowedIds) {
            $query = Store::select('id', 'name', 'city')->where('is_active', true)->orderBy('name');

            // Staff: only load stores they can access — reduces prompt token size.
            // Owner/admin ($allowedIds === null): load all company stores.
            if (is_array($allowedIds) && ! empty($allowedIds)) {
                $query->whereIn('id', $allowedIds);
            }

            return $query->get()
                ->map(fn ($s) => [
                    'id'   => $s->id,
                    'name' => $s->name,
                    'city' => $s->city ?? '',
                ])
                ->toArray();
        });

        return [
            // Currently active/switched store
            'active_store_id'   => $activeStore?->id,
            'active_store_name' => $activeStore?->name,

            // null = no restriction (owner), array = only these IDs (staff)
            'allowed_store_ids' => $allowedIds,

            // Filtered list — AI reads this to map store names → store_id
            'all_stores' => $allStores,
        ];
    }

    private function buildModuleContext(): array
    {
        $enabled  = [];
        $disabled = [];

        foreach (self::MODULE_SLUGS as $slug) {
            $label = self::MODULE_LABELS[$slug] ?? $slug;

            if (has_module($slug)) {
                $enabled[$slug] = $label;
            } else {
                $disabled[$slug] = $label;
            }
        }

        return [
            'enabled'  => $enabled,   // slug => label
            'disabled' => $disabled,  // slug => label
        ];
    }

    private function emptyContext(string $language): array
    {
        return [
            'user'     => ['id' => null, 'name' => 'Guest', 'role' => 'guest', 'is_owner' => false],
            'store'    => ['active_store_id' => null, 'active_store_name' => null, 'allowed_store_ids' => [], 'all_stores' => []],
            'modules'  => ['enabled' => [], 'disabled' => []],
            'language' => $language,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STATIC HELPERS (used by AiChatbotService)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Convert language code to AI instruction string.
     * Used inside system prompts so AI replies in correct language.
     */
    public static function languageInstruction(string $language): string
    {
        return match ($language) {
            'hi'       => 'IMPORTANT: Reply in Hindi only. Use Devanagari script (हिन्दी).',
            'gu'       => 'IMPORTANT: Reply in Gujarati only. Use Gujarati script (ગુજરાતી).',
            'hinglish' => 'IMPORTANT: Reply in Hinglish — Hindi meaning but written in Roman/English script. Example: "Aaj 5 invoices pending hain."',
            default    => 'IMPORTANT: Reply in English only.',
        };
    }

    /**
     * Build a compact store summary string for AI system prompts.
     * Example: "Active store: Junagadh Store (ID:3). Other allowed stores: Rajkot Store (ID:5)."
     */
    public static function storeContextString(array $storeCtx): string
    {
        $lines = [];

        if ($storeCtx['active_store_name']) {
            $lines[] = "User's currently active store: {$storeCtx['active_store_name']} (store_id: {$storeCtx['active_store_id']}).";
        }

        if (! empty($storeCtx['all_stores'])) {
            $storeList = collect($storeCtx['all_stores'])
                ->map(fn ($s) => "{$s['name']} (store_id: {$s['id']})")
                ->join(', ');
            $lines[] = "All available stores: {$storeList}.";
        }

        if ($storeCtx['allowed_store_ids'] === null) {
            $lines[] = "User has access to ALL stores (owner/admin).";
        } elseif (! empty($storeCtx['allowed_store_ids'])) {
            $lines[] = "User can only access store IDs: " . implode(', ', $storeCtx['allowed_store_ids']) . ".";
        }

        return implode(' ', $lines);
    }
}