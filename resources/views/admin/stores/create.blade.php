@extends('layouts.admin')

@section('title', 'Add New Branch')

@section('header-title')
    <h1 class="text-xs sm:text-sm font-bold text-gray-400 uppercase tracking-widest">Stores / Add Branch</h1>
@endsection

@section('content')
    {{-- 🌟 FIX: Removed max-w-5xl, using w-full to utilize the entire container width next to the sidebar --}}
    @php
        // String keys throughout — an integer-keyed array makes x-custom-select
        // reset to index 0 on render.
        $stateIdOptions = [];
        foreach ($states as $state) {
            $stateIdOptions[(string) $state->id] = $state->name;
        }

        $currencyOptions = ['INR' => '₹ INR (Rupee)'];

        $paymentMethodOptions = [];
        foreach ($paymentMethods as $method) {
            $paymentMethodOptions[(string) $method->id] = $method->label;
        }
    @endphp

    <div class="w-full mx-auto space-y-4 sm:space-y-6 pb-10" x-data="storeCreateForm()">

        {{-- Header & Back Button --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <p class="text-xs sm:text-sm text-gray-500 font-medium">Register a new physical or virtual branch for your
                    business.</p>
            </div>
            <a href="{{ route('admin.stores.index') }}"
                class="w-full sm:w-auto bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-4 py-2.5 rounded-xl text-sm font-bold transition-colors shadow-sm shrink-0 text-center">
                Back
            </a>
        </div>

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 sm:px-5 py-4 rounded-xl shadow-sm text-sm">
                <div class="font-bold flex items-center gap-2 mb-2">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i> Please fix the following errors:
                </div>
                <ul class="list-disc list-inside ml-2 sm:ml-6 space-y-1 text-xs sm:text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Main Form --}}
        <form action="{{ route('admin.stores.store') }}" method="POST" enctype="multipart/form-data"
            class="space-y-5 sm:space-y-6" @submit="isSubmitting = true">
            @csrf

            {{-- ========================================== --}}
            {{-- SECTION 1: IDENTITY & CONTACT              --}}
            {{-- ========================================== --}}
            <div class="bg-white p-5 sm:p-6 rounded-2xl shadow-sm border border-gray-100">
                <h2
                    class="text-sm sm:text-base font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2 flex items-center gap-2">
                    <i data-lucide="store" class="w-4 h-4 text-brand-500"></i> 1. Branch Identity
                </h2>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- Media Uploads --}}
                    <div class="lg:col-span-1 space-y-5">
                        {{-- Logo Upload --}}
                        <div>
                            <label
                                class="block text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Branch
                                Logo (Optional)</label>
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl border-2 border-dashed border-gray-300 overflow-hidden bg-gray-50 flex items-center justify-center shrink-0">
                                    <template x-if="logoPreview">
                                        <img :src="logoPreview" class="w-full h-full object-contain p-1">
                                    </template>
                                    <template x-if="!logoPreview">
                                        <i data-lucide="image" class="w-6 h-6 sm:w-8 sm:h-8 text-gray-300"></i>
                                    </template>
                                </div>
                                <label
                                    class="bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-600 px-3 py-2 rounded-lg text-xs font-bold cursor-pointer transition-colors shadow-sm">
                                    Browse Image
                                    <input type="file" name="logo" class="hidden"
                                        @change="previewImage($event, 'logoPreview')" accept="image/*">
                                </label>
                            </div>
                        </div>

                        {{-- Signature Upload --}}
                        <div>
                            <label
                                class="block text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Authorized
                                Signature (For PDFs)</label>
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                                <div
                                    class="w-32 h-12 rounded-lg border border-dashed border-gray-300 bg-gray-50 flex items-center justify-center overflow-hidden shrink-0">
                                    <template x-if="signaturePreview">
                                        <img :src="signaturePreview" class="w-full h-full object-contain">
                                    </template>
                                    <template x-if="!signaturePreview">
                                        <i data-lucide="pen-tool" class="w-4 h-4 text-gray-300"></i>
                                    </template>
                                </div>
                                <label
                                    class="bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-600 px-3 py-2 rounded-lg text-xs font-bold cursor-pointer transition-colors shadow-sm">
                                    Upload Sign
                                    <input type="file" name="signature" class="hidden"
                                        @change="previewImage($event, 'signaturePreview')" accept="image/*">
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Text Inputs --}}
                    <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                        <div class="sm:col-span-2">
                            <label
                                class="block text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Branch
                                Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                placeholder="e.g. Downtown Hub"
                                class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all shadow-sm">
                        </div>
                        <div>
                            <label
                                class="block text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Official
                                Email</label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                placeholder="branch@company.com"
                                class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all shadow-sm">
                        </div>
                        <div>
                            <label
                                class="block text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Contact
                                Phone</label>
                            <input type="tel" name="phone" x-model="phone" maxlength="10" placeholder="0000000000"
                                inputmode="numeric" @input="phone = phone.replace(/\D/g, '').slice(0,10)"
                                class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all shadow-sm">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========================================== --}}
            {{-- SECTION 2: LOCATION                        --}}
            {{-- ========================================== --}}
            <div class="bg-white p-5 sm:p-6 rounded-2xl shadow-sm border border-gray-100">
                <h2
                    class="text-sm sm:text-base font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2 flex items-center gap-2">
                    <i data-lucide="map-pin" class="w-4 h-4 text-brand-500"></i> 2. Location Details
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-5 mb-4 sm:mb-5">
                    <div>
                        <label
                            class="block text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">City</label>
                        <input type="text" name="city" value="{{ old('city') }}" placeholder="e.g. Ahmedabad"
                            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all shadow-sm">
                    </div>
                    <div>
                        <label
                            class="block text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">State</label>
                        <x-custom-select name="state_id" :options="$stateIdOptions" :selected="(string) old('state_id')" placeholder="Select State" />
                    </div>
                    <div>
                        <label
                            class="block text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Pincode
                            / Zip</label>
                        <input type="text" name="zip_code" x-model="zip" maxlength="6" placeholder="380001"
                            inputmode="numeric" @input="zip = zip.replace(/\D/g, '').slice(0,6)"
                            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all shadow-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Full
                        Street Address</label>
                    <textarea name="address" rows="2" placeholder="Shop number, building, street, landmark..."
                        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all resize-y shadow-sm">{{ old('address') }}</textarea>
                </div>
            </div>

            {{-- ========================================== --}}
            {{-- SECTION 3: MULTI-STORE BILLING OVERRIDES   --}}
            {{-- ========================================== --}}
            {{-- 🌟 FIX: We strictly check $isMultiStore. If false, this whole block is utterly invisible. --}}
            @if ($isMultiStore)
                <div class="bg-white p-5 sm:p-6 rounded-2xl shadow-sm border border-gray-100 space-y-8">

                    {{-- Compliance & Finance --}}
                    <div>
                        <h2
                            class="text-sm sm:text-base font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2 flex items-center gap-2">
                            <i data-lucide="receipt" class="w-4 h-4 text-brand-500"></i> 3. Billing & Compliance <span
                                class="text-gray-400 font-normal text-xs">(Overrides Global Settings)</span>
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                            <div>
                                <label
                                    class="block text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">GSTIN</label>
                                <input type="text" name="gst_number" value="{{ old('gst_number') }}"
                                    placeholder="24AAAAA0000A1Z5" maxlength="15"
                                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm uppercase font-mono focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all shadow-sm">
                            </div>
                            <div>
                                <label
                                    class="block text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Store
                                    UPI ID</label>
                                <input type="text" name="upi_id" value="{{ old('upi_id') }}"
                                    placeholder="store@bank"
                                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all shadow-sm">
                            </div>
                            <div>
                                <label
                                    class="block text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Currency</label>
                                {{-- Defaults to INR explicitly. The old markup relied on
                                     the browser showing the first option, which looked
                                     selected without any value actually being set. --}}
                                <x-custom-select name="currency" :options="$currencyOptions" :selected="old('currency', 'INR')"
                                    placeholder="Select currency" />
                            </div>
                            <div>
                                <label
                                    class="block text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Invoice
                                    Prefix</label>
                                <input type="text" name="invoice_prefix" value="{{ old('invoice_prefix') }}"
                                    placeholder="e.g. BOM-INV-"
                                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm uppercase font-mono focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all shadow-sm">
                            </div>
                            <div>
                                <label
                                    class="block text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Quotation
                                    Prefix</label>
                                <input type="text" name="quotation_prefix" value="{{ old('quotation_prefix') }}"
                                    placeholder="e.g. BOM-QTN-"
                                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm uppercase font-mono focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all shadow-sm">
                            </div>
                            <div>
                                <label
                                    class="block text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Next
                                    Invoice Number</label>
                                <input type="number" name="next_invoice_number"
                                    value="{{ old('next_invoice_number') }}" min="1" placeholder="e.g. 1001"
                                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-mono focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all shadow-sm">
                                <p class="text-[10px] text-gray-400 mt-1">Starting number for this store's invoices. Leave
                                    blank to use company default.</p>
                            </div>
                            <div>
                                <label
                                    class="block text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Default
                                    Payment Method</label>
                                <x-custom-select name="default_payment_method_id" :options="$paymentMethodOptions" :selected="(string) old('default_payment_method_id')"
                                    placeholder="No default" />
                                <p class="text-[10px] text-gray-400 mt-1">Pre-selected on POS and invoices for this branch.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Bank Details --}}
                    <div>
                        <h2
                            class="text-sm sm:text-base font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2 flex items-center gap-2">
                            <i data-lucide="building-2" class="w-4 h-4 text-brand-500"></i> 4. Bank Account Details
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                            <div class="md:col-span-2 lg:col-span-1">
                                <label
                                    class="block text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Bank
                                    Name</label>
                                <input type="text" name="bank_name" value="{{ old('bank_name') }}"
                                    placeholder="e.g. HDFC Bank"
                                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all shadow-sm">
                            </div>
                            <div class="md:col-span-1">
                                <label
                                    class="block text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Account
                                    Name</label>
                                <input type="text" name="account_name" value="{{ old('account_name') }}"
                                    placeholder="e.g. Acme Corp Ltd"
                                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all shadow-sm">
                            </div>
                            <div class="md:col-span-1">
                                <label
                                    class="block text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Account
                                    Number</label>
                                <input type="text" name="account_number" value="{{ old('account_number') }}"
                                    placeholder="e.g. 502000123456"
                                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-mono focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all shadow-sm">
                            </div>
                            <div class="md:col-span-1">
                                <label
                                    class="block text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">IFSC
                                    Code</label>
                                <input type="text" name="ifsc_code" value="{{ old('ifsc_code') }}"
                                    placeholder="e.g. HDFC0001234"
                                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm uppercase font-mono focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all shadow-sm">
                            </div>
                            <div class="md:col-span-1">
                                <label
                                    class="block text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Branch
                                    Name</label>
                                <input type="text" name="branch_name" value="{{ old('branch_name') }}"
                                    placeholder="e.g. CG Road Branch"
                                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all shadow-sm">
                            </div>
                        </div>
                    </div>

                    {{-- Invoice Content --}}
                    <div>
                        <h2
                            class="text-sm sm:text-base font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2 flex items-center gap-2">
                            <i data-lucide="file-text" class="w-4 h-4 text-brand-500"></i> 5. Invoice Content
                        </h2>
                        <div class="space-y-4">
                            <div>
                                <label
                                    class="block text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Invoice
                                    Footer Note</label>
                                <textarea name="invoice_footer_note" rows="2" placeholder="e.g. Thank you for your business!"
                                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all resize-y shadow-sm">{{ old('invoice_footer_note') }}</textarea>
                            </div>
                            <div>
                                <label
                                    class="block text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Terms
                                    & Conditions</label>
                                <textarea name="invoice_terms" rows="3" placeholder="1. Goods once sold will not be taken back..."
                                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all resize-y shadow-sm">{{ old('invoice_terms') }}</textarea>
                            </div>
                        </div>
                    </div>

                </div>
            @endif

            {{-- ========================================== --}}
            {{-- SECTION 3: SETTINGS & SUBMIT               --}}
            {{-- ========================================== --}}
            <div
                class="bg-white p-5 sm:p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-5 sticky bottom-0 z-20">

                {{-- Active Toggle --}}
                <label class="inline-flex items-center cursor-pointer shrink-0">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="sr-only peer"
                        {{ old('is_active', true) ? 'checked' : '' }}>
                    <div
                        class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-500">
                    </div>
                    <div class="ms-3">
                        <span class="block text-[13px] font-bold text-gray-800">Active Branch</span>
                        <span class="block text-[11px] text-gray-400 font-normal">Visible for billing & inventory</span>
                    </div>
                </label>

                {{-- Submit Button --}}
                <button type="submit" :disabled="isSubmitting"
                    class="w-full sm:w-auto bg-brand-500 hover:bg-brand-600 text-white px-8 py-3 rounded-xl text-sm font-bold shadow-md shadow-brand-500/20 flex items-center justify-center gap-2 transition-all disabled:opacity-70 active:scale-95 shrink-0">
                    <i data-lucide="save" class="w-4 h-4" x-show="!isSubmitting"></i>
                    <i data-lucide="loader-2" class="w-4 h-4 animate-spin" x-show="isSubmitting" x-cloak></i>
                    <span x-text="isSubmitting ? 'Registering...' : 'Register Branch'"></span>
                </button>

            </div>

        </form>
    </div>
@endsection

@push('scripts')
    <script>
        function storeCreateForm() {
            return {
                isSubmitting: false,
                phone: '{{ old('phone') }}',
                zip: '{{ old('zip_code') }}',
                subdomain: '{{ old('subdomain') }}',
                logoPreview: null,
                signaturePreview: null,

                previewImage(event, target) {
                    const file = event.target.files[0];
                    if (file) {
                        this[target] = URL.createObjectURL(file);
                    }
                }
            }
        }
    </script>
@endpush
