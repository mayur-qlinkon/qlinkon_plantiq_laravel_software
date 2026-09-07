@extends ('layouts.admin')

@section ('title', 'Order Inquiries')

@section ('header-title')
    <div>
        <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Orders</h1>
    </div>
@endsection

@push ('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        .stat-card {
            background: #fff;
            border: 1.5px solid #f1f5f9;
            border-radius: 14px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .filter-input {
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 13px;
            color: #1f2937;
            outline: none;
            font-family: inherit;
            background: #fff;
            transition: border-color 150ms ease;
        }
        .search-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-wrapper i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: #9ca3af;
            width: 14px;
            height: 14px;
        }

        .filter-input:focus {
            border-color: var(--brand-600);
        }

        select.filter-input {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 14px;
            padding-right: 30px;
            appearance: none;
            cursor: pointer;
        }

        /* Desktop Table Layout */
        @media (min-width: 1024px) {
            .order-row {
                display: grid;
                grid-template-columns: 40px 1.3fr 1.5fr 1.8fr 1fr 1fr 1fr auto;
                gap: 12px;
                align-items: center;
                padding: 14px 16px;
                border-bottom: 1px solid #f3f4f6;
                transition: background 120ms ease;
                min-width: 1050px;
            }
            .order-row:hover {
                background: #fafafa;
            }
            .order-row:last-child {
                border-bottom: none;
            }
        }

        /* Mobile Smart Card Layout */
        @media (max-width: 1023px) {
            .order-row {
                display: grid;
                grid-template-columns: 1fr auto;
                grid-template-areas:
                    "order status"
                    "customer total"
                    "items items"
                    "payment actions";
                gap: 12px;
                padding: 16px;
                border-bottom: 6px solid #f1f5f9;
                background: #fff;
            }
            .order-row:last-child {
                border-bottom: none;
            }

            .order-cell-num {
                display: none;
            }
            .order-cell-order {
                grid-area: order;
            }
            .order-cell-customer {
                grid-area: customer;
            }
            .order-cell-items {
                grid-area: items;
                background: #f8fafc;
                padding: 10px;
                border-radius: 8px;
            }
            .order-cell-total {
                grid-area: total;
                display: flex;
                flex-direction: column;
                align-items: flex-end;
            }
            .order-cell-status {
                grid-area: status;
                display: flex;
                align-items: flex-start;
                justify-content: flex-end;
            }
            .order-cell-payment {
                grid-area: payment;
                display: flex;
                align-items: center;
            }
            .order-cell-actions {
                grid-area: actions;
                display: flex;
                justify-content: flex-end;
            }

            .mobile-hide {
                display: none !important;
            }
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .pay-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
    </style>
@endpush

@section ('content')
    <div class="pb-10">
        {{-- ── Stats bar ── --}}
        <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
            <div class="stat-card">
                <div class="stat-icon bg-blue-50">
                    <i data-lucide="package" class="h-5 w-5 text-blue-600"></i>
                </div>
                <div>
                    <p class="text-[11px] font-bold tracking-wider text-gray-400 uppercase">Total</p>
                    <p class="text-xl font-black text-gray-900">{{ number_format($stats['total']) }}</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-amber-50">
                    <i data-lucide="inbox" class="h-5 w-5 text-amber-600"></i>
                </div>
                <div>
                    <p class="text-[11px] font-bold tracking-wider text-gray-400 uppercase">Inquiries</p>
                    <p class="text-xl font-black text-gray-900">{{ number_format($stats['inquiries']) }}</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-green-50">
                    <i data-lucide="check-circle" class="h-5 w-5 text-green-600"></i>
                </div>
                <div>
                    <p class="text-[11px] font-bold tracking-wider text-gray-400 uppercase">Confirmed</p>
                    <p class="text-xl font-black text-gray-900">{{ number_format($stats['confirmed']) }}</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-purple-50">
                    <i data-lucide="truck" class="h-5 w-5 text-purple-600"></i>
                </div>
                <div>
                    <p class="text-[11px] font-bold tracking-wider text-gray-400 uppercase">Shipped</p>
                    <p class="text-xl font-black text-gray-900">{{ number_format($stats['shipped']) }}</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-emerald-50">
                    <i data-lucide="indian-rupee" class="h-5 w-5 text-emerald-600"></i>
                </div>
                <div>
                    <p class="text-[11px] font-bold tracking-wider text-gray-400 uppercase">Revenue</p>
                    <p class="text-xl font-black text-gray-900">₹{{ number_format($stats['revenue'], 0) }}</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-orange-50">
                    <i data-lucide="clock" class="h-5 w-5 text-orange-500"></i>
                </div>
                <div>
                    <p class="text-[11px] font-bold tracking-wider text-gray-400 uppercase">Today</p>
                    <p class="text-xl font-black text-gray-900">{{ number_format($stats['today']) }}</p>
                </div>
            </div>
        </div>

        {{-- ── Main card ── --}}
        <div class="rounded-2xl border border-gray-100 bg-white">
            {{-- ── Toolbar ──
             Every filter sits in one row. The old arrangement hid payment,
             source and dates behind a toggle, which meant two clicks before a
             filter could even be seen — and the count badge is what people
             actually read after filtering, so it stays in view. --}}
            <div class="border-b border-gray-100 px-4 py-3">
                <form
                    method="GET"
                    action="{{ route('admin.orders.index') }}"
                    class="flex flex-col gap-2.5 lg:flex-row lg:items-center"
                >
                    {{-- Search --}}
                    <div class="relative w-full lg:min-w-[200px] lg:flex-1">
                        <input
                            type="text"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Search order number, customer, phone..."
                            class="filter-input w-full"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:flex lg:items-center">
                        {{-- Who took the order --}}
                        @if ($creators->isNotEmpty() || $hasStorefrontOrders)
                            @php
                            $userOptions = $creators->pluck('name', 'id')->toArray();

                            // Storefront orders are placed by guests, so they carry no
                            // creator at all. Without an explicit option they would be
                            // unreachable from this filter.
                            if ($hasStorefrontOrders) {
                                $userOptions['storefront'] = 'Storefront (online)';
                            }
                        @endphp
                            <div class="w-full shrink-0 lg:w-[150px]">
                                <x-custom-select
                                    name="created_by"
                                    placeholder="All Users"
                                    :options="$userOptions"
                                    selected="{{ request('created_by') }}"
                                />
                            </div>
                        @endif

                        {{-- Status --}}
                        <div class="w-full shrink-0 lg:w-[145px]">
                            <x-custom-select
                                name="status"
                                placeholder="All Status"
                                :options="collect($statusColors)->mapWithKeys(fn($s) => [$s => ucfirst(str_replace('_', ' ', $s))])->toArray()"
                                selected="{{ request('status') }}"
                            />
                        </div>

                        {{-- Payment --}}
                        <div class="w-full shrink-0 lg:w-[140px]">
                            <x-custom-select
                                name="payment_status"
                                placeholder="All Payments"
                                :options="collect(array_keys(\App\Models\Order::PAYMENT_STATUS_COLORS))->mapWithKeys(fn($ps) => [$ps => ucfirst($ps)])->toArray()"
                                selected="{{ request('payment_status') }}"
                            />
                        </div>

                        {{-- Source --}}
                        <div class="w-full shrink-0 lg:w-[135px]">
                            <x-custom-select
                                name="source"
                                placeholder="All Sources"
                                :options="[
                                'storefront' => 'Storefront',
                                'whatsapp' => 'WhatsApp',
                                'admin' => 'Admin',
                                'pos' => 'POS'
                            ]"
                                selected="{{ request('source') }}"
                            />
                        </div>

                        {{-- Dates --}}
                        <div class="col-span-2 flex shrink-0 items-center gap-1.5 sm:col-span-3 lg:col-span-1">
                            <input
                                type="date"
                                name="from"
                                value="{{ request('from') }}"
                                max="{{ request('to') ?: now()->format('Y-m-d') }}"
                                title="From date"
                                class="filter-input w-full lg:w-[132px]"
                            />
                            <span class="text-[11px] font-medium text-gray-400">to</span>
                            <input
                                type="date"
                                name="to"
                                value="{{ request('to') }}"
                                min="{{ request('from') }}"
                                title="To date"
                                class="filter-input w-full lg:w-[132px]"
                            />
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2 lg:ml-auto">
                        @if (request()->hasAny(['q', 'status', 'from', 'to', 'payment_status', 'source', 'created_by']))
                            <a
                                href="{{ route('admin.orders.index') }}"
                                class="flex items-center justify-center gap-1 rounded-xl border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-500 transition-colors hover:bg-gray-50 hover:text-gray-700"
                            >
                                <i data-lucide="x" class="h-3.5 w-3.5"></i> Clear
                            </a>
                        @endif

                        @if (has_permission('orders.create'))
                            <a
                                href="{{ route('admin.orders.create') }}"
                                class="flex flex-1 items-center justify-center gap-1.5 rounded-xl px-4 py-2 text-center text-sm font-bold text-white transition-opacity hover:opacity-90 lg:flex-none"
                                style="background: var(--brand-600)"
                            >
                                <i data-lucide="plus" class="h-4 w-4"></i> Create
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- ── Result summary ── --}}
            <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 bg-gray-50/60 px-4 py-2.5">
                <span class="text-[12px] font-bold text-gray-500">
                    <span id="order-count-badge">{{ number_format($orders->total()) }}</span>
                    order{{ $orders->total() === 1 ? '' : 's' }}
                </span>

                @if (request('created_by'))
                    @php
                    $activeCreator = request('created_by') === 'storefront'
                        ? 'Storefront (online)'
                        : $creators->firstWhere('id', (int) request('created_by'))?->name;
                @endphp
                    @if ($activeCreator)
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-2.5 py-1 text-[11px] font-bold text-gray-600"
                        >
                            <i data-lucide="user-round" class="h-3 w-3 text-gray-400"></i>
                            {{ $activeCreator }}
                        </span>
                    @endif
                @endif
            </div>
            <div id="orders-list-container">
                <div class="overflow-x-auto">
                    {{-- ── Table header ── --}}
                    <div class="order-row mobile-hide border-b border-gray-100 bg-gray-50/80">
                        <span class="text-[10px] font-black tracking-widest text-gray-400 uppercase">#</span>
                        <span class="text-[10px] font-black tracking-widest text-gray-400 uppercase">Order</span>
                        <span class="text-[10px] font-black tracking-widest text-gray-400 uppercase">Customer</span>
                        <span class="text-[10px] font-black tracking-widest text-gray-400 uppercase"
                            >Items & Address</span
                        >
                        <span class="text-[10px] font-black tracking-widest text-gray-400 uppercase">Total</span>
                        <span class="text-[10px] font-black tracking-widest text-gray-400 uppercase">Status</span>
                        <span class="text-[10px] font-black tracking-widest text-gray-400 uppercase">Payment</span>
                        <span class="text-[10px] font-black tracking-widest text-gray-400 uppercase">Action</span>
                    </div>

                    {{-- ── Order rows ── --}}
                    @forelse ($orders as $order)
                        @php
                            $sc = $order->status_color;
                            $pc = $order->payment_status_color;
                        @endphp
                        <div class="order-row group">
                            {{-- Numbering ── --}}
                            <div class="order-cell-num">
                                <span
                                    class="text-[12px] font-semibold text-gray-500"
                                    >{{ $orders->firstItem() + $loop->index }}</span
                                >
                            </div>
                            {{-- Order ── --}}
                            <div class="order-cell-order min-w-0">
                                <div class="mb-0.5 flex items-center gap-2">
                                    <span
                                        class="font-mono text-[13px] font-bold text-gray-900"
                                        >{{ $order->order_number }}</span
                                    >
                                    @if ($order->source === 'storefront')
                                        <span
                                            class="rounded bg-blue-50 px-1.5 py-0.5 text-[9px] font-bold tracking-wide text-blue-600 uppercase"
                                            >Web</span
                                        >
                                    @elseif ($order->source === 'whatsapp')
                                        <span
                                            class="rounded bg-green-50 px-1.5 py-0.5 text-[9px] font-bold tracking-wide text-green-600 uppercase"
                                            >WA</span
                                        >
                                    @endif
                                </div>
                                <p class="mt-0.5 text-[10px] text-gray-400">{{ $order->created_at->format('d M Y, h:i A') }}</p>
                            </div>
                            {{-- Customer ── --}}
                            <div class="order-cell-customer min-w-0">
                                <p class="truncate text-[13px] font-semibold text-gray-700">{{ $order->customer_name }}</p>
                                <p class="font-mono text-[11px] font-medium text-gray-400">{{ $order->customer_phone }}</p>
                            </div>
                            {{-- Items + Address ── --}}
                            <div class="order-cell-items min-w-0">
                                <p class="mb-1 text-[12px] font-medium text-gray-600">
                                    {{ $order->items_count }} item{{ $order->items_count !== 1 ? 's' : '' }} · {{ $order->items_qty }} qty
                                </p>
                                @if ($order->items->isNotEmpty())
                                    <p class="truncate text-[11px] text-gray-400">
                                        {{ $order->items->first()->product_name }}
                                        @if ($order->items->count() > 1)
                                            +{{ $order->items->count() - 1 }} more
                                        @endif
                                    </p>
                                @endif
                                @if ($order->delivery_city)
                                    <p class="mt-0.5 flex items-center gap-1 text-[11px] text-gray-400 lg:mt-1">
                                        <i data-lucide="map-pin" class="h-3 w-3 flex-shrink-0"></i>
                                        <span class="truncate">{{ $order->delivery_address }}</span>
                                    </p>
                                @endif
                            </div>

                            {{-- Total ── --}}
                            @if ($order->order_type !== 'inquiry')
                                <div class="order-cell-total">
                                    <p class="text-[14px] font-bold text-gray-900">₹{{ number_format($order->total_amount, 2) }}</p>
                                    <p class="text-[11px] font-medium text-gray-400 uppercase">{{ $order->payment_method ?? 'COD' }}</p>
                                </div>
                            @else
                                <div class="order-cell-total">
                                    <p class="text-[14px] font-bold text-gray-900">-</p>
                                </div>
                            @endif

                            {{-- Status badge ── --}}
                            <div class="order-cell-status">
                                <span
                                    class="status-badge"
                                    style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }}"
                                >
                                    <span class="status-dot" style="background: {{ $sc['dot'] }}"></span>
                                    {{ $order->status_label }}
                                </span>
                            </div>

                            {{-- Payment badge ── --}}
                            <div class="order-cell-payment">
                                <span class="pay-badge" style="background: {{ $pc['bg'] }}; color: {{ $pc['text'] }}">
                                    {{ ucfirst($order->payment_status) }}
                                </span>
                            </div>

                            {{-- Actions ── --}}
                            <div class="order-cell-actions flex items-center gap-2">
                                @if (has_permission('orders.view'))
                                    <a
                                        href="{{ route('admin.orders.show', $order->id) }}"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-blue-50 hover:text-blue-600"
                                        title="View Details"
                                    >
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </a>
                                @endif

                                {{-- 🌟 NEW: Edit Button (Only for Admin orders that are not fulfilled/cancelled) --}}
                                @if ($order->source === 'admin' && in_array($order->status, ['inquiry', 'confirmed', 'processing']))
                                    <a
                                        href="{{ route('admin.orders.edit', $order->id) }}"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-amber-50 hover:text-amber-600"
                                        title="Edit Order"
                                    >
                                        <i data-lucide="edit" class="h-4 w-4"></i>
                                    </a>
                                @endif

                                @if ($order->customer_phone && get_setting('whatsapp'))
                                    <a
                                        href="https://wa.me/91{{ preg_replace('/[^0-9]/', '', $order->customer_phone) }}?text={{ urlencode('Hi ' . $order->customer_name . ', your order #' . $order->order_number . ' has been received. We will confirm shortly.') }}"
                                        target="_blank"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-green-50 hover:text-green-600"
                                        title="WhatsApp Customer"
                                    >
                                        <i data-lucide="message-circle" class="h-4 w-4"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-20 text-center text-gray-400">
                            <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100">
                                <i data-lucide="package-x" class="h-7 w-7 text-gray-300"></i>
                            </div>
                            <p class="mb-1 font-semibold text-gray-500">No orders found</p>
                            <p class="text-sm">
                                @if (request()->hasAny(['q', 'status', 'from', 'to']))
                                    Try clearing your filters
                                @else
                                    Orders from your storefront will appear here
                                @endif
                            </p>
                            @if (request()->hasAny(['q', 'status', 'from', 'to']))
                                <a
                                    href="{{ route('admin.orders.index') }}"
                                    class="mt-3 rounded-xl px-4 py-2 text-sm font-bold text-white"
                                    style="background: var(--brand-600)"
                                >
                                    Clear Filters
                                </a>
                            @endif
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- ── Pagination ── --}}
            @if ($orders->hasPages())
                <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-[12px] font-medium text-gray-400">Showing {{ $orders->firstItem() }}–{{ $orders->lastItem() }} of {{ $orders->total() }} orders</p>
                    <div class="flex items-center gap-1.5">
                        @if ($orders->onFirstPage())
                            <span
                                class="flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-lg border border-gray-200 text-gray-300"
                            >
                                <i data-lucide="chevron-left" class="h-4 w-4"></i>
                            </span>
                        @else
                            <a
                                href="{{ $orders->previousPageUrl() }}"
                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition-colors hover:bg-gray-50"
                            >
                                <i data-lucide="chevron-left" class="h-4 w-4"></i>
                            </a>
                        @endif

                        @foreach ($orders->getUrlRange(max(1, $orders->currentPage() - 2), min($orders->lastPage(), $orders->currentPage() + 2)) as $page => $url)
                            @if ($page == $orders->currentPage())
                                <span
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-sm font-bold text-white"
                                    style="background: var(--brand-600)"
                                    >{{ $page }}</span
                                >
                            @else
                                <a
                                    href="{{ $url }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 transition-colors hover:bg-gray-50"
                                >
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach

                        @if ($orders->hasMorePages())
                            <a
                                href="{{ $orders->nextPageUrl() }}"
                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition-colors hover:bg-gray-50"
                            >
                                <i data-lucide="chevron-right" class="h-4 w-4"></i>
                            </a>
                        @else
                            <span
                                class="flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-lg border border-gray-200 text-gray-300"
                            >
                                <i data-lucide="chevron-right" class="h-4 w-4"></i>
                            </span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push ('scripts')
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const forms = document.querySelectorAll('form[action="{{ route('admin.orders.index') }}"]');
                const container = document.getElementById("orders-list-container");
                const countBadge = document.getElementById("order-count-badge");
                let timeout = null;

                const fetchResults = (url) => {
                    // Visual loading state
                    container.style.opacity = "0.5";
                    container.style.pointerEvents = "none";

                    fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest" } })
                        .then((res) => res.text())
                        .then((html) => {
                            const doc = new DOMParser().parseFromString(html, "text/html");

                            // Replace Table & Pagination
                            const newContainer = doc.getElementById("orders-list-container");
                            if (newContainer) container.innerHTML = newContainer.innerHTML;

                            // Replace Counter Badge
                            const newBadge = doc.getElementById("order-count-badge");
                            if (newBadge && countBadge) countBadge.innerHTML = newBadge.innerHTML;

                            // Reset styling & update Browser URL History
                            container.style.opacity = "1";
                            container.style.pointerEvents = "auto";
                            window.history.pushState({}, "", url);

                            // Re-render Lucide icons in the newly injected HTML
                            if (typeof lucide !== "undefined") {
                                lucide.createIcons();
                            }
                        })
                        .catch(() => {
                            container.style.opacity = "1";
                            container.style.pointerEvents = "auto";
                        });
                };

                const submitForms = () => {
                    const url = new URL("{{ route('admin.orders.index') }}", window.location.origin);

                    forms.forEach((form) => {
                        new FormData(form).forEach((v, k) => {
                            // Only non-empty values reach the URL. Setting an empty one
                            // would leave the parameter behind, so choosing "All Status"
                            // never actually cleared the filter.
                            if (v && v !== "all") {
                                url.searchParams.set(k, v);
                            }
                        });
                    });

                    fetchResults(url.toString());
                };

                forms.forEach((form) => {
                    form.addEventListener("submit", (e) => {
                        e.preventDefault();
                        submitForms();
                    });

                    // Delegated rather than bound per element: the custom select swaps
                    // its inner markup after Alpine boots, so listeners attached at
                    // DOMContentLoaded would be lost on the elements that matter most.
                    form.addEventListener("change", (e) => {
                        if (e.target.matches('select, input[type="date"]')) {
                            submitForms();
                        }
                    });
                });

                // Debounce Search input so it searches automatically while typing
                const searchInput = document.querySelector('input[name="q"]');
                if (searchInput) {
                    searchInput.addEventListener("input", () => {
                        clearTimeout(timeout);
                        timeout = setTimeout(submitForms, 400);
                    });
                    // Prevent the enter key from submitting the form conventionally
                    searchInput.addEventListener("keydown", (e) => {
                        if (e.key === "Enter") {
                            e.preventDefault();
                            clearTimeout(timeout);
                            submitForms();
                        }
                    });
                }

                // Global interceptor for Links (Pagination & Clear Filters)
                document.addEventListener("click", function (e) {
                    // Intercept Pagination
                    const pageLink = e.target.closest('#orders-list-container a[href*="?page="]');
                    if (pageLink) {
                        e.preventDefault();
                        fetchResults(pageLink.href);
                        return;
                    }

                    // Intercept "Clear Filters"
                    const clearBtn = e.target.closest('a[href="{{ route('admin.orders.index') }}"]');
                    if (clearBtn && !clearBtn.hasAttribute("target")) {
                        e.preventDefault();

                        document.querySelectorAll(".filter-input, select").forEach((i) => {
                            i.value = "";
                            i.dispatchEvent(new Event("change", { bubbles: true }));
                        });

                        fetchResults("{{ route('admin.orders.index') }}");
                    }
                });
            });
        </script>
    @endpush
@endsection
