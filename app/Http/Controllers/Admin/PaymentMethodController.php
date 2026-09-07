<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
class PaymentMethodController extends Controller
{
    /**
     * Display the single-page CRUD view with all payment methods.
     */
    public function index()
    {        

        $paymentMethods = PaymentMethod::where('company_id', Auth::user()->company_id)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.payment_methods', compact('paymentMethods'));
    }

    /**
     * Store a newly created payment method in storage.
     */
    public function store(Request $request)
    {        

        // 🔥 generate slug BEFORE validation
        $slug = $request->slug 
            ? Str::slug($request->slug) 
            : Str::slug($request->label);

        // 🛡️ Inject the generated slug back into the request so Laravel validates the correct data
        $request->merge(['slug' => $slug]);

        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'slug' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('payment_methods')->where(function ($q) use ($slug) {
                    return $q->where('company_id', Auth::user()->company_id)
                            ->where('slug', $slug)
                            ->whereNull('deleted_at');
                })
            ],
            'gateway' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
        ], [
            'slug.unique' => 'This payment method already exists.',
        ]);

        // Auto-generate slug from label if not provided
        $validated['slug'] = $slug;

        // Handle checkboxes safely (works for both JSON and standard forms)
        $validated['is_online'] = $request->boolean('is_online');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['company_id'] = Auth::user()->company_id;        

        try {
            $paymentMethod = PaymentMethod::create($validated);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please correct the highlighted fields.',
                    'errors'  => ['slug' => ['This payment method already exists.']]
                ], 422);
            }
            return back()->withInput()->withErrors([
                'slug' => 'This payment method already exists.'
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment method created successfully.',
                'data' => $paymentMethod,
            ]);
        }

        return back()->with('success', 'Payment method created successfully.');
    }

    /**
     * Update the specified payment method in storage.
     */
    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        

        // 🔥 normalize slug BEFORE validation. If empty, generate from label.
        $slug = $request->slug ? Str::slug($request->slug) : Str::slug($request->label);

        // 🛡️ Inject the generated slug back into the request so Laravel validates the correct data
        $request->merge(['slug' => $slug]);

        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'slug' => [
                'required',
                'string',
                'max:50',
                Rule::unique('payment_methods')
                    ->where(function ($q) use ($slug) {
                        return $q->where('company_id', Auth::user()->company_id)
                                ->where('slug', $slug)
                                ->whereNull('deleted_at');
                    })
                    ->ignore($paymentMethod->id)
            ],
            'gateway' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
        ], [
            'slug.unique' => 'This payment method already exists.',
        ]);

        $validated['slug'] = $slug;
        $validated['is_online'] = $request->boolean('is_online');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;                

        $validated['company_id'] = Auth::user()->company_id;        

        try {
            $paymentMethod->update($validated);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please correct the highlighted fields.',
                    'errors'  => ['slug' => ['This payment method already exists.']]
                ], 422);
            }
            return back()->withInput()->withErrors([
                'slug' => 'This payment method already exists for this store.'
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment method updated successfully.',
                'data' => $paymentMethod,
            ]);
        }

        return back()->with('success', 'Payment method updated successfully.');
    }

    /**
     * Remove the specified payment method from storage.
     */
    /**
     * Remove the specified payment method from storage.
     */
    public function destroy(Request $request, PaymentMethod $paymentMethod)
    {    

        try {
            $paymentMethod->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            // Error 23000 / 1451 means a foreign key constraint failed
            if ($e->getCode() == 23000) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete this method because it has been used for previous payments. Please disable it instead.',
                    ], 422);
                }
                return back()->with('error', 'Cannot delete this method because it has been used for previous payments. Please disable it instead.');
            }
            
            // Re-throw if it's a different database error
            throw $e;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment method deleted successfully.',
            ]);
        }

        return back()->with('success', 'Payment method deleted successfully.');
    }
}
