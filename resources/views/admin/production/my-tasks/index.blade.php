@extends ('layouts.admin')

@section ('title', "Today's Tasks - PlantIQ")

@section ('header-title')
    <div class="flex w-full items-center justify-between">
        <div>
            <h1 class="text-base font-bold tracking-tight text-gray-900 sm:text-lg">Today's Plant Care Tasks</h1>
            <p class="mt-0.5 text-xs font-medium text-gray-500">
                {{ now()->format('d M, Y') }} —
                <span class="font-bold text-amber-600">{{ $pendingCount }} pending</span>,
                <span class="font-bold text-emerald-600">{{ $doneCount }} done</span>
            </p>
        </div>
    </div>
@endsection

@section ('content')
    <div x-data="myTasksPage()" x-init="initData()" class="mx-auto w-full max-w-4xl space-y-5">
        {{-- ── Page Header ── --}}
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center md:hidden">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Today's Plant Care Tasks</h2>
                <p class="mt-0.5 text-xs font-medium text-gray-500">
                    {{ now()->format('d M, Y') }} —
                    <span class="font-bold text-amber-600" x-text="`${pendingCount} pending`"
                        >{{ $pendingCount }} pending</span
                    >,
                    <span class="font-bold text-emerald-600" x-text="`${doneCount} done`">{{ $doneCount }} done</span>
                </p>
            </div>
            <div class="flex items-center gap-3"></div>
        </div>

        {{-- ── Banner Messages ── --}}
        <div
            x-show="successMsg"
            x-transition
            x-cloak
            class="flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-xs font-bold text-green-700 shadow-sm sm:text-sm"
        >
            <i data-lucide="check-circle-2" class="h-5 w-5 shrink-0 text-green-600"></i>
            <span x-text="successMsg"></span>
        </div>

        {{-- ── Navigation Tabs ── --}}
        <div class="flex rounded-2xl border border-gray-100 bg-gray-100/80 p-1.5 shadow-inner">
            <button
                type="button"
                @click="activeTab = 'today'"
                class="flex flex-1 items-center justify-center gap-2 rounded-xl text-xs font-bold transition-all sm:text-sm"
                :class="activeTab === 'today'
                    ? 'bg-white text-gray-900 shadow-sm'
                    : 'text-gray-500 hover:text-gray-800'"
            >
                <i data-lucide="check-square" class="h-4 w-4"></i>
                <span>Today's Checklist</span>
                <span
                    class="rounded-full px-2 py-0.5 text-[11px] font-extrabold"
                    :class="pendingCount > 0 ? 'bg-amber-100 text-amber-700' : 'bg-gray-200 text-gray-600'"
                    x-text="pendingCount"
                ></span>
            </button>

            <button
                type="button"
                @click="activeTab = 'history'"
                class="flex flex-1 items-center justify-center gap-2 rounded-xl py-3 text-xs font-bold transition-all sm:text-sm"
                :class="activeTab === 'history'
                    ? 'bg-white text-gray-900 shadow-sm'
                    : 'text-gray-500 hover:text-gray-800'"
            >
                <i data-lucide="history" class="h-4 w-4"></i>
                <span>Past History</span>
            </button>
        </div>

        {{-- ════════════════════════════════════════════════════════════
             TAB 1: TODAY'S CHECKLIST
        ════════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'today'">
            @if ($tasks->isEmpty())
                <div
                    class="flex flex-col items-center justify-center rounded-2xl border border-gray-100 bg-white px-6 py-16 text-center shadow-sm"
                >
                    <div
                        class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-emerald-600"
                    >
                        <i data-lucide="check-circle-2" class="h-8 w-8"></i>
                    </div>
                    <h3 class="mb-1 text-base font-bold text-gray-900 sm:text-lg">All Done For Today!</h3>
                    <p class="max-w-sm text-xs text-gray-500 sm:text-sm">Either no zone is assigned to you, or all scheduled care tasks in your zone are completed.</p>
                </div>
            @else
                {{-- ── Zone Switcher / Filter Pills (Glove-Friendly Touch Targets ~44px height) ── --}}
                @if (isset($assignedZones) && $assignedZones->count() > 1)
                    <div
                        class="[&::-webkit-scrollbar]:hidden mb-5 flex [scrollbar-width:none] items-center gap-2 overflow-x-auto pb-1 [-ms-overflow-style:none]"
                    >
                        {{-- All Zones Button --}}
                        <button
                            type="button"
                            @click="selectedZone = 'all'"
                            class="flex h-11 shrink-0 items-center gap-2 rounded-xl border px-4 text-xs font-bold shadow-sm transition-all active:scale-95 sm:text-sm"
                            :class="selectedZone === 'all'
                                ? 'bg-gray-900 text-white border-gray-900 ring-2 ring-gray-900/20'
                                : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                        >
                            <i data-lucide="globe" class="h-4 w-4"></i>
                            <span>All Zones</span>
                            <span
                                class="rounded-full px-2 py-0.5 text-[10px] font-extrabold"
                                :class="selectedZone === 'all' ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-600'"
                                x-text="totalTasksCount"
                            ></span>
                        </button>

                        {{-- Individual Zone Buttons --}}
                        @foreach ($assignedZones as $z)
                            <button
                                type="button"
                                @click="selectedZone = '{{ $z->name }}'"
                                class="flex h-11 shrink-0 items-center gap-2 rounded-xl border px-4 text-xs font-bold shadow-sm transition-all active:scale-95 sm:text-sm"
                                :class="selectedZone === '{{ $z->name }}'
                                    ? 'bg-brand-600 text-white border-brand-600 ring-2 ring-brand-600/20'
                                    : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                            >
                                <i data-lucide="map-pin" class="h-4 w-4"></i>
                                <span>{{ $z->name }}</span>
                                @if (isset($tasks[$z->name]))
                                    <span
                                        class="rounded-full px-2 py-0.5 text-[10px] font-extrabold"
                                        :class="selectedZone === '{{ $z->name }}' ? 'bg-brand-700 text-white' : 'bg-gray-100 text-gray-600'"
                                    >
                                        {{ count($tasks[$z->name]) }}
                                    </span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif

                @foreach ($tasks as $zoneName => $zoneTasks)
                    <div
                        class="mb-6 space-y-2.5"
                        x-show="selectedZone === 'all' || selectedZone === '{{ $zoneName }}'"
                        x-transition
                    >
                        {{-- Zone Header --}}
                        <div class="mb-2.5 flex items-center justify-between px-1">
                            <div class="flex items-center gap-2">
                                <i data-lucide="map-pin" class="text-brand-600 h-4 w-4"></i>
                                <h2 class="text-xs font-black tracking-wide text-gray-700 uppercase sm:text-sm">
                                    {{ $zoneName }}
                                </h2>
                            </div>
                            <span class="rounded-md bg-gray-100 px-2 py-0.5 text-[11px] font-bold text-gray-500"
                                >{{ count($zoneTasks) }} tasks</span
                            >
                        </div>

                        {{-- Task Cards Container --}}
                        <div
                            class="divide-y divide-gray-50 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm"
                        >
                            @foreach ($zoneTasks as $task)
                                <div
                                    id="task-{{ $task->id }}"
                                    class="flex items-center gap-3 p-4 transition-colors sm:gap-4"
                                    :class="doneIds.includes({{ $task->id }}) ? 'bg-emerald-50/40' : 'hover:bg-gray-50/60'"
                                >
                                    {{-- Glove-Friendly Extra Large Checkbox Button (48px - 56px touch target) --}}
                                    <button
                                        type="button"
                                        @click="handleCheckboxClick({{ $task->id }}, '{{ $task->activity_type->value }}', {{ $task->plant_batch_id }})"
                                        :disabled="busyId === {{ $task->id }}"
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border-2 shadow-sm transition-all active:scale-95 sm:h-14 sm:w-14"
                                        :class="doneIds.includes({{ $task->id }})
                                            ? 'border-emerald-600 bg-emerald-600 text-white ring-4 ring-emerald-100'
                                            : 'border-gray-300 bg-white text-transparent hover:border-emerald-500 hover:bg-emerald-50/30'"
                                    >
                                        <i
                                            data-lucide="check"
                                            class="h-6 w-6 stroke-[3] sm:h-7 sm:w-7"
                                            x-show="doneIds.includes({{ $task->id }})"
                                        ></i>
                                        <i
                                            data-lucide="loader-2"
                                            class="text-brand-600 h-6 w-6 animate-spin"
                                            x-show="busyId === {{ $task->id }}"
                                            x-cloak
                                        ></i>
                                    </button>

                                    {{-- Task Info --}}
                                    <div class="min-w-0 flex-1 space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            {{-- Activity Label Badge --}}
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold"
                                                style="background-color: {{ $activityMeta[$task->activity_type->value]['color']['bg'] ?? '#f3f4f6' }}; color: {{ $activityMeta[$task->activity_type->value]['color']['text'] ?? '#374151' }}"
                                            >
                                                <i
                                                    data-lucide="{{ $task->activity_type->icon() }}"
                                                    class="h-3.5 w-3.5 shrink-0"
                                                ></i>
                                                <span>{{ $task->activity_type->label() }}</span>
                                            </span>

                                            @if ($task->is_required)
                                                <span
                                                    class="rounded-md bg-red-50 px-2 py-0.5 text-[10px] font-bold text-red-600"
                                                    >Required</span
                                                >
                                            @endif

                                            <template x-if="doneIds.includes({{ $task->id }})">
                                                <span
                                                    class="rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800"
                                                    >Done</span
                                                >
                                            </template>
                                        </div>

                                        {{-- Batch & Species Details --}}
                                        <div class="truncate text-xs font-semibold text-gray-900 sm:text-sm">
                                            <span class="font-mono text-gray-500"
                                                >#{{ $task->plantBatch->batch_code ?? $task->plant_batch_id }}</span
                                            >
                                            <span class="mx-1 text-gray-300">•</span>
                                            <span>{{ $task->plantBatch->product->name ?? 'Unknown species' }}</span>
                                        </div>

                                        @if ($task->template?->notes)
                                            <p class="text-xs text-gray-500 italic">Instruction: {{ $task->template->notes }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- ════════════════════════════════════════════════════════════
             TAB 2: PAST HISTORY (Paginated)
        ════════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'history'" x-cloak class="space-y-4">
            @if ($historyTasks->isEmpty())
                <div
                    class="flex flex-col items-center justify-center rounded-2xl border border-gray-100 bg-white px-6 py-16 text-center shadow-sm"
                >
                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-gray-50 text-gray-400">
                        <i data-lucide="history" class="h-7 w-7"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-900">No History Records</h3>
                    <p class="mt-1 text-xs text-gray-500">There are no prior completed or missed tasks in the system for your zone.</p>
                </div>
            @else
                <div
                    class="divide-y divide-gray-50 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm"
                >
                    @foreach ($historyTasks as $historyTask)
                        <div class="flex items-start justify-between gap-3 p-4 text-xs hover:bg-gray-50/50 sm:text-sm">
                            <div class="min-w-0 space-y-1.5">
                                <div class="flex flex-wrap items-center gap-2">
                                    {{-- Date Badge --}}
                                    <span
                                        class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-0.5 font-mono text-xs font-bold text-gray-500"
                                    >
                                        <i data-lucide="calendar" class="h-3 w-3"></i>
                                        {{ $historyTask->due_date ? $historyTask->due_date->format('d M, Y') : '' }}
                                    </span>

                                    {{-- Activity Badge --}}
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-bold"
                                        style="background-color: {{ $activityMeta[$historyTask->activity_type->value]['color']['bg'] ?? '#f3f4f6' }}; color: {{ $activityMeta[$historyTask->activity_type->value]['color']['text'] ?? '#374151' }}"
                                    >
                                        {{ $historyTask->activity_type->label() }}
                                    </span>

                                    {{-- Zone --}}
                                    <span class="rounded bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-600">
                                        {{ $historyTask->zone->name ?? 'Zone' }}
                                    </span>
                                </div>

                                {{-- Batch details --}}
                                <div class="truncate font-semibold text-gray-800">
                                    <span class="font-mono text-gray-500"
                                        >#{{ $historyTask->plantBatch->batch_code ?? $historyTask->plant_batch_id }}</span
                                    >
                                    <span class="mx-1 text-gray-300">•</span>
                                    <span>{{ $historyTask->plantBatch->product->name ?? 'Unknown species' }}</span>
                                </div>

                                {{-- Completed info --}}
                                @if ($historyTask->completedBy || $historyTask->completed_at)
                                    <p class="text-[11px] text-gray-400">
                                        Completed
                                        @if ($historyTask->completed_at) at{{ $historyTask->completed_at->format('h:i A') }} @endif
                                        @if ($historyTask->completedBy) by{{ $historyTask->completedBy->name }} @endif
                                    </p>
                                @endif
                            </div>

                            {{-- Status Tag --}}
                            <div class="shrink-0 pt-0.5">
                                @if ($historyTask->status->value === 'done')
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700"
                                    >
                                        <i data-lucide="check-circle-2" class="h-3.5 w-3.5"></i>
                                        Done
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700"
                                    >
                                        <i data-lucide="clock" class="h-3.5 w-3.5"></i>
                                        Missed
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- History Pagination --}}
                <div class="pt-2">{{ $historyTasks->links() }}</div>
            @endif
        </div>

        {{-- ── Top Summary & Action Bar ── --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                {{-- Progress & Summary --}}
                <div class="flex-1 space-y-2">
                    <div class="flex items-center justify-between text-xs sm:text-sm">
                        <span class="font-bold text-gray-700">Today's Completion Progress</span>
                        <span class="text-brand-600 font-bold" x-text="`${progressPercent}%`">0%</span>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="h-3 w-full overflow-hidden rounded-full bg-gray-100 p-0.5">
                        <div
                            class="bg-brand-600 h-full rounded-full transition-all duration-500 ease-out"
                            :style="`width: ${progressPercent}%`"
                        ></div>
                    </div>
                </div>

                {{-- Report Loss / Report Harvest / Move Batch Action Buttons (Glove-Friendly) --}}
                {{-- Mobile: equal 3-col grid, icon-on-top compact buttons (fits thumb width, no crowding).
                     sm+: reverts to icon+label side-by-side with full text, like a desktop toolbar. --}}
                <div class="grid grid-cols-3 gap-2 sm:flex sm:w-auto sm:shrink-0">
                    <button
                        type="button"
                        @click="openHarvestModal()"
                        class="flex flex-col items-center justify-center gap-1 rounded-xl bg-emerald-600 px-2 py-3 text-white shadow-sm transition-all hover:bg-emerald-700 active:scale-95 sm:flex-row sm:gap-2 sm:px-5"
                    >
                        <i data-lucide="scissors" class="h-5 w-5 shrink-0"></i>
                        <span class="text-[11px] leading-tight font-bold sm:hidden">Harvest</span>
                        <span class="hidden text-sm font-bold sm:inline">Report Harvest</span>
                    </button>
                    <button
                        type="button"
                        @click="openLossModal()"
                        class="flex flex-col items-center justify-center gap-1 rounded-xl bg-red-600 px-2 py-3 text-white shadow-sm transition-all hover:bg-red-700 active:scale-95 sm:flex-row sm:gap-2 sm:px-5"
                    >
                        <i data-lucide="alert-triangle" class="h-5 w-5 shrink-0"></i>
                        <span class="text-[11px] leading-tight font-bold sm:hidden">Loss</span>
                        <span class="hidden text-sm font-bold sm:inline">Report Loss</span>
                    </button>
                    <button
                        type="button"
                        @click="openMoveModal()"
                        class="flex flex-col items-center justify-center gap-1 rounded-xl bg-blue-600 px-2 py-3 text-white shadow-sm transition-all hover:bg-blue-700 active:scale-95 sm:flex-row sm:gap-2 sm:px-5"
                    >
                        <i data-lucide="move" class="h-5 w-5 shrink-0"></i>
                        <span class="text-[11px] leading-tight font-bold sm:hidden">Move</span>
                        <span class="hidden text-sm font-bold sm:inline">Move Batch</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════════
             MODAL: REPORT LOSS (Glove-Friendly Controls)
        ════════════════════════════════════════════════════════════ --}}
        <div
            x-show="lossModalOpen"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-3 sm:p-4"
            @click.self="closeLossModal()"
        >
            <div
                x-show="lossModalOpen"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="max-h-[90vh] w-full max-w-md space-y-4 overflow-y-auto rounded-2xl bg-white p-4 shadow-xl sm:p-6"
            >
                {{-- Modal Header --}}
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-50 text-red-600">
                            <i data-lucide="alert-triangle" class="h-5 w-5"></i>
                        </div>
                        <h3 class="text-base font-bold text-gray-900">Report Plant Loss</h3>
                    </div>
                    <button type="button" @click="closeLossModal()" class="p-1 text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                {{-- Error Banner --}}
                <div
                    x-show="errorMsg"
                    x-cloak
                    class="rounded-xl bg-red-50 px-3.5 py-2.5 text-xs font-semibold text-red-600"
                    x-text="errorMsg"
                ></div>

                {{-- Custom Batch Dropdown (Search-First) --}}
                <div class="relative w-full" @click.outside="batchDropdownOpen = false">
                    <label class="mb-1.5 block text-xs font-bold text-gray-700"
                        >Select Plant Batch <span class="text-red-500">*</span></label
                    >

                    {{-- Selected confirmation chip with Available Qty --}}
                    <div
                        x-show="selectedBatchId && !batchDropdownOpen"
                        x-cloak
                        class="flex items-center justify-between gap-2 rounded-xl border border-red-200 bg-red-50 px-3.5 py-2.5"
                    >
                        <div class="flex flex-col">
                            <span
                                class="truncate text-sm font-bold text-red-700"
                                x-text="
                                    selectedBatch
                                        ? `Batch #${selectedBatch.batch_code} — ${selectedBatch.product_name}`
                                        : ''
                                "
                            ></span>
                            <span
                                class="text-xs font-medium text-red-600/80"
                                x-text="selectedBatch ? `${selectedBatch.current_quantity} plants available` : ''"
                            ></span>
                        </div>
                        <button
                            type="button"
                            @click="
                                if (!lockBatch) {
                                    batchDropdownOpen = true;
                                    $nextTick(() => $refs.batchSearchInput.focus());
                                }
                            "
                            class="shrink-0 text-xs font-semibold text-red-700 underline decoration-dotted hover:text-red-900 disabled:opacity-50"
                            :disabled="lockBatch"
                        >
                            Change
                        </button>
                    </div>

                    {{-- Search input --}}
                    <div x-show="!selectedBatchId || batchDropdownOpen" x-cloak class="relative">
                        <input
                            type="text"
                            x-ref="batchSearchInput"
                            x-model="batchSearchTerm"
                            @focus="batchDropdownOpen = true"
                            :disabled="lockBatch || batchesLoading"
                            placeholder="Click to see recent, or type to search…"
                            autocomplete="off"
                            class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm transition-all outline-none hover:border-gray-300 focus:border-red-500 focus:ring-1 focus:ring-red-500 disabled:cursor-not-allowed disabled:bg-gray-50"
                        />
                        <i
                            data-lucide="loader-2"
                            class="pointer-events-none absolute top-1/2 right-3.5 h-4 w-4 -translate-y-1/2 animate-spin text-gray-400"
                            x-show="batchesLoading"
                            x-cloak
                        ></i>
                        <i
                            data-lucide="search"
                            class="pointer-events-none absolute top-1/2 right-3.5 h-4 w-4 -translate-y-1/2 text-gray-400"
                            x-show="!batchesLoading"
                        ></i>
                    </div>

                    {{-- Dropdown panel --}}
                    <div
                        x-show="batchDropdownOpen"
                        x-cloak
                        x-transition
                        class="absolute z-50 mt-1.5 max-h-72 w-full overflow-y-auto rounded-xl border border-gray-100 bg-white py-1 shadow-xl"
                    >
                        <template x-if="!batchSearchTerm && filteredBatches.length">
                            <p class="px-4 pt-2 pb-1 text-[10px] font-bold tracking-wide text-gray-400 uppercase">Recent</p>
                        </template>

                        <template x-if="!filteredBatches.length">
                            <p class="px-4 py-3 text-xs text-gray-400">No matching batch found.</p>
                        </template>

                        <template x-for="b in filteredBatches" :key="b.id">
                            <button
                                type="button"
                                @click="selectBatch(b.id)"
                                class="flex w-full items-center justify-between px-4 py-2.5 text-left text-xs transition-colors hover:bg-red-50 hover:text-red-700 sm:text-sm"
                                :class="String(selectedBatchId) === String(b.id)
                                    ? 'bg-red-50 text-red-700 font-bold'
                                    : 'text-gray-700'"
                            >
                                <span x-text="`Batch #${b.batch_code} — ${b.product_name}`"></span>
                                <span
                                    class="text-xs font-normal text-gray-400"
                                    x-text="`(${b.current_quantity} available)`"
                                ></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Quantity Lost (Glove-Friendly + / - Tap Buttons) --}}
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-gray-700"
                        >Quantity Lost <span class="text-red-500">*</span></label
                    >
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="quantityLost = Math.max(1, Number(quantityLost || 1) - 1)"
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-lg font-bold text-gray-700 hover:bg-gray-200 active:scale-95"
                        >
                            -
                        </button>
                        <input
                            type="number"
                            min="1"
                            x-model="quantityLost"
                            class="h-11 min-w-0 flex-1 rounded-xl border px-3 py-2 text-center text-base font-bold focus:outline-none"
                            :class="quantityExceeded
                                ? 'border-red-300 focus:border-red-500 text-red-600'
                                : 'border-gray-200 focus:border-red-500'"
                            placeholder="1"
                        />
                        <button
                            type="button"
                            @click="quantityLost = Number(quantityLost || 0) + 1"
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-lg font-bold text-gray-700 hover:bg-gray-200 active:scale-95"
                        >
                            +
                        </button>
                    </div>
                    <p
                        x-show="quantityExceeded"
                        x-cloak
                        class="mt-1 text-xs font-bold text-red-500"
                    >Cannot exceed available quantity</p>
                </div>

                {{-- Custom Reason Dropdown --}}
                <div class="relative w-full" @click.outside="reasonDropdownOpen = false">
                    <label class="mb-1.5 block text-xs font-bold text-gray-700"
                        >Reason for Loss <span class="text-red-500">*</span></label
                    >
                    <button
                        type="button"
                        @click="reasonDropdownOpen = !reasonDropdownOpen"
                        class="flex w-full items-center justify-between rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm transition-all outline-none hover:border-gray-300 focus:border-red-500 focus:ring-1 focus:ring-red-500"
                        :class="reasonDropdownOpen ? 'border-red-500 ring-1 ring-red-500' : ''"
                    >
                        <span
                            class="truncate font-medium"
                            :class="reason ? 'text-gray-900' : 'text-gray-400'"
                            x-text="selectedReasonLabel"
                        ></span>
                        <i
                            data-lucide="chevron-down"
                            class="h-4 w-4 shrink-0 text-gray-400 transition-transform"
                            :class="reasonDropdownOpen ? 'rotate-180' : ''"
                        ></i>
                    </button>

                    <div
                        x-show="reasonDropdownOpen"
                        x-cloak
                        x-transition
                        class="absolute z-50 mt-1.5 max-h-56 w-full overflow-y-auto rounded-xl border border-gray-100 bg-white py-1 shadow-xl"
                    >
                        <template x-for="r in reasonOptions" :key="r.value">
                            <button
                                type="button"
                                @click="selectReason(r.value)"
                                class="flex w-full items-center px-4 py-2.5 text-left text-xs transition-colors hover:bg-red-50 hover:text-red-700 sm:text-sm"
                                :class="reason === r.value ? 'bg-red-50 text-red-700 font-bold' : 'text-gray-700'"
                            >
                                <span x-text="r.label"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="mb-1.5 flex items-center justify-between text-xs font-bold text-gray-700">
                        <span>Notes & Observations <span class="font-normal text-gray-400">(optional)</span></span>
                        <span class="font-normal text-gray-400" x-text="`${notes.length}/1000`"></span>
                    </label>
                    <textarea
                        x-model="notes"
                        maxlength="1000"
                        rows="2"
                        class="w-full resize-none rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:border-red-500 focus:outline-none"
                        placeholder="Condition details or causes observed..."
                    ></textarea>
                </div>

                {{-- Action Buttons --}}
                <div class="flex gap-3 pt-2">
                    <button
                        type="button"
                        @click="closeLossModal()"
                        class="flex-1 rounded-xl border border-gray-200 py-3 text-xs font-bold text-gray-600 hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submitLoss()"
                        :disabled="!canSubmit || submitting"
                        class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-red-600 py-3 text-xs font-bold text-white transition-all hover:bg-red-700 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        <i data-lucide="loader-2" class="h-4 w-4 animate-spin" x-show="submitting" x-cloak></i>
                        <span x-text="submitting ? 'Submitting...' : 'Submit Report'"></span>
                    </button>
                </div>
            </div>
        </div>
        {{-- ════════════════════════════════════════════════════════════
             MODAL: REPORT HARVEST (Glove-Friendly Controls)
        ════════════════════════════════════════════════════════════ --}}
        <div
            x-show="harvestModalOpen"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-3 sm:p-4"
            @click.self="closeHarvestModal()"
        >
            <div
                x-show="harvestModalOpen"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="max-h-[90vh] w-full max-w-md space-y-4 overflow-y-auto rounded-2xl bg-white p-4 shadow-xl sm:p-6"
            >
                {{-- Modal Header --}}
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-600"
                        >
                            <i data-lucide="scissors" class="h-5 w-5"></i>
                        </div>
                        <h3 class="text-base font-bold text-gray-900">Report Harvest</h3>
                    </div>
                    <button type="button" @click="closeHarvestModal()" class="p-1 text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                {{-- Error Banner --}}
                <div
                    x-show="harvestErrorMsg"
                    x-cloak
                    class="rounded-xl bg-red-50 px-3.5 py-2.5 text-xs font-semibold text-red-600"
                    x-text="harvestErrorMsg"
                ></div>

                {{-- Batch Picker — search-first, matches the Plant Details picker pattern --}}
                <div class="relative w-full" @click.outside="harvestBatchDropdownOpen = false">
                    <label class="mb-1.5 block text-xs font-bold text-gray-700"
                        >Select Plant Batch <span class="text-red-500">*</span></label
                    >

                    {{-- Selected confirmation chip --}}
                    <div
                        x-show="selectedHarvestBatchId && !harvestBatchDropdownOpen"
                        x-cloak
                        class="flex items-center justify-between gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2.5"
                    >
                        <div class="flex flex-col">
                            <span
                                class="truncate text-sm font-bold text-emerald-700"
                                x-text="
                                    selectedHarvestBatch
                                        ? `Batch #${selectedHarvestBatch.batch_code} — ${selectedHarvestBatch.product_name}`
                                        : ''
                                "
                            ></span>
                            <span
                                class="text-xs font-medium text-emerald-600/80"
                                x-text="
                                    selectedHarvestBatch
                                        ? `${selectedHarvestBatch.current_quantity} plants available`
                                        : ''
                                "
                            ></span>
                        </div>
                        <button
                            type="button"
                            @click="
                                harvestBatchDropdownOpen = true;
                                $nextTick(() => $refs.harvestBatchSearchInput.focus());
                            "
                            class="shrink-0 text-xs font-semibold text-emerald-700 underline decoration-dotted hover:text-emerald-900"
                        >
                            Change
                        </button>
                    </div>

                    {{-- Search input — the trigger itself, no separate button --}}
                    <div x-show="!selectedHarvestBatchId || harvestBatchDropdownOpen" x-cloak class="relative">
                        <input
                            type="text"
                            x-ref="harvestBatchSearchInput"
                            x-model="harvestBatchSearchTerm"
                            @focus="harvestBatchDropdownOpen = true"
                            :disabled="harvestBatchesLoading"
                            placeholder="Click to see recent, or type to search…"
                            autocomplete="off"
                            class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm transition-all outline-none hover:border-gray-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 disabled:cursor-not-allowed disabled:bg-gray-50"
                        />
                        <i
                            data-lucide="loader-2"
                            class="pointer-events-none absolute top-1/2 right-3.5 h-4 w-4 -translate-y-1/2 animate-spin text-gray-400"
                            x-show="harvestBatchesLoading"
                            x-cloak
                        ></i>
                        <i
                            data-lucide="search"
                            class="pointer-events-none absolute top-1/2 right-3.5 h-4 w-4 -translate-y-1/2 text-gray-400"
                            x-show="!harvestBatchesLoading"
                        ></i>
                    </div>

                    {{-- Dropdown panel --}}
                    <div
                        x-show="harvestBatchDropdownOpen"
                        x-cloak
                        x-transition
                        class="absolute z-50 mt-1.5 max-h-72 w-full overflow-y-auto rounded-xl border border-gray-100 bg-white py-1 shadow-xl"
                    >
                        <template x-if="!harvestBatchSearchTerm && filteredHarvestBatches.length">
                            <p class="px-4 pt-2 pb-1 text-[10px] font-bold tracking-wide text-gray-400 uppercase">Recent</p>
                        </template>

                        <template x-if="!filteredHarvestBatches.length">
                            <p class="px-4 py-3 text-xs text-gray-400">No matching batch found.</p>
                        </template>

                        <template x-for="b in filteredHarvestBatches" :key="b.id">
                            <button
                                type="button"
                                @click="selectHarvestBatch(b.id)"
                                class="flex w-full items-center justify-between px-4 py-2.5 text-left text-xs transition-colors hover:bg-emerald-50 hover:text-emerald-700 sm:text-sm"
                                :class="String(selectedHarvestBatchId) === String(b.id)
                                    ? 'bg-emerald-50 text-emerald-700 font-bold'
                                    : 'text-gray-700'"
                            >
                                <span x-text="`Batch #${b.batch_code} — ${b.product_name}`"></span>
                                <span
                                    class="text-xs font-normal text-gray-400"
                                    x-text="`(${b.current_quantity} available)`"
                                ></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Quantity Harvested (Glove-Friendly + / - Tap Buttons) --}}
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-gray-700"
                        >Quantity Harvested <span class="text-red-500">*</span></label
                    >
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="quantityHarvested = Math.max(1, Number(quantityHarvested || 1) - 1)"
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-lg font-bold text-gray-700 hover:bg-gray-200 active:scale-95"
                        >
                            -
                        </button>
                        <input
                            type="number"
                            min="1"
                            x-model="quantityHarvested"
                            class="h-11 min-w-0 flex-1 rounded-xl border px-3 py-2 text-center text-base font-bold focus:outline-none"
                            :class="harvestQuantityExceeded
                                ? 'border-red-300 focus:border-red-500 text-red-600'
                                : 'border-gray-200 focus:border-emerald-500'"
                            placeholder="1"
                        />
                        <button
                            type="button"
                            @click="quantityHarvested = Number(quantityHarvested || 0) + 1"
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-lg font-bold text-gray-700 hover:bg-gray-200 active:scale-95"
                        >
                            +
                        </button>
                    </div>
                    <p
                        x-show="harvestQuantityExceeded"
                        x-cloak
                        class="mt-1 text-xs font-bold text-red-500"
                    >Cannot exceed available quantity</p>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="mb-1.5 flex items-center justify-between text-xs font-bold text-gray-700">
                        <span>Notes & Observations <span class="font-normal text-gray-400">(optional)</span></span>
                        <span class="font-normal text-gray-400" x-text="`${harvestNotes.length}/1000`"></span>
                    </label>
                    <textarea
                        x-model="harvestNotes"
                        maxlength="1000"
                        rows="2"
                        class="w-full resize-none rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:outline-none"
                        placeholder="Quality, grade or destination details..."
                    ></textarea>
                </div>

                {{-- Action Buttons --}}
                <div class="flex gap-3 pt-2">
                    <button
                        type="button"
                        @click="closeHarvestModal()"
                        class="flex-1 rounded-xl border border-gray-200 py-3 text-xs font-bold text-gray-600 hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submitHarvest()"
                        :disabled="!canSubmitHarvest || harvestSubmitting"
                        class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-emerald-600 py-3 text-xs font-bold text-white transition-all hover:bg-emerald-700 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        <i data-lucide="loader-2" class="h-4 w-4 animate-spin" x-show="harvestSubmitting" x-cloak></i>
                        <span x-text="harvestSubmitting ? 'Submitting...' : 'Submit Report'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════════
             MODAL: MOVE BATCH (Glove-Friendly Controls)
        ════════════════════════════════════════════════════════════ --}}
        <div
            x-show="moveModalOpen"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-3 sm:p-4"
            @click.self="closeMoveModal()"
        >
            <div
                x-show="moveModalOpen"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="max-h-[90vh] w-full max-w-md space-y-4 overflow-y-auto rounded-2xl bg-white p-4 shadow-xl sm:p-6"
            >
                {{-- Modal Header --}}
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                            <i data-lucide="move" class="h-5 w-5"></i>
                        </div>
                        <h3 class="text-base font-bold text-gray-900">Move Batch</h3>
                    </div>
                    <button type="button" @click="closeMoveModal()" class="p-1 text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                {{-- Error Banner --}}
                <div
                    x-show="moveErrorMsg"
                    x-cloak
                    class="rounded-xl bg-red-50 px-3.5 py-2.5 text-xs font-semibold text-red-600"
                    x-text="moveErrorMsg"
                ></div>

                {{-- Custom Batch Dropdown (Search-First) --}}
                <div class="relative w-full" @click.outside="moveBatchDropdownOpen = false">
                    <label class="mb-1.5 block text-xs font-bold text-gray-700"
                        >Select Plant Batch <span class="text-red-500">*</span></label
                    >

                    {{-- Selected confirmation chip --}}
                    <div
                        x-show="selectedMoveBatchId && !moveBatchDropdownOpen"
                        x-cloak
                        class="flex items-center justify-between gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3.5 py-2.5"
                    >
                        <div class="flex flex-col">
                            <span
                                class="truncate text-sm font-bold text-blue-700"
                                x-text="
                                    selectedMoveBatch
                                        ? `Batch #${selectedMoveBatch.batch_code} — ${selectedMoveBatch.product_name}`
                                        : ''
                                "
                            ></span>
                            <span
                                class="text-xs font-medium text-blue-600/80"
                                x-text="
                                    selectedMoveBatch ? `${selectedMoveBatch.current_quantity} plants available` : ''
                                "
                            ></span>
                        </div>
                        <button
                            type="button"
                            @click="
                                moveBatchDropdownOpen = true;
                                $nextTick(() => $refs.moveBatchSearchInput.focus());
                            "
                            class="shrink-0 text-xs font-semibold text-blue-700 underline decoration-dotted hover:text-blue-900"
                        >
                            Change
                        </button>
                    </div>

                    {{-- Search input --}}
                    <div x-show="!selectedMoveBatchId || moveBatchDropdownOpen" x-cloak class="relative">
                        <input
                            type="text"
                            x-ref="moveBatchSearchInput"
                            x-model="moveBatchSearchTerm"
                            @focus="moveBatchDropdownOpen = true"
                            :disabled="moveBatchesLoading"
                            placeholder="Click to see recent, or type to search…"
                            autocomplete="off"
                            class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm transition-all outline-none hover:border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 disabled:cursor-not-allowed disabled:bg-gray-50"
                        />
                        <i
                            data-lucide="loader-2"
                            class="pointer-events-none absolute top-1/2 right-3.5 h-4 w-4 -translate-y-1/2 animate-spin text-gray-400"
                            x-show="moveBatchesLoading"
                            x-cloak
                        ></i>
                        <i
                            data-lucide="search"
                            class="pointer-events-none absolute top-1/2 right-3.5 h-4 w-4 -translate-y-1/2 text-gray-400"
                            x-show="!moveBatchesLoading"
                        ></i>
                    </div>

                    {{-- Dropdown panel --}}
                    <div
                        x-show="moveBatchDropdownOpen"
                        x-cloak
                        x-transition
                        class="absolute z-50 mt-1.5 max-h-72 w-full overflow-y-auto rounded-xl border border-gray-100 bg-white py-1 shadow-xl"
                    >
                        <template x-if="!moveBatchSearchTerm && filteredMoveBatches.length">
                            <p class="px-4 pt-2 pb-1 text-[10px] font-bold tracking-wide text-gray-400 uppercase">Recent</p>
                        </template>
                        <template x-if="!filteredMoveBatches.length">
                            <p class="px-4 py-3 text-xs text-gray-400">No matching batch found.</p>
                        </template>
                        <template x-for="b in filteredMoveBatches" :key="b.id">
                            <button
                                type="button"
                                @click="selectMoveBatch(b.id)"
                                class="flex w-full items-center justify-between px-4 py-2.5 text-left text-xs transition-colors hover:bg-blue-50 hover:text-blue-700 sm:text-sm"
                                :class="String(selectedMoveBatchId) === String(b.id)
                                    ? 'bg-blue-50 text-blue-700 font-bold'
                                    : 'text-gray-700'"
                            >
                                <span x-text="`Batch #${b.batch_code} — ${b.product_name}`"></span>
                                <span
                                    class="text-xs font-normal text-gray-400"
                                    x-text="`(${b.current_quantity} available)`"
                                ></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Current location strip — appears once a batch is picked --}}
                <div
                    x-show="selectedMoveBatch"
                    x-cloak
                    class="flex items-center gap-2 rounded-xl bg-gray-50 px-3.5 py-2.5 text-xs text-gray-600"
                >
                    <i data-lucide="map-pin" class="h-4 w-4 shrink-0 text-gray-400"></i>
                    <span
                        >Currently in:
                        <span
                            class="font-bold text-gray-800"
                            x-text="selectedMoveBatch ? selectedMoveBatch.growing_space_name : ''"
                        ></span
                    ></span>
                </div>

                {{-- Destination Space Dropdown --}}
                <div class="relative w-full" @click.outside="moveSpaceDropdownOpen = false">
                    <label class="mb-1.5 block text-xs font-bold text-gray-700"
                        >Move To <span class="text-red-500">*</span></label
                    >
                    <button
                        type="button"
                        @click="
                            !moveSpacesLoading &&
                                selectedMoveBatchId &&
                                (moveSpaceDropdownOpen = !moveSpaceDropdownOpen)
                        "
                        :disabled="moveSpacesLoading || !selectedMoveBatchId"
                        class="flex w-full items-center justify-between rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm transition-all outline-none hover:border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 disabled:cursor-not-allowed disabled:bg-gray-50"
                        :class="moveSpaceDropdownOpen ? 'border-blue-500 ring-1 ring-blue-500' : ''"
                    >
                        <span
                            class="truncate font-medium"
                            :class="selectedMoveSpaceId ? 'text-gray-900' : 'text-gray-400'"
                            x-text="selectedMoveSpaceLabel"
                        ></span>
                        <i
                            data-lucide="chevron-down"
                            class="h-4 w-4 shrink-0 text-gray-400 transition-transform"
                            :class="moveSpaceDropdownOpen ? 'rotate-180' : ''"
                        ></i>
                    </button>

                    <div
                        x-show="moveSpaceDropdownOpen"
                        x-cloak
                        x-transition
                        class="absolute z-50 mt-1.5 max-h-56 w-full overflow-y-auto rounded-xl border border-gray-100 bg-white py-1 shadow-xl"
                    >
                        <template x-if="availableMoveSpaces.length === 0">
                            <p class="px-4 py-3 text-xs text-gray-400">No other space available in your assigned zones.</p>
                        </template>
                        <template x-for="s in availableMoveSpaces" :key="s.id">
                            <button
                                type="button"
                                @click="selectMoveSpace(s.id)"
                                class="flex w-full items-center justify-between px-4 py-2.5 text-left text-xs transition-colors hover:bg-blue-50 hover:text-blue-700 sm:text-sm"
                                :class="String(selectedMoveSpaceId) === String(s.id)
                                    ? 'bg-blue-50 text-blue-700 font-bold'
                                    : 'text-gray-700'"
                            >
                                <span x-text="s.name"></span>
                                <span class="text-xs font-normal text-gray-400" x-text="s.zone_name"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="mb-1.5 flex items-center justify-between text-xs font-bold text-gray-700">
                        <span>Notes <span class="font-normal text-gray-400">(optional)</span></span>
                        <span class="font-normal text-gray-400" x-text="`${moveNotes.length}/1000`"></span>
                    </label>
                    <textarea
                        x-model="moveNotes"
                        maxlength="1000"
                        rows="2"
                        class="w-full resize-none rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:outline-none"
                        placeholder="Reason for move, condition notes..."
                    ></textarea>
                </div>

                {{-- Action Buttons --}}
                <div class="flex gap-3 pt-2">
                    <button
                        type="button"
                        @click="closeMoveModal()"
                        class="flex-1 rounded-xl border border-gray-200 py-3 text-xs font-bold text-gray-600 hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submitMove()"
                        :disabled="!canSubmitMove || moveSubmitting"
                        class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-blue-600 py-3 text-xs font-bold text-white transition-all hover:bg-blue-700 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        <i data-lucide="loader-2" class="h-4 w-4 animate-spin" x-show="moveSubmitting" x-cloak></i>
                        <span x-text="moveSubmitting ? 'Moving...' : 'Move Batch'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global SPA function binding to guarantee smooth execution across SPA page loads
        window.myTasksPage = function () {
            return {
                activeTab: "today",
                selectedZone: "all",
                doneIds: @json ($tasks->flatten()->where('status', \App\Enums\Production\TaskStatus::Done)->pluck('id')->values()),
                pendingCount: @json ($pendingCount),
                doneCount: @json ($doneCount),
                totalTasksCount: @json ($tasks->flatten()->count()),
                busyId: null,

                // Modal state & dropdown control
                lossModalOpen: false,
                batchDropdownOpen: false,
                batchSearchTerm: "",
                reasonDropdownOpen: false,
                lockBatch: false,
                batches: [],
                batchesLoading: false,
                selectedBatchId: "",
                quantityLost: "1",
                reason: "",
                notes: "",
                submitting: false,
                errorMsg: "",
                successMsg: "",
                successTimer: null,

                // Harvest modal state
                harvestModalOpen: false,
                harvestBatchDropdownOpen: false,
                harvestBatchesLoading: false,
                harvestBatchSearchTerm: "",
                selectedHarvestBatchId: "",
                quantityHarvested: "1",
                harvestNotes: "",
                harvestSubmitting: false,
                harvestErrorMsg: "",

                // Move modal state
                moveModalOpen: false,
                moveBatchDropdownOpen: false,
                moveBatchSearchTerm: "",
                moveSpaceDropdownOpen: false,
                moveBatchesLoading: false,
                moveSpacesLoading: false,
                spaces: [],
                selectedMoveBatchId: "",
                selectedMoveSpaceId: "",
                moveNotes: "",
                moveSubmitting: false,
                moveErrorMsg: "",

                reasonOptions: [
                    { value: "mortality", label: "Natural Mortality" },
                    { value: "disease", label: "Disease / Infection" },
                    { value: "pest", label: "Pest Damage" },
                    { value: "weather", label: "Weather / Climate" },
                    { value: "mechanical", label: "Mechanical / Handling Damage" },
                    { value: "theft", label: "Theft / Missing" },
                    { value: "other", label: "Other" },
                ],

                initData() {
                    const urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.has("history_page")) {
                        this.activeTab = "history";
                    }
                    this.refreshIcons();
                },

                get progressPercent() {
                    const total = Number(this.pendingCount) + Number(this.doneCount);
                    if (total === 0) return 0;
                    return Math.round((Number(this.doneCount) / total) * 100);
                },

                get selectedBatch() {
                    return this.batches.find((b) => String(b.id) === String(this.selectedBatchId)) || null;
                },

                get filteredBatches() {
                    const term = this.batchSearchTerm.trim().toLowerCase();
                    if (!term) return this.batches.slice(0, 5);
                    return this.batches.filter(
                        (b) =>
                            (b.batch_code || "").toLowerCase().includes(term) ||
                            (b.product_name || "").toLowerCase().includes(term),
                    );
                },

                get selectedBatchLabel() {
                    if (this.batchesLoading) return "Loading batches...";
                    if (!this.selectedBatch) return "Select a plant batch";
                    return `Batch #${this.selectedBatch.batch_code} — ${this.selectedBatch.product_name} (${this.selectedBatch.current_quantity} available)`;
                },

                get selectedReasonLabel() {
                    const found = this.reasonOptions.find((r) => r.value === this.reason);
                    return found ? found.label : "Select reason for loss";
                },

                get quantityExceeded() {
                    if (!this.selectedBatch || !this.quantityLost) return false;
                    return Number(this.quantityLost) > Number(this.selectedBatch.current_quantity);
                },

                get canSubmit() {
                    return (
                        this.selectedBatchId &&
                        this.quantityLost &&
                        Number(this.quantityLost) > 0 &&
                        !this.quantityExceeded &&
                        this.reason
                    );
                },

                get selectedHarvestBatch() {
                    return this.batches.find((b) => String(b.id) === String(this.selectedHarvestBatchId)) || null;
                },

                // No search term → show the 5 most recent batches (already
                // recent-first from the backend). With a term, filter by
                // batch code or plant name — client-side since `batches` is
                // already fully loaded once per session.
                // Opening Stock batches are sold directly without a harvest step —
                // excluded here so workers can't report a harvest against them.
                get harvestEligibleBatches() {
                    return this.batches.filter((b) => b.source_type !== "opening_stock");
                },

                get filteredHarvestBatches() {
                    const term = this.harvestBatchSearchTerm.trim().toLowerCase();
                    if (!term) return this.harvestEligibleBatches.slice(0, 5);
                    return this.harvestEligibleBatches.filter(
                        (b) =>
                            (b.batch_code || "").toLowerCase().includes(term) ||
                            (b.product_name || "").toLowerCase().includes(term),
                    );
                },

                get selectedHarvestBatchLabel() {
                    if (this.harvestBatchesLoading) return "Loading batches...";
                    if (!this.selectedHarvestBatch) return "Select a plant batch";
                    return `Batch #${this.selectedHarvestBatch.batch_code} — ${this.selectedHarvestBatch.product_name} (${this.selectedHarvestBatch.current_quantity} available)`;
                },

                get harvestQuantityExceeded() {
                    if (!this.selectedHarvestBatch || !this.quantityHarvested) return false;
                    return Number(this.quantityHarvested) > Number(this.selectedHarvestBatch.current_quantity);
                },

                get canSubmitHarvest() {
                    return (
                        this.selectedHarvestBatchId &&
                        this.quantityHarvested &&
                        Number(this.quantityHarvested) > 0 &&
                        !this.harvestQuantityExceeded
                    );
                },

                get selectedMoveBatch() {
                    return this.batches.find((b) => String(b.id) === String(this.selectedMoveBatchId)) || null;
                },

                get filteredMoveBatches() {
                    const term = this.moveBatchSearchTerm.trim().toLowerCase();
                    if (!term) return this.batches.slice(0, 5);
                    return this.batches.filter(
                        (b) =>
                            (b.batch_code || "").toLowerCase().includes(term) ||
                            (b.product_name || "").toLowerCase().includes(term),
                    );
                },

                get selectedMoveBatchLabel() {
                    if (this.moveBatchesLoading) return "Loading batches...";
                    if (!this.selectedMoveBatch) return "Select a plant batch";
                    return `Batch #${this.selectedMoveBatch.batch_code} — ${this.selectedMoveBatch.product_name}`;
                },

                // Destination list excludes the batch's own current space —
                // moving a batch "into" the space it's already in isn't a move.
                get availableMoveSpaces() {
                    if (!this.selectedMoveBatch) return this.spaces;
                    return this.spaces.filter((s) => String(s.id) !== String(this.selectedMoveBatch.growing_space_id));
                },

                get selectedMoveSpace() {
                    return this.spaces.find((s) => String(s.id) === String(this.selectedMoveSpaceId)) || null;
                },

                get selectedMoveSpaceLabel() {
                    if (this.moveSpacesLoading) return "Loading spaces...";
                    if (!this.selectedMoveSpace) return "Select destination space";
                    return `${this.selectedMoveSpace.name} (${this.selectedMoveSpace.zone_name})`;
                },

                get canSubmitMove() {
                    return this.selectedMoveBatchId && this.selectedMoveSpaceId;
                },

                selectBatch(id) {
                    this.selectedBatchId = String(id);
                    this.batchDropdownOpen = false;
                    this.batchSearchTerm = "";
                },

                selectReason(val) {
                    this.reason = val;
                    this.reasonDropdownOpen = false;
                },

                toggleTask(taskId) {
                    if (this.busyId) return;
                    this.busyId = taskId;

                    const isDone = this.doneIds.includes(taskId);

                    fetch(`/admin/production/my-tasks/${taskId}/toggle`, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ?? "",
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                        body: JSON.stringify({}),
                    })
                        .then((r) => r.json())
                        .then((res) => {
                            this.busyId = null;
                            if (res.success) {
                                if (isDone) {
                                    this.doneIds = this.doneIds.filter((id) => id !== taskId);
                                    this.doneCount = Math.max(0, this.doneCount - 1);
                                    this.pendingCount++;
                                } else {
                                    this.doneIds.push(taskId);
                                    this.pendingCount = Math.max(0, this.pendingCount - 1);
                                    this.doneCount++;
                                }
                                this.refreshIcons();
                            } else {
                                alert(res.message);
                            }
                        })
                        .catch(() => {
                            this.busyId = null;
                            alert("Something went wrong, please try again.");
                        });
                },

                handleCheckboxClick(taskId, activityType, plantBatchId) {
                    if (!this.doneIds.includes(taskId) && activityType === "dead_check") {
                        this.openLossModalForTask(taskId, plantBatchId);
                        return;
                    }
                    this.toggleTask(taskId);
                },

                openLossModal() {
                    this.lossModalOpen = true;
                    this.errorMsg = "";
                    this.lockBatch = false;
                    if (this.batches.length === 0) this.fetchBatches();
                    this.refreshIcons();
                },

                openLossModalForTask(taskId, plantBatchId) {
                    this.lossModalOpen = true;
                    this.errorMsg = "";
                    this.lockBatch = true;
                    this.selectedBatchId = String(plantBatchId);
                    if (!this.reason) this.reason = "mortality";
                    if (this.batches.length === 0) this.fetchBatches();
                    this.refreshIcons();
                },

                closeLossModal() {
                    this.lossModalOpen = false;
                    this.selectedBatchId = "";
                    this.batchSearchTerm = "";
                    this.quantityLost = "1";
                    this.reason = "";
                    this.notes = "";
                    this.errorMsg = "";
                    this.lockBatch = false;
                },

                fetchBatches() {
                    this.batchesLoading = true;
                    fetch(`/admin/production/my-tasks/batches`, {
                        headers: {
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    })
                        .then((r) => r.json())
                        .then((res) => {
                            this.batchesLoading = false;
                            if (res.success) {
                                this.batches = res.batches || [];
                            } else {
                                this.errorMsg = "Failed to load batches.";
                            }
                        })
                        .catch(() => {
                            this.batchesLoading = false;
                            this.errorMsg = "Failed to load batches, please try again.";
                        });
                },

                submitLoss() {
                    if (!this.canSubmit || this.submitting) return;
                    this.submitting = true;
                    this.errorMsg = "";

                    fetch(`/admin/production/my-tasks/losses`, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ?? "",
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                        body: JSON.stringify({
                            plant_batch_id: this.selectedBatchId,
                            quantity_lost: this.quantityLost,
                            reason: this.reason,
                            notes: this.notes || null,
                        }),
                    })
                        .then((r) => r.json())
                        .then((res) => {
                            this.submitting = false;
                            if (res.success) {
                                this.closeLossModal();
                                this.successMsg = res.message || "Loss recorded successfully.";

                                if (res.completed_task_id && !this.doneIds.includes(res.completed_task_id)) {
                                    this.doneIds.push(res.completed_task_id);
                                    this.pendingCount = Math.max(0, this.pendingCount - 1);
                                    this.doneCount++;
                                }

                                clearTimeout(this.successTimer);
                                this.successTimer = setTimeout(() => (this.successMsg = ""), 3500);
                                this.refreshIcons();
                            } else {
                                this.errorMsg = res.message || "Something went wrong, please try again.";
                            }
                        })
                        .catch(() => {
                            this.submitting = false;
                            this.errorMsg = "Something went wrong, please try again.";
                        });
                },

                refreshIcons() {
                    this.$nextTick(() => {
                        if (typeof lucide !== "undefined") {
                            lucide.createIcons();
                        }
                    });
                },

                selectHarvestBatch(id) {
                    this.selectedHarvestBatchId = String(id);
                    this.harvestBatchDropdownOpen = false;
                    this.harvestBatchSearchTerm = "";
                },

                openHarvestModal() {
                    this.harvestModalOpen = true;
                    this.harvestErrorMsg = "";
                    // Reuses the same `batches` list fetched for the Loss modal
                    if (this.batches.length === 0) this.fetchBatches();
                    this.refreshIcons();
                },

                closeHarvestModal() {
                    this.harvestModalOpen = false;
                    this.selectedHarvestBatchId = "";
                    this.harvestBatchSearchTerm = "";
                    this.quantityHarvested = "1";
                    this.harvestNotes = "";
                    this.harvestErrorMsg = "";
                },

                submitHarvest() {
                    if (!this.canSubmitHarvest || this.harvestSubmitting) return;
                    this.harvestSubmitting = true;
                    this.harvestErrorMsg = "";

                    fetch(`/admin/production/my-tasks/harvests`, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ?? "",
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                        body: JSON.stringify({
                            plant_batch_id: this.selectedHarvestBatchId,
                            quantity_harvested: this.quantityHarvested,
                            notes: this.harvestNotes || null,
                        }),
                    })
                        .then((r) => r.json())
                        .then((res) => {
                            this.harvestSubmitting = false;
                            if (res.success) {
                                this.closeHarvestModal();
                                this.successMsg = res.message || "Harvest recorded successfully.";
                                clearTimeout(this.successTimer);
                                this.successTimer = setTimeout(() => (this.successMsg = ""), 3500);
                                this.refreshIcons();
                            } else {
                                this.harvestErrorMsg = res.message || "Something went wrong, please try again.";
                            }
                        })
                        .catch(() => {
                            this.harvestSubmitting = false;
                            this.harvestErrorMsg = "Something went wrong, please try again.";
                        });
                },

                selectMoveBatch(id) {
                    this.selectedMoveBatchId = String(id);
                    this.selectedMoveSpaceId = ""; // destination choices depend on the batch's current space
                    this.moveBatchDropdownOpen = false;
                    this.moveBatchSearchTerm = "";
                    if (this.spaces.length === 0) this.fetchSpaces();
                },

                selectMoveSpace(id) {
                    this.selectedMoveSpaceId = String(id);
                    this.moveSpaceDropdownOpen = false;
                },

                openMoveModal() {
                    this.moveModalOpen = true;
                    this.moveErrorMsg = "";
                    if (this.batches.length === 0) this.fetchBatches();
                    if (this.spaces.length === 0) this.fetchSpaces();
                    this.refreshIcons();
                },

                closeMoveModal() {
                    this.moveModalOpen = false;
                    this.selectedMoveBatchId = "";
                    this.moveBatchSearchTerm = "";
                    this.selectedMoveSpaceId = "";
                    this.moveNotes = "";
                    this.moveErrorMsg = "";
                },

                fetchSpaces() {
                    this.moveSpacesLoading = true;
                    fetch(`/admin/production/my-tasks/spaces`, {
                        headers: {
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    })
                        .then((r) => r.json())
                        .then((res) => {
                            this.moveSpacesLoading = false;
                            if (res.success) {
                                this.spaces = res.spaces || [];
                            } else {
                                this.moveErrorMsg = "Failed to load destination spaces.";
                            }
                        })
                        .catch(() => {
                            this.moveSpacesLoading = false;
                            this.moveErrorMsg = "Failed to load destination spaces, please try again.";
                        });
                },

                submitMove() {
                    if (!this.canSubmitMove || this.moveSubmitting) return;
                    this.moveSubmitting = true;
                    this.moveErrorMsg = "";

                    fetch(`/admin/production/my-tasks/move`, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ?? "",
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                        body: JSON.stringify({
                            plant_batch_id: this.selectedMoveBatchId,
                            growing_space_id: this.selectedMoveSpaceId,
                            notes: this.moveNotes || null,
                        }),
                    })
                        .then((r) => r.json())
                        .then((res) => {
                            this.moveSubmitting = false;
                            if (res.success) {
                                // Reflect the new location immediately in the local batches
                                // list so re-opening the modal shows the updated space
                                // without a fresh fetch.
                                const moved = this.batches.find((b) => String(b.id) === String(this.selectedMoveBatchId));
                                if (moved && this.selectedMoveSpace) {
                                    moved.growing_space_id = this.selectedMoveSpace.id;
                                    moved.growing_space_name = this.selectedMoveSpace.name;
                                }

                                this.closeMoveModal();
                                this.successMsg = res.message || "Batch moved successfully.";
                                clearTimeout(this.successTimer);
                                this.successTimer = setTimeout(() => (this.successMsg = ""), 3500);
                                this.refreshIcons();
                            } else {
                                this.moveErrorMsg = res.message || "Something went wrong, please try again.";
                            }
                        })
                        .catch(() => {
                            this.moveSubmitting = false;
                            this.moveErrorMsg = "Something went wrong, please try again.";
                        });
                },
            };
        };
    </script>
@endsection
