<?php

namespace App\Services\Production;

use App\Models\Production\ProductionPlan;
use App\Models\Production\ProductionPlanItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductionPlanService
{
    // ════════════════════════════════════════════════════
    //  PLAN CRUD
    // ════════════════════════════════════════════════════

    public function create(array $data): ProductionPlan
    {
        return DB::transaction(function () use ($data) {
            $plan = ProductionPlan::create([
                'company_id' => Auth::user()->company_id,
                'created_by' => Auth::id(),
                'title' => $data['title'],
                'purpose' => $data['purpose'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => ProductionPlan::STATUS_DRAFT,
            ]);

            if (! empty($data['items'])) {
                $this->syncItems($plan, $data['items']);
            }

            return $plan->load('items.product');
        });
    }

    public function update(ProductionPlan $plan, array $data): ProductionPlan
    {
        $this->assertEditable($plan);

        return DB::transaction(function () use ($plan, $data) {
            $plan->update([
                'title' => $data['title'],
                'purpose' => $data['purpose'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            if (array_key_exists('items', $data)) {
                $this->syncItems($plan, $data['items']);
            }

            return $plan->fresh(['items.product']);
        });
    }

    public function delete(ProductionPlan $plan): void
    {
        // Confirmed plans can be cancelled but not deleted —
        // they represent committed business intent.
        if ($plan->isConfirmed()) {
            throw new \RuntimeException('A confirmed Production Plan cannot be deleted. Cancel it instead.');
        }

        DB::transaction(fn () => $plan->delete());
    }

    // ════════════════════════════════════════════════════
    //  STATUS TRANSITIONS
    // ════════════════════════════════════════════════════

    public function confirm(ProductionPlan $plan): ProductionPlan
    {
        $this->assertTransition($plan, ProductionPlan::STATUS_CONFIRMED);

        if ($plan->items()->doesntExist()) {
            throw new \RuntimeException('A Production Plan must have at least one item before it can be confirmed.');
        }

        return DB::transaction(function () use ($plan) {
            $plan->update([
                'status' => ProductionPlan::STATUS_CONFIRMED,
                'confirmed_at' => now(),
                'confirmed_by' => Auth::id(),
            ]);

            return $plan->fresh(['items.product']);
        });
    }

    public function close(ProductionPlan $plan): ProductionPlan
    {
        $this->assertTransition($plan, ProductionPlan::STATUS_CLOSED);

        return DB::transaction(function () use ($plan) {
            $plan->update([
                'status' => ProductionPlan::STATUS_CLOSED,
                'closed_at' => now(),
            ]);

            return $plan->fresh();
        });
    }

    public function cancel(ProductionPlan $plan): ProductionPlan
    {
        $this->assertTransition($plan, ProductionPlan::STATUS_CANCELLED);

        return DB::transaction(function () use ($plan) {
            $plan->update(['status' => ProductionPlan::STATUS_CANCELLED]);

            return $plan->fresh();
        });
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ════════════════════════════════════════════════════

    /**
     * Replace all items in one query-efficient operation.
     * Used on create and on full plan update when 'items' key is present.
     */
    private function syncItems(ProductionPlan $plan, array $items): void
    {
        $plan->items()->delete();

        $rows = array_map(fn (array $item, int $index) => [
            'production_plan_id' => $plan->id,
            'product_id' => $item['product_id'],
            'target_quantity' => $item['target_quantity'],
            'target_date' => $item['target_date'],
            'remarks' => $item['remarks'] ?? null,
            'sort_order' => $item['sort_order'] ?? $index,
            'created_at' => now(),
            'updated_at' => now(),
        ], $items, array_keys($items));

        ProductionPlanItem::insert($rows);
    }

    private function assertEditable(ProductionPlan $plan): void
    {
        if (! $plan->isEditable()) {
            throw new \RuntimeException('This Production Plan can no longer be edited. Only Draft plans are editable.');
        }
    }

    private function assertTransition(ProductionPlan $plan, string $newStatus): void
    {
        if (! $plan->canTransitionTo($newStatus)) {
            $from = $plan->status_label;
            $to = ProductionPlan::STATUS_LABELS[$newStatus] ?? $newStatus;

            throw new \RuntimeException("Cannot move a Production Plan from \"{$from}\" to \"{$to}\".");
        }
    }

}