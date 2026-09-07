<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductStock;
use App\Models\State;
use App\Models\Store;
use App\Models\Warehouse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the warehouses.
     */
    public function index()
    {
        $storeIds = active_store_ids(); // 🛡️ Fetch authorized stores

        $warehouses = Warehouse::with('store')            
            ->when($storeIds !== null, fn ($q) => $q->whereIn('store_id', $storeIds)) // 🛡️ Multi-Store Security
            ->latest()
            ->paginate(15);

        return view('admin.warehouses.index', compact('warehouses'));
    }

    /**
     * Show the form for creating a new warehouse.
     */
    public function create()
    {
        $stores = Store::where('company_id', Auth::user()->company_id)
            ->where('is_active', true)
            ->get();

        $states = State::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.warehouses.create', compact('stores', 'states'));
    }

    /**
     * Store a newly created warehouse in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => ['required', Rule::exists('stores', 'id')->where('company_id', Auth::user()->company_id)],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            // Nullable in the schema and nullable on update, so a warehouse
            // could be edited without a phone but not created without one.
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state_id' => ['nullable', 'exists:states,id'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_default'] = $request->boolean('is_default', false);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['company_id'] = Auth::user()->company_id;

        try {
            DB::transaction(function () use ($validated) {
                // LOGIC: If this is set as default, remove default status from all other warehouses in this store
                if ($validated['is_default']) {
                    Warehouse::where('store_id', $validated['store_id'])
                        ->where('company_id', $validated['company_id'])
                        ->update(['is_default' => false]);
                }

                Warehouse::create($validated);
            });

            return redirect()->route('admin.warehouses.index')->with('success', 'Warehouse created successfully.');

        } catch (Exception $e) {
            Log::error('Error creating warehouse: '.$e->getMessage());

            return back()->withInput()->with('error', 'An error occurred while creating the warehouse. Please try again.');
        }
    }

    /**
     * Display the specified warehouse details and its aggregated stock.
     */
    public function show(Warehouse $warehouse)
    {
        // Security check
        if ($warehouse->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized action.');
        }

        // Query the aggregated stock for this specific warehouse
        $stocksQuery = ProductStock::with([
            'sku.product.category',
            'sku.product.media' => function ($query) {
                $query->where('is_primary', true)->where('media_type', 'image');
            },
            'sku.unit',
            'sku.product.saleUnit',
            'sku.product.productUnit',
        ])
            ->where('warehouse_id', $warehouse->id)
            ->where('qty', '>', 0); // Hide zero-stock items from the UI

        // Inject dynamic batch tracking if enabled globally
        if (function_exists('batch_enabled') && batch_enabled()) {
            $stocksQuery->with(['sku.batches' => function ($query) use ($warehouse) {
                $query->where('warehouse_id', $warehouse->id)
                    ->where('remaining_qty', '>', 0)
                    ->where('is_active', true)
                    ->orderBy('expiry_date', 'asc'); // FEFO ordering
            }]);
        }

        $stocks = $stocksQuery->paginate(20);

        return view('admin.warehouses.show', compact('warehouse', 'stocks'));
    }

    /**
     * Show the form for editing the specified warehouse.
     */
    public function edit(Warehouse $warehouse)
    {
        if ($warehouse->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized action.');
        }

        $stores = Store::where('company_id', Auth::user()->company_id)
            ->where('is_active', true)
            ->get();

        $states = State::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.warehouses.edit', compact('warehouse', 'stores', 'states'));
    }

    /**
     * Update the specified warehouse in storage.
     */
    public function update(Request $request, Warehouse $warehouse)
    {
        if ($warehouse->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'store_id' => ['required', Rule::exists('stores', 'id')->where('company_id', Auth::user()->company_id)],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state_id' => ['nullable', 'exists:states,id'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_default'] = $request->boolean('is_default', false);
        $validated['is_active'] = $request->boolean('is_active', false);

        try {
            DB::transaction(function () use ($validated, $warehouse) {
                if ($validated['is_default']) {
                    Warehouse::where('store_id', $validated['store_id'])
                        ->where('company_id', Auth::user()->company_id)
                        ->where('id', '!=', $warehouse->id)
                        ->update(['is_default' => false]);
                }

                $warehouse->update($validated);
            });

            return redirect()->route('admin.warehouses.index')->with('success', 'Warehouse updated successfully.');

        } catch (Exception $e) {
            Log::error('Error updating warehouse: '.$e->getMessage());

            return back()->withInput()->with('error', 'An error occurred while updating the warehouse. Please try again.');
        }
    }

    /**
     * Remove the specified warehouse from storage.
     */
    public function destroy(Warehouse $warehouse)
    {
        if ($warehouse->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized action.');
        }

        if ($warehouse->is_default) {
            return back()->with('error', 'You cannot delete the default warehouse. Please set another warehouse as default first.');
        }

        // Deleting a warehouse that still holds stock would take that stock out
        // of every total while the goods are still on the shelf. Soft deletes
        // do not help: the rows survive but the warehouse disappears from the
        // switcher, so nobody can move the stock out afterwards.
        //
        // Checked here rather than left to the foreign key, because
        // product_stocks keeps a row per SKU per warehouse even at zero
        // quantity — the constraint would refuse warehouses that are genuinely
        // empty, and the message would not say why.
        if ($warehouse->hasStockOnHand()) {
            $count = $warehouse->stockOnHandCount();

            return back()->with('error',
                "This warehouse still holds stock for {$count} ".($count === 1 ? 'product' : 'products')
                .'. Transfer or adjust the stock to zero before deleting it.'
            );
        }

        try {
            $warehouse->delete();

            return redirect()->route('admin.warehouses.index')->with('success', 'Warehouse deleted successfully.');
        } catch (Exception $e) {
            Log::error('Error deleting warehouse: '.$e->getMessage());

            return back()->with('error', 'Failed to delete warehouse. It may be linked to existing inventory records.');
        }
    }
}
