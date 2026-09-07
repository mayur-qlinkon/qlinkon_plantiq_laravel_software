<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\State;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\Admin\PosService;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\PosCheckoutException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class PosController extends Controller
{
    protected PosService $posService;

    public function __construct(PosService $posService)
    {
        $this->posService = $posService;
    }

    public function index()
    {
        $activeStore = active_store();

        if (! $activeStore) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Please select a Store Branch to access the POS.');
        }

        $categories = Category::where('is_active', true)->get();
        $clients = Client::where('is_active', true)->get();
        $units = Unit::where('is_active', true)->get();
        $states = State::where('is_active', true)->orderBy('name')->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();
        
        // Fetch all assigned stores for the dropdown
        $stores = auth_stores()->orderBy('name')->get();

        // Determine if the switcher should be visible
        $canSwitchStore = $stores->count() > 1 && has_permission('stores.switch');
        
        $warehouses = Warehouse::whereIn('store_id', active_store_ids())->get();
        $defaultClient = Client::where('name', 'Walk-in Customer')->first();
        $companyState = Auth::user()->company->state->name ?? 'Unknown';

        return view('admin.pos.index', compact(
            'categories','canSwitchStore','stores', 'warehouses', 'paymentMethods', 'defaultClient',
            'companyState', 'states', 'clients', 'units'
        ));
    }

    public function fetchProducts(Request $request)
    {
        $data = $this->posService->getGridProducts($request->all(), Auth::user()->company_id);
        
        return response()->json([
            'status' => 'success',
            'data' => $data['data'],
            'meta' => $data['meta'],
        ]);
    }

    public function scanItem(Request $request)
    {
        $term = trim($request->input('term', ''));
        $warehouseId = (int) $request->input('warehouse_id');

        if (empty($term) || ! $warehouseId) {
            return response()->json(['status' => 'error', 'message' => 'Invalid scan data.']);
        }

        $result = $this->posService->scanItem($term, $warehouseId, Auth::user()->company_id);

        return response()->json($result);
    }

    public function storeQuickProduct(Request $request)
    {
        // Same gate ProductController@store enforces — this is a second,
        // separate entry point into product creation and was bypassing it
        // entirely, letting POS's Quick Add modal create unlimited products
        // regardless of the tenant's plan.
        if (! check_plan_limit('products')) {
            return response()->json([
                'status' => 'error',
                'message' => "You have reached your plan's Product limit. Please upgrade your subscription to add more products.",
            ], 422);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
            'price' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'tax_percent' => 'nullable|numeric|min:0',
            'tax_type' => 'required|in:inclusive,exclusive',
            'sku' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'hsn_code' => 'nullable|string|max:20',
            'opening_stock' => 'nullable|numeric|min:0',
            'warehouse_id' => 'required|exists:warehouses,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        try {
            $skuData = $this->posService->createQuickProduct(
                $validated, 
                Auth::user()->company_id, 
                $request->file('image')
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Product created and ready for sale!',
                'data' => $skuData,
            ]);
        } catch (\Exception $e) {
            if ($e->getCode() == 23000) {
                return response()->json(['status' => 'error', 'message' => 'This SKU already exists in your company.'], 422);
            }
            return response()->json(['status' => 'error', 'message' => 'Failed to create product. '.$e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        // Laravel's `exists` rule runs a raw query and does NOT apply the
        // Tenantable global scope, so an unscoped rule accepts another
        // company's IDs. Every foreign-key rule here is scoped explicitly.
        $companyId = Auth::user()->company_id;

        $validated = $request->validate([
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', $companyId)],
            'customer_id' => ['nullable', Rule::exists('clients', 'id')->where('company_id', $companyId)],
            'customer_name' => 'nullable|string|max:255',
            'payment_method_id' => ['nullable', Rule::exists('payment_methods', 'id')->where('company_id', $companyId)],
            'amount_received' => 'nullable|numeric|min:0',
            'idempotency_key' => 'nullable|uuid',
            'items' => 'required|array|min:1',
            'items.*.product_sku_id' => ['required', Rule::exists('product_skus', 'id')->where('company_id', $companyId)],
            'items.*.unit_id' => ['required', Rule::exists('units', 'id')->where('company_id', $companyId)],
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_percent' => 'required|numeric|min:0',
            'items.*.tax_type' => 'required|in:inclusive,exclusive',
            'discount_type' => 'nullable|in:fixed,percent,percentage',
            'discount_value' => 'nullable|numeric|min:0',
        ]);

        $activeStore = active_store();
        if (! $activeStore) {
            return response()->json(['status' => 'error', 'message' => 'No active store selected.'], 403);
        }

        try {
            $result = $this->posService->processCheckout($validated, $companyId, $activeStore->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Transaction completed successfully.',
                'invoice_id' => $result['invoice_id'],
                'share_url' => $result['share_url'],
            ]);
        } catch (\Throwable $e) {
            Log::error('POS Checkout Failed', ['error' => $e->getMessage(), 'payload' => $request->except(['_token'])]);

            if ($e instanceof InsufficientStockException) {
                return response()->json(['status' => 'error', 'message' => 'Stock error: ' . $e->getMessage()], 422);
            }

            // Written for the cashier, safe to show.
            if ($e instanceof PosCheckoutException) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'products' => $e->products,
                ], 422);
            }

            // Anything else is internal. The message goes to the log, not the
            // browser — it used to leak things like warehouse/company IDs.
            return response()->json([
                'status' => 'error',
                'message' => 'Checkout could not be completed. Please try again, or contact support if this keeps happening.',
            ], 500);
        }
    }

    public function receipt($id)
    {
        $companyId = Auth::user()->company_id;
        $relations = ['items', 'customer', 'store', 'creator', 'company', 'payments.paymentMethod'];
        
        if (batch_enabled()) $relations[] = 'stockMovements';

        $invoice = Invoice::with($relations)->where('company_id', $companyId)->findOrFail($id);
        $payment = $invoice->payments->first();
        $shareToken = $this->posService->receiptToken($invoice);
        
        return view('admin.pos.receipt', compact('invoice', 'payment', 'shareToken'));
    }

    public function receiptJson($id)
    {
        $invoice = Invoice::where('company_id', Auth::user()->company_id)->findOrFail($id);

        return response()->json($this->posService->receiptPayload($invoice));
    }

    public function history(Request $request)
    {
        $activeStore = active_store();
        $perPage = min(50, max(1, (int) $request->query('per_page', 10)));

        $paginator = Invoice::with('customer')
            ->where('company_id', Auth::user()->company_id)
            ->where('source', 'pos')
            ->whereNotNull('invoice_number')
            ->when($activeStore, fn ($q) => $q->where('store_id', $activeStore->id))
            ->orderByDesc('id')
            ->paginate($perPage);

        $data = $paginator->getCollection()->map(fn ($invoice) => [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'date' => \Carbon\Carbon::parse($invoice->created_at)->format('d M Y, h:i A'),
            'client_name' => $invoice->customer->name ?? $invoice->customer_name ?? 'Guest',
            'share_url' => route('pos.receipt.public', [$invoice->id, $this->posService->receiptToken($invoice)]),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Load an invoice for a public share link, or abort.
     *
     * Reached without a session, so Tenantable registers no scope here and the
     * query spans every company by design — the token is the whole gate. The
     * record is loaded first because the signature is derived from its own
     * columns, then compared with hash_equals to keep the check constant-time.
     */
    private function resolveSharedInvoice(int $id, string $token): Invoice
    {
        $invoice = Invoice::with(['items', 'customer', 'store', 'creator', 'company', 'payments.paymentMethod'])
            ->findOrFail($id);

        abort_unless(
            hash_equals($this->posService->receiptToken($invoice), $token),
            403,
            'This receipt link is invalid or has expired.'
        );

        return $invoice;
    }

    public function publicReceipt(int $id, string $token)
    {
        $invoice = $this->resolveSharedInvoice($id, $token);
        $payment = $invoice->payments->first();

        return view('admin.pos.receipt', ['invoice' => $invoice, 'payment' => $payment, 'shareToken' => $token, 'isPublicView' => true]);
    }

    public function downloadPdf(int $id, string $token)
    {
        $invoice = $this->resolveSharedInvoice($id, $token);
        $payment = $invoice->payments->first();

        $pdf = Pdf::loadView('admin.pos.receipt', [
            'invoice' => $invoice,
            'payment' => $payment,
            'shareToken' => $token,
            'isPublicView' => true,
        ])
            ->setPaper([0, 0, 226.77, 1000], 'portrait')
            ->setOption([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => true,
                'dpi' => 72,
                'margin_top' => 0, 'margin_right' => 0, 'margin_bottom' => 0, 'margin_left' => 0,
            ]);

        return $pdf->download('Receipt-' . str_replace(['/', '\\'], '-', $invoice->invoice_number) . '.pdf');
    }
}