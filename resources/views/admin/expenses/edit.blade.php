@extends('layouts.admin')

@section('title', 'Edit Expense - ' . $expense->merchant_name)

@push('styles')
    <style>
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type="number"] {
            -moz-appearance: textfield;
        }

        .transition-smooth {
            transition: all 0.2s ease;
        }

        .custom-scroll::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
    </style>
@endpush

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Edit / Expenses</h1>
    </div>
@endsection

@section('content')
    <div class="w-full px-4 py-6 sm:px-6 lg:px-8 xl:px-5" x-data="expenseEditForm(@js($expense), @js(old()))">
        {{-- ── Global Errors Toast ── --}}
        @if ($errors->any())
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    console.error('[ExpenseEdit] Validation errors:', @json($errors->all()));
                    BizAlert.toast('Please fix the errors below.', 'error');
                });
            </script>
        @endif

        {{-- ── Header ── --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <a href="{{ route('admin.expenses.show', $expense) }}"
                    class="inline-flex items-center gap-1.5 text-sm font-bold text-gray-400 hover:text-brand-600 transition-colors mb-1">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Expense Details
                </a>
                {{-- <h1 class="text-2xl lg:text-3xl font-bold text-[#212538] tracking-tight">Edit Expense</h1> --}}
                <p class="text-sm text-gray-500 font-medium mt-0.5">Update expense details, receipt, or tax information.</p>
            </div>
            <div class="flex items-center gap-3">
                <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                    @if ($expense->status === 'pending_approval') bg-amber-100 text-amber-700
                    @elseif($expense->status === 'approved') bg-green-100 text-green-700
                    @elseif($expense->status === 'reimbursed') bg-blue-100 text-blue-700
                    @else bg-gray-100 text-gray-700 @endif">
                    Status: {{ ucfirst(str_replace('_', ' ', $expense->status)) }}
                </span>
            </div>
        </div>

        {{-- ── Main Form ── --}}
        <form action="{{ route('admin.expenses.update', $expense) }}" method="POST" enctype="multipart/form-data"
            @submit="submitForm($event)">
            @csrf
            <input type="hidden" name="store_id" value="{{ $expense->store_id ?? active_store()?->id }}">
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-5 xl:grid-cols-3 gap-6 lg:gap-8">

                {{-- LEFT COLUMN: Core Details (2/3 width) --}}
                <div class="lg:col-span-3 xl:col-span-2 space-y-6 lg:space-y-8">

                    {{-- General Information Card --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50/80 border-b border-gray-100">
                            <h4 class="flex items-center gap-2 text-sm font-bold text-gray-800">
                                <i data-lucide="file-text" class="w-4 h-4 text-brand-500"></i> General Information
                            </h4>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {{-- Merchant Name --}}
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                                        Merchant / Vendor Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="merchant_name" x-model="form.merchant_name" required
                                        placeholder="e.g., Amazon Web Services, Uber, Local Stationery"
                                        class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-800 font-medium focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all shadow-sm">
                                </div>

                                {{-- Expense Date --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                                        Date of Expense <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" name="expense_date" x-model="form.expense_date" required
                                        class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium text-gray-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all shadow-sm">
                                </div>

                                {{-- Reference Number --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                                        Ref / Invoice No.
                                    </label>
                                    <input type="text" name="reference_number" x-model="form.reference_number"
                                        placeholder="e.g., INV-2024-001"
                                        class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-mono text-gray-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all shadow-sm">
                                </div>

                                {{-- Category Selection --}}
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                                        Expense Category <span class="text-red-500">*</span>
                                    </label>
                                    <x-alpine-select name="expense_category_id" model="form.expense_category_id"
                                        items="categoryOptions" placeholder="Select Category" :required="true"
                                        :allow-empty="true" />
                                </div>

                                {{-- Description --}}
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                                        Notes / Description
                                    </label>
                                    <textarea name="notes" rows="4" x-model="form.notes" placeholder="Brief reason for this expense..."
                                        class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all resize-y shadow-sm">{{ old('notes', $expense->notes) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Receipt Upload Card --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50/80 border-b border-gray-100">
                            <h4 class="flex items-center gap-2 text-sm font-bold text-gray-800">
                                <i data-lucide="paperclip" class="w-4 h-4 text-brand-500"></i> Attach Receipt
                            </h4>
                        </div>
                        <div class="p-6">
                            {{-- Existing Receipt Preview (if any) --}}
                            <div x-show="hasExistingReceipt" x-cloak
                                class="mb-4 p-4 bg-gray-50 rounded-xl border border-gray-200">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-3">
                                        <i data-lucide="file-text" class="w-8 h-8 text-blue-500"></i>
                                        <div>
                                            <p class="text-sm font-bold text-gray-800" x-text="existingReceiptName"></p>
                                            <p class="text-xs text-gray-500">Current receipt</p>
                                        </div>
                                    </div>
                                    <button type="button" @click="removeExistingReceipt()"
                                        class="text-red-500 hover:text-red-700 hover:bg-red-50 p-1.5 rounded transition-colors"
                                        title="Remove receipt">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                                <template x-if="existingReceiptIsImage">
                                    <div class="mt-3">
                                        <img :src="existingReceiptUrl" class="max-h-40 rounded-lg shadow-sm border">
                                    </div>
                                </template>
                                <template x-if="!existingReceiptIsImage && existingReceiptUrl">
                                    <div class="mt-3">
                                        <a :href="existingReceiptUrl" target="_blank"
                                            class="text-blue-600 text-sm font-medium inline-flex items-center gap-1">
                                            <i data-lucide="external-link" class="w-3 h-3"></i> Open PDF
                                        </a>
                                    </div>
                                </template>
                            </div>

                            {{-- New File Upload Area --}}
                            <div class="relative group">
                                <input type="file" name="receipt" id="receipt" class="hidden"
                                    accept="image/*,application/pdf" @change="handleFileUpload($event)">
                                <label for="receipt"
                                    class="flex flex-col items-center justify-center w-full min-h-[160px] border-2 border-dashed border-gray-300 rounded-2xl bg-gray-50 hover:bg-brand-50 hover:border-brand-400 transition-all cursor-pointer overflow-hidden relative">

                                    {{-- Preview of newly selected file --}}
                                    <template x-if="receiptPreview">
                                        <div
                                            class="absolute inset-0 w-full h-full bg-black/5 flex items-center justify-center p-2">
                                            <img :src="receiptPreview"
                                                class="max-w-full max-h-full object-contain rounded-lg shadow-sm">
                                        </div>
                                    </template>

                                    {{-- PDF / Generic File View (new file) --}}
                                    <template x-if="receiptName && !receiptPreview">
                                        <div
                                            class="flex flex-col items-center z-10 bg-white/90 p-4 rounded-xl shadow-sm backdrop-blur-sm text-center">
                                            <i data-lucide="file-text" class="w-10 h-10 text-blue-500 mb-2 mx-auto"></i>
                                            <span class="text-sm font-bold text-gray-800 break-all"
                                                x-text="receiptName"></span>
                                            <span class="text-[10px] text-gray-400 mt-1">(New file will replace
                                                current)</span>
                                        </div>
                                    </template>

                                    {{-- Default State (no existing, no new file) --}}
                                    <template x-if="!receiptName && !hasExistingReceipt">
                                        <div
                                            class="flex flex-col items-center text-gray-400 group-hover:text-brand-500 transition-colors text-center p-4">
                                            <i data-lucide="upload-cloud" class="w-12 h-12 mb-2 mx-auto"></i>
                                            <p class="text-sm font-bold">Click or drag to upload receipt</p>
                                            <p class="text-[10px] uppercase tracking-wider font-semibold mt-1">JPEG, PNG,
                                                or PDF (Max 10MB)</p>
                                        </div>
                                    </template>

                                    {{-- Default State (existing, no new file) --}}
                                    <template x-if="!receiptName && hasExistingReceipt">
                                        <div
                                            class="flex flex-col items-center text-gray-400 group-hover:text-brand-500 transition-colors text-center p-4">
                                            <i data-lucide="upload-cloud" class="w-12 h-12 mb-2 mx-auto"></i>
                                            <p class="text-sm font-bold">Replace receipt</p>
                                            <p class="text-[10px] uppercase tracking-wider font-semibold mt-1">Click to
                                                upload a new file</p>
                                        </div>
                                    </template>
                                </label>

                                {{-- Remove button for newly selected file --}}
                                <template x-if="receiptName">
                                    <button type="button" @click.prevent="removeFile()"
                                        class="absolute top-3 right-3 bg-red-500 hover:bg-red-600 text-white rounded-full p-1.5 shadow-md transition-transform active:scale-95 z-20"
                                        title="Cancel new file">
                                        <i data-lucide="x" class="w-4 h-4"></i>
                                    </button>
                                </template>
                            </div>

                            {{-- Hidden flag to signal removal of existing receipt (if user clicked the remove button) --}}
                            <input type="hidden" name="remove_receipt" x-model="removeReceiptFlag">
                        </div>
                    </div>
                </div>

                {{-- RIGHT COLUMN: Financials & Tax (Sticky) --}}
                <div class="lg:col-span-2 xl:col-span-1 lg:sticky lg:top-6 self-start space-y-6">

                    {{-- Financial Summary Card --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-gray-900 to-gray-800 px-6 py-4">
                            <h4 class="flex items-center gap-2 text-sm font-bold text-white">
                                <i data-lucide="calculator" class="w-4 h-4 text-brand-400"></i> Financial Details
                            </h4>
                        </div>

                        <div class="p-4 sm:p-6 lg:p-4 xl:p-6 space-y-6">
                            {{-- Base Amount --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                                    Base Amount (Taxable Value) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <span class="text-gray-500 font-bold text-lg">₹</span>
                                    </div>
                                    <input type="text" inputmode="numeric" pattern="[0-9]*" name="base_amount"
                                        x-model.number="form.base_amount"
                                        @input="
                                            form.base_amount = $event.target.value.replace(/\D/g, '');
                                        "
                                        required
                                        class="block w-full pl-9 pr-4 py-3 border border-gray-200 rounded-xl text-lg font-black text-gray-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all shadow-sm"
                                        placeholder="0">
                                </div>
                            </div>

                            {{-- Tax Type & Rate --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Tax
                                        Type</label>
                                    <x-alpine-select name="tax_type" model="form.tax_type" items="taxTypeOptions"
                                        placeholder="Select Tax Type" :allow-empty="false" />
                                </div>

                                <div>
                                    <label
                                        class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Tax
                                        Rate</label>
                                    <div class="relative">
                                        <input type="number" step="0.01" name="tax_percent"
                                            x-model.number="form.tax_percent" :disabled="form.tax_type === 'none'"
                                            class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-sm font-bold text-gray-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all shadow-sm disabled:bg-gray-100 disabled:text-gray-400">
                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                            <span class="text-gray-500 font-bold">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Live Calculation Preview --}}
                            <div
                                class="bg-gray-50 rounded-xl p-3 sm:p-5 lg:p-3 xl:p-5 border border-gray-100 mt-2 space-y-3">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="font-semibold text-gray-600">Base Value</span>
                                    <span class="font-bold text-gray-800"
                                        x-text="'₹ ' + formatNumber(form.base_amount)"></span>
                                </div>

                                <template x-if="form.tax_type === 'cgst_sgst' && form.tax_percent > 0">
                                    <div class="space-y-2 pt-1 border-t border-gray-200">
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="font-medium text-gray-500">CGST (<span
                                                    x-text="(form.tax_percent/2).toFixed(2)"></span>%)</span>
                                            <span class="font-medium text-gray-700"
                                                x-text="'+ ₹ ' + formatNumber(cgst)"></span>
                                        </div>
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="font-medium text-gray-500">SGST (<span
                                                    x-text="(form.tax_percent/2).toFixed(2)"></span>%)</span>
                                            <span class="font-medium text-gray-700"
                                                x-text="'+ ₹ ' + formatNumber(sgst)"></span>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="form.tax_type === 'igst' && form.tax_percent > 0">
                                    <div class="flex justify-between items-center text-sm pt-1 border-t border-gray-200">
                                        <span class="font-medium text-gray-500">IGST (<span
                                                x-text="form.tax_percent.toFixed(2)"></span>%)</span>
                                        <span class="font-medium text-gray-700"
                                            x-text="'+ ₹ ' + formatNumber(igst)"></span>
                                    </div>
                                </template>

                                <div class="flex justify-between items-center text-xs pt-1">
                                    <span class="font-semibold text-gray-400">Round Off</span>
                                    <span class="font-medium text-gray-500"
                                        x-text="(roundOff >= 0 ? '+' : '') + formatNumber(roundOff)"></span>
                                </div>

                                <div class="pt-3 border-t border-gray-200 flex justify-between items-end">
                                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total
                                        Amount</span>
                                    <span class="text-2xl font-black text-brand-600"
                                        x-text="'₹ ' + formatNumber(total)"></span>
                                </div>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="pt-2 flex flex-col xl:flex-row gap-3">
                                <input type="hidden" name="status" value="{{ $expense->status }}">
                                <a href="{{ route('admin.expenses.show', $expense) }}"
                                    class="flex-1 text-center px-4 py-3 rounded-xl border border-gray-200 text-gray-600 text-sm font-bold hover:bg-gray-50 transition-colors">
                                    Cancel
                                </a>
                                <button type="submit" :disabled="isSubmitting"
                                    class="flex-1 bg-[#108c2a] hover:bg-[#0c6b1f] text-white px-4 py-3 rounded-xl text-sm font-bold shadow-lg shadow-green-600/20 transition-all active:scale-95 flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed whitespace-nowrap">
                                    <i data-lucide="check-circle" class="w-5 h-5" x-show="!isSubmitting"></i>
                                    <i data-lucide="loader-2" class="w-5 h-5 animate-spin" x-show="isSubmitting"
                                        style="display: none;"></i>
                                    <span x-text="isSubmitting ? 'Saving...' : 'Update Expense'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Helper Card --}}
                    <div class="bg-blue-50 rounded-xl p-4 border border-blue-100 text-sm">
                        <div class="flex gap-3">
                            <i data-lucide="lightbulb" class="w-5 h-5 text-blue-600 flex-shrink-0"></i>
                            <div class="space-y-1">
                                <p class="font-bold text-blue-800">Note</p>
                                <p class="text-blue-700 text-xs">Approved or reimbursed expenses cannot be edited. If you
                                    need to modify a finalized expense, please reverse its status first.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        function expenseEditForm(expenseData, oldInput = {}) {
            // Helper to merge old input (from validation errors) with existing model data
            const mergeValue = (field, defaultValue = null) => {
                if (oldInput.hasOwnProperty(field) && oldInput[field] !== undefined && oldInput[field] !== '') {
                    return oldInput[field];
                }
                return expenseData[field] ?? defaultValue;
            };

            // Derived from the model's own accessors. The media relation was
            // never eager-loaded here, so every one of these was empty and the
            // existing receipt simply never rendered.
            const existingReceiptUrl = expenseData.receipt_url || null;
            const hasExistingReceipt = !!existingReceiptUrl;
            const existingReceiptName = expenseData.receipt_name || '';
            const existingReceiptIsImage = !!expenseData.receipt_is_image;

            return {
                // Select Data Options
                categoryOptions: @js(
    collect($categories)
        ->flatMap(function ($parent) {
            $items = collect([['id' => $parent->id, 'name' => $parent->name . ' (Main)']]);
            foreach ($parent->children as $child) {
                $items->push(['id' => $child->id, 'name' => '  ↳ ' . $child->name]);
            }
            return $items;
        })
        ->values(),
),
                taxTypeOptions: [{
                        id: 'none',
                        name: 'No Tax'
                    },
                    {
                        id: 'cgst_sgst',
                        name: 'CGST + SGST'
                    },
                    {
                        id: 'igst',
                        name: 'IGST'
                    }
                ],

                form: {
                    merchant_name: mergeValue('merchant_name', ''),
                    expense_date: (() => {
                        const raw = expenseData.expense_date;
                        let formatted;
                        if (raw) {
                            // Convert the ISO string (UTC) to local YYYY-MM-DD
                            if (typeof raw === 'string' && raw.includes('T')) {
                                formatted = new Date(raw).toLocaleDateString('en-CA');
                            } else if (typeof raw === 'string') {
                                formatted = raw.slice(0, 10);
                            } else {
                                formatted = new Date(raw).toLocaleDateString('en-CA');
                            }
                        } else {
                            formatted = new Date().toISOString().split('T')[0];
                        }
                        // Respect old input (validation errors)
                        return (oldInput.expense_date !== undefined && oldInput.expense_date !== '') ? oldInput
                            .expense_date : formatted;
                    })(),
                    reference_number: mergeValue('reference_number', ''),
                    expense_category_id: mergeValue('expense_category_id', ''),
                    notes: mergeValue('notes', ''),
                    base_amount: parseFloat(mergeValue('base_amount', 0)),
                    tax_type: mergeValue('tax_type', 'none'),
                    tax_percent: parseFloat(mergeValue('tax_percent', 0)),
                },

                // Receipt state
                hasExistingReceipt: hasExistingReceipt,
                existingReceiptUrl: existingReceiptUrl,
                existingReceiptName: existingReceiptName,
                existingReceiptIsImage: existingReceiptIsImage,
                removeReceiptFlag: 0, // 0 = keep, 1 = remove existing
                receiptName: null,
                receiptPreview: null,
                // Tax calculation fields
                cgst: 0,
                sgst: 0,
                igst: 0,
                total: 0,
                roundOff: 0,
                isSubmitting: false,

                init() {
                    console.log('[ExpenseEdit] expense_date value:', this.form.expense_date);
                    console.log('[ExpenseEdit] Initialized with data:', this.form);
                    this.calculateTaxes();

                    this.$watch('form.base_amount', () => this.calculateTaxes());
                    this.$watch('form.tax_type', () => {
                        if (this.form.tax_type === 'none') this.form.tax_percent = 0;
                        this.calculateTaxes();
                    });
                    this.$watch('form.tax_percent', () => this.calculateTaxes());
                },

                calculateTaxes() {
                    let base = parseFloat(this.form.base_amount) || 0;
                    let percent = parseFloat(this.form.tax_percent) || 0;

                    this.cgst = this.sgst = this.igst = 0;

                    if (this.form.tax_type === 'igst' && percent > 0) {
                        this.igst = (base * percent) / 100;
                    } else if (this.form.tax_type === 'cgst_sgst' && percent > 0) {
                        let half = (base * (percent / 2)) / 100;
                        this.cgst = half;
                        this.sgst = half;
                    }

                    let exactTotal = base + this.cgst + this.sgst + this.igst;
                    this.total = Math.round(exactTotal);
                    this.roundOff = this.total - exactTotal;

                    console.log('[ExpenseEdit] Tax calc:', {
                        base,
                        type: this.form.tax_type,
                        percent,
                        cgst: this.cgst,
                        sgst: this.sgst,
                        igst: this.igst,
                        exact_total: exactTotal,
                        total: this.total,
                        round_off: this.roundOff
                    });
                },

                handleFileUpload(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    console.log('[ExpenseEdit] New file selected:', file.name);
                    this.receiptName = file.name;
                    if (file.type.startsWith('image/')) {
                        if (this.receiptPreview) URL.revokeObjectURL(this.receiptPreview);
                        this.receiptPreview = URL.createObjectURL(file);
                    } else {
                        this.receiptPreview = null;
                    }
                    // If a new file is uploaded, we assume we want to replace the existing receipt,
                    // so we clear the remove flag and also will replace later.
                    this.removeReceiptFlag = 0;
                },

                removeFile() {
                    console.log('[ExpenseEdit] Cancelling new file selection');
                    const input = document.getElementById('receipt');
                    if (input) input.value = '';
                    if (this.receiptPreview) URL.revokeObjectURL(this.receiptPreview);
                    this.receiptName = null;
                    this.receiptPreview = null;
                },

                removeExistingReceipt() {
                    console.log('[ExpenseEdit] Marking existing receipt for removal');
                    this.removeReceiptFlag = 1;
                    // Visually hide the existing receipt preview
                    this.hasExistingReceipt = false;
                    // Optionally, you could also clear the hidden fields
                },

                formatNumber(value) {
                    return parseFloat(value).toLocaleString('en-IN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                },

                submitForm(e) {
                    if (this.form.base_amount <= 0) {
                        e.preventDefault();
                        BizAlert.toast('Base amount must be greater than zero.', 'error');
                        return;
                    }

                    // If we are removing the receipt AND not uploading a new one, we need to send the flag.
                    // The hidden field already contains this.removeReceiptFlag.
                    // If we are uploading a new file, the controller will replace it anyway; the flag can be ignored.
                    console.log('[ExpenseEdit] Submitting form. removeReceiptFlag =', this.removeReceiptFlag);
                    this.isSubmitting = true;
                }
            };
        }
    </script>
@endpush
