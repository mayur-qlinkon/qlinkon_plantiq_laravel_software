<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SkuSearchContext;
use App\Http\Controllers\Admin\Concerns\SearchesSkus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInvoiceRequest;
use App\Http\Requests\Admin\UpdateInvoiceRequest;
use App\Models\Challan;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceReturn;
use App\Models\InvoiceWriteOff;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\State;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\ProductService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    use SearchesSkus;

    /**
     * Line-item picker search for invoices.
     *
     * Sales context: an invoice commits stock, so only SKUs with stock on hand
     * in the selected warehouse are offered.
     */
    public function searchSkus(Request $request, ProductService $productService)
    {
        return $this->respondWithSkuSearch($request, $productService, SkuSearchContext::Sales);
    }
    protected InvoiceService $invoiceService;

    protected PaymentService $paymentService;

    protected InventoryService $inventoryService;

    public function __construct(
        InvoiceService $invoiceService,
        PaymentService $paymentService,
        InventoryService $inventoryService
    ) {
        $this->invoiceService = $invoiceService;
        $this->paymentService = $paymentService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display a list of all invoices (Unified: POS + Direct)
     */
    public function index(Request $request)
    {
        $storeIds = active_store_ids(); // null = owner/super-admin (sees all stores)

        $query = Invoice::with(['client', 'creator', 'payments', 'returns', 'writeOffs'])
            ->when($storeIds, fn ($q) => $q->whereIn('store_id', $storeIds))
            ->latest();

         if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('invoice_number', 'like', "%{$request->search}%")
                  ->orWhere('customer_name', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        // ── Date Range Filter ───────────────────────────────────────────
        if ($request->filled('start_date')) {
            $query->whereDate('invoice_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('invoice_date', '<=', $request->end_date);
        }

        // Count for the selected range BEFORE pagination limits the result set.
        $filteredCount = ($request->filled('start_date') || $request->filled('end_date'))
            ? $query->count()
            : null;

        // ── Stat cards: aggregate totals across the FULL filtered result set (not just this page) ──
        $filteredIds = (clone $query)->pluck('id');

        $totalAmount = Invoice::whereIn('id', $filteredIds)->sum('grand_total');

        $totalPaid = Payment::where('paymentable_type', Invoice::class)
            ->whereIn('paymentable_id', $filteredIds)
            ->where('status', 'completed')
            ->sum('amount');

        $totalKasar = InvoiceWriteOff::whereIn('invoice_id', $filteredIds)
            ->where('status', 'confirmed')
            ->sum('amount');

        $totalReturned = InvoiceReturn::whereIn('invoice_id', $filteredIds)
            ->where('status', 'confirmed')
            ->sum('grand_total');

        $totalPending = max(0, $totalAmount - $totalPaid - $totalKasar - $totalReturned);

        $invoiceStats = [
            // Count of the full filtered set, so picking a user answers
            // "how many did they raise" without paging through the list.
            'total_count' => $filteredIds->count(),
            'total_amount' => $totalAmount,
            'total_paid' => $totalPaid,
            'total_kasar' => $totalKasar,
            'total_returned' => $totalReturned,
            'total_pending' => $totalPending,
        ];

        $invoices = $query->paginate(50)->withQueryString();

        $paymentMethods = PaymentMethod::getForSelector();

        // Only people who have actually raised an invoice in the visible stores.
        // Listing every user would fill the filter with names that can only ever
        // return an empty result.
        $creators = User::whereIn('id', function ($q) use ($storeIds) {
            $q->select('created_by')
                ->from('invoices')
                ->whereNotNull('created_by')
                ->when($storeIds, fn ($sub) => $sub->whereIn('store_id', $storeIds));
        })
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.invoices.index', compact(
            'invoices', 'filteredCount', 'paymentMethods', 'invoiceStats', 'creators'
        ));
    }

    /**
     * Show the dedicated B2B/Direct Invoice creation form
     */
    public function create(Request $request)
    {
        $company = Company::find(Auth::user()->company_id);
        $companyState = $company->state->name ?? 'Unknown';
        $clients = Client::with('state')->where('is_active', true)->get();

        $storeIds = active_store_ids();
        $warehouses = Warehouse::whereIn('store_id', $storeIds)
                ->where('is_active', true)
                ->get();        
        $units = Unit::all();
        $states = State::where('is_active', true)->orderBy('name')->get();
        
        // Resolve one company-level fallback unit, shared by every prefill path
        // (challan -> invoice and order -> invoice). Older rows may carry no
        // unit_id snapshot, and items.*.unit_id is required on the invoice form.
        // Prefers the default 'Piece' unit, otherwise the oldest unit available.
        $fallbackUnitId = Unit::where('company_id', Auth::user()->company_id)
            ->orderByRaw("CASE WHEN name = 'Piece' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->value('id');

        // Load challan for pre-fill when converting from a Delivery Challan
        $challanPrefill = null;
        $challanPrefillJs = null;
        if ($request->filled('challan_id')) {
            $challanPrefill = Challan::with(['items.productSku.product', 'invoices'])
                ->where('company_id', Auth::user()->company_id)
                ->findOrFail($request->challan_id);

            // Hard guard. Hiding the button in the view is not enough — the
            // challan_id can be supplied directly in the URL, and two open tabs
            // can both pass a view-level check.
            if ($challanPrefill->items->sum('qty_pending') <= 0) {
                $existing = $challanPrefill->invoices->first();

                return redirect()
                    ->route('admin.challans.show', $challanPrefill->id)
                    ->with('error', $existing
                        ? "This challan is already fully billed on invoice {$existing->invoice_number}."
                        : 'This challan has no pending quantity left to invoice.');
            }

            // Build a JS-safe array of pending items for Alpine.js pre-population
            $challanPrefillJs = $challanPrefill->items
                ->filter(fn ($i) => $i->qty_pending > 0)
                ->map(fn ($i) => [
                    'challan_item_id' => $i->id,
                    'product_id' => $i->product_id,
                    'product_sku_id' => $i->product_sku_id,
                    'unit_id' => $i->unit_id ?? $i->productSku?->unit_id ?? $fallbackUnitId,
                    'product_name' => $i->product_name,
                    'sku_code' => $i->sku_code,
                    'hsn_code' => $i->hsn_code,
                    'quantity' => (float) $i->qty_pending,
                    'unit_price' => (float) $i->unit_price,
                    'tax_percent' => (float) $i->tax_rate,
                    'tax_type' => 'exclusive',
                    'discount_type' => 'fixed',
                    'discount_value' => 0,
                    'batch_id' => $i->batch_id,
                    'batch_number' => $i->batch_number,
                ])
                ->values();
        }

        // Load order for pre-fill when converting from an Order
        $orderPrefill    = null;
        $orderPrefillJs  = null;        
        if ($request->filled('order_id')) {
            $orderPrefill = Order::with(['items.skuWithTrashed.unit'])
                ->where('company_id', Auth::user()->company_id)
                ->findOrFail($request->order_id);

            // Build a JS-safe array of items for Alpine.js pre-population.
            // unit_price may be null for catalog/inquiry orders — defaults to 0
            // so the user can fill in the price on the invoice form.
            $orderPrefillJs = $orderPrefill->items->map(fn ($i) => [
                'product_id'     => $i->product_id,
                'product_sku_id' => $i->sku_id,
                'unit_id'        => $i->skuWithTrashed?->unit_id ?? $fallbackUnitId,
                'product_name'   => $i->product_name,
                'sku_code'       => $i->sku_code ?? '',
                'hsn_code'       => $i->skuWithTrashed?->hsn_code ?? '',
                'quantity'       => (float) $i->qty,
                'unit_price'     => (float) ($i->unit_price ?? 0),
                'tax_percent'    => (float) ($i->tax_rate ?? 0),
                'tax_type'       => 'exclusive',
                'discount_type'  => 'fixed',
                'discount_value' => 0,
                'batch_id'       => null,
                'batch_number'   => '',
            ])->values();
        }

         // ── Pass raw order customer info to the view for guest-mode pre-fill ──
        // IMPORTANT: Order.customer_id is a User (storefront account), NOT a Client (B2B clients table).
        // We must never cross-wire these ID spaces. Instead we pass the name+phone stored
        // on the order itself so the admin can manually select or create the right client.
        $orderGuestPrefill = null;
        if ($orderPrefill) {
            $orderGuestPrefill = [
                'name'  => $orderPrefill->customer_name ?? '',
                'phone' => $orderPrefill->customer_phone ?? '',
            ];
        }
        

        return view('admin.invoices.create', compact(
            'clients', 'warehouses', 'units', 'companyState', 'states',
            'challanPrefill', 'challanPrefillJs',
            'orderPrefill', 'orderPrefillJs', 'orderGuestPrefill'
        ));
    }

    /**
     * Store a new Invoice and trigger Stock/Payment
     */
    public function store(StoreInvoiceRequest $request)
    {
        try {
            return DB::transaction(function () use ($request) {

                $validated = $request->validated();

                // 1. Create Invoice via Service (Handles GST & Stock)
                $invoice = $this->invoiceService->createInvoice(
                    $validated,
                    Auth::user()->company_id
                );

                // 2. Handle Initial Payment if provided
                if (! empty($validated['amount_paid']) && $validated['amount_paid'] > 0) {
                    // 🌟 Pass the RAW amount the customer handed over directly to the service!
                    $this->paymentService->recordPayment($invoice, [
                        'amount' => $validated['amount_paid'],
                        'payment_method_id' => $validated['payment_method_id'] ?? null,
                        'payment_date' => now(),
                        'status' => 'completed',
                        'notes' => 'Initial payment received at invoice creation.',
                    ]);
                }

                // 3. Link back to source order (if invoice was created from an order)
                if (! empty($validated['order_id'])) {
                    // Write order_id onto the invoice itself (bidirectional link)
                    $invoice->update(['order_id' => $validated['order_id']]);

                    // Write invoice_id back onto the order
                    Order::where('id', $validated['order_id'])
                        ->where('company_id', Auth::user()->company_id) // tenant safety
                        ->update(['invoice_id' => $invoice->id]);

                    Log::info('[Invoice] Linked to source order', [
                        'invoice_id' => $invoice->id,
                        'order_id'   => $validated['order_id'],
                        'by'         => Auth::id(),
                    ]);
                }

                return redirect()->route('admin.invoices.show', $invoice->id)
                    ->with('success', 'Invoice generated successfully!');
            });

        } catch (\Exception $e) {
            Log::error('Invoice Creation Failed: '.$e->getMessage());

            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * View detailed Invoice (The Bill Preview)
     */
    public function show(Invoice $invoice)
    {
        abort_if($invoice->company_id !== Auth::user()->company_id, 403);
        // 🌟 Added 'company' and 'store.state' to eager load the new header data!
        $invoice->loadMissing('creator:id,name');
        $invoice->load([
            'items.sku.product',
            'client',
            'payments.paymentMethod',
            'payments.creator',
            'returns',
            'writeOffs',
            'stockMovements',
            'company',
            'store.state',
            'sourceOrder',
        ]);        
        return view('admin.invoices.show', compact('invoice'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoice $invoice)
    {
        abort_if($invoice->company_id !== Auth::user()->company_id, 403);
        if ($invoice->status === 'cancelled') {
            return back()->with('error', 'Cannot edit a cancelled invoice.');
        }

        // Confirmed invoices are finalized records. Only drafts are freely editable.
        if ($invoice->status === 'confirmed') {
            return redirect()->route('admin.invoices.show', $invoice->id)
                ->with('error', 'This invoice is already confirmed. Confirmed invoices cannot be edited — cancel it and create a new one instead.');
        }
        $company = Company::find(Auth::user()->company_id);
        $companyState = $company->state->name ?? 'Unknown';
        $clients = Client::with('state')->where('is_active', true)->get();
        $storeIds = active_store_ids();
        $warehouses = Warehouse::whereIn('store_id', $storeIds)
                ->where('is_active', true)
                ->get();        
        $units = Unit::all();
        $states = State::where('is_active', true)->orderBy('name')->get();

        $invoice->load(['items.sku', 'payments']);
        $invoiceStateId = State::where('name', $invoice->supply_state)->value('id');

        return view('admin.invoices.edit', compact('invoice', 'clients', 'warehouses', 'states', 'units', 'companyState','invoiceStateId'));
    }

    

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        try {
            return DB::transaction(function () use ($request, $invoice) {

                $validated = $request->validated();

                // 1. Update Invoice (Reverses old stock, updates rows, deducts new stock)
                $invoice = $this->invoiceService->updateInvoice(
                    $invoice,
                    $validated,
                    Auth::user()->company_id
                );

                // 2. Sync Payments (Updates existing, creates new, or deletes if set to 0)
                if (isset($validated['amount_paid'])) {
                    // 🌟 Pass the RAW amount received
                    $this->paymentService->updateInitialPayment($invoice, [
                        'amount' => $validated['amount_paid'],
                        'payment_method_id' => $validated['payment_method_id'] ?? null,
                    ]);
                }

                return redirect()->route('admin.invoices.show', $invoice->id)
                    ->with('success', 'Invoice updated successfully!');
            });
        } catch (\Exception $e) {
            Log::error('Invoice Update Failed', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'error' => $e->getMessage(),
                ]);
        }
    }

    /**
     * Add a payment to an existing invoice from the Index/Show page
     */
    public function addPayment(Request $request, Invoice $invoice)
    {
        abort_if($invoice->company_id !== Auth::user()->company_id, 403);

        if ($invoice->status === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Cannot add payments to a cancelled invoice.'], 422);
        }

        // 1. Validate the incoming request — matches the shared quick-payment modal contract.
        $request->validate([
            'amount'            => ['required', 'numeric', 'min:0.01'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'payment_date'      => ['nullable', 'date'],
            'reference'         => ['nullable', 'string', 'max:255'],
            'notes'             => ['nullable', 'string', 'max:1000'],
        ]);

        // 2. Security: Calculate actual balance due directly from the database
        $totalPaid = $invoice->payments()->where('status', 'completed')->sum('amount');
        $balanceDue = round($invoice->grand_total - $totalPaid, 2);

        if ($balanceDue <= 0) {
            return response()->json(['success' => false, 'message' => 'This invoice is already fully paid.'], 422);
        }

        // 3. Security: Prevent overpayment (Strict mode)
        if (round($request->amount, 2) > $balanceDue) {
            return response()->json([
                'success' => false,
                'message' => "Payment cannot exceed the balance due of ₹{$balanceDue}.",
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $invoice) {
                // Re-check balance inside transaction to prevent TOCTOU race
                $totalPaidNow = $invoice->payments()->where('status', 'completed')->lockForUpdate()->sum('amount');
                $balanceDueNow = round($invoice->grand_total - $totalPaidNow, 2);
                if ($balanceDueNow <= 0 || round($request->amount, 2) > $balanceDueNow) {
                    throw new \RuntimeException('Payment amount exceeds current balance due. Please refresh and try again.');
                }

                // 4. Record the payment using our robust service
                // (This will automatically trigger syncDocumentPaymentStatus to update the Invoice to 'partial' or 'paid')
                $this->paymentService->recordPayment($invoice, [
                    'amount'            => $request->amount,
                    'payment_method_id' => $request->payment_method_id,
                    'payment_date'      => $request->payment_date ?? now(),
                    'reference'         => $request->reference,
                    'notes'             => $request->notes,
                    'status'            => 'completed',
                ]);
            });

            return response()->json(['success' => true, 'message' => 'Payment recorded successfully.']);
        } catch (\Exception $e) {
            Log::error('Invoice Quick Payment Failed', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Download Invoice as PDF
     */
    public function downloadPdf(Invoice $invoice)
    {
        abort_if($invoice->company_id !== Auth::user()->company_id, 403);
        // Load all required relations
        $invoice->load([
            'items.sku.product',
            'client',
            'payments.paymentMethod',
            'company',
            'store.state',
        ]);
        $companyInfo = $invoice->store ?? Auth::user()->company;

        // Load the view and pass data
        $pdf = Pdf::loadView('admin.invoices.pdf', compact('invoice', 'companyInfo'))
        ->setOption(['defaultFont' => 'DejaVu Sans']); // Ensures ₹ is supported globally

        // Optional: Configure PDF settings for A4 paper
        $pdf->setPaper('A4', 'portrait');

        // Download the file
        $safeFilename = str_replace(['/', '\\'], '-', $invoice->invoice_number);

        // Download the file safely
        return $pdf->download('Invoice-'.$safeFilename.'.pdf');
    }

    /**
     * Cancel an Invoice (Reverse Stock & Ledger)
     */
    public function destroy(Invoice $invoice)
    {
        abort_if($invoice->company_id !== Auth::user()->company_id, 403);
        if ($invoice->status === 'cancelled') {
            return back()->with('error', 'Invoice is already cancelled.');
        }

        try {
            DB::transaction(function () use ($invoice) {
                // 🌟 Delegate entirely to the Service
                $this->invoiceService->cancelInvoice($invoice);
            });

            return back()->with('success', 'Invoice has been cancelled and stock reversed.');
        } catch (\Exception $e) {
            Log::error('Invoice Cancellation Failed: '.$e->getMessage());

            return back()->with('error', 'Failed to cancel invoice: '.$e->getMessage());
        }
    }
}