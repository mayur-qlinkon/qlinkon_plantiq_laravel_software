@extends ('layouts.admin')

@section('title', 'Harvest Lot Details - PlantIQ')

@php
    // Check if it's already an Enum object (due to model casting) or a string/value
    $currentStatus =
        $harvest->status instanceof \App\Enums\Production\HarvestStatus
            ? $harvest->status
            : \App\Enums\Production\HarvestStatus::tryFrom($harvest->status);

    // Fallback just in case database has some old/invalid status string
    if (!$currentStatus) {
        $currentStatus = \App\Enums\Production\HarvestStatus::Pending;
    }
@endphp

@section('header-title')
    <div class="flex w-full items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.production.harvest-lots.index') }}"
                class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-500 transition-colors hover:bg-slate-200 hover:text-slate-900">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
            </a>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-lg leading-tight font-bold text-slate-900">Harvest Lot #H-{{ $harvest->id }}</h1>
                </div>
                <p class="text-xs font-medium text-slate-500">Reported on
                    {{ \Carbon\Carbon::parse($harvest->harvested_on)->format('d M Y') }}</p>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="mx-auto w-full max-w-7xl space-y-6" x-data="harvestDetailSPA()">
        {{-- Page-level actions --}}
        <div class="flex justify-end">
            <a href="{{ route('admin.production.harvest-lots.pdf', $harvest->id) }}" target="_blank"
                class="flex h-9 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-600 shadow-sm transition-colors hover:bg-slate-50 hover:text-slate-900">
                <i data-lucide="download" class="h-3.5 w-3.5"></i>
                <span>Download PDF</span>
            </a>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- ========================================== --}}
            {{-- LEFT COLUMN: Details --}}
            {{-- ========================================== --}}
            <div class="space-y-6 lg:col-span-2">
                {{-- 1. Worker Report Info --}}
                <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-4">
                        <h2 class="text-sm font-bold text-slate-800">Harvest Report</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2">
                        <div class="border-b border-slate-100 p-5 md:border-r md:border-b-0">
                            <span class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Reported By</span>
                            <div class="mt-2 flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-slate-100 text-sm font-bold text-slate-600">
                                    {{ strtoupper(substr($harvest->harvestedBy->name ?? 'S', 0, 2)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900">
                                        {{ $harvest->harvestedBy->name ?? 'System' }}</p>
                                    <p class="text-xs font-medium text-slate-500">Field Worker</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-5">
                            <span class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Reported
                                Quantity</span>
                            <div class="mt-2 flex items-baseline gap-1.5">
                                <span
                                    class="text-3xl font-extrabold text-slate-900">{{ number_format($harvest->quantity_harvested) }}</span>
                                <span class="text-sm font-semibold text-slate-500">Plants</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. Plant Batch & Product Info --}}
                <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-4">
                        <h2 class="text-sm font-bold text-slate-800">Product & Source Batch</h2>
                    </div>
                    <div class="p-5">
                        <div class="flex items-start gap-4">
                            <div
                                class="bg-brand-50 text-brand-600 flex h-12 w-12 shrink-0 items-center justify-center rounded-xl">
                                <i data-lucide="sprout" class="h-6 w-6"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">
                                    {{ $harvest->batch->product->name ?? 'Unknown Product' }}
                                </h3>
                                <div class="mt-1 flex flex-wrap items-center gap-3 text-sm text-slate-600">
                                    <span class="flex items-center gap-1">
                                        <i data-lucide="box" class="h-3.5 w-3.5 text-slate-400"></i>
                                        SKU:
                                        <span class="font-semibold">{{ $harvest->batch->sku->sku ?? 'N/A' }}</span>
                                    </span>
                                    <span class="text-slate-300">•</span>
                                    <span class="flex items-center gap-1">
                                        <i data-lucide="hash" class="h-3.5 w-3.5 text-slate-400"></i>
                                        Source Batch:
                                        <span class="font-mono font-semibold">{{ $harvest->batch->batch_code }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        @if ($harvest->notes)
                            <div class="mt-4 rounded-xl bg-slate-50 p-4">
                                <span class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Worker
                                    Notes</span>
                                <p class="mt-1 text-sm font-medium text-slate-700">{{ $harvest->notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- 3. Received Details (If Status is Received) --}}
                @if ($harvest->status === \App\Enums\Production\HarvestStatus::Received)
                    <div class="overflow-hidden rounded-2xl border border-emerald-200 bg-emerald-50/30 shadow-sm">
                        <div class="flex items-center gap-2 border-b border-emerald-100 bg-emerald-100/50 px-5 py-4">
                            <i data-lucide="check-circle" class="h-5 w-5 text-emerald-600"></i>
                            <h2 class="text-sm font-bold text-emerald-800">Stock Received Info</h2>
                        </div>
                        <div class="grid grid-cols-1 divide-y divide-emerald-100 md:grid-cols-3 md:divide-x md:divide-y-0">
                            <div class="p-5">
                                <span class="text-[11px] font-bold tracking-wider text-emerald-600/70 uppercase">Final
                                    Received Qty</span>
                                <div class="mt-1 text-lg font-bold text-emerald-900">
                                    {{ number_format($harvest->received_quantity) }} Plants
                                </div>
                            </div>
                            <div class="p-5">
                                <span class="text-[11px] font-bold tracking-wider text-emerald-600/70 uppercase">Destination
                                    Warehouse</span>
                                <div class="mt-1 text-sm font-bold text-emerald-900">
                                    {{ $harvest->warehouse->name ?? 'Unknown' }}
                                </div>
                            </div>
                            <div class="p-5">
                                <span class="text-[11px] font-bold tracking-wider text-emerald-600/70 uppercase">Received By
                                    & Date</span>
                                <div class="mt-1 text-sm font-bold text-emerald-900">
                                    {{ $harvest->receivedBy->name ?? 'Admin' }}
                                </div>
                                <div class="text-xs font-medium text-emerald-700">
                                    {{ \Carbon\Carbon::parse($harvest->received_at)->format('d M Y, h:i A') }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- 4. Cancelled Details (If Status is Cancelled) --}}
                @if ($harvest->status === \App\Enums\Production\HarvestStatus::Cancelled)
                    <div class="overflow-hidden rounded-2xl border border-rose-200 bg-rose-50/30 p-6 text-center shadow-sm">
                        <div
                            class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                            <i data-lucide="x-octagon" class="h-6 w-6"></i>
                        </div>
                        <h2 class="mt-3 text-base font-bold text-rose-800">This Harvest Lot was Cancelled</h2>
                        <p class="mt-1 text-sm text-rose-600">The reported quantity was returned to the source plant batch.
                        </p>
                    </div>
                @endif
            </div>

            {{-- ========================================== --}}
            {{-- RIGHT COLUMN: Actions (Only if Pending) --}}
            {{-- ========================================== --}}
            @if ($harvest->status === \App\Enums\Production\HarvestStatus::Pending)
                <div class="space-y-4">
                    {{-- Primary Action Card: Accept & Stock --}}
                    <div class="border-brand-200 overflow-hidden rounded-2xl border bg-white shadow-md">
                        <div class="bg-brand-50 px-5 py-4">
                            <h2 class="text-brand-900 flex items-center gap-2 text-base font-bold">
                                <i data-lucide="package-plus" class="h-5 w-5"></i> Accept & Stock
                            </h2>
                            <p class="text-brand-700 mt-1 text-xs font-medium">Verify quantity and select a warehouse to add
                                to inventory.</p>
                        </div>

                        <form @submit.prevent="submitReceive" class="space-y-4 p-5">
                            <div>
                                <label class="mb-1.5 block text-xs font-bold text-slate-700">Actual Received Quantity <span
                                        class="text-rose-500">*</span></label>
                                <input type="number" x-model="receiveForm.received_quantity"
                                    class="focus:border-brand-500 focus:ring-brand-500/20 w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-900"
                                    required />
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-bold text-slate-700">Select Warehouse <span
                                        class="text-rose-500">*</span></label>
                                <select x-model="receiveForm.warehouse_id"
                                    class="focus:border-brand-500 focus:ring-brand-500/20 w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700"
                                    required>
                                    <option value="" disabled>-- Select a Warehouse --</option>
                                    @foreach ($warehouses as $wh)
                                        <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" :disabled="isReceiving"
                                class="bg-brand-600 hover:bg-brand-700 flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-bold text-white shadow-sm transition-all active:scale-95 disabled:cursor-not-allowed disabled:opacity-70">
                                <i data-lucide="check-circle-2" class="h-4 w-4" x-show="!isReceiving"></i>
                                <i data-lucide="loader-2" class="h-4 w-4 animate-spin" x-show="isReceiving" x-cloak></i>
                                <span x-text="isReceiving ? 'Processing...' : 'Approve & Update Inventory'"></span>
                            </button>
                        </form>
                    </div>

                    {{-- Secondary Actions --}}
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
                        <h3 class="mb-3 text-xs font-bold tracking-wider text-slate-400 uppercase">Other Actions</h3>
                        <div class="space-y-2">
                            <button @click="modals.correct = true"
                                class="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold text-slate-700 transition-colors hover:bg-slate-100 hover:text-slate-900">
                                <span class="flex items-center gap-2"><i data-lucide="edit-3"
                                        class="h-4 w-4 text-slate-500"></i> Correct Qty</span>
                                <i data-lucide="chevron-right" class="h-4 w-4 text-slate-400"></i>
                            </button>

                            <button @click="modals.cancel = true"
                                class="flex w-full items-center justify-between rounded-xl border border-rose-100 bg-rose-50 px-4 py-2.5 text-sm font-bold text-rose-700 transition-colors hover:bg-rose-100 hover:text-rose-900">
                                <span class="flex items-center gap-2"><i data-lucide="trash-2"
                                        class="h-4 w-4 text-rose-500"></i> Cancel Lot</span>
                                <i data-lucide="chevron-right" class="h-4 w-4 text-rose-400"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- ========================================== --}}
        {{-- MODALS --}}
        {{-- ========================================== --}}
        @if ($harvest->status === \App\Enums\Production\HarvestStatus::Pending)
            {{-- Correct Quantity Modal --}}
            <div x-show="modals.correct"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm" x-cloak>
                <div @click.outside="modals.correct = false" class="w-full max-w-md rounded-2xl bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h3 class="text-base font-bold text-slate-900">Correct Reported Quantity</h3>
                        <button @click="modals.correct = false" class="text-slate-400 hover:text-slate-600">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                    <form @submit.prevent="submitCorrect" class="space-y-4 p-5">
                        <p class="mb-2 text-xs font-medium text-slate-500">Adjust the quantity if the worker made a
                            mistake. The difference will be synced with the source batch.</p>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">New Quantity <span
                                    class="text-rose-500">*</span></label>
                            <input type="number" x-model="correctForm.quantity_harvested"
                                class="focus:border-brand-500 focus:ring-brand-500/20 w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-900"
                                required min="1" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">Remarks (Optional)</label>
                            <textarea x-model="correctForm.remarks"
                                class="focus:border-brand-500 focus:ring-brand-500/20 w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-900"
                                rows="2" placeholder="Reason for correction..."></textarea>
                        </div>
                        <div class="mt-4 flex gap-3">
                            <button type="button" @click="modals.correct = false"
                                class="w-1/2 rounded-xl bg-slate-100 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-200">
                                Cancel
                            </button>
                            <button type="submit" :disabled="isCorrecting"
                                class="flex w-1/2 items-center justify-center gap-2 rounded-xl bg-slate-900 py-2.5 text-sm font-bold text-white hover:bg-slate-800 disabled:opacity-70">
                                <i data-lucide="loader-2" class="h-4 w-4 animate-spin" x-show="isCorrecting" x-cloak></i>
                                <span>Save Correction</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Cancel Lot Modal --}}
            <div x-show="modals.cancel"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm" x-cloak>
                <div @click.outside="modals.cancel = false" class="w-full max-w-md rounded-2xl bg-white shadow-xl">
                    <div
                        class="flex items-center justify-between rounded-t-2xl border-b border-rose-100 bg-rose-50 px-5 py-4">
                        <h3 class="text-base font-bold text-rose-800">Cancel Harvest Lot</h3>
                        <button @click="modals.cancel = false" class="text-rose-400 hover:text-rose-600">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                    <form @submit.prevent="submitCancel" class="space-y-4 p-5">
                        <p class="text-sm font-medium text-slate-600">Are you sure you want to cancel this lot? The <span
                                class="font-bold text-slate-900">{{ number_format($harvest->quantity_harvested) }}
                                plants</span> will be returned to the source batch. This cannot be undone.</p>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">Cancellation Remarks
                                (Optional)</label>
                            <textarea x-model="cancelForm.remarks"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-900 focus:border-rose-500 focus:ring-rose-500/20"
                                rows="2" placeholder="Why is this being cancelled?"></textarea>
                        </div>
                        <div class="mt-4 flex gap-3">
                            <button type="button" @click="modals.cancel = false"
                                class="w-1/2 rounded-xl bg-slate-100 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-200">
                                Keep Lot
                            </button>
                            <button type="submit" :disabled="isCancelling"
                                class="flex w-1/2 items-center justify-center gap-2 rounded-xl bg-rose-600 py-2.5 text-sm font-bold text-white hover:bg-rose-700 disabled:opacity-70">
                                <i data-lucide="loader-2" class="h-4 w-4 animate-spin" x-show="isCancelling" x-cloak></i>
                                <span>Yes, Cancel Lot</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
    <script>
        window.harvestDetailSPA = function() {
            return {
                harvestId: {{ $harvest->id }},

                receiveForm: {
                    received_quantity: {{ $harvest->quantity_harvested }},
                    warehouse_id: "",
                },

                correctForm: {
                    quantity_harvested: {{ $harvest->quantity_harvested }},
                    remarks: "",
                },

                cancelForm: {
                    remarks: "",
                },

                isReceiving: false,
                isCorrecting: false,
                isCancelling: false,

                modals: {
                    correct: false,
                    cancel: false,
                },

                init() {
                    this.$nextTick(() => {
                        if (typeof lucide !== "undefined") lucide.createIcons();
                    });
                },

                async submitReceive() {
                    this.isReceiving = true;
                    try {
                        const res = await fetch(`/admin/production/harvest-lots/${this.harvestId}/receive`, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify(this.receiveForm),
                        });
                        const data = await res.json();

                        if (data.success) {
                            if (typeof BizAlert !== "undefined") BizAlert.toast(data.message, "success");
                            window.location.reload(); // Reload to show updated received status
                        } else {
                            if (typeof BizAlert !== "undefined") BizAlert.toast(data.message || "Error occurred",
                                "error");
                        }
                    } catch (e) {
                        if (typeof BizAlert !== "undefined") BizAlert.toast("Network error, please try again.",
                            "error");
                    } finally {
                        this.isReceiving = false;
                    }
                },

                async submitCorrect() {
                    this.isCorrecting = true;
                    try {
                        const res = await fetch(`/admin/production/harvest-lots/${this.harvestId}/quantity`, {
                            method: "PATCH",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify(this.correctForm),
                        });
                        const data = await res.json();

                        if (data.success) {
                            if (typeof BizAlert !== "undefined") BizAlert.toast(data.message, "success");
                            window.location.reload();
                        } else {
                            if (typeof BizAlert !== "undefined") BizAlert.toast(data.message || "Error occurred",
                                "error");
                        }
                    } catch (e) {
                        if (typeof BizAlert !== "undefined") BizAlert.toast("Network error, please try again.",
                            "error");
                    } finally {
                        this.isCorrecting = false;
                    }
                },

                async submitCancel() {
                    this.isCancelling = true;
                    try {
                        const res = await fetch(`/admin/production/harvest-lots/${this.harvestId}/cancel`, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify(this.cancelForm),
                        });
                        const data = await res.json();

                        if (data.success) {
                            if (typeof BizAlert !== "undefined") BizAlert.toast(data.message, "success");
                            window.location.reload();
                        } else {
                            if (typeof BizAlert !== "undefined") BizAlert.toast(data.message || "Error occurred",
                                "error");
                        }
                    } catch (e) {
                        if (typeof BizAlert !== "undefined") BizAlert.toast("Network error, please try again.",
                            "error");
                    } finally {
                        this.isCancelling = false;
                    }
                },
            };
        };
    </script>
@endsection
