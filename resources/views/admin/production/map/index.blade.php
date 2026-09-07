@extends ('layouts.admin')

@section('title', 'Production Map')

@section('header-title')
    <h1 class="text-xs font-bold tracking-widest text-gray-400 uppercase sm:text-sm">Production Map</h1>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Faint grid behind bins — reads as a plan surface without pretending
                   to be a real floorplan, which we have no coordinates for. */
        .pm-grid {
            background-image:
                linear-gradient(rgba(0, 0, 0, 0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 0, 0, 0.025) 1px, transparent 1px);
            background-size: 20px 20px;
        }

        .pm-bin {
            box-shadow: 2px 2px 0 rgba(17, 24, 39, 0.06);
        }

        .pm-scroll::-webkit-scrollbar {
            height: 6px;
        }

        .pm-scroll::-webkit-scrollbar-thumb {
            background: #e5e7eb;
            border-radius: 8px;
        }
    </style>
@endpush

@section('content')
    <div class="pb-16" x-data="productionMap()">
        {{-- ── Controls & Legend ── --}}
        <div x-data="{ showInfo: false }" class="mb-6 flex flex-col gap-3">
            {{-- Filter Bar --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-3 shadow-sm lg:p-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <div class="relative flex-1">
                        <i data-lucide="search"
                            class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" x-model="q" @keydown.escape="q = ''"
                            placeholder="Find a batch code, plant or space — PB-0042, Areca Palm, GH1-R3"
                            class="w-full rounded-xl border border-gray-200 py-2.5 pr-3 pl-9 text-sm transition-colors outline-none focus:border-[var(--brand-600)] focus:ring-1 focus:ring-[var(--brand-600)]" />
                    </div>

                    <div class="flex gap-2">
                        <div class="flex-1 lg:w-48 lg:flex-none">
                            <x-alpine-select name="species" model="species" items="speciesOptions" placeholder="All plants"
                                :allow-empty="true" />
                        </div>

                        <button type="button" @click="showInfo = !showInfo"
                            :class="showInfo
                                ?
                                'bg-gray-100 text-gray-900 border-gray-300' :
                                'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                            class="flex shrink-0 items-center gap-1.5 rounded-xl border px-3 py-2.5 text-sm font-bold transition-colors">
                            <i data-lucide="info" class="h-4 w-4"></i>
                            <span class="hidden sm:inline">Stats & Legend</span>
                        </button>
                    </div>
                </div>

                <div
                    class="mt-4 flex flex-col gap-3 border-t border-gray-50 pt-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="pm-scroll flex gap-1.5 overflow-x-auto pb-1 lg:pb-0">
                        @php
                            $chips = [
                                'all' => 'All',
                                'empty' => 'Empty',
                                'partial' => 'Partly',
                                'full' => 'Full',
                                'over' => 'Over capacity',
                                'stale' => '180d+ old',
                            ];
                        @endphp
                        @foreach ($chips as $key => $label)
                            <button type="button" @click="filter = '{{ $key }}'"
                                :class="filter === '{{ $key }}'
                                    ?
                                    'text-white border-transparent' :
                                    'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                                :style="filter === '{{ $key }}' ? 'background: var(--brand-600)' : ''"
                                class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-bold transition-colors">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    <div
                        class="flex items-center justify-between text-[11px] font-bold text-gray-500 lg:justify-end lg:gap-3">
                        <span x-text="matchCount() + ' of {{ $stats['total_spaces'] }} spaces match'"></span>
                        <button type="button" @click="reset()" x-show="isFiltered()" x-cloak
                            class="flex items-center gap-1 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-bold text-red-600 transition-colors hover:bg-red-100 hover:text-red-700">
                            <i data-lucide="x" class="h-3.5 w-3.5"></i>
                            Clear
                        </button>
                    </div>
                </div>
            </div>

            {{-- Collapsible Overview & Legend --}}
            <div x-show="showInfo" x-cloak class="rounded-2xl border border-gray-200 bg-gray-50 p-4 shadow-inner lg:p-5">
                <div class="flex flex-col gap-8 lg:flex-row lg:gap-12">
                    {{-- Stats --}}
                    <div class="w-full lg:w-1/3">
                        <h3 class="mb-4 text-[10px] font-black tracking-widest text-gray-400 uppercase">
                            Facility Stats
                        </h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-[10px] font-bold text-gray-500 uppercase">Spaces</p>
                                <p class="text-xl font-black text-gray-900">{{ $stats['total_spaces'] }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-gray-500 uppercase">Batches</p>
                                <p class="text-xl font-black text-gray-900">{{ $stats['batches'] }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-gray-500 uppercase">Plants</p>
                                <p class="text-xl font-black text-gray-900">{{ number_format($stats['plants']) }}</p>
                            </div>
                            @if ($stats['capacity'] > 0)
                                <div>
                                    <p class="text-[10px] font-bold text-gray-500 uppercase">Capacity</p>
                                    <span class="text-xl font-black text-gray-900">{{ $stats['util_pct'] }}%</span>
                                </div>
                            @endif
                        </div>

                        @if ($stats['capacity'] > 0)
                            <div class="mt-4">
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200">
                                    <div class="h-full rounded-full"
                                        style="width: {{ min(100, $stats['util_pct']) }}%; background: var(--brand-600)">
                                    </div>
                                </div>
                                <p class="mt-1.5 font-mono text-[10px] text-gray-500">
                                    {{ number_format($stats['plants']) }} / {{ number_format($stats['capacity']) }} used
                                </p>
                            </div>
                        @endif
                    </div>

                    {{-- Legend --}}
                    <div class="flex-1">
                        <h3 class="mb-4 text-[10px] font-black tracking-widest text-gray-400 uppercase">Map Legend</h3>
                        <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                            <span class="flex items-center gap-3">
                                <span
                                    class="h-4 w-4 shrink-0 rounded-sm border border-dashed border-gray-300 bg-white"></span>
                                <span class="text-xs font-bold text-gray-600">Empty ({{ $stats['empty'] }})</span>
                            </span>

                            <span class="flex items-center gap-3">
                                <span class="h-4 w-4 shrink-0 rounded-sm border"
                                    style="
                                        border-color: var(--brand-600);
                                        background: color-mix(in srgb, var(--brand-600) 35%, white);
                                    "></span>
                                <span class="text-xs font-bold text-gray-600">Partly filled</span>
                            </span>

                            <span class="flex items-center gap-3">
                                <span class="h-4 w-4 shrink-0 rounded-sm border border-gray-900 bg-gray-900"></span>
                                <span class="text-xs font-bold text-gray-600">Full or occupied</span>
                            </span>

                            <span class="flex items-center gap-3">
                                <span class="h-4 w-4 shrink-0 rounded-sm border border-red-600 bg-red-500"></span>
                                <span class="text-xs font-bold text-red-600">Over capacity ({{ $stats['over'] }})</span>
                            </span>

                            <span class="flex items-center gap-3">
                                <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-amber-400">
                                    <i data-lucide="clock" class="h-2.5 w-2.5 text-white"></i>
                                </span>
                                <span class="text-xs font-bold text-amber-600">Batch 180d+ old
                                    ({{ $stats['stale'] }})</span>
                            </span>
                        </div>

                        <div
                            class="mt-5 flex items-start gap-2.5 rounded-xl border border-gray-200 bg-white p-3 text-xs text-gray-500">
                            <i data-lucide="info" class="mt-0.5 h-4 w-4 shrink-0 text-gray-400"></i>
                            <p><strong>Minimap shortcut:</strong> The small squares beside each site header below represent
                                spaces. Click one to jump straight to it.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── The map ── --}}
        @forelse ($sites as $site)
            @php
                // Zones arrive flat; grouping by parent rebuilds the tree without
                // a recursive query. 0 stands in for "no parent" because groupBy
                // turns a null key into an empty string.
                $zonesByParent = $site->zones->groupBy(fn($z) => $z->parent_id ?? 0);
                $cellsByZone = $cells->where('site_id', $site->id)->groupBy(fn($c) => $c['zone_id'] ?? 0);
                $siteCells = $cells->where('site_id', $site->id);
            @endphp

            <div class="mb-10">
                {{-- Site header --}}
                <div
                    class="sticky top-0 z-20 -mx-1 flex flex-wrap items-center gap-3 border-b border-gray-100 bg-gray-50/95 px-1 py-3 backdrop-blur">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg text-[11px] font-black text-white"
                        style="background: var(--brand-600)">
                        {{ strtoupper(substr($site->name, 0, 2)) }}
                    </div>
                    <h2 class="text-lg font-black tracking-tight text-gray-900">{{ $site->name }}</h2>
                    <span
                        class="rounded-full border border-gray-200 bg-white px-2.5 py-1 text-[11px] font-bold text-gray-500">
                        {{ $siteCells->count() }} spaces
                    </span>

                    {{-- Minimap: every space in this site, one dot each --}}
                    <div class="ml-auto hidden max-w-[420px] flex-wrap gap-[3px] lg:flex">
                        @foreach ($siteCells as $c)
                            <button type="button" @click="jump({{ $c['id'] }})"
                                title="{{ $c['code'] }} — {{ $c['status'] }}"
                                class="h-[13px] w-[13px] rounded-[3px] border transition-transform hover:scale-125
                                    @if ($c['status'] === 'empty') border-dashed border-gray-300 bg-white
                                    @elseif ($c['status'] === 'over') border-red-600 bg-red-500
                                    @elseif ($c['status'] === 'partial') border-emerald-200 bg-emerald-200
                                    @else border-gray-900 bg-gray-900 @endif"></button>
                        @endforeach
                    </div>
                </div>

                {{-- Root zones --}}
                @foreach ($zonesByParent[0] ?? [] as $zone)
                    @include ('admin.production.map._zone', [
                        'zone' => $zone,
                        'depth' => 1,
                        'zonesByParent' => $zonesByParent,
                        'cellsByZone' => $cellsByZone,
                    ])
                @endforeach

                {{-- Spaces hanging directly off the site, with no zone at all --}}
                @if (($cellsByZone[0] ?? collect())->isNotEmpty())
                    <div class="mt-6">
                        <div class="mb-3 flex items-center gap-3">
                            <div class="h-[2px] w-8 bg-gray-900"></div>
                            <span class="text-xs font-black tracking-widest text-gray-700 uppercase">Unzoned</span>
                            <div class="h-px flex-1 bg-gray-100"></div>
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ($cellsByZone[0] as $cell)
                                @include ('admin.production.map._bin', [
                                    'cell' => $cell,
                                    'compact' => false,
                                ])
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-2xl border border-gray-100 bg-white py-20 text-center">
                <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100">
                    <i data-lucide="map" class="h-7 w-7 text-gray-300"></i>
                </div>
                <p class="font-semibold text-gray-500">No production sites in this store</p>
                <p class="mt-1 text-sm text-gray-400">Set up sites and zones under Production → Layout first.</p>
            </div>
        @endforelse
    </div>

    <script>
        window.productionMap = function() {
            return {
                // Keyed by space id. Filtering is entirely client side because
                // the whole tree is already on the page.
                cells: @json ($cells),

                speciesOptions: @js(collect($species)->map(fn($s) => ['id' => $s, 'name' => $s])->values()),

                q: "",
                species: "",
                filter: "all",

                init() {
                    this.$nextTick(() => window.lucide && lucide.createIcons());
                },

                show(id) {
                    const c = this.cells[id];
                    if (!c) return false;

                    const q = this.q.trim().toLowerCase();
                    if (q && !c.search.includes(q)) return false;

                    if (this.species && !c.batches.some((b) => b.species === this.species)) return false;

                    switch (this.filter) {
                        case "empty":
                            return c.status === "empty";
                        case "partial":
                            return c.status === "partial";
                        case "full":
                            return c.status === "full";
                        case "over":
                            return c.status === "over";
                        case "stale":
                            return c.has_stale;
                        default:
                            return true;
                    }
                },

                // A zone whose every space is filtered out is noise, so it
                // collapses along with them.
                zoneVisible(ids) {
                    return ids.some((id) => this.show(id));
                },

                matchCount() {
                    return Object.keys(this.cells).filter((id) => this.show(id)).length;
                },

                isFiltered() {
                    return this.q !== "" || this.species !== "" || this.filter !== "all";
                },

                reset() {
                    this.q = "";
                    this.species = "";
                    this.filter = "all";
                },

                jump(id) {
                    const el = document.getElementById("pm-space-" + id);
                    if (!el) return;

                    el.scrollIntoView({
                        behavior: "smooth",
                        block: "center"
                    });
                    el.classList.add("ring-2", "ring-offset-2");
                    el.style.setProperty("--tw-ring-color", "var(--brand-600)");
                    setTimeout(() => el.classList.remove("ring-2", "ring-offset-2"), 1600);
                },
            };
        };
    </script>
@endsection
