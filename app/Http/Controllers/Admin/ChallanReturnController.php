<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreChallanReturnRequest;
use App\Http\Requests\Admin\UpdateChallanReturnRequest;
use App\Models\Challan;
use App\Models\ChallanReturn;
use App\Services\ChallanReturnService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChallanReturnController extends Controller
{
    public function __construct(
        protected ChallanReturnService $returnService
    ) {}

    /**
     * Display a listing of the challan returns.
     */
    public function index(Request $request)
    {        

        $query = ChallanReturn::with(['challan', 'createdBy'])->latest();


        // Basic Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('return_number', 'like', "%{$search}%")
                ->orWhereHas('challan', function ($q) use ($search) {
                    $q->where('challan_number', 'like', "%{$search}%")
                        ->orWhere('party_name', 'like', "%{$search}%");
                });
        }

        // Status/Condition Filter
        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }

        $returns = $query->paginate(15)->withQueryString();

        return view('admin.challan-returns.index', compact('returns'));
    }

    /**
     * Show the form for creating a new return against a specific challan.
     */
    public function create(Request $request)
    {
        $challan = Challan::findOrFail($request->challan);
        // 1. Ensure the challan is physically returnable
        if (! $challan->is_returnable) {
            return redirect()->route('admin.challans.show', $challan->id)
                ->with('error', 'This challan is not marked as returnable.');
        }

        // 2. Load only line items that actually have pending quantities left
        $challan->load(['items' => function ($query) {
            $query->whereRaw('(qty_sent - qty_returned - qty_invoiced) > 0');
        }, 'items.product', 'items.productSku']);

        // 3. If everything is already settled, block the return
        if ($challan->items->isEmpty()) {
            return redirect()->route('admin.challans.show', $challan->id)
                ->with('error', 'All items on this challan have already been returned or invoiced.');
        }

        return view('admin.challan-returns.create', compact('challan'));
    }

    /**
     * Store a newly created return in storage.
     */
    public function store(StoreChallanReturnRequest $request)
    {
        try {
            $return = $this->returnService->processReturn($request->validated());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Return processed successfully.',
                    'redirect' => route('admin.challan-returns.show', $return->id),
                ]);
            }

            return redirect()->route('admin.challan-returns.show', $return->id)
                ->with('success', 'Challan return processed successfully.');

        } catch (Exception $e) {
            Log::error('Challan Return Failed: '.$e->getMessage());

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', 'Failed to process return: '.$e->getMessage());
        }
    }

    /**
     * Display the specified return.
     */
    public function show(ChallanReturn $challanReturn)
    {
        // Eager load nested relationships for the view
        $challanReturn->load([
            'challan.store',
            'items.challanItem.product',
            'items.challanItem.productSku',
            'createdBy',
        ]);

        return view('admin.challan-returns.show', compact('challanReturn'));
    }

    /**
     * Show the form for editing the specified return.
     * Only notes, condition, and damage_notes can be edited.
     */
    public function edit(ChallanReturn $challanReturn)
    {
        $challanReturn->load([
            'challan',
            'items.challanItem.product',
            'items.challanItem.productSku',
        ]);

        return view('admin.challan-returns.edit', compact('challanReturn'));
    }

    /**
     * Update the specified return in storage.
     */
    public function update(UpdateChallanReturnRequest $request, ChallanReturn $challanReturn)
    {
        try {
            $this->returnService->updateReturn($challanReturn, $request->validated());

            return redirect()->route('admin.challan-returns.show', $challanReturn->id)
                ->with('success', 'Return record updated successfully.');

        } catch (Exception $e) {
            Log::error('Return Update Failed: '.$e->getMessage());

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Download PDF for the Challan Return.
     */
    public function downloadPdf(ChallanReturn $challanReturn)
    {
        $challanReturn->load([
            'challan.store',
            'items.challanItem.product',
            'items.challanItem.productSku',
        ]);

        $pdf = Pdf::loadView('admin.challan-returns.pdf', compact('challanReturn'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('Return_'.$challanReturn->return_number.'.pdf');
    }
}
