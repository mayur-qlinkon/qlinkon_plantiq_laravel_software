<?php

namespace App\Services\Admin;

use App\Models\ProductSku;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockAdjustmentService
{
    /**
     * Apply a manual stock adjustment for a single SKU in a specific warehouse.
     *
     * Writes one row to stock_movements (immutable ledger) and updates
     * product_stocks (live snapshot) — both inside a single DB transaction.
     *
     * @param  ProductSku  $sku
     * @param  Warehouse   $warehouse
     * @param  string      $type     'add' | 'remove' | 'set'
     * @param  int         $qty      Always a positive integer from the form
     * @param  string      $reason   Enum label stored in the movement note
     * @param  string|null $note     Optional free-text note from the user
     * @return array{ new_warehouse_qty: int, new_total_qty: int, delta: int, direction: string }
     *
     * @throws \Exception  When remove would push stock below zero
     */
    public function adjust(
        ProductSku $sku,
        Warehouse $warehouse,
        string $type,
        int $qty,
        string $reason,
        ?string $note = null
    ): array {
        // Eagerly load product so we can fall back to product_unit_id for unit_id
        $sku->loadMissing('product');

        // unit_id is required (non-nullable) in stock_movements
        $unitId = $sku->unit_id ?? $sku->product->product_unit_id;

        // store_id is required (non-nullable) in stock_movements — derive from warehouse
        $storeId = $warehouse->store_id;

        return DB::transaction(function () use ($sku, $warehouse, $type, $qty, $reason, $note, $unitId, $storeId) {

            // ── 1. Lock the current stock row (prevents race conditions on concurrent adjustments) ──
            $stock = ProductStock::lockForUpdate()
                ->where('product_sku_id', $sku->id)
                ->where('warehouse_id', $warehouse->id)
                ->first();

            $currentQty = $stock ? (int) $stock->qty : 0;

            // ── 2. Determine direction and delta ──
            [$delta, $direction] = match ($type) {
                'add'    => [$qty, 'in'],
                'remove' => [-$qty, 'out'],
                'set'    => [
                    $qty - $currentQty,
                    $qty >= $currentQty ? 'in' : 'out',
                ],
            };

            // ── 3. Guard: never allow negative stock ──
            $newWarehouseQty = $currentQty + $delta;
            if ($newWarehouseQty < 0) {
                throw new \Exception(
                    "Cannot remove {$qty} units from {$warehouse->name}. Only {$currentQty} available."
                );
            }

            // ── 4. No-op guard: set to same value, nothing to write ──
            if ($delta === 0) {
                return [
                    'new_warehouse_qty' => $currentQty,
                    'new_total_qty'     => (int) $sku->stocks()->sum('qty'),
                    'delta'             => 0,
                    'direction'         => 'in',
                ];
            }

            // ── 5. Update the live snapshot (product_stocks) ──
            if ($stock) {
                $stock->update(['qty' => $newWarehouseQty]);
            } else {
                ProductStock::create([
                    'company_id'     => $sku->company_id,
                    'store_id'       => $storeId,
                    'product_sku_id' => $sku->id,
                    'warehouse_id'   => $warehouse->id,
                    'qty'            => $newWarehouseQty,
                ]);
            }

            // ── 6. Append to the immutable ledger (stock_movements) ──
            $noteText = '[' . $reason . ']' . ($note ? ' ' . $note : '');

            StockMovement::create([
                'company_id'     => $sku->company_id,
                'store_id'       => $storeId,
                'product_sku_id' => $sku->id,
                'warehouse_id'   => $warehouse->id,
                'unit_id'        => $unitId,
                'direction'      => $direction,
                'quantity'       => abs($delta),
                'balance_after'  => $newWarehouseQty,
                'movement_type'  => 'adjustment',
                'user_id'        => Auth::id(),
                'note'           => $noteText,
            ]);

            // ── 7. Return fresh total for the UI update ──
            $newTotalQty = (int) $sku->stocks()->sum('qty');

            return [
                'new_warehouse_qty' => $newWarehouseQty,
                'new_total_qty'     => $newTotalQty,
                'delta'             => $delta,
                'direction'         => $direction,
            ];
        });
    }
}