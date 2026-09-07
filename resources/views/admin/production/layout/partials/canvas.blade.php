<main class="order-2 flex min-h-[500px] flex-col overflow-hidden rounded-lg border border-gray-200 bg-[#f7f8fa] shadow-sm lg:order-none lg:min-h-0 lg:rounded-none lg:border-x-0 lg:border-y-0 lg:shadow-none">
    <header class="flex h-12 shrink-0 items-center justify-between border-b border-gray-200 bg-white px-4">
        <div class="min-w-0">
            <p class="layout-panel-title">Canvas</p>
            <p class="mt-0.5 truncate text-xs font-semibold text-gray-500" x-text="selectedPath"></p>
        </div>

        <div class="flex items-center gap-2 text-[11px] font-bold text-gray-400">
            <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
            <span>Live structure</span>
        </div>
    </header>

    <section class="nav-scroll min-h-0 flex-1 overflow-auto">
        <div class="min-h-full p-5">
            <template x-if="!activeNode">
                <div class="mb-8">
                    <div class="mb-8">
                        <h2 class="text-2xl font-black tracking-tight text-gray-900">Nursery Overview</h2>
                        <p class="mt-1 text-sm font-medium text-gray-500">Select a production site below to manage inventory and view physical capacity.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <template x-for="site in nodes" :key="site.key">
                            <div
                                @click="selectNode(site)"
                                class="group cursor-pointer rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition-all hover:border-[var(--brand-600)] hover:shadow-md"
                            >
                                <div class="mb-6 flex items-center gap-4">
                                    <div
                                        class="flex h-14 w-14 items-center justify-center rounded-xl bg-gray-50 text-gray-400 transition-colors group-hover:bg-[var(--color-brand-50)] group-hover:text-[var(--brand-600)]"
                                    >
                                        <i class="fa-solid fa-industry text-2xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-black text-gray-900" x-text="site.name"></h3>
                                        <p class="mt-0.5 text-[10px] font-bold tracking-widest text-gray-400 uppercase">Production Site</p>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between border-t border-gray-100 pt-4">
                                    <span class="text-sm font-bold text-gray-600"
                                        ><span
                                            class="font-black text-gray-900"
                                            x-text="site.children_summary?.total_children || 0"
                                        ></span>
                                        managed areas</span
                                    >
                                    <i
                                        class="fa-solid fa-arrow-right text-sm text-gray-300 transition-colors group-hover:text-[var(--brand-600)]"
                                    ></i>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- SITE & ZONE DASHBOARD HEADER -->
            <template x-if="activeNode && activeNode.type !== 'growing_space'">
                <div class="mb-8">
                    <div class="mb-6">
                        <h2 class="text-3xl font-black tracking-tight text-gray-900" x-text="activeNode.name"></h2>
                        <p
                            class="mt-1 text-sm font-medium text-gray-500"
                            x-text="`Nursery ${typeLabel(activeNode.type)} Overview`"
                        ></p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <!-- Growing Spaces Card -->
                        <div class="rounded-2xl border border-gray-100 bg-gray-50 p-6 shadow-sm">
                            <p class="text-[10px] font-black tracking-widest text-gray-400 uppercase">Growing Spaces</p>
                            <div class="mt-3 flex items-baseline gap-2">
                                <span
                                    class="text-4xl font-black text-gray-900"
                                    x-text="occupancy?.occupied_spaces || 0"
                                ></span>
                                <span class="text-sm font-bold text-gray-400"
                                    >/ <span x-text="occupancy?.total_spaces || 0"></span> Active</span
                                >
                            </div>
                        </div>

                        <!-- Live Inventory Card -->
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-6 shadow-sm">
                            <p class="text-[10px] font-black tracking-widest text-emerald-600 uppercase">Live Inventory</p>
                            <div class="mt-3 flex items-baseline gap-2">
                                <span
                                    class="text-4xl font-black text-emerald-900"
                                    x-text="(occupancy?.total_plants || 0).toLocaleString()"
                                ></span>
                                <span class="text-sm font-bold text-emerald-700">Plants</span>
                            </div>
                            <p
                                class="mt-2 text-xs font-bold text-emerald-600"
                                x-text="`${occupancy?.active_batches || 0} Active Batches`"
                            ></p>
                        </div>

                        <!-- Capacity Types Card -->
                        <div class="relative overflow-hidden rounded-2xl bg-[#1a1c23] p-6 text-white shadow-md">
                            <i class="fa-solid fa-chart-pie absolute top-4 right-4 text-lg text-gray-600"></i>
                            <p class="text-[10px] font-black tracking-widest text-gray-400 uppercase">Capacity Types Across <span x-text="activeNode.type ===
                                    'site'
                                        ? 'Site'
                                        : 'Zone'"></span></p>

                            <!-- Capacity Map Loop -->
                            <div class="mt-4 flex flex-wrap gap-2">
                                <template x-if="!occupancy?.capacity_breakdown?.length">
                                    <span class="text-xs font-medium text-gray-500">No capacity data available</span>
                                </template>
                                <template x-for="cap in (occupancy?.capacity_breakdown || [])" :key="cap.unit">
                                    <div
                                        class="flex items-center gap-2 rounded-lg border border-gray-700 bg-gray-800 px-3 py-1.5 transition-colors hover:border-gray-500"
                                    >
                                        <span class="text-xs font-medium text-gray-400" x-text="cap.unit_label"></span>
                                        <span
                                            class="text-sm font-black text-white"
                                            x-text="(cap.total_capacity || 0).toLocaleString()"
                                        ></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- GROWING SPACE HEADER (Keeps the original compact design for the deepest level) -->
            <template x-if="activeNode && activeNode.type === 'growing_space'">
                <div class="mb-8 flex flex-wrap items-end justify-between gap-4 border-b border-gray-200 pb-6">
                    <div class="flex items-center gap-4">
                        <span
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-emerald-100 bg-emerald-50 text-emerald-600"
                        >
                            <i class="fa-solid fa-seedling text-xl"></i>
                        </span>
                        <div class="min-w-0">
                            <p
                                class="mb-0.5 text-[10px] font-black tracking-widest text-gray-400 uppercase"
                                x-text="spaceTypeName(activeNode.growing_space_type_id)"
                            ></p>
                            <h2
                                class="truncate text-3xl font-black tracking-tight text-gray-900"
                                x-text="activeNode.name"
                            ></h2>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="activeNode?.type === 'growing_space'">
                <div class="mx-auto max-w-4xl space-y-6">
                    {{-- Capacity Card --}}
                    <div
                        class="relative overflow-hidden rounded-3xl border border-gray-200 bg-white p-6 shadow-sm md:p-8"
                    >
                        <template x-if="occupancy.is_over_capacity">
                            <div
                                class="absolute top-0 left-0 w-full bg-red-500 py-1.5 text-center text-[10px] font-black tracking-widest text-white uppercase shadow-sm"
                            >
                                <i class="fa-solid fa-triangle-exclamation mr-1"></i> Over Capacity Warning
                            </div>
                        </template>

                        <div
                            class="flex flex-col justify-between gap-6 md:flex-row md:items-end"
                            :class="occupancy.is_over_capacity ? 'mt-4' : ''"
                        >
                            <div>
                                <p class="mb-1 text-[10px] font-black tracking-widest text-gray-400 uppercase">Space Utilization</p>
                                <div class="flex items-baseline gap-2">
                                    <span
                                        class="text-4xl font-black tracking-tight"
                                        :class="occupancy.is_over_capacity ? 'text-red-600' : 'text-gray-900'"
                                        x-text="occupancy.quantity_occupied || 0"
                                    ></span>
                                    <span class="text-lg font-bold text-gray-400"
                                        >/ <span x-text="activeNode.capacity || '-'"></span>
                                        <span x-text="capacityUnit(activeNode.growing_space_type_id)"></span
                                    ></span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span
                                    class="rounded-lg px-3 py-1.5 text-sm font-black"
                                    :class="occupancy.is_over_capacity
                                        ? 'bg-red-50 text-red-700 border border-red-200'
                                        : 'bg-emerald-50 text-emerald-700 border border-emerald-100'"
                                    x-text="
                                        `${occupancyPercentage(occupancy.quantity_occupied, activeNode.capacity)}% Full`
                                    "
                                ></span>
                            </div>
                        </div>

                        <div
                            class="mt-6 h-3 w-full overflow-hidden rounded-full bg-gray-100 ring-1 ring-gray-200 ring-inset"
                        >
                            <div
                                class="h-full rounded-full transition-all duration-700 ease-out"
                                :class="occupancyFillColor(
                                    occupancyPercentage(occupancy.quantity_occupied, activeNode.capacity),
                                )"
                                :style="`width: ${occupancyBarWidth(occupancy.quantity_occupied, activeNode.capacity)}`"
                            ></div>
                        </div>
                    </div>

                    {{-- Planted Inventory (Batches) --}}
                    <div>
                        <div class="mb-4 flex items-center justify-between px-2">
                            <h3 class="text-[11px] font-black tracking-widest text-gray-400 uppercase">
                                Planted Inventory
                            </h3>
                            <div class="flex items-center gap-2">
                                <span
                                    class="rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-500"
                                    x-text="`${occupancy.batch_count || 0} Batches`"
                                ></span>
                                <button
                                    type="button"
                                    class="layout-primary-btn !px-3 !py-1.5 !text-[11px]"
                                    @click="openPlacementModal()"
                                >
                                    <i class="fa-solid fa-plus text-[10px]"></i>
                                    Place Batch
                                </button>
                            </div>
                        </div>

                        <template x-if="occupancyLoading && !occupancy.batches?.length">
                            <div
                                class="flex items-center justify-center rounded-2xl border border-dashed border-gray-200 bg-white py-12 text-xs font-bold text-gray-400"
                            >
                                <i class="fa-solid fa-circle-notch fa-spin mr-2 text-lg text-gray-300"></i> Fetching
                                live inventory...
                            </div>
                        </template>

                        <template x-if="!occupancyLoading && !occupancy.batches?.length">
                            <div
                                class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-emerald-200 bg-emerald-50/50 py-16 text-center"
                            >
                                <div
                                    class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl border border-emerald-100 bg-white text-emerald-500 shadow-sm"
                                >
                                    <i class="fa-solid fa-seedling text-2xl"></i>
                                </div>
                                <p class="text-base font-black text-emerald-900">This space is ready for planting</p>
                                <p class="mt-1 text-sm font-medium text-emerald-700/70">Move batches here using the Inspector panel tools.</p>
                            </div>
                        </template>

                        <template x-if="occupancy.batches?.length">
                            <div class="space-y-3" :class="occupancyLoading ? 'opacity-50' : ''">
                                <template x-for="row in occupancy.batches" :key="row.placement_id">
                                    <div
                                        class="group flex cursor-pointer flex-col justify-between gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition-all hover:border-emerald-300 hover:shadow-md sm:flex-row sm:items-center"
                                        @click="window.location.href = '{{ url('/admin/production/plant-batches') }}/' + row.batch_id"
                                    >
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gray-50 text-gray-400 transition-colors group-hover:bg-emerald-50 group-hover:text-emerald-600"
                                            >
                                                <i class="fa-solid fa-leaf text-lg"></i>
                                            </div>
                                            <div>
                                                <div class="mb-0.5 flex items-center gap-2">
                                                    <span
                                                        class="text-[10px] font-black tracking-widest text-gray-500 uppercase"
                                                        x-text="row.batch_code"
                                                    ></span>
                                                </div>
                                                <p
                                                    class="truncate text-sm font-black text-gray-900"
                                                    x-text="row.product_name"
                                                ></p>
                                            </div>
                                        </div>
                                        <div
                                            class="flex flex-row items-center justify-between gap-3 border-t border-gray-100 pt-3 sm:flex-col sm:items-end sm:justify-center sm:border-0 sm:pt-0"
                                        >
                                            <div class="text-right">
                                                <span
                                                    class="block text-[10px] font-black tracking-widest text-gray-400 uppercase sm:mb-1"
                                                    >Quantity Placed</span
                                                >
                                                <span class="text-base font-black text-gray-900"
                                                    ><span x-text="row.current_quantity"></span>
                                                    <span
                                                        class="text-xs text-gray-500"
                                                        x-text="capacityUnit(activeNode.growing_space_type_id)"
                                                    ></span
                                                ></span>
                                            </div>
                                            <button
                                                type="button"
                                                class="layout-danger-btn shrink-0 !px-2.5 !py-1 !text-[11px]"
                                                title="Remove batch from this space"
                                                @click.stop="releaseBatch(row.batch_id, row.batch_code)"
                                            >
                                                <i class="fa-solid fa-arrow-right-from-bracket text-[10px]"></i>
                                                Release
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <template x-if="activeNode && activeNode.type !== 'growing_space'">
                <div>
                    <template x-if="!activeChildren.length && !loading">
                        <div class="flex min-h-[360px] flex-col items-center justify-center text-center">
                            <div
                                class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-gray-100 bg-white text-gray-300 shadow-sm"
                            >
                                <i class="fa-solid fa-folder-open text-xl"></i>
                            </div>
                            <p class="text-sm font-black text-gray-600">This area is currently empty</p>
                            <p class="mt-1 max-w-sm text-xs leading-5 text-gray-400">Use the Inspector panel on the right to set up sub-zones or active growing spaces inside <span x-text="activeNode.name" class="font-bold text-gray-500"></span>.</p>
                        </div>
                    </template>

                    <div class="mb-4" x-show="activeChildren.length">
                        <h3
                            class="mb-4 text-[11px] font-black tracking-widest text-gray-400 uppercase"
                            x-text="activeNode.type === 'site' ? 'Root Production Zones' : 'Sub-Zones & Spaces'"
                        ></h3>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <template x-for="node in activeChildren" :key="node.key">
                                <div
                                    @click="selectNode(node)"
                                    class="group flex cursor-pointer flex-col rounded-2xl border border-gray-200 bg-white shadow-sm transition-all hover:border-[var(--brand-600)] hover:shadow-md"
                                >
                                    <!-- Top section -->
                                    <div class="flex flex-1 items-center justify-between border-b border-gray-100 p-5">
                                        <div class="flex items-center gap-4">
                                            <span
                                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl"
                                                :class="typeTone(node.type)"
                                            >
                                                <i class="fa-solid text-lg" :class="typeIcon(node.type)"></i>
                                            </span>
                                            <div>
                                                <h3
                                                    class="text-lg font-black text-gray-900 transition-colors group-hover:text-[var(--brand-600)]"
                                                    x-text="node.name"
                                                ></h3>
                                                <p class="mt-0.5 text-[10px] font-bold tracking-widest text-gray-400 uppercase">
                                                    <template x-if="node.type === 'zone'">
                                                        <span
                                                            x-text="
                                                                `${node.children_summary?.total_children || 0} SUB-ITEMS`
                                                            "
                                                        ></span>
                                                    </template>
                                                    <template x-if="node.type === 'growing_space'">
                                                        <span x-text="spaceTypeName(node.growing_space_type_id)"></span>
                                                    </template>
                                                </p>
                                            </div>
                                        </div>
                                        <i
                                            class="fa-solid fa-arrow-right text-gray-300 transition-colors group-hover:text-[var(--brand-600)]"
                                        ></i>
                                    </div>

                                    <!-- Bottom section (Live Plants, Assignments & Badges) -->
                                    <div class="flex flex-col gap-4 rounded-b-2xl bg-gray-50/50 p-5">
                                        
                                        <!-- Employee Assignments (Zone only) -->
                                        <template x-if="node.type === 'zone' && node.assignments && node.assignments.length > 0">
                                            <div class="flex flex-wrap gap-1.5">
                                                <template x-for="worker in node.assignments">
                                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-white px-2 py-1 text-[10px] font-bold text-gray-600 border border-gray-200 shadow-sm">
                                                        <i class="fa-regular fa-user text-gray-400"></i>
                                                        <span x-text="worker"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </template>

                                        <div class="flex items-end justify-between">
                                            <div>
                                                <p class="mb-1 text-[10px] font-black tracking-widest text-gray-400 uppercase">Live Plants</p>
                                                <p
                                                    class="text-xl font-black text-gray-900"
                                                    x-text="(node.occupancy?.quantity_occupied || 0).toLocaleString()"
                                                ></p>
                                            </div>

                                            <!-- Capacity Unit Badges -->
                                            <div class="flex flex-wrap justify-end gap-1">
                                                <template x-if="node.type === 'growing_space'">
                                                    <span
                                                        class="rounded bg-gray-200 px-2 py-1 text-[9px] font-black tracking-wider text-gray-600 uppercase"
                                                        x-text="capacityUnit(node.growing_space_type_id)"
                                                    ></span>
                                                </template>

                                                <template x-if="node.type === 'zone' && !node.occupancy?.capacity_breakdown">
                                                    <span class="rounded bg-blue-100 px-2 py-1 text-[9px] font-black tracking-wider text-blue-600 uppercase">ZONE</span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </section>
    {{-- ══════════════════════════════════════════════════════
         PLACE BATCH MODAL
         Opens when user clicks "Place Batch" on a growing space.
         Fetches active batches, lets user pick one and confirm.
    ══════════════════════════════════════════════════════ --}}
    <template x-teleport="body">
        <div
            x-show="placementModal.open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4 backdrop-blur-sm"
            @click.self="placementModal.open = false"
            style="display: none"
        >
            <div
                class="w-full max-w-lg overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl"
                @click.stop
            >
                {{-- Modal Header --}}
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <p class="text-sm font-black text-gray-900">Place a Batch</p>
                        <p class="mt-0.5 text-xs font-medium text-gray-400">
                            Placing into:
                            <span class="font-bold text-emerald-600" x-text="activeNode?.name"></span>
                        </p>
                    </div>
                    <button type="button" class="layout-icon-btn" @click="placementModal.open = false">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                {{-- Search --}}
                <div class="border-b border-gray-100 px-5 py-3">
                    <div class="relative">
                        <i
                            class="fa-solid fa-magnifying-glass absolute top-1/2 left-3 -translate-y-1/2 text-[11px] text-gray-400"
                        ></i>
                        <input
                            type="text"
                            class="layout-input !py-2 !pl-8 !text-xs"
                            placeholder="Search by batch code or product…"
                            x-model="placementModal.search"
                            @input.debounce.300ms="searchBatches()"
                        />
                    </div>
                </div>

                {{-- Batch list --}}
                <div class="max-h-72 overflow-y-auto px-3 py-2">
                    {{-- Loading --}}
                    <template x-if="placementModal.loading">
                        <div class="flex items-center justify-center py-10 text-xs font-bold text-gray-400">
                            <i class="fa-solid fa-circle-notch fa-spin mr-2 text-base"></i>
                            Loading batches…
                        </div>
                    </template>

                    {{-- Empty --}}
                    <template x-if="!placementModal.loading && filteredModalBatches.length === 0">
                        <div class="py-10 text-center text-xs font-bold text-gray-400">
                            <i class="fa-solid fa-seedling mb-2 block text-2xl text-gray-200"></i>
                            No active batches found
                        </div>
                    </template>

                    {{-- List --}}
                    <template x-if="!placementModal.loading && filteredModalBatches.length > 0">
                        <div class="space-y-1.5 py-1">
                            <template x-for="batch in filteredModalBatches" :key="batch.id">
                                <div
                                    class="flex cursor-pointer items-center gap-3 rounded-xl border px-4 py-3 transition-all"
                                    :class="placementModal.selectedBatchId === batch.id
                                        ? 'border-emerald-400 bg-emerald-50 ring-1 ring-emerald-300'
                                        : 'border-gray-100 hover:border-emerald-200 hover:bg-emerald-50/50'"
                                    @click="placementModal.selectedBatchId = batch.id"
                                >
                                    {{-- Selection indicator --}}
                                    <span
                                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 transition-all"
                                        :class="placementModal.selectedBatchId === batch.id
                                            ? 'border-emerald-500 bg-emerald-500'
                                            : 'border-gray-300 bg-white'"
                                    >
                                        <i
                                            x-show="placementModal.selectedBatchId === batch.id"
                                            class="fa-solid fa-check text-[8px] text-white"
                                        ></i>
                                    </span>

                                    {{-- Batch info --}}
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-[10px] font-black tracking-wider text-gray-500 uppercase"
                                                x-text="batch.batch_code"
                                            ></span>
                                            <template x-if="batch.current_space">
                                                <span
                                                    class="rounded border border-amber-200 bg-amber-50 px-1.5 py-0.5 text-[9px] font-bold text-amber-600"
                                                >
                                                    <i class="fa-solid fa-location-dot mr-0.5"></i>
                                                    <span x-text="batch.current_space"></span>
                                                </span>
                                            </template>
                                        </div>
                                        <p
                                            class="truncate text-sm font-bold text-gray-900"
                                            x-text="batch.product_name || '—'"
                                        ></p>
                                    </div>

                                    {{-- Quantity --}}
                                    <div class="shrink-0 text-right">
                                        <p
                                            class="text-base font-black text-gray-900"
                                            x-text="batch.current_quantity"
                                        ></p>
                                        <p
                                            class="text-[10px] font-bold text-gray-400"
                                            x-text="capacityUnit(activeNode?.growing_space_type_id)"
                                        ></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                {{-- Notes field (shown once a batch is selected) --}}
                <template x-if="placementModal.selectedBatchId">
                    <div class="border-t border-gray-100 px-5 py-3">
                        <label class="layout-field-label"
                            >Placement Notes
                            <span class="font-medium text-gray-400 normal-case">(optional)</span></label
                        >
                        <input
                            type="text"
                            class="layout-input !text-xs"
                            placeholder="e.g. Back-left corner of bench"
                            x-model="placementModal.notes"
                            maxlength="1000"
                        />
                    </div>
                </template>

                {{-- Footer --}}
                <div class="flex items-center justify-between border-t border-gray-100 bg-gray-50 px-5 py-4">
                    <button type="button" class="layout-secondary-btn" @click="placementModal.open = false">
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="layout-primary-btn"
                        :disabled="!placementModal.selectedBatchId || placementModal.saving"
                        :class="!placementModal.selectedBatchId || placementModal.saving
                            ? 'opacity-50 cursor-not-allowed'
                            : ''"
                        @click="placeBatch()"
                    >
                        <i
                            class="fa-solid text-xs"
                            :class="placementModal.saving ? 'fa-circle-notch fa-spin' : 'fa-seedling'"
                        ></i>
                        <span x-text="placementModal.saving ? 'Placing…' : 'Confirm Placement'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</main>
