<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    {{-- 🌟 CRITICAL: Needed for the checkout and quick-client AJAX requests --}}
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>POS Terminal - {{ config('app.name') }}</title>

    <link rel="icon" type="image/png"
        href="{{ get_setting('favicon') ? asset('storage/' . get_setting('favicon')) : asset('assets/icons/favicon.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset_v('assets/css/tailwind.min.css') }}" />

    {{-- 🌟 CRITICAL: Alpine.js for the posEngine --}}
    <script src="{{ asset('assets/js/lucide.min.js') }}"></script>
    <script src="{{ asset('assets/js/sweetalert2.js') }}"></script>
    <script defer src="{{ asset('assets/js/alpinejs.min.js') }}"></script>
    <script defer src="{{ asset('assets/js/helpers.js') }}"></script>

    @php
        $primary = get_setting('primary_color', '#008a62');
        $hover = get_setting('primary_hover_color', '#007050');
    @endphp
    <style>
        /* ═══════════════════════════════════════════════
           THEME VARIABLES
        ═══════════════════════════════════════════════ */
        :root {
            --brand-50: {{ $primary }}1A;
            --brand-100: {{ $primary }}33;
            --brand-600: {{ $hover }};
            --bg-page: #f4f6f9;
            --ease: cubic-bezier(0.4, 0, 0.2, 1);
        }

        [x-cloak] {
            display: none !important;
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .product-card {
            box-shadow: 0 2px 10px -3px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body class="flex h-[100dvh] overflow-hidden bg-white font-sans text-gray-800 select-none">
    {{-- 🌟 MAIN ALPINE.JS WRAPPER: This wraps everything so the left and right panes can talk to each other --}}
    {{-- 🌟 Pass clients and payment methods into the engine --}}
    <div x-data="posEngine('{{ $companyState ?? '' }}', @js($clients ?? []), @js($paymentMethods ?? []), @js($warehouses->map->only(['id', 'name'])->values()))" @keydown.window="handleGlobalScan($event)"
        class="flex h-full w-full flex-1 overflow-hidden">
        {{-- ========================================== --}}
        {{-- LEFT PANE: PRODUCTS & SEARCH (~70%)        --}}
        {{-- ========================================== --}}
        <div class="flex h-full flex-1 flex-col overflow-hidden bg-white">
            {{-- 1. Search Header --}}
            <header class="flex h-[64px] shrink-0 items-center justify-between border-b border-gray-100 px-5">
                <div class="flex w-full items-center gap-2 lg:w-1/2">
                    <div class="relative w-full max-w-md">
                        <i data-lucide="search"
                            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                        {{-- 🌟 Alpine Binding: x-model and @input.debounce --}}
                        <input type="text" x-model="searchQuery" @input.debounce.300ms="handleSearch()"
                            x-ref="searchInput" placeholder="Search products..."
                            class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-100 bg-gray-50 py-2.5 pr-4 pl-9 text-sm transition-all placeholder:text-gray-400 focus:ring-2 focus:outline-none" />
                    </div>
                    @if (has_permission('pos.create_quick_product'))
                        <button @click="isProductModalOpen = true" title="Quick Add Product" style="margin-right: 10px"
                            class="bg-brand-500 hover:bg-brand-600 flex items-center justify-center rounded p-2 text-white shadow-sm transition-colors">
                            <i data-lucide="plus" class="h-5 w-5"></i>
                        </button>
                    @endif
                </div>

                <div class="flex shrink-0 items-center gap-2 lg:gap-4">
                    {{-- Loading Indicator --}}
                    <div x-show="isLoading" x-cloak class="text-brand-500 flex items-center gap-2 text-xs font-bold">
                        <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span class="hidden lg:inline">Loading...</span>
                    </div>
                    <div
                        class="border-brand-700 bg-brand-700 flex items-center overflow-hidden rounded border text-white shadow-sm">
                        {{-- 🌟 Forces focus back to the search bar for barcode scanning --}}
                        <button @click="openScanner()" title="Camera Scanner"
                            class="hover:bg-brand-600 border-brand-600 border-r p-2 transition-colors">
                            <i data-lucide="scan-barcode" class="h-5 w-5"></i>
                        </button>
                        {{-- 🌟 Opens the POS History modal (recent POS bills only) --}}
                        <button @click="openHistoryModal()" title="POS History"
                            class="hover:bg-brand-600 block p-2 transition-colors">
                            <i data-lucide="clock" class="h-5 w-5"></i>
                        </button>
                    </div>
                </div>
            </header>

            {{-- 2. Category Filter & Switchers Bar --}}
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 gap-4">

                {{-- LEFT: Scrollable Category List --}}
                <div class="no-scrollbar flex flex-1 items-center gap-2.5 overflow-x-auto scroll-smooth pr-4">
                    <button @click="setCategory('')"
                        :class="activeCategory === ''
                            ?
                            'bg-brand-500 text-white shadow-md border-brand-500' :
                            'bg-gray-50 text-gray-600 hover:bg-gray-100 border-gray-200'"
                        class="shrink-0 flex items-center gap-2 rounded-xl border px-4 py-2 text-[13px] font-bold whitespace-nowrap transition-all">
                        <i data-lucide="layout-grid" class="h-4 w-4"></i> All
                    </button>

                    @foreach ($categories as $category)
                        <button @click="setCategory({{ $category->id }})"
                            :class="activeCategory === {{ $category->id }} ?
                                'bg-brand-500 text-white shadow-md border-brand-500' :
                                'bg-gray-50 text-gray-600 hover:bg-gray-100 border-gray-200'"
                            class="shrink-0 rounded-xl border px-4 py-2 text-[13px] font-bold whitespace-nowrap transition-all">
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>

                {{-- RIGHT: Store & Warehouse Switchers --}}
                {{-- Both controls share one shape — same height, padding, icon
                     size and chevron — so they read as a pair. The store used a
                     custom dropdown and the warehouse a native select, which
                     left them different heights and sitting off each other's
                     baseline. --}}
                <div class="hidden shrink-0 items-center gap-2 border-l border-gray-200 pl-4 md:flex">

                    {{-- Store Switcher --}}
                    @if (isset($canSwitchStore) && $canSwitchStore)
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" type="button"
                                :class="open ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-500/15' :
                                    'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'"
                                class="flex h-[38px] items-center gap-2 rounded-xl border pr-2.5 pl-3 shadow-sm transition-all">
                                <i data-lucide="store" class="text-brand-600 h-4 w-4 shrink-0"></i>
                                <span class="flex flex-col items-start leading-none">
                                    <span
                                        class="text-[9px] font-bold tracking-widest text-gray-400 uppercase">Store</span>
                                    <span class="mt-0.5 max-w-[110px] truncate text-[12px] font-bold text-gray-800">
                                        {{ active_store()->name ?? 'Select' }}
                                    </span>
                                </span>
                                <i data-lucide="chevron-down"
                                    class="h-3.5 w-3.5 shrink-0 text-gray-400 transition-transform"
                                    :class="open && 'rotate-180'"></i>
                            </button>

                            <div x-cloak x-show="open" @click.away="open = false"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="absolute right-0 z-50 mt-2 w-60 overflow-hidden rounded-xl border border-gray-100 bg-white p-1.5 shadow-[0_10px_40px_-8px_rgba(0,0,0,0.18)]">
                                <p
                                    class="px-2.5 pt-1.5 pb-2 text-[9px] font-black tracking-widest text-gray-400 uppercase">
                                    Switch Store
                                </p>
                                @foreach ($stores as $store)
                                    @php $isActive = active_store() && active_store()->id == $store->id; @endphp
                                    <form method="POST" action="{{ route('admin.store.switch') }}">
                                        @csrf
                                        <input type="hidden" name="store_id" value="{{ $store->id }}" />
                                        <button type="submit"
                                            class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2.5 text-left transition-colors {{ $isActive ? 'bg-brand-50 text-brand-700' : 'text-gray-700 hover:bg-gray-50' }}">
                                            <i data-lucide="store"
                                                class="h-4 w-4 shrink-0 {{ $isActive ? 'text-brand-600' : 'text-gray-400' }}"></i>
                                            <span
                                                class="flex-1 truncate text-[13px] {{ $isActive ? 'font-bold' : 'font-medium' }}">
                                                {{ $store->name }}
                                            </span>
                                            @if ($isActive)
                                                <i data-lucide="check" class="text-brand-600 h-4 w-4 shrink-0"></i>
                                            @endif
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    @elseif (active_store())
                        <div
                            class="flex h-[38px] items-center gap-2 rounded-xl border border-gray-100 bg-gray-50 px-3">
                            <i data-lucide="store" class="text-brand-600 h-4 w-4 shrink-0"></i>
                            <span class="flex flex-col items-start leading-none">
                                <span class="text-[9px] font-bold tracking-widest text-gray-400 uppercase">Store</span>
                                <span
                                    class="mt-0.5 max-w-[110px] truncate text-[12px] font-bold text-gray-700">{{ active_store()->name }}</span>
                            </span>
                        </div>
                    @endif

                    {{-- Warehouse Switcher --}}
                    {{-- Built as an Alpine dropdown rather than a native select.
                         A select renders the operating system's own list, so
                         however the pill around it was styled the open menu
                         still looked nothing like the store's. --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" type="button"
                            :class="open ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500/15' :
                                'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'"
                            class="flex h-[38px] items-center gap-2 rounded-xl border pr-2.5 pl-3 shadow-sm transition-all">
                            <i data-lucide="warehouse" class="h-4 w-4 shrink-0 text-blue-500"></i>
                            <span class="flex flex-col items-start leading-none">
                                <span
                                    class="text-[9px] font-bold tracking-widest text-gray-400 uppercase">Warehouse</span>
                                <span class="mt-0.5 max-w-[110px] truncate text-[12px] font-bold text-gray-800"
                                    x-text="warehouses.find(w => w.id == warehouse_id)?.name || 'Select'"></span>
                            </span>
                            <i data-lucide="chevron-down"
                                class="h-3.5 w-3.5 shrink-0 text-gray-400 transition-transform"
                                :class="open && 'rotate-180'"></i>
                        </button>

                        <div x-cloak x-show="open" @click.away="open = false"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="absolute right-0 z-50 mt-2 w-60 overflow-hidden rounded-xl border border-gray-100 bg-white p-1.5 shadow-[0_10px_40px_-8px_rgba(0,0,0,0.18)]">
                            <p
                                class="px-2.5 pt-1.5 pb-2 text-[9px] font-black tracking-widest text-gray-400 uppercase">
                                Switch Warehouse
                            </p>
                            <template x-for="wh in warehouses" :key="wh.id">
                                <button type="button"
                                    @click="
                                        open = false;
                                        if (warehouse_id != wh.id) {
                                            warehouse_id = wh.id;
                                            changeWarehouse();
                                        }
                                    "
                                    :class="warehouse_id == wh.id ? 'bg-blue-50 text-blue-700' :
                                        'text-gray-700 hover:bg-gray-50'"
                                    class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2.5 text-left transition-colors">
                                    <i data-lucide="warehouse" class="h-4 w-4 shrink-0"
                                        :class="warehouse_id == wh.id ? 'text-blue-500' : 'text-gray-400'"></i>
                                    <span class="flex-1 truncate text-[13px]"
                                        :class="warehouse_id == wh.id ? 'font-bold' : 'font-medium'"
                                        x-text="wh.name"></span>
                                    <i data-lucide="check" class="h-4 w-4 shrink-0 text-blue-500"
                                        x-show="warehouse_id == wh.id"></i>
                                </button>
                            </template>
                        </div>
                    </div>

                </div>
            </div>

            {{-- 3. Product Grid & Infinite Scroll --}}
            <div class="no-scrollbar relative flex-1 overflow-y-auto p-5"
                @scroll="
                    if ($event.target.scrollHeight - $event.target.scrollTop - $event.target.clientHeight < 300)
                        loadMore();
                ">
                {{-- Empty State (No Products Found) --}}
                <div x-show="!isLoading && products.length === 0" x-cloak
                    class="absolute inset-0 flex flex-col items-center justify-center text-gray-400">
                    <i data-lucide="package-x" class="mb-3 h-12 w-12 opacity-20"></i>
                    <p class="font-medium">No products found</p>
                </div>

                <div class="grid grid-cols-2 gap-3 pb-28 transition-all duration-300 sm:grid-cols-3 sm:gap-4 md:grid-cols-4 lg:grid-cols-3 lg:pb-6 xl:grid-cols-4 2xl:grid-cols-5"
                    :class="isLoading && page === 1 ?
                        'opacity-50 blur-[2px] pointer-events-none' :
                        'opacity-100 blur-0'">
                    {{-- 🌟 Sleek POS Card Template --}}
                    <template x-for="product in products" :key="product.product_sku_id">
                        <div @click="product.stock > 0 ? addToCart(product) : null"
                            class="hover:border-brand-500 group relative flex cursor-pointer flex-col overflow-hidden rounded-xl border border-gray-200 bg-white transition-all hover:shadow-md">
                            {{-- Image Section with Overlays --}}
                            <div
                                class="relative aspect-[4/3] w-full shrink-0 overflow-hidden bg-gray-50 sm:aspect-[3/2]">
                                <img :src="product.image_url || '/assets/defaults/product.svg'"
                                    :alt="product.product_name"
                                    onerror="this.onerror=null; this.src='/assets/defaults/product.svg';"
                                    class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />

                                {{-- Top Left Overlay: Price (Matches your reference image) --}}
                                <div class="absolute top-0 left-0 z-10 rounded-br-xl bg-[#5b61f4] px-2.5 py-1 text-[12px] font-black text-white shadow-sm"
                                    x-text="'₹' + parseFloat(product.display_price).toFixed(2)"></div>

                                {{-- Top Right Overlay: Stock & Unit (Matches your reference image) --}}
                                <div class="absolute top-0 right-0 z-10 rounded-bl-xl bg-[#0ea5e9] px-2 py-1 text-[10px] font-bold tracking-wide text-white uppercase shadow-sm"
                                    x-text="product.stock + ' ' + (product.unit_name || '')"></div>

                                {{-- Out of Stock Blocker (Faded overlay so you can't click it) --}}
                                <div x-show="product.stock <= 0"
                                    class="absolute inset-0 z-20 flex items-center justify-center bg-white/70 backdrop-blur-[1px]">
                                    <span
                                        class="rounded-lg bg-red-500 px-2.5 py-1 text-[10px] font-black tracking-widest text-white uppercase shadow-sm">Out
                                        of Stock</span>
                                </div>
                            </div>

                            {{-- Info Section (Bottom) --}}
                            <div class="flex flex-1 flex-col justify-center border-t border-gray-50 bg-white p-3">
                                <h3 class="group-hover:text-brand-600 mb-0.5 truncate text-[13px] leading-tight font-bold text-gray-800 transition-colors"
                                    x-text="product.display_name || product.product_name"></h3>
                                <div class="flex items-center gap-1.5 opacity-70">
                                    <span
                                        class="truncate font-mono text-[10px] tracking-widest text-gray-500 uppercase"
                                        x-text="product.sku_code || product.barcode"></span>
                                    <template x-if="product.variant_name">
                                        <span class="truncate text-[10px] text-gray-400">• <span class="font-bold"
                                                x-text="product.variant_name"></span></span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Infinite scroll is handled by @scroll on the container above --}}
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- RIGHT PANE: CART & CHECKOUT (~30%)         --}}
        {{-- ========================================== --}}
        {{-- Mobile Overlay Backdrop --}}
        <div x-show="isMobileCartOpen" x-transition.opacity @click="isMobileCartOpen = false"
            class="fixed inset-0 z-40 bg-gray-900/50 backdrop-blur-sm lg:hidden" x-cloak></div>

        <div :class="isMobileCartOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'" {{-- 🌟 Tuned widths: w-[340px] for iPad (lg), w-[400px] for PC (xl) --}}
            class="fixed inset-y-0 right-0 z-50 flex h-full w-full shrink-0 flex-col border-l border-gray-100 bg-[#fdfdfd] shadow-2xl transition-transform duration-300 ease-in-out sm:max-w-[400px] lg:relative lg:w-[340px] lg:shadow-[-5px_0_15px_rgba(0,0,0,0.02)] xl:w-[400px]">
            {{-- 1. Customer Selection --}}
            <div class="shrink-0 border-b border-gray-100 bg-white p-5">
                <div class="mb-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <button @click="isMobileCartOpen = false"
                            class="rounded-lg bg-gray-100 p-1 text-gray-600 lg:hidden">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                        <h2 class="text-[15px] font-bold text-gray-800">Customer</h2>
                    </div>
                </div>

                <div class="mb-3 flex items-center gap-2">
                    {{-- Searchable Input --}}
                    <div class="relative flex-1" @click.away="isClientDropdownOpen = false">
                        <input type="text" x-model="clientSearchTerm" @focus="isClientDropdownOpen = true"
                            @input="
                                isClientDropdownOpen = true;
                                customer.id = '';
                            "
                            placeholder="Walk-in Guest or Search..."
                            class="focus:ring-brand-500 focus:border-brand-500 w-full rounded-lg border border-gray-100 bg-gray-50 py-2 pr-8 pl-3 text-xs font-bold text-gray-700 transition-all focus:ring-1 focus:outline-none" />
                        <i data-lucide="chevron-down"
                            class="pointer-events-none absolute top-1/2 right-2.5 h-4 w-4 -translate-y-1/2 text-gray-400"></i>

                        {{-- Floating Dropdown List --}}
                        <ul x-show="isClientDropdownOpen" x-cloak x-transition
                            class="custom-scrollbar absolute top-full left-0 z-[60] mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-2xl">
                            {{-- Permanent Walk-in Guest Option --}}
                            <li @click="selectCustomer(null)"
                                class="hover:bg-brand-50 cursor-pointer border-b border-gray-100 px-4 py-3 transition-colors">
                                <div class="text-brand-600 text-[13px] font-bold">Walk-in Guest</div>
                                <div class="mt-0.5 text-[10px] text-gray-400">Proceed without saving customer info
                                </div>
                            </li>

                            <li x-show="filteredClientList.length === 0"
                                class="px-4 py-4 text-center text-xs font-medium text-gray-500">
                                No matching customers.
                            </li>

                            <template x-for="client in filteredClientList" :key="client.id">
                                <li @click="selectCustomer(client)"
                                    class="cursor-pointer border-b border-gray-100 px-4 py-3 transition-colors last:border-0 hover:bg-gray-50">
                                    <div class="text-[13px] font-bold text-gray-800" x-text="client.name"></div>
                                    <div class="mt-0.5 flex flex-wrap items-center gap-2 text-[11px] text-gray-500">
                                        <span x-show="client.phone" x-text="'📞 ' + client.phone"></span>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>

                    {{-- Quick Add Button --}}
                    @if (has_permission('pos.create_quick_client'))
                        <button type="button" @click="isClientModalOpen = true"
                            class="bg-brand-500 hover:bg-brand-600 focus:ring-brand-500 flex h-[36px] w-[36px] shrink-0 items-center justify-center rounded-xl text-white shadow-md transition-all focus:ring-2 focus:outline-none">
                            <i data-lucide="plus" class="h-4 w-4"></i>
                        </button>
                    @endif
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <i data-lucide="user" class="h-3.5 w-3.5"></i>
                    <span>Selected:
                        <span class="text-brand-600 font-bold"
                            x-text="customer.id ? customer.name : 'Guest'"></span></span>
                </div>
            </div>

            {{-- 2 & 3. SCROLLABLE BODY (Cart Items + Payment Math) --}}
            <div class="custom-scrollbar flex-1 overflow-y-auto bg-gray-50/30 pb-6">
                {{-- Cart Items Ledger --}}
                <div class="min-h-[150px] bg-white">
                    {{-- Empty State --}}
                    <div x-show="cart.length === 0" x-cloak
                        class="flex flex-col items-center justify-center p-8 text-gray-400">
                        <i data-lucide="shopping-bag" class="mb-3 h-12 w-12 stroke-1 text-gray-300"></i>
                        <p class="text-sm font-medium text-gray-500">Order is empty</p>
                        <p class="mt-1 text-xs">Scan or add items to get started.</p>
                    </div>

                    {{-- Filled State --}}
                    <ul x-show="cart.length > 0" x-cloak class="divide-y divide-gray-50 border-b border-gray-100">
                        <template x-for="(item, index) in cart" :key="item.product_sku_id">
                            <li class="group p-4 transition-colors hover:bg-gray-50/50">
                                <div class="mb-2 flex items-start justify-between gap-3">
                                    {{-- Product Thumbnail --}}
                                    <div
                                        class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-100">
                                        <img :src="item.image_url || '/assets/images/placeholder.webp'"
                                            class="h-full w-full object-cover" />
                                    </div>

                                    <div class="flex-1 pr-2">
                                        <h3 class="text-[13px] leading-tight font-bold text-gray-800"
                                            x-text="item.display_name || item.product_name"></h3>
                                        <div class="mt-0.5 flex flex-col">
                                            <span class="font-mono text-[10px] text-gray-500"
                                                x-text="
                                                    formatCurrency(item.unit_price) + ' / ' + (item.unit_name || 'unit')
                                                "></span>

                                            {{-- 🌟 Smart Product Tax Display --}}
                                            <span x-show="item.tax_percent > 0"
                                                class="mt-0.5 text-[9px] font-bold tracking-wide"
                                                :class="item.tax_type === 'inclusive' ?
                                                    'text-blue-500' :
                                                    'text-brand-500'"
                                                x-text="
                                                    (item.tax_type === 'inclusive' ? 'Incl. ' : '+ ') +
                                                    item.tax_percent +
                                                    '% Tax (' +
                                                    formatCurrency(item.calculated_tax || 0) +
                                                    ')'
                                                ">
                                            </span>
                                        </div>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <div class="text-[14px] font-black text-gray-800"
                                            x-text="formatCurrency(item.unit_price * item.quantity)"></div>
                                    </div>
                                </div>

                                <div class="mt-3 flex items-center justify-between">
                                    {{-- Qty Controls (Using RAW SVGs so they never disappear) --}}
                                    <div
                                        class="flex h-9 items-center overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                                        <button @click="updateQty(index, -1)"
                                            class="flex h-full w-10 items-center justify-center bg-gray-50 text-gray-600 transition-colors hover:bg-gray-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M5 12h14" />
                                            </svg>
                                        </button>
                                        <input type="number" min="1" step="1"
                                            x-model.number="item.quantity"
                                            @keydown="window.preventInvalidChars($event)"
                                            @input="
                                                item.quantity = window.sanitizeQty(item.quantity);
                                                calculateCart();
                                            "
                                            class="no-scrollbar h-full w-12 border-x border-gray-200 bg-white text-center text-[13px] font-bold outline-none" />
                                        <button @click="updateQty(index, 1)"
                                            class="flex h-full w-10 items-center justify-center bg-gray-50 text-gray-600 transition-colors hover:bg-gray-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M5 12h14" />
                                                <path d="M12 5v14" />
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- Remove Button (Using RAW SVG) --}}
                                    <button @click="updateQty(index, -item.quantity)"
                                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-50 text-red-500 transition-all hover:bg-red-100 hover:text-red-600 active:scale-95">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M3 6h18" />
                                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                                            <line x1="10" y1="11" x2="10" y2="17" />
                                            <line x1="14" y1="11" x2="14" y2="17" />
                                        </svg>
                                    </button>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>

                {{-- Detail Payment & Math --}}
                <div class="border-t border-gray-200 bg-[#fafafa] p-5">
                    <h2 class="mb-3 text-[14px] font-bold tracking-widest text-gray-800 uppercase">Payment Details</h2>

                    {{-- Built inline rather than with x-payment-method-select.
                         That component is a native select shared by invoices,
                         purchases and expenses; turning it into a custom
                         dropdown would change all of them at once, and only POS
                         needs this treatment. --}}
                    <div class="mb-4" x-data="{ open: false }">
                        <label class="mb-2 block text-[12px] font-bold tracking-wider text-gray-600 uppercase">
                            Payment Type <span class="text-red-500">*</span>
                        </label>

                        <div class="relative">
                            <button @click="open = !open" type="button"
                                :class="open ? 'border-brand-500 ring-2 ring-brand-500/15' :
                                    'border-gray-200 hover:border-gray-300'"
                                class="flex h-[42px] w-full items-center gap-2.5 rounded-xl border bg-white px-3 shadow-sm transition-all">
                                <i :data-lucide="paymentIcon(selectedPaymentMethod?.slug)"
                                    class="text-brand-600 h-4 w-4 shrink-0"></i>
                                <span class="flex-1 truncate text-left text-[13px] font-bold text-gray-800"
                                    x-text="selectedPaymentMethod ? (selectedPaymentMethod.label || selectedPaymentMethod.name) : 'Unpaid'"></span>
                                <i data-lucide="chevron-down"
                                    class="h-3.5 w-3.5 shrink-0 text-gray-400 transition-transform"
                                    :class="open && 'rotate-180'"></i>
                            </button>

                            <div x-cloak x-show="open" @click.away="open = false"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="absolute right-0 left-0 z-50 mt-2 overflow-hidden rounded-xl border border-gray-100 bg-white p-1.5 shadow-[0_10px_40px_-8px_rgba(0,0,0,0.18)]">
                                <p
                                    class="px-2.5 pt-1.5 pb-2 text-[9px] font-black tracking-widest text-gray-400 uppercase">
                                    Payment Type
                                </p>

                                {{-- Leaving the sale unpaid is a deliberate choice, so it is
                                     an option in the list rather than an empty first entry. --}}
                                <button type="button" @click="open = false; selectPaymentMethod(null)"
                                    :class="!payment.method_id ? 'bg-brand-50 text-brand-700' : 'text-gray-700 hover:bg-gray-50'"
                                    class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2.5 text-left transition-colors">
                                    <i data-lucide="circle-slash" class="h-4 w-4 shrink-0"
                                        :class="!payment.method_id ? 'text-brand-600' : 'text-gray-400'"></i>
                                    <span class="flex-1 truncate text-[13px]"
                                        :class="!payment.method_id ? 'font-bold' : 'font-medium'">Unpaid</span>
                                    <i data-lucide="check" class="text-brand-600 h-4 w-4 shrink-0"
                                        x-show="!payment.method_id"></i>
                                </button>

                                <template x-for="pm in paymentMethods" :key="pm.id">
                                    <button type="button" @click="open = false; selectPaymentMethod(pm)"
                                        :class="payment.method_id == pm.id ? 'bg-brand-50 text-brand-700' :
                                            'text-gray-700 hover:bg-gray-50'"
                                        class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2.5 text-left transition-colors">
                                        <i :data-lucide="paymentIcon(pm.slug)" class="h-4 w-4 shrink-0"
                                            :class="payment.method_id == pm.id ? 'text-brand-600' : 'text-gray-400'"></i>
                                        <span class="flex-1 truncate text-[13px]"
                                            :class="payment.method_id == pm.id ? 'font-bold' : 'font-medium'"
                                            x-text="pm.label || pm.name"></span>
                                        <i data-lucide="check" class="text-brand-600 h-4 w-4 shrink-0"
                                            x-show="payment.method_id == pm.id"></i>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 text-[13px] font-medium text-gray-500">
                        <label
                            class="focus-within:border-brand-500 focus-within:ring-brand-500 flex cursor-text items-center justify-between rounded-lg border border-gray-200 bg-white p-2 shadow-sm transition-all focus-within:ring-1">
                            <span class="pointer-events-none font-bold whitespace-nowrap text-gray-700">Received
                                (₹)</span>
                            <input type="text" inputmode="numeric" pattern="[0-9]*" placeholder="0"
                                x-model="payment.received"
                                @input="
                                    payment.received = payment.received.toString().replace(/\D/g, '');
                                    calculatePayment();
                                "
                                class="text-brand-600 w-full flex-1 bg-transparent pl-3 text-right text-base font-black focus:outline-none" />
                        </label>

                        <div class="flex items-center justify-between px-1">
                            <span>Change Amount</span>
                            <span class="font-bold text-green-600" x-text="formatCurrency(payment.change)"></span>
                        </div>

                        <div class="flex items-center justify-between px-1">
                            <span>Due Amount</span>
                            <span class="font-bold text-red-500" x-text="formatCurrency(payment.due)"></span>
                        </div>

                        <hr class="my-3 border-gray-200" />

                        <div class="flex items-center justify-between px-1">
                            <span>Sub total</span>
                            <span class="font-bold text-gray-800" x-text="formatCurrency(totals.subtotal)"></span>
                        </div>

                        @if (has_permission('pos.apply_discount'))
                            {{-- A segmented toggle rather than a dropdown: with only
                                 two choices, a menu costs a second click on the
                                 busiest screen in the app. --}}
                            <div class="flex items-center justify-between">
                                <span class="px-1">Discount</span>
                                <div
                                    class="focus-within:border-brand-500 focus-within:ring-brand-500/15 flex h-[34px] items-center overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-all focus-within:ring-2">
                                    <div class="flex h-full shrink-0 bg-gray-50 p-0.5">
                                        <button type="button" @click="setDiscountType('fixed')"
                                            :class="totals.discount_type === 'fixed' ?
                                                'bg-white text-brand-600 shadow-sm' :
                                                'text-gray-400 hover:text-gray-600'"
                                            class="flex w-7 items-center justify-center rounded-lg text-xs font-black transition-all">₹</button>
                                        <button type="button" @click="setDiscountType('percent')"
                                            :class="totals.discount_type === 'percent' ?
                                                'bg-white text-brand-600 shadow-sm' :
                                                'text-gray-400 hover:text-gray-600'"
                                            class="flex w-7 items-center justify-center rounded-lg text-xs font-black transition-all">%</button>
                                    </div>
                                    <input type="text" inputmode="decimal" placeholder="0"
                                        x-model="totals.discount_value"
                                        @input="
                                            let value = $event.target.value
                                                .replace(/[^0-9.]/g, '')
                                                .replace(/(\..*?)\..*/g, '$1');

                                            totals.discount_value = clampDiscount(parseFloat(value) || 0);
                                            $event.target.value = totals.discount_value;

                                            calculateCart();
                                        "
                                        class="h-full w-16 border-l border-gray-200 px-2 text-right text-xs font-bold text-red-500 placeholder-gray-300 focus:outline-none" />
                                </div>
                            </div>
                        @endif

                        {{-- GST is read from each SKU and is not editable here.
                             This row used to hold an order-level tax percentage
                             the cashier could type in. It was added to the
                             amount collected but never sent to the backend —
                             the invoice has no field for it — so the customer
                             paid it and no invoice ever recorded it. --}}
                        <div class="flex items-start justify-between px-1">
                            <div class="flex flex-col pt-1">
                                <span>GST</span>
                            </div>
                            <span class="pt-1 text-[13px] font-bold text-gray-700"
                                x-text="formatCurrency(totals.tax)"></span>
                        </div>

                        <div class="flex items-start justify-between px-1" x-show="totals.round_off != 0">
                            <span class="pt-1">Round Off</span>
                            <span class="pt-1 text-[13px] font-bold text-gray-700"
                                x-text="(totals.round_off < 0 ? '− ' : '+ ') + formatCurrency(Math.abs(totals.round_off))"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. STICKY CHECKOUT FOOTER --}}
            <div class="z-20 shrink-0 border-t border-gray-100 bg-white p-4 shadow-[0_-5px_15px_rgba(0,0,0,0.03)]">
                <div class="mb-3 flex items-end justify-between px-1">
                    <div>
                        <span class="text-[14px] font-bold text-gray-800">Payable Amount</span>
                        <div class="mt-0.5 text-[10px] text-gray-400">
                            Round off: <span x-text="totals.round_off"></span>
                        </div>
                    </div>
                    <span class="text-brand-600 text-2xl font-black" x-text="formatCurrency(totals.payable)"></span>
                </div>

                @if (has_permission('pos.create_sale'))
                    <button @click="placeOrder()" :disabled="cart.length === 0 || isProcessing"
                        :class="cart.length === 0 ?
                            'bg-[#cbd5e1] cursor-not-allowed' :
                            'bg-brand-500 hover:bg-brand-600 shadow-lg hover:shadow-brand-500/30 active:scale-95'"
                        class="flex w-full items-center justify-center gap-2 rounded-xl py-3.5 text-sm font-bold tracking-wide text-white transition-all">
                        <span x-show="!isProcessing">Place an Order</span>
                        <span x-show="isProcessing" class="flex items-center gap-2">
                            <i data-lucide="loader-2" class="h-4 w-4 animate-spin"></i> Processing...
                        </span>
                    </button>
                @endif
            </div>
        </div>

        {{-- ======================================================= --}}
        {{-- 🟢 QUICK CLIENT MODAL COMPONENT                         --}}
        {{-- ======================================================= --}}
        <x-quick-client-modal :states="$states" />
        {{-- 🟢 CAMERA BARCODE SCANNER MODAL --}}
        <x-barcode-scanner-modal />
        {{-- 🌟 🟢 QUICK PRODUCT MODAL --}}
        <x-quick-product-modal :categories="$categories" :units="$units" />

        {{-- 🌟 🟢 POS HISTORY MODAL --}}
        <div x-show="isHistoryModalOpen" style="display: none"
            class="fixed inset-0 z-[118] flex items-center justify-center bg-gray-900/80 px-4 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <div
                class="flex h-[80vh] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-2xl">
                {{-- Header --}}
                <div class="flex shrink-0 items-center justify-between border-b border-gray-100 bg-white px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-brand-50 flex h-9 w-9 items-center justify-center rounded-full">
                            <i data-lucide="clock" class="text-brand-600 h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 class="text-[15px] leading-tight font-bold text-gray-800">POS History</h3>
                            <p class="text-[11px] font-medium text-gray-500">Recent bills from this terminal</p>
                        </div>
                    </div>
                    <button @click="closeHistoryModal()" title="Close"
                        class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                {{-- Bill List --}}
                <div class="no-scrollbar flex-1 overflow-y-auto px-5 py-3">
                    <template x-if="historyLoading">
                        <div class="flex h-full items-center justify-center text-sm font-medium text-gray-400">
                            Loading...
                        </div>
                    </template>

                    <template x-if="!historyLoading && historyBills.length === 0">
                        <div class="flex h-full flex-col items-center justify-center gap-2 text-center">
                            <i data-lucide="receipt" class="h-8 w-8 text-gray-300"></i>
                            <p class="text-sm font-medium text-gray-400">No POS bills yet.</p>
                        </div>
                    </template>

                    <template x-if="!historyLoading">
                        <template x-for="bill in historyBills" :key="bill.id">
                            <div
                                class="mb-2 flex items-center justify-between gap-3 rounded-xl border border-gray-100 bg-gray-50 p-3">
                                <div class="min-w-0">
                                    <p class="truncate text-[13px] font-bold text-gray-800"
                                        x-text="bill.invoice_number"></p>
                                    <p class="text-[11px] text-gray-500" x-text="bill.date"></p>
                                    <p class="text-[11px] font-medium text-gray-600" x-text="bill.client_name"></p>
                                </div>
                                <button @click="viewHistoryBill(bill)"
                                    class="border-brand-200 bg-brand-50 text-brand-600 hover:bg-brand-100 shrink-0 rounded-lg border px-3 py-1.5 text-[12px] font-bold transition-colors">
                                    View
                                </button>
                            </div>
                        </template>
                    </template>
                </div>

                {{-- Pagination Footer --}}
                <div class="flex shrink-0 items-center justify-between border-t border-gray-100 bg-white px-5 py-3">
                    <button @click="loadHistory(historyPage - 1)" :disabled="historyPage <= 1 || historyLoading"
                        :class="historyPage <= 1 || historyLoading ?
                            'opacity-40 cursor-not-allowed' :
                            'hover:bg-gray-100'"
                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-[12px] font-bold text-gray-600 transition-colors">
                        Prev
                    </button>
                    <span class="text-[12px] font-medium text-gray-500">
                        Page <span x-text="historyPage"></span> of <span x-text="historyLastPage"></span>
                    </span>
                    <button @click="loadHistory(historyPage + 1)"
                        :disabled="historyPage >= historyLastPage || historyLoading"
                        :class="historyPage >= historyLastPage || historyLoading ?
                            'opacity-40 cursor-not-allowed' :
                            'hover:bg-gray-100'"
                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-[12px] font-bold text-gray-600 transition-colors">
                        Next
                    </button>
                </div>
            </div>
        </div>

        {{-- 🌟 🟢 RECEIPT PREVIEW MODAL --}}
        <div x-show="isReceiptModalOpen" style="display: none"
            class="fixed inset-0 z-[120] flex items-center justify-center bg-gray-900/80 px-4 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <div
                class="flex h-[85vh] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-2xl">
                {{-- Header --}}
                <div class="flex shrink-0 items-center justify-between border-b border-gray-100 bg-white px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-brand-50 flex h-9 w-9 items-center justify-center rounded-full">
                            <i data-lucide="check-circle" class="text-brand-600 h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 class="text-[15px] leading-tight font-bold text-gray-800">Payment Successful</h3>
                            <p class="text-[11px] font-medium text-gray-500">Bill generated successfully</p>
                        </div>
                    </div>
                    <button @click="closeReceiptModal()" title="Close"
                        class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                {{-- Iframe Container (Simulates the POS Printer Machine) --}}
                <div class="relative flex flex-1 flex-col items-center overflow-hidden bg-[#e5e7eb] py-6 shadow-inner">
                    {{-- Realistic Printer Slot Visual --}}
                    {{-- <div
                        class="absolute top-0 left-1/2 -translate-x-1/2 w-[86mm] h-3 bg-gradient-to-b from-gray-800 to-gray-600 rounded-b-lg shadow-md z-10 border-b border-gray-900">
                    </div> --}}

                    {{-- The actual receipt paper --}}
                    <div
                        class="relative flex h-full w-[80mm] flex-col overflow-hidden bg-white shadow-[0_10px_25px_rgba(0,0,0,0.15)] transition-all">
                        {{-- Jagged paper tear effect at the bottom (Optional but looks great) --}}
                        <div
                            class="absolute bottom-0 left-0 z-10 h-2 w-full rotate-180 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4IiBoZWlnaHQ9IjgiPgo8cG9seWdvbiBwb2ludHM9IjAsOCA0LDAgOCw4IiBmaWxsPSIjZTVlN2ViIi8+Cjwvc3ZnPg==')]">
                        </div>

                        <iframe :src="currentReceiptUrl" id="receiptFrame"
                            class="no-scrollbar h-full w-full border-none bg-white pb-4 outline-none"></iframe>
                    </div>
                </div>

                {{-- Footer Action Buttons --}}
                <div class="flex shrink-0 flex-wrap gap-3 border-t border-gray-100 bg-white p-5">
                    <button @click="closeReceiptModal()"
                        class="flex-1 rounded-xl border border-gray-200 bg-gray-50 py-2.5 text-sm font-bold text-gray-600 transition-all hover:bg-gray-100 hover:text-gray-900 focus:ring-2 focus:ring-gray-200 active:scale-95">
                        <i data-lucide="arrow-left" class="mr-1 mb-0.5 inline-block h-4 w-4"></i> New Order
                    </button>
                    {{-- 🌟 Share / WhatsApp button --}}
                    <button @click="shareReceiptLink()" title="Share receipt link"
                        class="text-brand-600 bg-brand-50 border-brand-200 hover:bg-brand-100 flex items-center gap-1.5 rounded-xl border px-4 py-2.5 text-sm font-bold transition-all active:scale-95">
                        <i data-lucide="share-2" class="h-4 w-4"></i>
                        <span class="hidden sm:inline">Share</span>
                    </button>
                    <button @click="printReceipt()"
                        class="bg-brand-500 hover:bg-brand-600 shadow-brand-500/30 focus:ring-brand-500 flex flex-1 items-center justify-center gap-2 rounded-xl py-2.5 text-[15px] font-bold text-white shadow-lg transition-all focus:ring-2 active:scale-95">
                        <i data-lucide="printer" class="h-5 w-5"></i> Print Bill
                    </button>
                </div>
            </div>
        </div>

        {{-- Floating Mobile Cart Button --}}
        <div class="fixed bottom-6 left-1/2 z-[45] w-[92%] -translate-x-1/2 sm:w-[400px] lg:hidden">
            <button @click="isMobileCartOpen = true"
                class="bg-brand-500 hover:bg-brand-600 shadow-brand-500/30 flex w-full items-center justify-between rounded-2xl px-5 py-3.5 text-white shadow-xl transition-transform active:scale-95">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <i data-lucide="shopping-bag" class="h-5 w-5"></i>
                        <span x-show="cart.length > 0" x-text="cart.length"
                            class="absolute -top-2 -right-2 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-black text-white">
                        </span>
                    </div>
                    <span class="text-sm font-bold">View Cart</span>
                </div>

                <div class="text-lg font-black" x-text="formatCurrency(totals.payable)"></div>
            </button>
        </div>
    </div>

    <script src="{{ asset('assets/js/sweetalert2.js') }}"></script>
    <script src="{{ asset('assets/js/swal.js') }}"></script>
    <script>
        document.addEventListener("alpine:init", () => {
            lucide.createIcons();
        });
    </script>

    <script>
        document.addEventListener("alpine:init", () => {
            Alpine.data("posEngine", (companyState, clientsList, paymentMethodsList, warehouseList) => ({
                clients: clientsList,
                paymentMethods: paymentMethodsList,
                // Needed by name here, not just as <option> markup, because the
                // switcher is a custom dropdown now.
                warehouses: warehouseList,

                // ─────────────────────────────────────────────────────────
                // 1. STATE MANAGEMENT
                // ─────────────────────────────────────────────────────────

                // Grid & Search State
                products: [],
                categories: [],
                activeCategory: "",
                searchQuery: "",
                // Searchable Dropdown State
                clientSearchTerm: "",
                isClientDropdownOpen: false,
                // 🌟 Filter the list based on what the user types
                get filteredClientList() {
                    if (this.clientSearchTerm.trim() === "") {
                        return this.clients;
                    }
                    const term = this.clientSearchTerm.toLowerCase();
                    return this.clients.filter((client) => {
                        return client.name.toLowerCase().includes(term) || (client.phone &&
                            client.phone.includes(term));
                    });
                },
                // 🌟 Handle clicking an item in the dropdown
                selectCustomer(client) {
                    if (!client) {
                        // Walk-in Guest Selected
                        this.customer.id = "";
                        this.customer.name = "Guest";
                        this.clientSearchTerm = ""; // Clear search box so placeholder shows
                    } else {
                        // Real Customer Selected
                        this.customer.id = client.id;
                        this.customer.name = client.name;
                        this.clientSearchTerm = client.name; // Put name in the search box
                    }
                    this.isClientDropdownOpen = false;
                },
                isLoading: false,

                // Infinite Scroll State
                page: 1,
                hasMorePages: true,

                // Scanner State
                scanBuffer: "",
                lastScanTime: 0,
                warehouse_id: "{{ $warehouses->firstWhere('is_default', true)?->id ?? ($warehouses->first()?->id ?? '') }}",
                previous_warehouse_id: "{{ $warehouses->firstWhere('is_default', true)?->id ?? ($warehouses->first()?->id ?? '') }}",

                // 🌟 CAMERA SCANNER STATE
                isScannerModalOpen: false,
                html5QrcodeScanner: null,

                // Cart & Customer State
                cart: JSON.parse(localStorage.getItem("pos_cart")) || [],
                customer: {
                    id: "",
                    name: "Guest",
                    gstin: "",
                    state: companyState,
                    registration_type: "unregistered",
                },

                // Checkout & Math State
                payment: {
                    method_id: paymentMethodsList.length > 0 ? paymentMethodsList[0].id : "",
                    method_name: paymentMethodsList.length > 0 ? paymentMethodsList[0].name : "",
                    received: "",
                    change: 0,
                    due: 0,
                },
                totals: {
                    gross_subtotal: 0, // sum of item prices before discount
                    taxable_subtotal: 0, // sum of taxable values after discount
                    subtotal: 0, // keep this for UI if needed
                    discount_type: "fixed",
                    discount_value: 0,
                    discount_amount: 0,
                    item_tax_amount: 0,
                    tax: 0,
                    round_off: 0,
                    payable: 0,
                },
                // Lives on the component, not inside totals, because that is
                // where placeOrder() reads and writes it. The copy in totals
                // was never read by anything.
                idempotencyKey: null,
                isProcessing: false,
                isClientModalOpen: false,
                newClient: {
                    name: "",
                    phone: "",
                    city: "",
                    state_id: "",
                    registration_type: "unregistered",
                },
                isProductModalOpen: false,
                isReceiptModalOpen: false,
                currentReceiptUrl: "",
                currentReceiptShareUrl: "",
                isViewingHistoryReceipt: false,
                isHistoryModalOpen: false,
                historyBills: [],
                historyPage: 1,
                historyLastPage: 1,
                historyLoading: false,
                isMobileCartOpen: false,
                newProduct: {
                    name: "",
                    category_id: "",
                    unit_id: "",
                    price: "",
                    cost: "",
                    tax_percent: 0,
                    tax_type: "exclusive",
                    sku: "",
                    barcode: "",
                    opening_stock: 0,
                },

                // ─────────────────────────────────────────────────────────
                // CAMERA SCANNER LOGIC
                // ─────────────────────────────────────────────────────────
                openScanner() {
                    this.isScannerModalOpen = true;
                    setTimeout(() => {
                        if (!this.html5QrcodeScanner) {
                            this.html5QrcodeScanner = new Html5QrcodeScanner(
                                "camera-reader", {
                                    fps: 10,
                                    qrbox: {
                                        width: 250,
                                        height: 150,
                                    },
                                },
                                false,
                            );
                        }
                        this.html5QrcodeScanner.render(
                            (decodedText) => this.onCameraScanSuccess(decodedText),
                            (error) => {
                                /* Ignore standard scan frame errors */
                            },
                        );
                    }, 300);
                },

                closeScanner() {
                    this.isScannerModalOpen = false;
                    if (this.html5QrcodeScanner) {
                        this.html5QrcodeScanner.clear().catch((error) => console.error(
                            "Failed to clear scanner", error));
                    }
                },

                onCameraScanSuccess(decodedText) {
                    // 1. Play beep (Optional)
                    let audio = new Audio("/assets/audio/beep.mp3");
                    audio.play().catch((e) => {});

                    // 2. Close camera
                    this.closeScanner();

                    // 3. Process barcode
                    this.processBarcode(decodedText);
                },

                // ─────────────────────────────────────────────────────────
                // CUSTOMER & WAREHOUSE LOGIC
                // ─────────────────────────────────────────────────────────
                async saveQuickClient() {
                    // Only the name is required. A phone number is welcome but
                    // often not offered at the counter, and clients.phone is
                    // nullable — refusing to save without one meant the sale
                    // went through as a walk-in and the customer was never
                    // recorded at all.
                    if (!this.newClient.name.trim()) {
                        BizAlert.toast("Please enter the customer's name.", "error");
                        return;
                    }

                    // Partial numbers are worse than none: they cannot be dialled
                    // and they occupy the uniqueness check.
                    if (this.newClient.phone && this.newClient.phone.length !== 10) {
                        BizAlert.toast("Enter all 10 digits, or leave the phone number blank.",
                            "error");
                        return;
                    }

                    try {
                        let csrfMeta = document.querySelector('meta[name="csrf-token"]');
                        let response = await fetch("/admin/clients", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                "X-CSRF-TOKEN": csrfMeta.content,
                            },
                            // An empty string is a value as far as validation is
                            // concerned, so it would reach the unique rule and
                            // collide with the next client saved without a phone.
                            body: JSON.stringify({
                                ...this.newClient,
                                phone: this.newClient.phone?.trim() || null,
                                city: this.newClient.city?.trim() || null,
                                state_id: this.newClient.state_id || null,
                            }),
                        });

                        let data = await response.json();

                        if (!response.ok) {
                            // 🌟 Safely checks for validation errors vs general server errors
                            let errorMessage = data.errors ?
                                Object.values(data.errors)[0][0] :
                                data.message || "Something went wrong";

                            BizAlert.toast(errorMessage, "error");
                            return;
                        }

                        BizAlert.toast("Client added successfully!", "success");
                        this.isClientModalOpen = false;

                        // Push to the array so the dropdown updates instantly
                        this.clients.push(data.client);

                        // Auto-select the newly created client
                        this.selectCustomer(data.client);

                        this.newClient = {
                            name: "",
                            phone: "",
                            city: "",
                            state_id: "",
                            registration_type: "unregistered",
                        };
                    } catch (error) {
                        console.error(error);
                        BizAlert.toast("Network error.", "error");
                    }
                },

                // ─────────────────────────────────────────────────────────
                // QUICK ADD PRODUCT LOGIC
                // ─────────────────────────────────────────────────────────
                /**
                 * Digits only, no leading zeros.
                 *
                 * "000000000000" was accepted because nothing normalised it —
                 * it parses to 0, but reads as a stock figure on screen.
                 */
                /**
                 * Digits only — no decimal point, no leading zeros.
                 *
                 * Amounts are entered in whole rupees. The decimal columns still
                 * store 120.00; this only decides what can be typed, and it
                 * removes a class of input the old sanitiser let through, like a
                 * lone "." that parses to nothing.
                 */
                cleanInteger(value, max = null) {
                    let cleaned = String(value ?? "").replace(/[^0-9]/g, "").replace(/^0+(?=\d)/, "");

                    if (max !== null && cleaned !== "") {
                        const num = parseInt(cleaned, 10);
                        if (!isNaN(num) && num > max) cleaned = String(max);
                    }

                    return cleaned;
                },

                /**
                 * Digits and at most one decimal point, optionally capped.
                 * Trailing "." is kept so "12." can be typed on the way to
                 * "12.5" — stripping it would fight the person typing.
                 */
                cleanDecimal(value, max = null) {
                    let cleaned = String(value ?? "")
                        .replace(/[^0-9.]/g, "")
                        .replace(/(\..*?)\..*/g, "$1")
                        .replace(/^0+(?=\d)/, "");

                    if (max !== null && cleaned !== "" && !cleaned.endsWith(".")) {
                        const num = parseFloat(cleaned);
                        if (!isNaN(num) && num > max) cleaned = String(max);
                    }

                    return cleaned;
                },

                /**
                 * HSN codes are 4, 6 or 8 digits. Leading zeros are kept —
                 * they are part of the code, not a formatting artefact.
                 */
                cleanHsn(value) {
                    return String(value ?? "").replace(/[^0-9]/g, "").slice(0, 8);
                },

                async saveQuickProduct() {
                    // Basic Validation
                    if (
                        !this.newProduct.name ||
                        !this.newProduct.category_id ||
                        !this.newProduct.unit_id ||
                        !this.newProduct.price ||
                        !this.newProduct.cost
                    ) {
                        BizAlert.toast("Please fill all required (*) fields.", "error");
                        return;
                    }

                    BizAlert.loading("Saving product...");

                    try {
                        let csrfMeta = document.querySelector('meta[name="csrf-token"]');

                        // We MUST use FormData to handle the image file upload
                        let formData = new FormData();
                        formData.append("name", this.newProduct.name);
                        formData.append("category_id", this.newProduct.category_id);
                        formData.append("unit_id", this.newProduct.unit_id);
                        formData.append("price", this.newProduct.price);
                        formData.append("cost", this.newProduct.cost);
                        formData.append("tax_percent", this.newProduct.tax_percent);
                        formData.append("tax_type", this.newProduct.tax_type);
                        formData.append("sku", this.newProduct.sku);
                        formData.append("barcode", this.newProduct.barcode);
                        formData.append("opening_stock", this.newProduct.opening_stock);

                        // 🌟 Bind the opening stock directly to the active POS warehouse!
                        formData.append("warehouse_id", this.warehouse_id);

                        // Append Image if selected
                        let imageInput = this.$refs.productImageFile;
                        if (imageInput && imageInput.files.length > 0) {
                            formData.append("image", imageInput.files[0]);
                        }

                        let response = await fetch("/admin/pos/quick-product", {
                            method: "POST",
                            headers: {
                                Accept: "application/json",
                                "X-CSRF-TOKEN": csrfMeta.content,
                                // 🚨 DO NOT set 'Content-Type' here! Browser automatically sets it for FormData.
                            },
                            body: formData,
                        });

                        let data = await response.json();

                        if (!response.ok) {
                            // 🌟 Safely checks for validation errors vs general server errors
                            let errorMessage = data.errors ?
                                Object.values(data.errors)[0][0] :
                                data.message || "Something went wrong";

                            BizAlert.toast(errorMessage, "error");
                            return;
                        }

                        BizAlert.toast("Product Created & Added to Cart!", "success");

                        // 1. Close Modal
                        this.isProductModalOpen = false;

                        // 2. Add directly to cart (the backend formats it perfectly for us)
                        this.addToCart(data.data);

                        // 3. Force the visual grid to refresh so it shows up there too
                        this.products = [];
                        this.page = 1;
                        this.fetchProducts(false);

                        // 4. Reset Form
                        this.newProduct = {
                            name: "",
                            category_id: "",
                            unit_id: "",
                            price: "",
                            cost: "",
                            tax_percent: 0,
                            tax_type: "exclusive",
                            sku: "",
                            barcode: "",
                            opening_stock: 0,
                        };
                        if (imageInput) imageInput.value = ""; // Clear file input
                    } catch (error) {
                        console.error(error);
                        BizAlert.toast("Network error while saving product.", "error");
                    }
                },
                async changeWarehouse() {
                    if (this.cart.length > 0) {
                        let result = await Swal.fire({
                            title: "Clear Cart?",
                            text: "Changing the warehouse will clear your current cart. Continue?",
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonColor: "#008a62",
                            cancelButtonColor: "#d33",
                            confirmButtonText: "Yes, change it!",
                        });

                        if (!result.isConfirmed) {
                            this.warehouse_id = this.previous_warehouse_id;
                            return;
                        }
                        this.cart = [];
                        this.calculateCart();
                    }

                    this.previous_warehouse_id = this.warehouse_id;
                    this.products = [];
                    this.page = 1;
                    this.hasMorePages = true;
                    this.fetchProducts(false);
                },

                // ─────────────────────────────────────────────────────────
                // 2. INITIALIZATION & DATA FETCHING
                // ─────────────────────────────────────────────────────────
                init() {
                    this.fetchProducts();
                    if (this.paymentMethods && this.paymentMethods.length > 0) {
                        // label is the column; name is not one, so it is only a
                        // fallback for anything that supplies a differently
                        // shaped object.
                        this.payment.method_id = this.paymentMethods[0].id;
                        this.payment.method_name =
                            this.paymentMethods[0].label || this.paymentMethods[0].name || "Cash";
                    }
                    this.calculateCart();

                    // Ensure icons render immediately after the template loops finish
                    this.$nextTick(() => {
                        if (typeof lucide !== "undefined") lucide.createIcons();
                    });
                },

                async fetchProducts(append = false) {
                    if (this.isLoading || (!this.hasMorePages && append)) return;

                    this.isLoading = true;
                    if (!append) {
                        this.page = 1;
                        // 🌟 DO NOT empty this.products here! Keep the old products on screen.
                    }

                    try {
                        const url =
                            `/admin/api/products?page=${this.page}&search=${encodeURIComponent(this.searchQuery)}&category_id=${this.activeCategory}&warehouse_id=${this.warehouse_id}&per_page=50`;
                        const response = await fetch(url);
                        const res = await response.json();

                        if (res.status === "success") {
                            if (append) {
                                this.products = [...this.products, ...res.data];
                            } else {
                                // 🌟 REPLACE the products array instantly once the new data arrives
                                this.products = res.data;
                            }
                            this.hasMorePages = this.page < res.meta.total_pages;
                        }
                    } catch (error) {
                        console.error("Failed to fetch products:", error);
                        BizAlert.toast("Failed to load products", "error");
                    } finally {
                        this.isLoading = false;
                    }
                },

                loadMore() {
                    if (this.hasMorePages && !this.isLoading) {
                        this.page++;
                        this.fetchProducts(true);
                    }
                },

                setCategory(categoryId) {
                    this.activeCategory = categoryId;
                    this.fetchProducts(false);
                },

                handleSearch() {
                    this.fetchProducts(false);
                },

                // ─────────────────────────────────────────────────────────
                // 3. THE LIGHTNING SCANNER (GLOBAL LISTENER)
                // ─────────────────────────────────────────────────────────
                handleGlobalScan(e) {
                    if (this.isProcessing) return;

                    const currentTime = new Date().getTime();

                    if (currentTime - this.lastScanTime > 50) {
                        this.scanBuffer = "";
                    }

                    if (e.key !== "Enter" && e.key.length === 1) {
                        this.scanBuffer += e.key;
                    }

                    this.lastScanTime = currentTime;

                    if (e.key === "Enter" && this.scanBuffer.length > 3) {
                        e.preventDefault();
                        this.processBarcode(this.scanBuffer);
                        this.scanBuffer = "";
                    }
                },

                async processBarcode(barcode) {
                    try {
                        const url =
                            `/admin/pos/scan?term=${encodeURIComponent(barcode)}&warehouse_id=${this.warehouse_id}`;
                        const response = await fetch(url);
                        const res = await response.json();

                        if (res.status === "exact") {
                            this.addToCart(res.data);
                            BizAlert.toast(`Added ${res.data.product_name}`, "success");
                        } else {
                            BizAlert.toast("Product not found or out of stock.", "error");
                        }
                    } catch (error) {
                        console.error(error);
                    }
                },

                // ─────────────────────────────────────────────────────────
                // 4. CART MANAGEMENT & MATH
                // ─────────────────────────────────────────────────────────
                addToCart(product) {
                    let existingItem = this.cart.find((item) => item.product_sku_id === product
                        .product_sku_id);

                    if (existingItem) {
                        existingItem.quantity++;
                    } else {
                        this.cart.unshift({
                            product_sku_id: product.product_sku_id || product.unique_id,
                            product_id: product.product_id || product.id,
                            product_name: product.name || product.product_name,
                            display_name: product.display_name || product.name || product
                                .product_name,
                            unit_price: parseFloat(product.unit_price || product
                                .display_price || product.price || 0),
                            unit_id: product.unit_id,
                            unit_name: product.unit_name,
                            quantity: 1,
                            tax_percent: parseFloat(product.tax_percent || 0),
                            tax_type: product.tax_type || "exclusive",
                            stock: product.stock || 999,
                            // 🌟 SAVE THE IMAGE URL FOR THE CART UI
                            image_url: product.image_url || "",
                        });
                    }
                    this.calculateCart();
                },

                /**
                 * Keeps the discount inside the bounds of whichever mode is
                 * active: a percentage cannot exceed 100, and a flat amount
                 * cannot exceed the subtotal.
                 */
                clampDiscount(value) {
                    const val = Math.max(0, parseFloat(value) || 0);

                    return this.totals.discount_type === "percent" ?
                        Math.min(100, val) :
                        Math.min(val, this.totals.subtotal || 0);
                },

                /**
                 * Switching mode re-clamps the value. Without this a ₹500 flat
                 * discount stayed 500 when switched to percent and quietly
                 * became a 500% discount.
                 */
                setDiscountType(type) {
                    if (this.totals.discount_type === type) return;

                    this.totals.discount_type = type;
                    this.totals.discount_value = this.clampDiscount(this.totals.discount_value);
                    this.calculateCart();
                },

                updateQty(index, change) {
                    let newQty = this.cart[index].quantity + change;
                    if (newQty <= 0) {
                        this.cart.splice(index, 1);
                    } else {
                        this.cart[index].quantity = newQty;
                    }
                    this.calculateCart();
                },

                calculateCart() {
                    const round2 = (n) => Math.round((Number(n) + Number.EPSILON) * 100) / 100;
                    const num = (v) => parseFloat(v) || 0;

                    // Normalize cart lines first
                    const lines = this.cart.map((item) => ({
                        ...item,
                        _qty: num(item.quantity),
                        _unit: num(item.unit_price),
                        _taxPct: num(item.tax_percent),
                        _type: (item.tax_type || "exclusive").toLowerCase(),
                    }));

                    // Gross subtotal = sum of displayed unit prices × qty
                    const grossSubtotal = lines.reduce((sum, item) => {
                        return round2(sum + round2(item._qty * item._unit));
                    }, 0);

                    // Global discount
                    let discountAmt = 0;
                    const discVal = num(this.totals.discount_value);

                    if (this.totals.discount_type === "percent") {
                        discountAmt = round2(grossSubtotal * (discVal / 100));
                    } else {
                        discountAmt = round2(discVal);
                    }

                    discountAmt = Math.min(discountAmt, grossSubtotal);

                    // Allocate global discount proportionally to each line
                    let allocatedDiscount = 0;
                    let taxableSubtotal = 0;
                    let itemTaxTotal = 0;
                    let grandTotalBeforeOrderTax = 0;

                    lines.forEach((line, index) => {
                        const lineGross = round2(line._qty * line._unit);
                        const ratio = grossSubtotal > 0 ? lineGross / grossSubtotal : 0;

                        const lineDiscount =
                            index === lines.length - 1 ? round2(discountAmt -
                                allocatedDiscount) : round2(discountAmt * ratio);

                        allocatedDiscount = round2(allocatedDiscount + lineDiscount);

                        const grossAfterDiscount = Math.max(0, round2(lineGross -
                            lineDiscount));

                        let taxable = 0;
                        let tax = 0;
                        let finalLineTotal = 0;

                        if (line._type === "inclusive") {
                            // Unit price already includes GST
                            if (line._taxPct > 0) {
                                taxable = round2(grossAfterDiscount / (1 + line._taxPct / 100));
                                tax = round2(grossAfterDiscount - taxable);
                            } else {
                                taxable = grossAfterDiscount;
                                tax = 0;
                            }
                            finalLineTotal = grossAfterDiscount;
                        } else {
                            // Unit price is base price, GST is added on top
                            taxable = grossAfterDiscount;
                            tax = round2(taxable * (line._taxPct / 100));
                            finalLineTotal = round2(taxable + tax);
                        }

                        line.line_gross = lineGross;
                        line.line_discount = lineDiscount;
                        line.line_gross_after_discount = grossAfterDiscount;
                        line.line_taxable = taxable;
                        line.calculated_tax = tax;
                        line.line_total = finalLineTotal;

                        taxableSubtotal = round2(taxableSubtotal + taxable);
                        itemTaxTotal = round2(itemTaxTotal + tax);
                        grandTotalBeforeOrderTax = round2(grandTotalBeforeOrderTax +
                            finalLineTotal);

                        // Keep reactive cart updated
                        this.cart[index] = line;
                    });

                    // An order-level tax percentage used to be added here. It
                    // was never sent to the backend — the invoice DTO has no
                    // field for it and neither does the invoices table — so the
                    // cashier collected it and no invoice ever recorded it. GST
                    // is per item in any case; a flat charge belongs in
                    // other_charges, which the invoice does support.
                    const payableBeforeRound = grandTotalBeforeOrderTax;

                    // Invoices are rounded to the rupee by InvoiceService, so
                    // the same rounding happens here. The old line subtracted
                    // payable from itself and was always zero, leaving POS
                    // showing ₹287.50 against an invoice of ₹288 — and handing
                    // the cashier the wrong change.
                    const roundedPayable = round2(Math.round(payableBeforeRound));

                    this.totals.gross_subtotal = grossSubtotal;
                    this.totals.taxable_subtotal = taxableSubtotal;
                    this.totals.subtotal =
                        taxableSubtotal; // keep UI-friendly subtotal as taxable value
                    this.totals.discount_amount = discountAmt;
                    this.totals.item_tax_amount = itemTaxTotal;
                    this.totals.tax = itemTaxTotal;
                    this.totals.payable = roundedPayable;
                    this.totals.round_off = round2(roundedPayable - payableBeforeRound);

                    this.calculatePayment();

                    localStorage.setItem("pos_cart", JSON.stringify(this.cart));
                },

                // ─────────────────────────────────────────────────────────
                // 5. CHECKOUT LOGIC
                // ─────────────────────────────────────────────────────────
                get selectedPaymentMethod() {
                    return this.paymentMethods.find((pm) => pm.id == this.payment.method_id) ||
                        null;
                },

                paymentIcon(slug) {
                    const map = {
                        cash: "banknote",
                        upi: "qr-code",
                        card: "credit-card",
                        credit_card: "credit-card",
                        bank_transfer: "landmark",
                        cheque: "scroll-text",
                    };
                    return map[slug] || "wallet";
                },

                /**
                 * Takes the method itself rather than a change event — the
                 * previous version read e.target.options, which only exists on
                 * a native select.
                 */
                selectPaymentMethod(method) {
                    this.payment.method_id = method ? method.id : "";
                    this.payment.method_name = method ? (method.label || method.name || "") : "";

                    const slug = method ? (method.slug || "") : "";
                    const isCash = slug === "cash" || this.payment.method_name.toLowerCase() === "cash";

                    if (!method) {
                        this.payment.received = 0;
                    } else if (!isCash) {
                        // Digital payments arrive for the exact amount, so the
                        // field is filled in for the cashier.
                        this.payment.received = this.totals.payable;
                    } else {
                        // Cash is counted at the drawer, so it is typed in.
                        this.payment.received = "";
                    }

                    this.calculatePayment();

                    this.$nextTick(() => {
                        if (typeof lucide !== "undefined") lucide.createIcons();
                    });
                },

                calculatePayment() {
                    let payable = parseFloat(this.totals.payable) || 0;

                    // Allow 0 received if no payment method is selected
                    if (!this.payment.method_id) {
                        this.payment.received = 0;
                    }

                    let received = parseFloat(this.payment.received) || 0;

                    if (payable === 0) {
                        this.payment.change = received;
                        this.payment.due = 0;
                        return;
                    }

                    let diff = received - payable;

                    if (diff >= 0) {
                        this.payment.change = diff;
                        this.payment.due = 0;
                    } else {
                        this.payment.change = 0;
                        this.payment.due = Math.abs(diff);
                    }
                },
                // ─────────────────────────────────────────────────────────
                // RECEIPT & PRINTING LOGIC
                // ─────────────────────────────────────────────────────────
                closeReceiptModal() {
                    this.isReceiptModalOpen = false;
                    this.currentReceiptUrl = "";

                    // Bill was opened from History (just viewing an old receipt) —
                    // don't touch the live cart, there's no active order to clear.
                    if (this.isViewingHistoryReceipt) {
                        this.isViewingHistoryReceipt = false;
                        return;
                    }

                    // NOW we clear the cart and prepare for the next customer
                    this.cart = [];
                    this.payment.received = "";
                    this.customer.id = ""; // Optional: Reset to Walk-in
                    this.customer.name = "Guest";
                    this.clientSearchTerm = "";
                    this.totals.discount_value = 0;
                    this.calculateCart();
                },

                // ─────────────────────────────────────────────────────────
                // POS HISTORY LOGIC
                // ─────────────────────────────────────────────────────────
                async openHistoryModal() {
                    this.isHistoryModalOpen = true;
                    await this.loadHistory(1);
                },

                closeHistoryModal() {
                    this.isHistoryModalOpen = false;
                },

                async loadHistory(page = 1) {
                    this.historyLoading = true;
                    try {
                        const response = await fetch(
                            `{{ route('admin.pos.history') }}?page=${page}`, {
                                headers: {
                                    Accept: "application/json"
                                },
                            });
                        if (!response.ok) throw new Error("Failed to load history");

                        const result = await response.json();
                        this.historyBills = result.data;
                        this.historyPage = result.meta.current_page;
                        this.historyLastPage = result.meta.total_pages;
                    } catch (e) {
                        BizAlert.toast("Could not load POS history", "error");
                    } finally {
                        this.historyLoading = false;
                    }
                },

                viewHistoryBill(bill) {
                    this.currentReceiptUrl = `/admin/pos/receipt/${bill.id}`;
                    this.currentReceiptShareUrl = bill.share_url || "";
                    this.isViewingHistoryReceipt = true;
                    this.isHistoryModalOpen = false;
                    this.isReceiptModalOpen = true;
                },

                async printReceipt() {
                    // Check if the app is running inside the Flutter WebView Wrapper
                    if (window.AndroidPrinter && typeof window.AndroidPrinter.printReceipt ===
                        "function") {
                        try {
                            // Extract the invoice ID from the currentReceiptUrl (e.g., /admin/pos/receipt/123)
                            const invoiceId = this.currentReceiptUrl.split("/").pop();

                            // Fetch the structured JSON data specifically designed for the Bluetooth printer
                            const response = await fetch(`/admin/pos/receipt/${invoiceId}/json`, {
                                headers: {
                                    Accept: "application/json"
                                },
                            });

                            if (!response.ok) throw new Error(
                                "Could not load receipt data for native printing");

                            // Retrieve the raw JSON string and push it to the Flutter Native Bridge
                            const jsonString = await response.text();
                            window.AndroidPrinter.printReceipt(jsonString);

                            // Stop execution here so the browser print dialogue does not open
                            return;
                        } catch (err) {
                            console.error(
                                "Native print bridge failed, falling back to browser print:",
                                err);
                        }
                    }

                    // Standard Fallback: Trigger standard browser print for PCs or normal mobile browsers
                    const frame = document.getElementById("receiptFrame");
                    if (frame) {
                        frame.contentWindow.focus();
                        frame.contentWindow.print();
                    }
                },

                async placeOrder() {
                    if (this.cart.length === 0) {
                        BizAlert.toast("Cart is empty", "error");
                        return;
                    }

                    // A pending due is a deliberate choice the cashier already
                    // made on this screen, and the receipt prints the unpaid
                    // amount either way — so a confirmation here only adds a
                    // click to the busiest step of the counter workflow.

                    this.isProcessing = true;
                    BizAlert.loading("Processing Order...");

                    // One key per intended sale. Generated on the first attempt
                    // and kept through failures, so a retry after a timeout
                    // (where the server may have committed) resolves to the same
                    // invoice instead of a second one. Cleared only on success.
                    if (!this.idempotencyKey) {
                        this.idempotencyKey = this.newIdempotencyKey();
                    }

                    const payload = {
                        warehouse_id: this.warehouse_id,
                        customer_id: this.customer.id || null,
                        customer_name: this.customer.id ? null : this.customer.name,
                        payment_method_id: this.payment.method_id,
                        amount_received: this.payment.received,
                        idempotency_key: this.idempotencyKey,
                        // 🌟 Map only the strict data the backend needs, dropping frontend UI keys
                        items: this.cart.map((item) => ({
                            product_sku_id: item.product_sku_id,
                            unit_id: item.unit_id,
                            quantity: item.quantity,
                            unit_price: item.unit_price,
                            tax_percent: item.tax_percent,
                            tax_type: item.tax_type,
                        })),
                        // Tax is not sent: the server derives it from each
                        // SKU's own rate. An order-level tax used to be posted
                        // here and quietly dropped, because neither the invoice
                        // DTO nor the invoices table has anywhere to put it —
                        // the cashier collected it and no invoice recorded it.
                        discount_type: this.totals.discount_type,
                        discount_value: this.totals.discount_value,
                        discount_amount: this.totals.discount_amount,
                    };

                    try {
                        let response = await fetch("/admin/pos/store", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                "X-CSRF-TOKEN": document.querySelector(
                                    'meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify(payload),
                        });

                        let data = await response.json();

                        if (!response.ok) throw new Error(data.message || "Checkout Failed");

                        BizAlert.toast("Order Placed Successfully!", "success");

                        this.currentReceiptUrl = `/admin/pos/receipt/${data.invoice_id}`;
                        this.currentReceiptShareUrl = data.share_url || "";
                        this.isReceiptModalOpen = true;
                        this.isMobileCartOpen = false; // Close mobile drawer behind the receipt

                        // Sale is done — the next one is a genuinely new
                        // transaction and must not dedup against this invoice.
                        this.idempotencyKey = null;

                        this.cart = [];
                        this.payment.received = "";
                        this.totals.discount_value = 0;
                        this.calculateCart();
                    } catch (error) {
                        console.error(error);
                        BizAlert.toast(error.message, "error");
                    } finally {
                        this.isProcessing = false;
                    }
                },

                // crypto.randomUUID needs a secure context. The fallback keeps
                // the POS working on plain http (local counter machines) — it
                // only has to be unique per company, not cryptographically
                // strong, since the server validates the format either way.
                newIdempotencyKey() {
                    if (window.crypto && typeof window.crypto.randomUUID === "function") {
                        return window.crypto.randomUUID();
                    }

                    return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g, (c) => {
                        const r = (Math.random() * 16) | 0;
                        const v = c === "x" ? r : (r & 0x3) | 0x8;
                        return v.toString(16);
                    });
                },

                async shareReceiptLink() {
                    if (!this.currentReceiptShareUrl) {
                        BizAlert.toast("Share link not available yet.", "error");
                        return;
                    }
                    try {
                        // Native share sheet on mobile (WhatsApp, etc.)
                        if (navigator.share) {
                            await navigator.share({
                                title: "Your Receipt",
                                text: "Here is your receipt:",
                                url: this.currentReceiptShareUrl,
                            });
                        } else {
                            // Fallback: copy to clipboard on desktop
                            await navigator.clipboard.writeText(this.currentReceiptShareUrl);
                            BizAlert.toast("Receipt link copied to clipboard!", "success");
                        }
                    } catch (e) {
                        if (e.name !== "AbortError") {
                            BizAlert.toast("Could not share. Try copying manually.", "error");
                        }
                    }
                },

                formatCurrency(val) {
                    return (
                        "₹" +
                        parseFloat(val).toLocaleString("en-IN", {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                        })
                    );
                },
            }));
        });
    </script>
</body>

</html>
