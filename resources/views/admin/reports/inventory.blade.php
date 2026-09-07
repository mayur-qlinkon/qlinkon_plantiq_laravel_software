@extends('layouts.admin')

@section('title', 'Inventory Report')

@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Inventory Report</h1>
@endsection

@section('content')
    <div
    class="pb-20"
    x-data="{
        activeTab: new URLSearchParams(window.location.search).get('tab') || 'master',
        expandedRows: [],
    
        toggleRow(id) {
            if (this.expandedRows.includes(id)) {
                this.expandedRows = this.expandedRows.filter(rowId => rowId !== id);
            } else {
                this.expandedRows.push(id);
            }
        },

        setTab(tab) {
            this.activeTab = tab;

            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.history.pushState({}, '', url);
        },

        handleShortcut(e) {
            const tag = e.target.tagName.toLowerCase();

            // Do not trigger shortcuts while typing in inputs, textareas, selects, or editable fields
            if (['input', 'textarea', 'select'].includes(tag) || e.target.isContentEditable) {
                return;
            }

            // Alt + 1 / 2 / 3 / 4
            if (e.altKey && !e.ctrlKey && !e.shiftKey) {
                if (e.key === '1') {
                    e.preventDefault();
                    this.setTab('master');
                }

                if (e.key === '2') {
                    e.preventDefault();
                    this.setTab('alerts');
                }

                if (e.key === '3') {
                    e.preventDefault();
                    this.setTab('ledger');
                }

                if (e.key === '4' && {{ batch_enabled() && $batchReport ? 'true' : 'false' }}) {
                    e.preventDefault();
                    this.setTab('batches');
                }
            }

            // Optional: Arrow left / right to move between tabs
            if (e.key === 'ArrowRight') {
                e.preventDefault();
                this.nextTab();
            }

            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                this.prevTab();
            }
        },

        nextTab() {
            const tabs = ['master', 'alerts', 'ledger'{{ batch_enabled() && $batchReport ? ", 'batches'" : '' }}];
            const currentIndex = tabs.indexOf(this.activeTab);
            const nextIndex = (currentIndex + 1) % tabs.length;
            this.setTab(tabs[nextIndex]);
        },

        prevTab() {
            const tabs = ['master', 'alerts', 'ledger'{{ batch_enabled() && $batchReport ? ", 'batches'" : '' }}];
            const currentIndex = tabs.indexOf(this.activeTab);
            const prevIndex = (currentIndex - 1 + tabs.length) % tabs.length;
            this.setTab(tabs[prevIndex]);
        },

        ledgerSearch: '{{ request('search_movement', '') }}',
        ledgerLoading: false,
        _ledgerTimer: null,

        fetchLedger() {
            clearTimeout(this._ledgerTimer);
            this._ledgerTimer = setTimeout(async () => {
                this.ledgerLoading = true;
                try {
                    const url = new URL('{{ route('admin.inventory.reports.ledger') }}');
                    url.searchParams.set('search_movement', this.ledgerSearch);
                    const res  = await fetch(url.toString(), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const html = await res.text();
                    document.getElementById('ledger-results').innerHTML = html;
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                } catch(e) {
                    console.error('Ledger search failed:', e);
                } finally {
                    this.ledgerLoading = false;
                }
            }, 400);
        }
    }"
    @keydown.window="handleShortcut($event)"
