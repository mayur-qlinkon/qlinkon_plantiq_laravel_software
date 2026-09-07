<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SkuSearchContext;
use App\Http\Controllers\Admin\Concerns\SearchesSkus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuotationRequest;
use App\Http\Requests\Admin\UpdateQuotationRequest;
use App\Models\Client;
use App\Models\Quotation;
use App\Models\State;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\ProductService;
use App\Services\QuotationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuotationController extends Controller
{
    use SearchesSkus;

    /**
     * Line-item picker search for quotations.
     *
     * Sales context, matching the previous behaviour: a quotation is only
     * offered for items that can actually be fulfilled.
     */
    public function searchSkus(Request $request, ProductService $productService)
    {
        return $this->respondWithSkuSearch($request, $productService, SkuSearchContext::Sales);
    }
    public function __construct(
        protected QuotationService $quotationService,
    ) {}

    // =========================================================================
    //  READ
    // =========================================================================

    public function index(Request $request)
    {
        // active_store_ids() never returns null — it returns [] for super
        // admins, [id] for a scoped user, or [0] when no store resolves. The
        // old comment claimed null, and [] is falsy, so when() silently skipped
        // the filter. Combined with Quotation having no tenant scope at the
        // time, this query had no company condition at all.
        $storeIds = active_store_ids();

        $query = Quotation::with(['customer', 'creator'])
            ->where('company_id', Auth::user()->company_id)
            ->when($storeIds, fn ($q) => $q->whereIn('store_id', $storeIds))
            ->latest('quotation_date')
            ->latest('id');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('quotation_number', 'like', "%{$request->search}%")
                  ->orWhere('customer_name',  'like', "%{$request->search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Date Range Filter (quotation_date)
        if ($request->filled('start_date')) {
            $query->whereDate('quotation_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('quotation_date', '<=', $request->end_date);
        }

        // Count for the selected range BEFORE pagination limits the result set.
        $filteredCount = ($request->filled('start_date') || $request->filled('end_date'))
            ? $query->count()
            : null;

        $quotations = $query->paginate(15)->withQueryString();

        return view('admin.quotations.index', compact('quotations', 'filteredCount'));
    }

    public function show(Quotation $quotation)
    {
        abort_if($quotation->company_id !== Auth::user()->company_id, 403);

        $quotation->load(['items', 'customer', 'store', 'creator', 'invoice']);

        return view('admin.quotations.show', compact('quotation'));
    }

    // =========================================================================
    //  FORMS
    // =========================================================================

    public function create()
    {
        $companyId = Auth::user()->company_id;        
        $storeIds  = active_store_ids();

        return view('admin.quotations.create', [            
            'clients'      => Client::with('state')->where('company_id', $companyId)->where('is_active', true)->get(),
            'warehouses'   => Warehouse::whereIn('store_id', $storeIds)->where('is_active', true)->get(),
            'states'       => State::where('is_active', true)->orderBy('name')->get(),
            'units'        => Unit::where('is_active', true)->get(),
            'companyState' => Auth::user()->company->state?->name ?? 'Unknown',
        ]);
    }

    public function edit(Quotation $quotation)
    {
        abort_if($quotation->company_id !== Auth::user()->company_id, 403);

        if ($quotation->status === 'converted') {
            return redirect()->route('admin.quotations.show', $quotation->id)
                ->with('error', 'Converted quotations cannot be edited.');
        }

        $quotation->load('items');        
        $storeIds = active_store_ids();

        return view('admin.quotations.edit', [
            'quotation'    => $quotation,            
            'clients'      => Client::with('state')->where('company_id', $quotation->company_id)->where('is_active', true)->get(),
            'warehouses'   => Warehouse::whereIn('store_id', $storeIds)->where('is_active', true)->get(),
            'states'       => State::where('is_active', true)->orderBy('name')->get(),
            'units'        => Unit::where('is_active', true)->get(),
            'companyState' => Auth::user()->company->state?->name ?? 'Unknown',
        ]);
    }

    // =========================================================================
    //  WRITE
    // =========================================================================

    public function store(StoreQuotationRequest $request)
    {
        try {
            $quotation = $this->quotationService->createQuotation(
                $request->validated(),
                Auth::user()->company_id,
            );

            return redirect()->route('admin.quotations.show', $quotation->id)
                ->with('success', 'Quotation created successfully!');

        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Failed to create quotation: ' . $e->getMessage());
        }
    }

    public function update(UpdateQuotationRequest $request, Quotation $quotation)
    {
        abort_if($quotation->company_id !== Auth::user()->company_id, 403);

        try {
            $quotation = $this->quotationService->updateQuotation($quotation, $request->validated());

            return redirect()->route('admin.quotations.show', $quotation->id)
                ->with('success', 'Quotation updated successfully.');

        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Failed to update quotation: ' . $e->getMessage());
        }
    }

    public function destroy(Quotation $quotation)
    {
        abort_if($quotation->company_id !== Auth::user()->company_id, 403);

        if ($quotation->status === 'converted') {
            return back()->with('error', 'Cannot delete a quotation that has already been converted to an invoice.');
        }

        $quotation->delete();

        return redirect()->route('admin.quotations.index')
            ->with('success', 'Quotation archived successfully.');
    }

    // =========================================================================
    //  ACTIONS
    // =========================================================================

    public function markAsSent(Quotation $quotation)
    {
        abort_if($quotation->company_id !== Auth::user()->company_id, 403);

        $quotation->update([
            'status'  => 'sent',
            'is_sent' => true,
            'sent_at' => now(),
            'sent_by' => Auth::id(),
        ]);

        return back()->with('success', 'Quotation marked as sent.');
    }

    public function downloadPdf(Quotation $quotation)
    {
        abort_if($quotation->company_id !== Auth::user()->company_id, 403);

        $quotation->load(['items', 'customer', 'store', 'creator']);
        $company = $quotation->company ?? Auth::user()->company;

        $pdf = Pdf::loadView('admin.quotations.pdf', compact('quotation', 'company'));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('Quotation_' . $quotation->quotation_number . '.pdf');
    }

    public function convertToInvoice(Quotation $quotation)
    {
        abort_if($quotation->company_id !== Auth::user()->company_id, 403);

        if ($quotation->status === 'converted') {
            return back()->with('error', 'This quotation has already been converted to an invoice.');
        }

        try {
            $invoice = $this->quotationService->convertToInvoice($quotation);

            return redirect()->route('admin.invoices.edit', $invoice->id)
                ->with('success', 'Quotation successfully converted to a Draft Invoice! Please confirm to deduct stock.');

        } catch (Exception $e) {
            return back()->with('error', 'Failed to convert quotation: ' . $e->getMessage());
        }
    }
}