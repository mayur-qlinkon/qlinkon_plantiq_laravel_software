@extends ('layouts.admin')

@section ('title', 'Harvest Receiving Inbox - PlantIQ')

@php
    // Prepare Enum metadata for JavaScript to easily render labels and colors
    $statusMeta = [];
    foreach(\App\Enums\Production\HarvestStatus::cases() as $case) {
        $statusMeta[$case->value] = [
            'label' => $case->label(),
            'color' => $case->color(),
        ];
    }
@endphp

@section ('header-title')
    <div class="flex w-full items-center justify-between">
        <div class="flex items-center gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-lg leading-tight font-bold text-slate-900">Harvest Receiving</h1>
                </div>
                <p class="text-xs font-medium text-slate-500">Accept harvested crops into warehouse stock</p>
            </div>
        </div>
    </div>
@endsection

@section ('content')
    <div class="mx-auto w-full max-w-7xl space-y-6" x-data="harvestInboxSPA()">
        {{-- ============================================================== --}}
        {{-- SUMMARY CARDS (KPIs) --}}
        {{-- ============================================================== --}}
        <section class="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-2 md:grid md:grid-cols-4 md:overflow-visible md:pb-0 md:snap-none custom-scrollbar">
            {{-- Pending Inbox --}}
            <div
                @click="switchTab('pending')"
                class="relative min-w-[240px] shrink-0 snap-center cursor-pointer overflow-hidden rounded-2xl border p-4 shadow-sm transition-all duration-200 md:min-w-0 md:shrink"
                :class="activeStatusFilter === 'pending'
                    ? 'border-amber-300 bg-amber-50/20 ring-2 ring-amber-500'
                    : 'border-slate-200/80 bg-white hover:shadow-md'"
            >
                <div class="mb-2 flex items-start justify-between">
                    <span class="text-xs font-bold tracking-wider text-slate-500 uppercase">Awaiting Check</span>
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-100 text-sm font-bold text-amber-700"
                    >
                        <i data-lucide="clock" class="h-4 w-4"></i>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-extrabold text-slate-900 md:text-3xl" x-text="kpis.pendingCount">0</span>
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">Lots</span>
                </div>
                <p class="mt-1 text-[11px] font-medium text-slate-500">Ready for warehouse verification</p>
            </div>

            {{-- Harvested Today --}}
            <div class="min-w-[240px] shrink-0 snap-center rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm md:min-w-0 md:shrink">
                <div class="mb-2 flex items-start justify-between">
                    <span class="text-xs font-bold tracking-wider text-slate-500 uppercase">Today Harvested</span>
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-100 text-sm font-bold text-blue-700"
                    >
                        <i data-lucide="scissors" class="h-4 w-4"></i>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-extrabold text-slate-900 md:text-3xl" x-text="fmt(kpis.todayHarvestQty)"
                        >0</span
                    >
                    <span class="text-xs font-semibold text-slate-500">Plants</span>
                </div>
                <p class="mt-1 text-[11px] font-medium text-slate-500">Reported by field workers</p>
            </div>

            {{-- Received Today --}}
            <div
                @click="switchTab('received')"
                class="min-w-[240px] shrink-0 snap-center cursor-pointer rounded-2xl border p-4 shadow-sm transition-all duration-200 md:min-w-0 md:shrink"
                :class="activeStatusFilter === 'received'
                    ? 'border-emerald-300 bg-emerald-50/20 ring-2 ring-emerald-500'
                    : 'border-slate-200/80 bg-white hover:shadow-md'"
            >
                <div class="mb-2 flex items-start justify-between">
                    <span class="text-xs font-bold tracking-wider text-slate-500 uppercase">Accepted Today</span>
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-100 text-sm font-bold text-emerald-700"
                    >
                        <i data-lucide="check-circle" class="h-4 w-4"></i>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-extrabold text-slate-900 md:text-3xl" x-text="fmt(kpis.todayReceivedQty)"
                        >0</span
                    >
                    <span class="text-xs font-semibold text-slate-500">In Stock</span>
                </div>
                <p class="mt-1 flex items-center text-[11px] font-bold text-emerald-600">
                    <i data-lucide="trending-up" class="mr-1 h-3 w-3"></i> Stock Updated
                </p>
            </div>

            {{-- Oldest Waiting --}}
            <div class="min-w-[240px] shrink-0 snap-center rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm md:min-w-0 md:shrink">
                <div class="mb-2 flex items-start justify-between">
                    <span class="text-xs font-bold tracking-wider text-slate-500 uppercase">Oldest In Queue</span>
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-100 text-sm font-bold text-rose-700"
                    >
                        <i data-lucide="hourglass" class="h-4 w-4"></i>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-extrabold text-slate-900 md:text-3xl" x-text="kpis.oldestHours">0</span>
                    <span class="text-xs font-bold text-rose-600">Hours Ago</span>
                </div>
                <p
                    class="mt-1 truncate text-[11px] font-medium text-rose-600"
                    x-text="kpis.oldestLabel"
                >No pending lots</p>
            </div>
        </section>

        {{-- ============================================================== --}}
        {{-- TOOLBAR: SEARCH & FILTERS --}}
        {{-- ============================================================== --}}
        <section class="space-y-3 rounded-2xl border border-slate-200/80 bg-white p-3 shadow-sm">
            <div class="flex flex-col items-stretch justify-between gap-3 md:flex-row md:items-center">
                {{-- Status Switcher Tabs --}}
                <div class="flex max-w-full items-center overflow-x-auto rounded-xl bg-slate-100 p-1 text-xs font-bold">
                    <button
                        @click="switchTab('pending')"
                        :class="activeStatusFilter === 'pending'
                            ? 'bg-white text-slate-900 shadow-sm'
                            : 'text-slate-500 hover:text-slate-900'"
                        class="flex items-center gap-2 rounded-lg px-3.5 py-2 whitespace-nowrap transition-all"
                    >
                        <i data-lucide="alert-circle" class="h-4 w-4 text-amber-500"></i>
                        <span>Pending</span>
                        <span
                            class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] text-amber-800"
                            x-text="kpis.pendingCount"
                        ></span>
                    </button>

                    <button
                        @click="switchTab('received')"
                        :class="activeStatusFilter === 'received'
                            ? 'bg-white text-slate-900 shadow-sm'
                            : 'text-slate-500 hover:text-slate-900'"
                        class="flex items-center gap-2 rounded-lg px-3.5 py-2 whitespace-nowrap transition-all"
                    >
                        <i data-lucide="check-circle-2" class="h-4 w-4 text-emerald-500"></i>
                        <span>Accepted</span>
                    </button>

                    <button
                        @click="switchTab('cancelled')"
                        :class="activeStatusFilter === 'cancelled'
                            ? 'bg-white text-slate-900 shadow-sm'
                            : 'text-slate-500 hover:text-slate-900'"
                        class="flex items-center gap-2 rounded-lg px-3.5 py-2 whitespace-nowrap transition-all"
                    >
                        <i data-lucide="x-circle" class="h-4 w-4 text-rose-500"></i>
                        <span>Cancelled</span>
                    </button>

                    <button
                        @click="switchTab('all')"
                        :class="activeStatusFilter === 'all'
                            ? 'bg-white text-slate-900 shadow-sm'
                            : 'text-slate-500 hover:text-slate-900'"
                        class="flex items-center gap-2 rounded-lg px-3.5 py-2 whitespace-nowrap transition-all"
                    >
                        <span>All Lots</span>
                    </button>
                </div>

                {{-- Search Input --}}
                <div class="relative max-w-md flex-1">
                    <i
                        data-lucide="search"
                        class="absolute top-1/2 left-3.5 -translate-y-1/2 text-sm text-slate-400"
                    ></i>
                    <input
                        type="text"
                        x-model="searchQuery"
                        @input="onSearchInput()"
                        placeholder="Search product, batch code, worker name, or lot ID..."
                        class="focus:border-brand-500 focus:ring-brand-500/20 w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pr-4 pl-10 text-sm font-medium text-slate-800 transition-all placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:outline-none"
                    />
                </div>
            </div>
        </section>

        {{-- ============================================================== --}}
        {{-- HARVEST INBOX LIST --}}
        {{-- ============================================================== --}}
        <section
            class="space-y-3 transition-opacity duration-150"
            :class="loading ? 'pointer-events-none opacity-50' : ''"
        >
            {{-- Desktop Header Row --}}
            <div
                class="hidden grid-cols-12 gap-4 border-b border-slate-200/60 px-6 py-2.5 text-xs font-bold tracking-wider text-slate-400 uppercase lg:grid"
            >
                <div class="col-span-2">Lot & Date</div>
                <div class="col-span-3">Plant & Batch</div>
                <div class="col-span-2">Reported By</div>
                <div class="col-span-1 text-right">Harvest Qty</div>
                <div class="col-span-2 text-center">Status</div>
                <div class="col-span-2 text-right">Action</div>
            </div>

            {{-- Empty State --}}
            <template x-if="filteredLots.length === 0">
                <div class="space-y-3 rounded-2xl border border-slate-200/80 bg-white p-12 text-center" x-cloak>
                    <div
                        class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400"
                    >
                        <i data-lucide="package-open" class="h-8 w-8"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-800">No Harvest Lots Found</h3>
                    <p class="mx-auto max-w-sm text-xs text-slate-500">There are no harvests matching your search or selected status filters right now.</p>
                </div>
            </template>

            {{-- Harvest Item Card (Loop) --}}
            <template x-for="lot in filteredLots" :key="lot.id">
                <div
                    class="group relative overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm transition-all duration-200 hover:shadow-md md:px-6 md:py-4"
                >
                    {{-- Left Accent Bar --}}
                    <div
                        class="absolute top-0 bottom-0 left-0 w-1.5"
                        :style="`background-color: ${statusMeta[lot.status]?.color?.dot}`"
                    ></div>

                    <div class="grid grid-cols-1 items-center gap-4 pl-2 lg:grid-cols-12">
                        {{-- Column 1: Lot ID & Date --}}
                        <div class="flex items-start justify-between lg:col-span-2 lg:block">
                            <div>
                                <span
                                    class="font-mono text-sm font-black text-slate-900"
                                    x-text="'#H-' + lot.id"
                                ></span>
                                <p
                                    class="mt-0.5 text-xs font-medium text-slate-500"
                                    x-text="formatDate(lot.harvested_on)"
                                ></p>
                            </div>
                            <div class="lg:hidden">
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-bold whitespace-nowrap"
                                    :style="`background-color: ${statusMeta[lot.status]?.color?.bg}; color: ${statusMeta[lot.status]?.color?.text}; border: 1px solid ${statusMeta[lot.status]?.color?.dot}40`"
                                    x-text="statusMeta[lot.status]?.label"
                                ></span>
                            </div>
                        </div>

                        {{-- Column 2: Product & Plant Batch Info --}}
                        <div class="space-y-1 lg:col-span-3">
                            <div class="flex items-center gap-2">
                                <i data-lucide="sprout" class="text-brand-600 h-3.5 w-3.5"></i>
                                <h3
                                    class="text-sm leading-snug font-bold text-slate-900"
                                    x-text="lot.batch?.product?.name || 'Unknown Product'"
                                ></h3>
                            </div>
                            <div class="flex items-center gap-2 text-xs font-medium text-slate-500">
                                <span
                                    class="rounded-md bg-slate-100 px-2 py-0.5 font-mono font-semibold text-slate-700"
                                    x-text="lot.batch?.batch_code"
                                ></span>
                            </div>
                        </div>

                        {{-- Column 3: Field Worker Info --}}
                        <div class="flex items-center gap-2.5 lg:col-span-2">
                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-slate-100 text-xs font-bold text-slate-600"
                                x-text="getInitials(lot.harvested_by?.name)"
                            ></div>
                            <div>
                                <p
                                    class="text-xs font-bold text-slate-800"
                                    x-text="lot.harvested_by?.name || 'System'"
                                ></p>
                                <p class="text-[11px] text-slate-400">Field Worker</p>
                            </div>
                        </div>

                        {{-- Column 4: Harvest Quantity --}}
                        <div
                            class="flex items-center justify-between rounded-xl bg-slate-50 p-2.5 lg:col-span-1 lg:block lg:bg-transparent lg:p-0 lg:text-right"
                        >
                            <span class="text-xs font-bold text-slate-400 lg:hidden">Harvest Quantity:</span>
                            <div>
                                <div class="text-base font-black text-slate-900 lg:text-lg">
                                    <span x-text="lot.quantity_harvested"></span>
                                    <span class="text-xs font-semibold text-slate-500">Plants</span>
                                </div>
                            </div>
                        </div>

                        {{-- Column 5: Status Badge (Desktop) --}}
                        <div class="hidden justify-center lg:col-span-2 lg:flex">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-bold whitespace-nowrap"
                                :style="`background-color: ${statusMeta[lot.status]?.color?.bg}; color: ${statusMeta[lot.status]?.color?.text}; border-color: ${statusMeta[lot.status]?.color?.dot}40`"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full"
                                    :class="lot.status === 'pending' ? 'animate-pulse' : ''"
                                    :style="`background-color: ${statusMeta[lot.status]?.color?.dot}`"
                                ></span>
                                <span x-text="statusMeta[lot.status]?.label"></span>
                            </span>
                        </div>

                        {{-- Column 6: Action Buttons --}}
                        <div
                            class="flex items-center justify-end gap-2 border-t border-slate-100 pt-2 lg:col-span-2 lg:border-t-0 lg:pt-0"
                        >
                            {{-- If Pending: Show Big Primary Accept Button --}}
                            <template x-if="lot.status === 'pending'">
                                <a
                                    :href="`/admin/production/harvest-lots/${lot.id}`"
                                    class="tap-highlight-transparent bg-brand-600 hover:bg-brand-700 flex w-full items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold whitespace-nowrap text-white shadow-sm transition-all active:scale-95 lg:w-auto"
                                >
                                    <i data-lucide="package-plus" class="h-4 w-4"></i>
                                    <span>Accept & Stock</span>
                                </a>
                            </template>

                            {{-- View Details Button --}}
                            <a
                                :href="`/admin/production/harvest-lots/${lot.id}`"
                                class="flex items-center justify-center gap-1.5 rounded-xl bg-slate-100 px-3 py-2.5 text-xs font-bold text-slate-700 transition-all hover:bg-slate-200"
                                title="View Detail History"
                            >
                                <i data-lucide="chevron-right" class="h-4 w-4"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </template>
        </section>

        {{-- AJAX Pagination — Prev / Next only, driven by the data() endpoint --}}
        <div
            x-show="pagination.last_page > 1"
            x-cloak
            class="flex items-center justify-between rounded-2xl border border-slate-200/80 bg-white px-4 py-3"
        >
            <p class="text-xs font-medium text-slate-500">Page <span x-text="pagination.current_page"></span> of <span x-text="pagination.last_page"></span> &middot; <span x-text="pagination.total"></span> total lots</p>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="goToPage(pagination.current_page - 1)"
                    :disabled="pagination.current_page <= 1 || loading"
                    class="flex items-center gap-1 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700 transition-all hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-40"
                >
                    <i data-lucide="chevron-left" class="h-3.5 w-3.5"></i> Prev
                </button>
                <button
                    type="button"
                    @click="goToPage(pagination.current_page + 1)"
                    :disabled="pagination.current_page >= pagination.last_page || loading"
                    class="flex items-center gap-1 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700 transition-all hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-40"
                >
                    Next <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        window.harvestInboxSPA = function () {
            return {
                // Laravel data injection — initial page paint only.
                // All subsequent tab switches / pagination / search go through fetchLots().
                rawLots: @json ($lots->items(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
                statusMeta: @json ($statusMeta, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
                pagination: {
                    current_page: {{ $lots->currentPage() }},
                    last_page: {{ $lots->lastPage() }},
                    total: {{ $lots->total() }},
                    per_page: {{ $lots->perPage() }},
                },
                // Company-wide KPI aggregates — computed server-side, independent
                // of whichever status tab happens to be active. Refreshed on
                // every fetchLots() call so they never go stale after an
                // accept/cancel action either.
                kpis: @json ($kpis),

                activeStatusFilter: "{{ $status }}",
                searchQuery: "",
                loading: false,
                searchDebounceTimer: null,

                init() {
                    this.refreshIcons();
                },

                // Server already applies status + search filtering — rawLots
                // IS the current view's data, nothing left to filter client-side.
                get filteredLots() {
                    return this.rawLots;
                },

                switchTab(status) {
                    if (this.activeStatusFilter === status && !this.loading) return;
                    this.activeStatusFilter = status;
                    this.fetchLots(1);
                },

                goToPage(page) {
                    if (page < 1 || page > this.pagination.last_page || this.loading) return;
                    this.fetchLots(page);
                },

                onSearchInput() {
                    clearTimeout(this.searchDebounceTimer);
                    this.searchDebounceTimer = setTimeout(() => this.fetchLots(1), 350);
                },

                async fetchLots(page = 1) {
                    this.loading = true;

                    try {
                        const params = new URLSearchParams({ status: this.activeStatusFilter, page });
                        if (this.searchQuery) params.set("search", this.searchQuery);

                        const res = await fetch(`{{ route('admin.production.harvest-lots.data') }}?${params.toString()}`, {
                            headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
                        });
                        const data = await res.json();

                        if (data.success) {
                            this.rawLots = data.lots;
                            this.pagination = data.pagination;
                            this.kpis = data.kpis;
                        } else if (typeof BizAlert !== "undefined" && BizAlert.toast) {
                            BizAlert.toast("Failed to load harvest lots.", "error");
                        }
                    } catch (e) {
                        if (typeof BizAlert !== "undefined" && BizAlert.toast) {
                            BizAlert.toast("Failed to load harvest lots. Please check your connection.", "error");
                        }
                    } finally {
                        this.loading = false;
                        this.refreshIcons();
                    }
                },

                formatDate(dateString) {
                    if (!dateString) return "";
                    const date = new Date(dateString);
                    const datePart = date.toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
                    const timePart = date.toLocaleTimeString("en-IN", { hour: "2-digit", minute: "2-digit", hour12: true });
                    return `${datePart}, ${timePart}`;
                },

                fmt(n) {
                    return new Intl.NumberFormat("en-IN").format(n || 0);
                },

                getInitials(name) {
                    if (!name) return "SYS";
                    return name
                        .split(" ")
                        .map((n) => n[0])
                        .join("")
                        .substring(0, 2)
                        .toUpperCase();
                },

                refreshIcons() {
                    this.$nextTick(() => {
                        if (typeof lucide !== "undefined") {
                            lucide.createIcons();
                        }
                    });
                },
            };
        };
    </script>
@endsection
