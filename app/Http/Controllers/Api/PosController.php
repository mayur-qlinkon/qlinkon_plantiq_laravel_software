<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\Admin\PosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * POS endpoints for the Flutter counter app.
 *
 * Every one of these delegates to PosService — the same service the web POS
 * runs on. Nothing about pricing, GST, stock or invoice numbering is decided
 * here, so a bill rung up on the app and one rung up in a browser cannot come
 * out different.
 *
 * The active store comes from the X-Store-Id header via the store.context
 * middleware, which seeds it exactly where active_store() expects to find it.
 */
class PosController extends Controller
{
    public function __construct(protected PosService $posService) {}

    /**
     * Everything the app needs to render the counter screen, in one call.
     *
     * Deliberately excludes the client list — that can run to thousands of
     * rows on an established tenant and is a poor fit for a payload the app
     * refreshes on every cold start. Clients get their own searchable
     * endpoint below.
     */
    public function bootstrap()
    {
        $store = active_store();

        return response()->json([
            'status' => 'success',
            'data' => [
                'store' => [
                    'id' => $store->id,
                    'name' => $store->name,
                    'address' => $store->address,
                    'phone' => $store->phone,
                    'gstin' => $store->gst_number,
                    'upi_id' => $store->upi_id,
                ],
                'company' => [
                    'name' => Auth::user()->company->name ?? null,
                    'state' => Auth::user()->company->state->name ?? null,
                ],
                'warehouses' => Warehouse::whereIn('store_id', active_store_ids())
                    ->get(['id', 'name', 'is_default'])
                    ->map(fn ($w) => [
                        'id' => $w->id,
                        'name' => $w->name,
                        'is_default' => (bool) $w->is_default,
                    ])->values(),
                'categories' => Category::where('is_active', true)
                    ->orderBy('name')->get(['id', 'name']),
                'units' => Unit::where('is_active', true)->get(['id', 'name']),
                'payment_methods' => PaymentMethod::where('is_active', true)
                    ->orderBy('sort_order')->get(['id', 'name']),
                'permissions' => [
                    'pos.create_quick_product' => has_permission('pos.create_quick_product'),
                ],
            ],
        ]);
    }

    /**
     * Paginated product grid, scoped to one warehouse so the stock figure
     * shown on each tile is the stock the cashier can actually sell.
     */
    public function products(Request $request)
    {
        $data = $this->posService->getGridProducts($request->all(), Auth::user()->company_id);

        return response()->json([
            'status' => 'success',
            'data' => $data['data'],
            'meta' => $data['meta'],
        ]);
    }

    /**
     * Barcode / QR lookup. Returns either one exact hit or a shortlist —
     * the app decides whether to add straight to the cart or show a picker.
     */
    public function scan(Request $request)
    {
        $validated = $request->validate([
            'term' => ['required', 'string', 'max:255'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
        ]);

        $result = $this->posService->scanItem(
            trim($validated['term']),
            (int) $validated['warehouse_id'],
            Auth::user()->company_id
        );

        return response()->json($result);
    }

    /**
     * Searchable, paginated client list for the customer picker.
     */
    public function clients(Request $request)
    {
        $search = trim($request->query('search', ''));
        $perPage = min(50, max(1, (int) $request->query('per_page', 25)));

        $paginator = Client::where('is_active', true)
            ->when($search !== '', fn ($q) => $q->where(function ($sq) use ($search) {
                $sq->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'status' => 'success',
            'data' => $paginator->getCollection()->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'gstin' => $c->gst_number,
                'registration_type' => $c->registration_type,
            ])->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Ring up the sale.
     *
     * The receipt payload is returned inline rather than leaving the app to
     * fetch it: the printer needs it immediately, and a second round trip is
     * exactly the wrong thing to add at a counter on a patchy connection.
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'customer_id' => ['nullable', 'exists:clients,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'amount_received' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_sku_id' => ['required', 'exists:product_skus,id'],
            'items.*.unit_id' => ['required', 'exists:units,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_percent' => ['required', 'numeric', 'min:0'],
            'items.*.tax_type' => ['required', 'in:inclusive,exclusive'],
            'discount_type' => ['nullable', 'in:fixed,percent,percentage'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
        ]);

        $activeStore = active_store();

        if (! $activeStore) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active store selected.',
            ], 403);
        }

        try {
            $result = $this->posService->processCheckout(
                $validated,
                Auth::user()->company_id,
                $activeStore->id
            );

            $invoice = Invoice::findOrFail($result['invoice_id']);

            return response()->json([
                'status' => 'success',
                'message' => 'Transaction completed successfully.',
                'data' => [
                    'invoice_id' => $result['invoice_id'],
                    'share_url' => $result['share_url'],
                    'receipt' => $this->posService->receiptPayload($invoice),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[API POS] Checkout Failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'store_id' => $activeStore->id,
            ]);

            if ($e instanceof InsufficientStockException) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Stock error: '.$e->getMessage(),
                ], 422);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Checkout failed. '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reprint an earlier bill.
     */
    public function receipt(int $id)
    {
        $invoice = Invoice::where('company_id', Auth::user()->company_id)->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $this->posService->receiptPayload($invoice),
        ]);
    }

    /**
     * Recent POS bills for the active store, newest first.
     */
    public function history(Request $request)
    {
        $activeStore = active_store();
        $perPage = min(50, max(1, (int) $request->query('per_page', 20)));

        $paginator = Invoice::with('customer')
            ->where('company_id', Auth::user()->company_id)
            ->where('source', 'pos')
            ->whereNotNull('invoice_number')
            ->when($activeStore, fn ($q) => $q->where('store_id', $activeStore->id))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'status' => 'success',
            'data' => $paginator->getCollection()->map(fn ($invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'date' => $invoice->created_at->format('d M Y, h:i A'),
                'client_name' => $invoice->customer->name ?? $invoice->customer_name ?? 'Guest',
                'grand_total' => (float) $invoice->grand_total,
                'share_url' => route('pos.receipt.public', [
                    $invoice->id,
                    $this->posService->receiptToken($invoice),
                ]),
            ])->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}