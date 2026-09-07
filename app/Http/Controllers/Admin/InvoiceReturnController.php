<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ExcessReturnQuantityException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInvoiceReturnRequest;
use App\Http\Requests\Admin\UpdateInvoiceReturnRequest;
use App\Models\Invoice;
use App\Models\InvoiceReturn;
use App\Models\State;
use App\Models\Warehouse;

use App\Services\InvoiceReturnService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceReturnController extends Controller
{
    protected InvoiceReturnService $returnService;

    // 🌟 Inject our powerful service
    public function __construct(InvoiceReturnService $returnService)
    {
        $this->returnService = $returnService;
    }

    /**
     * Display a listing of Credit Notes (Returns).
     */
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        
        $storeIds = active_store_ids();

        $query = InvoiceReturn::with(['customer', 'invoice'])
            ->where('company_id', $companyId)
            ->when($storeIds, fn ($q) => $q->whereIn('store_id', $storeIds));

        // 2. Apply Search Logic (Safely Grouped!)
        if ($request->filled('search')) {
            $searchTerm = $request->search;

            // The closure function($q) puts parentheses around the OR statements in SQL
            // e.g., WHERE company_id = 1 AND (credit_note_number LIKE %..% OR customer_name LIKE %..%)
            $query->where(function ($q) use ($searchTerm) {
                $q->where('credit_note_number', 'like', "%{$searchTerm}%")
                    ->orWhere('customer_name', 'like', "%{$searchTerm}%");
            });
        }

        // 3. Apply Status Filter (Draft / Confirmed)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 4. Apply Return Type Filter (Refund / Credit Note / Replacement)
        if ($request->filled('return_type')) {
            $query->where('return_type', $request->return_type);
        }

        // 5. Order, Paginate, and preserve URL parameters (withQueryString)
        $returns = $query->latest('return_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.invoice-returns.index', compact('returns'));
    }

    /**
     * Show the form for creating a return.
     * Note: We specifically pass the original Invoice here!
     */
    public function create(Invoice $invoice)
    {
        abort_if($invoice->company_id !== Auth::user()->company_id, 403);

        if ($invoice->status === 'draft' || $invoice->status === 'cancelled') {
            return back()->with('error', 'You can only create a return for a confirmed invoice.');
        }

        // Load the original invoice items with their SKUs
        $invoice->load(['items.sku', 'client']);

        // Per-line returnable capacity. `return_quantity` already reflects confirmed returns,
        // so remaining = quantity - return_quantity. Keyed by invoice_item.id for the blade.
        $returnableMap = $this->buildReturnableMap($invoice);
                
        $storeIds = active_store_ids();
        $warehouses = Warehouse::whereIn('store_id',  $storeIds)->where('is_active', true)->get();
        $states = State::where('is_active', true)->orderBy('name')->get();
        $companyState = Auth::user()->company->state->name ?? 'Unknown';

        return view('admin.invoice-returns.create', compact('invoice', 'warehouses', 'states', 'companyState', 'returnableMap'));
    }

    /**
     * Store a newly created return in storage.
     */
    public function store(StoreInvoiceReturnRequest $request, Invoice $invoice)
    {
        abort_if($invoice->company_id !== Auth::user()->company_id, 403);

        try {
            // Let the service handle the math and DB insertion
            $invoiceReturn = $this->returnService->createReturn($invoice, $request->validated());

            return redirect()->route('admin.invoice-returns.show', $invoiceReturn->id)
                ->with('success', 'Draft Credit Note created successfully. Please review and confirm.');

        } catch (ExcessReturnQuantityException $e) {
            Log::warning('Invoice Return Store blocked by excess quantity', [
                'invoice_id' => $invoice->id,
                'product' => $e->productLabel,
                'requested' => $e->requestedQty,
                'remaining' => $e->remainingQty,
            ]);

            return back()->withInput()->with('error', $e->friendlyMessage());
        } catch (Exception $e) {
            Log::error('Invoice Return Store Failed: '.$e->getMessage());

            return back()->withInput()->with('error', 'Failed to create return: '.$e->getMessage());
        }
    }

    /**
     * Display the specified return.
     */
    public function show(InvoiceReturn $invoiceReturn)
    {
        abort_if($invoiceReturn->company_id !== Auth::user()->company_id, 403);

        $invoiceReturn->load([
            'items.product',
            'items.sku',
            'items.originalInvoiceItem',
            'customer',
            'store',
            'warehouse',
            'invoice.items',
        ]);

        // Capacity snapshot for the invoice, so the "show" page can display
        // Original / Already Returned / Remaining next to each line.
        $returnableMap = $invoiceReturn->invoice
            ? $this->buildReturnableMap($invoiceReturn->invoice)
            : [];

        return view('admin.invoice-returns.show', compact('invoiceReturn', 'returnableMap'));
    }

    /**
     * Show the form for editing the return.
     */
    public function edit(InvoiceReturn $invoiceReturn)
    {
        abort_if($invoiceReturn->company_id !== Auth::user()->company_id, 403);

        if ($invoiceReturn->status === 'confirmed') {
            return redirect()->route('admin.invoice-returns.show', $invoiceReturn->id)
                ->with('error', 'Confirmed Credit Notes cannot be edited. They are locked for accounting.');
        }

        $invoiceReturn->load(['items', 'invoice.items.sku']);
        $invoice = $invoiceReturn->invoice; // Need original invoice context for the UI

        // Returnable capacity — exclude this draft's own line quantities so the user
        // can see the "room" they already have reserved for themselves.
        $returnableMap = $this->buildReturnableMap($invoice, excludeReturn: $invoiceReturn);
        $states = State::where('is_active', true)->orderBy('name')->get();
        $companyState = Auth::user()->company->state->name ?? 'Unknown';
                
        $storeIds = active_store_ids();
        $warehouses = Warehouse::whereIn('store_id',  $storeIds)->where('is_active', true)->get();

        return view('admin.invoice-returns.edit', compact('invoiceReturn', 'invoice', 'warehouses', 'states', 'companyState', 'returnableMap'));
    }

    /**
     * Update the specified return.
     */
    public function update(UpdateInvoiceReturnRequest $request, InvoiceReturn $invoiceReturn)
    {
        abort_if($invoiceReturn->company_id !== Auth::user()->company_id, 403);

        try {
            $this->returnService->updateReturn($invoiceReturn, $request->validated());

            return redirect()->route('admin.invoice-returns.show', $invoiceReturn->id)
                ->with('success', 'Credit Note updated successfully.');

        } catch (ExcessReturnQuantityException $e) {
            Log::warning('Invoice Return Update blocked by excess quantity', [
                'return_id' => $invoiceReturn->id,
                'product' => $e->productLabel,
                'requested' => $e->requestedQty,
                'remaining' => $e->remainingQty,
            ]);

            return back()->withInput()->with('error', $e->friendlyMessage());
        } catch (Exception $e) {
            Log::error('Invoice Return Update Failed: '.$e->getMessage());

            return back()->withInput()->with('error', 'Failed to update return: '.$e->getMessage());
        }
    }

    /**
     * 🟢 THE CRITICAL ERP ACTION: Confirm the return and restore stock.
     */
    public function confirm(InvoiceReturn $invoiceReturn)
    {
        abort_if($invoiceReturn->company_id !== Auth::user()->company_id, 403);

        try {
            // This triggers the InventoryService under the hood!
            $invoiceReturn = $this->returnService->confirmReturn($invoiceReturn);

            $message = $invoiceReturn->restock
                ? 'Credit Note confirmed! Stock has been securely returned to the warehouse.'
                : 'Credit Note confirmed! No stock was moved (restock was off for this return).';

            return back()->with('success', $message);

        } catch (ExcessReturnQuantityException $e) {
            Log::warning('Invoice Return Confirm blocked by excess quantity', [
                'return_id' => $invoiceReturn->id,
                'product' => $e->productLabel,
                'requested' => $e->requestedQty,
                'remaining' => $e->remainingQty,
            ]);

            return back()->with('error', $e->friendlyMessage());
        } catch (Exception $e) {
            Log::error('Invoice Return Confirm Failed: '.$e->getMessage());

            return back()->with('error', 'Confirmation failed: '.$e->getMessage());
        }
    }

    /**
     * Remove the draft return.
     */
    public function destroy(InvoiceReturn $invoiceReturn)
    {
        abort_if($invoiceReturn->company_id !== Auth::user()->company_id, 403);

        if ($invoiceReturn->status === 'confirmed') {
            return back()->with('error', 'Cannot delete a confirmed Credit Note.');
        }

        $invoiceReturn->delete();

        return redirect()->route('admin.invoice-returns.index')
            ->with('success', 'Draft Credit Note deleted successfully.');
    }

    /**
     * Build a per-invoice-item capacity snapshot:
     *   [invoice_item_id => ['original' => 10, 'returned' => 3, 'remaining' => 7]]
     *
     * Draft returns DON'T yet consume `return_quantity`, so we layer their line quantities
     * in separately to produce an honest "room left" figure for the UI. When editing a
     * specific draft, pass it via $excludeReturn so its own saved lines aren't counted
     * against the user.
     */
    private function buildReturnableMap(Invoice $invoice, ?InvoiceReturn $excludeReturn = null): array
    {
        if (! $invoice->relationLoaded('items')) {
            $invoice->load('items');
        }

        $invoiceItemIds = $invoice->items->pluck('id')->all();

        // Sum quantities across OTHER draft returns of this invoice.
        $draftAggregates = [];
        if (! empty($invoiceItemIds)) {
            InvoiceReturn::where('invoice_id', $invoice->id)
                ->where('status', 'draft')
                ->when($excludeReturn, fn ($q) => $q->where('id', '!=', $excludeReturn->id))
                ->with(['items' => fn ($q) => $q->whereIn('invoice_item_id', $invoiceItemIds)])
                ->get()
                ->each(function ($draft) use (&$draftAggregates) {
                    foreach ($draft->items as $line) {
                        $key = (int) $line->invoice_item_id;
                        $draftAggregates[$key] = ($draftAggregates[$key] ?? 0) + (float) $line->quantity;
                    }
                });
        }

        $map = [];
        foreach ($invoice->items as $item) {
            $original = (float) $item->quantity;
            $confirmed = (float) ($item->return_quantity ?? 0);
            $draft = (float) ($draftAggregates[$item->id] ?? 0);
            $alreadyReturned = $confirmed + $draft;
            $remaining = max(0.0, $original - $alreadyReturned);

            $map[$item->id] = [
                'original' => $original,
                'returned' => $alreadyReturned,
                'returned_confirmed' => $confirmed,
                'returned_draft' => $draft,
                'remaining' => $remaining,
            ];
        }

        return $map;
    }

    /**
     * Download Credit Note (Invoice Return) as PDF
     */
    public function downloadPdf(InvoiceReturn $invoiceReturn)
    {
        abort_if($invoiceReturn->company_id !== Auth::user()->company_id, 403);

        // Load all required relations for the PDF rendering
        $invoiceReturn->load([
            'items.product',
            'items.sku',
            'customer',
            'company',
            'store.state',
            'invoice' // Load original invoice for reference
        ]);

        // Fallback to company if store details are missing
        $companyInfo = $invoiceReturn->store ?? Auth::user()->company;

        // Load the view and pass data (You will create this blade view next)
        $pdf = Pdf::loadView('admin.invoice-returns.pdf', compact('invoiceReturn', 'companyInfo'))
            ->setOption(['defaultFont' => 'DejaVu Sans']); // Ensures ₹ symbol is supported globally

        // Configure PDF settings for A4 paper
        $pdf->setPaper('A4', 'portrait');

        // Create a safe filename without slashes
        $safeFilename = str_replace(['/', '\\'], '-', $invoiceReturn->credit_note_number);

        // Download the file safely
        return $pdf->download('CreditNote-' . $safeFilename . '.pdf');
    }


}
