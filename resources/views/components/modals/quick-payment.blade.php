{{--
    ╔══════════════════════════════════════════════════╗
    ║  QUICK PAYMENT MODAL — Reusable Blade Component  ║
    ║  Usage:                                          ║
    ║  <x-modals.quick-payment                         ║
    ║      :action="route('',$e)"║
    ║      :due-amount="$expense->due_amount"          ║
    ║      :total-amount="$expense->total_amount"      ║
    ║      :paid-amount="$expense->total_paid"         ║
    ║      :payment-methods="$paymentMethods"          ║
    ║      currency="₹"                               ║
    ║  />                                              ║
    ╚══════════════════════════════════════════════════╝
--}}
@props([
    'action',
    'dueAmount',
    'totalAmount',
    'paidAmount',
    'paymentMethods',
    'currency' => '₹',
    'title' => 'Record Payment',
    'subtitle' => 'Payment will be recorded immediately.',
])

<script>
    if (typeof quickPaymentModal === 'undefined') {
        function quickPaymentModal({
            dueAmount,
            action
        }) {
            return {
                isOpen: false,
                isLoading: false,
                showExtra: false,
                dueAmount: parseFloat(dueAmount) || 0,
                today: new Date().toISOString().split('T')[0],

                form: {
                    amount: 0,
                    payment_method_id: null,
                    payment_date: new Date().toISOString().split('T')[0],
                    reference: '',
                    notes: '',
                },

                errors: {},
                alert: {
                    show: false,
                    type: '',
                    message: ''
                },

                open(payload = {}) {
                    if (payload.dueAmount !== undefined) this.dueAmount = parseFloat(payload.dueAmount) || 0;
                    if (payload.action !== undefined) action = payload.action;
                    if (this.dueAmount <= 0) return;
                    this._reset();
                    this.isOpen = true;
                    document.body.style.overflow = 'hidden';
                    this.$nextTick(() => {
                        this.$el.querySelector('input[type="number"]')?.focus();
                    });
                },

                close() {
                    this.isOpen = false;
                    document.body.style.overflow = '';
                    setTimeout(() => this._reset(), 200);
                },

                _reset() {
                    this.form.amount = parseFloat(this.dueAmount.toFixed(2));
                    this.form.payment_method_id = null;
                    this.form.payment_date = this.today;
                    this.form.reference = '';
                    this.form.notes = '';
                    this.errors = {};
                    this.alert = {
                        show: false,
                        type: '',
                        message: ''
                    };
                    this.showExtra = false;
                    this.isLoading = false;
                },

                validateAmount() {
                    const amt = parseFloat(this.form.amount) || 0;
                    if (amt <= 0) {
                        this.errors.amount = 'Amount must be greater than ₹0.';
                    } else if (amt > this.dueAmount + 0.001) {
                        this.errors.amount = `Cannot exceed the due amount of ₹${this.dueAmount.toFixed(2)}.`;
                    } else {
                        delete this.errors.amount;
                    }
                },

                _validate() {
                    this.errors = {};
                    this.validateAmount();
                    if (!this.form.payment_method_id) {
                        this.errors.payment_method_id = 'Please select a payment method.';
                    }
                    return Object.keys(this.errors).length === 0;
                },

                _showAlert(type, message) {
                    this.alert = {
                        show: true,
                        type,
                        message
                    };
                },

                async submit() {
                    if (!this._validate()) return;

                    this.isLoading = true;
                    this.alert.show = false;

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

                    try {
                        const res = await fetch(action, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify(this.form),
                        });

                        const data = await res.json();

                        if (res.ok && data.success) {
                            this.dueAmount = Math.max(0, this.dueAmount - parseFloat(this.form.amount));
                            this._showAlert('success', data.message ?? 'Payment recorded successfully.');
                            setTimeout(() => window.location.reload(), 1400);
                        } else {
                            const msg = data.errors ?
                                Object.values(data.errors).flat().join(' ') :
                                (data.message ?? 'Failed to record payment. Please try again.');
                            this._showAlert('error', msg);
                        }
                    } catch (err) {
                        this._showAlert('error', 'Network error. Check your connection and try again.');
                    } finally {
                        this.isLoading = false;
                    }
                },
            };
        }
    }
</script>

