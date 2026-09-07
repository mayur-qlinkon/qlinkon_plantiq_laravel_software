<?php

namespace App\Services\Production;

use App\Models\Production\ActivityTemplate;
use App\Models\Production\PlantBatch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ActivityTemplateService
{
    /**
     * @throws \RuntimeException Whenever an active template with the same activity_type exists
     */
    public function create(array $data): ActivityTemplate
    {
        $data['company_id'] = $data['company_id'] ?? Auth::user()->company_id;
        $data['created_by'] = $data['created_by'] ?? Auth::id();

        $this->assertNotDuplicate(
            $data['company_id'],
            $data['product_id'] ?? null,
            $data['activity_type']
        );

        return DB::transaction(fn () => ActivityTemplate::create($data));
    }

    public function update(ActivityTemplate $template, array $data): ActivityTemplate
    {
        $productId = array_key_exists('product_id', $data) ? $data['product_id'] : $template->product_id;
        $activityType = $data['activity_type'] ?? $template->activity_type->value;

        $this->assertNotDuplicate(
            $template->company_id, 
            $productId, 
            $activityType, 
            excludeId: $template->id
        );

        return DB::transaction(function () use ($template, $data) {
            $template->update($data);

            return $template->fresh();
        });
    }

    public function delete(ActivityTemplate $template): void
    {
        $template->delete();
    }

    /**
     * Phase 3 (Daily Task Generation):
     * Merges Global Default templates with species-specific templates for a batch.
     * Global Default applies to every batch as the baseline; a species-specific
     * template for the same activity_type overrides (replaces) the global one —
     * it does NOT stack into a duplicate task. Global activity_types with no
     * species-specific counterpart still apply as-is.
     */
    public function resolveForBatch(PlantBatch $batch): Collection
    {
        $globalTemplates = ActivityTemplate::where('company_id', $batch->company_id)
            ->globalDefault()
            ->active()
            ->ordered()
            ->get();

        $speciesTemplates = ActivityTemplate::where('company_id', $batch->company_id)
            ->forProduct($batch->product_id)
            ->active()
            ->ordered()
            ->get();

        // Merge keyed by activity_type — species-specific wins on collision.
        // Global templates load first, then species overwrite the same keys.
        return $globalTemplates
            ->keyBy(fn (ActivityTemplate $t) => $t->activity_type->value)
            ->merge($speciesTemplates->keyBy(fn (ActivityTemplate $t) => $t->activity_type->value))
            ->values();
    }

    // ════════════════════════════════════════════════════
    //  GUARDS
    // ════════════════════════════════════════════════════

    protected function assertNotDuplicate(int $companyId, ?int $productId, string $activityType, ?int $excludeId = null): void
    {
        $exists = ActivityTemplate::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('product_id', $productId)
            ->where('activity_type', $activityType)
            ->where('is_active', true)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();

        if ($exists) {
            $scope = $productId ? 'is species' : 'Global Default';
            throw new \RuntimeException("{$scope} ke liye ye activity pehle se hi template mein hai.");
        }
    }
}