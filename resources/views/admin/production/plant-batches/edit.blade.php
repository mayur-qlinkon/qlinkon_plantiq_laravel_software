@extends ('layouts.admin')

@section ('title', 'Edit ' . $plantBatch->batch_code . ' — PlantIQ')

@section ('header-title')
    <div class="flex w-full items-center justify-between">
        <div>
            <h1 class="text-lg font-bold tracking-tight text-gray-900">Edit Batch</h1>
            <p class="text-[12px] font-medium text-gray-500">
                {{ $plantBatch->batch_code }} &middot; {{ $plantBatch->product->name ?? '—' }}
            </p>
        </div>

        <a
            href="{{ route('admin.production.plant-batches.show', $plantBatch) }}"
            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-600 shadow-sm transition-all hover:bg-gray-50 hover:text-gray-900 active:scale-95"
        >
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            Back to Batch
        </a>
    </div>
@endsection

@section ('content')
    <div class="mx-auto w-full max-w-3xl" x-data="plantBatchEdit()">
        {{-- Flash messages --}}
        @if (session('success'))
            <div
                class="mb-5 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3.5 text-sm font-semibold text-emerald-800 shadow-sm"
            >
                <i data-lucide="check-circle" class="h-5 w-5 shrink-0 text-emerald-500"></i>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div
                class="mb-5 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-3.5 text-sm font-semibold text-red-800 shadow-sm"
            >
                <i data-lucide="alert-circle" class="h-5 w-5 shrink-0 text-red-500"></i>
                {{ session('error') }}
            </div>
        @endif

        {{-- Validation errors --}}
        @if ($errors->any())
            <div
                class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
            >
                <i data-lucide="circle-x" class="mt-0.5 h-4 w-4 shrink-0 text-red-500"></i>
                <div>
                    <p class="font-bold">Please fix the following errors:</p>
                    <ul class="mt-1 list-disc pl-4 font-medium">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════════════
             SECTION 1 — Plant Details & Quantity Correction
             Plant/Product and Initial Quantity are permanent identity
             fields (never editable after creation). Current Quantity is
             the only correctable field here, via a dedicated form that
             posts to the adjust-quantity endpoint and writes a
             BatchAdjustment ledger row. Separate <form> from Batch Info
             below since they hit different routes/methods.
             ══════════════════════════════════════════════════════════ --}}
        <div class="mb-4 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-gray-100 px-6 py-4">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <i data-lucide="sprout" class="h-3.5 w-3.5"></i>
                </div>
                <h2 class="text-sm font-bold text-gray-900">Plant Details</h2>
            </div>

            <form
                method="POST"
                action="{{ route('admin.production.plant-batches.adjust-quantity', $plantBatch) }}"
                @submit="submittingAdjustment = true"
            >
                @csrf
                @method ('PATCH')

                <div class="space-y-5 px-6 py-5">
                    {{-- Plant / Product — locked, permanent identity field --}}
                    <div>
                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700"> Plant / Product </label>
                        <div
                            class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-medium text-gray-500"
                        >
                            <i data-lucide="lock" class="h-3.5 w-3.5 shrink-0 text-gray-400"></i>
                            <span>{{ $plantBatch->product->name ?? '—' }}</span>
                            @if ($plantBatch->sku)
                                <span class="text-gray-400">&middot; {{ $plantBatch->sku->sku }}</span>
                            @endif
                        </div>
                        <p class="mt-1.5 text-[11px] font-semibold text-gray-400">Fixed at batch creation — cannot be changed.</p>
                    </div>

                    {{-- Initial Quantity (locked) + Current Quantity (editable) side by side --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-[13px] font-bold text-gray-700"> Initial Quantity </label>
                            <div
                                class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-medium text-gray-500"
                            >
                                <i data-lucide="lock" class="h-3.5 w-3.5 shrink-0 text-gray-400"></i>
                                <span>{{ $plantBatch->initial_quantity }}</span>
                            </div>
                            <p class="mt-1.5 text-[11px] font-semibold text-gray-400">Permanent — total plants that entered the nursery.</p>
                        </div>

                        <div>
                            <label for="current_quantity" class="mb-1.5 block text-[13px] font-bold text-gray-700">
                                Current Quantity
                                @if ($isOpeningStockBatch)
                                    <span class="text-red-500">*</span>
                                @endif
                            </label>

                            @if ($isOpeningStockBatch)
                                <input
                                    type="number"
                                    id="current_quantity"
                                    name="current_quantity"
                                    min="0"
                                    value="{{ old('current_quantity', $plantBatch->current_quantity) }}"
                                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 shadow-sm transition-all outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/10"
                                />
                                <p class="mt-1.5 text-[11px] font-semibold text-gray-400">Correct stock on hand — recorded in the history below.</p>
                            @else
                                <div
                                    class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-medium text-gray-500"
                                >
                                    <i data-lucide="lock" class="h-3.5 w-3.5 shrink-0 text-gray-400"></i>
                                    <span>{{ $plantBatch->current_quantity }}</span>
                                </div>
                                <p class="mt-1.5 text-[11px] font-semibold text-amber-600">Direct correction is only available for Opening Stock batches — use Loss / Harvest for this batch.</p>
                            @endif

                            @error ('current_quantity')
                                <p class="mt-1.5 text-[12px] font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    @if ($isOpeningStockBatch)
                        {{-- Notes — reason for this correction, saved on the ledger row --}}
                        <div>
                            <label for="adjustment_notes" class="mb-1.5 block text-[13px] font-bold text-gray-700">
                                Correction Notes <span class="font-normal text-gray-400">(optional)</span>
                            </label>
                            <textarea
                                id="adjustment_notes"
                                name="notes"
                                rows="3"
                                placeholder="Reason for this correction — e.g. physical recount, damaged stock found…"
                                class="w-full resize-none rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition-all outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/10"
                                >{{ old('notes') }}</textarea
                            >
                            @error ('notes')
                                <p class="mt-1.5 text-[12px] font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                </div>

                @if ($isOpeningStockBatch)
                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50/50 px-6 py-4">
                        <button
                            type="submit"
                            :disabled="submittingAdjustment"
                            class="inline-flex items-center gap-2 rounded-xl bg-gray-900 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:bg-gray-800 hover:shadow active:scale-95 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <i
                                data-lucide="loader-2"
                                class="h-4 w-4 animate-spin"
                                x-show="submittingAdjustment"
                                x-cloak
                            ></i>
                            <span x-text="submittingAdjustment ? 'Saving…' : 'Save Adjustment'">Save Adjustment</span>
                        </button>
                    </div>
                @endif
            </form>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             SECTION 2 — Adjustment History (paginated)
             ══════════════════════════════════════════════════════════ --}}
        <div class="mb-4 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-gray-100 px-6 py-4">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                    <i data-lucide="history" class="h-3.5 w-3.5"></i>
                </div>
                <h2 class="text-sm font-bold text-gray-900">Adjustment History</h2>
                <span class="ml-auto text-[11px] font-semibold text-gray-400">{{ $adjustments->total() }} total</span>
            </div>

            @if ($adjustments->isEmpty())
                <div class="px-6 py-10 text-center">
                    <i data-lucide="inbox" class="mx-auto h-6 w-6 text-gray-300"></i>
                    <p class="mt-2 text-sm font-medium text-gray-400">No quantity corrections recorded yet.</p>
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach ($adjustments as $adjustment)
                        <div class="flex items-start gap-4 px-6 py-4">
                            <div
                                class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $adjustment->delta >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' }}"
                            >
                                <i
                                    data-lucide="{{ $adjustment->delta >= 0 ? 'trending-up' : 'trending-down' }}"
                                    class="h-4 w-4"
                                ></i>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-bold text-gray-900">
                                        {{ $adjustment->old_quantity }} &rarr; {{ $adjustment->new_quantity }}
                                    </span>
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-bold {{ $adjustment->delta >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}"
                                    >
                                        {{ $adjustment->delta >= 0 ? '+' : '' }}{{ $adjustment->delta }}
                                    </span>
                                </div>

                                @if ($adjustment->notes)
                                    <p class="mt-1 text-[13px] font-medium text-gray-600">{{ $adjustment->notes }}</p>
                                @endif

                                <p class="mt-1.5 text-[11px] font-semibold text-gray-400">
                                    {{ $adjustment->created_at->format('d M Y, h:i A') }} &middot; by {{ $adjustment->adjustedBy->name ?? 'Unknown' }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-gray-100 px-6 py-4">{{ $adjustments->links() }}</div>
            @endif
        </div>
    </div>
@endsection

@push ('scripts')
    <script>
        function plantBatchEdit() {
            return {
                submittingAdjustment: false,
            };
        }
    </script>
@endpush
