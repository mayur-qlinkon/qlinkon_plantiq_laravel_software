<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\Admin\StockAdjustmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class StockAdjustmentController extends Controller
{
    public function __construct(
        protected StockAdjustmentService $service
    ) {}

    /**
     * Stock adjustment history listing + quick-adjust modal.
     *
     * Route: GET admin/stock-adjustments
     */
    public function index(Request $request): View
    {
        $storeIds = active_store_ids();

        $movements = StockMovement::query()
            ->where('movement_type', 'adjustment')
            ->whereIn('store_id', $storeIds)
            ->with(['sku.product', 'warehouse', 'user'])
            // id breaks ties on created_at — same-second movements otherwise
            // come back in arbitrary order and pagination becomes unstable.
            // Insert-only ledger — id is the reliable order, not created_at.
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $warehouses = Warehouse::where('is_active', true)
            ->whereIn('store_id', $storeIds)
            ->orderBy('name')
            ->get();

        return view('admin.warehouses.stock-adjustments', compact('movements', 'warehouses'));
    }

    /**
     * Search products/SKUs for the quick-adjust modal (AJAX, type-ahead).
     *
     * Route: GET admin/stock-adjustments/search-skus?q=...
     */
    public function searchSkus(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $skus = ProductSku::query()
            ->with('product:id,name')
            ->where(function ($query) use ($q) {
                $query->where('sku', 'like', "%{$q}%")
                    ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$q}%"));
            })
            ->limit(15)
            ->get()
            ->map(fn ($sku) => [
                'sku_id'       => $sku->id,
                'product_id'   => $sku->product_id,
                'sku_code'     => $sku->sku,
                'product_name' => $sku->product->name ?? 'N/A',
                'label'        => ($sku->product->name ?? 'N/A') . ' — ' . $sku->sku,
            ]);

        return response()->json($skus);
    }

    /**
     * Apply a stock adjustment for one SKU in one warehouse.
     *
     * Route: POST admin/products/{product}/skus/{sku}/adjust-stock
     * Middleware: auth, subscription, module:inventory
     */
    public function store(Request $request, Product $product, ProductSku $sku): JsonResponse
    {
        // ── 1. Validate inputs ──
        $validated = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'type'         => ['required', 'string', 'in:add,remove,set'],
            'qty'          => ['required', 'integer', 'min:0'],
            'reason'       => ['required', 'string', 'in:correction,physical_count,damage,expiry,theft,opening_stock,other'],
            'note'         => ['nullable', 'string', 'max:255'],
        ]);

        // ── 2. Ownership guard: SKU must belong to this product (Tenantable handles company scope) ──
        if ($sku->product_id !== $product->id) {
            return response()->json(['success' => false, 'message' => 'Invalid SKU for this product.'], 403);
        }

        // ── 3. Warehouse guard: must belong to this company (Tenantable global scope already applied) ──
        $warehouse = Warehouse::find($validated['warehouse_id']);
        if (! $warehouse) {
            return response()->json(['success' => false, 'message' => 'Warehouse not found.'], 422);
        }

        // ── 4. Run adjustment ──
        try {
            $result = $this->service->adjust(
                sku:       $sku,
                warehouse: $warehouse,
                type:      $validated['type'],
                qty:       (int) $validated['qty'],
                reason:    $validated['reason'],
                note:      $validated['note'] ?? null,
            );
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        // ── 5. Build a human-readable summary for the toast ──
        if ($result['delta'] === 0) {
            $summary = 'No change — stock already at ' . $result['new_warehouse_qty'] . ' units.';
        } else {
            $arrow   = $result['direction'] === 'in' ? '▲' : '▼';
            $summary = "Stock adjusted {$arrow} " . abs($result['delta']) . ' units. New total: ' . $result['new_total_qty'] . ' units.';
        }

        return response()->json([
            'success'           => true,
            'message'           => $summary,
            'new_total_qty'     => $result['new_total_qty'],
            'new_warehouse_qty' => $result['new_warehouse_qty'],
            'warehouse_id'      => $warehouse->id,
            'sku_id'            => $sku->id,
        ]);
    }
}