@extends ('layouts.admin')

@section('title', 'Edit Branch')

@section('header-title')
    <h1 class="text-xs font-bold tracking-widest text-gray-400 uppercase sm:text-sm">Stores / Edit Branch</h1>
@endsection

@section('content')
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

    <div class="mx-auto w-full space-y-4 pb-10 sm:space-y-6" x-data="storeEditForm('{{ $store->logo ? asset('storage/' . $store->logo) : ($store->company?->logo ? asset('storage/' . $store->company->logo) : '') }}', '{{ $store->signature ? asset('storage/' . $store->signature) : '' }}')">
        {{-- Header & Back Button --}}
        <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <p class="text-xs font-medium text-gray-500 sm:text-sm">Update the details and configuration for this branch.
                </p>
            </div>
            <a href="{{ route('admin.stores.index') }}"
                class="w-full shrink-0 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-center text-sm font-bold text-gray-700 shadow-sm transition-colors hover:bg-gray-50 sm:w-auto">
                Back
            </a>
        </div>

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-600 shadow-sm sm:px-5">
                <div class="mb-2 flex items-center gap-2 font-bold">
                    <i data-lucide="alert-circle" class="h-4 w-4"></i> Please fix the following mistakes:
                </div>
                <ul class="ml-2 list-inside list-disc space-y-1 text-xs sm:ml-6 sm:text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Main Form --}}
        <form action="{{ route('admin.stores.update', $store->id) }}" method="POST" enctype="multipart/form-data"
            class="space-y-5 pb-24 sm:space-y-6" @submit="isSubmitting = true">
            @csrf
            @method ('PUT')

            {{-- Section numbers are assigned as sections render, so hiding a
                 module-gated block never leaves a gap in the sequence. --}}
            @php($sectionNo = 0)

            {{-- ========================================== --}}
            {{-- SECTION 1: IDENTITY & CONTACT              --}}
            {{-- ========================================== --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-6">
                <h2
                    class="mb-4 flex items-center gap-2 border-b border-gray-100 pb-2 text-sm font-bold text-gray-800 sm:text-base">
                    <i data-lucide="store" class="text-brand-500 h-4 w-4"></i> {{ ++$sectionNo }}. Branch Identity
                </h2>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {{-- Media Uploads --}}
                    <div class="space-y-5 lg:col-span-1">
                        {{-- Logo Upload --}}
                        <div>
                            <label
                                class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Branch
                                Logo (Optional)</label>
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 sm:h-20 sm:w-20">
                                    <template x-if="logoPreview">
                                        <img :src="logoPreview" class="h-full w-full object-contain p-1" />
                                    </template>
                                    <template x-if="!logoPreview">
                                        <i data-lucide="image" class="h-6 w-6 text-gray-300 sm:h-8 sm:w-8"></i>
                                    </template>
                                </div>
                                <label
                                    class="cursor-pointer rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-bold text-gray-600 shadow-sm transition-colors hover:bg-gray-100">
                                    Change Image
                                    <input type="file" name="logo" class="hidden"
                                        @change="previewImage($event, 'logoPreview')" accept="image/*" />
                                </label>
                            </div>
                        </div>

                        {{-- Signature Upload --}}
                        <div>
                            <label
                                class="mb-2 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Authorized
                                Signature (For PDFs)</label>
                            <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-center">
                                <div
                                    class="flex h-12 w-32 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-dashed border-gray-300 bg-gray-50">
                                    <template x-if="signaturePreview">
                                        <img :src="signaturePreview" class="h-full w-full object-contain" />
                                    </template>
                                    <template x-if="!signaturePreview">
                                        <i data-lucide="pen-tool" class="h-4 w-4 text-gray-300"></i>
                                    </template>
                                </div>
                                <label
                                    class="cursor-pointer rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-bold text-gray-600 shadow-sm transition-colors hover:bg-gray-100">
                                    Update Sign
                                    <input type="file" name="signature" class="hidden"
                                        @change="previewImage($event, 'signaturePreview')" accept="image/*" />
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Text Inputs --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:col-span-2">
                        <div class="sm:col-span-2">
                            <label
                                class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Branch
                                Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $store->name) }}" required
                                placeholder="e.g. Downtown Hub"
                                class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1" />
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Official
                                Email</label>
                            <input type="email" name="email" value="{{ old('email', $store->email) }}"
                                placeholder="branch@company.com"
                                class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1" />
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Contact
                                Phone</label>
                            <input type="tel" name="phone" x-model="phone" maxlength="10" placeholder="0000000000"
                                inputmode="numeric" @input="phone = phone.replace(/\D/g, '').slice(0, 10)"
                                class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========================================== --}}
            {{-- SECTION 2: LOCATION                        --}}
            {{-- ========================================== --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-6">
                <h2
                    class="mb-4 flex items-center gap-2 border-b border-gray-100 pb-2 text-sm font-bold text-gray-800 sm:text-base">
                    <i data-lucide="map-pin" class="text-brand-500 h-4 w-4"></i> {{ ++$sectionNo }}. Location Details
                </h2>
                <div class="mb-4 grid grid-cols-1 gap-4 sm:mb-5 sm:grid-cols-3 sm:gap-5">
                    <div>
                        <label
                            class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">City</label>
                        <input type="text" name="city" value="{{ old('city', $store->city) }}"
                            placeholder="e.g. Ahmedabad"
                            class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1" />
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">State</label>
                        <x-custom-select name="state_id" :options="$stateIdOptions" :selected="(string) old('state_id', $store->state_id)" placeholder="Select State" />
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Pincode
                            / Zip</label>
                        <input type="text" name="zip_code" x-model="zip" maxlength="6" placeholder="380001"
                            inputmode="numeric" @input="zip = zip.replace(/\D/g, '').slice(0, 6)"
                            class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1" />
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Full
                        Street Address</label>
                    <textarea name="address" rows="2" placeholder="Shop number, building, street, landmark..."
                        class="focus:border-brand-500 focus:ring-brand-500 w-full resize-y rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1">{{ old('address', $store->address) }}</textarea>
                </div>
            </div>

            {{-- ========================================== --}}
            {{-- SECTION 3: MULTI-STORE BILLING OVERRIDES   --}}
            {{-- ========================================== --}}
            @if (has_module('invoicing'))
                <div class="space-y-8 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-6">
                    {{-- Compliance & Finance --}}
                    <div>
                        <h2
                            class="mb-4 flex items-center gap-2 border-b border-gray-100 pb-2 text-sm font-bold text-gray-800 sm:text-base">
                            <i data-lucide="receipt" class="text-brand-500 h-4 w-4"></i> {{ ++$sectionNo }}. Billing &
                            Compliance
                        </h2>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3">
                            <div>
                                <label
                                    class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">GSTIN</label>
                                <input type="text" name="gst_number"
                                    value="{{ old('gst_number', $store->gst_number) }}" placeholder="24AAAAA0000A1Z5"
                                    maxlength="15"
                                    class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 font-mono text-sm uppercase shadow-sm transition-all outline-none focus:ring-1" />
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Store
                                    UPI ID</label>
                                <input type="text" name="upi_id" value="{{ old('upi_id', $store->upi_id) }}"
                                    placeholder="store@bank"
                                    class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1" />
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Currency</label>
                                {{-- Falls back to INR for stores saved before this
                                     column had a value. The old markup relied on the
                                     browser showing the first option, which looked
                                     selected without any value actually being set. --}}
                                <x-custom-select name="currency" :options="$currencyOptions" :selected="old('currency', $store->currency) ?: 'INR'"
                                    placeholder="Select currency" />
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Invoice
                                    Prefix</label>
                                <input type="text" name="invoice_prefix"
                                    value="{{ old('invoice_prefix', $store->getRawOriginal('invoice_prefix')) }}"
                                    placeholder="e.g. BOM-INV-"
                                    class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 font-mono text-sm uppercase shadow-sm transition-all outline-none focus:ring-1" />
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Quotation
                                    Prefix</label>
                                <input type="text" name="quotation_prefix"
                                    value="{{ old('quotation_prefix', $store->getRawOriginal('quotation_prefix')) }}"
                                    placeholder="e.g. BOM-QTN-"
                                    class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 font-mono text-sm uppercase shadow-sm transition-all outline-none focus:ring-1" />
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Next
                                    Invoice Number</label>
                                <input type="number" name="next_invoice_number"
                                    value="{{ old('next_invoice_number', $store->getRawOriginal('next_invoice_number')) }}"
                                    min="1" placeholder="e.g. 1001"
                                    class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 font-mono text-sm shadow-sm transition-all outline-none focus:ring-1" />
                                <p class="mt-1 text-[10px] text-gray-400">Starting number for this store's invoices.</p>
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Default
                                    Payment Method</label>
                                <x-custom-select name="default_payment_method_id" :options="$paymentMethodOptions" :selected="(string) old('default_payment_method_id', $store->default_payment_method_id)"
                                    placeholder="No default" />
                                <p class="mt-1 text-[10px] text-gray-400">Pre-selected on POS and invoices for this branch.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Bank Details --}}
                    <div>
                        <h2
                            class="mb-4 flex items-center gap-2 border-b border-gray-100 pb-2 text-sm font-bold text-gray-800 sm:text-base">
                            <i data-lucide="building-2" class="text-brand-500 h-4 w-4"></i> {{ ++$sectionNo }}. Bank
                            Account Details
                        </h2>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3">
                            <div class="sm:col-span-2 lg:col-span-1">
                                <label
                                    class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Bank
                                    Name</label>
                                <input type="text" name="bank_name"
                                    value="{{ old('bank_name', $store->getRawOriginal('bank_name')) }}"
                                    placeholder="e.g. HDFC Bank"
                                    class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1" />
                            </div>
                            <div class="sm:col-span-1">
                                <label
                                    class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Account
                                    Name</label>
                                <input type="text" name="account_name"
                                    value="{{ old('account_name', $store->getRawOriginal('account_name')) }}"
                                    placeholder="e.g. Acme Corp Ltd"
                                    class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1" />
                            </div>
                            <div class="sm:col-span-1">
                                <label
                                    class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Account
                                    Number</label>
                                <input type="text" name="account_number"
                                    value="{{ old('account_number', $store->getRawOriginal('account_number')) }}"
                                    placeholder="e.g. 502000123456"
                                    class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 font-mono text-sm shadow-sm transition-all outline-none focus:ring-1" />
                            </div>
                            <div class="sm:col-span-1">
                                <label
                                    class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">IFSC
                                    Code</label>
                                <input type="text" name="ifsc_code"
                                    value="{{ old('ifsc_code', $store->getRawOriginal('ifsc_code')) }}"
                                    placeholder="e.g. HDFC0001234"
                                    class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 font-mono text-sm uppercase shadow-sm transition-all outline-none focus:ring-1" />
                            </div>
                            <div class="sm:col-span-1">
                                <label
                                    class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Branch
                                    Name</label>
                                <input type="text" name="branch_name"
                                    value="{{ old('branch_name', $store->getRawOriginal('branch_name')) }}"
                                    placeholder="e.g. CG Road Branch"
                                    class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1" />
                            </div>
                        </div>
                    </div>

                    {{-- Invoice Content --}}
                    <div>
                        <h2
                            class="mb-4 flex items-center gap-2 border-b border-gray-100 pb-2 text-sm font-bold text-gray-800 sm:text-base">
                            <i data-lucide="file-text" class="text-brand-500 h-4 w-4"></i> {{ ++$sectionNo }}. Invoice
                            Content
                        </h2>
                        <div class="space-y-4">
                            <div>
                                <label
                                    class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Invoice
                                    Footer Note</label>
                                <textarea name="invoice_footer_note" rows="2" placeholder="e.g. Thank you for your business!"
                                    class="focus:border-brand-500 focus:ring-brand-500 w-full resize-y rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1">{{ old('invoice_footer_note', $store->getRawOriginal('invoice_footer_note')) }}</textarea>
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Terms
                                    & Conditions</label>
                                <textarea name="invoice_terms" rows="3" placeholder="1. Goods once sold will not be taken back..."
                                    class="focus:border-brand-500 focus:ring-brand-500 w-full resize-y rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1">{{ old('invoice_terms', $store->getRawOriginal('invoice_terms')) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if (has_module('storefront'))
                {{-- ========================================== --}}
                {{-- SECTION 5: PUBLIC STOREFRONT               --}}
                {{-- ========================================== --}}
                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-6">
                    <h2
                        class="mb-4 flex items-center gap-2 border-b border-gray-100 pb-2 text-sm font-bold text-gray-800 sm:text-base">
                        <i data-lucide="shopping-cart" class="text-brand-500 h-4 w-4"></i> {{ ++$sectionNo }}. Public
                        Storefront
                    </h2>

                    <div class="mb-6 flex flex-col gap-5 sm:flex-row">
                        <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                            <input type="hidden" name="storefront_enabled" value="0" />
                            <input type="checkbox" name="storefront_enabled" value="1" class="peer sr-only"
                                {{ old('storefront_enabled', $store->storefront_enabled) ? 'checked' : '' }} />
                            <div
                                class="peer peer-checked:bg-brand-500 relative h-6 w-11 rounded-full bg-gray-200 peer-focus:outline-none after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white">
                            </div>
                            <div class="ms-3">
                                <span class="block text-[13px] font-bold text-gray-800">Storefront Online</span>
                                <span class="block text-[11px] font-normal text-gray-400">When off, visitors see a
                                    maintenance page for this store.</span>
                            </div>
                        </label>

                        <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                            <input type="hidden" name="is_primary" value="0" />
                            <input type="checkbox" name="is_primary" value="1" class="peer sr-only"
                                {{ old('is_primary', $store->is_primary) ? 'checked' : '' }} />
                            <div
                                class="peer peer-checked:bg-brand-500 relative h-6 w-11 rounded-full bg-gray-200 peer-focus:outline-none after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white">
                            </div>
                            <div class="ms-3">
                                <span class="block text-[13px] font-bold text-gray-800">Primary Store</span>
                                <span class="block text-[11px] font-normal text-gray-400">Storefront orders are routed here
                                    first. Turning this on removes it from any other
                                    store.</span>
                            </div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3">
                        <div class="sm:col-span-2 lg:col-span-3">
                            <label
                                class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Tagline</label>
                            <input type="text" name="tagline" value="{{ old('tagline', $store->tagline) }}"
                                maxlength="160" placeholder="e.g. Fresh Plants, Delivered to Your Door"
                                class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1" />
                        </div>

                        <div class="sm:col-span-2 lg:col-span-3">
                            <label
                                class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Description</label>
                            <textarea name="description" rows="3"
                                class="focus:border-brand-500 focus:ring-brand-500 w-full resize-y rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1">{{ old('description', $store->description) }}</textarea>
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">WhatsApp
                                Number</label>
                            <input type="text" name="whatsapp" value="{{ old('whatsapp', $store->whatsapp) }}"
                                maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                placeholder="0000000000"
                                class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1" />
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Support
                                Email</label>
                            <input type="email" name="support_email"
                                value="{{ old('support_email', $storeSettings['support_email'] ?? '') }}"
                                placeholder="support@company.com"
                                class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1" />
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Map
                                Embed URL</label>
                            <input type="url" name="map_embed_url"
                                value="{{ old('map_embed_url', $store->map_embed_url) }}" placeholder="https://..."
                                class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1" />
                        </div>

                        <div class="sm:col-span-2 lg:col-span-3">
                            <label
                                class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Business
                                Hours</label>
                            <textarea name="business_hours" rows="3" placeholder="Mon-Sat: 9 AM - 8 PM"
                                class="focus:border-brand-500 focus:ring-brand-500 w-full resize-y rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1">{{ old('business_hours', $store->business_hours) }}</textarea>
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Instagram</label>
                            <input type="url" name="instagram" value="{{ old('instagram', $store->instagram) }}"
                                placeholder="https://instagram.com/..."
                                class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1" />
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Facebook</label>
                            <input type="url" name="facebook" value="{{ old('facebook', $store->facebook) }}"
                                placeholder="https://facebook.com/..."
                                class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1" />
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Twitter</label>
                            <input type="url" name="twitter" value="{{ old('twitter', $store->twitter) }}"
                                placeholder="https://twitter.com/..."
                                class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1" />
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">YouTube</label>
                            <input type="url" name="youtube"
                                value="{{ old('youtube', $storeSettings['youtube'] ?? '') }}"
                                placeholder="https://youtube.com/..."
                                class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1" />
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">LinkedIn</label>
                            <input type="url" name="linkedin"
                                value="{{ old('linkedin', $storeSettings['linkedin'] ?? '') }}"
                                placeholder="https://linkedin.com/..."
                                class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1" />
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">Google
                                Maps</label>
                            <input type="url" name="google"
                                value="{{ old('google', $storeSettings['google'] ?? '') }}"
                                placeholder="https://maps.google.com/..."
                                class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1" />
                        </div>
                    </div>
                </div>

                {{-- ========================================== --}}
                {{-- SECTION 6: SEO & META                      --}}
                {{-- ========================================== --}}
                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-6">
                    <h2
                        class="mb-4 flex items-center gap-2 border-b border-gray-100 pb-2 text-sm font-bold text-gray-800 sm:text-base">
                        <i data-lucide="search" class="text-brand-500 h-4 w-4"></i> {{ ++$sectionNo }}. SEO & Meta
                    </h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3">
                        <div class="sm:col-span-2 lg:col-span-3">
                            <label
                                class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">SEO
                                Title</label>
                            <input type="text" name="seo_title" value="{{ old('seo_title', $store->seo_title) }}"
                                maxlength="160" placeholder="e.g. Best Plants in City | Acme Store"
                                class="focus:border-brand-500 focus:ring-brand-500 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1" />
                        </div>

                        <div class="sm:col-span-2 lg:col-span-3">
                            <label
                                class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">SEO
                                Description</label>
                            <textarea name="seo_description" rows="3" maxlength="300"
                                placeholder="Brief description for search engines..."
                                class="focus:border-brand-500 focus:ring-brand-500 w-full resize-y rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1">{{ old('seo_description', $store->seo_description) }}</textarea>
                        </div>

                        <div class="sm:col-span-2 lg:col-span-3">
                            <label
                                class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-500 uppercase sm:text-xs">SEO
                                Keywords</label>
                            <textarea name="seo_keywords" rows="2" placeholder="plants, nursery, indoor plants"
                                class="focus:border-brand-500 focus:ring-brand-500 w-full resize-y rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm shadow-sm transition-all outline-none focus:ring-1">{{ old('seo_keywords', $storeSettings['seo_keywords'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ========================================== --}}
            {{-- SECTION 3: SETTINGS & SUBMIT               --}}
            {{-- ========================================== --}}
            <div
                class="sticky bottom-0 z-20 -mx-1 flex flex-col items-start justify-between gap-5 rounded-2xl border border-gray-100 bg-white p-5 shadow-[0_-4px_16px_rgba(0,0,0,0.08)] sm:flex-row sm:items-center sm:p-6">
                {{-- Active Toggle --}}
                <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                    <input type="hidden" name="is_active" value="0" />
                    <input type="checkbox" name="is_active" value="1" class="peer sr-only"
                        {{ old('is_active', $store->is_active) ? 'checked' : '' }} />
                    <div
                        class="peer peer-checked:bg-brand-500 relative h-6 w-11 rounded-full bg-gray-200 peer-focus:outline-none after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white">
                    </div>
                    <div class="ms-3">
                        <span class="block text-[13px] font-bold text-gray-800">Active Branch</span>
                        <span class="block text-[11px] font-normal text-gray-400">Visible for billing & inventory</span>
                    </div>
                </label>

                {{-- Submit Button --}}
                <button type="submit" :disabled="isSubmitting"
                    class="bg-brand-500 hover:bg-brand-600 shadow-brand-500/20 flex w-full shrink-0 items-center justify-center gap-2 rounded-xl px-8 py-3 text-sm font-bold text-white shadow-md transition-all active:scale-95 disabled:opacity-70 sm:w-auto">
                    <i data-lucide="save" class="h-4 w-4" x-show="!isSubmitting"></i>
                    <i data-lucide="loader-2" class="h-4 w-4 animate-spin" x-show="isSubmitting" x-cloak></i>
                    <span x-text="isSubmitting ? 'Saving Changes...' : 'Save Changes'"></span>
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        function storeEditForm(initialLogo, initialSignature) {
            return {
                isSubmitting: false,
                phone: "{{ old('phone', $store->phone) }}",
                zip: "{{ old('zip_code', $store->zip_code) }}",
                subdomain: "{{ old('subdomain', $store->subdomain ?? '') }}",
                logoPreview: initialLogo,
                signaturePreview: initialSignature,

                previewImage(event, target) {
                    const file = event.target.files[0];
                    if (file) {
                        this[target] = URL.createObjectURL(file);
                    }
                },
            };
        }
    </script>
@endpush
