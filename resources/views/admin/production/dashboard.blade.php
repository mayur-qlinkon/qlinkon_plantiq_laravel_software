@extends ('layouts.admin')

@section('title', 'Production Dashboard')

@section('header-title')
    <div class="flex w-full items-center justify-between">
        <div>
            <h1 class="text-lg font-bold tracking-tight text-gray-900">Production Dashboard</h1>
            <p class="text-[12px] font-medium text-gray-500">{{ now()->format('d M, Y') }}</p>
        </div>
    </div>
@endsection

@section('content')
    <div class="mx-auto w-full" x-data="productionDashboard()">
        {{-- ══ Stat Cards ═══════════════════════════════════════ --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="mb-2 flex items-center gap-2">
                    <i data-lucide="clipboard-check" class="h-4 w-4 text-gray-400"></i>
                    <p class="text-xs font-semibold text-gray-500">Today's Activities</p>
                </div>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['today_activities'] }}</p>
            </div>
            <div class="rounded-2xl border border-orange-100 bg-orange-50 p-4 shadow-sm">
                <div class="mb-2 flex items-center gap-2">
                    <i data-lucide="list-todo" class="h-4 w-4 text-orange-600"></i>
                    <p class="text-xs font-semibold text-orange-700">Total Pending Tasks</p>
                </div>
                <p class="text-2xl font-bold text-orange-700">{{ $stats['pending_tasks'] }}</p>
            </div>

            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 shadow-sm">
                <div class="mb-2 flex items-center gap-2">
                    <i data-lucide="sprout" class="h-4 w-4 text-emerald-600"></i>
                    <p class="text-xs font-semibold text-emerald-700">Live Plants</p>
                </div>
                <p class="text-2xl font-bold text-emerald-700">{{ number_format($stats['live_plants']) }}</p>
            </div>

            <div class="rounded-2xl border border-sky-100 bg-sky-50 p-4 shadow-sm">
                <div class="mb-2 flex items-center gap-2">
                    <i data-lucide="layers" class="h-4 w-4 text-sky-600"></i>
                    <p class="text-xs font-semibold text-sky-700">Live Batches</p>
                </div>
                <p class="text-2xl font-bold text-sky-700">{{ $stats['live_batches'] }}</p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="mb-2 flex items-center gap-2">
                    <i data-lucide="users" class="h-4 w-4 text-gray-400"></i>
                    <p class="text-xs font-semibold text-gray-500">Workers Assigned</p>
                </div>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['total_workers'] }}</p>
            </div>

            <div class="rounded-2xl border border-red-100 bg-red-50 p-4 shadow-sm">
                <div class="mb-2 flex items-center gap-2">
                    <i data-lucide="skull" class="h-4 w-4 text-red-600"></i>
                    <p class="text-xs font-semibold text-red-700">Dead Plants Today</p>
                </div>
                <p class="text-2xl font-bold text-red-700">{{ $stats['today_dead_plants'] }}</p>
            </div>

            <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4 shadow-sm">
                <div class="mb-2 flex items-center gap-2">
                    <i data-lucide="scissors" class="h-4 w-4 text-amber-600"></i>
                    <p class="text-xs font-semibold text-amber-700">Harvested Today</p>
                </div>
                <p class="text-2xl font-bold text-amber-700">{{ number_format($stats['today_harvested']) }}</p>
            </div>

            <div class="rounded-2xl border border-purple-100 bg-purple-50 p-4 shadow-sm">
                <div class="mb-2 flex items-center gap-2">
                    <i data-lucide="calendar-check-2" class="h-4 w-4 text-purple-600"></i>
                    <p class="text-xs font-semibold text-purple-700">This Month Harvested</p>
                </div>
                <p class="text-2xl font-bold text-purple-700">{{ number_format($stats['month_harvested']) }}</p>
            </div>
        </div>

        {{-- ══ Quick Actions ═════════════════════════════════════ --}}
        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <button type="button" @click="openModal('harvest')"
                class="flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-bold text-gray-700 shadow-sm transition-all hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700">
                <i data-lucide="scissors" class="h-4 w-4"></i>
                Harvest
            </button>
            <button type="button" @click="openModal('loss')"
                class="flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-bold text-gray-700 shadow-sm transition-all hover:border-red-300 hover:bg-red-50 hover:text-red-700">
                <i data-lucide="skull" class="h-4 w-4"></i>
                Loss
            </button>
            <button type="button" @click="openModal('adjustment')"
                class="flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-bold text-gray-700 shadow-sm transition-all hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700">
                <i data-lucide="sliders-horizontal" class="h-4 w-4"></i>
                Adjustment
            </button>
            <button type="button" @click="openModal('move')"
                class="flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-bold text-gray-700 shadow-sm transition-all hover:border-purple-300 hover:bg-purple-50 hover:text-purple-700">
                <i data-lucide="move" class="h-4 w-4"></i>
                Move Batch
            </button>
        </div>

        {{-- ══ Active Batches + Recent Activity ═════════════════ --}}
        <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
            {{-- Active Plant Batches --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm xl:col-span-2">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <h2 class="text-sm font-bold text-gray-900">Active Plant Batches</h2>
                    <a href="{{ route('admin.production.plant-batches.index') }}"
                        class="text-xs font-bold text-sky-600 hover:text-sky-700">
                        View All
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100 text-left">
                                <th class="px-6 py-2.5 text-[11px] font-bold tracking-wide text-gray-400 uppercase">
                                    Batch ID
                                </th>
                                <th class="px-6 py-2.5 text-[11px] font-bold tracking-wide text-gray-400 uppercase">
                                    Plant
                                </th>
                                <th class="px-6 py-2.5 text-[11px] font-bold tracking-wide text-gray-400 uppercase">
                                    Zone / Site
                                </th>
                                <th class="px-6 py-2.5 text-[11px] font-bold tracking-wide text-gray-400 uppercase">
                                    Remaining
                                </th>
                                <th class="px-6 py-2.5 text-[11px] font-bold tracking-wide text-gray-400 uppercase">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($activeBatches as $batch)
                                @php
                                    $placement = $batch->currentPlacement->first();
                                    $zoneName = $placement?->growingSpace?->zone?->name;
                                    $siteName =
                                        $placement?->growingSpace?->zone?->site?->name ??
                                        $placement?->growingSpace?->site?->name;
                                @endphp
                                <tr class="border-b border-gray-50 last:border-b-0 hover:bg-gray-50">
                                    <td class="px-6 py-3 text-sm font-bold text-gray-900">
                                        <a href="{{ route('admin.production.plant-batches.show', $batch) }}"
                                            class="hover:text-sky-600">
                                            {{ $batch->batch_code }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-3">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                                <i data-lucide="sprout" class="h-3.5 w-3.5"></i>
                                            </div>
                                            <span
                                                class="text-sm font-medium text-gray-700">{{ $batch->product->name ?? '—' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 text-xs text-gray-500">
                                        {{ $zoneName ?? '—' }}
                                        @if ($siteName)
                                            <br /><span class="text-gray-400">{{ $siteName }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-sm font-semibold text-gray-700">
                                        {{ number_format($batch->current_quantity) }}
                                    </td>
                                    <td class="px-6 py-3">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold"
                                            style="background-color: {{ $batch->status_color['bg'] }}; color: {{ $batch->status_color['text'] }}">
                                            <span class="h-1.5 w-1.5 rounded-full"
                                                style="background-color: {{ $batch->status_color['dot'] }}"></span>
                                            {{ $batch->status_label }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-sm font-medium text-gray-400">
                                        No active batches right now.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($activeBatches->hasPages())
                    <div class="border-t border-gray-100 px-6 py-3">{{ $activeBatches->onEachSide(1)->links() }}</div>
                @endif
            </div>

            {{-- Recent Activity --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h2 class="text-sm font-bold text-gray-900">Recent Activity</h2>
                </div>

                <div class="max-h-[560px] overflow-y-auto">
                    @forelse ($recentActivity as $item)
                        <div class="flex items-start gap-3 border-b border-gray-50 px-6 py-3 last:border-b-0">
                            <div
                                class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500">
                                <i data-lucide="{{ $item['icon'] }}" class="h-3.5 w-3.5"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-800">{{ $item['title'] }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ $item['subtitle'] }}
                                    @if ($item['actor'])
                                        &middot; {{ $item['actor'] }}
                                    @endif
                                </p>
                            </div>
                            <span class="shrink-0 text-[11px] whitespace-nowrap text-gray-400">
                                {{ \Illuminate\Support\Carbon::parse($item['timestamp'])->diffForHumans(null, true) }}
                            </span>
                        </div>
                    @empty
                        <div class="py-8 text-center">
                            <p class="text-sm font-medium text-gray-400">No recent activity yet.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ══ Quick Action Modals (static — backend on hold) ═══ --}}
        @php
            // Accent classes are spelled out in full (not built via string interpolation)
            // so Tailwind's compiler can detect them at build time.
$quickActionModals = [
    'harvest' => [
        'title' => 'Record Harvest',
        'icon' => 'scissors',
        'iconBg' => 'bg-emerald-100',
        'iconText' => 'text-emerald-600',
    ],
    'loss' => [
        'title' => 'Record Loss',
        'icon' => 'skull',
        'iconBg' => 'bg-red-100',
        'iconText' => 'text-red-600',
    ],
    'adjustment' => [
        'title' => 'Batch Adjustment',
        'icon' => 'sliders-horizontal',
        'iconBg' => 'bg-sky-100',
        'iconText' => 'text-sky-600',
    ],
    'move' => [
        'title' => 'Move Batch',
        'icon' => 'move',
        'iconBg' => 'bg-purple-100',
        'iconText' => 'text-purple-600',
                ],
            ];
        @endphp

        @foreach ($quickActionModals as $key => $modal)
            <div x-show="activeModal === '{{ $key }}'" x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" style="display: none">
                <div class="w-full max-w-md rounded-2xl bg-white shadow-xl">
                    <div class="flex items-center gap-3 border-b border-gray-100 px-6 py-4">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl {{ $modal['iconBg'] }} {{ $modal['iconText'] }}">
                            <i data-lucide="{{ $modal['icon'] }}" class="h-4 w-4"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-900">{{ $modal['title'] }}</h3>
                        <button type="button" @click="closeModal()" class="ml-auto text-gray-400 hover:text-gray-600">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>

                    <div class="space-y-4 px-6 py-5">
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-600">Batch</label>
                            <x-alpine-select name="plant_batch_id" model="formData.plant_batch_id" items="batches"
                                item-label="item.batch_code + ' — ' + item.product_name" placeholder="Select a batch"
                                :allow-empty="true" />
                        </div>

                        <div x-show="selectedBatch" x-cloak
                            class="rounded-xl bg-gray-50 px-3 py-2 text-xs font-medium text-gray-600">
                            @if ($key === 'move')
                                Currently in:
                                <span class="font-bold text-gray-800"
                                    x-text="
                                        selectedBatch?.growing_space_name
                                            ? selectedBatch.growing_space_name +
                                              (selectedBatch.zone_name ? ' — ' + selectedBatch.zone_name : '')
: 'Not placed yet'
                                    "></span>
                            @else
                                Current available quantity:
                                <span class="font-bold text-gray-800"
                                    x-text="selectedBatch?.current_quantity ?? '—'"></span>
                            @endif
                        </div>

                        @if ($key === 'harvest')
                            <div>
                                <label class="mb-1.5 block text-xs font-bold text-gray-600">Quantity Harvested</label>
                                <input type="number" min="1" x-model="formData.quantity_harvested"
                                    class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm"
                                    placeholder="e.g. 120" />
                            </div>
                        @elseif ($key === 'loss')
                            <div>
                                <label class="mb-1.5 block text-xs font-bold text-gray-600">Quantity Lost</label>
                                <input type="number" min="1" x-model="formData.quantity_lost"
                                    class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm"
                                    placeholder="e.g. 6" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-bold text-gray-600">Reason</label>
                                <x-alpine-select name="reason" model="formData.reason" items="reasonOptions"
                                    placeholder="Select reason" :allow-empty="true" />
                            </div>
                        @elseif ($key === 'adjustment')
                            <div>
                                <label class="mb-1.5 block text-xs font-bold text-gray-600">New Quantity</label>
                                <input type="number" min="0" x-model="formData.current_quantity"
                                    class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm"
                                    placeholder="Corrected total" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-bold text-gray-600">Reason</label>
                                <input type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm"
                                    placeholder="Why is this being adjusted?" />
                            </div>
                        @else
                            <div>
                                <label class="mb-1.5 block text-xs font-bold text-gray-600">Move To (Growing Space)</label>
                                <x-alpine-select name="growing_space_id" model="formData.growing_space_id"
                                    items="growingSpaceOptions" placeholder="Select destination" :allow-empty="true" />
                            </div>
                        @endif

                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-600">Notes</label>
                            <textarea rows="2" x-model="formData.notes" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm"
                                placeholder="Optional notes"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
                        <button type="button" @click="closeModal()"
                            class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-bold text-gray-600 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="button" @click="submitAction()" :disabled="isSubmitting"
                            class="rounded-xl bg-gray-900 px-4 py-2 text-sm font-bold text-white hover:bg-gray-800 disabled:opacity-50">
                            Save
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        function productionDashboard() {
            return {
                activeModal: null,
                isSubmitting: false,
                batches: [],
                batchesLoaded: false,

                reasonOptions: [{
                        id: 'disease',
                        name: 'Disease'
                    },
                    {
                        id: 'pest',
                        name: 'Pest'
                    },
                    {
                        id: 'environmental',
                        name: 'Environmental'
                    },
                    {
                        id: 'other',
                        name: 'Other'
                    }
                ],
                growingSpaceOptions: @js(
    collect($growingSpaces ?? [])
        ->map(fn($s) => ['id' => $s->id, 'name' => $s->name . ($s->zone ? ' — ' . $s->zone->name : '')])
        ->values(),
),

                formData: {
                    plant_batch_id: "",
                    quantity_harvested: "",
                    quantity_lost: "",
                    reason: "",
                    current_quantity: "",
                    growing_space_id: "",
                    notes: "",
                },

                get selectedBatch() {
                    return this.batches.find((b) => String(b.id) === String(this.formData.plant_batch_id)) || null;
                },

                async loadBatches() {
                    if (this.batchesLoaded) return;
                    try {
                        const response = await fetch("{{ route('admin.production.quick-actions.batches') }}", {
                            headers: {
                                Accept: "application/json"
                            },
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.batches = data.batches;
                            this.batchesLoaded = true;
                        }
                    } catch (error) {
                        console.error("Failed to load batches:", error);
                    }
                },

                openModal(type) {
                    this.activeModal = type;
                    this.resetForm();
                    this.loadBatches();
                    this.$nextTick(() => {
                        if (typeof lucide !== "undefined") lucide.createIcons();
                    });
                },

                closeModal() {
                    this.activeModal = null;
                    this.resetForm();
                },

                resetForm() {
                    this.formData = {
                        plant_batch_id: "",
                        quantity_harvested: "",
                        quantity_lost: "",
                        reason: "",
                        current_quantity: "",
                        growing_space_id: "",
                        notes: "",
                    };
                },

                getEndpoint() {
                    const routes = {
                        harvest: "{{ route('admin.production.quick-actions.harvest') }}",
                        loss: "{{ route('admin.production.quick-actions.loss') }}",
                        adjustment: "{{ route('admin.production.quick-actions.adjustment') }}",
                        // Reuses the specific placement endpoint dynamically based on selected batch
                        move: `/admin/production/plant-batches/${this.formData.plant_batch_id}/place`,
                    };
                    return routes[this.activeModal];
                },

                async submitAction() {
                    if (!this.formData.plant_batch_id) {
                        if (typeof BizAlert !== "undefined") BizAlert.toast("Please select a batch first.", "error");
                        return;
                    }

                    this.isSubmitting = true;
                    const endpoint = this.getEndpoint();

                    try {
                        const response = await fetch(endpoint, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    "content"),
                            },
                            body: JSON.stringify(this.formData),
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            if (typeof BizAlert !== "undefined")
                                BizAlert.toast(data.message || "Action completed successfully.", "success");
                            this.closeModal();

                            // Reload to reflect new stat totals
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            if (typeof BizAlert !== "undefined")
                                BizAlert.toast(data.message || "Unable to complete action.", "error");
                        }
                    } catch (error) {
                        console.error("Submission error:", error);
                        if (typeof BizAlert !== "undefined") BizAlert.toast("A network or server error occurred.",
                            "error");
                    } finally {
                        this.isSubmitting = false;
                    }
                },
            };
        }
    </script>
@endsection
