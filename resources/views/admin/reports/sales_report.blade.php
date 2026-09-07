@extends ('layouts.admin')

@section ('title', 'Reports & Analytics')

@section ('header-title')
    <h1 class="text-sm font-bold tracking-widest text-slate-500 uppercase">Sales Report</h1>
@endsection

@push ('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@section ('content')
    @php
        $formatAmt = fn($amount) => number_format((float) $amount, 2, '.', ',');
    @endphp

    <div class="w-full px-4" x-data="reportDashboard()">
        {{-- 🌟 HEADER & FILTER TOOLBAR --}}
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="mb-1 flex items-center gap-2">
                    <span
                        class="rounded-full border border-indigo-100 bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700"
                    >
                        Showing Data For: {{ $filterLabel }}
                    </span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Sales & Revenue Analytics</h1>
                <p class="text-sm text-slate-500">Track earnings, product metrics, and key customer insights.</p>
            </div>

            {{-- Controls & Actions --}}
            <div class="flex flex-wrap items-center gap-3">
                {{-- Dynamic Filter Form --}}
                <form
                    id="filterForm"
                    action="{{ route('admin.reports.index') }}"
                    method="GET"
                    class="flex flex-wrap items-center gap-3"
                >
                    <div
                        x-data="{ customDate: {{ $activeFilter === 'custom' ? 'true' : 'false' }} }"
                        @change="
                            if ($event.target.value !== 'custom') {
                                customDate = false;
                                $el.closest('form').submit();
                            } else {
                                customDate = true;
                            }
                        "
                        class="flex flex-wrap items-center gap-3"
                    >
                        {{-- Custom Select Dropdown Component --}}
                        <div class="w-full shrink-0 sm:w-[180px]">
                            <x-custom-select
                                name="date_filter"
                                placeholder="Select Range"
                                :options="[
                                    'today'      => 'Today',
                                    'this_week'  => 'This Week',
                                    'this_month' => 'This Month',
                                    'this_year'  => 'This Year',
                                    'custom'     => 'Custom Range...'
                                ]"
                                selected="{{ $activeFilter }}"
                            />
                        </div>

                        {{-- Custom Date Inputs --}}
                        <template x-if="customDate">
                            <div
                                class="animate-fade-in flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-1 shadow-sm"
                            >
                                <input
                                    type="date"
                                    name="start_date"
                                    value="{{ request('start_date') }}"
                                    required
                                    class="rounded-md border border-slate-200 px-2.5 py-1.5 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                                />
                                <span class="text-xs font-bold text-slate-400">to</span>
                                <input
                                    type="date"
                                    name="end_date"
                                    value="{{ request('end_date') }}"
                                    required
                                    class="rounded-md border border-slate-200 px-2.5 py-1.5 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                                />
                                <button
                                    type="submit"
                                    class="rounded-md bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-slate-800"
                                >
                                    Apply
                                </button>
                                <a
                                    href="{{ route('admin.reports.index') }}"
                                    class="p-1.5 text-rose-500 transition-colors hover:text-rose-700"
                                    title="Reset Filter"
                                >
                                    <i data-lucide="x" class="h-4 w-4"></i>
                                </a>
                            </div>
                        </template>
                    </div>
                </form>

                {{-- Export PDF Button --}}
                <a
                    href="{{ route('admin.reports.export', request()->query()) }}"
                    target="_blank"
                    class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-slate-800"
                >
                    <i data-lucide="download" class="h-4 w-4"></i>
                    <span>Export PDF</span>
                </a>
            </div>
        </div>

        {{-- 🌟 1. FINANCIAL SUMMARY STAT CARDS --}}
        <div class="mb-8 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">
            {{-- Gross Sales Card --}}
            <div
                class="relative overflow-hidden rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow"
            >
                <div class="absolute top-0 right-0 left-0 h-1 bg-indigo-500"></div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold tracking-wider text-slate-400 uppercase">Gross Sales</span>
                    <div class="rounded-lg bg-indigo-50 p-2 text-indigo-600">
                        <i data-lucide="receipt" class="h-5 w-5"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="text-2xl font-bold text-slate-900">₹{{ $formatAmt($salesSummary['gross_sales']) }}</div>
                    <p class="mt-1 text-xs text-slate-500">Confirmed sales total</p>
                </div>
            </div>

            {{-- Total Returns Card --}}
            <div
                class="relative overflow-hidden rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow"
            >
                <div class="absolute top-0 right-0 left-0 h-1 bg-rose-500"></div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold tracking-wider text-slate-400 uppercase">Total Returns</span>
                    <div class="rounded-lg bg-rose-50 p-2 text-rose-600">
                        <i data-lucide="rotate-ccw" class="h-5 w-5"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="text-2xl font-bold text-rose-600">₹{{ $formatAmt($salesSummary['returns']) }}</div>
                    <p class="mt-1 text-xs text-slate-500">Credit notes processed</p>
                </div>
            </div>

            {{-- Net Sales Card --}}
            <div
                class="relative overflow-hidden rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow"
            >
                <div class="absolute top-0 right-0 left-0 h-1 bg-emerald-500"></div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold tracking-wider text-slate-400 uppercase">Net Sales</span>
                    <div class="rounded-lg bg-emerald-50 p-2 text-emerald-600">
                        <i data-lucide="trending-up" class="h-5 w-5"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="text-2xl font-bold text-emerald-700">₹{{ $formatAmt($salesSummary['net_sales']) }}</div>
                    <p class="mt-1 text-xs text-slate-500">Actual earned revenue</p>
                </div>
            </div>

            {{-- Sales By Channel Breakdown --}}
            <div
                class="relative flex flex-col justify-between overflow-hidden rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow"
            >
                <div class="absolute top-0 right-0 left-0 h-1 bg-amber-500"></div>
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-xs font-bold tracking-wider text-slate-400 uppercase">Sales By Channel</span>
                    <i data-lucide="store" class="h-4 w-4 text-slate-400"></i>
                </div>
                <div class="space-y-2.5 text-xs">
                    @php
                        $grandTotalSourceRevenue = $salesBySource->sum('total_revenue') ?: 1;
                    @endphp
                    @forelse ($salesBySource as $source)
                        @php
                            $percentage = round(($source->total_revenue / $grandTotalSourceRevenue) * 100);
                            $badgeColor = $source->source == 'pos' ? 'bg-amber-500' : ($source->source == 'online' ? 'bg-blue-500' : 'bg-slate-500');
                        @endphp
                        <div>
                            <div class="mb-1 flex justify-between font-semibold text-slate-700">
                                <span class="text-[10px] tracking-wider uppercase">{{ $source->source }}</span>
                                <span class="font-bold text-slate-900">₹{{ $formatAmt($source->total_revenue) }}</span>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-slate-100">
                                <div
                                    class="{{ $badgeColor }} h-1.5 rounded-full"
                                    style="width: {{ $percentage }}%"
                                ></div>
                            </div>
                        </div>
                    @empty
                        <div class="py-2 text-center text-xs font-medium text-slate-400 italic">
                            No sales data recorded.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- 🌟 2. PRODUCT PERFORMANCE TABLES (Side-by-Side) --}}
        <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Top Selling Products --}}
            <div class="flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                    <div class="flex items-center gap-2">
                        <div class="rounded-md bg-emerald-100 p-1.5 text-emerald-700">
                            <i data-lucide="trophy" class="h-4 w-4"></i>
                        </div>
                        <h2 class="text-sm font-bold text-slate-800">Top Selling Products</h2>
                    </div>
                    <span class="text-xs font-medium text-slate-400">By Qty Sold</span>
                </div>
                <div class="flex-1 overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead
                            class="border-b border-slate-100 bg-slate-50 text-[11px] font-bold tracking-wider text-slate-400 uppercase"
                        >
                            <tr>
                                <th class="px-6 py-3">Product Info</th>
                                <th class="px-6 py-3 text-center">Qty Sold</th>
                                <th class="px-6 py-3 text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($topProducts as $product)
                                <tr class="transition-colors hover:bg-slate-50/80">
                                    <td class="px-6 py-3.5">
                                        <div class="text-xs font-bold text-slate-900">
                                            {{ $product->display_name ?? $product->product_name }}
                                        </div>
                                        <div class="mt-0.5 font-mono text-[11px] text-slate-400">
                                            {{ $product->sku_code }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-3.5 text-center">
                                        <span
                                            class="inline-flex items-center rounded-full border border-emerald-100 bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-700"
                                        >
                                            {{ (float) $product->total_qty_sold }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3.5 text-right text-xs font-bold text-slate-900">
                                        ₹{{ $formatAmt($product->total_revenue) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-8 text-center text-xs font-medium text-slate-400">
                                        No sales data found for this period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Low Selling Products --}}
            <div class="flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                    <div class="flex items-center gap-2">
                        <div class="rounded-md bg-rose-100 p-1.5 text-rose-700">
                            <i data-lucide="trending-down" class="h-4 w-4"></i>
                        </div>
                        <h2 class="text-sm font-bold text-slate-800">Low Performing Products</h2>
                    </div>
                    <span class="text-xs font-medium text-slate-400">Needs Attention</span>
                </div>
                <div class="flex-1 overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead
                            class="border-b border-slate-100 bg-slate-50 text-[11px] font-bold tracking-wider text-slate-400 uppercase"
                        >
                            <tr>
                                <th class="px-6 py-3">Product Info</th>
                                <th class="px-6 py-3 text-center">Qty Sold</th>
                                <th class="px-6 py-3 text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($lowProducts as $product)
                                <tr class="transition-colors hover:bg-slate-50/80">
                                    <td class="px-6 py-3.5">
                                        <div class="text-xs font-bold text-slate-900">
                                            {{ $product->display_name ?? $product->product_name }}
                                        </div>
                                        <div class="mt-0.5 font-mono text-[11px] text-slate-400">
                                            {{ $product->sku_code }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-3.5 text-center">
                                        <span
                                            class="inline-flex items-center rounded-full border border-rose-100 bg-rose-50 px-2.5 py-0.5 text-xs font-bold text-rose-700"
                                        >
                                            {{ (float) $product->total_qty_sold }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3.5 text-right text-xs font-bold text-slate-900">
                                        ₹{{ $formatAmt($product->total_revenue) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-8 text-center text-xs font-medium text-slate-400">
                                        No sales data found for this period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- 🌟 3. TOP CUSTOMERS TABLE --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                <div class="flex items-center gap-2">
                    <div class="rounded-md bg-blue-100 p-1.5 text-blue-700">
                        <i data-lucide="users" class="h-4 w-4"></i>
                    </div>
                    <h2 class="text-sm font-bold text-slate-800">Top Valued Customers</h2>
                </div>
                <span class="text-xs font-medium text-slate-400">Ranked by Total Spend</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead
                        class="border-b border-slate-100 bg-slate-50 text-[11px] font-bold tracking-wider text-slate-400 uppercase"
                    >
                        <tr>
                            <th class="px-6 py-3">Customer</th>
                            <th class="px-6 py-3">Phone</th>
                            <th class="px-6 py-3 text-center">Invoices</th>
                            <th class="px-6 py-3 text-right">Total Spent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($topCustomers as $customer)
                            <tr class="transition-colors hover:bg-slate-50/80">
                                <td class="px-6 py-3.5">
                                    <div class="text-xs font-bold text-slate-900">{{ $customer->client_name }}</div>
                                </td>
                                <td class="px-6 py-3.5 font-mono text-xs text-slate-600">
                                    {{ $customer->client_phone ?? '—' }}
                                </td>
                                <td class="px-6 py-3.5 text-center">
                                    <span
                                        class="inline-flex items-center rounded-full border border-blue-100 bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-700"
                                    >
                                        {{ $customer->invoice_count }} {{ Str::plural('Invoice', $customer->invoice_count) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-right text-xs font-bold text-slate-900">
                                    ₹{{ $formatAmt($customer->total_spent) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-xs font-medium text-slate-400">
                                    No customer purchase records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push ('scripts')
    <script>
        function reportDashboard() {
            return {
                init() {
                    if (typeof lucide !== "undefined") {
                        setTimeout(() => lucide.createIcons(), 50);
                    }
                },
            };
        }
    </script>
@endpush
