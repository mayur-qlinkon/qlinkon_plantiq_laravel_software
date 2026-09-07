<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SkuSearchContext;
use App\Http\Controllers\Admin\Concerns\SearchesSkus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreChallanRequest;
use App\Http\Requests\Admin\UpdateChallanRequest;
use App\Models\Challan;
use App\Models\Client;
use App\Models\State;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\ChallanService;
use App\Services\ProductService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ChallanController extends Controller
{
    use SearchesSkus;

    /**
     * Line-item picker search for delivery challans.
     *
     * Delivery context: stock levels are shown but not enforced, preserving the
     * existing behaviour where a challan may be prepared for goods still being
     * picked. Stock is validated on submit, not while searching.
     */
    public function searchSkus(Request $request, ProductService $productService)
    {
        return $this->respondWithSkuSearch($request, $productService, SkuSearchContext::Delivery);
    }
    public function __construct(
        protected ChallanService $challanService
    ) {}

    /**
     * Display a listing of the challans.
     */
    public function index(Request $request)
    {        

        $query = Challan::with(['store', 'client', 'supplier', 'branchStore'])            
            ->latest();

        // 1. Text Search (Challan Number, Party Name)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('challan_number', 'like', "%{$search}%")
                    ->orWhere('party_name', 'like', "%{$search}%");
            });
        }

        // 2. Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('challan_type')) {
            $query->where('challan_type', $request->challan_type);
        }

        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }

        // 3. Date Range Filter (challan_date)
        if ($request->filled('start_date')) {
            $query->whereDate('challan_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('challan_date', '<=', $request->end_date);
        }

        // Count for the selected range BEFORE pagination limits the result set.
        $filteredCount = ($request->filled('start_date') || $request->filled('end_date'))
            ? $query->count()
            : null;

        // 4. Paginate and append query string to preserve filters across pages
        $challans = $query->paginate(15)->withQueryString();

        return view('admin.challans.index', compact('challans', 'filteredCount'));
    }

    /**
     * Show the form for creating a new challan.
     */
    public function create()
    {
        // Fetch active masters for the dropdowns
        $clients = Client::where('is_active', true)->orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $stores = auth_stores()->orderBy('name')->get();
        $storeIds = auth_stores()->pluck('id');
        $warehouses = Warehouse::whereIn('store_id', $storeIds)->get();
        $units = Unit::where('is_active', true)->get();
        $states = State::orderBy('name')->get();

        $typeLabels = Challan::TYPE_LABELS;

        return view('admin.challans.create', [
            'clients' => $clients,
            'suppliers' => $suppliers,
            'stores' => $stores,
            'warehouses' => $warehouses,
            'units' => $units,
            'states' => $states,
            'typeLabels' => $typeLabels,
            'batchEnabled' => batch_enabled(),
        ]);
    }

    /**
     * Store a newly created challan in storage.
     */
    public function store(StoreChallanRequest $request)
    {
        try {
            $challan = $this->challanService->createChallan($request->validated());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Challan created successfully!',
                    'redirect' => route('admin.challans.show', $challan->id),
                ]);
            }

            return redirect()->route('admin.challans.show', $challan->id)
                ->with('success', 'Challan created successfully.');

        } catch (Exception $e) {
            Log::error('Challan Creation Failed: '.$e->getMessage());

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', 'Failed to create challan: '.$e->getMessage());
        }
    }

    /**
     * Display the specified challan.
     */
    public function show(Challan $challan)
    {
        // Eager load everything needed for the view/PDF template
        $challan->load([
            'store',
            'client',
            'supplier',
            'branchStore',
            'fromState',
            'toState',
            'createdBy',
            'dispatchedBy',
            'items.product',
            'items.productSku',
            // 'items.unit',
            'statusHistory.changedBy',
        ]);

        return view('admin.challans.show', compact('challan'));
    }

    /**
     * Show the form for editing the specified challan.
     */
    public function edit(Challan $challan)
    {
        // 🛡️ Note: We do NOT block the view here, because the user might just want to edit notes or dates.
        // The ChallanService strictly guards the `items` array from being edited if status is dispatched/received.

        $challan->load(['items.product', 'items.productSku']);

        $clients = Client::where('is_active', true)->orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $stores = auth_stores()->orderBy('name')->get();
        $storeIds = auth_stores()->pluck('id');
        $warehouses = Warehouse::whereIn('store_id', $storeIds)->get();
        $units = Unit::where('is_active', true)->get();
        $states = State::orderBy('name')->get();

        // 🌟 Root fix for date formats applied here so Alpine.js doesn't fail on UTC conversion
        $challan->items->each(function ($item) {
            if ($item->manufacturing_date) {
                $item->manufacturing_date = Carbon::parse($item->manufacturing_date)->format('Y-m-d');
            }
            if ($item->expiry_date) {
                $item->expiry_date = Carbon::parse($item->expiry_date)->format('Y-m-d');
            }
        });
        $typeLabels = Challan::TYPE_LABELS;

        return view('admin.challans.edit', [
            'challan' => $challan,
            'clients' => $clients,
            'suppliers' => $suppliers,
            'stores' => $stores,
            'warehouses' => $warehouses,
            'units' => $units,
            'states' => $states,
            'typeLabels' => $typeLabels,
            'batchEnabled' => batch_enabled(),
        ]);
    }

    /**
     * Update the specified challan in storage.
     */
    public function update(UpdateChallanRequest $request, Challan $challan)
    {
        try {
            $this->challanService->updateChallan($challan, $request->validated());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Challan updated successfully!',
                    'redirect' => route('admin.challans.show', $challan->id),
                ]);
            }

            return redirect()->route('admin.challans.show', $challan->id)
                ->with('success', 'Challan updated successfully.');

        } catch (Exception $e) {
            Log::error('Challan Update Failed: '.$e->getMessage());

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', 'Failed to update challan: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified challan from storage.
     * (Unrestricted deletion as requested)
     */
    public function destroy(Challan $challan)
    {
        try {
            DB::transaction(function () use ($challan) {
                // Explicitly delete items first to handle any DB-level strict foreign keys safely
                $challan->items()->delete();
                $challan->statusHistory()->delete();
                $challan->delete();
            });

            if (request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Challan deleted successfully.']);
            }

            return redirect()->route('admin.challans.index')
                ->with('success', 'Challan deleted successfully.');

        } catch (Exception $e) {
            Log::error('Challan Deletion Failed: '.$e->getMessage());

            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to delete challan.'], 500);
            }

            return back()->with('error', 'Failed to delete challan: '.$e->getMessage());
        }
    }

    /**
     * Download the PDF generation of the Challan.
     */
    public function downloadPdf(Challan $challan)
    {
        try {
            $challan->load([
                'store',
                'client',
                'supplier',
                'branchStore',
                'fromState',
                'toState',
                'items.product',
                'items.productSku',
            ]);

            $pdf = Pdf::loadView('admin.challans.pdf', compact('challan'))
                ->setPaper('a4', 'portrait');
            
            return $pdf->download('Challan_'.$challan->challan_number.'.pdf');
        }
        catch (Exception $e) {
            Log::error('Challan PDF Download Failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

    }

    /**
     * API Endpoint: Quick update status (e.g. marking "Dispatched" or "Delivered" from UI button)
     */
    public function updateStatus(Request $request, Challan $challan)
    {
        try {
            $request->validate([
                'status' => ['required', 'string', Rule::in(array_keys(Challan::STATUS_LABELS))],
                'notes' => ['nullable', 'string', 'max:500'],
            ]);

            // We securely pass just the status and notes to the service.
            // By NOT passing an 'items' array, the service's strict $isEditingItems guard remains false,
            // allowing the status to update, and correctly triggering the processInventoryMovement() if needed!
            $this->challanService->updateChallan($challan, [
                'status' => $request->status,
                'internal_notes' => $request->notes ?? $challan->internal_notes,
            ]);

            return redirect()
                ->route('admin.challans.index')
                ->with('success', 'Challan marked as '.Challan::STATUS_LABELS[$request->status].' successfully!');

        } catch (Exception $e) {
            Log::error('Challan Status Update Failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
