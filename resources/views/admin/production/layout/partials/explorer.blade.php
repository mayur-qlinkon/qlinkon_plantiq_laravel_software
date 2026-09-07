<aside
    class="layout-panel order-1 flex h-[350px] min-h-0 flex-col overflow-hidden rounded-lg shadow-sm lg:order-none lg:h-auto lg:rounded-none lg:rounded-l-lg lg:border-y-0 lg:border-r lg:border-l-0 lg:shadow-none"
>
    <div class="flex h-12 items-center justify-between border-b border-gray-100 px-4">
        <div class="flex items-center gap-2">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                <i class="fa-solid fa-sitemap text-xs"></i>
            </span>
            <span class="layout-panel-title">Nursery Map</span>
        </div>

        <div class="flex items-center gap-1.5">
            {{-- The map answers "where is everything right now", which this tree
                 cannot show without drilling. Surfaced here because that is the
                 moment the question comes up. --}}

            <a
                href="{{ route('admin.production.map') }}"
                class="pm-link group relative inline-flex items-center gap-1.5 overflow-hidden rounded-full px-2.5 py-1 text-[11px] font-bold text-white"
                style="background: var(--brand-600)"
                title="See where every batch is placed"
            >
                <span class="pm-shine"></span>
                <i data-lucide="map" class="relative h-3 w-3"></i>
                <span class="relative">View Map</span>
            </a>

            <button type="button" class="layout-icon-btn" title="Refresh tree" @click="loadSites()">
                <i class="fa-solid fa-rotate-right text-xs" :class="{ 'fa-spin': loading }"></i>
            </button>
        </div>
    </div>

    <div class="border-b border-gray-100 p-3">
        <label class="sr-only" for="production-layout-search">Search layout</label>
        <div class="relative">
            <input
                id="production-layout-search"
                type="search"
                class="layout-input"
                placeholder="Search sites, zones, spaces"
                x-model.debounce.150ms="search"
            />
        </div>
    </div>

    <div class="nav-scroll min-h-0 flex-1 overflow-y-auto px-2 py-3">
        <template x-if="loading && !nodes.length">
            <div class="flex h-full items-center justify-center text-xs font-semibold text-gray-400">
                Loading production layout
            </div>
        </template>

        <template x-if="errorMessage">
            <div
                class="m-2 rounded-lg border border-red-100 bg-red-50 p-3 text-xs font-semibold text-red-600"
                x-text="errorMessage"
            ></div>
        </template>

        <template x-if="!loading && !errorMessage && !nodes.length">
            <div class="flex h-full flex-col items-center justify-center px-6 text-center">
                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 text-gray-400">
                    <i class="fa-solid fa-industry"></i>
                </div>
                <p class="text-sm font-bold text-gray-600">No production sites</p>
                <p class="mt-1 text-xs leading-5 text-gray-400">Use the Inspector to create the first site.</p>
            </div>
        </template>

        {{-- Flat list, no per-row borders/shadows. Hierarchy comes from
             indentation + icon size/weight only — matches a plain file tree. --}}
        <div class="space-y-0.5">
            <template x-for="row in treeRows" :key="row.node.key">
                <button
                    type="button"
                    class="flex w-full items-center gap-2 rounded-lg py-1.5 pr-2 text-left transition-colors"
                    :class="explorerRowClass(row.node)"
                    :style="`padding-left: ${8 + row.depth * 14}px`"
                    @click="selectNode(row.node)"
                >
                    {{-- Static expand indicator — not a button. Clicking the row
                         itself already expands (selectNode loads + opens children),
                         this is purely a visual cue, so there's nothing to click
                         separately and no confusing chevron direction. --}}
                    <i
                        x-show="row.node.type !== 'growing_space'"
                        class="fa-solid shrink-0 text-[9px]"
                        :class="[
                            row.node.loading
                                ? 'fa-circle-notch fa-spin'
                                : row.node.expanded
                                  ? 'fa-caret-down'
                                  : 'fa-caret-right',
                            activeNode?.key === row.node.key ? 'text-white/70' : 'text-gray-300',
                        ]"
                    ></i>
                    <i x-show="row.node.type === 'growing_space'" class="w-[9px] shrink-0"></i>

                    <span
                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md"
                        :class="activeNode?.key === row.node.key ? 'bg-white/20 text-white' : typeTone(row.node.type)"
                    >
                        <i class="fa-solid text-[10px]" :class="typeIcon(row.node.type)"></i>
                    </span>

                    <span class="min-w-0 flex-1">
                        <span
                            class="block truncate text-[13px]"
                            :class="row.node.type === 'growing_space' ? 'font-semibold' : 'font-bold'"
                            x-text="row.node.name"
                        ></span>
                        <span
                            class="block truncate text-[10px]"
                            :class="activeNode?.key === row.node.key ? 'text-white/70' : 'text-gray-400'"
                            x-text="explorerSubLabel(row.node)"
                        ></span>
                    </span>

                    {{-- Occupancy dot — growing spaces only, right-aligned, no border noise --}}
                    <span
                        x-show="row.node.type === 'growing_space'"
                        class="h-2 w-2 shrink-0 rounded-full"
                        :class="occupancyDotClass(row.node)"
                        :title="occupancyDotTitle(row.node)"
                    ></span>
                </button>
            </template>
        </div>
    </div>
</aside>
