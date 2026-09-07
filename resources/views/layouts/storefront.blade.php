<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="company-slug" content="{{ request()->route('slug') ?? '' }}" />
    <meta name="storefront-base" content="{{ rtrim(tenant_url(''), '/') }}" />

    @php
        try {
            $user = auth()->user();
            $client = null; // <-- Initialize by default to prevent undefined variable error

            $primary = get_setting('primary_color') ?: '#008a62';
            $hover = get_setting('primary_hover_color') ?: '#007050';

            $routeSlug = request()->route('slug') ?? 'store';
            $company = $company ?? new \App\Models\Company(['name' => 'StoreFront', 'slug' => $routeSlug]);
            $companySlug = $company?->slug ?: $routeSlug;

            $autoFill = [
                'name' => '',
                'phone' => '',
                'email' => '',
                'delivery_address' => '',
                'notes' => '',
            ];

            if ($user && $company?->id) {
                $client = \App\Models\Client::where('company_id', $company->id)->where('user_id', $user->id)->first();

                $autoFill = [
                    'name' => $client?->name ?? ($user->name ?? ''),
                    'phone' => $client?->phone ?? ($user->phone ?? ''),
                    'email' => $user->email ?? '',
                    'delivery_address' => $client?->address ?? '',
                    'notes' => '',
                ];

                if ($client?->city || $client?->zip_code) {
                    $append = implode(', ', array_filter([$client?->city, $client?->zip_code]));
                    if ($append) {
                        $autoFill['delivery_address'] .= ($autoFill['delivery_address'] ? ', ' : '') . $append;
                    }
                }
            }

            $accountUrl = tenant_url('login');

            if ($user) {
                if ($client) {
                    // Customer: has a linked Client record — go to storefront portal
                    $accountUrl = tenant_url('portal/dashboard');
                } elseif ($user->isSuperAdmin()) {
                    // Platform super admin
                    $accountUrl = route('platform.dashboard');
                } elseif ($user->company_id) {
                    // Any internal user — owner, manager or team member
                    $accountUrl = route('admin.dashboard');
                }
                // else: no company_id (orphaned user) → stays on storefront login URL
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Storefront Layout Error: ' . $e->getMessage());

            $client = null; // <-- Fallback initialization
            $primary = '#008a62';
            $hover = '#007050';
            $companySlug = request()->route('slug') ?? 'store';
            $company = $company ?? new \App\Models\Company(['name' => 'StoreFront', 'slug' => $companySlug]);
            $autoFill = ['name' => '', 'phone' => '', 'email' => '', 'address' => '', 'notes' => ''];
            $accountUrl = tenant_url('login');
        }
    @endphp

    {{-- Public-facing values come from the primary store, not company settings.
         The stores table is the single source of truth for anything a visitor
         sees; get_setting() reads a separate table that the admin SEO form no
         longer writes to. --}}
    <title>@yield ('title', storefront_setting('seo_title', 'Plantiq Storefront'))</title>
    @yield ('meta')

    <link rel="icon" type="image/png"
        href="{{ get_setting('favicon') ? asset('storage/' . get_setting('favicon')) : asset('assets/icons/favicon.png') }}" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset_v('assets/css/tailwind.min.css') }}" />

    <script>
        // Set before any deferred script runs — cart.js keys localStorage on this.
        window.__COMPANY_SLUG__ =
            document.querySelector('meta[name="company-slug"]')?.content || "store";
    </script>

    <script src="{{ asset_v('assets/js/lucide.min.js') }}"></script>
    <script defer src="{{ asset_v('assets/js/sweetalert2.js') }}"></script>

    {{-- cart.js must be evaluated before Alpine starts: the body x-data calls
         window.fetchCartTotals() inside initCart(). Deferred scripts run in
         document order, so loading it here guarantees that ordering instead of
         relying on cart.js sitting at the end of <body>. --}}
    <script defer src="{{ asset_v('assets/js/cart.js') }}"></script>

    {{-- Alpine is loaded exactly once. A second copy resets window.Alpine and
         restarts it without the plugins registered against the first instance,
         which silently breaks x-intersect and double-initialises every x-data. --}}
    <script defer src="{{ asset_v('assets/js/alpinejs.min.js') }}"></script>
    <script defer src="{{ asset_v('assets/js/intersect-alpine.min.js') }}"></script>

    <style>
        :root {
            --brand-500: {{ $primary }};
            --brand-600: {{ $hover }};
            --font-sans: "Poppins", sans-serif;
            --color-brand-50: color-mix(in srgb, var(--brand-500) 10%, white);
            --color-brand-100: color-mix(in srgb, var(--brand-500) 20%, white);
            --color-brand-500: var(--brand-500);
            --color-brand-600: var(--brand-600);
            --color-brand-700: var(--brand-700);
        }

        body {
            background-color: #ffffff;
            color: #1f2937;
            font-family: var(--font-sans);
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

        .co-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 5px;
        }

        .co-input {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 9px 13px;
            font-size: 13px;
            color: #1f2937;
            outline: none;
            font-family: inherit;
            background: #fff;
            transition:
                border-color 150ms ease,
                box-shadow 150ms ease;
        }

        .co-input:focus {
            border-color: var(--brand-600);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-600) 10%, transparent);
        }

        .co-input.error {
            border-color: #f43f5e;
        }
    </style>

    @stack ('styles')
</head>

