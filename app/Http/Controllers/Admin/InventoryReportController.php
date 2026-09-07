<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductBatch;
use App\Models\ProductSku;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryReportController extends Controller
{
    public function index(Request $request)
    {
        $storeIds = active_store_ids(); // 🛡️ Active store only — respects store switch

        // 1. MASTER STOCK: Consolidated view with warehouse breakdown
        // 🛡️ Products are now company-level. Filter via warehouse → store_id
        $masterStock = ProductSku::whereHas('stocks.warehouse', function ($q) use ($storeIds) {
                $q->whereIn('store_id', $storeIds);
            })
            ->with([
                'product.category',
                'product.productUnit',
                'stocks.warehouse.store',
                'skuValues.attributeValue', // 🌟 UX: Eager load variants
            ])
            // 🛡️ Sum only stock from active store's warehouses
            ->withSum(['stocks as total_qty' => function ($q) use ($storeIds) {
                $q->whereHas('warehouse', fn ($w) => $w->whereIn('store_id', $storeIds));
            }], 'qty')
            ->orderByDesc('total_qty')
            ->paginate(15, ['*'], 'stock_page');

        // 2. LOW STOCK ALERTS: The Action List
        // 🛡️ Products are now company-level. Filter via warehouse → store_id
        $lowStockAlerts = ProductSku::whereHas('stocks.warehouse', function ($q) use ($storeIds) {
                $q->whereIn('store_id', $storeIds);
            })
            ->with([
                'product.category',
                'stocks.warehouse.store',
                'skuValues.attributeValue' // 🌟 UX: Eager load variants
            ])
            // 🛡️ Low stock check against ONLY this store's warehouse stock
            ->where(function ($query) use ($storeIds) {
                $query->selectRaw('COALESCE(SUM(ps.qty), 0)')
                    ->from('product_stocks as ps')
                    ->join('warehouses as wh', 'ps.warehouse_id', '=', 'wh.id')
                    ->whereColumn('ps.product_sku_id', 'product_skus.id')
                    ->whereIn('wh.store_id', $storeIds);
            }, '<=', DB::raw('product_skus.stock_alert'))
            ->where('stock_alert', '>', 0)
            ->paginate(15, ['*'], 'alert_page');

        // 3. STOCK MOVEMENT LEDGER: The Audit Trail
        // 🛡️ stock_movements has store_id directly — no join needed
        $movements = StockMovement::with([
            'sku.product',
            'sku.skuValues.attributeValue', // 🌟 UX: Eager load variants
            'warehouse.store',
            'user:id,name',
        ])
            // active_store_ids() returns [] for super admins, meaning "no store
            // restriction" — but whereIn('store_id', []) compiles to 0=1 and
            // returns nothing. Same trap already fixed in QuotationController.
            ->when($storeIds, fn ($q) => $q->whereIn('store_id', $storeIds))
            ->when($request->search_movement, function ($q, $term) {
                $q->whereHas('sku.product', function ($p) use ($term) {
                    $p->where('name', 'like', "%{$term}%");
                })->orWhere('reference_id', 'like', "%{$term}%");
            })
            // Ordered by id, not created_at. This is an insert-only ledger, so
            // id is monotonic and always reflects true insertion order, while
            // created_at can be wrong — clock skew, imports and backfills have
            // all produced rows dated ahead of genuinely newer ones.
            ->latest('id')
            ->paginate(20, ['*'], 'ledger_page');
        
        // Calculate total inventory valuation — filtered by active store via warehouses        
        $totalValuation = DB::table('product_stocks')
            ->join('product_skus', 'product_stocks.product_sku_id', '=', 'product_skus.id')
            ->join('warehouses', 'product_stocks.warehouse_id', '=', 'warehouses.id')
            ->where('product_stocks.company_id', Auth::user()->company_id)
            ->whereIn('warehouses.store_id', $storeIds)
            ->selectRaw('SUM(product_stocks.qty * product_skus.cost) as total')
            ->value('total') ?? 0;

        // 4. BATCH TRACKING REPORT (only when feature is enabled)
        $batchReport = null;
        if (batch_enabled()) {
            $batchReport = ProductBatch::with([
                    'sku.product',
                    'sku.skuValues.attributeValue', // 🌟 UX: Eager load variants
                    'warehouse',
                    'supplier'
                ])
                // 🛡️ Products are company-level; filter via warehouse → store_id
                ->whereHas('warehouse', fn ($q) => $q->whereIn('store_id', $storeIds))
                ->where('remaining_qty', '>', 0)
                ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
                ->orderBy('expiry_date')
                ->paginate(20, ['*'], 'batch_page');
        }

        return view('admin.reports.inventory', compact(
            'masterStock',
            'lowStockAlerts',
            'movements',
            'totalValuation',
            'batchReport'
        ));
    }
    public function ajaxLedger(Request $request)
    {
        $storeIds = active_store_ids(); // 🛡️ Active store only

        $movements = StockMovement::with([
                'sku.product',
                'sku.skuValues.attributeValue',
                'warehouse.store',
                'user:id,name',
            ])
            // See index(): [] means unrestricted, not "match nothing".
            ->when($storeIds, fn ($q) => $q->whereIn('store_id', $storeIds))
            ->when($request->search_movement, function ($q, $term) {
                $q->where(function ($inner) use ($term) {
                    $inner->whereHas('sku.product', fn($p) => $p->where('name', 'like', "%{$term}%"))
                          ->orWhereHas('sku', fn($s) => $s->where('sku', 'like', "%{$term}%"))
                          ->orWhere('reference_id', 'like', "%{$term}%");
                });
            })
            // See index(): ordered by id, the only reliable insertion order.
            ->latest('id')
            ->paginate(20, ['*'], 'ledger_page');

        return view('admin.reports._ledger_rows', compact('movements'));
    }
}
