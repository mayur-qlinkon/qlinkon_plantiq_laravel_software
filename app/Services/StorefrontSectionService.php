<?php

namespace App\Services;

use App\Models\Banner;
use App\Models\Category;
use App\Models\StorefrontSection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class StorefrontSectionService
{
    // ════════════════════════════════════════════════════
    //  CREATE
    // ════════════════════════════════════════════════════

    /**
     * Create a new storefront section.
     * sort_order auto-appended via model boot.
     */
    public function create(array $data): StorefrontSection
    {
        return DB::transaction(function () use ($data) {

            $section = StorefrontSection::create($this->prepareData($data));

            Log::info('[StorefrontSection] Created', [
                'id' => $section->id,
                'title' => $section->title,
                'type' => $section->type,
                'company_id' => $section->company_id,
                'by' => Auth::id(),
            ]);

            return $section;
        });
    }

    // ════════════════════════════════════════════════════
    //  UPDATE
    // ════════════════════════════════════════════════════

    /**
     * Update an existing storefront section.
     */
    public function update(StorefrontSection $section, array $data): StorefrontSection
    {
        return DB::transaction(function () use ($section, $data) {

            $old = $section->only(['title', 'type', 'is_active', 'sort_order']);

            $section->update($this->prepareData($data));

            Log::info('[StorefrontSection] Updated', [
                'id' => $section->id,
                'changes' => $section->getChanges(),
                'old' => $old,
                'by' => Auth::id(),
            ]);

            return $section->fresh();
        });
    }

    // ════════════════════════════════════════════════════
    //  DELETE
    // ════════════════════════════════════════════════════

    /**
     * Soft delete a section.
     * Soft delete preserves analytics and audit trail.
     */
    public function delete(StorefrontSection $section): bool
    {
        try {
            $result = $section->delete();

            Log::info('[StorefrontSection] Deleted (soft)', [
                'id' => $section->id,
                'title' => $section->title,
                'by' => Auth::id(),
            ]);

            return (bool) $result;

        } catch (Throwable $e) {
            Log::error('[StorefrontSection] Delete failed', [
                'id' => $section->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ════════════════════════════════════════════════════
    //  TOGGLE ACTIVE
    // ════════════════════════════════════════════════════

    /**
     * Toggle is_active on a section.
     * Returns new state.
     */
    public function toggleActive(StorefrontSection $section): bool
    {
        try {
            $section->update(['is_active' => ! $section->is_active]);

            Log::info('[StorefrontSection] Toggled active', [
                'id' => $section->id,
                'is_active' => $section->is_active,
                'by' => Auth::id(),
            ]);

            return $section->is_active;

        } catch (Throwable $e) {
            Log::error('[StorefrontSection] Toggle failed', [
                'id' => $section->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ════════════════════════════════════════════════════
    //  REORDER
    // ════════════════════════════════════════════════════

    /**
     * Reorder sections via drag-drop.
     * Validates all IDs belong to company before writing.
     */
    public function reorder(int $companyId, array $sectionIds): bool
    {
        if (empty($sectionIds)) {
            return true;
        }

        // Validate all IDs belong to this company
        $validIds = StorefrontSection::forCompany($companyId)
            ->whereIn('id', $sectionIds)
            ->pluck('id')
            ->toArray();

        if (empty($validIds)) {
            Log::warning('[StorefrontSection] Reorder: no valid IDs', [
                'company_id' => $companyId,
                'sent_ids' => $sectionIds,
            ]);

            return false;
        }

        // Only reorder IDs that passed validation
        // Maintains relative order of sent IDs
        $orderedValidIds = array_values(
            array_filter($sectionIds, fn ($id) => in_array($id, $validIds))
        );

        StorefrontSection::reorderForCompany($companyId, $orderedValidIds);

        Log::info('[StorefrontSection] Reordered', [
            'company_id' => $companyId,
            'order' => $orderedValidIds,
            'by' => Auth::id(),
        ]);

        return true;
    }

    // ════════════════════════════════════════════════════
    //  DUPLICATE
    // ════════════════════════════════════════════════════

    /**
     * Duplicate a section.
     * New section appended to end, set inactive by default.
     */
    public function duplicate(StorefrontSection $section): StorefrontSection
    {
        return DB::transaction(function () use ($section) {

            $newSection = $section->replicate(['sort_order', 'view_count', 'click_count', 'created_by', 'updated_by']);
            $newSection->title = 'Copy of '.$section->title;
            $newSection->is_active = false; // Always start inactive — admin must explicitly activate
            $newSection->view_count = 0;
            $newSection->click_count = 0;
            $newSection->created_by = Auth::id();
            $newSection->updated_by = null;

            // Auto sort_order via model boot (append to end)
            unset($newSection->sort_order);
            $newSection->save();

            Log::info('[StorefrontSection] Duplicated', [
                'original_id' => $section->id,
                'new_id' => $newSection->id,
                'by' => Auth::id(),
            ]);

            return $newSection;
        });
    }

    // ════════════════════════════════════════════════════
    //  ADMIN LIST
    // ════════════════════════════════════════════════════

    /**
     * Get all sections for admin index — ordered, with stats.
     */
    public function getAdminList(int $companyId): Collection
    {
        return StorefrontSection::forCompany($companyId)
            ->ordered()
            ->with(['category:id,name,image', 'creator:id,name'])
            ->withTrashed(false) // Only non-deleted
            ->get();
    }

    // ════════════════════════════════════════════════════
    //  STOREFRONT — PUBLIC RESOLUTION
    // ════════════════════════════════════════════════════

    /**
     * Get all live sections for homepage rendering.
     * Each section carries its resolved products.
     * Used in public StorefrontController.
     *
     * Returns collection of sections with ->products already loaded
     * so the blade can just foreach and render.
     */
    public function getLiveSectionsWithProducts(int $companyId): Collection
    {
        $sections = StorefrontSection::getLiveForCompany($companyId);

        // Resolve products per section — each section type handles its own query
        $sections->each(function (StorefrontSection $section) {
            try {
                $section->setRelation('resolved_products', $section->resolveProducts());
            } catch (Throwable $e) {
                // Never crash homepage — log and give empty collection
                Log::error('[StorefrontSection] resolveProducts failed', [
                    'section_id' => $section->id,
                    'type' => $section->type,
                    'error' => $e->getMessage(),
                ]);
                $section->setRelation('resolved_products', collect());
            }
        });

        return $sections;
    }

    /**
     * Get a single live section with its products.
     * Used for partial reloads or API endpoints.
     */
    public function getLiveSectionWithProducts(int $sectionId, int $companyId): ?StorefrontSection
    {
        $section = StorefrontSection::forCompany($companyId)
            ->isLive()
            ->with(['category'])
            ->find($sectionId);

        if (! $section) {
            return null;
        }

        try {
            $section->setRelation('resolved_products', $section->resolveProducts());
        } catch (Throwable $e) {
            Log::error('[StorefrontSection] Single section resolve failed', [
                'section_id' => $sectionId,
                'error' => $e->getMessage(),
            ]);
            $section->setRelation('resolved_products', collect());
        }

        return $section;
    }

    // ════════════════════════════════════════════════════
    //  FORM DATA HELPERS
    // ════════════════════════════════════════════════════

    /**
     * Get all data needed to render create/edit form.
     * Single call from controller — no data logic in controller.
     */
    public function getFormData(int $companyId): array
    {
        return [
            'types' => StorefrontSection::TYPE_LABELS,
            'layouts' => StorefrontSection::LAYOUT_LABELS,
            'categories' => Category::where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'banners' => Banner::where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('title')
                ->get(['id', 'title', 'type']),
            'banner_positions' => [
                'home_top' => 'Home — Top (Hero)',
                'home_middle' => 'Home — Middle',
                'home_bottom' => 'Home — Bottom',
                'category_page' => 'Category Page',
                'product_page' => 'Product Page',
            ],
        ];
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ════════════════════════════════════════════════════

    /**
     * Prepare validated data for create/update.
     * Normalises booleans and nulls consistently.
     * Single source of truth for what goes into the DB.
     */
    private function prepareData(array $data): array
    {
        return [
            // ── Identity ──
            'title' => $data['title'],
            'admin_label' => ! empty($data['admin_label']) ? $data['admin_label'] : null,
            'subtitle' => $data['subtitle'] ?? null,

            // ── Type ──
            'type' => $data['type'],
            'category_id' => $data['category_id'] ?? null,
            'custom_html' => $data['custom_html'] ?? null,
            'banner_position' => $data['type'] === 'banner' ? ($data['banner_position'] ?? null) : null,

            // ── Display ──
            'layout' => $data['layout'] ?? 'grid',
            'products_limit' => $data['products_limit'] ?? 8,
            'columns' => $data['columns'] ?? 4,

            // ── Toggles — explicit bool cast, never null in DB ──
            'show_view_all' => (bool) ($data['show_view_all'] ?? true),
            'show_section_title' => (bool) ($data['show_section_title'] ?? true),
            'show_on_mobile' => (bool) ($data['show_on_mobile'] ?? true),
            'show_on_desktop' => (bool) ($data['show_on_desktop'] ?? true),
            'is_active' => (bool) ($data['is_active'] ?? true),

            // ── Optional visual ──
            'view_all_url' => $data['view_all_url'] ?? null,
            'bg_color' => $data['bg_color'] ?? null,
            'heading_color' => $data['heading_color'] ?? null,

            // ── Scheduling ──
            'starts_at' => ! empty($data['starts_at']) ? $data['starts_at'] : null,
            'ends_at' => ! empty($data['ends_at']) ? $data['ends_at'] : null,

            // ── Sort ──
            // Only include sort_order if explicitly provided
            // Otherwise model boot auto-appends
            ...(isset($data['sort_order']) ? ['sort_order' => (int) $data['sort_order']] : []),
        ];
    }
}
