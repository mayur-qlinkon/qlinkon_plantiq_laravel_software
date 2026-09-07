<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\State;
use App\Models\Store;
use App\Models\Setting;
use App\Models\User;
use Exception;
use App\Enums\Auth\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    /**
     * Helper to determine if the current tenant has a multi-store plan.
     * If true, they can override billing/bank settings at the store level.
     */
    private function isMultiStorePlan(): bool
    {
        $subscription = tenant_subscription();

        return $subscription && $subscription->plan && $subscription->plan->store_limit > 1;
    }

    /**
     * Display a listing of the stores.
     */
    public function index()
    {
        $storeIds = auth_store_ids();

        $stores = Store::with('state')
            ->when($storeIds !== null, fn ($q) => $q->whereIn('id', $storeIds)) // 🛡️ Store-level restriction
            ->latest()
            ->paginate(15);
        $canAddMore = check_plan_limit('stores');

        return view('admin.stores.index', compact('stores', 'canAddMore'));
    }

    /**
     * Show the form for creating a new store.
     */
    public function create()
    {
        if (! check_plan_limit('stores')) {
            return redirect()->route('admin.stores.index')
                ->with('error', 'You have reached your subscription limit for stores. Please upgrade your plan to add more.');
        }

        $states = State::where('is_active', true)->orderBy('name')->get();
        $isMultiStore = $this->isMultiStorePlan();

        // Fetched here rather than inside the blade so the form stays free of
        // queries, matching how $states is already supplied.
        $paymentMethods = PaymentMethod::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('admin.stores.create', compact('states', 'isMultiStore', 'paymentMethods'));
    }

    /**
     * Store a newly created store in storage.
     */
    public function store(Request $request)
    {
        if (! check_plan_limit('stores')) {
            abort(403, 'Store limit reached. Please upgrade your plan.');
        }

        $isMultiStore = $this->isMultiStorePlan();

        // 1. Basic Rules (Always Applied)
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state_id' => ['nullable', 'exists:states,id'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'is_active'          => ['nullable', 'boolean'],
            'storefront_enabled' => ['nullable', 'boolean'],
            'tagline'            => ['nullable', 'string', 'max:160'],
            'whatsapp'           => ['nullable', 'string', 'max:20'],
            'instagram'          => ['nullable', 'url', 'max:255'],
            'facebook'           => ['nullable', 'url', 'max:255'],
            'twitter'            => ['nullable', 'url', 'max:255'],
            'seo_title'          => ['nullable', 'string', 'max:160'],
            'seo_description'    => ['nullable', 'string', 'max:300'],
            'business_hours'     => ['nullable', 'string', 'max:500'],
            'logo'               => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'signature'          => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            // Domain & Subdomain — globally unique across all stores
            'domain'             => ['nullable', 'string', 'max:253', 'unique:stores,domain',
                                     'regex:/^(?:[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/'],
            'subdomain'          => ['nullable', 'string', 'max:63', 'unique:stores,subdomain',
                                     'regex:/^[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?$/'],
        ];

        // 2. Billing & Override Rules (Only Applied if Multi-Store Plan)
        if ($isMultiStore) {
            $rules = array_merge($rules, [
                'gst_number' => ['nullable', 'string', 'max:15'],
                'upi_id' => ['nullable', 'string', 'max:255'],
                'bank_name' => ['nullable', 'string', 'max:255'],
                'account_name' => ['nullable', 'string', 'max:255'],
                'account_number' => ['nullable', 'string', 'max:255'],
                'ifsc_code' => ['nullable', 'string', 'max:255'],
                'branch_name' => ['nullable', 'string', 'max:255'],
                'invoice_prefix' => ['nullable', 'string', 'max:10'],
                'quotation_prefix' => ['nullable', 'string', 'max:10'],
                'purchase_prefix' => ['nullable', 'string', 'max:10'],
                'next_invoice_number' => ['nullable', 'integer', 'min:1'],
                'default_tax_type' => ['nullable', 'string', 'max:50'],
                'default_payment_terms' => ['nullable', 'string', 'max:50'],
                // Scoped to the tenant so one company cannot point a store at
                // another company's payment method by posting its id. Soft
                // deletes and is_active are checked here because exists()
                // queries the table directly and skips model scopes.
                'default_payment_method_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('payment_methods', 'id')
                        ->where('company_id', Auth::user()->company_id)
                        ->where('is_active', true)
                        ->whereNull('deleted_at'),
                ],
                'round_off_amounts' => ['nullable', 'boolean'],
                'invoice_footer_note' => ['nullable', 'string'],
                'invoice_terms' => ['nullable', 'string'],
            ]);
        }

        $validated = $request->validate($rules);
        $validated['company_id'] = Auth::user()->company_id;
        $validated['is_active']          = $request->boolean('is_active', true);
    

        if ($isMultiStore) {
            $validated['round_off_amounts'] = $request->boolean('round_off_amounts', true);
        }

        // 🌟 IMAGE UPLOAD LOGIC (Add this right before DB::transaction)
        if ($request->hasFile('logo')) {
            // Replace with your ImageService if you have one
            $validated['logo'] = $request->file('logo')->store('stores/logos', 'public');
        }

        if ($request->hasFile('signature')) {
            $validated['signature'] = $request->file('signature')->store('stores/signatures', 'public');
        }

        try {
            DB::transaction(function () use ($validated) {
                $store = Store::create($validated);

                // Auto-attach every company admin so they can access the new store.
                $ownerIds = User::where('company_id', $validated['company_id'])
                    ->where('user_type', UserType::COMPANY_ADMIN)
                    ->pluck('id')
                    ->toArray();

                // Always include the creating user even if they have a non-owner role.
                $userIds = array_unique(array_merge($ownerIds, [Auth::id()]));

                $store->users()->syncWithoutDetaching($userIds);
            });

            return redirect()->route('admin.stores.index')
                ->with('success', 'Store branch created successfully.');

        } catch (Exception $e) {
            Log::error('Error creating store: '.$e->getMessage());

            return back()->withInput()
                ->with('error', 'An error occurred while creating the store. Please check the logs and try again.');
        }
    }

    /**
     * Display the specified store details.
     */
    public function show(Store $store)
    {
        // Security checks are mostly handled by Tenantable, but extra safety is good.
        if ($store->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized action.');
        }

        $storeIds = auth_store_ids();
        if ($storeIds !== null && !in_array($store->id, $storeIds)) {
            abort(403, 'Unauthorized store access.');
        }

        $isMultiStore = $this->isMultiStorePlan();

        return view('admin.stores.show', compact('store', 'isMultiStore'));
    }

    /**
     * Show the form for editing the specified store.
     */
    public function edit(Store $store)
    {
        if ($store->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized action.');
        }

        $storeIds = auth_store_ids();
        if ($storeIds !== null && !in_array($store->id, $storeIds)) {
            abort(403, 'Unauthorized store access.');
        }

        $states = State::where('is_active', true)->orderBy('name')->get();
        $isMultiStore = $this->isMultiStorePlan();

        // Config with no column of its own lives in store-scoped key-value
        // rows. Loaded once here so the form does not fire a query per field.
        $storeSettings = Setting::where('company_id', $store->company_id)
            ->where('store_id', $store->id)
            ->pluck('value', 'key');

        $paymentMethods = PaymentMethod::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('admin.stores.edit', compact('store', 'states', 'isMultiStore', 'storeSettings', 'paymentMethods'));
    }

    /**
     * Update the specified store in storage.
     */
    public function update(Request $request, Store $store)
    {
        if ($store->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized action.');
        }

        $storeIds = auth_store_ids();
        if ($storeIds !== null && !in_array($store->id, $storeIds)) {
            abort(403, 'Unauthorized store access.');
        }

        $isMultiStore = $this->isMultiStorePlan();

        // 1. Basic Rules
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state_id' => ['nullable', 'exists:states,id'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'is_active'          => ['nullable', 'boolean'],            
            'twitter'            => ['nullable', 'url', 'max:255'],
            'seo_title'          => ['nullable', 'string', 'max:160'],
            'seo_description'    => ['nullable', 'string', 'max:300'],
            'business_hours'     => ['nullable', 'string', 'max:500'],
            'logo'               => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'signature'          => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            // Domain & Subdomain — globally unique, ignore current store on update
            'domain'             => ['nullable', 'string', 'max:253', "unique:stores,domain,{$store->id}",
                                     'regex:/^(?:[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/'],
            'subdomain'          => ['nullable', 'string', 'max:63', "unique:stores,subdomain,{$store->id}",
                                     'regex:/^[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?$/'],
        ];

        // 2. Billing Rules
        //
        // Gated on the invoicing module, not the plan's store limit. Billing
        // now lives only on the store, so a single-store tenant would have
        // nowhere left to set their invoice prefix if this stayed multi-store.
        if (has_module('invoicing')) {
            $rules = array_merge($rules, [
                'gst_number' => ['nullable', 'string', 'max:15'],
                'upi_id' => ['nullable', 'string', 'max:255'],
                'bank_name' => ['nullable', 'string', 'max:255'],
                'account_name' => ['nullable', 'string', 'max:255'],
                'account_number' => ['nullable', 'string', 'max:255'],
                'ifsc_code' => ['nullable', 'string', 'max:255'],
                'branch_name' => ['nullable', 'string', 'max:255'],
                'invoice_prefix' => ['nullable', 'string', 'max:10'],
                'quotation_prefix' => ['nullable', 'string', 'max:10'],
                'purchase_prefix' => ['nullable', 'string', 'max:10'],
                'next_invoice_number' => ['nullable', 'integer', 'min:1'],
                'default_tax_type' => ['nullable', 'string', 'max:50'],
                'default_payment_terms' => ['nullable', 'string', 'max:50'],
                // Same tenant scoping as the create path.
                'default_payment_method_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('payment_methods', 'id')
                        ->where('company_id', Auth::user()->company_id)
                        ->where('is_active', true)
                        ->whereNull('deleted_at'),
                ],
                'round_off_amounts' => ['nullable', 'boolean'],
                'invoice_footer_note' => ['nullable', 'string'],
                'invoice_terms' => ['nullable', 'string'],
            ]);
        }

        // 3. Public storefront identity — only when the module is licensed.
        if (has_module('storefront')) {
            $rules = array_merge($rules, [
                'tagline' => ['nullable', 'string', 'max:160'],
                'description' => ['nullable', 'string'],
                'whatsapp' => ['nullable', 'string', 'max:20'],
                'instagram' => ['nullable', 'url', 'max:255'],
                'facebook' => ['nullable', 'url', 'max:255'],
                'map_embed_url' => ['nullable', 'url', 'max:255'],
                'storefront_enabled' => ['nullable', 'boolean'],
                'seo_keywords' => ['nullable', 'string', 'max:500'],
                'support_email' => ['nullable', 'email', 'max:255'],
                'youtube' => ['nullable', 'url', 'max:255'],
                'linkedin' => ['nullable', 'url', 'max:255'],
                'google' => ['nullable', 'url', 'max:255'],
            ]);
        }

        $rules['is_primary'] = ['nullable', 'boolean'];

        $validated = $request->validate($rules);

        // These have no column — they are written as key-value rows below.
        $validated = array_diff_key($validated, array_flip([
            'seo_keywords', 'support_email', 'youtube', 'linkedin', 'google',
        ]));

        $validated['is_active']          = $request->boolean('is_active', false);
        $validated['is_primary']         = $request->boolean('is_primary', false);

        if (has_module('invoicing')) {
            $validated['round_off_amounts'] = $request->boolean('round_off_amounts', false);
        }

        if (has_module('storefront')) {
            $validated['storefront_enabled'] = $request->boolean('storefront_enabled', false);
        }

        // 🌟 IMAGE UPLOAD LOGIC FOR UPDATE (Add this right before DB::transaction)
        if ($request->hasFile('logo')) {
            if ($store->logo) {
                Storage::disk('public')->delete($store->logo);
            }
            $validated['logo'] = $request->file('logo')->store('stores/logos', 'public');
        }

        if ($request->hasFile('signature')) {
            if ($store->signature) {
                Storage::disk('public')->delete($store->signature);
            }
            $validated['signature'] = $request->file('signature')->store('stores/signatures', 'public');
        }

        // Config without a column of its own. Kept separate from $validated so
        // it never reaches Store::update() as an unknown attribute.
        $kvKeys = has_module('storefront')
            ? ['seo_keywords', 'support_email', 'youtube', 'linkedin', 'google']
            : [];

        try {
            DB::transaction(function () use ($validated, $store, $kvKeys, $request) {
                $store->update($validated);

                foreach ($kvKeys as $key) {
                    if (! $request->has($key)) {
                        continue;
                    }

                    Setting::set(
                        $key,
                        $request->input($key) ?: null,
                        $store->company_id,
                        'storefront',
                        'text',
                        $store->id
                    );
                }
            });

            forget_settings_cache($store->company_id);

            return redirect()->route('admin.stores.index')
                ->with('success', 'Store branch updated successfully.');

        } catch (Exception $e) {
            Log::error('Error updating store: '.$e->getMessage());

            return back()->withInput()
                ->with('error', 'An error occurred while updating the store. Please try again.');
        }
    }

    /**
     * Switch current active store in session.
     * Validates the store belongs to the user (not just the company)
     * and that the user has the stores.switch permission.
     */
    public function switch(Request $request)
    {
        $user = Auth::user();

        // Permission check — owners pass automatically via has_permission()
        if (! has_permission('stores.switch')) {
            abort(403, 'You do not have permission to switch stores.');
        }

        $storeId = (int) $request->store_id;

        // Owners can switch to ANY store in their company.
        // Non-owners can only switch to their pivot-assigned stores.
        if (is_company_admin()) {
            $valid = Store::where('id', $storeId)
                ->where('company_id', $user->company_id)
                ->exists();
        } else {
            $valid = $user->stores()->where('stores.id', $storeId)->exists();
        }

        if (! $valid) {
            abort(403, 'You are not assigned to this store.');
        }

        session(['store_id' => $storeId]);

        return back();
    }

    /**
     * Remove the specified store from storage.
     */
    public function destroy(Store $store)
    {
        if ($store->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized action.');
        }

        $storeIds = auth_store_ids();
        if ($storeIds !== null && !in_array($store->id, $storeIds)) {
            abort(403, 'Unauthorized store access.');
        }

        // Optional safety net: Prevent deleting if it's their only active store
        $totalStores = Store::where('company_id', Auth::user()->company_id)->count();
        if ($totalStores <= 1) {
            return back()->with('error', 'You cannot delete your primary store. You must have at least one store active.');
        }

        try {
            // Note: If you have foreign key constraints (like invoices linked to a store),
            // you might want to soft-delete or handle that gracefully here.
            $store->delete();

            return redirect()->route('admin.stores.index')->with('success', 'Store branch deleted successfully.');

        } catch (Exception $e) {
            Log::error('Error deleting store: '.$e->getMessage());

            return back()->with('error', 'Failed to delete store. It may be linked to existing records.');
        }
    }
}