>

        {{-- 1. TOP METRICS OVERVIEW --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-center gap-4">
                <div class="bg-blue-50 p-4 rounded-lg text-blue-600">
                    <i data-lucide="boxes" class="w-8 h-8"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-1">Total Valuation</p>
                    <h2 class="text-3xl font-black text-gray-900">₹ {{ number_format($totalValuation, 2) }}</h2>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-center gap-4">
                <div class="bg-red-50 p-4 rounded-lg text-red-600">
                    <i data-lucide="alert-triangle" class="w-8 h-8"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-1">Low Stock Alerts</p>
                    <h2 class="text-3xl font-black text-gray-900">{{ $lowStockAlerts->total() }} <span class="text-sm font-medium text-gray-500">Items</span></h2>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-center gap-4">
                <div class="bg-green-50 p-4 rounded-lg text-green-600">
                    <i data-lucide="arrow-left-right" class="w-8 h-8"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-1">Recent Movements</p>
                    <h2 class="text-3xl font-black text-gray-900">{{ $movements->total() }} <span class="text-sm font-medium text-gray-500">Logs</span></h2>
                </div>
            </div>
        </div>

        {{-- 2. REPORT TABS --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            
            {{-- Tab Headers --}}
            <div class="flex border-b border-gray-200 bg-gray-50 px-2 pt-2 gap-2 overflow-x-auto no-scrollbar">
                <button @click="setTab('master')" 
                    :class="activeTab === 'master' ? 'bg-white text-blue-600 border-t-2 border-t-blue-600 shadow-sm' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'"
                    class="px-6 py-3 text-sm font-bold uppercase tracking-wider rounded-t-lg transition-all whitespace-nowrap">
                    <i data-lucide="database" class="w-4 h-4 inline-block mr-1.5 pb-0.5"></i> Master Stock
                    <span class="hidden md:inline-flex ml-2 inline-flex items-center rounded bg-gray-800 px-1.5 py-0.5 text-[9px] font-semibold text-white tracking-wider">
                        ALT+1
                    </span>
                </button>
                <button @click="setTab('alerts')" 
                    :class="activeTab === 'alerts' ? 'bg-white text-red-600 border-t-2 border-t-red-600 shadow-sm' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'"
                    class="px-6 py-3 text-sm font-bold uppercase tracking-wider rounded-t-lg transition-all whitespace-nowrap flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 inline-block pb-0.5"></i> 
                    Reorder Alerts
                    @if($lowStockAlerts->total() > 0)
                        <span class="bg-red-500 text-white text-[10px] px-2 rounded-full">{{ $lowStockAlerts->total() }}</span>
                    @endif
                    <span class="hidden md:inline-flex ml-2 inline-flex items-center rounded bg-gray-800 px-1.5 py-0.5 text-[9px] font-semibold text-white tracking-wider">
                        ALT+2
                    </span>
                </button>
                <button @click="setTab('ledger')"
                    :class="activeTab === 'ledger' ? 'bg-white text-gray-900 border-t-2 border-t-gray-800 shadow-sm' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'"
                    class="px-6 py-3 text-sm font-bold uppercase tracking-wider rounded-t-lg transition-all whitespace-nowrap">
                    <i data-lucide="history" class="w-4 h-4 inline-block mr-1.5 pb-0.5"></i> Movement Ledger
                    <span class="hidden md:inline-flex ml-2 inline-flex items-center rounded bg-gray-800 px-1.5 py-0.5 text-[9px] font-semibold text-white tracking-wider">
                        ALT+3
                    </span>
                </button>
                @if(batch_enabled())
                <button @click="setTab('batches')"
                    :class="activeTab === 'batches' ? 'bg-white text-indigo-600 border-t-2 border-t-indigo-600 shadow-sm' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'"
                    class="px-6 py-3 text-sm font-bold uppercase tracking-wider rounded-t-lg transition-all whitespace-nowrap">
                    <i data-lucide="layers" class="w-4 h-4 inline-block mr-1.5 pb-0.5"></i> Batch Tracking
                    <span class="hidden md:inline-flex ml-2 inline-flex items-center rounded bg-gray-800 px-1.5 py-0.5 text-[9px] font-semibold text-white tracking-wider">
                        ALT+4
                    </span>
                </button>
                @endif
            </div>

            {{-- 3. TAB CONTENT: MASTER STOCK --}}
            <div x-show="activeTab === 'master'" x-cloak class="p-0">
                
                {{-- 🖥️ DESKTOP VIEW --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[900px]">
                        <thead class="bg-white border-b border-gray-200 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4 w-12"></th>
                                <th class="px-6 py-4">Product & SKU</th>
                                <th class="px-6 py-4">Category</th>
                                <th class="px-6 py-4 text-right">Unit COGS</th>
                                <th class="px-6 py-4 text-right">Total Qty</th>
                                <th class="px-6 py-4 text-right">Total Value</th>
                                <th class="px-6 py-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($masterStock as $sku)
                                @php
                                    $isLow = $sku->total_qty <= $sku->stock_alert;
                                    $isOut = $sku->total_qty <= 0;
                                @endphp
                                @php
                                    $variantStr = $sku->skuValues?->map(fn($v) => $v->attributeValue?->value)->filter()->implode(' - ');
                                    $displayName = $variantStr ? ($sku->product?->name . ' - ' . $variantStr) : ($sku->product?->name ?? 'Unknown Product');
                                @endphp
                                <tr class="hover:bg-gray-50 transition-colors cursor-pointer" @click="toggleRow({{ $sku->id }})">
                                    <td class="px-6 py-4 text-center">
                                        <i data-lucide="chevron-down" 
                                           class="w-4 h-4 text-gray-400 transition-transform duration-200"
                                           :class="expandedRows.includes({{ $sku->id }}) ? 'rotate-180' : ''"></i>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900 text-sm">{{ $displayName }}</div>
                                        <div class="text-[11px] text-gray-500 font-mono mt-0.5">SKU: {{ $sku->sku }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-xs font-medium text-gray-600">
                                        {{ $sku->product->category->name ?? 'Uncategorized' }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-semibold text-gray-700">
                                        ₹ {{ number_format($sku->cost, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="text-sm font-black {{ $isOut ? 'text-red-600' : 'text-gray-900' }}">
                                            {{ (float) $sku->total_qty }} 
                                        </span>
                                        <span class="text-[10px] text-gray-500 ml-1">{{ $sku->product->productUnit->short_name ?? 'Units' }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-black text-brand-600">
                                        ₹ {{ number_format($sku->total_qty * $sku->cost, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if ($isOut)
                                            <span class="bg-red-50 text-red-700 border border-red-200 px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider">Out of Stock</span>
                                        @elseif ($isLow)
                                            <span class="bg-orange-50 text-orange-700 border border-orange-200 px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider">Low Stock</span>
                                        @else
                                            <span class="bg-green-50 text-green-700 border border-green-200 px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider">Healthy</span>
                                        @endif
                                    </td>
                                </tr>
                                
                                {{-- EXPANDABLE: Warehouse Breakdown --}}
                                <tr x-show="expandedRows.includes({{ $sku->id }})" x-cloak class="bg-gray-50 border-b border-gray-200">
                                    <td colspan="7" class="px-14 py-4">
                                        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                                            <div class="bg-gray-100 px-4 py-2 border-b border-gray-200 text-[10px] font-bold text-gray-500 uppercase tracking-widest">
                                                Warehouse Breakdown
                                            </div>
                                            <table class="w-full text-left text-xs">
                                                <tbody class="divide-y divide-gray-100">
                                                    @forelse($sku->stocks as $stock)
                                                        <tr>
                                                            <td class="px-4 py-2 font-medium text-gray-800">
                                                                {{ $stock->warehouse->name }}
                                                                <span class="text-gray-400 ml-1">({{ $stock->warehouse->store->name ?? 'Primary' }})</span>
                                                            </td>
                                                            <td class="px-4 py-2 text-right font-bold text-gray-900">
                                                                {{ (float) $stock->qty }}
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="2" class="px-4 py-3 text-center text-gray-400 italic">No physical stock recorded in any warehouse.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500 font-medium">No inventory data available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- 📱 MOBILE VIEW (CARDS) --}}
                <div class="md:hidden divide-y divide-gray-50">
                    @forelse ($masterStock as $sku)
                        @php
                            $isLow = $sku->total_qty <= $sku->stock_alert;
                            $isOut = $sku->total_qty <= 0;
                        @endphp
                        <div class="p-4 bg-white flex flex-col gap-3">
                            <div class="flex justify-between items-start gap-2">
                                <div class="min-w-0">
                                    <p class="font-bold text-gray-900 text-[14px] leading-tight">{{ $sku->product?->name ?? 'Unknown Product' }}</p>
                                    <p class="text-[11px] text-gray-500 font-mono mt-0.5">SKU: {{ $sku->sku }} • <span class="font-sans font-medium">{{ $sku->product->category->name ?? 'Uncategorized' }}</span></p>
                                </div>
                                <div class="text-right shrink-0">
                                    @if ($isOut)
                                        <span class="bg-red-50 text-red-700 border border-red-200 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider">Out of Stock</span>
                                    @elseif ($isLow)
                                        <span class="bg-orange-50 text-orange-700 border border-orange-200 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider">Low Stock</span>
                                    @else
                                        <span class="bg-green-50 text-green-700 border border-green-200 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider">Healthy</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center justify-between bg-gray-50/80 px-3 py-2.5 rounded-lg border border-gray-100">
                                <div>
                                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Total Qty</p>
                                    <p class="text-[14px] font-black {{ $isOut ? 'text-red-600' : 'text-gray-900' }}">
                                        {{ (float) $sku->total_qty }} <span class="text-[10px] text-gray-500 font-medium">{{ $sku->product->productUnit->short_name ?? 'Units' }}</span>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Value</p>
                                    <p class="text-[13px] font-bold text-brand-600">₹ {{ number_format($sku->total_qty * $sku->cost, 2) }}</p>
                                </div>
                            </div>

                            <button @click="toggleRow({{ $sku->id }})" class="text-[11px] font-bold text-gray-500 flex items-center justify-center gap-1 bg-gray-50 py-1.5 rounded-md hover:bg-gray-100 transition-colors">
                                <i data-lucide="layout-list" class="w-3.5 h-3.5"></i> 
                                <span x-text="expandedRows.includes({{ $sku->id }}) ? 'Hide Warehouses' : 'View Warehouses'"></span>
                            </button>

                            {{-- Mobile Expanded Warehouse Breakdown --}}
                            <div x-show="expandedRows.includes({{ $sku->id }})" x-cloak class="mt-1 border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                                <div class="bg-gray-100 px-3 py-2 border-b border-gray-200 text-[10px] font-bold text-gray-500 uppercase tracking-widest">
                                    Warehouse Breakdown
                                </div>
                                <div class="divide-y divide-gray-100">
                                    @forelse($sku->stocks as $stock)
                                        <div class="flex justify-between items-center px-3 py-2 text-xs">
                                            <span class="font-medium text-gray-800">{{ $stock->warehouse->name }} <span class="text-gray-400">({{ $stock->warehouse->store->name ?? 'Primary' }})</span></span>
                                            <span class="font-bold text-gray-900">{{ (float) $stock->qty }}</span>
                                        </div>
                                    @empty
                                        <div class="px-3 py-3 text-center text-gray-400 italic text-xs">No physical stock recorded.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-500 font-medium bg-white text-sm">No inventory data available.</div>
                    @endforelse
                </div>

                <div class="p-4 border-t border-gray-100">
                    {{ $masterStock->appends(['tab' => 'master'])->links() }}
                </div>
            </div>

            {{-- 4. TAB CONTENT: LOW STOCK ALERTS --}}
            <div x-show="activeTab === 'alerts'" x-cloak class="p-0">
                
                {{-- 🖥️ DESKTOP VIEW --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[750px]">
                        <thead class="bg-red-50 border-b border-red-100 text-[11px] font-bold text-red-800 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Product & SKU</th>
                                <th class="px-6 py-4 text-center">Alert Threshold</th>
                                <th class="px-6 py-4 text-center">Current Qty</th>
                                <th class="px-6 py-4 text-center">Deficit</th>
                                <th class="px-6 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($lowStockAlerts as $alert)
                                @php 
                                    $deficit = $alert->stock_alert - $alert->current_qty; 
                                    $variantStr = $alert->skuValues?->map(fn($v) => $v->attributeValue?->value)->filter()->implode(' - ');
                                    $displayName = $variantStr ? ($alert->product?->name . ' - ' . $variantStr) : ($alert->product?->name ?? 'Unknown Product');
                                @endphp
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900 text-sm">{{ $displayName }}</div>
                                        <div class="text-[11px] text-gray-500 font-mono mt-0.5">SKU: {{ $alert->sku }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-center font-semibold text-gray-600">
                                        {{ (float) $alert->stock_alert }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="font-black text-lg {{ $alert->current_qty <= 0 ? 'text-red-600' : 'text-orange-600' }}">
                                            {{ (float) $alert->current_qty }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-bold whitespace-nowrap">
                                            -{{ (float) $deficit }} Short
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        {{-- Placeholder for future Purchase Order feature --}}
                                        <a href="{{ route('admin.purchases.create', ['sku_id' => $alert->id, 'qty' => $deficit]) }}" 
                                            class="inline-block bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-3 py-1.5 rounded text-xs font-bold transition-colors shadow-sm text-center whitespace-nowrap">
                                            Reorder
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center text-green-600">
                                            <i data-lucide="check-circle" class="w-12 h-12 mb-3 opacity-50"></i>
                                            <p class="font-bold text-lg">All Stock is Healthy!</p>
                                            <p class="text-sm text-gray-500 mt-1">No items are currently below their alert thresholds.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- 📱 MOBILE VIEW (CARDS) --}}
                <div class="md:hidden divide-y divide-red-100 bg-red-50/20">
                    @forelse ($lowStockAlerts as $alert)
                        @php 
                            $deficit = $alert->stock_alert - $alert->current_qty; 
                            $variantStr = $alert->skuValues?->map(fn($v) => $v->attributeValue?->value)->filter()->implode(' - ');
                            $displayName = $variantStr ? ($alert->product?->name . ' - ' . $variantStr) : ($alert->product?->name ?? 'Unknown Product');
                        @endphp
                        <div class="p-4 flex flex-col gap-3">
                            <div class="flex justify-between items-start gap-2">
                                <div class="min-w-0">
                                    <p class="font-bold text-gray-900 text-[14px] leading-tight">{{ $displayName }}</p>
                                    <p class="text-[11px] text-gray-500 font-mono mt-0.5">SKU: {{ $alert->sku }}</p>
                                </div>
                                <span class="bg-red-100 text-red-800 px-1.5 py-0.5 rounded text-[10px] font-bold whitespace-nowrap shrink-0 border border-red-200">
                                    -{{ (float) $deficit }} Short
                                </span>
                            </div>

                            <div class="flex items-center justify-between bg-white px-3 py-2.5 rounded-lg border border-red-100 shadow-sm">
                                <div>
                                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Alert Threshold</p>
                                    <p class="text-[13px] font-semibold text-gray-600">{{ (float) $alert->stock_alert }}</p>
                                </div>
                                <i data-lucide="arrow-right" class="w-4 h-4 text-gray-300"></i>
                                <div class="text-right">
                                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Current Qty</p>
                                    <p class="text-[15px] font-black {{ $alert->current_qty <= 0 ? 'text-red-600' : 'text-orange-600' }}">{{ (float) $alert->current_qty }}</p>
                                </div>
                            </div>

                            <a href="{{ route('admin.purchases.create', ['sku_id' => $alert->id, 'qty' => $deficit]) }}" class="w-full bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 py-2 rounded-lg text-xs font-bold transition-colors shadow-sm text-center flex items-center justify-center gap-1.5">
                                <i data-lucide="shopping-cart" class="w-3.5 h-3.5"></i> Reorder Now
                            </a>
                        </div>
                    @empty
                        <div class="p-8 text-center bg-white">
                            <div class="flex flex-col items-center justify-center text-green-600">
                                <i data-lucide="check-circle" class="w-10 h-10 mb-2 opacity-50"></i>
                                <p class="font-bold text-sm">All Stock is Healthy!</p>
                            </div>
                        </div>
                    @endforelse
                </div>

                <div class="p-4 border-t border-gray-100">
                    {{ $lowStockAlerts->appends(['tab' => 'alerts'])->links() }}
                </div>
            </div>

            {{-- 5. TAB CONTENT: MOVEMENT LEDGER --}}
            <div x-show="activeTab === 'ledger'" x-cloak class="p-0">
                
                {{-- Optional Search Bar for Ledger --}}
                 <div class="p-4 border-b border-gray-100 bg-white flex justify-end">
                    <div class="relative w-full md:w-72">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
                        <input type="text"
                            x-model="ledgerSearch"
                            @input.debounce.400ms="fetchLedger()"
                            placeholder="Search SKU or Ref ID..."
                            class="w-full pl-9 py-2 border border-gray-300 rounded-lg text-sm focus:border-gray-500 outline-none transition-all"
                            :class="ledgerLoading ? 'pr-9' : 'pr-4'">
                        <svg x-show="ledgerLoading" x-cloak
                            class="absolute right-3 top-1/2 -translate-y-1/2 animate-spin w-4 h-4 text-gray-400 pointer-events-none"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                    </div>
                </div>

               <div id="ledger-results">
                    {{-- 🖥️ DESKTOP VIEW --}}
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[850px]">
                            <thead class="bg-gray-50 border-b border-gray-200 text-[10px] font-bold text-gray-500 uppercase tracking-wider">
                                <tr>
                                    <th class="px-4 py-3">Date & Time</th>
                                    <th class="px-4 py-3">Product</th>
                                    <th class="px-4 py-3">Warehouse</th>
                                    <th class="px-4 py-3">Movement Type</th>
                                    <th class="px-4 py-3 text-right">Qty</th>
                                    <th class="px-4 py-3 text-right">Balance</th>
                                    <th class="px-4 py-3">User</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($movements as $log)
                                    @php
                                        $variantStr = $log->sku?->skuValues?->map(fn($v) => $v->attributeValue?->value)->filter()->implode(' - ');
                                        $displayName = $variantStr ? ($log->sku->product?->name . ' - ' . $variantStr) : ($log->sku->product?->name ?? 'Unknown Product');
                                    @endphp
                                    <tr class="hover:bg-gray-50 transition-colors text-sm">
                                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                            <div class="font-bold text-gray-800">{{ $log->created_at->format('d M Y') }}</div>
                                            <div class="text-[10px]">{{ $log->created_at->format('h:i A') }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-bold text-gray-900 line-clamp-1">{{ $displayName }}</div>
                                            <div class="text-[10px] text-gray-500 font-mono mt-0.5">SKU: {{ $log->sku->sku ?? '-' }}</div>
                                        </td>
                                        <td class="px-4 py-3 font-medium text-gray-700">
                                            {{ $log->warehouse->name ?? 'Unknown' }}
                                        </td>
                                       <td class="px-4 py-3">
                                        @php
                                            $badgeClass = match($log->movement_type) {
                                                'transfer_in', 'opening' => 'bg-blue-50 text-blue-700 border-blue-200',
                                                'transfer_out' => 'bg-purple-50 text-purple-700 border-purple-200',
                                                'sale' => 'bg-green-50 text-green-700 border-green-200',
                                                'adjustment' => 'bg-orange-50 text-orange-700 border-orange-200',
                                                default => 'bg-gray-50 text-gray-700 border-gray-200',
                                            };
                                            $adjReason = $adjNote = '';
                                            if ($log->movement_type === 'adjustment' && $log->note) {
                                                preg_match('/^\[([^\]]+)\](.*)$/', $log->note, $m);
                                                $adjReason = $m[1] ?? $log->note;
                                                $adjNote   = trim($m[2] ?? '');
                                            }
                                        @endphp
                                        <div class="inline-flex items-center gap-1.5">
                                            <span class="inline-block border px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-widest {{ $badgeClass }}">
                                                {{ str_replace('_', ' ', $log->movement_type) }}
                                            </span>
                                            @if($log->movement_type === 'adjustment' && $log->note)
                                                <div class="group relative">
                                                    <i data-lucide="info" class="w-3.5 h-3.5 text-orange-400 hover:text-orange-600 cursor-help transition-colors"></i>
                                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2.5 w-max max-w-[220px] bg-gray-900 text-white text-[11px] rounded-xl px-3.5 py-2.5 invisible group-hover:visible opacity-0 group-hover:opacity-100 transition-all duration-150 z-[999] shadow-2xl pointer-events-none">
                                                        <p class="font-black text-orange-300 uppercase tracking-widest text-[10px]">{{ $adjReason }}</p>
                                                        @if($adjNote)
                                                            <p class="text-gray-300 mt-1 leading-relaxed font-normal normal-case tracking-normal">{{ $adjNote }}</p>
                                                        @endif
                                                        <div class="absolute top-full left-1/2 -translate-x-1/2 border-[5px] border-transparent border-t-gray-900"></div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                        <td class="px-4 py-3 text-right font-black text-lg {{ $log->quantity > 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $log->quantity > 0 ? '+' : '' }}{{ (float) $log->quantity }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-bold text-gray-900">
                                            {{ (float) $log->balance_after }}
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-500 font-medium">
                                            {{ $log->user->name ?? 'System' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 font-medium">No movements recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- 📱 MOBILE VIEW (CARDS) --}}
                    <div class="md:hidden divide-y divide-gray-50 bg-white">
                        @forelse ($movements as $log)
                            @php
                                $badgeClass = match($log->movement_type) {
                                    'transfer_in', 'opening' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'transfer_out' => 'bg-purple-50 text-purple-700 border-purple-200',
                                    'sale' => 'bg-green-50 text-green-700 border-green-200',
                                    'adjustment' => 'bg-orange-50 text-orange-700 border-orange-200',
                                    default => 'bg-gray-50 text-gray-700 border-gray-200',
                                };
                                $variantStr = $log->sku?->skuValues?->map(fn($v) => $v->attributeValue?->value)->filter()->implode(' - ');
                                $displayName = $variantStr ? ($log->sku->product?->name . ' - ' . $variantStr) : ($log->sku->product?->name ?? 'Unknown Product');
                            @endphp
                            <div class="p-4 flex flex-col gap-2.5">
                                <div class="flex justify-between items-start">
                                    <div class="min-w-0">
                                        <p class="font-bold text-gray-900 text-[13px] leading-tight truncate">{{ $displayName }}</p>
                                        <p class="text-[10px] text-gray-500 font-mono mt-0.5">SKU: {{ $log->sku->sku ?? '-' }}</p>
                                    </div>
                                    <span class="shrink-0 border px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-widest {{ $badgeClass }}">
                                        {{ str_replace('_', ' ', $log->movement_type) }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between text-[11px] text-gray-500">
                                    <span class="flex items-center gap-1"><i data-lucide="clock" class="w-3 h-3"></i> {{ $log->created_at->format('d M y, h:i A') }}</span>
                                    <span class="flex items-center gap-1"><i data-lucide="map-pin" class="w-3 h-3"></i> {{ $log->warehouse->name ?? 'Unknown' }}</span>
                                </div>

                                <div class="flex items-center justify-between bg-gray-50/80 px-3 py-2 rounded-lg border border-gray-100 mt-1">
                                    <div>
                                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider block mb-0.5">Qty Change</span>
                                        <span class="font-black text-[14px] {{ $log->quantity > 0 ? 'text-green-600' : 'text-red-600' }}">{{ $log->quantity > 0 ? '+' : '' }}{{ (float) $log->quantity }}</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider block mb-0.5">New Balance</span>
                                        <span class="font-bold text-gray-900 text-[13px]">{{ (float) $log->balance_after }}</span>
                                    </div>
                                </div>
                                <p class="text-[9px] text-gray-400 text-right">Logged by: {{ $log->user->name ?? 'System' }}</p>
                            </div>
                        @empty
                            <div class="p-8 text-center text-gray-500 font-medium text-sm">No movements recorded yet.</div>
                        @endforelse
                    </div>

                    <div class="p-4 border-t border-gray-100">
                        {{ $movements->appends(['tab' => 'ledger', 'search_movement' => request('search_movement')])->links() }}
                    </div>
                </div>
            </div>

            {{-- TAB CONTENT: BATCH TRACKING --}}
            @if(batch_enabled() && $batchReport)
            <div x-show="activeTab === 'batches'" x-cloak class="p-0">
                
                {{-- 🖥️ DESKTOP VIEW --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[950px]">
                        <thead class="bg-white border-b border-gray-200 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Product & SKU</th>
                                <th class="px-6 py-4">Batch #</th>
                                <th class="px-6 py-4">Warehouse</th>
                                <th class="px-6 py-4">Supplier</th>
                                <th class="px-6 py-4 text-center">Mfg Date</th>
                                <th class="px-6 py-4 text-center">Expiry Date</th>
                                <th class="px-6 py-4 text-right">Orig Qty</th>
                                <th class="px-6 py-4 text-right">Remaining</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($batchReport as $batch)
                                @php
                                    $isExpired = $batch->expiry_date && $batch->expiry_date->isPast();
                                    $isExpiringSoon = $batch->expiry_date && !$isExpired && $batch->expiry_date->diffInDays(now()) <= 30;
                                    $variantStr = $batch->sku?->skuValues?->map(fn($v) => $v->attributeValue?->value)->filter()->implode(' - ');
                                    $displayName = $variantStr ? ($batch->sku->product?->name . ' - ' . $variantStr) : ($batch->sku->product?->name ?? 'Unknown Product');
                                @endphp
                                <tr class="hover:bg-gray-50 transition-colors {{ $isExpired ? 'bg-red-50' : '' }}">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900">{{ $displayName }}</div>
                                        <div class="text-[10px] text-gray-500 font-mono mt-0.5">{{ $batch->sku->sku ?? '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-sm font-bold text-gray-800">
                                        {{ $batch->batch_number ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">{{ $batch->warehouse->name ?? '-' }}</td>
                                    <td class="px-6 py-4 text-gray-700">{{ $batch->supplier->name ?? '-' }}</td>
                                    <td class="px-6 py-4 text-center text-gray-600">
                                        {{ $batch->manufacturing_date ? $batch->manufacturing_date->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($batch->expiry_date)
                                            <span class="font-semibold {{ $isExpired ? 'text-red-600' : ($isExpiringSoon ? 'text-orange-500' : 'text-gray-700') }}">
                                                {{ $batch->expiry_date->format('d/m/Y') }}
                                                @if($isExpired)
                                                    <span class="ml-1 text-[9px] bg-red-100 text-red-700 px-1 py-0.5 rounded">EXPIRED</span>
                                                @elseif($isExpiringSoon)
                                                    <span class="ml-1 text-[9px] bg-orange-100 text-orange-700 px-1 py-0.5 rounded">EXPIRING</span>
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right text-gray-700">{{ (float) $batch->qty }}</td>
                                    <td class="px-6 py-4 text-right font-bold {{ $batch->remaining_qty <= 0 ? 'text-red-500' : 'text-green-600' }}">
                                        {{ (float) $batch->remaining_qty }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-500 font-medium">No active batches found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- 📱 MOBILE VIEW (CARDS) --}}
                <div class="md:hidden divide-y divide-gray-50 bg-white">
                    @forelse($batchReport as $batch)
                        @php
                            $isExpired = $batch->expiry_date && $batch->expiry_date->isPast();
                            $isExpiringSoon = $batch->expiry_date && !$isExpired && $batch->expiry_date->diffInDays(now()) <= 30;
                            $variantStr = $batch->sku?->skuValues?->map(fn($v) => $v->attributeValue?->value)->filter()->implode(' - ');
                            $displayName = $variantStr ? ($batch->sku->product?->name . ' - ' . $variantStr) : ($batch->sku->product?->name ?? 'Unknown Product');
                        @endphp
                        <div class="p-4 flex flex-col gap-3 {{ $isExpired ? 'bg-red-50/30' : '' }}">
                            
                            <div class="flex justify-between items-start gap-2">
                                <div class="min-w-0">
                                    <p class="font-bold text-gray-900 text-[13px] leading-tight truncate">{{ $displayName }}</p>
                                    <p class="text-[10px] text-gray-500 font-mono mt-0.5">{{ $batch->sku->sku ?? '-' }}</p>
                                </div>
                                <span class="bg-gray-100 text-gray-800 border border-gray-200 px-1.5 py-0.5 rounded text-[10px] font-bold font-mono shrink-0">
                                    {{ $batch->batch_number ?? '-' }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-[11px]">
                                <div class="text-gray-600"><span class="font-semibold text-gray-400">Whs:</span> {{ $batch->warehouse->name ?? '-' }}</div>
                                <div class="text-gray-600 text-right truncate"><span class="font-semibold text-gray-400">Sup:</span> {{ $batch->supplier->name ?? '-' }}</div>
                            </div>

                            <div class="flex items-center justify-between bg-gray-50/80 px-3 py-2 rounded-lg border border-gray-100">
                                <div>
                                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider block mb-0.5">Mfg Date</span>
                                    <span class="font-semibold text-gray-700">{{ $batch->manufacturing_date ? $batch->manufacturing_date->format('d/m/Y') : '-' }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider block mb-0.5">Expiry Date</span>
                                    @if($batch->expiry_date)
                                        <span class="font-semibold {{ $isExpired ? 'text-red-600' : ($isExpiringSoon ? 'text-orange-500' : 'text-gray-700') }}">
                                            {{ $batch->expiry_date->format('d/m/Y') }}
                                        </span>
                                        @if($isExpired)
                                            <span class="ml-1 text-[8px] bg-red-100 text-red-700 px-1 py-0.5 rounded font-bold">EXPIRED</span>
                                        @elseif($isExpiringSoon)
                                            <span class="ml-1 text-[8px] bg-orange-100 text-orange-700 px-1 py-0.5 rounded font-bold">SOON</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex justify-between items-center pt-1">
                                <span class="text-[10px] text-gray-500 font-medium">Orig: {{ (float) $batch->qty }}</span>
                                <span class="text-[13px] font-black {{ $batch->remaining_qty <= 0 ? 'text-red-500' : 'text-green-600' }}">
                                    {{ (float) $batch->remaining_qty }} <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Remaining</span>
                                </span>
                            </div>

                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-500 font-medium text-sm">No active batches found.</div>
                    @endforelse
                </div>

                <div class="p-4 border-t border-gray-100">
                    {{ $batchReport->appends(['tab' => 'batches'])->links() }}
                </div>
            </div>
            @endif

        </div>
    </div>
@endsection