<div x-data="quickPaymentModal({
    dueAmount: {{ (float) $dueAmount }},
    action: '{{ $action }}'
})" x-on:open-quick-payment.window="open($event.detail)" x-on:keydown.escape.window="close()"
    x-cloak>

    {{-- ══════════════════════════════════════════
         BACKDROP + PANEL WRAPPER
    ══════════════════════════════════════════ --}}
    <template x-teleport="body">
        <div x-show="isOpen" x-transition:enter="transition-opacity ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" {{-- no-print: x-teleport moves this node to <body>, where print
                 stylesheets that force `body > div { display: block !important }`
                 override Alpine's inline display:none and paint the backdrop
                 across the whole printed page. --}}
            class="no-print fixed inset-0 z-[9998] flex items-end sm:items-center justify-center">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-gray-950/50 backdrop-blur-[2px]" x-on:click="close()"></div>

            {{-- Panel — bottom-sheet on mobile, centered card on sm+ --}}
            <div x-show="isOpen" x-transition:enter="transition ease-out duration-250"
                x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95" x-on:click.stop
                class="relative z-[9999] w-full sm:max-w-[440px] bg-white rounded-t-[20px] sm:rounded-2xl shadow-2xl ring-1 ring-gray-200/80 overflow-hidden max-h-[90vh] flex flex-col">

                {{-- ── Drag handle (mobile) ── --}}
                <div class="flex justify-center pt-3 pb-1 sm:hidden">
                    <div class="w-10 h-1 bg-gray-200 rounded-full"></div>
                </div>

                {{-- ── Header ── --}}
                <div class="flex items-start justify-between px-5 pt-4 pb-3 sm:pt-5">
                    <div>
                        <h3 class="text-[15px] font-semibold text-gray-900 tracking-tight">{{ $title }}</h3>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $subtitle }}</p>
                    </div>
                    <button type="button" x-on:click="close()"
                        class="mt-0.5 flex-shrink-0 flex items-center justify-center w-7 h-7 rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                        aria-label="Close">
                        <svg class="w-[14px] h-[14px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- ── Financial Summary Strip ── --}}
                <div
                    class="mx-5 mb-3 grid grid-cols-3 rounded-xl border border-gray-100 bg-gray-50/80 divide-x divide-gray-100 overflow-hidden">
                    <div class="px-3 py-2.5 text-center">
                        <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-widest">Total</p>
                        <p class="text-sm font-semibold text-gray-700 mt-0.5 tabular-nums">
                            {{ $currency }}{{ number_format((float) $totalAmount, 2) }}</p>
                    </div>
                    <div class="px-3 py-2.5 text-center">
                        <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-widest">Paid</p>
                        <p class="text-sm font-semibold text-emerald-600 mt-0.5 tabular-nums">
                            {{ $currency }}{{ number_format((float) $paidAmount, 2) }}</p>
                    </div>
                    <div class="px-3 py-2.5 text-center">
                        <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-widest">Due</p>
                        <p class="text-sm font-bold mt-0.5 tabular-nums transition-colors"
                            :class="dueAmount > 0 ? 'text-rose-600' : 'text-emerald-600'">{{ $currency }}<span
                                x-text="dueAmount.toFixed(2)"></span></p>
                    </div>
                </div>

                {{-- ── Alert Banner ── --}}
                <div x-show="alert.show" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="mx-5 mb-3 flex items-start gap-2 px-3.5 py-2.5 rounded-xl text-[13px] border"
                    :class="{
                        'bg-emerald-50 border-emerald-200 text-emerald-800': alert.type === 'success',
                        'bg-rose-50 border-rose-200 text-rose-800': alert.type === 'error'
                    }">
                    {{-- success icon --}}
                    <svg x-show="alert.type === 'success'" class="w-4 h-4 flex-shrink-0 mt-px" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{-- error icon --}}
                    <svg x-show="alert.type === 'error'" class="w-4 h-4 flex-shrink-0 mt-px" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span x-text="alert.message" class="leading-snug"></span>
                </div>

                {{-- ── Form ── --}}
                <form x-on:submit.prevent="submit()" class="px-5 pb-5 space-y-4 overflow-y-auto flex-1" novalidate>

                    {{-- Amount --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">
                            Amount <span class="text-rose-400">*</span>
                        </label>
                        <div class="relative">
                            {{-- Stable inline container protecting the currency symbol alignment natively --}}
                            <div
                                class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none select-none text-gray-400 text-sm">
                                {{ $currency }}
                            </div>
                            <input type="number" x-model.number="form.amount" x-on:input="validateAmount()"
                                step="0.01" min="0.01" :max="dueAmount" placeholder="0.00"
                                autocomplete="off"
                                class="w-full pl-9 pr-4 py-2.5 text-sm rounded-xl border outline-none transition-all duration-150"
                                :class="errors.amount ?
                                    'border-rose-300 ring-2 ring-rose-100 bg-rose-50/50 text-rose-800' :
                                    'border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-100'">
                        </div>
                        {{-- Inline amount error --}}
                        <p x-show="errors.amount" x-text="errors.amount"
                            class="mt-1.5 text-[11px] text-rose-500 font-medium"></p>
                        {{-- Live balance preview --}}
                        <p x-show="form.amount > 0 && !errors.amount" class="mt-1.5 text-[11px] text-gray-400">
                            Balance remaining: <span class="font-semibold text-gray-600 tabular-nums"
                                x-text="'{{ $currency }}' + Math.max(0, dueAmount - (parseFloat(form.amount) || 0)).toFixed(2)"></span>
                        </p>
                    </div>

                    {{-- Payment Method — pill toggle grid --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-2">
                            Payment Method <span class="text-rose-400">*</span>
                        </label>
                        {{-- Upgraded responsive responsive container handling single column on narrow mobile screens --}}
                        <div class="grid grid-cols-1 xs:grid-cols-2 gap-2">
                            @forelse($paymentMethods as $method)
                                <label
                                    class="relative flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl border cursor-pointer select-none transition-all duration-150 text-[13px] min-w-0"
                                    :class="form.payment_method_id == {{ $method->id }} ?
                                        'border-brand-500 bg-brand-50 text-brand-700 font-semibold ring-1 ring-brand-200 shadow-sm' :
                                        'border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50'">
                                    <input type="radio" x-model.number="form.payment_method_id"
                                        value="{{ $method->id }}" class="sr-only">
                                    <span class="flex-shrink-0 w-2 h-2 rounded-full transition-colors duration-150"
                                        :class="form.payment_method_id == {{ $method->id }} ? 'bg-brand-500' : 'bg-gray-300'"></span>
                                    {{-- Word wrapping guard prevents breaks on complex or custom banking labels --}}
                                    <span class="break-words leading-tight flex-1 min-w-0">{{ $method->label }}</span>
                                </label>
                            @empty
                                <p class="col-span-full text-xs text-gray-400 italic py-1">No payment methods
                                    configured.</p>
                            @endforelse
                        </div>
                        <p x-show="errors.payment_method_id" x-text="errors.payment_method_id"
                            class="mt-1.5 text-[11px] text-rose-500 font-medium"></p>
                    </div>

                    {{-- Payment Date --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Payment Date</label>
                        <input type="date" x-model="form.payment_date" :max="today"
                            class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 transition-all duration-150">
                    </div>

                    {{-- Collapsible: Reference + Notes --}}
                    <div>
                        <button type="button" x-on:click="showExtra = !showExtra"
                            class="flex items-center gap-1.5 text-[11px] font-medium text-gray-400 hover:text-brand-600 transition-colors">
                            <svg class="w-3 h-3 transition-transform duration-200"
                                :class="showExtra ? 'rotate-90' : 'rotate-0'" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                            <span x-text="showExtra ? 'Hide details' : '+ Reference & Notes'"></span>
                        </button>

                        <div x-show="showExtra" class="mt-3 space-y-3 overflow-hidden">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Reference / Cheque
                                    No.</label>
                                <input type="text" x-model="form.reference" placeholder="e.g. CHQ-00123"
                                    class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 transition-all duration-150">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Notes</label>
                                <textarea x-model="form.notes" rows="2" placeholder="Optional internal notes..."
                                    class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 transition-all duration-150 resize-none"></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- ── Action Buttons ── --}}
                    <div class="flex items-center gap-2.5 pt-1 border-t border-gray-100">
                        <button type="button" x-on:click="close()"
                            class="flex-1 px-4 py-2.5 text-[13px] font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors active:scale-[0.98]">
                            Cancel
                        </button>
                        <button type="submit"
                            :disabled="isLoading || !!errors.amount || dueAmount <= 0 || !form.payment_method_id"
                            class="flex-[2] flex items-center justify-center gap-2 px-4 py-2.5 text-[13px] font-semibold text-white rounded-xl transition-all duration-150 active:scale-[0.98] disabled:opacity-55 disabled:cursor-not-allowed disabled:active:scale-100"
                            :class="dueAmount <= 0 ? 'bg-gray-400' :
                                'bg-brand-600 hover:bg-brand-700 shadow-sm shadow-indigo-200'">
                            <svg x-show="isLoading" class="w-3.5 h-3.5 animate-spin" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            <span
                                x-text="isLoading ? 'Recording…' : (dueAmount <= 0 ? 'Fully Paid' : 'Record Payment')"></span>
                        </button>
                    </div>

                </form>
            </div>{{-- /panel --}}
        </div>{{-- /wrapper --}}
    </template>
</div>
