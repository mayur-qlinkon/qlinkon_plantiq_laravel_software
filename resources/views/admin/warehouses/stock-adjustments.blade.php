@extends ('layouts.admin')

@section('title', 'Stock Adjustments')

@section('header-title')
    <h1 class="text-xs font-bold tracking-widest text-gray-400 uppercase sm:text-sm">Stock Adjustments</h1>
@endsection

@section('content')
    <div class="space-y-4 pb-10 sm:space-y-6" x-data="stockAdjustments()">
        @if (session('success'))
            <script>
                document.addEventListener("DOMContentLoaded", () => BizAlert.toast("{{ session('success') }}", "success"));
            </script>
        @endif

        @if (session('error'))
            <script>
                document.addEventListener("DOMContentLoaded", () => BizAlert.toast("{{ session('error') }}", "error"));
            </script>
        @endif

        {{-- 🌟 RESPONSIVE HEADER --}}
        <div class="flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
            <div class="w-full lg:w-auto">
                <p class="text-xs font-medium text-gray-500 sm:text-sm">Track and apply manual stock corrections across your
                    warehouses.</p>
            </div>

            <div class="flex w-full flex-col items-center gap-3 sm:flex-row lg:w-auto">
                {{-- Search Bar (client-side filter on current page) --}}
                <div class="relative w-full shrink-0 sm:w-64">
                    <i data-lucide="search" class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" x-model="search" placeholder="Search product, SKU or warehouse..."
                        class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-xl border border-gray-200 bg-white py-2.5 pr-3 pl-9 text-sm text-gray-700 shadow-sm transition-all outline-none placeholder:text-gray-400 focus:ring-2" />
                </div>

                {{-- Adjust Stock Button --}}
                @if (has_permission('warehouses.view'))
                    <button type="button" @click="openModal()"
                        class="bg-brand-500 hover:bg-brand-600 flex w-full shrink-0 items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold whitespace-nowrap text-white shadow-md transition-all active:scale-95 sm:w-auto">
                        <i data-lucide="sliders-horizontal" class="h-5 w-5"></i> Adjust Stock
                    </button>
                @endif
            </div>
        </div>

        {{-- 🌟 MAIN TABLE CARD --}}
        <div class="flex flex-col overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
            {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
            <div class="custom-scrollbar hidden overflow-x-auto md:block">
                <table class="w-full min-w-[900px] text-left text-sm whitespace-nowrap">
                    <thead
                        class="border-b border-gray-100 bg-[#f8fafc] text-[10px] font-bold tracking-wider text-gray-400 uppercase sm:text-[11px]">
                        <tr>
                            <th class="px-4 py-3 sm:px-6 sm:py-4">PRODUCT / SKU</th>
                            <th class="px-4 py-3 sm:px-6 sm:py-4">WAREHOUSE</th>
                            <th class="px-4 py-3 text-center sm:px-6 sm:py-4">DIRECTION</th>
                            <th class="px-4 py-3 text-right sm:px-6 sm:py-4">QTY</th>
                            <th class="px-4 py-3 text-right sm:px-6 sm:py-4">BALANCE AFTER</th>
                            <th class="px-4 py-3 sm:px-6 sm:py-4">REASON / NOTE</th>
                            <th class="px-4 py-3 sm:px-6 sm:py-4">BY</th>
                            <th class="px-4 py-3 text-right sm:px-6 sm:py-4">DATE</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($movements as $movement)
                            @php
                                $productName = $movement->sku->product->name ?? 'Deleted Product';
                                $skuCode = $movement->sku->sku ?? 'N/A';
                                $warehouseName = $movement->warehouse->name ?? 'Deleted Warehouse';
                                $rawNote = (string) $movement->note;
                                $reasonLabel = 'other';
                                $extraNote = '';
                                if (preg_match('/^\[(.*?)\]\s*(.*)$/', $rawNote, $m)) {
                                    $reasonLabel = $m[1];
                                    $extraNote = $m[2];
                                }
                            @endphp
                            <tr class="transition-colors hover:bg-gray-50/50"
                                x-show="matchesSearch('{{ strtolower(addslashes($productName)) }}', '{{ strtolower(addslashes($skuCode)) }}', '{{ strtolower(addslashes($warehouseName)) }}')">
                                <td class="px-4 py-3 sm:px-6 sm:py-4">
                                    <div class="flex flex-col">
                                        <span
                                            class="text-xs font-bold text-[#475569] sm:text-[13.5px]">{{ $productName }}</span>
                                        <span
                                            class="bg-brand-50 text-brand-600 mt-1 w-fit rounded px-1.5 py-0.5 text-[9px] font-black uppercase sm:text-[10px]">
                                            SKU: {{ $skuCode }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-3 sm:px-6 sm:py-4">
                                    <div class="flex items-center gap-1.5 text-xs font-medium text-gray-600 sm:text-sm">
                                        <i data-lucide="warehouse" class="h-3.5 w-3.5 text-gray-300"></i>
                                        {{ $warehouseName }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-center sm:px-6 sm:py-4">
                                    @if ($movement->direction === 'in')
                                        <span
                                            class="inline-flex items-center gap-1 rounded-md bg-[#dcfce7] px-2.5 py-0.5 text-[9px] font-bold text-[#16a34a] uppercase sm:text-[10px]">
                                            <i data-lucide="arrow-up" class="h-3 w-3"></i> In
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-1 rounded-md bg-red-50 px-2.5 py-0.5 text-[9px] font-bold text-red-500 uppercase sm:text-[10px]">
                                            <i data-lucide="arrow-down" class="h-3 w-3"></i> Out
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-right text-xs font-bold text-gray-700 sm:px-6 sm:py-4 sm:text-sm">
                                    {{ rtrim(rtrim(number_format((float) $movement->quantity, 2), '0'), '.') }}
                                </td>

                                <td class="px-4 py-3 text-right text-xs font-bold text-gray-500 sm:px-6 sm:py-4 sm:text-sm">
                                    {{ rtrim(rtrim(number_format((float) $movement->balance_after, 2), '0'), '.') }}
                                </td>

                                <td class="px-4 py-3 sm:px-6 sm:py-4">
                                    <div class="flex max-w-[220px] flex-col">
                                        <span
                                            class="w-fit rounded bg-amber-50 px-1.5 py-0.5 text-[9px] font-black text-amber-600 uppercase sm:text-[10px]">
                                            {{ str_replace('_', ' ', $reasonLabel) }}
                                        </span>
                                        @if ($extraNote)
                                            <span class="mt-1 truncate text-[11px] text-gray-400 italic"
                                                title="{{ $extraNote }}">{{ $extraNote }}</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 py-3 sm:px-6 sm:py-4">
                                    <span
                                        class="text-xs font-medium text-gray-600 sm:text-[13px]">{{ $movement->user->name ?? 'System' }}</span>
                                </td>

                                <td class="px-4 py-3 text-right sm:px-6 sm:py-4">
                                    <div class="flex flex-col items-end">
                                        <span
                                            class="text-xs font-medium text-gray-600">{{ $movement->created_at->format('d M Y') }}</span>
                                        <span
                                            class="text-[11px] text-gray-400">{{ $movement->created_at->format('h:i A') }}</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <i data-lucide="sliders-horizontal" class="mb-2 h-10 w-10 opacity-20"></i>
                                        <p class="text-sm font-medium">No stock adjustments recorded yet.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 📱 MOBILE VIEW (CARDS) --}}
            <div class="divide-y divide-gray-50 border-t border-gray-50 bg-white md:hidden">
                @forelse ($movements as $movement)
                    @php
                        $productName = $movement->sku->product->name ?? 'Deleted Product';
                        $skuCode = $movement->sku->sku ?? 'N/A';
                        $warehouseName = $movement->warehouse->name ?? 'Deleted Warehouse';
                        $rawNote = (string) $movement->note;
                        $reasonLabel = 'other';
                        $extraNote = '';
                        if (preg_match('/^\[(.*?)\]\s*(.*)$/', $rawNote, $m)) {
                            $reasonLabel = $m[1];
                            $extraNote = $m[2];
                        }
                    @endphp
                    <div class="flex flex-col gap-3 p-4 transition-colors hover:bg-gray-50/50"
                        x-show="matchesSearch('{{ strtolower(addslashes($productName)) }}', '{{ strtolower(addslashes($skuCode)) }}', '{{ strtolower(addslashes($warehouseName)) }}')">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate text-[14px] font-bold text-gray-900">{{ $productName }}</p>
                                <span
                                    class="bg-brand-50 text-brand-600 mt-1 inline-block rounded px-1.5 py-0.5 text-[9px] font-black uppercase">
                                    SKU: {{ $skuCode }}
                                </span>
                            </div>
                            <div class="shrink-0">
                                @if ($movement->direction === 'in')
                                    <span
                                        class="inline-flex items-center gap-1 rounded-md bg-[#dcfce7] px-2 py-0.5 text-[9px] font-bold text-[#16a34a] uppercase">
                                        <i data-lucide="arrow-up" class="h-3 w-3"></i> In
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center gap-1 rounded-md bg-red-50 px-2 py-0.5 text-[9px] font-bold text-red-500 uppercase">
                                        <i data-lucide="arrow-down" class="h-3 w-3"></i> Out
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 rounded-lg border border-gray-100 bg-gray-50/80 px-3 py-2.5">
                            <div class="flex items-center justify-between">
                                <div class="flex min-w-0 items-center gap-1.5">
                                    <i data-lucide="warehouse" class="h-3.5 w-3.5 shrink-0 text-gray-400"></i>
                                    <span class="truncate text-[12px] font-bold text-gray-700">{{ $warehouseName }}</span>
                                </div>
                                <span
                                    class="shrink-0 text-[11px] font-medium text-gray-500">{{ $movement->created_at->format('d M, h:i A') }}</span>
                            </div>
                            <div class="flex items-center justify-between border-t border-gray-100/50 pt-1.5">
                                <span class="text-[11px] font-medium text-gray-500">Qty:
                                    <b
                                        class="text-gray-700">{{ rtrim(rtrim(number_format((float) $movement->quantity, 2), '0'), '.') }}</b></span>
                                <span class="text-[11px] font-medium text-gray-500">Balance:
                                    <b
                                        class="text-gray-700">{{ rtrim(rtrim(number_format((float) $movement->balance_after, 2), '0'), '.') }}</b></span>
                            </div>
                            <div class="flex items-center justify-between border-t border-gray-100/50 pt-1.5">
                                <span
                                    class="w-fit rounded bg-amber-50 px-1.5 py-0.5 text-[9px] font-black text-amber-600 uppercase">
                                    {{ str_replace('_', ' ', $reasonLabel) }}
                                </span>
                                <span class="text-[11px] text-gray-400">{{ $movement->user->name ?? 'System' }}</span>
                            </div>
                            @if ($extraNote)
                                <div class="truncate border-t border-gray-100/50 pt-1 text-[11px] text-gray-400 italic"
                                    title="{{ $extraNote }}">
                                    {{ $extraNote }}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="bg-white p-8 text-center text-sm text-gray-400">
                        <div class="flex flex-col items-center justify-center">
                            <i data-lucide="sliders-horizontal" class="mb-2 h-10 w-10 opacity-20"></i>
                            <p class="font-medium text-gray-500">No stock adjustments recorded yet.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- 🌟 Pagination Controls --}}
            @if ($movements->hasPages())
                <div class="border-t border-gray-100 bg-white px-4 py-4 sm:px-6">{{ $movements->links() }}</div>
            @endif
        </div>

        {{-- ============================================================ --}}
        {{-- 🌟 QUICK ADJUST MODAL --}}
        {{-- ============================================================ --}}
        @php
            // Prepare arrays for our custom select components
            $warehouseOptions = [];
            foreach ($warehouses as $warehouse) {
                $warehouseOptions[$warehouse->id] =
                    $warehouse->name . ($warehouse->city ? " — {$warehouse->city}" : '');
            }

            $typeOptions = [
                'add' => 'Add (+)',
                'remove' => 'Remove (-)',
                'set' => 'Set to Value',
            ];

            $reasonOptions = [
                'opening_stock' => 'Opening Stock',
                'correction' => 'Correction',
                'physical_count' => 'Physical Count',
                'damage' => 'Damage',
                'expiry' => 'Expiry',
                'theft' => 'Theft',
                'other' => 'Other',
            ];
        @endphp
        <template x-teleport="body">
            <div x-show="modalOpen" x-cloak class="fixed inset-0 z-[100] flex items-end justify-center sm:items-center"
                @keydown.escape.window="closeModal()">
                {{-- Backdrop --}}
                <div x-show="modalOpen" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0" class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"
                    @click="closeModal()"></div>

                {{-- Panel --}}
                <div x-show="modalOpen" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                    class="relative flex max-h-[92vh] w-full flex-col overflow-hidden rounded-t-2xl bg-white shadow-xl sm:max-w-lg sm:rounded-2xl">
                    {{-- Header --}}
                    <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-5 py-4 sm:px-6">
                        <h3 class="flex items-center gap-2 text-sm font-bold text-gray-800 sm:text-base">
                            <i data-lucide="sliders-horizontal" class="text-brand-500 h-4 w-4"></i>
                            Adjust Stock
                        </h3>
                        <button type="button" @click="closeModal()"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>

                    {{-- Body (scrollable with extra bottom padding to prevent dropdown clipping) --}}
                    <div class="custom-scrollbar flex-1 space-y-4 overflow-y-auto px-5 pt-5 pb-44 sm:px-6">
                        {{-- Product / SKU search --}}
                        <div class="relative">
                            <label class="mb-1.5 block text-xs font-bold text-gray-600">Product / SKU <span
                                    class="text-red-500">*</span></label>

                            <template x-if="!selectedSku">
                                <div class="relative">
                                    <i data-lucide="search"
                                        class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                                    <input type="text" x-model="skuQuery" @input.debounce.300ms="searchSkus()"
                                        @focus="skuDropdownOpen = true" placeholder="Type product name or SKU code..."
                                        autocomplete="off"
                                        class="w-full rounded-xl border py-2.5 pr-9 pl-9 text-sm transition-all outline-none"
                                        :class="errors.sku_id ?
                                            'border-red-300 focus:ring-2 focus:ring-red-500/20 focus:border-red-500' :
                                            'border-gray-200 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500'" />
                                    <i x-show="skuSearching" data-lucide="loader-2"
                                        class="absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 animate-spin text-gray-400"></i>
                                </div>
                            </template>

                            {{-- Selected chip --}}
                            <template x-if="selectedSku">
                                <div
                                    class="border-brand-200 bg-brand-50/60 flex items-center justify-between gap-2 rounded-xl border px-3.5 py-2.5">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-gray-800"
                                            x-text="selectedSku.product_name"></p>
                                        <p class="text-brand-600 text-[11px] font-bold uppercase"
                                            x-text="'SKU: ' + selectedSku.sku_code"></p>
                                    </div>
                                    <button type="button" @click="clearSku()"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-white hover:text-red-500">
                                        <i data-lucide="x" class="h-4 w-4"></i>
                                    </button>
                                </div>
                            </template>

                            {{-- Dropdown results --}}
                            <div x-show="
                                    skuDropdownOpen &&
                                    !selectedSku &&
                                    (skuResults.length > 0 || (skuQuery.length >= 2 && !skuSearching))
                                "
                                x-cloak @click.outside="skuDropdownOpen = false"
                                class="custom-scrollbar absolute z-20 mt-1.5 max-h-56 w-full overflow-y-auto rounded-xl border border-gray-100 bg-white shadow-lg">
                                <template x-for="item in skuResults" :key="item.sku_id">
                                    <button type="button" @click="pickSku(item)"
                                        class="w-full border-b border-gray-50 px-3.5 py-2.5 text-left transition-colors last:border-0 hover:bg-gray-50">
                                        <p class="truncate text-xs font-bold text-gray-700" x-text="item.product_name">
                                        </p>
                                        <p class="text-[10px] font-bold text-gray-400 uppercase"
                                            x-text="'SKU: ' + item.sku_code"></p>
                                    </button>
                                </template>
                                <div x-show="skuQuery.length >= 2 && !skuSearching && skuResults.length === 0"
                                    class="px-3.5 py-4 text-center text-xs text-gray-400">
                                    No matching product or SKU found.
                                </div>
                            </div>

                            <p x-show="errors.sku_id" x-text="errors.sku_id"
                                class="mt-1 text-[11px] font-medium text-red-500"></p>
                        </div>

                        {{-- Warehouse --}}
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-600">Warehouse <span
                                    class="text-red-500">*</span></label>
                            <div :key="modalOpen" @change="form.warehouse_id = $event.target.value">
                                <x-custom-select name="warehouse_id" placeholder="Select warehouse..." :options="$warehouseOptions"
                                    ::class="errors.warehouse_id ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' :
                                        ''" />
                            </div>
                            <p x-show="errors.warehouse_id" x-text="errors.warehouse_id"
                                class="mt-1 text-[11px] font-medium text-red-500"></p>
                        </div>

                        {{-- Type + Qty --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1.5 block text-xs font-bold text-gray-600">Adjustment Type <span
                                        class="text-red-500">*</span></label>
                                <div :key="modalOpen" @change="form.type = $event.target.value">
                                    <x-custom-select name="type" :options="$typeOptions" selected="add" ::class="errors.type ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : ''" />
                                </div>
                                <p x-show="errors.type" x-text="errors.type"
                                    class="mt-1 text-[11px] font-medium text-red-500"></p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-bold text-gray-600">
                                    <span x-text="form.type === 'set' ? 'New Quantity' : 'Quantity'"></span>
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="text" inputmode="numeric" data-int data-int-min="0"
                                    x-model.number="form.qty"
                                    placeholder="0"
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm transition-all outline-none"
                                    :class="errors.qty ?
                                        'border-red-300 focus:ring-2 focus:ring-red-500/20 focus:border-red-500' :
                                        'border-gray-200 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500'" />
                                <p x-show="errors.qty" x-text="errors.qty"
                                    class="mt-1 text-[11px] font-medium text-red-500"></p>
                            </div>
                        </div>

                        {{-- Reason --}}
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-600">Reason <span
                                    class="text-red-500">*</span></label>
                            <div :key="modalOpen" @change="form.reason = $event.target.value">
                                <x-custom-select name="reason" placeholder="Select reason..." :options="$reasonOptions"
                                    ::class="errors.reason ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : ''" />
                            </div>
                            <p x-show="errors.reason" x-text="errors.reason"
                                class="mt-1 text-[11px] font-medium text-red-500"></p>
                        </div>

                        {{-- Note --}}
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-600">Note <span
                                    class="font-medium text-gray-400 normal-case">(optional)</span></label>
                            <textarea x-model="form.note" maxlength="255" rows="2" placeholder="Any additional context..."
                                class="focus:ring-brand-500/20 focus:border-brand-500 w-full resize-none rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm transition-all outline-none focus:ring-2"></textarea>
                            <p class="mt-1 text-right text-[10px] text-gray-400"
                                x-text="(form.note?.length || 0) + '/255'"></p>
                        </div>

                        {{-- General/server error banner --}}
                        <div x-show="generalError" x-cloak
                            class="flex items-start gap-2 rounded-xl border border-red-100 bg-red-50 px-3.5 py-2.5">
                            <i data-lucide="alert-triangle" class="mt-0.5 h-4 w-4 shrink-0 text-red-500"></i>
                            <p class="text-xs font-medium text-red-600" x-text="generalError"></p>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div
                        class="flex shrink-0 items-center justify-end gap-3 border-t border-gray-100 bg-gray-50/60 px-5 py-4 sm:px-6">
                        <button type="button" @click="closeModal()" :disabled="submitting"
                            class="rounded-xl px-4 py-2.5 text-sm font-bold text-gray-500 transition-colors hover:bg-gray-100 disabled:opacity-50">
                            Cancel
                        </button>
                        <button type="button" @click="submitAdjustment()" :disabled="submitting"
                            class="bg-brand-500 hover:bg-brand-600 flex min-w-[130px] items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold text-white shadow-md transition-all active:scale-95 disabled:opacity-60 disabled:active:scale-100">
                            <i x-show="submitting" data-lucide="loader-2" class="h-4 w-4 animate-spin"></i>
                            <i x-show="!submitting" data-lucide="check" class="h-4 w-4"></i>
                            <span x-text="submitting ? 'Saving...' : 'Save Adjustment'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endsection

@push('scripts')
    <script>
        function stockAdjustments() {
            return {
                search: "",
                modalOpen: false,
                submitting: false,
                generalError: "",

                skuQuery: "",
                skuResults: [],
                skuSearching: false,
                skuDropdownOpen: false,
                skuSearchController: null,
                selectedSku: null,

                form: {
                    warehouse_id: "",
                    type: "add",
                    qty: null,
                    reason: "",
                    note: "",
                },

                errors: {},

                init() {
                    if (window.lucide) window.lucide.createIcons();
                },

                // Client-side filter for the history table on the current page
                matchesSearch(productName, skuCode, warehouseName) {
                    if (this.search.trim() === "") return true;
                    const query = this.search.toLowerCase();
                    return productName.includes(query) || skuCode.includes(query) || warehouseName.includes(query);
                },

                openModal() {
                    this.resetForm();
                    this.modalOpen = true;
                    document.body.style.overflow = "hidden";
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                },

                closeModal() {
                    if (this.submitting) return;
                    this.modalOpen = false;
                    document.body.style.overflow = "";
                },

                resetForm() {
                    this.form = {
                        warehouse_id: "",
                        type: "add",
                        qty: null,
                        reason: "",
                        note: ""
                    };
                    this.errors = {};
                    this.generalError = "";
                    this.selectedSku = null;
                    this.skuQuery = "";
                    this.skuResults = [];
                    this.skuDropdownOpen = false;
                },

                async searchSkus() {
                    const query = this.skuQuery.trim();
                    this.skuDropdownOpen = true;

                    if (query.length < 2) {
                        this.skuResults = [];
                        return;
                    }

                    // Abort any in-flight request so late responses can't overwrite fresher ones
                    if (this.skuSearchController) this.skuSearchController.abort();
                    this.skuSearchController = new AbortController();

                    this.skuSearching = true;
                    try {
                        const res = await fetch(
                            `{{ route('admin.stock-adjustments.search-skus') }}?q=${encodeURIComponent(query)}`, {
                                headers: {
                                    Accept: "application/json"
                                },
                                signal: this.skuSearchController.signal,
                            });

                        if (!res.ok) throw new Error("Search failed");
                        const data = await res.json();
                        this.skuResults = Array.isArray(data) ? data : [];
                    } catch (e) {
                        if (e.name !== "AbortError") {
                            this.skuResults = [];
                        }
                    } finally {
                        this.skuSearching = false;
                    }
                },

                pickSku(item) {
                    this.selectedSku = item;
                    this.skuDropdownOpen = false;
                    this.skuQuery = "";
                    this.skuResults = [];
                    if (this.errors.sku_id) delete this.errors.sku_id;
                },

                clearSku() {
                    this.selectedSku = null;
                    this.skuQuery = "";
                    this.skuResults = [];
                },

                validate() {
                    this.errors = {};
                    let valid = true;

                    if (!this.selectedSku) {
                        this.errors.sku_id = "Please select a product / SKU.";
                        valid = false;
                    }
                    if (!this.form.warehouse_id) {
                        this.errors.warehouse_id = "Please select a warehouse.";
                        valid = false;
                    }
                    if (!this.form.type) {
                        this.errors.type = "Please select an adjustment type.";
                        valid = false;
                    }
                    if (this.form.qty === null || this.form.qty === "" || isNaN(this.form.qty)) {
                        this.errors.qty = "Please enter a quantity.";
                        valid = false;
                    } else if (Number(this.form.qty) < 0) {
                        this.errors.qty = "Quantity cannot be negative.";
                        valid = false;
                    } else if (!Number.isInteger(Number(this.form.qty))) {
                        this.errors.qty = "Quantity must be a whole number.";
                        valid = false;
                    }
                    if (!this.form.reason) {
                        this.errors.reason = "Please select a reason.";
                        valid = false;
                    }
                    if (this.form.note && this.form.note.length > 255) {
                        this.errors.note = "Note must be under 255 characters.";
                        valid = false;
                    }

                    return valid;
                },

                async submitAdjustment() {
                    this.generalError = "";

                    if (!this.validate()) {
                        return;
                    }

                    this.submitting = true;

                    const url =
                        `{{ url('admin/stock-adjustments') }}/${this.selectedSku.product_id}/skus/${this.selectedSku.sku_id}/adjust-stock`;

                    try {
                        const res = await fetch(url, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                                    "content") || "",
                            },
                            body: JSON.stringify({
                                warehouse_id: this.form.warehouse_id,
                                type: this.form.type,
                                qty: this.form.qty,
                                reason: this.form.reason,
                                note: this.form.note || null,
                            }),
                        });

                        const data = await res.json().catch(() => null);

                        if (res.status === 422 && data?.errors) {
                            // Laravel validation error bag shape: { errors: { field: [msg, ...] } }
                            const mapped = {};
                            Object.keys(data.errors).forEach((key) => {
                                mapped[key] = Array.isArray(data.errors[key]) ? data.errors[key][0] : data
                                    .errors[key];
                            });
                            this.errors = mapped;
                            return;
                        }

                        if (!res.ok || !data || data.success === false) {
                            this.generalError =
                                data?.message || "Something went wrong while saving the adjustment. Please try again.";
                            return;
                        }

                        BizAlert.toast(data.message || "Stock adjusted successfully.", "success");
                        this.closeModal();

                        // Reload so the history table + balances reflect the fresh data
                        setTimeout(() => window.location.reload(), 600);
                    } catch (e) {
                        this.generalError = "Network error — please check your connection and try again.";
                    } finally {
                        this.submitting = false;
                    }
                },
            };
        }
    </script>
@endpush
