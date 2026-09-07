<?php

namespace App\Services\Production;

use App\Enums\Production\BatchSourceType;
use App\Enums\Production\BatchStatus;
use App\Models\Production\BatchAdjustment;
use App\Models\Production\ProductionPlan;
use App\Models\Production\PlantBatch;
use App\Models\Production\ProductionPlanItem;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class PlantBatchService
{
    // ════════════════════════════════════════════════════
    //  CREATE
    // ════════════════════════════════════════════════════

    public function create(array $validated, int $companyId, int $userId): PlantBatch
    {
        $idempotencyKey = $validated['idempotency_key'] ?? null;

        try {
            return DB::transaction(function () use ($validated, $companyId, $userId) {
            $sourceType = BatchSourceType::from($validated['source_type']);

            // Validate source reference if applicable
            if ($sourceType->hasReference() && !empty($validated['source_reference_id'])) {
                $this->validateSourceReference(
                    $sourceType,
                    (int) $validated['source_reference_id'],
                    $companyId
                );
            }

            // Resolve product_sku_id — 'single' products have exactly one SKU
            // and it's never asked on the form, so auto-resolve it here.
            // 'variable' products come with product_sku_id already validated
            // by the request (required_if variable).
            $validated['product_sku_id'] = $validated['product_sku_id']
                ?? $this->resolveDefaultSku((int) $validated['product_id']);

            return PlantBatch::create([
                ...$validated,
                'company_id'       => $companyId,
                'created_by'       => $userId,
                // current_quantity always mirrors initial_quantity on creation
                'current_quantity' => $validated['initial_quantity'],
                // status is never user-supplied on create — always starts active
                'status'           => BatchStatus::Active->value,
            ]);
            });

        } catch (QueryException $e) {
            // 23000 is an integrity constraint violation. When the request
            // carried an idempotency key and a batch already exists for it,
            // this is the second half of a double-submit rather than a
            // failure — hand back the batch the first request created.
            //
            // Looking the row up (instead of parsing the index name out of the
            // driver message) also confirms it was this index that fired.
            if ($idempotencyKey !== null && (string) $e->getCode() === '23000') {
                $existing = PlantBatch::where('company_id', $companyId)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            throw $e;
        }
    }

    // Auto-resolve the SKU for a 'single'-type product (exactly one SKU always
    // exists). Returns null for 'variable' (form must supply it) or 'catalog'
    // (no SKU exists at all).
    private function resolveDefaultSku(int $productId): ?int
    {
        $product = Product::with('skus')->find($productId);

        if (! $product || $product->type !== 'single') {
            return null;
        }

        return $product->skus->first()?->id;
    }

    // ════════════════════════════════════════════════════
    //  UPDATE
    // ════════════════════════════════════════════════════

   public function update(PlantBatch $batch, array $validated): PlantBatch
    {
        return DB::transaction(function () use ($batch, $validated) {
            // product_id and initial_quantity are locked once any reduction has happened.
            if (!$batch->isQuantityIntact()) {
                unset($validated['product_id'], $validated['initial_quantity']);
            } elseif (isset($validated['initial_quantity'])) {
                // No reductions yet — sync current_quantity to match new initial.
                $validated['current_quantity'] = $validated['initial_quantity'];
            }

            // Backfill product_sku_id for batches created before this field
            // existed (e.g. PLB-2026-0002) — 'single' products auto-resolve
            // their one SKU on the very next save, even if the SKU field
            // was hidden on the form because the product isn't variable.
            if (empty($validated['product_sku_id']) && is_null($batch->product_sku_id)) {
                $productId = $validated['product_id'] ?? $batch->product_id;
                $validated['product_sku_id'] = $this->resolveDefaultSku((int) $productId);
            }

            $batch->update($validated);

            return $batch->fresh();
        });
    }

    // ════════════════════════════════════════════════════
    //  STATUS TRANSITION
    // ════════════════════════════════════════════════════

    public function updateStatus(PlantBatch $batch, BatchStatus $newStatus): PlantBatch
    {
        if (!$batch->canTransitionTo($newStatus)) {
            throw new InvalidArgumentException(
                "Cannot transition batch '{$batch->batch_code}' from '{$batch->status}' to '{$newStatus->value}'."
            );
        }

        if ($newStatus === BatchStatus::Cancelled) {
            $this->ensureNoBatchActivity($batch);
        }

        // Reopening is only for a batch closed by mistake. A batch that closed
        // because its last plant was harvested has nothing left to reopen —
        // allowing it would resurrect a finished lifecycle.
        if ($newStatus === BatchStatus::Active && $batch->current_quantity < 1) {
            throw new InvalidArgumentException(
                "Batch '{$batch->batch_code}' holds no plants and cannot be reopened."
            );
        }

        // batch_end_datetime marks real lifecycle end — set when the batch
        // stops being Active, cleared if it's ever reactivated.
        $endDatetime = match ($newStatus) {
            BatchStatus::Closed, BatchStatus::Cancelled => now(),
            BatchStatus::Active                          => null,
        };

        $batch->update([
            'status'               => $newStatus->value,
            'batch_end_datetime'   => $endDatetime,
        ]);

        return $batch->fresh();
    }

    // ════════════════════════════════════════════════════
    //  QUANTITY ADJUST — called by BatchHarvest (record/correct/cancel) and BatchLoss
    // ════════════════════════════════════════════════════

    // This method is a defined contract for future modules (Harvest, Loss, Return, Correction).
    // Do NOT call directly from controllers.
    // delta negative = reduce (harvest reported, loss recorded)
    // delta positive = restore (harvest quantity reduced, harvest cancelled)
    public function adjustQuantity(PlantBatch $batch, int $delta): void
    {
        DB::transaction(function () use ($batch, $delta) {
            // Re-fetch with lock to prevent race conditions
            $batch = PlantBatch::lockForUpdate()->findOrFail($batch->id);

            if ($delta === 0) {
                throw new InvalidArgumentException('Adjustment delta cannot be zero.');
            }

            $newQty = $batch->current_quantity + $delta;

            if ($newQty < 0) {
                throw new RuntimeException(
                    "Cannot adjust by {$delta}. Current quantity is only {$batch->current_quantity}."
                );
            }

            $batch->update([
                'current_quantity' => $newQty,
                'status' => match(true) {
                    $newQty === 0 && $batch->status === BatchStatus::Active->value => BatchStatus::Closed->value,
                    $newQty > 0 && $batch->status === BatchStatus::Closed->value => BatchStatus::Active->value,
                    default => $batch->status,
                },
            ]);
        });
    }

    // ════════════════════════════════════════════════════
    //  ADJUST QUANTITY WITH LEDGER — manual admin correction on the edit
    //  form (currently Opening Stock batches only — those skip the
    //  harvest/loss lifecycle and are edited directly). Reuses
    //  adjustQuantity() for the row-locked delta + auto status flip,
    //  then records a BatchAdjustment row so the change has a permanent,
    //  timestamped audit trail — old/new quantity, note, and who did it.
    //  Not restricted to Opening Stock at this layer — any batch's
    //  current_quantity can be corrected this way if needed later.
    // ════════════════════════════════════════════════════

    public function adjustQuantityWithLedger(PlantBatch $batch, int $newQuantity, ?string $notes, int $userId): PlantBatch
    {
        return DB::transaction(function () use ($batch, $newQuantity, $notes, $userId) {
            $oldQuantity = $batch->current_quantity;
            $delta = $newQuantity - $oldQuantity;

            if ($delta === 0) {
                throw new InvalidArgumentException('New quantity is the same as the current quantity.');
            }

            // Row-locks the batch, validates against negative quantity,
            // auto-flips Active <-> Closed at zero.
            $this->adjustQuantity($batch, $delta);

            BatchAdjustment::create([
                'company_id'     => $batch->company_id,
                'plant_batch_id' => $batch->id,
                'old_quantity'   => $oldQuantity,
                'new_quantity'   => $newQuantity,
                'delta'          => $delta,
                'notes'          => $notes,
                'adjusted_by'    => $userId,
            ]);

            return $batch->fresh();
        });
    }
     // ════════════════════════════════════════════════════
    //  SOURCE SEARCH — recent 5 (no term) or search results (with term)
    //  Non-tech user ko raw ID nahi daalna padta, ye dropdown data deta hai.
    // ════════════════════════════════════════════════════

    public function searchSourceOptions(BatchSourceType $sourceType, int $companyId, ?string $term = null, int $limit = 5): array
    {
        return match ($sourceType) {
            BatchSourceType::ProductionPlan => $this->searchPlanItems($companyId, $term, $limit),
            BatchSourceType::Purchase       => $this->searchPurchaseItems($companyId, $term, $limit),
            default                         => [],
        };
    }

    private function searchPlanItems(int $companyId, ?string $term, int $limit): array
    {
        $items = ProductionPlanItem::with(['plan', 'product'])
            ->whereHas('plan', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->where('status', ProductionPlan::STATUS_CONFIRMED);
            })
            ->when($term, function ($q) use ($term) {
                $q->where(function ($sub) use ($term) {
                    $sub->whereHas('plan', fn ($p) => $p->where('title', 'like', "%{$term}%"))
                        ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$term}%"));
                });
            })
            ->latest('id')
            ->limit($limit)
            ->get();

        return $items->map(fn ($item) => [
            'id'       => $item->id,
            'label'    => ($item->plan->title ?? 'Untitled Plan') . ' — ' . ($item->product->name ?? '—'),
            'sublabel' => 'Qty ' . $item->target_quantity . ($item->target_date ? ' · Target ' . $item->target_date->format('d M, Y') : ''),
        ])->values()->all();
    }

    private function searchPurchaseItems(int $companyId, ?string $term, int $limit): array
    {
        $items = PurchaseItem::with(['purchase', 'product'])
            ->whereHas('purchase', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->whereIn('status', ['received', 'partially_received']);
            })
            ->when($term, function ($q) use ($term) {
                $q->where(function ($sub) use ($term) {
                    $sub->whereHas('purchase', fn ($p) => $p->where('purchase_number', 'like', "%{$term}%"))
                        ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$term}%"));
                });
            })
            ->latest('id')
            ->limit($limit)
            ->get();

        return $items->map(fn ($item) => [
            'id'       => $item->id,
            'label'    => ($item->purchase->purchase_number ?? 'Purchase') . ' — ' . ($item->product->name ?? '—'),
            'sublabel' => 'Qty ' . (int) $item->quantity .
              ' · Received ' . (int) $item->quantity_received,
        ])->values()->all();
    }

    // ════════════════════════════════════════════════════
    //  PLANT SEARCH — recent 4 (no term) or search results (with term)
    //  Used by the Plant Details picker on the batch create/edit form.
    // ════════════════════════════════════════════════════

    public function searchPlantOptions(int $companyId, ?string $term, int $limit = 4): array
    {
        $plants = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('product_type', 'sellable')
            ->with(['skus' => fn ($q) => $q->where('is_active', true)])
            ->when($term, fn ($q) => $q->where('name', 'like', "%{$term}%"))
            ->latest('id')
            ->limit($limit)
            ->get();

        $skuIds = $plants->flatMap(fn ($p) => $p->skus->pluck('id'));

        $stockBySku = ProductStock::whereIn('product_sku_id', $skuIds)
            ->selectRaw('product_sku_id, SUM(qty) as total_qty')
            ->groupBy('product_sku_id')
            ->pluck('total_qty', 'product_sku_id');

        return $plants->map(function ($plant) use ($stockBySku) {
            $stock = $plant->skus->sum(fn ($sku) => (int) ($stockBySku[$sku->id] ?? 0));

            return [
                'id'       => $plant->id,
                'label'    => $plant->name,
                'sublabel' => 'In stock: ' . $stock,
                'stock'    => $stock,
            ];
        })->values()->all();
    }

    

    // ════════════════════════════════════════════════════
    //  PREFILL — for source-assisted batch creation
    // ════════════════════════════════════════════════════

    // Returns an array that the create view uses to pre-populate form fields.
    public function prefillFromSource(BatchSourceType $sourceType, int $sourceReferenceId, int $companyId): array
    {
        return match($sourceType) {
            BatchSourceType::ProductionPlan => $this->prefillFromPlanItem($sourceReferenceId, $companyId),
            BatchSourceType::Purchase       => $this->prefillFromPurchaseItem($sourceReferenceId, $companyId),
            default                         => [],
        };
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ════════════════════════════════════════════════════

    private function validateSourceReference(BatchSourceType $sourceType, int $referenceId, int $companyId): void
    {
        match($sourceType) {
            BatchSourceType::ProductionPlan => $this->validatePlanItemSource($referenceId, $companyId),
            BatchSourceType::Purchase       => $this->validatePurchaseItemSource($referenceId, $companyId),
            default                         => null,
        };
    }

    private function validatePlanItemSource(int $planItemId, int $companyId): void
    {
        $item = ProductionPlanItem::with('plan')
            ->whereHas('plan', fn($q) => $q->where('company_id', $companyId))
            ->findOrFail($planItemId);

        if (!$item->plan->isConfirmed()) {
            throw new InvalidArgumentException(
                'A batch can only be created from a confirmed Production Plan.'
            );
        }
    }

    private function validatePurchaseItemSource(int $purchaseItemId, int $companyId): void
    {
        $item = PurchaseItem::with('purchase')
            ->whereHas('purchase', fn($q) => $q->where('company_id', $companyId))
            ->findOrFail($purchaseItemId);

        if (!in_array($item->purchase->status, ['received', 'partially_received'])) {
            throw new InvalidArgumentException(
                'A batch can only be created from a received or partially received Purchase.'
            );
        }
    }

    private function prefillFromPlanItem(int $planItemId, int $companyId): array
    {
        $item = ProductionPlanItem::with(['plan', 'product'])
            ->whereHas('plan', fn($q) => $q->where('company_id', $companyId))
            ->findOrFail($planItemId);

        return [
            'product_id'           => $item->product_id,
            'product_name'         => $item->product->name,
            'initial_quantity'     => $item->target_quantity,
            // datetime-local only accepts YYYY-MM-DDTHH:MM. toDateTimeString()
            // returns a space-separated value, which the browser discards
            // silently, leaving the field blank on the prefilled form.
            'batch_start_datetime' => now()->format('Y-m-d\TH:i'),
            'source_type'          => BatchSourceType::ProductionPlan->value,
            'source_reference_id'  => $planItemId,
            'source_label'         => "Plan: {$item->plan->title} — {$item->product->name}",
        ];
    }

    private function prefillFromPurchaseItem(int $purchaseItemId, int $companyId): array
    {
        $item = PurchaseItem::with(['purchase', 'product'])
            ->whereHas('purchase', fn($q) => $q->where('company_id', $companyId))
            ->findOrFail($purchaseItemId);

        return [
            'product_id'          => $item->product_id,
            'product_name'        => $item->product->name,
            // Use quantity_received — the batch reflects what physically arrived, not what was ordered.
            'initial_quantity'    => (int) $item->quantity_received,
            // Same datetime-local format requirement as the plan prefill above.
            'batch_start_datetime' => now()->format('Y-m-d\TH:i'),
            'source_type'         => BatchSourceType::Purchase->value,
            'source_reference_id' => $purchaseItemId,
            'source_label'        => "Purchase: {$item->purchase->purchase_number} — {$item->product->name}",
        ];
    }

    private function ensureNoBatchActivity(PlantBatch $batch): void
    {
        // Phase 1: quantity check is sufficient — Loss and Harvest reduce quantity.
        // When those modules are built, add explicit child-record checks here.
        if (!$batch->isQuantityIntact()) {
            throw new InvalidArgumentException(
                "Cannot cancel batch '{$batch->batch_code}' — quantity has already been reduced by a loss or harvest record."
            );
        }
    }
}