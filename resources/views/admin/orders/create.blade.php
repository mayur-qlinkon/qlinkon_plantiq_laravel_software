@extends('layouts.admin')

@section('title', 'Create Offline Order - Qlinkon BIZNESS')

@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Create Order</h1>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }
    </style>
@endpush

@section('content')
    {{-- 🌟 1. Pass the store-to-warehouse mapping to Alpine --}}
    <div class="pb-20" x-data="orderForm(@js($warehouses), @js($clients ?? []))">

        {{-- ── HEADER ── --}}
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-500 uppercase tracking-widest">Create Offline Order</h1>
                <p class="text-sm text-gray-500">Manually generate a confirmed order or inquiry.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.orders.index') }}"
                    class="text-sm font-bold text-gray-500 hover:text-gray-800 transition-colors">Cancel</a>
                <button type="submit" form="mainOrderForm"
                    class="bg-[#108c2a] hover:bg-[#0c6b1f] text-white px-8 py-2.5 rounded-lg text-sm font-bold transition-all shadow-md flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i> Create Order
                </button>
            </div>
        </div>

        {{-- ── ERRORS ── --}}
        @if ($errors->any())
            <div class="bg-red-50 text-red-700 p-4 rounded-lg mb-6 border border-red-200 shadow-sm">
                <div class="font-bold mb-2 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i> Please fix the following errors:
                </div>
                <ul class="list-disc list-inside text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="mainOrderForm" action="{{ route('admin.orders.store') }}" method="POST"
            @submit="BizAlert.loading('Processing Order...')">
            @csrf
            <input type="hidden" name="source" value="admin">
            <input type="hidden" name="store_id" value="{{ active_store()?->id }}">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">

                {{-- ── 1. CUSTOMER & LOGISTICS ── --}}
                <div class="lg:col-span-8 flex flex-col gap-6">

                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex justify-between items-center mb-5 border-b border-gray-100 pb-3">
                            <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Customer Details</h2>
                            <button type="button" @click="toggleWalkIn()"
                                :class="isWalkIn ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                class="px-3 py-1.5 rounded text-xs font-bold uppercase tracking-widest transition-colors">
                                <span x-text="isWalkIn ? 'Walk-in Active' : 'Select Existing'"></span>
                            </button>
                        </div>

                        {{-- CLIENT DROPDOWN (Shown when NOT Walk-in) --}}
                        <div class="mb-4 relative" x-show="!isWalkIn" @click.away="isClientDropdownOpen = false" x-cloak>
                            <label class="block text-[11px] font-bold text-gray-600 uppercase mb-1.5">
                                Search Existing Customer <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="text" x-model="clientSearchTerm" @focus="isClientDropdownOpen = true"
                                    @input="isClientDropdownOpen = true; formData.client_id = ''"
                                    placeholder="Search customer by name or phone..."
                                    class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-[#108c2a] outline-none bg-white font-bold text-gray-700">
                                <i data-lucide="chevron-down"
                                    class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                            </div>
                            <input type="hidden" name="client_id" :value="formData.client_id">

                            <ul x-show="isClientDropdownOpen" x-transition
                                class="absolute z-[60] w-full bg-white border border-gray-200 rounded-lg shadow-2xl mt-1 max-h-60 overflow-y-auto overscroll-contain top-full left-0 custom-scrollbar">
                                <li x-show="filteredClients.length === 0"
                                    class="px-4 py-4 text-sm text-gray-500 text-center font-medium">
                                    No matching customers found.
                                </li>
                                <template x-for="client in filteredClients" :key="client.id">
                                    <li @click="selectClient(client)"
                                        class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0 transition-colors">
                                        <div class="font-bold text-[13px] text-gray-800" x-text="client.name"></div>
                                        <div class="text-[11px] text-gray-500 mt-0.5 flex items-center gap-2">
                                            <span x-show="client.phone" x-text="'📞 ' + client.phone"></span>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        {{-- If not Walk-In AND no client is selected, fade out the inputs to force user to use dropdown --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4"
                            :class="!isWalkIn && !formData.client_id ? 'opacity-50 pointer-events-none grayscale' : ''">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase mb-1.5">Name</label>
                                <input type="text" name="customer_name" x-model="formData.customer_name"
                                    :readonly="!isWalkIn"
                                    class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-[#108c2a] outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase mb-1.5">Phone</label>
                                <input type="text" name="customer_phone" x-model="formData.customer_phone"
                                    :readonly="!isWalkIn" maxlength="10" pattern="[0-9]{10}" inputmode="numeric"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                    class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-[#108c2a] outline-none"
                                    placeholder="Enter 10 digit phone number" required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-[11px] font-bold text-gray-600 uppercase mb-1.5">Email</label>
                                <input type="email" name="customer_email" x-model="formData.customer_email"
                                    :readonly="!isWalkIn"
                                    class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-[#108c2a] outline-none">
                            </div>
                        </div>

                        <h3 class="text-xs font-bold text-gray-700 mt-6 mb-3">Delivery Address</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4"
                            :class="!isWalkIn && !formData.client_id ? 'opacity-50 pointer-events-none grayscale' : ''">
                            <div class="md:col-span-3">
                                <input type="text" name="delivery_address" x-model="formData.delivery_address"
                                    placeholder="Street Address" :readonly="!isWalkIn"
                                    class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-[#108c2a] outline-none">
                            </div>
                            <div>
                                <input type="text" name="delivery_city" x-model="formData.delivery_city"
                                    placeholder="City" :readonly="!isWalkIn"
                                    class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-[#108c2a] outline-none">
                            </div>
                            <div>
                                <input type="text" name="delivery_state" placeholder="State"
                                    x-model="formData.delivery_state" :readonly="!isWalkIn"
                                    class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-[#108c2a] outline-none">
                            </div>
                            <div>
                                <input type="text" name="delivery_pincode" x-model="formData.delivery_pincode"
                                    placeholder="Pincode" :readonly="!isWalkIn"
                                    class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-[#108c2a] outline-none">
                            </div>
                        </div>
                    </div>

                    {{-- ── 2. CART ITEMS & SEARCH ── --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div
                            class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Order Items</h2>

                            {{-- SKU Search --}}
                            <div class="relative w-full sm:w-96" x-data="{ showResults: false }">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i data-lucide="search" class="w-4 h-4 text-gray-400"></i>
                                </div>
                                <input type="text" x-model="globalSearch" @input.debounce.300ms="fetchGlobalSkus()"
                                    @focus="showResults = true" @click.away="showResults = false"
                                    placeholder="Search product to add..."
                                    class="w-full border border-gray-300 rounded shadow-sm pl-9 pr-4 py-2.5 text-sm focus:border-brand-500 outline-none bg-white">

                                <ul x-show="showResults && globalSearch.length > 1" x-cloak
                                    class="absolute z-50 w-full bg-white border border-gray-200 rounded-lg shadow-2xl mt-1 max-h-60 overflow-y-auto top-full left-0">

                                    <li x-show="isSearching"
                                        class="px-4 py-3 text-xs text-gray-500 text-center flex justify-center gap-2">
                                        <i data-lucide="loader" class="w-4 h-4 animate-spin text-[#108c2a]"></i>
                                        Searching...
                                    </li>
                                    <li x-show="!isSearching && globalSearchResults.length === 0"
                                        class="px-4 py-4 text-center text-sm text-gray-500">
                                        No items found in selected warehouse.
                                    </li>

                                    <template x-for="result in globalSearchResults" :key="result.product_sku_id">
                                        <li @click="addSkuToTable(result); showResults = false"
                                            class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0 transition-colors">
                                            <div class="flex justify-between items-start gap-2">
                                                <div class="text-[13px] font-bold text-gray-800 leading-tight"
                                                    x-text="result.display_name || result.product_name"></div>
                                                <div class="text-[12px] font-black text-[#108c2a] flex-shrink-0"
                                                    x-text="result.price ? '₹' + parseFloat(result.price).toFixed(2) : '₹0.00'">
                                                </div>
                                            </div>
                                            <div
                                                class="text-[11px] text-gray-500 flex items-center flex-wrap gap-1.5 mt-1.5 font-medium">
                                                <span>Code:</span>
                                                <span
                                                    class="bg-gray-100 border border-gray-200 text-gray-600 px-1 py-0.5 rounded font-mono text-[10px] font-bold tracking-wide"
                                                    x-text="result.sku_code"></span>
                                                <span class="text-gray-300">&middot;</span>
                                                <span x-text="'Stock: ' + (result.stock || 0)"></span>
                                            </div>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>

                        <div class="overflow-x-auto min-h-[150px]">
                            <table class="w-full text-left border-collapse">
                                <thead
                                    class="bg-white border-b border-gray-200 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                                    <tr>
                                        <th class="px-5 py-3 w-1/2">Product</th>
                                        <th class="px-4 py-3 text-right">Price</th>
                                        <th class="px-4 py-3 text-center">Qty</th>
                                        <th class="px-4 py-3 text-right">Total</th>
                                        <th class="px-4 py-3 text-center"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr x-show="items.length === 0">
                                        <td colspan="5" class="text-center py-8 text-gray-400 text-sm">Cart is empty.
                                            Search above to add items.</td>
                                    </tr>
                                    <template x-for="(item, index) in items" :key="item.key">
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="px-5 py-3 align-middle">
                                                <div class="text-[13px] font-bold text-gray-800 leading-tight"
                                                    x-text="item.display_name || item.name"></div>
                                                <div class="flex items-center gap-1.5 mt-1.5">
                                                    <span class="text-gray-500 text-[11px] font-medium">Code:</span>
                                                    <span
                                                        class="bg-[#dcfce7] text-[#16a34a] text-[10px] px-1.5 py-0.5 rounded font-mono font-bold tracking-wide border border-green-200"
                                                        x-text="item.sku"></span>
                                                </div>
                                                {{-- Required payload for backend OrderService --}}
                                                <input type="hidden" :name="'items[' + index + '][sku_id]'"
                                                    :value="item.sku_id">
                                                <input type="hidden" :name="'items[' + index + '][product_id]'"
                                                    :value="item.product_id">
                                            </td>
                                            <td class="px-4 py-3 text-right text-sm font-bold text-gray-600"
                                                x-text="'₹' + item.price"></td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center justify-center">
                                                    <button type="button"
                                                        @click="item.qty = Math.max(1, item.qty - 1); calculate()"
                                                        class="w-7 h-7 border border-gray-300 rounded-l bg-gray-50 text-gray-600">-</button>
                                                    <input type="number" min="1" step="1"
                                                        :name="'items[' + index + '][qty]'" x-model="item.qty"
                                                        @keydown="window.preventInvalidChars($event)"
                                                        @input="item.qty = window.sanitizeQty(item.qty); calculate()"
                                                        class="w-12 h-7 border-y border-x-0 border-gray-300 text-center text-sm font-bold outline-none p-0">
                                                    <button type="button" @click="item.qty++; calculate()"
                                                        class="w-7 h-7 border border-gray-300 rounded-r bg-gray-50 text-gray-600">+</button>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-right font-black text-gray-800 text-sm"
                                                x-text="'₹' + (item.price * item.qty).toFixed(2)"></td>
                                            <td class="px-4 py-3 text-center align-middle">
                                                <button type="button" @click="removeItem(index)"
                                                    class="text-red-400 hover:text-red-600 hover:bg-red-50 p-1.5 rounded transition-colors"
                                                    title="Remove Item">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path
                                                            d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                                        </path>
                                                        <line x1="10" y1="11" x2="10"
                                                            y2="17"></line>
                                                        <line x1="14" y1="11" x2="14"
                                                            y2="17"></line>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- ── 3. META & SUMMARY (SIDEBAR) ── --}}
                <div class="lg:col-span-4 flex flex-col gap-6">

                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
                        <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider border-b border-gray-100 pb-3">
                            Order Configuration</h2>

                        <input type="hidden" name="store_id" value="{{ active_store()?->id }}">

                        {{-- Warehouse Custom Select — Syncs back into local Alpine data model state and clears stale stock lookups --}}
                        <div class="w-full shrink-0"
                            @change="formData.warehouse_id = $event.target.value; globalSearchResults = [];">
                            <label class="block text-[11px] font-bold text-gray-600 uppercase mb-1.5">Warehouse (For
                                Inventory)</label>
                            <x-custom-select name="warehouse_id" placeholder="Select Warehouse" :options="collect($warehouses)->pluck('name', 'id')->toArray()"
                                selected="{{ $warehouses->firstWhere('is_default', true)?->id ?? ($warehouses->first()?->id ?? '') }}"
                                required />
                        </div>

                        <div class="grid grid-cols-2 gap-3 items-end">
                            {{-- Order Status Custom Select --}}
                            <div class="w-full shrink-0">
                                <label class="block text-[11px] font-bold text-gray-600 uppercase mb-1.5">Order
                                    Status</label>
                                <x-custom-select name="status" placeholder="Select Status" :options="[
                                    'confirmed' => 'Confirmed',
                                    'inquiry' => 'Inquiry (Quote)',
                                    'processing' => 'Processing',
                                    'shipped' => 'Shipped',
                                    'delivered' => 'Delivered',
                                ]"
                                    selected="confirmed" />
                            </div>
                            {{-- Payment Status Custom Select --}}
                            <div class="w-full shrink-0">
                                <label class="block text-[11px] font-bold text-gray-600 uppercase mb-1.5">Payment
                                    Status</label>
                                <x-custom-select name="payment_status" placeholder="Select Status" :options="[
                                    'paid' => 'Paid',
                                    'pending' => 'Pending',
                                ]"
                                    selected="paid" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 items-end">
                            {{-- Payment Method Custom Select --}}
                            <div class="w-full shrink-0">
                                <label class="block text-[11px] font-bold text-gray-600 uppercase mb-1.5">Payment
                                    Method</label>
                                <x-custom-select name="payment_method" placeholder="Select Method" :options="[
                                    'cash' => 'Cash',
                                    'card' => 'Card / POS',
                                    'upi' => 'UPI / Online',
                                    'bank_transfer' => 'Bank Transfer',
                                ]"
                                    selected="cash" />
                            </div>
                            {{-- Delivery Type Custom Select --}}
                            <div class="w-full shrink-0">
                                <label class="block text-[11px] font-bold text-gray-600 uppercase mb-1.5">Delivery
                                    Type</label>
                                <x-custom-select name="delivery_type" placeholder="Select Type" :options="[
                                    'pickup' => 'Store Pickup',
                                    'delivery' => 'Delivery',
                                ]"
                                    selected="pickup" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3
                            class="text-sm font-bold text-gray-800 uppercase tracking-wider border-b border-gray-100 pb-3 mb-4">
                            Financial Summary</h3>

                        <div class="space-y-3">
                            <div class="flex justify-between items-center text-sm text-gray-600">
                                <span>Cart Total:</span>
                                <span class="font-bold" x-text="'₹' + totals.cart.toFixed(2)"></span>
                            </div>

                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600">Discount (-):</span>
                                <input type="text" inputmode="numeric" pattern="[0-9]*" name="discount_amount"
                                    x-model="totals.discount"
                                    @input="
                                        totals.discount = $event.target.value.replace(/\D/g, '');
                                        calculate();
                                    "
                                    class="w-24 border border-gray-300 rounded px-2 py-1 text-right text-red-500 font-bold outline-none focus:border-[#108c2a]">
                            </div>

                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600">Shipping (+):</span>
                                <input type="text" inputmode="numeric" pattern="[0-9]*" name="shipping_amount"
                                    x-model="totals.shipping"
                                    @input="
                                        totals.shipping = $event.target.value.replace(/\D/g, '');
                                        calculate();
                                    "
                                    class="w-24 border border-gray-300 rounded px-2 py-1 text-right text-gray-800 font-bold outline-none focus:border-[#108c2a]">
                            </div>

                            <div class="pt-3 border-t border-gray-100 flex justify-between items-end">
                                <div>
                                    <div class="text-[10px] text-gray-400">Taxes calculated on save</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-0.5">Est.
                                        Grand Total</div>
                                    <div class="text-2xl font-black text-[#108c2a]"
                                        x-text="'₹' + Math.max(0, totals.grand).toFixed(2)"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <label class="block text-[11px] font-bold text-gray-600 uppercase mb-1.5">Admin Notes
                            (Internal)</label>
                        <textarea name="admin_notes" rows="3" placeholder="Order instructions or reference..."
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:border-brand-500 outline-none resize-none"></textarea>
                    </div>

                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        function orderForm(allWarehouses = [], allClients = []) {
            return {
                clients: allClients,
                clientSearchTerm: '',
                isClientDropdownOpen: false,
                isWalkIn: false, // Default to false to show the Dropdown first
                globalSearch: '',
                isSearching: false,
                globalSearchResults: [],
                items: [],
                itemCounter: 0,

                formData: {
                    client_id: '',
                    customer_name: '',
                    customer_phone: '',
                    customer_email: '',
                    delivery_address: '',
                    delivery_city: '',
                    delivery_state: '',
                    delivery_pincode: '',
                    store_id: '{{ active_store()?->id ?? '' }}',
                    warehouse_id: '{{ $warehouses->firstWhere('is_default', true)?->id ?? ($warehouses->first()?->id ?? '') }}',
                },

                get filteredClients() {
                    if (this.clientSearchTerm.trim() === '') return this.clients;
                    return this.clients.filter(c =>
                        (c.name || '').toLowerCase().includes(this.clientSearchTerm.toLowerCase()) ||
                        (c.phone || '').includes(this.clientSearchTerm)
                    );
                },

                selectClient(client) {
                    this.formData.client_id = client.id;
                    this.clientSearchTerm = client.name;

                    // Auto-fill form
                    this.formData.customer_name = client.name || '';
                    this.formData.customer_phone = client.phone || '';
                    this.formData.customer_email = client.email || '';
                    this.formData.delivery_address = client.address || '';
                    this.formData.delivery_city = client.city || '';
                    // 🛡️ Bulletproof fallback: Checks loaded relation -> direct attribute -> empty
                    this.formData.delivery_state = (client.state && client.state.name) ? client.state.name : (client
                        .state_name || '');
                    this.formData.delivery_pincode = client.zip_code || '';

                    this.isClientDropdownOpen = false;
                },

                totals: {
                    cart: 0,
                    discount: 0,
                    shipping: 0,
                    grand: 0
                },

                init() {
                    setTimeout(() => {
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    }, 100);
                },

                toggleWalkIn() {
                    this.isWalkIn = !this.isWalkIn;
                    if (this.isWalkIn) {
                        // Switched to Walk-In: Clear everything so user can type freely
                        this.formData.client_id = '';
                        this.clientSearchTerm = '';
                        this.formData.customer_name = '';
                        this.formData.customer_phone = '';
                        this.formData.customer_email = '';
                        this.formData.delivery_address = '';
                        this.formData.delivery_city = '';
                        this.formData.delivery_state = '';
                        this.formData.delivery_pincode = '';
                    }
                },

                async fetchGlobalSkus() {
                    if (!this.formData.warehouse_id) {
                        BizAlert.toast('Please select a warehouse first to check stock.', 'error');
                        this.globalSearch = '';
                        return;
                    }

                    if (this.globalSearch.length < 2) return;

                    this.isSearching = true;

                    try {
                        let response = await fetch(
                            `{{ route('admin.orders.search-skus') }}?term=${encodeURIComponent(this.globalSearch)}&warehouse_id=${this.formData.warehouse_id}`
                        );

                        if (!response.ok) throw new Error('Search failed');

                        this.globalSearchResults = await response.json();
                    } catch (error) {
                        console.error("Search Error:", error);
                    } finally {
                        this.isSearching = false;
                    }
                },

                addSkuToTable(result) {
                    // Prevent duplicate items in the table
                    if (this.items.some(item => item.sku_id === result.product_sku_id)) {
                        BizAlert.toast('This product is already added!', 'error');
                        this.globalSearch = '';
                        this.showResults = false;
                        return;
                    }

                    this.items.push({
                        key: this.itemCounter++,
                        product_id: result.product_id,
                        sku_id: result.product_sku_id,
                        name: result.product_name,
                        display_name: result.display_name || result.product_name, // 🌟 Map the variant name
                        sku: result.sku_code,
                        price: parseFloat(result.price) || 0,
                        qty: 1
                    });

                    this.globalSearch = '';
                    this.calculate();
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                    this.calculate();
                },

                calculate() {
                    let sum = 0;
                    this.items.forEach(item => {
                        sum += (parseFloat(item.price) * parseFloat(item.qty));
                    });

                    this.totals.cart = sum;

                    let disc = parseFloat(this.totals.discount) || 0;
                    let ship = parseFloat(this.totals.shipping) || 0;

                    this.totals.grand = this.totals.cart - disc + ship;
                }
            }
        }
    </script>
@endpush
