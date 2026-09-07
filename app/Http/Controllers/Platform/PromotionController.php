<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;

use App\Models\Platform\Promotion;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PromotionController extends Controller
{
    /**
     * Display a listing of the promotions.
     */
    public function index(Request $request)
    {
        // Super admin can see all promotions. 
        // Using withoutGlobalScope('tenant') because this is the platform admin panel.
        $promotions = Promotion::withoutGlobalScope('tenant')
            ->with('company') // Assuming you want to see which company it belongs to
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('platform.promotions.index', compact('promotions'));
    }

    /**
     * Show the form for creating a new promotion.
     */
    public function create()
    {
        return view('platform.promotions.create');
    }

    /**
     * Store a newly created promotion in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validatePromotion($request);

        // Platform admin creates platform-level (company_id = null) by default, 
        // unless they explicitly selected a company in the form.
        if (!isset($validated['company_id'])) {
            $validated['company_id'] = null;
        }

        Promotion::withoutGlobalScope('tenant')->create($validated);

        return redirect()->route('platform.promotions.index')
            ->with('success', 'Promotion created successfully.');
    }

    /**
     * Display the specified promotion.
     */
    public function show($id)
    {
        $promotion = Promotion::withoutGlobalScope('tenant')
            ->with('usages.client', 'usages.usable') // Eager load audit trails
            ->findOrFail($id);

        return view('platform.promotions.show', compact('promotion'));
    }

    /**
     * Show the form for editing the specified promotion.
     */
    public function edit($id)
    {
        $promotion = Promotion::withoutGlobalScope('tenant')->findOrFail($id);

        return view('platform.promotions.edit', compact('promotion'));
    }

    /**
     * Update the specified promotion in storage.
     */
    public function update(Request $request, $id)
    {
        $promotion = Promotion::withoutGlobalScope('tenant')->findOrFail($id);
        
        $validated = $this->validatePromotion($request, $promotion->id);

        if (!array_key_exists('company_id', $validated)) {
             $validated['company_id'] = $promotion->company_id;
        }

        $promotion->update($validated);

        return redirect()->route('platform.promotions.index')
            ->with('success', 'Promotion updated successfully.');
    }

    /**
     * Remove the specified promotion from storage.
     */
    public function destroy($id)
    {
        $promotion = Promotion::withoutGlobalScope('tenant')->findOrFail($id);
        
        // Prevent deletion if it has already been used to keep audit trails intact
        if ($promotion->times_used > 0) {
            return redirect()->route('platform.promotions.index')
                ->with('error', 'Cannot delete promotion because it has already been used. Consider deactivating it instead.');
        }

        $promotion->delete();

        return redirect()->route('platform.promotions.index')
            ->with('success', 'Promotion deleted successfully.');
    }

    /**
     * Centralized validation rules for DRYness.
     */
    private function validatePromotion(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'company_id'             => ['nullable', 'exists:companies,id'],
            'name'                   => ['required', 'string', 'max:255'],
            'code'                   => [
                'nullable', 
                'string', 
                'max:50', 
                Rule::unique('promotions', 'code')->ignore($ignoreId)
            ],
            'description'            => ['nullable', 'string'],
            'is_active'              => ['boolean'],
            'auto_apply'             => ['boolean'],
            'discount_type'          => ['required', Rule::in([
                Promotion::TYPE_FIXED,
                Promotion::TYPE_PERCENTAGE,
                Promotion::TYPE_FREE_TRIAL,
                Promotion::TYPE_FREE_MODULE,
                Promotion::TYPE_FREE_LIMIT,
            ])],
            'discount_value'         => ['nullable', 'numeric', 'min:0'],
            'max_discount'           => ['nullable', 'numeric', 'min:0'],
            'minimum_order_amount'   => ['nullable', 'numeric', 'min:0'],
            'maximum_order_amount'   => ['nullable', 'numeric', 'min:0'],
            'usage_limit'            => ['nullable', 'integer', 'min:1'],
            'usage_per_customer'     => ['nullable', 'integer', 'min:1'],
            'starts_at'              => ['nullable', 'date'],
            'expires_at'             => ['nullable', 'date', 'after_or_equal:starts_at'],
            'priority'               => ['nullable', 'integer', 'min:0'],
            
            // Flexible JSON fields validation (if you submit them via form)
            'reward'                 => ['nullable', 'array'],
            'conditions'             => ['nullable', 'array'],
        ]);
    }
}