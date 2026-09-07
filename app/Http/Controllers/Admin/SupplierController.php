<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSupplierRequest;
use App\Http\Requests\Admin\UpdateSupplierRequest;
use App\Models\State;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class SupplierController extends Controller
{
    /**
     * Display a listing of the suppliers.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status'); // 'active' | 'inactive' | null
        $registrationType = $request->input('registration_type'); // 'regular' | 'composition' | 'unregistered' | null

        $query = Supplier::with(['store', 'state'])->orderBy('created_at', 'desc');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('gstin', 'like', $like);
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if (in_array($registrationType, ['regular', 'composition', 'unregistered', 'overseas'], true)) {
            $query->where('registration_type', $registrationType);
        }

        $suppliers = $query->paginate(15)->withQueryString();

        $stores = Store::where('is_active', true)->get();
        $states = State::where('is_active', true)->orderBy('name')->get();

        return view('admin.suppliers.index', compact('suppliers', 'stores', 'states', 'search', 'status', 'registrationType'));
    }

    /**
     * Store a newly created supplier.
     */
    public function store(StoreSupplierRequest $request)
    {
        $validated = $request->validated();

        // 🌟 Set initial current balance based on opening balance
        $validated['current_balance'] = $validated['opening_balance'] ?? 0;

        $supplier = Supplier::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Supplier onboarded successfully!',
                'data' => $supplier,
            ]);
        }

        return redirect()->back()->with('success', 'Supplier onboarded successfully!');
    }

    /**
     * Update the specified supplier.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $validated = $request->validated();

        // Note: In a live ERP, updating opening balance after transactions exist requires recalculating the ledger.
        // For now, we update it as provided. If opening balance changed, we adjust current balance roughly.
        if (isset($validated['opening_balance']) && $supplier->opening_balance != $validated['opening_balance']) {
            $difference = $validated['opening_balance'] - $supplier->opening_balance;
            $validated['current_balance'] = $supplier->current_balance + $difference;
        }

        $supplier->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Supplier details updated!',
            ]);
        }

        return redirect()->back()->with('success', 'Supplier details updated!');
    }

    /**
     * Generate and download a PDF list of filtered suppliers.
     */
    public function downloadPdf(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status');
        $registrationType = $request->input('registration_type');

        // Eager load relations to match index query optimization
        $query = Supplier::with(['store', 'state'])->orderBy('created_at', 'desc');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('gstin', 'like', $like);
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if (in_array($registrationType, ['regular', 'composition', 'unregistered', 'overseas'], true)) {
            $query->where('registration_type', $registrationType);
        }

        // Get all matching records for the report context
        $suppliers = $query->get();
        
        $generatedAt = now()->format('d-M-Y h:i A');

        // Render the print blade view using dompdf
        $pdf = Pdf::loadView('admin.suppliers.pdf', compact(
            'suppliers', 
            'search', 
            'status', 
            'registrationType', 
            'generatedAt'
        ));

        // Use landscape orientation to accommodate financial & registration columns neatly
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('suppliers-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Remove the specified supplier (Soft Delete).
     */
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Supplier removed from active list.']);
        }

        return redirect()->back()->with('success', 'Supplier removed from active list.');
    }
}