<body class="flex min-h-screen flex-col font-sans antialiased" x-data="{
    cartOpen: false,
    qrScannerOpen: false,
    cartView: 'cart',
    cartItems: [],
    cartCount: 0,
    cartSubtotal: '0.00',
    cartTotal: '0.00',
    cartTax: null,
    cartRoundOff: 0,
    totalsPending: false,

    // 🌟 Inject the safe Laravel variable directly into Alpine
    form: @js($autoFill),
    formErrors: {},
    isSubmitting: false,
    orderResult: {},

    mobileMenuOpen: false,

    initCart() {
        window.__alpineCart = this;
        this.syncFromStorage();

        // Re-run Lucide whenever cartView changes — covers all x-if template swaps
        this.$watch('cartView', () => {
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
                console.log('[Lucide] Icons re-initialized for view:', this.cartView);
            });
        });

        // Re-run on cartOpen too — covers the shopping-bag icon in header
        this.$watch('cartOpen', (val) => {
            if (val) {
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            }
        });

        console.log('[Cart] Alpine ready');
    },

    syncFromStorage() {
        this.cartItems = window.getCart ? window.getCart() : [];
        const totals = window.getCartTotals ? window.getCartTotals() : { count: 0, subtotal: '0.00', total: '0.00' };
        this.cartCount = totals.count;
        this.cartSubtotal = totals.subtotal;
        this.cartTotal = totals.total;

        this.refreshTotals();
    },

    // The tax figure comes from the server, so it arrives a moment after the
    // subtotal. Until it does the total stays hidden rather than showing a
    // number that is about to change under the customer.
    async refreshTotals() {
        if (this.cartItems.length === 0) {
            this.cartTax = null;
            this.cartRoundOff = 0;
            return;
        }

        // Guarded like syncFromStorage() above: if cart.js has not evaluated yet
        // the subtotal already on screen stays, instead of an unhandled rejection
        // leaving totalsPending stuck true and the total hidden forever.
        if (typeof window.fetchCartTotals !== 'function') {
            console.warn('[Cart] fetchCartTotals unavailable — showing subtotal only');
            this.cartTax = null;
            this.cartRoundOff = 0;
            return;
        }

        this.totalsPending = true;
        const totals = await window.fetchCartTotals(this.form.state || null);
        this.totalsPending = false;

        if (!totals) return;

        this.cartSubtotal = Number(totals.subtotal).toFixed(2);
        this.cartTax = Number(totals.tax_amount);
        this.cartRoundOff = Number(totals.round_off);
        this.cartTotal = Number(totals.total_amount).toFixed(2);
    },

    removeItem(skuId) {
        window.removeFromCart(skuId);
        this.syncFromStorage();
    },
    changeQty(skuId, qty) {
        window.updateCartQty(skuId, qty);
        this.syncFromStorage();
    },

    openCart() {
        this.cartView = 'cart';
        this.cartOpen = true;
    },

    goToCheckout() {
        this.formErrors = {};
        this.cartView = 'checkout';
        // The delivery state decides whether GST splits into CGST + SGST or
        // becomes IGST, so the figures are refreshed once it is known.
        this.$watch('form.state', () => this.refreshTotals());
        this.$nextTick(() => {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    },

    validateForm() {
        const e = {};
        if (!this.form.name.trim()) e.name = 'Name is required';
        if (!this.form.phone.trim()) e.phone = 'Phone number is required';
        if (this.form.phone.trim() && !/^[6-9]\d{9}$/.test(this.form.phone.trim()))
            e.phone = 'Enter a valid 10-digit mobile number';
        if (!this.form.delivery_address.trim()) e.delivery_address = 'Delivery address is required'; // Changed from address
        this.formErrors = e;
        return Object.keys(e).length === 0;
    },

    async submitOrder() {
        if (!this.validateForm()) return;
        this.isSubmitting = true;
        this.formErrors = {};

        // 🌟 FINAL FIX: Hum yaha payload me dono keys ('address' aur 'delivery_address') 
        // bhej rahe hain taaki cart.js ya Laravel kahin par bhi validation fail na ho.
        const payload = {
            ...this.form,
            address: this.form.delivery_address,
            delivery_address: this.form.delivery_address
        };

        const result = await window.placeOrder(payload);
        this.isSubmitting = false;

        if (result.success) {
            this.orderResult = result;
            this.cartView = 'success';
            this.syncFromStorage();
            // Re-init icons after Alpine renders the success view
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        } else {
            this.formErrors.server = result.message;
        }
    },

    continueShopping() {
        this.cartOpen = false;
        this.cartView = 'cart';
        this.orderResult = {};
        this.form = { name: '', phone: '', email: '', delivery_address: '', city: '', state: '', pincode: '', notes: '' };
    },
    showToast(productName) {
        // Simple toast — no library needed
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 z-[9999] flex items-center gap-2.5 bg-gray-900 text-white text-sm font-semibold px-4 py-3 rounded-2xl shadow-xl transition-all duration-300 opacity-0 translate-y-2';
        toast.innerHTML = `
            <svg xmlns='http://www.w3.org/2000/svg' class='w-4 h-4 text-green-400 flex-shrink-0' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2.5'>
                <path stroke-linecap='round' stroke-linejoin='round' d='M5 13l4 4L19 7'/>
            </svg>
            <span>${productName} added to cart</span>
        `;
        document.body.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateX(-50%) translateY(0)';
            });
        });

        // Animate out and remove
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(-50%) translateY(8px)';
            setTimeout(() => toast.remove(), 300);
        }, 2500);
    },
}" x-init="initCart()">
    {{-- ════════ HEADER ════════ --}}
    <header class="sticky top-0 z-40 border-b border-gray-100 bg-white shadow-sm">
        <div
            class="mx-auto flex h-[72px] max-w-[1400px] items-center justify-between gap-4 px-4 sm:gap-8 sm:px-6 lg:px-8">
            {{-- Logo directly on the left --}}
            <a href="{{ tenant_url('') }}" class="flex shrink-0 items-center gap-2.5">
                @if (storefront_store()?->logo_url)
                    <img src="{{ storefront_store()->logo_url }}" alt="Store Logo" class="h-10 object-contain"
                        onerror="this.onerror=null; this.src='{{ asset('assets/images/logo.webp') }}'" />
                @else
                    <div class="flex h-8 w-8 items-center justify-center rounded text-white shadow-sm sm:h-9 sm:w-9"
                        style="background: var(--brand-600)">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                            <path d="M3 9l1-5h16l1 5" />
                            <path d="M5 9v11h14V9" />
                            <path d="M9 20V12h6v8" />
                        </svg>
                    </div>
                    <span class="text-[18px] font-bold tracking-tight text-gray-800 sm:text-[22px]">
                        {{ $company->name ?? 'StoreFront' }}
                    </span>
                @endif
            </a>

            <div class="relative hidden max-w-3xl flex-1 md:block" x-data="searchDropdown()">
                <i data-lucide="search"
                    class="pointer-events-none absolute top-1/2 left-4 z-10 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                <input type="text" x-model="query" @input.debounce.300ms="suggest()" @keydown.enter="goToSearch()"
                    @focus="open = results.length > 0" @click.away="open = false" placeholder="Search for products..."
                    class="w-full rounded-full bg-[#f3f4f6] py-2.5 pr-10 pl-11 text-sm text-gray-700 transition-shadow focus:ring-2 focus:ring-gray-200 focus:outline-none" />

                {{-- Clear button ── --}}
                <button x-show="query.length > 0"
                    @click="
                        query = '';
                        results = [];
                        open = false;
                    "
                    class="absolute top-1/2 right-4 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="h-3.5 w-3.5"></i>
                </button>

                {{-- Dropdown ── --}}
                <div x-show="open" x-cloak
                    class="absolute top-full right-0 left-0 z-50 mt-2 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-xl">
                    {{-- Loading ── --}}
                    <template x-if="loading">
                        <div class="flex items-center gap-2 px-4 py-3 text-sm text-gray-400">
                            <i data-lucide="loader-2" class="h-4 w-4 animate-spin"></i>
                            Searching...
                        </div>
                    </template>

                    {{-- Results ── --}}
                    <template x-if="!loading && results.length > 0">
                        <div>
                            <div class="border-b border-gray-50 px-4 py-2">
                                <p class="text-[11px] font-bold tracking-wider text-gray-400 uppercase"
                                    x-text="results.length + ' results for &quot;' + query + '&quot;'"></p>
                            </div>
                            <template x-for="product in results" :key="product.slug">
                                <a :href="storefrontBase + '/p/' + product.slug"
                                    class="flex items-center gap-3 px-4 py-2.5 transition-colors hover:bg-gray-50">
                                    <img :src="product.image"
                                        class="h-10 w-10 flex-shrink-0 rounded-lg border border-gray-100 bg-gray-100 object-cover"
                                        onerror="this.onerror=null; this.src='{{ asset('assets/defaults/product.svg') }}';" />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-[13px] font-semibold text-gray-800"
                                            x-text="product.name"></p>
                                        <p class="text-[12px] font-medium text-gray-400">
                                            <template x-if="product.product_type !== 'catalog'">
                                                <span>₹<span
                                                        x-text="parseFloat(product.price).toFixed(2)"></span></span>
                                            </template>
                                            <template x-if="product.product_type === 'catalog'">
                                                <span class="text-brand-600 font-semibold">View Details</span>
                                            </template>
                                        </p>
                                    </div>
                                    <i data-lucide="arrow-right" class="h-3.5 w-3.5 flex-shrink-0 text-gray-300"></i>
                                </a>
                            </template>

                            {{-- View all results ── --}}
                            <div class="border-t border-gray-50 px-4 py-2.5">
                                <button @click="goToSearch()"
                                    class="w-full rounded-lg py-1.5 text-center text-[12px] font-bold transition-colors"
                                    style="color: var(--brand-600)">
                                    View all results for "<span x-text="query"></span>"
                                    <i data-lucide="arrow-right" class="ml-1 inline h-3 w-3"></i>
                                </button>
                            </div>
                        </div>
                    </template>

                    {{-- No results ── --}}
                    <template x-if="!loading && results.length === 0 && query.length >= 2">
                        <div class="px-4 py-4 text-center text-sm text-gray-400">
                            <i data-lucide="search-x" class="mx-auto mb-1.5 h-6 w-6 opacity-40"></i>
                            <p class="font-semibold">No products found for "<span x-text="query"></span>"</p>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-4 text-gray-600">
                {{-- 1. QR Scanner Button --}}
                <button
                    @click="
                        qrScannerOpen = true;
                        startScanner();
                    "
                    class="hover:text-brand-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-[22px] w-[22px]" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <!-- Corner Scanner Frame -->
                        <path d="M4 8V4h4" />
                        <path d="M20 8V4h-4" />
                        <path d="M4 16v4h4" />
                        <path d="M20 16v4h-4" />

                        <!-- QR Blocks -->
                        <rect x="7" y="7" width="3" height="3" fill="currentColor" stroke="none" />
                        <rect x="14" y="7" width="3" height="3" fill="currentColor" stroke="none" />
                        <rect x="7" y="14" width="3" height="3" fill="currentColor" stroke="none" />
                        <rect x="14" y="14" width="3" height="3" fill="currentColor" stroke="none" />
                    </svg>
                </button>
                <a href="{{ $accountUrl }}" class="transition-colors hover:text-gray-900 sm:block">
                    <i data-lucide="user" class="h-[22px] w-[22px]"></i>
                </a>
                <button @click="openCart()" class="relative transition-colors hover:text-gray-900">
                    <i data-lucide="shopping-bag" class="h-[22px] w-[22px]"></i>
                    <span
                        class="absolute -top-1.5 -right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white"
                        x-show="cartCount > 0" x-text="cartCount > 9 ? '9+' : cartCount"></span>
                </button>
            </div>
        </div>
    </header>

    {{-- Mobile search ── --}}
    <div class="relative z-30 border-b border-gray-100 bg-white px-4 py-3 shadow-sm md:hidden"
        x-data="searchDropdown()">
        <div class="relative">
            <i data-lucide="search"
                class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
            <input type="text" x-model="query" @input.debounce.300ms="suggest()" @keydown.enter="goToSearch()"
                @click.away="open = false" placeholder="Search for products..."
                class="w-full rounded-full bg-[#f3f4f6] py-2 pr-4 pl-10 text-sm focus:outline-none" />
        </div>

        {{-- Mobile dropdown ── --}}
        <div x-show="open" x-cloak
            class="absolute top-full right-4 left-4 z-50 mt-1 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-xl">
            <template x-if="!loading && results.length > 0">
                <div>
                    <template x-for="product in results" :key="product.slug">
                        <a :href="storefrontBase + '/p/' + product.slug"
                            class="flex items-center gap-3 px-4 py-2.5 transition-colors hover:bg-gray-50">
                            <img :src="product.image"
                                class="h-9 w-9 flex-shrink-0 rounded-lg bg-gray-100 object-cover"
                                onerror="this.onerror=null; this.src='{{ asset('assets/defaults/product.svg') }}';" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[13px] font-semibold text-gray-800" x-text="product.name"></p>
                                <template x-if="product.product_type !== 'catalog'">
                                    <p class="text-[12px] text-gray-400">₹<span
                                            x-text="parseFloat(
                                                product.price,
                                            ).toFixed(2)"></span>
                                    </p>
                                </template>
                                <template x-if="product.product_type === 'catalog'">
                                    <p class="text-brand-600 text-[12px] font-semibold">View Details</p>
                                </template>
                            </div>
                        </a>
                    </template>
                    <div class="border-t border-gray-50 px-4 py-2">
                        <button @click="goToSearch()" class="w-full py-1 text-center text-[12px] font-bold"
                            style="color: var(--brand-600)">
                            View all results →
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ════════════════════════════════════════
         CART DRAWER
    ════════════════════════════════════════ --}}
    <div x-cloak x-show="cartOpen" class="relative z-[70]" role="dialog" aria-modal="true">
        {{-- Backdrop ── --}}
        <div x-show="cartOpen" x-transition:enter="ease-in-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in-out duration-300"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-500/50 transition-opacity" @click="cartOpen = false"></div>

        <div class="fixed inset-0 overflow-hidden">
            <div class="absolute inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <div x-show="cartOpen" x-transition:enter="transform transition ease-in-out duration-300"
                        x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                        x-transition:leave="transform transition ease-in-out duration-300"
                        x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                        class="pointer-events-auto w-screen max-w-[420px]">
                        <div @click.away="cartOpen = false" class="flex h-full flex-col bg-white shadow-xl">
                            {{-- ── Drawer header ── --}}
                            <div
                                class="flex flex-shrink-0 items-center justify-between border-b border-gray-100 px-5 py-4">
                                <template x-if="cartView === 'cart'">
                                    <h2 class="flex items-center text-base font-bold text-gray-900">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5 text-gray-400"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M5 8h14l-1 12H6L5 8z" />
                                            <path d="M9 10V7a3 3 0 0 1 6 0v3" />
                                        </svg>
                                        My Cart
                                        <span class="ml-1.5 text-sm font-medium text-gray-400"
                                            x-text="'(' + cartCount + ')'"></span>
                                    </h2>
                                </template>
                                <template x-if="cartView === 'checkout'">
                                    <div class="flex items-center gap-2">
                                        <button @click="cartView = 'cart'"
                                            class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-gray-100">
                                            <i data-lucide="arrow-left" class="h-4 w-4"></i>
                                        </button>
                                        <h2 class="flex items-center gap-2 text-base font-bold text-gray-900">
                                            <i data-lucide="clipboard-list" class="h-4 w-4 text-gray-400"></i>
                                            Checkout
                                        </h2>
                                    </div>
                                </template>
                                <template x-if="cartView === 'success'">
                                    <h2 class="flex items-center gap-2 text-base font-bold text-gray-900">
                                        <i data-lucide="check-circle" class="h-5 w-5 text-green-500"></i>
                                        Order Placed!
                                    </h2>
                                </template>
                                <button @click="cartOpen = false"
                                    class="rounded-lg p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600">
                                    <i data-lucide="x" class="h-5 w-5"></i>
                                </button>
                            </div>

                            {{-- ════════ VIEW: CART ════════ --}}
                            <template x-if="cartView === 'cart'">
                                <div class="flex min-h-0 flex-1 flex-col">
                                    <div class="no-scrollbar flex-1 overflow-y-auto px-4 py-4 sm:px-5">
                                        <template x-if="cartItems.length === 0">
                                            <div
                                                class="flex h-full flex-col items-center justify-center py-12 text-center">
                                                <div
                                                    class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="h-7 w-7 text-gray-300" viewBox="0 0 24 24"
                                                        fill="none" stroke="currentColor" stroke-width="1.8"
                                                        stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M5 8h14l-1 12H6L5 8z" />
                                                        <path d="M9 10V7a3 3 0 0 1 6 0v3" />
                                                    </svg>
                                                </div>
                                                <p class="mb-1 font-semibold text-gray-500">Your cart is empty</p>
                                                <p class="mb-4 text-sm text-gray-400">Add products to get started</p>
                                                <button @click="cartOpen = false"
                                                    class="rounded-xl px-4 py-2 text-sm font-bold text-white"
                                                    style="background: var(--brand-600)">
                                                    Continue Shopping
                                                </button>
                                            </div>
                                        </template>

                                        <template x-if="cartItems.length > 0">
                                            <div class="space-y-3">
                                                <template x-for="item in cartItems" :key="item.sku_id">
                                                    <div class="flex gap-3 rounded-xl bg-gray-50 p-3">
                                                        <img :src="item.image"
                                                            class="h-16 w-16 flex-shrink-0 rounded-lg border border-gray-100 bg-white object-cover"
                                                            onerror="this.onerror=null; this.src='{{ asset('assets/defaults/product.svg') }}';" />
                                                        <div class="min-w-0 flex-1">
                                                            <p class="mb-0.5 line-clamp-1 text-[13px] font-semibold text-gray-800"
                                                                x-text="item.name"></p>
                                                            <p class="mb-1.5 text-[11px] text-gray-400"
                                                                x-show="item.variant" x-text="item.variant"></p>
                                                            <div class="flex items-center justify-between">
                                                                <div
                                                                    class="flex items-center overflow-hidden rounded-lg border border-gray-200 bg-white">
                                                                    <button
                                                                        @click="changeQty(item.sku_id, item.qty - 1)"
                                                                        class="flex h-7 w-7 items-center justify-center text-sm font-bold text-gray-500 hover:bg-gray-50">
                                                                        −
                                                                    </button>
                                                                    <span class="w-8 text-center text-[13px] font-bold"
                                                                        x-text="item.qty"></span>
                                                                    <button
                                                                        @click="changeQty(item.sku_id, item.qty + 1)"
                                                                        class="flex h-7 w-7 items-center justify-center text-sm font-bold text-gray-500 hover:bg-gray-50">
                                                                        +
                                                                    </button>
                                                                </div>
                                                                <div class="flex items-center gap-2">
                                                                    <span class="text-[13px] font-bold text-gray-900"
                                                                        x-text="
                                                                            '₹' + (item.price * item.qty).toFixed(2)
                                                                        "></span>
                                                                    <button @click="removeItem(item.sku_id)"
                                                                        class="flex h-6 w-6 items-center justify-center text-red-400 hover:text-red-600">
                                                                        <i data-lucide="x" class="h-3.5 w-3.5"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="flex-shrink-0 border-t border-gray-100 bg-gray-50/50 px-5 py-5">
                                        <div class="mb-4 space-y-2">
                                            <div class="flex justify-between text-sm font-medium text-gray-500">
                                                <span>Subtotal</span>
                                                <span class="font-mono font-semibold text-gray-800"
                                                    x-text="'₹ ' + cartSubtotal"></span>
                                            </div>
                                            {{-- GST is shown as its own line so the price the customer
                                                 agrees to is the price they are charged. --}}
                                            <div class="flex justify-between text-sm font-medium text-gray-500"
                                                x-show="cartTax !== null && cartTax > 0">
                                                <span>GST</span>
                                                {{-- x-text is evaluated even while x-show hides the element,
                                                     so this has to survive cartTax being null before the
                                                     server has answered. --}}
                                                <span class="font-mono font-semibold text-gray-800"
                                                    x-text="'₹ ' + (cartTax ?? 0).toFixed(2)"></span>
                                            </div>
                                            <div class="flex justify-between text-sm font-medium text-gray-500"
                                                x-show="cartRoundOff != 0">
                                                <span>Round Off</span>
                                                <span class="font-mono font-semibold text-gray-800"
                                                    x-text="(cartRoundOff < 0 ? '− ₹ ' : '+ ₹ ') + Math.abs(cartRoundOff ?? 0).toFixed(2)"></span>
                                            </div>
                                            <div
                                                class="flex justify-between border-t border-gray-200 pt-2 text-base font-bold text-gray-900">
                                                <span>Total</span>
                                                <span class="font-mono" x-show="!totalsPending"
                                                    x-text="'₹ ' + cartTotal"></span>
                                                <span class="font-mono text-sm font-medium text-gray-400"
                                                    x-show="totalsPending">Calculating…</span>
                                            </div>
                                        </div>
                                        <button @click="goToCheckout()" :disabled="cartItems.length === 0"
                                            class="flex w-full items-center justify-center gap-2 rounded-xl py-3.5 text-[15px] font-bold text-white transition-all"
                                            style="background: var(--brand-600)"
                                            :class="cartItems.length === 0 ?
                                                'opacity-50 cursor-not-allowed' :
                                                'hover:opacity-90'">
                                            Checkout Now <i data-lucide="arrow-right" class="h-4 w-4"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            {{-- ════════ VIEW: CHECKOUT ════════ --}}
                            <template x-if="cartView === 'checkout'">
                                <div class="flex min-h-0 flex-1 flex-col">
                                    <div class="no-scrollbar flex-1 overflow-y-auto px-5 py-4">
                                        {{-- Order summary — itemized (name, qty, price per line) ── --}}
                                        <div class="mb-5 overflow-hidden rounded-xl border border-gray-100 bg-gray-50">
                                            <div
                                                class="flex items-center gap-2 border-b border-gray-100 px-4 py-2 text-[11px] font-black tracking-widest text-gray-400 uppercase">
                                                <i data-lucide="shopping-bag" class="h-3.5 w-3.5"></i>
                                                <span
                                                    x-text="cartCount + (cartCount === 1 ? ' item' : ' items')"></span>
                                            </div>
                                            <template x-for="item in cartItems" :key="item.sku_id">
                                                <div
                                                    class="flex items-center gap-3 border-b border-gray-100 px-4 py-2.5 last:border-b-0">
                                                    <div class="min-w-0 flex-1">
                                                        <p class="line-clamp-1 text-[13px] font-semibold text-gray-800"
                                                            x-text="item.name"></p>
                                                        <p class="text-[11px] text-gray-400" x-show="item.variant"
                                                            x-text="item.variant"></p>
                                                    </div>
                                                    <span class="shrink-0 text-[12px] font-medium text-gray-500"
                                                        x-text="'x' + item.qty"></span>
                                                    <span
                                                        class="shrink-0 font-mono text-[13px] font-bold text-gray-900"
                                                        x-text="'₹' + parseFloat(item.price).toFixed(2)"></span>
                                                </div>
                                            </template>
                                            <div
                                                class="flex items-center justify-between border-t border-gray-100 px-4 pt-2.5 text-[13px] font-medium text-gray-500">
                                                <span>Subtotal</span>
                                                <span class="font-mono" x-text="'₹ ' + cartSubtotal"></span>
                                            </div>
                                            <div class="flex items-center justify-between px-4 py-1 text-[13px] font-medium text-gray-500"
                                                x-show="cartTax !== null && cartTax > 0">
                                                <span>GST</span>
                                                <span class="font-mono"
                                                    x-text="'₹ ' + (cartTax ?? 0).toFixed(2)"></span>
                                            </div>
                                            <div class="flex items-center justify-between px-4 pb-2.5">
                                                <span class="text-sm font-bold text-gray-700">Total</span>
                                                <span class="font-mono text-sm font-bold text-gray-900"
                                                    x-show="!totalsPending" x-text="'₹ ' + cartTotal"></span>
                                                <span class="font-mono text-sm text-gray-400"
                                                    x-show="totalsPending">Calculating…</span>
                                            </div>
                                        </div>

                                        {{-- Server error ── --}}
                                        <template x-if="formErrors.server">
                                            <div
                                                class="mb-4 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                                                <i data-lucide="alert-circle"
                                                    class="mt-0.5 h-4 w-4 flex-shrink-0 text-red-500"></i>
                                                <p class="text-[13px] font-semibold text-red-700"
                                                    x-text="formErrors.server"></p>
                                            </div>
                                        </template>

                                        {{-- Form ── --}}
                                        <div class="space-y-4">
                                            <div>
                                                <label class="co-label">Full Name <span
                                                        class="text-red-500">*</span></label>
                                                <input type="text" x-model="form.name"
                                                    placeholder="Your full name" class="co-input"
                                                    :class="formErrors.name ? 'error' : ''" />
                                                <template x-if="formErrors.name">
                                                    <p class="mt-1 text-[11px] font-semibold text-red-500"
                                                        x-text="formErrors.name"></p>
                                                </template>
                                            </div>

                                            <div>
                                                <label class="co-label">Mobile Number <span
                                                        class="text-red-500">*</span></label>
                                                <input type="tel" x-model="form.phone"
                                                    placeholder="10-digit mobile number" maxlength="10"
                                                    minlength="10" pattern="[0-9]{10}" inputmode="numeric"
                                                    class="co-input" :class="formErrors.phone ? 'error' : ''"
                                                    @input="form.phone = form.phone.replace(/[^0-9]/g, '')" />

                                                <template x-if="formErrors.phone">
                                                    <p class="mt-1 text-[11px] font-semibold text-red-500"
                                                        x-text="formErrors.phone"></p>
                                                </template>
                                            </div>

                                            <div>
                                                <label class="co-label">Email Address
                                                    <span class="font-normal text-gray-400 normal-case">(optional — for
                                                        order confirmation)</span></label>
                                                <input type="email" x-model="form.email"
                                                    placeholder="your@email.com" class="co-input" />
                                            </div>

                                            <div>
                                                <label class="co-label">Delivery Address <span
                                                        class="text-red-500">*</span></label>
                                                <textarea x-model="form.delivery_address" placeholder="House/flat no, street, area, landmark" rows="2"
                                                    class="co-input resize-none" :class="formErrors.delivery_address ? 'error' : ''"></textarea>
                                                <template x-if="formErrors.delivery_address">
                                                    <p class="mt-1 text-[11px] font-semibold text-red-500"
                                                        x-text="formErrors.delivery_address"></p>
                                                </template>
                                            </div>

                                            <div>
                                                <label class="co-label">Notes
                                                    <span
                                                        class="font-normal text-gray-400 normal-case">(optional)</span></label>
                                                <textarea x-model="form.notes" placeholder="Special instructions, preferred delivery time..." rows="2"
                                                    class="co-input resize-none"></textarea>
                                            </div>

                                            <div
                                                class="flex items-center gap-3 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3">
                                                <i data-lucide="banknote"
                                                    class="h-4 w-4 flex-shrink-0 text-blue-500"></i>
                                                <div>
                                                    <p class="text-[12px] font-bold text-blue-800">Cash on Delivery</p>
                                                    <p class="text-[11px] font-medium text-blue-500">Pay when you
                                                        receive your order</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex-shrink-0 border-t border-gray-100 bg-gray-50/50 px-5 py-4">
                                        <button @click="submitOrder()" :disabled="isSubmitting"
                                            class="flex w-full items-center justify-center gap-2 rounded-xl py-3.5 text-[15px] font-bold text-white transition-all"
                                            style="background: var(--brand-600)"
                                            :class="isSubmitting ? 'opacity-70 cursor-not-allowed' : 'hover:opacity-90'">
                                            <template x-if="isSubmitting">
                                                <span class="flex items-center gap-2">
                                                    <i data-lucide="loader-2" class="h-4 w-4 animate-spin"></i>
                                                    Placing Order...
                                                </span>
                                            </template>
                                            <template x-if="!isSubmitting">
                                                <span class="flex items-center gap-2">
                                                    <i data-lucide="check-circle" class="h-4 w-4"></i>
                                                    Place Order
                                                </span>
                                            </template>
                                        </button>
                                        <p class="mt-2 text-center text-[11px] font-medium text-gray-400">We'll call
                                            you to confirm before dispatch</p>
                                    </div>
                                </div>
                            </template>

                            {{-- ════════ VIEW: SUCCESS ════════ --}}
                            <template x-if="cartView === 'success'">
                                <div class="flex min-h-0 flex-1 flex-col">
                                    <div class="no-scrollbar flex-1 overflow-y-auto px-5 py-6">
                                        <div class="mb-6 flex flex-col items-center text-center">
                                            <div
                                                class="mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-green-100">
                                                <i data-lucide="check" class="h-10 w-10 text-green-500"></i>
                                            </div>
                                            <h3 class="mb-1 text-xl font-bold text-gray-900">Order Placed!</h3>
                                            <p class="text-sm font-medium text-gray-500">Thank you! We'll confirm your
                                                order shortly.</p>
                                        </div>

                                        <div class="mb-5 space-y-3 rounded-2xl bg-gray-50 p-4">
                                            <div class="flex items-center justify-between">
                                                <span
                                                    class="text-[11px] font-black tracking-widest text-gray-400 uppercase">Order
                                                    Number</span>
                                                <a :href="`{{ tenant_url('orders') }}/${orderResult.order_number}`">
                                                    <span class="font-mono text-sm font-bold text-gray-900"
                                                        x-text="orderResult.order_number"></span>
                                                </a>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span
                                                    class="text-[11px] font-black tracking-widest text-gray-400 uppercase">Total</span>
                                                <span class="text-sm font-bold text-gray-900"
                                                    x-text="orderResult.total"></span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span
                                                    class="text-[11px] font-black tracking-widest text-gray-400 uppercase">Payment</span>
                                                <span class="text-sm font-bold text-gray-700">Cash on Delivery</span>
                                            </div>
                                        </div>

                                        <div class="mb-5 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3">
                                            <p class="flex items-center gap-2 text-[13px] font-semibold text-blue-800">
                                                <i data-lucide="phone" class="h-4 w-4"></i>
                                                We'll contact
                                                <span x-text="form.phone" class="mx-1 font-mono font-bold"></span> to
                                                confirm
                                            </p>
                                        </div>

                                        <template x-if="orderResult.whatsapp_url">
                                            <a :href="orderResult.whatsapp_url" target="_blank" rel="noopener"
                                                class="mb-3 flex w-full items-center justify-center gap-2 rounded-xl bg-[#25d366] py-3 text-[14px] font-bold text-white transition-colors hover:bg-[#1eb858]">
                                                <!-- Authentic WhatsApp SVG Icon -->
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                    fill="currentColor" class="h-4 w-4">
                                                    <path
                                                        d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.513 2.262 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.457L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.625 1.451 5.403.002 9.803-4.394 9.805-9.799.002-2.618-1.016-5.08-2.868-6.932C16.357 1.99 13.882 1.002 12.01 1.002 6.603 1.002 2.203 5.4 2.2 10.806c-.001 1.562.411 3.09 1.196 4.453l-.993 3.628 3.72-.975v.001-.001zM17.487 14.39c-.3-.15-1.774-.875-2.026-.967-.253-.092-.437-.138-.62.138-.184.276-.713.875-.874 1.059-.16.184-.322.207-.622.057-.302-.15-1.273-.469-2.426-1.496-.897-.8-1.502-1.787-1.679-2.087-.177-.3-.02-.461.13-.611.136-.135.3-.349.45-.524.15-.175.2-.3.3-.5.1-.2.05-.375-.025-.525-.075-.15-.62-1.494-.85-2.046-.223-.538-.447-.465-.62-.474-.16-.008-.344-.01-.528-.01-.184 0-.483.07-.736.346-.253.276-1.011.989-1.011 2.41 0 1.42 1.034 2.795 1.18 2.99.143.19 2.03 3.1 4.92 4.35.687.297 1.224.474 1.643.607.69.219 1.319.19 1.815.115.553-.083 1.774-.725 2.026-1.388.253-.662.253-1.23.177-1.347-.076-.118-.276-.188-.576-.338z" />
                                                </svg>
                                                Notify Owner on WhatsApp
                                            </a>
                                        </template>
                                        <template x-if="orderResult.receipt_url">
                                            <a :href="orderResult.receipt_url" target="_blank" rel="noopener"
                                                class="mb-3 flex w-full items-center justify-center gap-2 rounded-xl bg-gray-100 py-3 text-[14px] font-bold text-gray-700 transition-colors hover:bg-gray-200">
                                                <i data-lucide="file-down" class="h-4 w-4"></i>
                                                Download Receipt
                                            </a>
                                        </template>
                                    </div>

                                    <div class="flex-shrink-0 border-t border-gray-100 px-5 py-4">
                                        <button @click="continueShopping()"
                                            class="flex w-full items-center justify-center gap-2 rounded-xl py-3.5 text-[15px] font-bold text-white transition-all hover:opacity-90"
                                            style="background: var(--brand-600)">
                                            <i data-lucide="store" class="h-4 w-4"></i>
                                            Continue Shopping
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════ MAIN ════════ --}}
    <main class="flex min-w-0 flex-1 flex-col">
        @yield ('content')
    </main>

    {{-- ════════ FOOTER ════════ --}}
    <footer class="mt-auto border-t border-gray-200 bg-[#f8f9fa] pt-16 pb-8">
        <div class="mx-auto max-w-[1400px] px-4 sm:px-6 lg:px-8">
            <div class="mb-12 grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-4">
                <div>
                    <div class="mb-5">
                        <h4 class="text-[16px] font-black tracking-tight text-gray-900">
                            {{ $company->name ?? config('app.name') }}
                        </h4>
                        @if (storefront_setting('tagline'))
                            <p class="mt-1 text-[12px] leading-relaxed font-medium text-gray-500">
                                {{ storefront_setting('tagline') }}</p>
                        @endif
                    </div>
                    <ul class="space-y-4 text-[13px] font-medium text-gray-500">
                        {{-- 🛡️ Fallback dummy data if settings are empty --}}
                        @php
                            $phone = storefront_setting('phone') ?: '+91 98765 43210';
                            $email = storefront_setting('email') ?: 'support@' . ($companySlug ?? 'store') . '.com';
                            $address =
                                storefront_setting('address') ?: '123 Commerce Avenue, Business District, 400001';
                        @endphp

                        <li class="flex items-start gap-3">
                            <i data-lucide="phone" class="mt-0.5 h-4 w-4 shrink-0 text-gray-400"></i>
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">{{ $phone }}</a>
                        </li>
                        <li class="flex items-start gap-3">
                            <i data-lucide="mail" class="mt-0.5 h-4 w-4 shrink-0 text-gray-400"></i>
                            <a href="mailto:{{ $email }}">{{ $email }}</a>
                        </li>
                        <li class="flex items-start gap-3">
                            <i data-lucide="map-pin" class="mt-0.5 h-4 w-4 shrink-0 text-gray-400"></i>
                            <span class="leading-relaxed">{{ $address }}</span>
                        </li>

                        {{-- Business Hours --}}
                        @if (storefront_setting('business_hours'))
                            <li class="flex items-start gap-3 pt-2">
                                <i data-lucide="clock" class="mt-0.5 h-4 w-4 shrink-0 text-gray-400"></i>
                                <span
                                    class="leading-relaxed whitespace-pre-line">{{ storefront_setting('business_hours') }}</span>
                            </li>
                        @endif
                    </ul>

                    {{-- Social Icons (Left as-is) --}}
                    @php
                        use Illuminate\Support\Str;

                        $socialLinks = [
                            [
                                'key' => 'whatsapp',
                                'label' => 'WhatsApp',
                                'icon' => 'message-circle',
                                'type' => 'whatsapp',
                            ],
                            [
                                'key' => 'instagram',
                                'label' => 'Instagram',
                                'icon' => 'instagram',
                                'type' => 'url',
                            ],
                            [
                                'key' => 'facebook',
                                'label' => 'Facebook',
                                'icon' => 'facebook',
                                'type' => 'url',
                            ],
                            [
                                'key' => 'youtube',
                                'label' => 'YouTube',
                                'icon' => 'youtube',
                                'type' => 'url',
                            ],
                            [
                                'key' => 'linkedin',
                                'label' => 'LinkedIn',
                                'icon' => 'linkedin',
                                'type' => 'url',
                            ],
                            [
                                'key' => 'twitter',
                                'label' => 'Twitter / X',
                                'icon' => 'twitter',
                                'type' => 'url',
                            ],
                            [
                                'key' => 'google',
                                'label' => 'Google Maps',
                                'icon' => 'map-pin',
                                'type' => 'url',
                            ],
                        ];

                        $normalizeUrl = function ($url) {
                            $url = trim((string) $url);

                            if ($url === '') {
                                return null;
                            }

                            // Allow full URLs, and also convert plain domain text to https://
                            if (!Str::startsWith($url, ['http://', 'https://', 'mailto:', 'tel:', '/', '#'])) {
                                $url = 'https://' . $url;
                            }

                            return $url;
                        };
                    @endphp

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        @foreach ($socialLinks as $item)
                            @php
                                // Only four social handles exist as store columns
                                // (whatsapp, instagram, facebook, twitter). YouTube,
                                // LinkedIn and Google Maps have no column and stay
                                // company-level, so each key falls to whichever
                                // store actually holds it.
                                $value = in_array($item['key'], ['whatsapp', 'instagram', 'facebook', 'twitter'], true)
                                    ? storefront_setting($item['key'])
                                    : get_setting($item['key']);
                                $href = null;

                                if ($item['type'] === 'whatsapp' && !empty($value)) {
                                    $phone = preg_replace('/[^0-9]/', '', $value);
                                    if (!empty($phone)) {
                                        $href = 'https://wa.me/' . $phone;
                                    }
                                } else {
                                    $href = $normalizeUrl($value);
                                }
                            @endphp

                            @if (!empty($href))
                                <a href="{{ $href }}" target="_blank" rel="noopener noreferrer"
                                    aria-label="{{ $item['label'] }}"
                                    class="hover:text-brand-500 hover:border-brand-500 flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition-colors">
                                    @if ($item['key'] === 'whatsapp')
                                        <!-- Authentic WhatsApp SVG Icon -->
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                            fill="currentColor" class="h-4 w-4">
                                            <path
                                                d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.513 2.262 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.457L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.625 1.451 5.403.002 9.803-4.394 9.805-9.799.002-2.618-1.016-5.08-2.868-6.932C16.357 1.99 13.882 1.002 12.01 1.002 6.603 1.002 2.203 5.4 2.2 10.806c-.001 1.562.411 3.09 1.196 4.453l-.993 3.628 3.72-.975v.001-.001zM17.487 14.39c-.3-.15-1.774-.875-2.026-.967-.253-.092-.437-.138-.62.138-.184.276-.713.875-.874 1.059-.16.184-.322.207-.622.057-.302-.15-1.273-.469-2.426-1.496-.897-.8-1.502-1.787-1.679-2.087-.177-.3-.02-.461.13-.611.136-.135.3-.349.45-.524.15-.175.2-.3.3-.5.1-.2.05-.375-.025-.525-.075-.15-.62-1.494-.85-2.046-.223-.538-.447-.465-.62-.474-.16-.008-.344-.01-.528-.01-.184 0-.483.07-.736.346-.253.276-1.011.989-1.011 2.41 0 1.42 1.034 2.795 1.18 2.99.143.19 2.03 3.1 4.92 4.35.687.297 1.224.474 1.643.607.69.219 1.319.19 1.815.115.553-.083 1.774-.725 2.026-1.388.253-.662.253-1.23.177-1.347-.076-.118-.276-.188-.576-.338z" />
                                        </svg>
                                    @else
                                        <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4"></i>
                                    @endif
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>

                @if ($client)
                    <div>
                        <h4 class="mb-5 text-[15px] font-bold text-gray-900">My Account</h4>
                        <ul class="space-y-3 text-[13px] font-medium text-gray-500">
                            <li>
                                <a href="{{ tenant_url('portal/dashboard') }}"
                                    class="hover:text-brand-500 transition-colors">Dashboard</a>
                            </li>
                            <li>
                                <a href="{{ tenant_url('portal/orders') }}"
                                    class="hover:text-brand-500 transition-colors">My Orders</a>
                            </li>
                            <li>
                                <a href="{{ tenant_url('portal/profile') }}"
                                    class="hover:text-brand-500 transition-colors">My Profile</a>
                            </li>
                        </ul>
                    </div>
                @endif

                @php
                    $legalPages = $footerPages->where('type', \App\Models\Page::TYPE_LEGAL);
                    $aboutPages = $footerPages->where('type', \App\Models\Page::TYPE_ABOUT);
                @endphp
                @if ($legalPages->isNotEmpty())
                    <div>
                        <h4 class="mb-5 text-[15px] font-bold text-gray-900">Our Service</h4>

                        <ul class="space-y-3 text-[13px] font-medium text-gray-500">
                            @foreach ($legalPages as $page)
                                <li>
                                    <a href="{{ tenant_url('page/' . $page->slug) }}"
                                        class="hover:text-brand-500 transition-colors">
                                        {{ $page->title }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($aboutPages->isNotEmpty())
                    <div>
                        <h4 class="mb-5 text-[15px] font-bold text-gray-900">Information</h4>

                        <ul class="space-y-3 text-[13px] font-medium text-gray-500">
                            @foreach ($aboutPages as $page)
                                <li>
                                    <a href="{{ tenant_url('page/' . $page->slug) }}"
                                        class="hover:text-brand-500 transition-colors">
                                        {{ $page->title }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="flex flex-col items-center justify-between gap-4 border-t border-gray-200 pt-6 md:flex-row">
                <p class="text-[12px] font-medium tracking-wide text-gray-500">
                    &copy;{{ date('Y') }}, Powered by
                    <a class="font-bold" href="https://qlinkon.com/" target="_blank"
                        style="color: var(--brand-600)">Qlinkon</a>
                </p>
                <div class="flex items-center gap-4">
                    <div class="flex gap-2 opacity-80">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 374.685 35.78"
                            class="h-6 w-auto max-w-full" aria-label="Payment Methods">
                            <g id="Pyment" transform="translate(-0.365 -0.365)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="374.685" height="35.78"
                                    viewBox="0 0 374.685 35.78">
                                    <g id="Pyment_inner" transform="translate(-0.365 -0.365)">
                                        <path id="Path_58"
                                            d="M139.174.7H76.1a1.909,1.909,0,0,0-1.472.631A1.909,1.909,0,0,0,74,2.8V33.707a2.156,2.156,0,0,0,2.1,2.1h63.072a2.156,2.156,0,0,0,2.1-2.1V2.8a1.909,1.909,0,0,0-.631-1.472A1.909,1.909,0,0,0,139.174.7Z"
                                            transform="translate(80.805 0)" fill="#fff" stroke="#e0e0e0"
                                            stroke-width="0.67"></path>
                                        <path id="Path_59"
                                            d="M92.012,25.134A11.3,11.3,0,0,0,97.9,23.452a9.1,9.1,0,0,0,3.784-4.625,10.9,10.9,0,0,0,.631-6.307A10.8,10.8,0,0,0,93.9,4.11a10.05,10.05,0,0,0-6.1.631,10.28,10.28,0,0,0-4.625,3.784,12.67,12.67,0,0,0-1.682,6.1,9.862,9.862,0,0,0,3.154,7.358,9.543,9.543,0,0,0,7.358,3.154Z"
                                            transform="translate(89.073 3.528)" fill="#007bdb"></path>
                                        <path id="Path_60"
                                            d="M99.012,25.024a10.3,10.3,0,0,0,5.887-1.892,10.864,10.864,0,0,0,3.784-4.835,9.227,9.227,0,0,0,.631-5.887,9.35,9.35,0,0,0-2.943-5.256A10.721,10.721,0,0,0,100.9,4.21a10.05,10.05,0,0,0-6.1.631,10.28,10.28,0,0,0-4.625,3.784A11.3,11.3,0,0,0,88.5,14.512a8.532,8.532,0,0,0,.841,3.995,9.606,9.606,0,0,0,2.313,3.364,12.016,12.016,0,0,0,3.364,2.313,9.11,9.11,0,0,0,3.995.841Z"
                                            transform="translate(96.79 3.638)" fill="#e42b00"></path>
                                        <path id="Path_61"
                                            d="M91.654,20.537a12.3,12.3,0,0,0,3.154-7.569A12.82,12.82,0,0,0,91.654,5.4a8.8,8.8,0,0,0-2.313,3.364,9.412,9.412,0,0,0-.841,4.2,10.021,10.021,0,0,0,.841,4.2,8.8,8.8,0,0,0,2.313,3.364Z"
                                            transform="translate(96.79 5.181)" fill="#1740ce" fill-rule="evenodd">
                                        </path>
                                        <path id="Path_62"
                                            d="M102.474.7H39.4a1.909,1.909,0,0,0-1.472.631A1.909,1.909,0,0,0,37.3,2.8V33.707a2.156,2.156,0,0,0,2.1,2.1h63.072a2.156,2.156,0,0,0,2.1-2.1V2.8a1.909,1.909,0,0,0-.631-1.472A1.909,1.909,0,0,0,102.474.7Z"
                                            transform="translate(40.347 0)" fill="#fff" stroke="#e0e0e0"
                                            stroke-width="0.67"></path>
                                        <path id="Path_63"
                                            d="M55.422,25.024a10.3,10.3,0,0,0,5.887-1.892A10.864,10.864,0,0,0,65.093,18.3a10.947,10.947,0,0,0,.42-5.887A9.351,9.351,0,0,0,62.57,7.154,10.721,10.721,0,0,0,57.1,4.21a10.05,10.05,0,0,0-6.1.631,10.28,10.28,0,0,0-4.625,3.784A11.3,11.3,0,0,0,44.7,14.512a8.532,8.532,0,0,0,.841,3.995,9.606,9.606,0,0,0,2.313,3.364,12.016,12.016,0,0,0,3.364,2.313,10.022,10.022,0,0,0,4.2.841Z"
                                            transform="translate(48.505 3.638)" fill="#c00"></path>
                                        <path id="Path_64"
                                            d="M62.412,25.134A11.3,11.3,0,0,0,68.3,23.452a9.1,9.1,0,0,0,3.784-4.625,10.9,10.9,0,0,0,.631-6.307A10.8,10.8,0,0,0,64.3,4.11a10.05,10.05,0,0,0-6.1.631,10.279,10.279,0,0,0-4.625,3.784,12.67,12.67,0,0,0-1.682,6.1,9.862,9.862,0,0,0,3.154,7.358,9.543,9.543,0,0,0,7.358,3.154Z"
                                            transform="translate(56.442 3.528)" fill="#f90"></path>
                                        <path id="Path_65"
                                            d="M54.954,20.537a12.3,12.3,0,0,0,3.154-7.569A12.82,12.82,0,0,0,54.954,5.4a8.8,8.8,0,0,0-2.313,3.364,9.413,9.413,0,0,0-.841,4.2,8.532,8.532,0,0,0,.841,3.995,10.493,10.493,0,0,0,2.313,3.574Z"
                                            transform="translate(56.332 5.181)" fill="#f16d27" fill-rule="evenodd">
                                        </path>
                                        <path id="Path_66"
                                            d="M65.874.7H2.8a1.909,1.909,0,0,0-1.472.631A1.909,1.909,0,0,0,.7,2.8V33.707a1.909,1.909,0,0,0,.631,1.472A1.909,1.909,0,0,0,2.8,35.81H65.874a2.156,2.156,0,0,0,2.1-2.1V2.8a1.909,1.909,0,0,0-.631-1.472A2.271,2.271,0,0,0,65.874.7Z"
                                            transform="translate(0 0)" fill="#fff" stroke="#e0e0e0"
                                            stroke-width="0.67"></path>
                                        <path id="Path_67"
                                            d="M22.819,5.91,16.932,19.786H13.148L10.415,8.643c0-.21-.21-.42-.42-.631,0-.21-.21-.42-.42-.631A22.972,22.972,0,0,0,6,6.331V5.91h6.1a1.606,1.606,0,0,1,1.051.42,4.6,4.6,0,0,1,.631,1.051l1.472,7.989L19.035,5.91Zm14.717,9.251c0-3.574-5.046-3.784-5.046-5.466,0-.42.42-1.051,1.472-1.051a5.452,5.452,0,0,1,3.574.631l.631-2.943A8.716,8.716,0,0,0,34.8,5.7c-3.574,0-6.1,1.892-6.1,4.625,0,1.892,1.682,3.154,3.154,3.784,1.261.631,1.892,1.051,1.892,1.682,0,.841-1.051,1.261-2.1,1.261a6.91,6.91,0,0,1-3.574-.841l-.631,2.943a8.432,8.432,0,0,0,3.995.631c3.784.21,6.1-1.682,6.1-4.625Zm9.251,4.625H50.15L47.207,5.91H44.263c-.42,0-.631,0-.841.21a2.9,2.9,0,0,0-.631.841L37.536,19.786H41.32l.841-2.1h4.625ZM42.792,14.74l1.892-5.256,1.051,5.256ZM27.865,5.91,24.921,19.786H21.347L24.291,5.91h3.574Z"
                                            transform="translate(5.843 5.512)" fill="#1a1f71"></path>
                                        <path id="Path_68"
                                            d="M211.774.7H148.7a1.908,1.908,0,0,0-1.472.631A1.909,1.909,0,0,0,146.6,2.8V33.707a2.156,2.156,0,0,0,2.1,2.1h63.072a2.156,2.156,0,0,0,2.1-2.1V2.8a1.909,1.909,0,0,0-.631-1.472A2.271,2.271,0,0,0,211.774.7Z"
                                            transform="translate(160.838 0)" fill="#fff" stroke="#e0e0e0"
                                            stroke-width="0.67"></path>
                                        <path id="Path_69"
                                            d="M175.474.7H112.4a1.909,1.909,0,0,0-1.472.631A1.909,1.909,0,0,0,110.3,2.8V33.707a2.156,2.156,0,0,0,2.1,2.1h63.072a2.156,2.156,0,0,0,2.1-2.1V2.8a1.909,1.909,0,0,0-.631-1.472A2.271,2.271,0,0,0,175.474.7Z"
                                            transform="translate(120.822 0)" fill="#fff" stroke="#e0e0e0"
                                            stroke-width="0.67"></path>
                                        <path id="Path_70"
                                            d="M151.2,15.561s.21-1.261,1.051-4.2c.631-2.313,1.261-4.415,1.261-4.625l.21-.631h2.523c2.943,0,3.364,0,3.784.42.841.42,1.051,1.051.841,2.1a4.408,4.408,0,0,1-1.682,2.313c-.21.21-.42.21-.42.42a.651.651,0,0,0,.21.42l.42.42a2.321,2.321,0,0,1,0,1.682c0,.42-.21.841-.21,1.261v.631h-2.523c-.21-.21-.21-.42,0-1.261s.21-1.261,0-1.472-.42-.42-1.261-.42h-.841a13.374,13.374,0,0,0-.631,1.892l-.42,1.261h-1.051c-.21-.21-.631-.21-1.261-.21Zm6.307-5.256c.631-.21.841-.42.841-1.261v-.42c-.21-.21-.631-.21-1.682-.21h-.841l-.21.42a13.392,13.392,0,0,0-.42,1.472c0,.21,0,.21.21.21a5.274,5.274,0,0,0,2.1-.21Zm4.2,5.466a2.9,2.9,0,0,1-.841-.631c-.21-.631,0-1.682.841-4.835l.42-1.261h2.313l-.21.631a16.663,16.663,0,0,0-.841,3.574v.631c0,.21.841.42,1.261.21s.631-.631,1.682-3.995a1.092,1.092,0,0,1,.42-.841l.21-.21h2.1s-1.261,4.625-1.682,6.517v.21h-2.1c0-.21,0-.21.21-.21.21-.631,0-.631-.841,0a2.387,2.387,0,0,1-1.682.631c-.631-.21-1.051-.21-1.261-.42Zm6.938-.21a16.7,16.7,0,0,1,1.261-4.625l1.261-4.625h2.1c2.733,0,3.364,0,3.784.42a1.641,1.641,0,0,1,.841.841,2.666,2.666,0,0,1,.21,1.261,1.575,1.575,0,0,1-.21,1.051,4.41,4.41,0,0,1-2.733,2.733c-.42,0-1.051.21-1.472.21-1.892.21-1.892,0-1.892.42a13.388,13.388,0,0,0-.42,1.472l-.42,1.261H169.7c-.21-.21-.631-.21-1.051-.42Zm5.676-4.835a.772.772,0,0,0,.631-.21c.21-.21.42-.21.42-.42a.772.772,0,0,0,.21-.631V8.833c-.21-.21-.42-.42-1.472-.42h-1.051a6.138,6.138,0,0,0-.42,1.892v.21a4.629,4.629,0,0,0,1.682.21Zm3.574,5.046q-.946-.315-.631-1.892c.42-1.472,1.051-1.892,3.574-2.313,1.472-.21,1.892-.42,2.1-1.051s-.21-.42-.841-.42h-.631a.452.452,0,0,0-.42.42l-.21.21H180c-.841,0-1.261,0-1.261-.21.21-.42.42-.631.631-1.051a1.909,1.909,0,0,1,1.472-.631,5.834,5.834,0,0,1,3.784,0,1.641,1.641,0,0,1,.841.841c.21.21.21.631-.631,3.154a19.148,19.148,0,0,1-.631,2.523v.21h-2.1l-.21-.21c-.21-.42-.21-.42-1.051,0a6.139,6.139,0,0,1-1.892.42c-.21.21-.42,0-1.051,0Zm3.574-1.261.631-.631a1.264,1.264,0,0,0,.21-.841v-.42h-.42a2.387,2.387,0,0,0-1.682.631c-.21,0-.21.21-.42.21,0,.21-.21.21-.21.42s0,.42.21.42a4.629,4.629,0,0,0,1.682.21Zm3.364,4.2c-.21,0-.21-.21,0-.841s.21-.841.841-1.051a1.235,1.235,0,0,0,1.051-.42,17.036,17.036,0,0,0,0-4.625V9.043h2.313v4.2c.21.21.631-.631,2.313-3.784l.21-.42h2.1s-1.472,2.733-2.943,5.256c-2.1,3.574-2.523,4.2-3.574,4.415Z"
                                            transform="translate(165.909 5.953)" fill="#2a2c83" fill-rule="evenodd"
                                            opacity="0.94"></path>
                                        <path id="Path_71" d="M171.7,17.322,174.643,6.6l2.733,5.466Z"
                                            transform="translate(188.508 6.504)" fill="#097a44" fill-rule="evenodd">
                                        </path>
                                        <path id="Path_72" d="M170.8,17.322,173.743,6.6l2.733,5.466Z"
                                            transform="translate(187.516 6.504)" fill="#f46f20" fill-rule="evenodd">
                                        </path>
                                        <g id="Group_21" transform="translate(236.903 7.638)">
                                            <g id="Group_20" transform="translate(23.652 1.261)">
                                                <path id="Path_73"
                                                    d="M126.4,13.851v6.307h-2.1V4.6h5.256a4.775,4.775,0,0,1,3.364,1.261,4.961,4.961,0,0,1,1.472,3.364,4.183,4.183,0,0,1-1.472,3.364,4.775,4.775,0,0,1-3.364,1.261Zm0-7.358v5.466h3.364a2.049,2.049,0,0,0,1.892-.841,2.665,2.665,0,0,0,0-3.784h0a2.283,2.283,0,0,0-1.892-.841Zm12.614,2.733a4.945,4.945,0,0,1,3.574,1.261,4.279,4.279,0,0,1,1.261,3.154v6.517H141.96V18.686h0a3.788,3.788,0,0,1-3.364,1.892,4.557,4.557,0,0,1-2.943-1.051A3.286,3.286,0,0,1,134.391,17a3,3,0,0,1,1.261-2.523,5.349,5.349,0,0,1,3.364-1.051,6.105,6.105,0,0,1,2.943.631V13.43a1.912,1.912,0,0,0-.841-1.682c-.631-.42-1.261-.841-1.892-.631a3.064,3.064,0,0,0-2.733,1.472l-1.682-1.051a4.246,4.246,0,0,1,4.2-2.313ZM136.494,17a1.5,1.5,0,0,0,.631,1.261,1.394,1.394,0,0,0,1.472.42,4.409,4.409,0,0,0,2.313-.841,2.919,2.919,0,0,0,1.051-2.1,4.508,4.508,0,0,0-2.523-.841,3.552,3.552,0,0,0-2.1.631A1.587,1.587,0,0,0,136.494,17Zm18.291-7.358-6.728,15.347h-2.1l2.523-5.466-4.2-9.881h2.1l3.154,7.569h0l3.154-7.569h2.1Z"
                                                    transform="translate(-124.3 -4.6)" fill="#5f6368"></path>
                                            </g>
                                            <path id="Path_74"
                                                d="M125.92,9.392a5.821,5.821,0,0,0-.21-1.892H117.3v3.364h4.836a4.674,4.674,0,0,1-1.682,2.733V15.91H123.4a8.4,8.4,0,0,0,2.523-6.517Z"
                                                transform="translate(-108.365 -0.142)" fill="#4285f4"></path>
                                            <path id="Path_75"
                                                d="M121.489,16.358a9.115,9.115,0,0,0,5.887-2.1l-2.943-2.313a4.827,4.827,0,0,1-2.943.841A5.362,5.362,0,0,1,116.443,9H113.5v2.313A9.015,9.015,0,0,0,121.489,16.358Z"
                                                transform="translate(-112.554 1.512)" fill="#34a853"></path>
                                            <path id="Path_76"
                                                d="M116.939,12.076a4.643,4.643,0,0,1,0-3.364V6.4H114a8.906,8.906,0,0,0,0,7.989Z"
                                                transform="translate(-113.05 -1.354)" fill="#fbbc04"></path>
                                            <path id="Path_77"
                                                d="M121.489,7.574a4.775,4.775,0,0,1,3.364,1.261l2.523-2.523A8.873,8.873,0,0,0,113.5,8.835l2.943,2.313A5.77,5.77,0,0,1,121.489,7.574Z"
                                                transform="translate(-112.554 -4)" fill="#ea4335"></path>
                                        </g>
                                    </g>
                                </svg>
                            </g>
                        </svg>
                    </div>
                    <button onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
                        class="ml-4 flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition-colors hover:bg-gray-50"
                        title="Back to top">
                        <i data-lucide="arrow-up" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>
        </div>
    </footer>

    @if (storefront_setting('whatsapp'))
        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', storefront_setting('whatsapp')) }}?text={{ urlencode("Hi, I'm interested in your products") }}"
            target="_blank"
            class="fixed right-6 bottom-6 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-[#25d366] shadow-lg transition-transform hover:scale-110"
            title="Chat on WhatsApp">
            <!-- Authentic WhatsApp SVG Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                class="h-7 w-7 text-white">
                <path
                    d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.513 2.262 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.457L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.625 1.451 5.403.002 9.803-4.394 9.805-9.799.002-2.618-1.016-5.08-2.868-6.932C16.357 1.99 13.882 1.002 12.01 1.002 6.603 1.002 2.203 5.4 2.2 10.806c-.001 1.562.411 3.09 1.196 4.453l-.993 3.628 3.72-.975v.001-.001zM17.487 14.39c-.3-.15-1.774-.875-2.026-.967-.253-.092-.437-.138-.62.138-.184.276-.713.875-.874 1.059-.16.184-.322.207-.622.057-.302-.15-1.273-.469-2.426-1.496-.897-.8-1.502-1.787-1.679-2.087-.177-.3-.02-.461.13-.611.136-.135.3-.349.45-.524.15-.175.2-.3.3-.5.1-.2.05-.375-.025-.525-.075-.15-.62-1.494-.85-2.046-.223-.538-.447-.465-.62-.474-.16-.008-.344-.01-.528-.01-.184 0-.483.07-.736.346-.253.276-1.011.989-1.011 2.41 0 1.42 1.034 2.795 1.18 2.99.143.19 2.03 3.1 4.92 4.35.687.297 1.224.474 1.643.607.69.219 1.319.19 1.815.115.553-.083 1.774-.725 2.026-1.388.253-.662.253-1.23.177-1.347-.076-.118-.276-.188-.576-.338z" />
            </svg>
        </a>
    @endif

    {{-- QR Scanner Modal --}}
    <div x-cloak x-show="qrScannerOpen" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"
            @click="
                stopScanner();
                qrScannerOpen = false;
            "></div>

        <div class="relative z-10 w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h3 class="flex items-center gap-2 font-bold text-gray-800">
                    <i data-lucide="scan" class="text-brand-500 h-5 w-5"></i>
                    Scan Product QR
                </h3>
                <button
                    @click="
                        stopScanner();
                        qrScannerOpen = false;
                    "
                    class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="h-6 w-6"></i>
                </button>
            </div>

            <div class="p-6">
                <div id="reader"
                    class="w-full overflow-hidden rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50"></div>
                <p class="mt-4 text-center text-xs font-medium text-gray-400">Point your camera at a product QR code to
                    redirect</p>
            </div>
        </div>
    </div>

    {{-- Load QR Scanner Library --}}
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <script>
        let html5QrCode;

        function startScanner() {
            // Initialize scanner after modal opens
            setTimeout(() => {
                html5QrCode = new Html5Qrcode("reader");
                const config = {
                    fps: 10,
                    qrbox: {
                        width: 250,
                        height: 250
                    }
                };

                html5QrCode
                    .start({
                            facingMode: "environment"
                        }, // Use back camera
                        config,
                        (decodedText) => {
                            // 🎯 SUCCESS CALLBACK
                            console.log(`Scan result: ${decodedText}`);

                            // Simple logic: if it's a URL, redirect
                            if (decodedText.startsWith("http")) {
                                stopScanner();
                                window.location.href = decodedText;
                            }
                        },
                        (errorMessage) => {
                            // Ignore constant "no QR found" noise in console
                        },
                    )
                    .catch((err) => {
                        console.error("Camera start error:", err);
                        alert("Camera permission denied or not found.");
                    });
            }, 300);
        }

        function stopScanner() {
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode
                    .stop()
                    .then(() => {
                        html5QrCode.clear();
                        console.log("Scanner stopped.");
                    })
                    .catch((err) => console.error("Scanner stop error:", err));
            }
        }
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            lucide.createIcons();
        });

        // Absolute base URL for this tenant — works for slug, subdomain, custom domain.
        // Slug mode  → "https://plantiq.viewlink.in/techzon"
        // Host mode  → "https://test.viewlink.in"
        const storefrontBase = document.querySelector('meta[name="storefront-base"]')?.content ?? "";

        function searchDropdown() {
            return {
                query: "",
                results: [],
                open: false,
                loading: false,
                storefrontBase: storefrontBase,

                async suggest() {
                    if (this.query.length < 2) {
                        this.results = [];
                        this.open = false;
                        return;
                    }

                    this.loading = true;
                    this.open = true;

                    try {
                        const url = this.storefrontBase + "/suggest?q=" + encodeURIComponent(this.query);
                        const res = await fetch(url, {
                            headers: {
                                "X-Requested-With": "XMLHttpRequest"
                            },
                        });
                        const data = await res.json();
                        this.results = data.products ?? [];

                        this.$nextTick(() => lucide.createIcons());
                    } catch (e) {
                        console.error("[Search] Suggest error:", e);
                        this.results = [];
                    } finally {
                        this.loading = false;
                    }
                },

                goToSearch() {
                    if (!this.query.trim()) return;
                    this.open = false;
                    window.location.href = this.storefrontBase + "/search?q=" + encodeURIComponent(this.query);
                },
            };
        }
    </script>
    {{-- cart.js is loaded in <head> (deferred, ahead of Alpine) so that its
         window.* helpers exist before any component initialises. --}}
    <script>
        // Expose company slug for cart key isolation
        const slugMeta = document.querySelector('meta[name="company-slug"]');
        window.__COMPANY_SLUG__ = slugMeta?.content || "store";
        console.log("[Storefront] Loaded | Company:", window.__COMPANY_SLUG__);

        document.addEventListener("DOMContentLoaded", () => {
            if (typeof lucide !== "undefined") lucide.createIcons();
        });
    </script>

    @stack ('scripts')
</body>

</html>
