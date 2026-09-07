<?php

namespace App\Http\Controllers\Admin\Production;

use App\Enums\Production\HarvestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Production\ReceiveHarvestRequest;
use App\Models\Production\BatchHarvest;
use App\Models\Warehouse;
use App\Services\Production\BatchHarvestService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class HarvestReceivingController extends Controller
{
    public function __construct(
        protected BatchHarvestService $batchHarvestService
    ) {}

    // ════════════════════════════════════════════════════
    //  INDEX — list harvest lots (pending by default)
    //  GET /admin/production/harvest-lots
    // ════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $status = $request->filled('status') ? $request->status : 'all';

        $lots = $this->paginatedLots($status);

        $warehouses = Warehouse::where('company_id', Auth::user()->company_id)->orderBy('name')->get(['id', 'name']);
        $statusTypes = HarvestStatus::cases();
        $kpis = $this->computeKpis();

        return view('admin.production.harvest-lots.index', compact('lots', 'warehouses', 'statusTypes', 'status', 'kpis'));
    }

    // ════════════════════════════════════════════════════
    //  DATA — AJAX JSON endpoint, powers client-side tab switching
    //  and pagination without a full page reload. Shares the exact
    //  same query logic as index() via the private helpers below,
    //  so the two paths can never drift out of sync.
    //  KPIs are ALWAYS recomputed company-wide, independent of the
    //  currently selected status tab or page — this is what index()
    //  originally got wrong when KPIs were derived client-side from
    //  whatever page happened to be loaded.
    //  GET /admin/production/harvest-lots/data
    // ════════════════════════════════════════════════════

    public function data(Request $request): JsonResponse
    {
        $status = $request->filled('status') ? $request->status : 'all';
        $page = $request->integer('page', 1);
        $search = $request->filled('search') ? trim($request->search) : null;

        $lots = $this->paginatedLots($status, $page, $search);

        return response()->json([
            'success' => true,
            'lots' => $lots->items(),
            'pagination' => [
                'current_page' => $lots->currentPage(),
                'last_page' => $lots->lastPage(),
                'total' => $lots->total(),
                'per_page' => $lots->perPage(),
            ],
            'kpis' => $this->computeKpis(),
        ]);
    }

    // Shared query builder for both index() and data() — keeps eager loads,
    // ordering, and pagination size identical across both entry points.
    // Search runs server-side across the FULL filtered dataset, not just the
    // currently loaded page — matches lot id, batch code, product name, or
    // the reporting worker's name.
    private function paginatedLots(string $status, int $page = 1, ?string $search = null)
    {
        return BatchHarvest::with(['batch.product', 'batch.sku', 'harvestedBy', 'receivedBy', 'warehouse'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('production_batch_harvests.id', 'like', "%{$search}%")
                        ->orWhereHas('batch', fn ($b) => $b->where('batch_code', 'like', "%{$search}%"))
                        ->orWhereHas('batch.product', fn ($p) => $p->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('harvestedBy', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                });
            })
            ->latestFirst()
            ->paginate(20, ['*'], 'page', $page);
    }

    // Company-wide KPI aggregates — deliberately NOT filtered by the active
    // status tab, so switching tabs never changes what these numbers mean.
    // Tenantable's global scope already restricts every query to the
    // logged-in user's company — no explicit company_id filter needed here.
    private function computeKpis(): array
    {
        $today = today();

        $pendingCount = BatchHarvest::pending()->count();

        $todayHarvestQty = (int) BatchHarvest::whereDate('harvested_on', $today)->sum('quantity_harvested');

        $todayReceivedQty = (int) BatchHarvest::received()
            ->whereDate('received_at', $today)
            ->sum('received_quantity');

        $oldestPending = BatchHarvest::with(['batch.product', 'harvestedBy'])
            ->pending()
            ->oldest('created_at')
            ->first();

        $oldestHours = 0;
        $oldestLabel = 'No pending lots';

        if ($oldestPending) {
            $oldestHours = (int) $oldestPending->created_at->diffInHours(now());
            $oldestLabel = ($oldestPending->harvestedBy->name ?? 'System')
                . ' • ' . ($oldestPending->batch->product->name ?? 'Unknown');
        }

        return [
            'pendingCount' => $pendingCount,
            'todayHarvestQty' => $todayHarvestQty,
            'todayReceivedQty' => $todayReceivedQty,
            'oldestHours' => $oldestHours,
            'oldestLabel' => $oldestLabel,
        ];
    }



    // Manual harvest entry (create/store/edit/update) was removed: its Blade
    // views never existed, so create/edit were permanent 500s, and nothing in
    // the UI ever posted to store/update. Harvest lots are produced by workers
    // through My Tasks and approved here via receive().

    // ════════════════════════════════════════════════════
    //  SHOW — single lot detail (open the lot to count & approve)
    //  GET /admin/production/harvest-lots/{harvest}
    // ════════════════════════════════════════════════════

    public function show(BatchHarvest $harvest)
    {
        $harvest->load(['batch.product', 'batch.sku', 'harvestedBy', 'receivedBy', 'warehouse']);

        $warehouses = Warehouse::where('company_id', Auth::user()->company_id)->orderBy('name')->get(['id', 'name']);

        return view('admin.production.harvest-lots.show', compact('harvest', 'warehouses'));
    }

    // ════════════════════════════════════════════════════
    //  DOWNLOAD PDF — server-rendered harvest lot detail
    //  GET /admin/production/harvest-lots/{harvest}/pdf
    // ════════════════════════════════════════════════════

    public function downloadPdf(BatchHarvest $harvest)
    {
        $harvest->load(['batch.product', 'batch.sku', 'harvestedBy', 'receivedBy', 'warehouse']);

        $generatedAt = now()->format('d-M-Y h:i A');

        $pdf = Pdf::loadView('admin.production.harvest-lots.pdf', compact('harvest', 'generatedAt'));

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('Harvest-Lot-H-'.$harvest->id.'.pdf');
    }

    // ════════════════════════════════════════════════════
    //  RECEIVE — approve action, pushes stock into inventory
    //  POST /admin/production/harvest-lots/{harvest}/receive
    // ════════════════════════════════════════════════════

    public function receive(ReceiveHarvestRequest $request, BatchHarvest $harvest): JsonResponse
    {
        try {
            $this->batchHarvestService->receive(
                harvest: $harvest,
                receivedQuantity: (int) $request->received_quantity,
                warehouseId: (int) $request->warehouse_id,
                userId: Auth::id(),
            );

            Log::info('[HarvestReceiving] Lot received', [
                'harvest_id' => $harvest->id,
                'received_by' => Auth::id(),
                'received_quantity' => $request->received_quantity,
                'warehouse_id' => $request->warehouse_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Harvest lot received into inventory.',
            ]);

        } catch (RuntimeException $e) {
            // e.g. already received, or batch has no linked SKU
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);

        } catch (Throwable $e) {
            Log::error('[HarvestReceiving] Receive failed', ['harvest_id' => $harvest->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Something went wrong, please try again.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  CORRECT QUANTITY — Admin-only, pending lots only
    //  PATCH /admin/production/harvest-lots/{harvest}/quantity
    // ════════════════════════════════════════════════════

    public function correctQuantity(Request $request, BatchHarvest $harvest): JsonResponse
    {
        $request->validate([
            'quantity_harvested' => ['required', 'integer', 'min:1'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->batchHarvestService->correctQuantity(
                harvest: $harvest,
                newQuantity: (int) $request->quantity_harvested,
                remarks: $request->remarks,
                userId: Auth::id(),
            );

            Log::info('[HarvestReceiving] Quantity corrected', [
                'harvest_id' => $harvest->id,
                'performed_by' => Auth::id(),
                'new_quantity' => $request->quantity_harvested,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Harvest quantity corrected successfully.',
                'redirect' => route('admin.production.harvest-lots.show', $harvest->id),
            ]);
        } catch (RuntimeException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('[HarvestReceiving] Quantity correction failed', ['harvest_id' => $harvest->id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Something went wrong, please try again.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  CANCEL — Admin-only, pending lots only
    //  POST /admin/production/harvest-lots/{harvest}/cancel
    // ════════════════════════════════════════════════════

    public function cancel(Request $request, BatchHarvest $harvest): JsonResponse
    {
        $request->validate([
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->batchHarvestService->cancel(
                harvest: $harvest,
                remarks: $request->remarks,
                userId: Auth::id(),
            );

            Log::info('[HarvestReceiving] Lot cancelled', [
                'harvest_id' => $harvest->id,
                'performed_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Harvest lot cancelled successfully.',
                'redirect' => route('admin.production.harvest-lots.show', $harvest->id),
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('[HarvestReceiving] Cancel failed', ['harvest_id' => $harvest->id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Something went wrong, please try again.'], 500);
        }
    }
}