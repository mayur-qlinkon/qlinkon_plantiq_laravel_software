<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SkuSearchContext;
use App\Http\Controllers\Admin\Concerns\SearchesSkus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePurchaseRequest;
use App\Http\Requests\Admin\UpdatePurchaseRequest;
use App\Models\ProductSku;
use App\Models\Purchase;
use App\Models\PurchaseReturnItem;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\PaymentMethod;
use App\Services\PaymentService;
use App\Services\ProductService;
use App\Services\PurchaseReturnService;
use App\Services\PurchaseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class PurchaseController extends Controller
{
    use SearchesSkus;

    /**
     * Line-item picker search for purchase orders.
     *
     * Procurement context: no warehouse is required and stock levels are
     * irrelevant, since a purchase creates stock rather than consuming it.
     * This is the only context that receives supplier cost.
     */
    public function searchSkus(Request $request, ProductService $productService)
    {
        return $this->respondWithSkuSearch($request, $productService, SkuSearchContext::Procurement);
    }

    /**
     * Search received purchase orders that still have returnable quantity.
     *
     * logic was already correctly store-scoped and is left unchanged.
     */
    public function searchForReturn(Request $request)
    {
        $term     = $request->query('term');
        $storeIds = active_store_ids();

        $query = Purchase::with('supplier:id,name')
            ->where('company_id', Auth::user()->company_id)
            // Restrict search to authorised stores.
            ->when($storeIds !== null, fn ($q) => $q->whereIn('store_id', $storeIds))
            // Only received POs can be returned.
            ->whereIn('status', ['received', 'partially_received'])
            // Exclude POs where every item has already been fully returned.
            // A PO is fully returned when no purchase_item has remaining
            // returnable qty (original qty − sum of non-cancelled return qtys).
            ->whereHas('items', function ($q) {
                $q->whereRaw(
                    'quantity > COALESCE((
                        SELECT SUM(pri.quantity)
                        FROM purchase_return_items pri
                        JOIN purchase_returns pr ON pri.purchase_return_id = pr.id
                        WHERE pri.purchase_item_id = purchase_items.id
                          AND pr.status != ?
                    ), 0)',
                    ['cancelled']
                );
            });

        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('purchase_number', 'like', "%{$term}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$term}%"));
            });
        }

        // Latest first, capped to keep the dropdown usable.
        $purchases = $query->latest()
            ->take(10)
            ->get(['id', 'purchase_number', 'supplier_id', 'total_amount', 'purchase_date']);

        return response()->json($purchases);
    }
    public function __construct(
        protected PurchaseService  $purchaseService,
        protected PaymentService   $paymentService,
    ) {}

    /**
     * Display a listing of the purchases.
     */
    public function index(Request $request)
    {    
        $storeIds = active_store_ids();

        $query = Purchase::with(['supplier', 'warehouse', 'store'])
            ->when($storeIds, fn ($q) => $q->whereIn('store_id', $storeIds))
            ->latest();

        // 1. Text Search (PO Number, Invoice, Supplier Name)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('purchase_number', 'like', "%{$search}%")
                    ->orWhere('supplier_invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // 2. Status Filter (Added)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 3. Payment Status Filter (Added)
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // 4. Date Range Filter (purchase_date)
        if ($request->filled('start_date')) {
            $query->whereDate('purchase_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('purchase_date', '<=', $request->end_date);
        }

        // Count for the selected range BEFORE pagination limits the result set.
        $filteredCount = ($request->filled('start_date') || $request->filled('end_date'))
            ? $query->count()
            : null;

        // 5. Paginate and append query string (Crucial for Page 2, Page 3 etc.)
        $purchases = $query->paginate(15)->withQueryString();

        $paymentMethods = PaymentMethod::getForSelector();

        return view('admin.purchases.index', compact('purchases', 'paymentMethods', 'filteredCount'));
    }

    /**
     * Show the form for creating a new purchase.
     */
    public function create(Request $request)
    {
        // Fetch active masters for the dropdowns
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();        
        $units = Unit::where('is_active', true)->get();

        $storeIds =  active_store_ids(); // 🛡️ Get authorized store IDs
        $warehouses = Warehouse::whereIn('store_id',$storeIds)->where('is_active', true)->orderBy('name')->get();

        // 🌟 CATCH THE REORDER DATA
        $prefillSkuId = $request->query('sku_id');
        $prefillQty = $request->query('qty');

        $selectedSku = null;
        if ($prefillSkuId) {
            // Eager load product to get the name and details for the frontend
            $selectedSku = ProductSku::with('product')->find($prefillSkuId);
        }

        $units = Unit::where('is_active', true)->get();

        return view('admin.purchases.create', [
            'suppliers' => $suppliers,            
            'warehouses' => $warehouses,
            'units' => $units,
            'batchEnabled' => batch_enabled(),
            'selectedSku' => $selectedSku, // 👈 Pass to view
            'prefillQty' => $prefillQty,
        ]);
    }

    /**
     * Store a newly created purchase in storage.
     */
    public function store(StorePurchaseRequest $request)
    {
        try {
            // Pass validated data to our robust service
            $purchase = $this->purchaseService->createPurchase($request->validated());

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Purchase Order created!', 'redirect' => route('admin.purchases.show', $purchase->id)]);
            }

            return redirect()->route('admin.purchases.show', $purchase->id)
                ->with('success', 'Purchase Order generated successfully.');

        } catch (\Exception $e) {
            Log::error('Purchase Creation Failed: '.$e->getMessage());

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', 'Failed to create purchase: '.$e->getMessage());
        }
    }

    /**
     * Display the specified purchase.
     */
    public function show(Purchase $purchase)
    {
        $storeIds = active_store_ids();
        abort_if($storeIds !== null && !in_array($purchase->store_id, $storeIds), 403, 'Unauthorized store access.');

        $purchase->load([
            'supplier',
            'store',
            'warehouse',
            'creator',
            'updater',
            'items.product',
            'items.productSku',
            'items.unit',
            'payments.paymentMethod',
            'payments.creator',
        ]);

        $paymentMethods = PaymentMethod::getForSelector();

        return view('admin.purchases.show', compact('purchase', 'paymentMethods'));
    }

    /**
     * Show the form for editing the specified purchase.
     */
    public function edit(Purchase $purchase)
    {
        // 🛡️ ERP GUARD: Do not allow editing financial data of received purchases
        if ($purchase->status === 'received') {
            return redirect()->route('admin.purchases.show', $purchase->id)
                ->with('error', 'Cannot edit a fully received purchase. Please process a Purchase Return to make adjustments.');
        }

        $purchase->load(['items.product', 'items.productSku', 'items.unit']);

        $purchase->items->each(function ($item) {
            if ($item->manufacturing_date) {
                $item->manufacturing_date = Carbon::parse($item->manufacturing_date)->format('d-m-Y');
            }
            if ($item->expiry_date) {
                $item->expiry_date = Carbon::parse($item->expiry_date)->format('d-m-Y');
            }
        });

        $storeIds = active_store_ids();
        abort_if($storeIds !== null && !in_array($purchase->store_id, $storeIds), 403, 'Unauthorized store access.');

        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();                
        $warehouses = Warehouse::whereIn('store_id', active_store_ids())->where('is_active', true)->orderBy('name')->get();

        $units = Unit::where('is_active', true)->get();

        return view('admin.purchases.edit', [
            'purchase' => $purchase,
            'suppliers' => $suppliers,            
            'warehouses' => $warehouses,
            'units' => $units,
            'batchEnabled' => batch_enabled(),
        ]);
    }

    /**
     * Update the specified purchase in storage.
     */
    public function update(UpdatePurchaseRequest $request, Purchase $purchase)
    {
        $storeIds = active_store_ids();
        abort_if($storeIds !== null && !in_array($purchase->store_id, $storeIds), 403, 'Unauthorized store access.');

        try {
            $this->purchaseService->updatePurchase($purchase, $request->validated());

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Purchase Order updated!', 'redirect' => route('admin.purchases.show', $purchase->id)]);
            }

            return redirect()->route('admin.purchases.show', $purchase->id)
                ->with('success', 'Purchase Order updated successfully.');

        } catch (InvalidArgumentException $e) {
            // Business rules — rejected status moves, guarded records. These are
            // meant to be read by the user.
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());

        } catch (\Throwable $e) {
            Log::error('Purchase Update Failed', [
                'purchase_id' => $purchase->id,
                'exception'   => $e,
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not update this purchase. Please try again.',
                ], 500);
            }

            return back()->withInput()->with('error', 'Could not update this purchase. Please try again.');
        }
    }

    /**
     * API Endpoint: Fetch Purchase Order details for processing a return.
     *
     * * @param int $id
     * @return JsonResponse
     */
    /**
     * API Endpoint: Fetch Purchase Order details for processing a return.
     */
    public function getForReturn(Request $request, $id, PurchaseReturnService $returnService)
    {
        $storeIds = active_store_ids();

        $purchase = Purchase::with(['items.product', 'items.productSku', 'items.unit'])
            ->where('company_id', Auth::user()->company_id)
            ->whereIn('store_id', $storeIds)
            ->findOrFail($id);

        // Availability is computed in one place now. This method used to sum
        // return rows itself against ordered quantity, which disagreed with the
        // guard that actually blocks the write.
        $available = $returnService->availableQuantities(
            (int) $purchase->id,
            $request->query('exclude_return_id') ? (int) $request->query('exclude_return_id') : null
        );

        $filteredItems = $purchase->items
            ->map(function ($item) use ($available) {
                $item->available_qty = $available[$item->id] ?? 0;

                return $item;
            })
            ->filter(fn ($item) => $item->available_qty > 0) // Hide fully returned rows
            ->values();

        $purchase->setRelation('items', $filteredItems);

        return response()->json($purchase);
    }

    /**
     * Record a payment against a Purchase Order via PaymentService.
     * Replaces the old manual updatePayment() — now creates a real Payment row
     * and syncs paid_amount / balance_amount / payment_status atomically.
     */
    public function addPayment(Request $request, Purchase $purchase)
    {
        $storeIds = active_store_ids();
        abort_if($storeIds !== null && !in_array($purchase->store_id, $storeIds), 403, 'Unauthorized store access.');

        if ($purchase->payment_status === 'paid') {
            return response()->json(['success' => false, 'message' => 'This purchase is already fully paid.'], 422);
        }

        $request->validate([
            'amount'            => ['required', 'numeric', 'min:0.01'],
            // Scoped to this tenant. An unscoped id accepted another company's
            // payment method, and a bad one surfaced as a raw foreign-key error.
            'payment_method_id' => [
                'required',
                Rule::exists('payment_methods', 'id')
                    ->where('company_id', $purchase->company_id)
                    ->whereNull('deleted_at'),
            ],
            'payment_date'      => ['nullable', 'date'],
            'reference'         => ['nullable', 'string', 'max:255'],
            'notes'             => ['nullable', 'string', 'max:1000'],
        ]);

        // Guard: cannot pay more than balance due
        $balanceDue = round((float) $purchase->balance_amount, 2);
        if (round((float) $request->amount, 2) > $balanceDue) {
            return response()->json([
                'success' => false,
                'message' => "Amount cannot exceed the balance due of ₹{$balanceDue}.",
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $purchase, $balanceDue) {
                // Re-check balance inside transaction (prevent race condition)
                $currentBalance = round((float) Purchase::lockForUpdate()->find($purchase->id)->balance_amount, 2);
                if (round((float) $request->amount, 2) > $currentBalance) {
                    throw new \RuntimeException("Payment amount exceeds current balance due of ₹{$currentBalance}.");
                }

                $this->paymentService->recordPayment($purchase, [
                    'amount'            => $request->amount,
                    'payment_method_id' => $request->payment_method_id,
                    'payment_date'      => $request->payment_date ?? now(),
                    'reference'         => $request->reference,
                    'notes'             => $request->notes,
                    'status'            => 'completed',
                ]);
            });

            return response()->json(['success' => true, 'message' => 'Payment recorded successfully.']);

        } catch (\RuntimeException $e) {
            // Thrown deliberately above when the balance moved under us.
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);

        } catch (\Throwable $e) {
            Log::error('Purchase Payment Failed', [
                'purchase_id' => $purchase->id,
                'exception'   => $e,
            ]);

            // Raw exception text stays in the log: it used to be echoed back,
            // exposing SQL and schema details to the browser.
            return response()->json([
                'success' => false,
                'message' => 'Could not record this payment. Please try again.',
            ], 500);
        }
    }

    /**
     * Remove the specified purchase from storage.
     */
    public function destroy(Purchase $purchase)
    {
        $storeIds = active_store_ids();
        abort_if($storeIds !== null && !in_array($purchase->store_id, $storeIds), 403, 'Unauthorized store access.');

        // 🛡️ ERP GUARD: Never delete received purchases. It breaks the stock ledger.
        if ($purchase->status === 'received') {
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Cannot delete a received purchase. Stock is already in the warehouse.'], 403);
            }

            return back()->with('error', 'Cannot delete a received purchase. Stock is already in the warehouse.');
        }

        // Money against the order outlives a soft delete: the payment rows stay
        // on the ledger pointing at a document nobody can open.
        if ($purchase->payments()->where('status', 'completed')->exists()) {
            $message = 'This purchase has recorded payments and cannot be deleted. Reverse the payments first.';

            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }

            return back()->with('error', $message);
        }

        DB::transaction(function () use ($purchase) {
            // Both soft-delete now, so restoring the header brings its lines back.
            $purchase->items()->delete();
            $purchase->delete();
        });

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Purchase Order deleted successfully.']);
        }

        return redirect()->route('admin.purchases.index')
            ->with('success', 'Purchase Order deleted successfully.');
    }

    public function downloadPdf(Purchase $purchase)
    {
        $purchase->load(['supplier', 'store', 'warehouse', 'items.product', 'items.productSku', 'items.unit']);

        $pdf = Pdf::loadView('admin.purchases.pdf', compact('purchase'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('Purchase_Order_'.$purchase->purchase_number.'.pdf');
    }
}
