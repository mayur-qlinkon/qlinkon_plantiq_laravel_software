@extends ('layouts.admin')

@section ('title', $plantBatch->batch_code . ' — Plant Batch')

@push ('styles')
    <style>
        /* Custom animations */
        @keyframes pulse-slow {
            0%,
            100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }
        .animate-pulse-slow {
            animation: pulse-slow 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes slideUp {
            from {
                transform: translateY(10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        .animate-slide-up {
            animation: slideUp 0.3s ease-out;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }

        /* Print styles */
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
            }
        }
    </style>
@endpush

@section ('header-title')
    <div class="flex w-full items-center justify-between">
        <div class="flex items-center gap-3">
            <div>
                <h1 class="text-lg font-bold tracking-tight text-gray-900">{{ $plantBatch->batch_code }}</h1>
                <p class="text-[12px] font-medium text-gray-500">
                    {{ $plantBatch->product->name ?? '—' }} &middot; {{ $plantBatch->batch_start_datetime?->format('d M, Y') }}
                </p>
            </div>
            <a
                href="{{ route('admin.production.plant-batches.index') }}"
                class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-600 shadow-sm transition-all hover:bg-gray-50"
            >
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Back
            </a>
        </div>
    </div>
@endsection

@section ('content')
    <div class="mx-auto w-full" x-data="batchDetailManager()">
        {{-- Flash messages --}}
        @if (session('success'))
            <div
                class="animate-slide-up mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3.5 text-sm font-semibold text-emerald-800 shadow-sm"
            >
                <i data-lucide="check-circle" class="h-5 w-5 shrink-0 text-emerald-500"></i>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div
                class="animate-slide-up mb-6 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-3.5 text-sm font-semibold text-red-800 shadow-sm"
            >
                <i data-lucide="alert-circle" class="h-5 w-5 shrink-0 text-red-500"></i>
                {{ session('error') }}
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════════════
             STATUS TRANSITION CARD
        ══════════════════════════════════════════════════════════ --}}
        <div
            class="animate-fade-in no-print mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
        >
                <div class="p-5 sm:p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">Batch Status</h3>
                            <p class="mt-1 text-xs text-gray-500">Transition the batch to its next lifecycle stage</p>
                        </div>
                        <div class="flex flex-wrap gap-2" x-data="batchStatusManager()">
                            {{-- Header actions --}}
                            
                                <a href="{{ route('admin.production.plant-batches.pdf', $plantBatch) }}" target="_blank"
                                class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-600 shadow-sm transition-all hover:bg-gray-50 hover:text-gray-900"
                            >
                                <i data-lucide="download" class="h-4 w-4"></i>
                                Download PDF
                            </a>
                            @if ($plantBatch->source_type === 'opening_stock')
                                <a
                                    href="{{ route('admin.production.plant-batches.edit', $plantBatch) }}"
                                    class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-bold text-indigo-700 shadow-sm transition-all hover:bg-indigo-100"
                                >
                                    <i data-lucide="pencil" class="h-4 w-4"></i>
                                    Edit
                                </a>
                            @endif
                            @foreach ($allowedTransitions ?? [] as $transition)
                                <button
                                    type="button"
                                    @click="updateStatus('{{ $transition['value'] }}', '{{ $transition['label'] }}')"
                                    :disabled="loading"
                                    class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-bold shadow-sm transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed
                                            {{ $transition['value'] === 'cancelled'
                                                ? 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100'
                                                : 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100' }}"
                                >
                                    <i data-lucide="loader-2" class="h-4 w-4 animate-spin" x-show="loading" x-cloak></i>
                                    @if ($transition['value'] === 'closed')
                                        <i data-lucide="check-circle" class="h-4 w-4" x-show="!loading"></i>
                                    @elseif ($transition['value'] === 'cancelled')
                                        <i data-lucide="x-circle" class="h-4 w-4" x-show="!loading"></i>
                                    @endif
                                    {{ $transition['label'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             MAIN GRID — Left: Details | Right: Sidebar
        ══════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- ── LEFT COLUMN (2/3 width) ───────────────────────── --}}
            <div class="space-y-6 lg:col-span-2">
                {{-- Batch Core Details Card --}}
                <div
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md"
                >
                    <div
                        class="flex items-center gap-3 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white px-6 py-4"
                    >
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
                            <i data-lucide="info" class="h-4 w-4"></i>
                        </div>
                        <h2 class="text-sm font-bold text-gray-900">Batch Details</h2>
                        <span class="ml-auto text-xs font-medium text-gray-400">ID: #{{ $plantBatch->id }}</span>
                    </div>

                    <div class="grid grid-cols-1 gap-px bg-gray-100 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="bg-white p-5 transition-colors hover:bg-gray-50/50">
                            <p class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase">Batch Code</p>
                            <p class="flex items-center gap-2 text-sm font-bold text-gray-900">
                                <i data-lucide="hash" class="h-3.5 w-3.5 text-indigo-400"></i>
                                {{ $plantBatch->batch_code }}
                            </p>
                        </div>
                        <div class="bg-white p-5 transition-colors hover:bg-gray-50/50">
                            <p class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase">Plant</p>
                            <p class="text-sm font-bold text-gray-900">{{ $plantBatch->product->name ?? '—' }}</p>
                        </div>
                        <div class="bg-white p-5 transition-colors hover:bg-gray-50/50">
                            <p class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase">Created By</p>
                            <p class="text-sm font-bold text-gray-900">{{ $plantBatch->createdBy->name ?? '—' }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ $plantBatch->createdBy->role ?? 'Staff' }}</p>
                        </div>

                        <div class="bg-white p-5 transition-colors hover:bg-gray-50/50">
                            <p class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase">Batch Start</p>
                            <p class="text-sm font-bold text-gray-900">
                                {{ $plantBatch->batch_start_datetime?->format('d M, Y') ?? '—' }}
                            </p>
                            <p class="mt-0.5 text-xs text-gray-500">
                                {{ $plantBatch->batch_start_datetime?->format('h:i A') ?? '' }}
                            </p>
                        </div>

                        @if ($plantBatch->source_type === 'production_plan' && $plantBatch->productionPlanItem?->target_date)
                            <div class="bg-white p-5 transition-colors hover:bg-gray-50/50">
                                <p class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase">Plan Target Date</p>
                                <p class="text-sm font-bold text-gray-900">
                                    {{ $plantBatch->productionPlanItem->target_date->format('d M, Y') }}
                                </p>
                            </div>
                        @endif
                        <div class="bg-white p-5 transition-colors hover:bg-gray-50/50">
                            <p class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase">Batch End</p>
                            <p class="text-sm font-bold text-gray-900">
                                {{ $plantBatch->batch_end_datetime?->format('d M, Y') ?? '—' }}
                            </p>
                            <p class="mt-0.5 text-xs text-gray-500">
                                {{ $plantBatch->batch_end_datetime?->format('h:i A') ?? '' }}
                            </p>
                        </div>
                    </div>

                    @if ($plantBatch->notes)
                        <div class="border-t border-gray-100 bg-amber-50/30 px-6 py-5">
                            <div class="flex items-start gap-3">
                                <i data-lucide="sticky-note" class="mt-0.5 h-4 w-4 shrink-0 text-amber-500"></i>
                                <div>
                                    <p class="mb-1.5 text-xs font-bold tracking-wider text-amber-600 uppercase">Notes</p>
                                    <p class="text-sm leading-relaxed font-medium text-gray-700">{{ $plantBatch->notes }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Quantity Card --}}
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div
                        class="flex items-center gap-3 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-white px-6 py-4"
                    >
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600"
                        >
                            <i data-lucide="layers" class="h-4 w-4"></i>
                        </div>
                        <h2 class="text-sm font-bold text-gray-900">Quantity Tracking</h2>
                        @if (!$plantBatch->isQuantityIntact())
                            <span
                                class="ml-auto inline-flex items-center gap-1.5 rounded-lg bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700"
                            >
                                <i data-lucide="alert-triangle" class="h-3 w-3"></i>
                                Changed -{{ number_format($plantBatch->initial_quantity - $plantBatch->current_quantity) }}
                            </span>
                        @else
                            <span
                                class="ml-auto inline-flex items-center gap-1.5 rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700"
                            >
                                <i data-lucide="check-circle" class="h-3 w-3"></i>
                                Intact
                            </span>
                        @endif
                    </div>

                    <div class="p-6">
                        @php
                            $lost = $plantBatch->initial_quantity - $plantBatch->current_quantity;
                            $pct  = $plantBatch->initial_quantity > 0
                                ? round(($plantBatch->current_quantity / $plantBatch->initial_quantity) * 100)
                                : 0;
                        @endphp

                        <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div class="rounded-xl bg-gray-50 p-4">
                                <p class="mb-1 text-xs font-bold text-gray-400 uppercase">Initial</p>
                                <p class="text-2xl font-black text-gray-400">{{ number_format($plantBatch->initial_quantity) }}</p>
                            </div>
                            <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-4">
                                <p class="mb-1 text-xs font-bold text-emerald-600 uppercase">Current (Live)</p>
                                <p class="text-2xl font-black text-emerald-700">{{ number_format($plantBatch->current_quantity) }}</p>
                            </div>
                            @if ($lost > 0)
                                <div class="rounded-xl border border-red-100 bg-red-50 p-4">
                                    <p class="mb-1 text-xs font-bold text-red-600 uppercase">Reduced By</p>
                                    <p class="text-2xl font-black text-red-500">-{{ number_format($lost) }}</p>
                                </div>
                            @else
                                <div class="rounded-xl bg-gray-50 p-4">
                                    <p class="mb-1 text-xs font-bold text-gray-400 uppercase">Reduced By</p>
                                    <p class="text-2xl font-black text-gray-300">—</p>
                                </div>
                            @endif
                            <div class="rounded-xl bg-indigo-50 p-4">
                                <p class="mb-1 text-xs font-bold text-indigo-600 uppercase">Remaining</p>
                                <p class="text-2xl font-black text-indigo-700">{{ $pct }}%</p>
                            </div>
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <span class="text-xs font-bold text-gray-500">Batch Health</span>
                                <span
                                    class="text-xs font-bold {{ $pct > 60 ? 'text-emerald-600' : ($pct > 30 ? 'text-amber-600' : 'text-red-600') }}"
                                >
                                    {{ $pct }}% Remaining
                                </span>
                            </div>
                            <div class="h-3 w-full overflow-hidden rounded-full bg-gray-100">
                                <div
                                    class="h-full rounded-full transition-all duration-700 
                                    {{ $pct > 60 ? 'bg-gradient-to-r from-emerald-500 to-emerald-400' : ($pct > 30 ? 'bg-gradient-to-r from-amber-400 to-amber-300' : 'bg-gradient-to-r from-red-500 to-red-400') }}"
                                    style="width: {{ $pct }}%"
                                ></div>
                            </div>
                            <p class="mt-3 text-xs font-medium text-gray-400">
                                <i data-lucide="info" class="mr-1 inline h-3 w-3"></i>
                                Initial quantity is permanent. Current quantity updates via Loss or Harvest records.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Source Card --}}
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center gap-3 border-b border-gray-100 px-6 py-4">
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-purple-100 text-purple-600">
                            <i data-lucide="git-merge" class="h-4 w-4"></i>
                        </div>
                        <h2 class="text-sm font-bold text-gray-900">Source Document</h2>
                    </div>

                    <div class="p-6">
                        @if ($plantBatch->source_type === 'production_plan' && $plantBatch->source_document)
                            <div class="flex items-start gap-4">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-600"
                                >
                                    <i data-lucide="clipboard-list" class="h-6 w-6"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-gray-900">{{ $plantBatch->source_document->title }}</p>
                                    <p class="mt-1 text-xs font-medium text-gray-500">Production Plan</p>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @if ($plantBatch->source_document->purpose)
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-purple-50 px-3 py-1.5 text-xs font-semibold text-purple-700"
                                            >
                                                <i data-lucide="target" class="h-3 w-3"></i>
                                                {{ str($plantBatch->source_document->purpose)->headline() }}
                                            </span>
                                        @endif
                                        @if ($plantBatch->source_document->status)
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-700"
                                            >
                                                <i data-lucide="activity" class="h-3 w-3"></i>
                                                {{ str($plantBatch->source_document->status)->headline() }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <a
                                    href="{{ route('admin.production.plans.index') }}"
                                    class="shrink-0 text-sm font-bold text-indigo-600 transition-colors hover:text-indigo-700 hover:underline"
                                >
                                    View Plan →
                                </a>
                            </div>

                        @elseif ($plantBatch->source_type === 'purchase' && $plantBatch->source_document)
                            <div class="flex items-start gap-4">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600"
                                >
                                    <i data-lucide="shopping-cart" class="h-6 w-6"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-gray-900">{{ $plantBatch->source_document->purchase_number }}</p>
                                    <p class="mt-1 text-xs font-medium text-gray-500">
                                        Purchase Order • {{ $plantBatch->source_document->purchase_date?->format('d M, Y') }}
                                    </p>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @if ($plantBatch->source_document->supplier_invoice_number)
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700"
                                            >
                                                <i data-lucide="file-text" class="h-3 w-3"></i>
                                                Invoice: {{ $plantBatch->source_document->supplier_invoice_number }}
                                            </span>
                                        @endif
                                        @if ($plantBatch->source_document->status)
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-700"
                                            >
                                                <i data-lucide="activity" class="h-3 w-3"></i>
                                                {{ str($plantBatch->source_document->status)->headline() }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <a
                                    href="#"
                                    class="shrink-0 text-sm font-bold text-indigo-600 transition-colors hover:text-indigo-700 hover:underline"
                                >
                                    View PO →
                                </a>
                            </div>

                        @else
                            <div class="flex items-start gap-4">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-500"
                                >
                                    <i data-lucide="git-branch" class="h-6 w-6"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900">{{ $plantBatch->source_type_label }}</p>
                                    @if ($plantBatch->source_reference_id)
                                        <p class="mt-1 text-xs font-medium text-gray-500">
                                            Line Item ID:
                                            <span
                                                class="font-bold text-gray-700"
                                                >{{ $plantBatch->source_reference_id }}</span
                                            >
                                        </p>
                                    @else
                                        <p class="mt-1 text-xs font-medium text-gray-400">No linked source document</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ══ Placement & Occupancy ════════════════════════════ --}}
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div
                        class="flex items-center gap-3 border-b border-gray-100 bg-gradient-to-r from-amber-50 to-white px-6 py-4"
                    >
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                            <i data-lucide="map-pin" class="h-4 w-4"></i>
                        </div>
                        <h2 class="text-sm font-bold text-gray-900">Placement & Occupancy</h2>
                        <span
                            class="ml-auto inline-flex items-center gap-1.5 rounded-lg bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700"
                        >
                            {{ $placements->total() }} {{ Str::plural('move', $placements->total()) }}
                        </span>
                    </div>

                    {{-- Current location highlight --}}
                    <div class="border-b border-gray-100 p-5">
                        @php $current = $currentPlacement; @endphp
                        @if ($current)
                            <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50/50 p-4">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600"
                                >
                                    <i data-lucide="layout-grid" class="h-5 w-5"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-bold tracking-wide text-amber-600 uppercase">Currently Placed</p>
                                    <p class="mt-0.5 truncate text-sm font-bold text-gray-900">
                                        {{ $current->growingSpace->site->name ?? '—' }}
                                        <span class="mx-1 text-gray-300">→</span>
                                        {{ $current->growingSpace->zone->name ?? '—' }}
                                        <span class="mx-1 text-gray-300">→</span>
                                        {{ $current->growingSpace->name ?? '—' }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-gray-500">
                                        Since {{ $current->placed_at->format('d M, Y') }} &middot; {{ $current->placed_at->diffForHumans(null, true) }} ago
                                        @if ($current->placedBy)
                                            &middot; by {{ $current->placedBy->name }}
                                        @endif
                                    </p>

                                    @php $zoneEmployees = $current->growingSpace->zone->assignments ?? collect(); @endphp
                                    <div class="mt-3 border-t border-amber-200/60 pt-3">
                                        <p class="mb-1.5 text-[11px] font-bold tracking-wide text-amber-600 uppercase">Zone Staff ({{ $zoneEmployees->count() }})</p>
                                        @if ($zoneEmployees->isEmpty())
                                            <p class="text-xs font-medium text-gray-400">No employees assigned to this zone.</p>
                                        @else
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($zoneEmployees as $assignment)
                                                    <span
                                                        class="inline-flex items-center gap-1.5 rounded-lg bg-white px-2.5 py-1 text-xs font-semibold text-gray-700 shadow-sm"
                                                    >
                                                        <i data-lucide="user" class="h-3 w-3 text-amber-500"></i>
                                                        {{ $assignment->employee->full_name ?: '—' }}
                                                        @if ($assignment->employee->designation)
                                                            <span class="text-gray-400"
                                                                >&middot; {{ $assignment->employee->designation->name }}</span
                                                            >
                                                        @endif
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            <div
                                class="flex items-center gap-3 rounded-xl border border-dashed border-gray-200 bg-gray-50 p-4"
                            >
                                <i data-lucide="alert-circle" class="h-5 w-5 shrink-0 text-gray-400"></i>
                                <p class="text-sm font-medium text-gray-500">This batch is not currently placed in any growing space.</p>
                            </div>
                        @endif
                    </div>

                    {{-- Movement history --}}
                    <div class="px-6 py-4">
                        <p class="mb-3 text-xs font-bold tracking-wider text-gray-400 uppercase">Movement History</p>

                        @if ($placements->isEmpty())
                            <p class="py-4 text-center text-sm font-medium text-gray-400">No placement history yet.</p>
                        @else
                            <div class="divide-y divide-gray-50">
                                @foreach ($placements as $placement)
                                    <div class="flex items-center justify-between gap-3 py-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-gray-900">
                                                {{ $placement->growingSpace->site->name ?? '—' }}
                                                <span class="mx-1 text-gray-300">→</span>
                                                {{ $placement->growingSpace->zone->name ?? '—' }}
                                                <span class="mx-1 text-gray-300">→</span>
                                                {{ $placement->growingSpace->name ?? '—' }}
                                            </p>
                                            <p class="mt-0.5 text-xs text-gray-500">
                                                {{ $placement->placed_at->format('d M, Y') }} → {{ $placement->ended_at?->format('d M, Y') ?? 'Active now' }}
                                                @if ($placement->placedBy)
                                                    &middot; by {{ $placement->placedBy->name }}
                                                @endif
                                            </p>
                                        </div>
                                        @if (is_null($placement->ended_at))
                                            <span
                                                class="shrink-0 rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700"
                                                >ACTIVE</span
                                            >
                                        @else
                                            <span
                                                class="shrink-0 rounded-md bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-500"
                                            >
                                                {{ (int) $placement->placed_at->diffInDays($placement->ended_at ?? now()) }}d
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            @if ($placements->hasPages())
                                <div class="mt-2 border-t border-gray-100 pt-3">{{ $placements->links() }}</div>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- ══ Task Completion ═══════════════════════════════════ --}}
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div
                        class="flex items-center gap-3 border-b border-gray-100 bg-gradient-to-r from-sky-50 to-white px-6 py-4"
                    >
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-sky-100 text-sky-600">
                            <i data-lucide="list-checks" class="h-4 w-4"></i>
                        </div>
                        <h2 class="text-sm font-bold text-gray-900">Task Completion</h2>
                        @if ($taskStats['missed'] > 0)
                            <span
                                class="ml-auto inline-flex items-center gap-1.5 rounded-lg bg-red-100 px-2.5 py-1 text-xs font-bold text-red-700"
                            >
                                {{ $taskStats['missed'] }} missed
                            </span>
                        @endif
                    </div>

                    <div class="px-6 py-4">
                        {{-- Stat cards --}}
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="rounded-xl bg-gray-50 p-3">
                                <p class="text-xs font-medium text-gray-500">Today Total</p>
                                <p class="mt-1 text-lg font-bold text-gray-900">{{ $taskStats['total'] }}</p>
                            </div>
                            <div class="rounded-xl bg-emerald-50 p-3">
                                <p class="text-xs font-medium text-emerald-700">Completed</p>
                                <p class="mt-1 text-lg font-bold text-emerald-700">{{ $taskStats['completed'] }}</p>
                            </div>
                            <div class="rounded-xl bg-red-50 p-3">
                                <p class="text-xs font-medium text-red-700">Missed</p>
                                <p class="mt-1 text-lg font-bold text-red-700">{{ $taskStats['missed'] }}</p>
                            </div>
                            <div class="rounded-xl bg-amber-50 p-3">
                                <p class="text-xs font-medium text-amber-700">Pending</p>
                                <p class="mt-1 text-lg font-bold text-amber-700">{{ $taskStats['pending'] }}</p>
                            </div>
                        </div>

                        {{-- 7-day trend strip --}}
                        <p class="mt-4 mb-1.5 text-xs font-medium text-gray-400">Last 7 Days</p>
                        <div class="grid grid-cols-7 gap-2">
                            @foreach ($taskWeek as $day)
                                @php
                                    $stripClass = match ($day['state']) {
                                        'done' => 'bg-emerald-400',
                                        'missed' => $day['isToday'] ? 'bg-red-50 border-2 border-red-400' : 'bg-red-400',
                                        'pending' => $day['isToday'] ? 'bg-amber-50 border-2 border-amber-400' : 'bg-amber-400',
                                        default => 'bg-gray-100',
                                    };
                                @endphp
                                <div class="text-center">
                                    <div class="h-7 rounded-md {{ $stripClass }}"></div>
                                    <span
                                        class="text-[11px] {{ $day['isToday'] ? 'font-bold text-gray-700' : 'text-gray-400' }}"
                                    >
                                        {{ $day['isToday'] ? 'Today' : $day['label'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Open tasks: due today + still-pending overdue --}}
                    <div class="border-t border-gray-100">
                        @if ($openTasks->isEmpty())
                            <div class="py-6 text-center">
                                <p class="text-sm font-medium text-gray-400">No tasks due right now.</p>
                            </div>
                        @else
                            @foreach ($openTasks as $task)
                                @php
                                    $type = $task->activity_type;
                                    $isMissed = $task->status === \App\Enums\Production\TaskStatus::Pending && $task->due_date->isPast() && !$task->due_date->isToday();
                                    $badge = match (true) {
                                        $task->status === \App\Enums\Production\TaskStatus::Done => ['bg-emerald-100 text-emerald-700', 'Done'],
                                        $isMissed => ['bg-red-100 text-red-700', 'Missed'],
                                        default => ['bg-amber-100 text-amber-700', 'Pending'],
                                    };
                                @endphp
                                <div class="flex items-center gap-3 border-b border-gray-50 px-6 py-3 last:border-b-0">
                                    <i
                                        data-lucide="{{ $type?->icon() ?? 'circle' }}"
                                        class="h-4 w-4 shrink-0 text-gray-400"
                                    ></i>
                                    <span
                                        class="flex-1 text-sm font-medium text-gray-800"
                                        >{{ $type?->label() ?? 'Task' }}</span
                                    >
                                    <span class="text-xs text-gray-400">
                                        @if ($task->status === \App\Enums\Production\TaskStatus::Done)
                                            Completed by {{ $task->completedBy?->full_name ?: 'Unknown' }}, {{ $task->completed_at?->format('h:i A') }}
                                        @elseif ($isMissed)
                                            Was due {{ $task->due_date->format('d M') }}
                                        @else
                                            Due today
                                        @endif
                                    </span>
                                    <span
                                        class="rounded-lg px-2.5 py-1 text-xs font-bold {{ $badge[0] }}"
                                        >{{ $badge[1] }}</span
                                    >
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                {{-- ══ Daily Activities ══════════════════════════════════ --}}
                <div
                    id="daily-activities"
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                >
                    <div
                        class="flex items-center gap-3 border-b border-gray-100 bg-gradient-to-r from-sky-50 to-white px-6 py-4"
                    >
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-sky-100 text-sky-600">
                            <i data-lucide="calendar-check" class="h-4 w-4"></i>
                        </div>
                        <h2 class="text-sm font-bold text-gray-900">Daily Activities</h2>
                        <span
                            class="ml-auto inline-flex items-center gap-1.5 rounded-lg bg-sky-100 px-2.5 py-1 text-xs font-bold text-sky-700"
                        >
                            {{ $activities->total() }} logged
                        </span>
                    </div>

                    <div class="px-6 py-4">
                        @if ($activities->isEmpty())
                            <div class="py-8 text-center">
                                <i data-lucide="sprout" class="mx-auto mb-2 h-8 w-8 text-gray-300"></i>
                                <p class="text-sm font-medium text-gray-400">No activities logged yet for this batch.</p>
                            </div>
                        @else
                            <div class="divide-y divide-gray-50">
                                @foreach ($activities as $activity)
                                    @php $type = $activity->activityTypeEnum(); @endphp
                                    <div class="flex items-start gap-3 py-3">
                                        <span
                                            class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                                            style="background-color: {{ $type->color()['bg'] }}; color: {{ $type->color()['text'] }}"
                                        >
                                            <i data-lucide="{{ $type->icon() }}" class="h-4 w-4"></i>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span
                                                    class="text-sm font-bold text-gray-900"
                                                    >{{ $type->label() }}</span
                                                >
                                                <span
                                                    class="text-xs text-gray-400"
                                                    >{{ $activity->performed_on->format('d M, Y, h:i A') }}</span
                                                >
                                            </div>
                                            <p class="mt-0.5 text-xs text-gray-500">
                                                by {{ $activity->performedBy->name ?? 'Unknown' }}
                                            </p>
                                            @if ($activity->notes)
                                                <p class="mt-1 text-xs text-gray-500 italic">{{ $activity->notes }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if ($activities->hasPages())
                                <div class="mt-2 border-t border-gray-100 pt-3">{{ $activities->links() }}</div>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- ══ Loss & Harvest ════════════════════════════════════ --}}
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div
                        class="flex items-center gap-3 border-b border-gray-100 bg-gradient-to-r from-rose-50 to-white px-6 py-4"
                    >
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-100 text-rose-600">
                            <i data-lucide="trending-down" class="h-4 w-4"></i>
                        </div>
                        <h2 class="text-sm font-bold text-gray-900">Loss & Harvest</h2>
                    </div>

                    {{-- Summary strip --}}
                    <div class="grid grid-cols-3 gap-px border-b border-gray-100 bg-gray-100">
                        <div class="bg-white p-4 text-center">
                            <p class="mb-1 text-[11px] font-bold tracking-wider text-red-500 uppercase">Total Lost</p>
                            <p class="text-xl font-black text-red-600">{{ number_format($totalLost) }}</p>
                        </div>
                        <div class="bg-white p-4 text-center">
                            <p class="mb-1 text-[11px] font-bold tracking-wider text-emerald-500 uppercase">Total Harvested</p>
                            <p class="text-xl font-black text-emerald-600">{{ number_format($totalHarvested) }}</p>
                        </div>
                        <div class="bg-white p-4 text-center">
                            <p class="mb-1 text-[11px] font-bold tracking-wider text-indigo-500 uppercase">Remaining</p>
                            <p class="text-xl font-black text-indigo-600">{{ number_format($plantBatch->current_quantity) }}</p>
                        </div>
                    </div>

                    {{-- Losses --}}
                    <div class="border-b border-gray-100 px-6 py-4">
                        <div class="mb-3 flex items-center gap-2">
                            <i data-lucide="alert-triangle" class="h-3.5 w-3.5 text-red-500"></i>
                            <p class="text-xs font-bold tracking-wider text-gray-400 uppercase">Losses</p>
                        </div>

                        @if ($losses->isEmpty())
                            <p class="py-3 text-center text-sm font-medium text-gray-400">No losses recorded.</p>
                        @else
                            <div class="divide-y divide-gray-50">
                                @foreach ($losses as $loss)
                                    <div class="flex items-center justify-between gap-3 py-2.5">
                                        <div class="min-w-0">
                                            <p class="text-sm font-bold text-gray-900">
                                                -{{ number_format($loss->quantity_lost) }}
                                                <span
                                                    class="ml-1 font-medium text-gray-500"
                                                    >{{ $loss->reason_label }}</span
                                                >
                                            </p>
                                            <p class="mt-0.5 text-xs text-gray-500">
                                                {{ $loss->loss_date->format('d M, Y') }}
                                                @if ($loss->recordedBy)
                                                    &middot; by {{ $loss->recordedBy->name }}
                                                @endif
                                                @if ($loss->notes)
                                                    &middot; {{ $loss->notes }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if ($losses->hasPages())
                                <div class="mt-2 border-t border-gray-100 pt-3">{{ $losses->links() }}</div>
                            @endif
                        @endif
                    </div>

                    {{-- Harvests --}}
                    <div class="px-6 py-4">
                        <div class="mb-3 flex items-center gap-2">
                            <i data-lucide="scissors" class="h-3.5 w-3.5 text-emerald-500"></i>
                            <p class="text-xs font-bold tracking-wider text-gray-400 uppercase">Harvests</p>
                        </div>

                        @if ($harvests->isEmpty())
                            <p class="py-3 text-center text-sm font-medium text-gray-400">No harvests recorded.</p>
                        @else
                            <div class="divide-y divide-gray-50">
                                @foreach ($harvests as $harvest)
                                    <div class="flex items-center justify-between gap-3 py-2.5">
                                        <div class="min-w-0">
                                            <p class="text-sm font-bold text-gray-900">
                                                +{{ number_format($harvest->quantity_harvested) }}
                                                <span
                                                    class="ml-1 font-medium text-gray-500"
                                                    >{{ $harvest->batch?->product?->name ?? 'Unknown plant' }}</span
                                                >
                                            </p>
                                            <p class="mt-0.5 text-xs text-gray-500">
                                                {{ $harvest->harvested_on->format('d M, Y, h:i A') }}
                                                @if ($harvest->harvestedBy)
                                                    &middot; by {{ $harvest->harvestedBy->name }}
                                                @endif
                                                @if ($harvest->status === \App\Enums\Production\HarvestStatus::Received)
                                                    &middot; received {{ number_format($harvest->received_quantity ?? 0) }}
                                                @endif
                                            </p>
                                        </div>
                                        <span
                                            class="shrink-0 rounded-md px-2 py-0.5 text-[10px] font-bold"
                                            style="background-color: {{ $harvest->status->color()['bg'] }}; color: {{ $harvest->status->color()['text'] }}"
                                        >
                                            {{ $harvest->status->label() }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>

                            @if ($harvests->hasPages())
                                <div class="mt-2 border-t border-gray-100 pt-3">{{ $harvests->links() }}</div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            {{-- end left column --}}

            {{-- ── RIGHT COLUMN — Sidebar (1/3 width) ────── --}}
            <div class="space-y-6">
                {{-- Quick Stats --}}
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h2 class="text-sm font-bold text-gray-900">Quick Stats</h2>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <div
                            class="flex items-center justify-between px-6 py-3.5 transition-colors hover:bg-gray-50/50"
                        >
                            <span class="text-sm font-medium text-gray-500">Status</span>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold"
                                style="background-color: {{ $plantBatch->status_color['bg'] }}; color: {{ $plantBatch->status_color['text'] }}"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full"
                                    style="background-color: {{ $plantBatch->status_color['dot'] }}"
                                ></span>
                                {{ $plantBatch->status_label }}
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between px-6 py-3.5 transition-colors hover:bg-gray-50/50"
                        >
                            <span class="text-sm font-medium text-gray-500">Initial Qty</span>
                            <span
                                class="text-sm font-black text-gray-800"
                                >{{ number_format($plantBatch->initial_quantity) }}</span
                            >
                        </div>
                        <div
                            class="flex items-center justify-between px-6 py-3.5 transition-colors hover:bg-gray-50/50"
                        >
                            <span class="text-sm font-medium text-gray-500">Current (Live)</span>
                            <span
                                class="text-sm font-black {{ $plantBatch->current_quantity === 0 ? 'text-gray-300' : 'text-emerald-700' }}"
                            >
                                {{ number_format($plantBatch->current_quantity) }}
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between px-6 py-3.5 transition-colors hover:bg-gray-50/50"
                        >
                            <span class="text-sm font-medium text-gray-500">Reduced</span>
                            <span class="text-sm font-black {{ $lost > 0 ? 'text-red-500' : 'text-gray-300' }}">
                                {{ $lost > 0 ? '-' . number_format($lost) : '—' }}
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between px-6 py-3.5 transition-colors hover:bg-gray-50/50"
                        >
                            <span class="text-sm font-medium text-gray-500">Remaining</span>
                            <span class="text-sm font-black text-gray-800">{{ $pct }}%</span>
                        </div>
                        <div
                            class="flex items-center justify-between px-6 py-3.5 transition-colors hover:bg-gray-50/50"
                        >
                            <span class="text-sm font-medium text-gray-500">Source</span>
                            <span class="text-sm font-bold text-gray-700">{{ $plantBatch->source_type_label }}</span>
                        </div>
                        <div
                            class="flex items-center justify-between px-6 py-3.5 transition-colors hover:bg-gray-50/50"
                        >
                            <span class="text-sm font-medium text-gray-500">Age</span>
                            <span class="text-sm font-bold text-gray-700"> {{ $plantBatch->age ?? '—' }} </span>
                        </div>
                    </div>
                </div>

                {{-- Recent Activity — end users mostly rely on this; full history is
                     in the Daily Activities feed which very few will dig into --}}
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center gap-3 border-b border-gray-100 px-6 py-4">
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-sky-50 text-sky-600">
                            <i data-lucide="history" class="h-3.5 w-3.5"></i>
                        </div>
                        <h2 class="text-sm font-bold text-gray-900">Recent Activity</h2>
                    </div>

                    @php $recentActivities = $activities->getCollection()->take(3); @endphp

                    @if ($recentActivities->isEmpty())
                        <div class="px-6 py-6 text-center">
                            <i data-lucide="sprout" class="mx-auto mb-2 h-6 w-6 text-gray-300"></i>
                            <p class="text-xs font-medium text-gray-400">No activity logged yet.</p>
                        </div>
                    @else
                        <div class="divide-y divide-gray-50">
                            @foreach ($recentActivities as $recent)
                                @php $recentType = $recent->activityTypeEnum(); @endphp
                                <div class="flex items-center gap-3 px-6 py-3">
                                    <span
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                                        style="background-color: {{ $recentType->color()['bg'] }}; color: {{ $recentType->color()['text'] }}"
                                    >
                                        <i data-lucide="{{ $recentType->icon() }}" class="h-4 w-4"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-bold text-gray-900">{{ $recentType->label() }}</p>
                                        <p class="text-xs text-gray-400">
                                            {{ $recent->performed_on->diffForHumans(null, true) }} ago &middot; {{ $recent->performedBy->name ?? 'Unknown' }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if ($activities->total() > 3)
                            <div class="border-t border-gray-100 px-6 py-3 text-center">
                                <a href="#daily-activities" class="text-xs font-bold text-sky-600 hover:text-sky-800">
                                    View all {{ $activities->total() }} activities →
                                </a>
                            </div>
                        @endif
                    @endif
                </div>

                {{-- Lifecycle Progress --}}
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white px-6 py-4">
                        <h2 class="text-sm font-bold text-gray-900">Lifecycle Stage</h2>
                        <p class="mt-0.5 text-xs font-medium text-gray-400">Track batch progress</p>
                    </div>

                    <div class="p-6">
                        @php
                            // "done"/"active" are all derived from real batch data now —
                            // isActive() already excludes both Closed and Cancelled batches,
                            // so no separate cancelled-guard is needed on the 'active' flags.
                            $everPlaced = $plantBatch->placements()->exists();

                            $latestHarvest = $totalHarvested > 0
                                ? $plantBatch->harvests()->latest('harvested_on')->first()
                                : null;

                            $stages = [
                                [
                                    'key'    => 'created',
                                    'label'  => 'Batch Created',
                                    'sub'    => $plantBatch->batch_start_datetime?->format('d M, Y'),
                                    'icon'   => 'package-plus',
                                    'done'   => true,
                                    'active' => false,
                                ],
                                [
                                    'key'    => 'placement',
                                    'label'  => 'Placed in Nursery',
                                    'sub'    => $currentPlacement
                                        ? ($currentPlacement->growingSpace->zone->name ?? '—') . ' → ' . ($currentPlacement->growingSpace->name ?? '—')
                                        : ($everPlaced ? 'Currently unplaced' : 'Awaiting placement'),
                                    'icon'   => 'map-pin',
                                    'done'   => $everPlaced,
                                    'active' => !$everPlaced && $plantBatch->isActive(),
                                ],
                                [
                                    'key'    => 'activities',
                                    'label'  => 'Daily Care Active',
                                    'sub'    => $activities->total() > 0
                                        ? $activities->total() . ' ' . \Illuminate\Support\Str::plural('activity', $activities->total()) . ' logged'
                                        : 'Watering, Fertilizing...',
                                    'icon'   => 'clipboard-list',
                                    // Care phase only "completes" once the batch itself closes —
                                    // it's an ongoing phase, not a one-time checkbox.
                                    'done'   => $plantBatch->isClosed(),
                                    'active' => $activities->total() > 0 && $plantBatch->isActive(),
                                ],
                                [
                                    'key'    => 'harvest',
                                    'label'  => 'Harvested / Closed',
                                    'sub'    => $plantBatch->isClosed()
                                        ? ($latestHarvest?->harvested_on?->format('d M, Y') ?? $plantBatch->updated_at?->format('d M, Y'))
                                        : ($totalHarvested > 0 ? "In progress — {$totalHarvested} harvested so far" : 'Pending'),
                                    'icon'   => 'leaf',
                                    'done'   => $plantBatch->isClosed(),
                                    'active' => $plantBatch->isActive() && $totalHarvested > 0,
                                ],
                            ];
                        @endphp

                        <div class="relative">
                            <div class="absolute top-2 left-4 h-[calc(100%-2rem)] w-px bg-gray-200"></div>

                            <div class="space-y-6">
                                @foreach ($stages as $stage)
                                    <div class="relative flex items-start gap-4">
                                        <div
                                            class="relative z-10 flex h-9 w-9 shrink-0 items-center justify-center rounded-full border-2 transition-all
                                            {{ $stage['done']
                                                ? 'border-emerald-400 bg-emerald-50 text-emerald-600'
                                                : ($stage['active']
                                                    ? 'border-indigo-400 bg-indigo-50 text-indigo-600 animate-pulse-slow'
                                                    : 'border-gray-200 bg-white text-gray-300') }}"
                                        >
                                            <i data-lucide="{{ $stage['icon'] }}" class="h-4 w-4"></i>
                                        </div>
                                        <div class="flex-1 pt-1">
                                            <p
                                                class="text-sm font-bold
                                                {{ $stage['done'] ? 'text-emerald-700' : ($stage['active'] ? 'text-indigo-700' : 'text-gray-400') }}"
                                            >
                                                {{ $stage['label'] }}
                                            </p>
                                            <p
                                                class="text-xs font-medium
                                                {{ $stage['done'] ? 'text-emerald-500' : 'text-gray-400' }}"
                                            >
                                                {{ $stage['sub'] ?? '' }}
                                            </p>
                                            @if ($stage['active'])
                                                <span
                                                    class="mt-1.5 inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-2 py-0.5 text-xs font-bold text-indigo-500"
                                                >
                                                    <span
                                                        class="h-1.5 w-1.5 animate-pulse rounded-full bg-indigo-500"
                                                    ></span>
                                                    In Progress
                                                </span>
                                            @endif
                                        </div>
                                        @if ($stage['done'])
                                            <i
                                                data-lucide="check-circle"
                                                class="mt-1.5 h-5 w-5 shrink-0 text-emerald-500"
                                            ></i>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Danger Zone — only for active batches with intact quantity --}}
                @if ($plantBatch->isActive() && $plantBatch->isQuantityIntact())
                    <div
                        class="overflow-hidden rounded-2xl border-2 border-dashed border-red-300 bg-white shadow-sm"
                        x-data="{ open: false }"
                    >
                        <button
                            @click="open = !open"
                            class="flex w-full items-center gap-3 px-6 py-4 text-left transition-colors hover:bg-red-50/50"
                        >
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-red-100 text-red-500">
                                <i data-lucide="alert-triangle" class="h-4 w-4"></i>
                            </div>
                            <span class="text-sm font-bold text-red-700">Danger Zone</span>
                            <i
                                data-lucide="chevron-down"
                                class="ml-auto h-4 w-4 text-red-400 transition-transform"
                                :class="open ? 'rotate-180' : ''"
                            ></i>
                        </button>

                        <div
                            x-show="open"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95"
                            class="border-t-2 border-dashed border-red-300 bg-red-50/30 px-6 py-5"
                        >
                            <div class="mb-4 flex items-start gap-3">
                                <i data-lucide="info" class="mt-0.5 h-5 w-5 shrink-0 text-red-500"></i>
                                <p class="text-sm font-medium text-red-700">Cancel this batch permanently. Only possible while quantity is untouched. This action cannot be undone.</p>
                            </div>
                            <button
                                type="button"
                                onclick="confirmDelete()"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-red-500/25 transition-all hover:bg-red-700 hover:shadow-red-500/40 active:scale-95"
                            >
                                <i data-lucide="x-circle" class="h-4 w-4"></i>
                                Delete Batch
                            </button>
                        </div>
                    </div>
                @endif
            </div>
            {{-- end right column --}}
        </div>
        {{-- end main grid --}}
    </div>
@endsection

@push ('scripts')
    <script>
        // ── Batch Detail Manager (Alpine.js) ──────────────────────
        function batchDetailManager() {
            return {
                init() {
                    // Re-initialize Lucide icons after Alpine renders
                    this.$nextTick(() => {
                        if (typeof lucide !== "undefined") {
                            lucide.createIcons();
                        }
                    });
                },
            };
        }

        // ── Status transition with SweetAlert2 ────────────────────
        function batchStatusManager() {
            return {
                loading: false,

                async updateStatus(newStatus, label) {
                    const result = await Swal.fire({
                        title: `Mark as ${label}?`,
                        text: "This action will update the batch status immediately.",
                        icon: newStatus === "cancelled" ? "warning" : "info",
                        showCancelButton: true,
                        confirmButtonColor: newStatus === "cancelled" ? "#ef4444" : "#3b82f6",
                        cancelButtonColor: "#6b7280",
                        confirmButtonText: `Yes, mark ${label}`,
                        cancelButtonText: "Cancel",
                        customClass: {
                            popup: "rounded-2xl",
                            confirmButton: "rounded-xl font-bold text-sm px-6 py-2.5",
                            cancelButton: "rounded-xl font-bold text-sm px-6 py-2.5",
                        },
                    });

                    if (!result.isConfirmed) return;

                    this.loading = true;

                    try {
                        const res = await fetch("{{ route('admin.production.plant-batches.update-status', $plantBatch) }}", {
                            method: "PATCH",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                "X-Requested-With": "XMLHttpRequest",
                            },
                            body: JSON.stringify({ status: newStatus }),
                        });

                        const json = await res.json();

                        if (!res.ok || !json.success) {
                            Swal.fire({
                                title: "Error!",
                                text: json.message || "Something went wrong.",
                                icon: "error",
                                customClass: {
                                    popup: "rounded-2xl",
                                },
                            });
                            return;
                        }

                        // Update badge in header without page reload
                        const badge = document.getElementById("status-badge");
                        const dot = document.getElementById("status-dot");
                        const lbl = document.getElementById("status-label");
                        const colors = json.status_color;

                        if (badge && colors) {
                            badge.style.backgroundColor = colors.bg;
                            badge.style.color = colors.text;
                            dot.style.backgroundColor = colors.dot;
                            lbl.textContent = json.status_label;
                        }

                        Swal.fire({
                            title: "Success!",
                            text: json.message || "Updated successfully.",
                            icon: "success",
                            timer: 2000,
                            showConfirmButton: false,
                            customClass: {
                                popup: "rounded-2xl",
                            },
                        });

                        // Reload after short delay so transition buttons refresh
                        setTimeout(() => window.location.reload(), 1500);
                    } catch (err) {
                        Swal.fire({
                            title: "Error!",
                            text: "Network error. Please try again.",
                            icon: "error",
                            customClass: {
                                popup: "rounded-2xl",
                            },
                        });
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }

        // ── Delete batch (destroy) with SweetAlert2 ───────────────
        async function confirmDelete() {
            const result = await Swal.fire({
                title: "Delete this batch?",
                html: '<p class="text-sm text-gray-600">This will permanently delete this plant batch and all associated records. This action cannot be undone.</p>',
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#ef4444",
                cancelButtonColor: "#6b7280",
                confirmButtonText: "Yes, delete batch",
                cancelButtonText: "Keep batch",
                customClass: {
                    popup: "rounded-2xl",
                    confirmButton: "rounded-xl font-bold text-sm px-6 py-2.5",
                    cancelButton: "rounded-xl font-bold text-sm px-6 py-2.5",
                },
            });

            if (!result.isConfirmed) return;

            try {
                const res = await fetch("{{ route('admin.production.plant-batches.destroy', $plantBatch) }}", {
                    method: "DELETE",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });

                const json = await res.json();

                if (!res.ok || !json.success) {
                    Swal.fire({
                        title: "Deletion Failed",
                        text: json.message || "Unable to delete batch. Please try again.",
                        icon: "error",
                        customClass: {
                            popup: "rounded-2xl",
                        },
                    });
                    return;
                }

                Swal.fire({
                    title: "Deleted!",
                    text: json.message || "The batch has been permanently deleted.",
                    icon: "success",
                    timer: 2000,
                    showConfirmButton: false,
                    customClass: {
                        popup: "rounded-2xl",
                    },
                });

                setTimeout(() => (window.location.href = "{{ route('admin.production.plant-batches.index') }}"), 1500);
            } catch (err) {
                Swal.fire({
                    title: "Error!",
                    text: "Network error. Please try again.",
                    icon: "error",
                    customClass: {
                        popup: "rounded-2xl",
                    },
                });
            }
        }
    </script>
@endpush
