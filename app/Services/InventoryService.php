<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\ProductBatch;
use App\Models\ProductSku;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    // ──────────────────────────────────────────────────────────────
    // PUBLIC API — These are the only methods you call from outside
    // ──────────────────────────────────────────────────────────────

    /**
     * Add stock to a warehouse.
     * Used by: Purchase receive, Purchase return receive, Stock adjustment (up)
     *
     * @throws \InvalidArgumentException
     */
    public function addStock(
        ProductSku $sku,
        int $warehouseId,
        float $qty,
        string $movementType,
        mixed $reference = null,
        ?int $batchId = null,
        ?string $batchNumber = null,
        ?float $unitCost = null
    ): void {
        if ($qty <= 0) {
            throw new \InvalidArgumentException("addStock qty must be > 0. Got: {$qty}");
        }

        $this->guardWarehouseBelongsToCompany($warehouseId, $sku->company_id);

        DB::transaction(function () use (
            $sku,
            $warehouseId,
            $qty,
            $movementType,
            $reference,
            $batchId,
            $batchNumber,
            $unitCost
        ) {
            $this->_add(
                $sku,
                $warehouseId,
                $qty,
                $movementType,
                $reference,
                $batchId,
                $batchNumber,
                $unitCost
            );
        });

    }

    /**
     * Deduct stock from a warehouse.
     * When batch_enabled(), uses FEFO (First-Expiry-First-Out) to auto-select
     * batches and decrement their remaining_qty.
     * Used by: Sale, POS, Transfer out, Purchase return to supplier
     *
     * @throws InsufficientStockException
     * @throws \InvalidArgumentException
     */
    public function deductStock(
        ProductSku $sku,
        int $warehouseId,
        float $qty,
        string $movementType,
        mixed $reference = null,
        ?int $batchId = null,
        ?string $batchNumber = null,
        ?float $unitCost = null
    ): void {
        if ($qty <= 0) {
            throw new \InvalidArgumentException("deductStock qty must be > 0. Got: {$qty}");
        }

        $this->guardWarehouseBelongsToCompany($warehouseId, $sku->company_id);

        DB::transaction(function () use ($sku, $warehouseId, $qty, $movementType, $reference, $batchId, $batchNumber, $unitCost) {
            if (batch_enabled()) {
                $this->_deductWithBatches($sku, $warehouseId, $qty, $movementType, $reference, $batchId, $batchNumber, $unitCost);
            } else {
                $this->_deduct($sku, $warehouseId, $qty, $movementType, $reference, $unitCost);
            }
        });
    }

    /**
     * Set stock to a specific quantity (manual adjustment).
     * Used by: Stock count / physical audit
     * Calculates difference and creates a +/- movement automatically.
     */
    public function adjustStock(
        ProductSku $sku,
        int $warehouseId,
        float $newQty,
        mixed $reference = null
    ): void {
        if ($newQty < 0) {
            throw new \InvalidArgumentException("adjustStock newQty cannot be negative. Got: {$newQty}");
        }

        $this->guardWarehouseBelongsToCompany($warehouseId, $sku->company_id);

        DB::transaction(function () use ($sku, $warehouseId, $newQty, $reference) {

            $stock = ProductStock::lockForUpdate()->firstOrCreate(
                [
                    'company_id' => $sku->company_id,
                    'product_sku_id' => $sku->id,
                    'warehouse_id' => $warehouseId,
                ],
                ['qty' => 0]
            );

            $difference = $newQty - $stock->qty;

            if ($difference == 0) {
                return; // Nothing changed — no movement needed
            }

            $stock->update(['qty' => $newQty]);

            StockMovement::create([
                'company_id' => $sku->company_id,
                'product_sku_id' => $sku->id,
                'warehouse_id' => $warehouseId,
                'batch_id' => null,
                'quantity' => $difference, // Positive or negative
                'movement_type' => 'adjustment',
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->id,
                'balance_after' => $stock->refresh()->qty,
            ]);
        });
    }

    /**
     * Transfer stock between two warehouses atomically.
     * Used by: Warehouse transfer module
     * Both deduct + add happen in ONE transaction — no partial state possible.
     *
     * @throws InsufficientStockException
     */
    public function transferStock(
        ProductSku $sku,
        int $fromWarehouseId,
        int $toWarehouseId,
        float $qty,
        mixed $reference = null
    ): void {
        if ($qty <= 0) {
            throw new \InvalidArgumentException("transferStock qty must be > 0. Got: {$qty}");
        }

        if ($fromWarehouseId === $toWarehouseId) {
            throw new \InvalidArgumentException('Source and destination warehouse cannot be the same.');
        }

        $this->guardWarehouseBelongsToCompany($fromWarehouseId, $sku->company_id);
        $this->guardWarehouseBelongsToCompany($toWarehouseId, $sku->company_id);

        // Single top-level transaction — private methods have NO nested transactions
        DB::transaction(function () use ($sku, $fromWarehouseId, $toWarehouseId, $qty, $reference) {
            $this->_deduct($sku, $fromWarehouseId, $qty, 'transfer_out', $reference);
            $this->_add($sku, $toWarehouseId, $qty, 'transfer_in', $reference);
        });
    }

    /**
     * Set opening stock when a new product/SKU is created.
     * Creates 'opening' movements — distinct from adjustments in reports.
     *
     * @param  array  $stockData  [['warehouse_id' => 1, 'qty' => 100], ...]
     */
    public function setOpeningStock(ProductSku $sku, array $stockData): void
    {
        foreach ($stockData as $row) {
            $qty = (float) ($row['qty'] ?? 0);

            if ($qty <= 0) {
                continue;
            }

            $this->guardWarehouseBelongsToCompany($row['warehouse_id'], $sku->company_id);

            // 'opening_stock', not 'adjustment' — an adjustment means a count
            // was corrected, which is a different thing in the audit report.
            // The string must match what ProductService writes, or opening
            // stock ends up split across two names no report catches both of.
            $this->addStock(
                sku: $sku,
                warehouseId: $row['warehouse_id'],
                qty: $qty,
                movementType: 'opening_stock',
                reference: null,
            );
        }
    }

    // ──────────────────────────────────────────────────────────────
    // READ HELPERS
    // ──────────────────────────────────────────────────────────────

    /**
     * Total stock of a SKU across ALL warehouses.
     */
    public function getStock(ProductSku $sku): float
    {
        return (float) ProductStock::where('product_sku_id', $sku->id)->sum('qty');
    }

    /**
     * Stock of a SKU in a specific warehouse.
     */
    public function getWarehouseStock(ProductSku $sku, int $warehouseId): float
    {
        return (float) ProductStock::where('product_sku_id', $sku->id)
            ->where('warehouse_id', $warehouseId)
            ->value('qty') ?? 0.0;
    }

    /**
     * Stock of a SKU across all warehouses, broken down per warehouse.
     * Useful for: product detail page, transfer form
     *
     * @return Collection [warehouse_id => qty]
     */
    public function getStockBreakdown(ProductSku $sku): Collection
    {
        return ProductStock::where('product_sku_id', $sku->id)
            ->with('warehouse:id,name')
            ->get()
            ->mapWithKeys(fn ($s) => [
                $s->warehouse_id => [
                    'warehouse' => $s->warehouse->name,
                    'qty' => (float) $s->qty,
                ],
            ]);
    }

    /**
     * Is stock available for a deduction?
     * Use this to validate BEFORE attempting deductStock — e.g. in sale form.
     */
    public function hasStock(ProductSku $sku, int $warehouseId, float $qty): bool
    {
        return $this->getWarehouseStock($sku, $warehouseId) >= $qty;
    }

    // ──────────────────────────────────────────────────────────────
    // PRIVATE CORE — No DB::transaction() here. Called by public methods.
    // ──────────────────────────────────────────────────────────────

    /**
     * Core add logic — no transaction wrapper (caller owns the transaction).
     */
    private function _add(
        ProductSku $sku,
        int $warehouseId,
        float $qty,
        string $movementType,
        mixed $reference,
        ?int $batchId = null,
        ?string $batchNumber = null,
        ?float $unitCost = null
    ): void {
        $stock = ProductStock::lockForUpdate()->firstOrCreate(
            [
                'company_id' => $sku->company_id,
                'product_sku_id' => $sku->id,
                'warehouse_id' => $warehouseId,
            ],
            ['qty' => 0]
        );

        $stock->increment('qty', $qty);

        // store_id is derived from the warehouse by the model. Auth::user()
        // ->store_id is not a column on users, so that first clause was always
        // null and the fallback behind it was doing all the work.
        StockMovement::create([
            'company_id' => $sku->company_id,
            'product_sku_id' => $sku->id,
            'warehouse_id' => $warehouseId,
            'unit_id' => $sku->product->product_unit_id,
            'batch_id' => $batchId ?? null,
            'batch_number' => $batchNumber,
            'unit_cost' => $unitCost ?? $sku->cost, // 🌟 NEW: Critical for valuation
            'direction' => 'in', // 🌟 NEW
            'user_id' => Auth::id(), // 🌟 NEW: The Audit Trail
            'quantity' => $qty,
            'movement_type' => $movementType,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
            'balance_after' => $stock->refresh()->qty,
        ]);
    }

    /**
     * Core deduct logic — no transaction wrapper (caller owns the transaction).
     *
     * @throws InsufficientStockException
     */
    private function _deduct(
        ProductSku $sku,
        int $warehouseId,
        float $qty,
        string $movementType,
        mixed $reference,
        ?float $unitCost = null // 🌟 NEW: For exact COGS tracking
    ): void {
        $stock = ProductStock::lockForUpdate()
            ->where('company_id', $sku->company_id)
            ->where('product_sku_id', $sku->id)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (! $stock || $stock->qty < $qty) {
            throw new InsufficientStockException(
                sku: $sku,
                available: (float) ($stock?->qty ?? 0),
                requested: $qty
            );
        }

        $stock->decrement('qty', $qty);

        StockMovement::create([
            'company_id' => $sku->company_id,
            'product_sku_id' => $sku->id,
            'warehouse_id' => $warehouseId,
            'unit_id' => $sku->product->product_unit_id,
            'unit_cost' => $unitCost ?? $sku->cost, // Locks in the Cost of Goods Sold
            'direction' => 'out', // 🌟 NEW
            'user_id' => Auth::id(), // 🌟 NEW
            'quantity' => -$qty,
            'movement_type' => $movementType,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
            'balance_after' => $stock->refresh()->qty,
        ]);
    }

    /**
     * FEFO batch deduction: deducts from product_stocks total, then reduces
     * remaining_qty on the earliest-expiring batches until qty is fulfilled.
     * Creates one StockMovement per batch slice consumed.
     *
     * Falls back to a movement with no batch_id if batches don't cover all qty
     * (graceful degradation when batch data is incomplete).
     *
     * @throws InsufficientStockException
     */
    private function _deductWithBatches(
        ProductSku $sku,
        int $warehouseId,
        float $qty,
        string $movementType,
        mixed $reference,
        ?int $batchId = null,
        ?string $batchNumber = null,
        ?float $unitCost = null
    ): void {
        // 1. Lock and validate total product stock
        $stock = ProductStock::lockForUpdate()
            ->where('company_id', $sku->company_id)
            ->where('product_sku_id', $sku->id)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (! $stock || $stock->qty < $qty) {
            throw new InsufficientStockException(
                sku: $sku,
                available: (float) ($stock?->qty ?? 0),
                requested: $qty
            );
        }

        $stock->decrement('qty', $qty);
        $balanceAfter = $stock->refresh()->qty;

        // 2. If a specific batch is explicitly given (e.g. future batch-select UI)
        if ($batchId) {
            $batch = ProductBatch::lockForUpdate()
                ->where('id', $batchId)
                ->where('company_id', $sku->company_id)
                ->first();

            if ($batch && $batch->remaining_qty > 0) {
                $batch->decrement('remaining_qty', min($qty, (float) $batch->remaining_qty));
            }

            $this->_createOutMovement($sku, $warehouseId, $qty, $movementType, $reference, $batchId, $batchNumber, $unitCost ?? $sku->cost, $balanceAfter);

            return;
        }

        // 3. FEFO auto-selection: expiry date ASC, then created_at ASC (FIFO fallback)
        $batches = ProductBatch::lockForUpdate()
            ->where('company_id', $sku->company_id)
            ->where('product_sku_id', $sku->id)
            ->where('warehouse_id', $warehouseId)
            ->where('remaining_qty', '>', 0)
            ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expiry_date')
            ->orderBy('created_at')
            ->get();

        $remaining = $qty;

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $deductFromBatch = min($remaining, (float) $batch->remaining_qty);
            $batch->decrement('remaining_qty', $deductFromBatch);

            $this->_createOutMovement(
                $sku, $warehouseId, $deductFromBatch, $movementType, $reference,
                $batch->id, $batch->batch_number,
                $unitCost ?? (float) $batch->purchase_price ?? $sku->cost,
                $balanceAfter
            );

            $remaining -= $deductFromBatch;
        }

        // 4. Fallback: if batches didn't cover everything (missing batch data),
        //    create a movement without batch info so stock stays consistent.
        if ($remaining > 0) {
            $this->_createOutMovement(
                $sku, $warehouseId, $remaining, $movementType, $reference,
                null, null, $unitCost ?? $sku->cost, $balanceAfter
            );
        }
    }

    /**
     * Shared helper to create a single 'out' StockMovement record.
     */
    private function _createOutMovement(
        ProductSku $sku,
        int $warehouseId,
        float $qty,
        string $movementType,
        mixed $reference,
        ?int $batchId,
        ?string $batchNumber,
        float $unitCost,
        float $balanceAfter
    ): void {
        StockMovement::create([
            'company_id' => $sku->company_id,
            'product_sku_id' => $sku->id,
            'warehouse_id' => $warehouseId,
            'unit_id' => $sku->product->product_unit_id,
            'batch_id' => $batchId,
            'batch_number' => $batchNumber,
            'unit_cost' => $unitCost,
            'direction' => 'out',
            'user_id' => Auth::id(),
            'quantity' => -$qty,
            'movement_type' => $movementType,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
            'balance_after' => $balanceAfter,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // GUARDS
    // ──────────────────────────────────────────────────────────────

    /**
     * Prevent cross-company stock writes.
     * Critical in multi-tenant SaaS — never skip this.
     *
     * @throws \InvalidArgumentException
     */
    private function guardWarehouseBelongsToCompany(int $warehouseId, int $companyId): void
    {
        $exists = Warehouse::where('id', $warehouseId)
            ->where('company_id', $companyId)
            ->exists();

        if (! $exists) {
            throw new \InvalidArgumentException(
                "Warehouse [{$warehouseId}] does not belong to company [{$companyId}]. Possible data leak."
            );
        }
    }
}
