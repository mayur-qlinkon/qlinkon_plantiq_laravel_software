@extends ('layouts.admin')

@section ('title', 'Sales Dashboard - ' . config('app.name', 'Laravel'))

@section ('header-title')
    <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Sales Dashboard</h1>
@endsection

@push ('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        /* Hide scrollbar for quick actions but keep it swipeable */
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* Smooth hover lift for stat cards */
        .stat-card {
            transition: all 0.2s ease-in-out;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
    </style>
@endpush

@section ('content')
    @php
        $formatAmt = fn($amount) => number_format((float) $amount, 2, '.', ',');
        
        $hour = now()->format('H');      
        // Prepare continuous 7-day data for the chart
        $chartDates = [];
        $salesData = [];
        $purchasesData = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $dateStr = now()->subDays($i)->format('Y-m-d');
            $chartDates[] = now()->subDays($i)->format('d M'); // e.g., "03 Apr"
            
            $salesData[] = $charts['weekly_sales'][$dateStr] ?? 0;
            $purchasesData[] = $charts['weekly_purchases'][$dateStr] ?? 0;
        }

        // Prepare Top Products Data
        $topProductsLabels = [];
        $topProductsSeries = [];
        foreach ($charts['top_products'] as $product) {
            $topProductsLabels[] = $product->product_name;
            $topProductsSeries[] = (float) $product->total_qty; // Casting to float for ApexCharts
        }

        // Prepare Top Customers Data (For later use or stacking)
        $topCustomersLabels = [];
        $topCustomersSeries = [];
        foreach ($charts['top_customers'] as $customer) {
            $topCustomersLabels[] = $customer->name;
            $topCustomersSeries[] = (float) $customer->total;
        }
    @endphp

    <div class="w-full pb-12">
        {{-- ═══════════════════════════════════════════
             AI CHATBOT POPUP
        ═══════════════════════════════════════════ --}}
        @if (has_module('ai_assistant'))
            <x-modals.ai-chatbot-popup />
        @endif

        @if (session('warning'))
            <div
                class="mb-6 flex items-start gap-3 rounded-r-lg border-l-4 border-amber-500 bg-amber-50 p-4 text-amber-800 shadow-sm"
            >
                <i data-lucide="alert-triangle" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600"></i>
                <div class="text-sm font-medium">{{ session('warning') }}</div>
            </div>
        @endif

        {{-- 2. THE 8 FINANCIAL METRIC CARDS (Owner Only) --}}
        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @if (has_module('invoicing'))
                {{-- Row 1: Monthly Stats --}}
                <a
                    href="{{ route('admin.invoices.index') }}"
                    class="stat-card relative block overflow-hidden rounded-xl bg-gradient-to-r from-cyan-400 to-blue-500 p-5 text-white"
                >
                    <div class="relative z-10 mb-2 flex items-start justify-between">
                        <div class="text-sm font-semibold tracking-wide">Sales (Month)</div>
                        <i data-lucide="shopping-cart" class="h-5 w-5 opacity-80"></i>
                    </div>
                    <div class="fit-number-wrapper relative z-10 flex w-full items-center overflow-hidden pr-12">
                        <div class="fit-number-text inline-block origin-left text-2xl font-black whitespace-nowrap">
                            ₹ {{ $formatAmt($financials['sales_this_month'] ?? 0) }}
                        </div>
                    </div>
                    <i
                        data-lucide="shopping-cart"
                        class="absolute -right-4 -bottom-4 h-24 w-24 text-white opacity-10"
                    ></i>
                </a>
                <a
                    href="{{ route('admin.invoice-returns.index') }}"
                    class="stat-card relative block overflow-hidden rounded-xl bg-gradient-to-r from-orange-400 to-orange-500 p-5 text-white"
                >
                    <div class="relative z-10 mb-2 flex items-start justify-between">
                        <div class="text-sm font-semibold tracking-wide">Sales Returns</div>
                        <i data-lucide="arrow-right" class="h-5 w-5 opacity-80"></i>
                    </div>
                    <div class="fit-number-wrapper relative z-10 flex w-full items-center overflow-hidden pr-12">
                        <div class="fit-number-text inline-block origin-left text-2xl font-black whitespace-nowrap">
                            ₹ {{ $formatAmt($financials['sales_returns_month'] ?? 0) }}
                        </div>
                    </div>
                    <i
                        data-lucide="arrow-right"
                        class="absolute -right-4 -bottom-4 h-24 w-24 text-white opacity-10"
                    ></i>
                </a>

                {{-- Row 2: Today Stats --}}
                <a
                    href="{{ route('admin.invoices.index') }}"
                    class="stat-card relative block overflow-hidden rounded-xl bg-gradient-to-r from-yellow-400 to-amber-500 p-5 text-white"
                >
                    <div class="relative z-10 mb-2 flex items-start justify-between">
                        <div class="text-sm font-semibold tracking-wide">Today Total Sales</div>
                        <i data-lucide="indian-rupee" class="h-5 w-5 opacity-80"></i>
                    </div>
                    <div class="fit-number-wrapper relative z-10 flex w-full items-center overflow-hidden pr-12">
                        <div class="fit-number-text inline-block origin-left text-2xl font-black whitespace-nowrap">
                            ₹ {{ $formatAmt($financials['sales_today'] ?? 0) }}
                        </div>
                    </div>
                    <i
                        data-lucide="indian-rupee"
                        class="absolute -right-4 -bottom-4 h-24 w-24 text-white opacity-10"
                    ></i>
                </a>

                <a
                    href="{{ route('admin.invoices.index') }}"
                    class="stat-card relative block overflow-hidden rounded-xl bg-gradient-to-r from-emerald-400 to-green-500 p-5 text-white"
                >
                    <div class="relative z-10 mb-2 flex items-start justify-between">
                        <div class="text-sm font-semibold tracking-wide">Today Received (Sales)</div>
                        <i data-lucide="banknote" class="h-5 w-5 opacity-80"></i>
                    </div>
                    <div class="fit-number-wrapper relative z-10 flex w-full items-center overflow-hidden pr-12">
                        <div class="fit-number-text inline-block origin-left text-2xl font-black whitespace-nowrap">
                            ₹ {{ $formatAmt($financials['received_today'] ?? 0) }}
                        </div>
                    </div>
                    <i data-lucide="banknote" class="absolute -right-4 -bottom-4 h-24 w-24 text-white opacity-10"></i>
                </a>
            @endif

            @if (has_module('purchases'))
                <a
                    href="{{ route('admin.purchases.index') }}"
                    class="stat-card relative block overflow-hidden rounded-xl bg-gradient-to-r from-purple-500 to-indigo-500 p-5 text-white"
                >
                    <div class="relative z-10 mb-2 flex items-start justify-between">
                        <div class="text-sm font-semibold tracking-wide">Purchases (Month)</div>
                        <i data-lucide="shopping-bag" class="h-5 w-5 opacity-80"></i>
                    </div>
                    <div class="fit-number-wrapper relative z-10 flex w-full items-center overflow-hidden pr-12">
                        <div class="fit-number-text inline-block origin-left text-2xl font-black whitespace-nowrap">
                            ₹ {{ $formatAmt($financials['purchases_this_month'] ?? 0) }}
                        </div>
                    </div>
                    <i
                        data-lucide="shopping-bag"
                        class="absolute -right-4 -bottom-4 h-24 w-24 text-white opacity-10"
                    ></i>
                </a>

                <a
                    href="{{ route('admin.purchase-returns.index') }}"
                    class="stat-card relative block overflow-hidden rounded-xl bg-gradient-to-r from-blue-400 to-cyan-500 p-5 text-white"
                >
                    <div class="relative z-10 mb-2 flex items-start justify-between">
                        <div class="text-sm font-semibold tracking-wide">Purchases Returns</div>
                        <i data-lucide="arrow-left" class="h-5 w-5 opacity-80"></i>
                    </div>
                    <div class="fit-number-wrapper relative z-10 flex w-full items-center overflow-hidden pr-12">
                        <div class="fit-number-text inline-block origin-left text-2xl font-black whitespace-nowrap">
                            ₹ {{ $formatAmt($financials['purchase_returns_month'] ?? 0) }}
                        </div>
                    </div>
                    <i data-lucide="arrow-left" class="absolute -right-4 -bottom-4 h-24 w-24 text-white opacity-10"></i>
                </a>

                <a
                    href="{{ route('admin.purchases.index') }}"
                    class="stat-card relative block overflow-hidden rounded-xl bg-gradient-to-r from-red-500 to-rose-600 p-5 text-white"
                >
                    <div class="relative z-10 mb-2 flex items-start justify-between">
                        <div class="text-sm font-semibold tracking-wide">Today Total Purchases</div>
                        <i data-lucide="layers" class="h-5 w-5 opacity-80"></i>
                    </div>
                    <div class="fit-number-wrapper relative z-10 flex w-full items-center overflow-hidden pr-12">
                        <div class="fit-number-text inline-block origin-left text-2xl font-black whitespace-nowrap">
                            ₹ {{ $formatAmt($financials['purchases_today'] ?? 0) }}
                        </div>
                    </div>
                    <i data-lucide="layers" class="absolute -right-4 -bottom-4 h-24 w-24 text-white opacity-10"></i>
                </a>
            @endif

            @if (has_module('expenses'))
                <a
                    href="{{ route('admin.expenses.index') }}"
                    class="stat-card relative block overflow-hidden rounded-xl bg-gradient-to-r from-fuchsia-500 to-purple-600 p-5 text-white"
                >
                    <div class="relative z-10 mb-2 flex items-start justify-between">
                        <div class="text-sm font-semibold tracking-wide">Today Total Expense</div>
                        <i data-lucide="minus-square" class="h-5 w-5 opacity-80"></i>
                    </div>
                    <div class="fit-number-wrapper relative z-10 flex w-full items-center overflow-hidden pr-12">
                        <div class="fit-number-text inline-block origin-left text-2xl font-black whitespace-nowrap">
                            ₹ {{ $formatAmt($financials['expense_today'] ?? 0) }}
                        </div>
                    </div>
                    <i
                        data-lucide="minus-square"
                        class="absolute -right-4 -bottom-4 h-24 w-24 text-white opacity-10"
                    ></i>
                </a>
            @endif
        </div>

        @if (has_module('invoicing'))
            {{-- MAIN CONTENT SPLIT: Charts --}}
            <div class="mb-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
                {{-- LEFT: Weekly Chart (Takes up 2/3 space) --}}
                {{-- 🟢 LEFT COLUMN: Stacked Bar Chart & Top Products Table (2/3 space) --}}
                <div class="flex flex-col gap-8 lg:col-span-2">
                    {{-- Weekly Chart --}}
                    <div class="h-fit rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-sm font-black tracking-wider text-gray-800 uppercase">
                                This Week Sales & Purchases
                            </h2>
                            <i data-lucide="bar-chart-2" class="h-5 w-5 text-gray-400"></i>
                        </div>
                        <div id="weekly-chart" class="h-[300px] w-full"></div>
                    </div>

                    {{-- NEW: Top Selling Products Table (List View) --}}
                    <div class="h-fit overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                            <div class="flex items-center gap-2">
                                <h2 class="text-sm font-black tracking-wider text-gray-800 uppercase">
                                    Top Selling Products ({{ now()->format('F') }})
                                </h2>
                            </div>
                        </div>
                        <div class="custom-scrollbar overflow-x-auto">
                            <table class="w-full text-left text-sm whitespace-nowrap">
                                <thead
                                    class="border-b border-gray-50 bg-white text-[10px] font-black tracking-wider text-gray-400 uppercase"
                                >
                                    <tr>
                                        <th class="px-6 py-4">Product</th>
                                        <th class="px-6 py-4 text-center">Quantity</th>
                                        <th class="px-6 py-4 text-right">Grand Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @forelse ($charts['top_products'] as $product)
                                        <tr class="transition-colors hover:bg-gray-50/50">
                                            <td class="px-6 py-4 font-semibold text-gray-600">
                                                {{ $product->product_name }}
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span
                                                    class="rounded-md border border-indigo-100 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-500"
                                                >
                                                    {{ (float) $product->total_qty }} {{ $product->unit_name }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right font-medium text-gray-700">
                                                ₹ {{ $formatAmt($product->total_revenue) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td
                                                colspan="3"
                                                class="px-6 py-8 text-center text-sm font-medium text-gray-400"
                                            >
                                                No top products found for this month.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- RIGHT: Pie Charts (Takes up 1/3 space) --}}
                <div class="flex flex-col gap-6 lg:col-span-1">
                    {{-- Top Customers Pie Chart --}}
                    <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-sm font-black tracking-wider text-gray-800 uppercase">Top 5 Customers</h2>
                            <i data-lucide="users" class="h-5 w-5 text-gray-400"></i>
                        </div>
                        @if (empty($topCustomersSeries))
                            <div class="flex h-[220px] items-center justify-center text-xs font-bold text-gray-400">
                                No customer data yet
                            </div>
                        @else
                            <div id="top-customers-chart" class="flex h-[220px] w-full justify-center"></div>
                        @endif
                    </div>

                    {{-- Top Products Pie Chart --}}
                    <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-sm font-black tracking-wider text-gray-800 uppercase">
                                Top Selling Products
                            </h2>
                            <i data-lucide="package" class="h-5 w-5 text-gray-400"></i>
                        </div>
                        @if (empty($topProductsSeries))
                            <div class="flex h-[220px] items-center justify-center text-xs font-bold text-gray-400">
                                No product data yet
                            </div>
                        @else
                            <div id="top-products-chart" class="flex h-[220px] w-full justify-center"></div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- RECENT ACTIVITIES --}}
        <div class="mb-8 overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50/50 px-6 py-4">
                <div class="flex items-center gap-2">
                    <i data-lucide="activity" class="h-5 w-5 text-gray-400"></i>
                    <h2 class="text-sm font-black tracking-wider text-gray-800 uppercase">Recent Activities</h2>
                </div>
                <span
                    class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-bold text-gray-400"
                >
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400"></span>
                    Live
                </span>
            </div>

            @if ($tables['recent_activities']->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-gray-300">
                    <i data-lucide="inbox" class="mb-3 h-10 w-10"></i>
                    <span class="text-sm font-bold">No recent activity yet</span>
                </div>
            @else
                <div class="divide-y divide-gray-50">
                    @foreach ($tables['recent_activities'] as $activity)
                        @php
                            $activityColors = [
                                'emerald' => ['bg' => 'bg-emerald-100', 'icon' => 'text-emerald-600', 'badge' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'],
                                'blue'    => ['bg' => 'bg-blue-100',    'icon' => 'text-blue-600',    'badge' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200'],
                                'orange'  => ['bg' => 'bg-orange-100',  'icon' => 'text-orange-600',  'badge' => 'bg-orange-50 text-orange-700 ring-1 ring-orange-200'],
                                'violet'  => ['bg' => 'bg-violet-100',  'icon' => 'text-violet-600',  'badge' => 'bg-violet-50 text-violet-700 ring-1 ring-violet-200'],
                            ];
                            $ac = $activityColors[$activity['color']] ?? $activityColors['blue'];
                        @endphp
                        <div
                            class="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-gray-50/60 sm:gap-4 sm:px-6"
                        >
                            {{-- Colored Icon --}}
                            <div
                                class="flex-shrink-0 w-9 h-9 rounded-lg {{ $ac['bg'] }} flex items-center justify-center"
                            >
                                <i data-lucide="{{ $activity['icon'] }}" class="w-4 h-4 {{ $ac['icon'] }}"></i>
                            </div>

                            {{-- Main Content --}}
                            <div class="min-w-0 flex-1">
                                <div class="mb-0.5 flex flex-wrap items-center gap-1.5">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $ac['badge'] }}">
                                        {{ $activity['label'] }}
                                    </span>
                                    <span
                                        class="truncate text-xs font-bold text-gray-700"
                                        >{{ $activity['reference'] }}</span
                                    >
                                </div>
                                <p class="truncate text-[11px] leading-tight text-gray-400">{{ $activity['description'] }}</p>
                            </div>

                            {{-- Amount + Time (right side) --}}
                            <div class="flex-shrink-0 text-right">
                                <p class="text-sm font-black text-gray-800 tabular-nums">₹ {{ $formatAmt($activity['amount']) }}</p>
                                <p class="mt-0.5 text-[10px] font-medium whitespace-nowrap text-gray-400">
                                    {{ $activity['time']->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- 3. RECENT SALES — Invoicing module only --}}
        @if (has_module('invoicing'))
            <x-admin.dashboard.recent-sales :sales="$tables['recent_sales']" />
        @endif

        {{-- 4. STOCK ALERTS — Inventory module only --}}
        @if (has_module('inventory'))
            <x-admin.dashboard.stock-alerts :skus="$tables['low_stock_skus']" />
        @endif
    </div>

    <script src="{{ asset('assets/js/apexcharts.min.js') }}"></script>

    <script>
        setTimeout(() => {
            (function () {
                var chartEl = document.querySelector("#weekly-chart");
                if (!chartEl) return;

                // Clear any old chart instance before rendering a new one (crucial for SPA)
                chartEl.innerHTML = "";

                var options = {
                    series: [
                        {
                            name: "Sales",
                            data: @json ($salesData),
                        },
                        {
                            name: "Purchases",
                            data: @json ($purchasesData),
                        },
                    ],
                    chart: {
                        type: "bar",
                        height: 300,
                        toolbar: { show: false },
                        fontFamily: "inherit",
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: "45%",
                            borderRadius: 4,
                        },
                    },
                    dataLabels: { enabled: false },
                    stroke: { show: true, width: 3, colors: ["transparent"] },
                    xaxis: {
                        categories: @json ($chartDates),
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: { style: { colors: "#9ca3af", fontSize: "12px", fontWeight: 500 } },
                    },
                    yaxis: {
                        labels: {
                            style: { colors: "#9ca3af", fontSize: "12px", fontWeight: 500 },
                            formatter: function (val) {
                                return "₹" + val.toLocaleString();
                            },
                        },
                    },
                    fill: { opacity: 1 },
                    colors: ["#06b6d4", "#6366f1"],
                    legend: {
                        position: "top",
                        horizontalAlign: "center",
                        fontWeight: 600,
                        markers: { radius: 12 },
                    },
                    grid: {
                        borderColor: "#f3f4f6",
                        strokeDashArray: 4,
                    },
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return "₹ " + val.toLocaleString();
                            },
                        },
                    },
                };

                var chart = new ApexCharts(chartEl, options);
                chart.render();
            })();

            // 🥧 Top Customers Pie Chart
            (function () {
                var el = document.querySelector("#top-customers-chart");
                if (!el) return;
                el.innerHTML = "";

                var options = {
                    series: @json ($topCustomersSeries),
                    labels: @json ($topCustomersLabels),
                    chart: { type: "pie", height: 250, fontFamily: "inherit" },
                    colors: ["#4f46e5", "#10b981", "#f59e0b", "#ef4444", "#06b6d4"],
                    stroke: { width: 2, colors: ["#ffffff"] },
                    dataLabels: { enabled: false },
                    legend: { position: "bottom", fontWeight: 500, markers: { radius: 12 } },
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return "₹ " + val.toLocaleString();
                            },
                        },
                    },
                };
                new ApexCharts(el, options).render();
            })();

            // 🥧 Top Products Pie Chart
            (function () {
                var el = document.querySelector("#top-products-chart");
                if (!el) return;
                el.innerHTML = "";

                var options = {
                    series: @json ($topProductsSeries),
                    labels: @json ($topProductsLabels),
                    chart: { type: "pie", height: 250, fontFamily: "inherit" },
                    colors: ["#3b82f6", "#8b5cf6", "#ec4899", "#f43f5e", "#f97316"],
                    stroke: { width: 2, colors: ["#ffffff"] },
                    dataLabels: { enabled: false },
                    legend: { position: "bottom", fontWeight: 500, markers: { radius: 12 } },
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return val + " Units";
                            },
                        },
                    },
                };
                new ApexCharts(el, options).render();
            })();

            // 🪗 Auto-Shrink Financial Numbers if they get too large
            (function () {
                const adjustTextSize = () => {
                    document.querySelectorAll(".fit-number-wrapper").forEach((wrapper) => {
                        const text = wrapper.querySelector(".fit-number-text");
                        if (!text) return;

                        // Temporarily reset scale to measure the text's true, uncompressed width
                        text.style.transform = "scale(1)";

                        let wrapperWidth = wrapper.clientWidth;
                        let textWidth = text.scrollWidth;

                        // If the text is wider than the wrapper, calculate the exact shrink ratio
                        if (textWidth > wrapperWidth) {
                            let scaleValue = wrapperWidth / textWidth;
                            // Apply the scale. origin-left in CSS ensures it stays pinned to the left edge
                            text.style.transform = `scale(${scaleValue})`;
                        }
                    });
                };

                // Run immediately on load (especially important for SPA)
                adjustTextSize();

                // Listen for screen rotations or window resizing to recalculate
                window.addEventListener("resize", adjustTextSize);

                // Ensure it runs after SPA transitions are fully injected
                document.addEventListener("spa:page-loaded", adjustTextSize); // Swap this event name with your actual SPA custom event if you have one
            })();
        }, 300);
    </script>
@endsection
