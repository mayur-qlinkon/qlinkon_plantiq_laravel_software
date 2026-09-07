@extends ('layouts.admin')

@section ('title', 'Create Plant Batch - PlantIQ')

@section ('header-title')
    <div class="flex w-full items-center justify-between">
        <div class="flex items-center gap-3">
            <div>
                <h1 class="text-lg font-bold tracking-tight text-gray-900">New Plant Batch</h1>
                <p class="text-[12px] font-medium text-gray-500">Register a new group of plants entering the nursery</p>
            </div>
        </div>
    </div>
@endsection

@section ('content')
    <div class="mx-auto w-full" x-data="plantBatchCreate()">
        {{-- Session Warnings --}}
        @if (session('warning'))
            <div
                class="mb-5 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800"
            >
                <i data-lucide="alert-triangle" class="mt-0.5 h-4 w-4 shrink-0 text-amber-500"></i>
                {{ session('warning') }}
            </div>
        @endif

        @if ($errors->any())
            <div
                class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
            >
                <i data-lucide="circle-x" class="mt-0.5 h-4 w-4 shrink-0 text-red-500"></i>
                <div>
                    <p class="font-bold">Please fix the following mistakes:</p>
                    <ul class="mt-1 list-disc pl-4 font-medium">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.production.plant-batches.store') }}" @submit.prevent="submitForm">
            @csrf

            {{-- Fixed for the life of this rendered page: a browser refresh,
                 back-and-resubmit or double click all replay the same value,
                 which the unique index on the batches table rejects.
                 old() keeps it stable across a validation bounce, where nothing
                 was inserted and the key is still unused. --}}
            <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) Str::uuid()) }}">

            {{-- ── Section 1: Source ─────────────────────────────────── --}}
            <div class="mb-4 rounded-2xl border border-gray-100 bg-white shadow-sm">
                <div class="flex items-center gap-3 border-b border-gray-100 px-6 py-4">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                        <i data-lucide="git-merge" class="h-3.5 w-3.5"></i>
                    </div>
                    <h2 class="text-sm font-bold text-gray-900">Batch Source</h2>
                    <span class="ml-auto text-[11px] font-semibold tracking-wide text-gray-400 uppercase"
                        >Step 1 of 3</span
                    >
                </div>

                <div class="space-y-5 px-6 py-5">
                    {{-- Source Type --}}
                    <div>
                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700">
                            Source Type <span class="text-red-500">*</span>
                        </label>
                        <p class="mb-3 text-[12px] text-gray-500">How are these plants entering the nursery?</p>

                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                            @foreach ($sourceTypes as $source)
                                <label
                                    class="relative flex cursor-pointer items-center gap-2.5 rounded-xl border px-3.5 py-3 text-sm font-semibold transition-all"
                                    :class="sourceType === '{{ $source->value }}'
                                        ? 'border-indigo-400 bg-indigo-50 text-indigo-700 shadow-sm'
                                        : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50'"
                                >
                                    <input
                                        type="radio"
                                        name="source_type"
                                        value="{{ $source->value }}"
                                        x-model="sourceType"
                                        @change="onSourceTypeChange"
                                        class="sr-only"
                                        {{ old('source_type', $prefill['source_type'] ?? '') === $source->value ? 'checked' : '' }}
                                    />
                                    <span
                                        class="h-2 w-2 rounded-full transition-colors"
                                        :class="sourceType === '{{ $source->value }}' ? 'bg-indigo-500' : 'bg-gray-300'"
                                    ></span>
                                    {{ $source->label() }}
                                </label>
                            @endforeach
                        </div>
                        @error ('source_type')
                            <p class="mt-1.5 text-[12px] font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Source Reference — searchable picker, replaces raw Line Item ID input --}}
                    <div x-show="showSourceRef" x-transition x-data="{ open: false }">
                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700">
                            <span x-text="sourceType === 'production_plan' ? 'Production Plan' : 'Purchase'"></span>
                            <span class="text-red-500">*</span>
                        </label>
                        <p
                            class="mb-2 text-[12px] text-gray-500"
                            x-text="
                                sourceType === 'production_plan'
                                    ? 'Search by plan title or plant name.'
                                    : 'Search by purchase number or plant name.'
                            "
                        ></p>

                        {{-- Hidden input — actual value that gets submitted --}}
                        <input
                            type="hidden"
                            name="source_reference_id"
                            x-model="sourceReferenceId"
                            value="{{ old('source_reference_id', $prefill['source_reference_id'] ?? '') }}"
                        />

                        {{-- Dropdown Container --}}
                        <div class="relative" @click.outside="open = false">
                            <div class="relative">
                                <input
                                    type="text"
                                    x-model="sourceSearchTerm"
                                    @focus="
                                        openSourceSearch();
                                        open = true;
                                    "
                                    @input.debounce.300ms="searchSourceOptions()"
                                    :disabled="productLocked"
                                    placeholder="Click to see recent, or type to search…"
                                    autocomplete="off"
                                    class="focus:border-brand-500 focus:ring-brand-500/10 w-full rounded-xl border border-gray-200 bg-white py-2.5 pr-10 pl-4 text-sm font-medium text-gray-900 shadow-sm transition-all outline-none focus:ring-2 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500"
                                />
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <i
                                        data-lucide="loader-2"
                                        class="h-4 w-4 animate-spin text-gray-400"
                                        x-show="sourceSearchLoading"
                                        x-cloak
                                    ></i>
                                    <i
                                        data-lucide="search"
                                        class="h-4 w-4 text-gray-400"
                                        x-show="!sourceSearchLoading"
                                    ></i>
                                </div>
                            </div>

                            {{-- Dropdown panel --}}
                            <div
                                x-show="open"
                                x-transition
                                x-cloak
                                class="absolute z-20 mt-1.5 max-h-72 w-full overflow-y-auto rounded-xl border border-gray-100 bg-white py-1.5 shadow-lg"
                            >
                                <template x-if="!sourceSearchLoading && sourceOptions.length === 0">
                                    <p class="px-4 py-3 text-[13px] text-gray-400">No results found.</p>
                                </template>
                                <template x-for="option in sourceOptions" :key="option.id">
                                    <button
                                        type="button"
                                        @click="
                                            selectSourceOption(option);
                                            open = false;
                                        "
                                        class="flex w-full flex-col items-start gap-0.5 px-4 py-2.5 text-left transition-colors hover:bg-gray-50"
                                    >
                                        <span class="text-[13px] font-bold text-gray-900" x-text="option.label"></span>
                                        <span
                                            class="text-[11px] font-medium text-gray-500"
                                            x-text="option.sublabel"
                                        ></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div
                            x-show="productLocked"
                            x-cloak
                            class="mt-1.5 flex items-center gap-1 text-[11px] font-semibold text-amber-600"
                        >
                            <i data-lucide="lock" class="h-3 w-3"></i> Locked — prefilled from source
                        </div>

                        {{-- Prefill success label --}}
                        <div
                            x-show="sourceLabel"
                            x-transition
                            x-cloak
                            class="mt-2 flex items-center gap-1.5 text-[12px] font-semibold text-emerald-700"
                        >
                            <i data-lucide="check-circle" class="h-3.5 w-3.5"></i>
                            <span x-text="sourceLabel"></span>
                        </div>

                        {{-- Prefill error --}}
                        <div
                            x-show="prefillError"
                            x-transition
                            x-cloak
                            class="mt-2 flex items-center gap-1.5 text-[12px] font-semibold text-red-600"
                        >
                            <i data-lucide="alert-circle" class="h-3.5 w-3.5"></i>
                            <span x-text="prefillError"></span>
                        </div>

                        @error ('source_reference_id')
                            <p class="mt-1.5 text-[12px] font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- ── Section 2: Plant Details ───────────────────────────── --}}
            <div class="mb-4 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                <div class="flex items-center gap-3 border-b border-gray-100 px-6 py-4">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                        <i data-lucide="sprout" class="h-3.5 w-3.5"></i>
                    </div>
                    <h2 class="text-sm font-bold text-gray-900">Plant Details</h2>
                    <span class="ml-auto text-[11px] font-semibold tracking-wide text-gray-400 uppercase"
                        >Step 2 of 3</span
                    >
                </div>

                <div class="space-y-5 px-6 py-5">
                    {{-- Plant --}}
                    <div>
                        <label for="product_id" class="mb-1.5 block text-[13px] font-bold text-gray-700">
                            Plant <span class="text-red-500">*</span>
                        </label>

                        <input type="hidden" name="product_id" x-model="productId" />

                        {{-- Locked state — prefilled from source --}}
                        <div
                            x-show="productLocked"
                            x-cloak
                            class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-bold text-gray-700"
                        >
                            <span x-text="productName || 'Selected plant'"></span>
                            <span class="flex items-center gap-1 text-[11px] font-semibold text-amber-600">
                                <i data-lucide="lock" class="h-3 w-3"></i> Locked — prefilled from source
                            </span>
                        </div>

                        {{-- Selected confirmation — shown once a plant is picked (manual, not locked) --}}
                        <div
                            x-show="!productLocked && productId && !plantDropdownOpen"
                            x-cloak
                            class="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5"
                        >
                            <span class="flex items-center gap-1.5 text-[13px] font-bold text-emerald-700">
                                <i data-lucide="check-circle-2" class="h-3.5 w-3.5"></i>
                                <span x-text="productName"></span>
                            </span>
                            <button
                                type="button"
                                @click="
                                    plantSearchTerm = '';
                                    plantDropdownOpen = true;
                                    openPlantSearch();
                                    $nextTick(() => $refs.plantSearchInput.focus());
                                "
                                class="text-[11px] font-semibold text-emerald-700 underline decoration-dotted hover:text-emerald-900"
                            >
                                Change
                            </button>
                        </div>

                        {{-- Searchable picker — manual selection --}}
                        <div
                            x-show="!productLocked && (!productId || plantDropdownOpen)"
                            x-cloak
                            class="relative"
                            @click.outside="plantDropdownOpen = false"
                        >
                            <div class="relative">
                                <input
                                    type="text"
                                    x-ref="plantSearchInput"
                                    x-model="plantSearchTerm"
                                    @focus="
                                        openPlantSearch();
                                        plantDropdownOpen = true;
                                    "
                                    @input.debounce.300ms="searchPlantOptions()"
                                    placeholder="Click to see recent, or type to search…"
                                    autocomplete="off"
                                    class="focus:border-brand-500 focus:ring-brand-500/10 w-full rounded-xl border border-gray-200 bg-white py-2.5 pr-10 pl-4 text-sm font-medium text-gray-900 shadow-sm transition-all outline-none focus:ring-2"
                                />
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <i
                                        data-lucide="loader-2"
                                        class="h-4 w-4 animate-spin text-gray-400"
                                        x-show="plantSearchLoading"
                                        x-cloak
                                    ></i>
                                    <i
                                        data-lucide="search"
                                        class="h-4 w-4 text-gray-400"
                                        x-show="!plantSearchLoading"
                                    ></i>
                                </div>
                            </div>

                            <div
                                x-show="plantDropdownOpen"
                                x-cloak
                                class="absolute z-20 mt-1.5 max-h-72 w-full overflow-y-auto rounded-xl border border-gray-100 bg-white py-1.5 shadow-lg"
                            >
                                <template x-if="!plantSearchLoading && plantOptions.length === 0">
                                    <p class="px-4 py-3 text-[13px] text-gray-400">No plants found.</p>
                                </template>
                                <template x-for="option in plantOptions" :key="option.id">
                                    <button
                                        type="button"
                                        @click="
                                            selectPlantOption(option);
                                            plantDropdownOpen = false;
                                        "
                                        class="flex w-full items-center justify-between px-4 py-2.5 text-left transition-colors hover:bg-gray-50"
                                    >
                                        <span class="text-[13px] font-bold text-gray-900" x-text="option.label"></span>
                                        <span
                                            class="text-[11px] font-medium text-gray-500"
                                            x-text="option.sublabel"
                                        ></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        @error ('product_id')
                            <p class="mt-1.5 text-[12px] font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- SKU / Variant — only shown for 'variable' type products.
                         'single' products have exactly one SKU, auto-resolved server-side. --}}
                    <div x-show="isVariableProduct" x-cloak>
                        <label for="product_sku_id" class="mb-1.5 block text-[13px] font-bold text-gray-700">
                            SKU / Variant <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select
                                id="product_sku_id"
                                name="product_sku_id"
                                x-model="productSkuId"
                                @change="onSkuChange()"
                                class="focus:border-brand-500 focus:ring-brand-500/10 ui-select w-full rounded-xl border border-gray-200 bg-white py-2.5 pr-8 pl-4 text-sm font-medium text-gray-900 shadow-sm transition-all outline-none focus:ring-2"
                            >
                                <option value="">Select a variant / SKU…</option>
                                <template x-for="skuOption in availableSkus" :key="skuOption.id">
                                    <option
                                        :value="skuOption.id"
                                        x-text="skuOption.sku"
                                        :selected="skuOption.id == productSkuId"
                                    ></option>
                                </template>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                <i data-lucide="chevron-down" class="h-4 w-4 text-gray-400"></i>
                            </div>
                        </div>
                        @error ('product_sku_id')
                            <p class="mt-1.5 text-[12px] font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Quantity --}}
                    <div>
                        <label for="initial_quantity" class="mb-1.5 block text-[13px] font-bold text-gray-700">
                            Initial Quantity <span class="text-red-500">*</span>
                        </label>
                        <p class="mb-2 text-[12px] text-gray-500">Total number of plants physically entering the nursery. This value is permanent after any loss or harvest.</p>
                        <input
                            type="hidden"
                            name="initial_quantity"
                            x-model="initialQuantity"
                            x-show="quantityLocked"
                            x-cloak
                        />
                        <input
                            type="number"
                            id="initial_quantity"
                            :name="quantityLocked ? '' : 'initial_quantity'"
                            x-model="initialQuantity"
                            :disabled="quantityLocked"
                            min="1"
                            placeholder="e.g. 500"
                            class="focus:border-brand-500 focus:ring-brand-500/10 w-48 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 shadow-sm transition-all outline-none focus:ring-2 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500"
                            value="{{ old('initial_quantity', $prefill['initial_quantity'] ?? '') }}"
                        />
                        <div
                            x-show="quantityLocked"
                            x-cloak
                            class="mt-1.5 flex items-center gap-1 text-[11px] font-semibold text-amber-600"
                        >
                            <i data-lucide="lock" class="h-3 w-3"></i> Locked — prefilled from source
                        </div>
                        @error ('initial_quantity')
                            <p class="mt-1.5 text-[12px] font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- ── Section 3: Batch Info ──────────────────────────────── --}}
            <div class="mb-6 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                <div class="flex items-center gap-3 border-b border-gray-100 px-6 py-4">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                        <i data-lucide="clipboard-list" class="h-3.5 w-3.5"></i>
                    </div>
                    <h2 class="text-sm font-bold text-gray-900">Batch Info</h2>
                    <span class="ml-auto text-[11px] font-semibold tracking-wide text-gray-400 uppercase"
                        >Step 3 of 3</span
                    >
                </div>

                <div class="space-y-5 px-6 py-5">
                    {{-- Batch Start Datetime --}}
                    <div>
                        <label for="batch_start_datetime" class="mb-1.5 block text-[13px] font-bold text-gray-700">
                            Batch Start Date/Time <span class="text-red-500">*</span>
                        </label>
                        <p class="mb-2 text-[12px] text-gray-500">When plants physically entered the nursery. You can backfill a past date/time — batch age is calculated from this.</p>
                        <input
                            type="datetime-local"
                            id="batch_start_datetime"
                            name="batch_start_datetime"
                            class="focus:border-brand-500 focus:ring-brand-500/10 w-64 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 shadow-sm transition-all outline-none focus:ring-2"
                            value="{{ old('batch_start_datetime', $prefill['batch_start_datetime'] ?? now()->format('Y-m-d\TH:i')) }}"
                        />
                        @error ('batch_start_datetime')
                            <p class="mt-1.5 text-[12px] font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label for="notes" class="mb-1.5 block text-[13px] font-bold text-gray-700">
                            Notes <span class="font-normal text-gray-400">(optional)</span>
                        </label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="3"
                            placeholder="Any relevant details about this batch — supplier, variety, condition on arrival, etc."
                            class="focus:border-brand-500 focus:ring-brand-500/10 w-full resize-none rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition-all outline-none focus:ring-2"
                            >{{ old('notes') }}</textarea
                        >
                        @error ('notes')
                            <p class="mt-1.5 text-[12px] font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- ── Actions ───────────────────────────────────────────── --}}
            <div class="flex items-center justify-between">
                <a
                    href="{{ route('admin.production.plant-batches.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-600 shadow-sm transition-all hover:bg-gray-50 hover:text-gray-900"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    :disabled="submitting"
                    class="bg-brand-600 hover:bg-brand-700 inline-flex items-center gap-2 rounded-xl px-6 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:shadow active:scale-95 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <i data-lucide="loader-2" class="h-4 w-4 animate-spin" x-show="submitting" x-cloak></i>
                    <i data-lucide="package-plus" class="h-4 w-4" x-show="!submitting"></i>
                    <span x-text="submitting ? 'Creating…' : 'Create Batch'">Create Batch</span>
                </button>
            </div>
        </form>
    </div>
@endsection

@push ('scripts')
    <script>
        function plantBatchCreate() {
            return {
                // Source
                sourceType: "{{ old('source_type', $prefill['source_type'] ?? '') }}",
                sourceReferenceId: "{{ old('source_reference_id', $prefill['source_reference_id'] ?? '') }}",
                sourceLabel: "{{ $prefill['source_label'] ?? '' }}",
                prefillLoading: false,
                prefillError: "",

                // Source search (searchable picker)
                sourceSearchTerm: "",
                sourceOptions: [],
                sourceSearchLoading: false,

                // Product
                productId: "{{ old('product_id', $prefill['product_id'] ?? '') }}",
                productLocked: false,

                // Product → type/SKU/stock lookup, built server-side once.
                productsData: {{
            Illuminate\Support\Js::from($products->mapWithKeys(fn ($p) => [
                               $p->id => [
                                   'type' => $p->type,
                                   'skus' => $p->skus->map(fn ($s) => [
                                       'id'    => $s->id,
                                       'sku'   => $s->sku,
                                       'stock' => (int) ($stockBySku[$s->id] ?? 0),
                                   ])->values(),
                               ],
                           ]))
        }},
                productSkuId: "{{ old('product_sku_id', $prefill['product_sku_id'] ?? '') }}",

                // Plant search (recent 4 / search)
                productName: "{{ $prefill['product_name'] ?? '' }}",
                plantSearchTerm: "",
                plantOptions: [],
                plantSearchLoading: false,
                plantDropdownOpen: false,

                get isVariableProduct() {
                    return this.productsData[this.productId]?.type === "variable";
                },

                get availableSkus() {
                    return this.productsData[this.productId]?.skus ?? [];
                },

                // Quantity
                initialQuantity: "{{ old('initial_quantity', $prefill['initial_quantity'] ?? '') }}",
                quantityLocked: false,

                // Form
                submitting: false,

                // Whether to show source reference input
                get showSourceRef() {
                    return ["production_plan", "purchase"].includes(this.sourceType);
                },

                init() {
                    // If server-side prefill was injected (from query string on page load),
                    // lock the fields that came from the source.
                    @if (!empty($prefill))
                    this.productLocked = true;
                    this.quantityLocked = true;
                    @endif
                },

                onSourceTypeChange() {
                    // Clear prefill when user switches source type
                    this.sourceLabel = "";
                    this.prefillError = "";
                    this.sourceReferenceId = "";
                    this.sourceSearchTerm = "";
                    this.sourceOptions = [];
                    this.productLocked = false;
                    this.quantityLocked = false;

                    // Full reset — a plant/quantity picked (or prefilled) under the
                    // previous source type must not leak into the newly chosen one.
                    // Without this, switching Plan → Opening Stock kept the old
                    // locked plant + quantity sitting in an "unlocked" state.
                    this.productId = "";
                    this.productName = "";
                    this.productSkuId = "";
                    this.initialQuantity = "";
                    this.plantSearchTerm = "";
                    this.plantOptions = [];
                    this.plantDropdownOpen = false;
                },

                async openSourceSearch() {
                    // Click pe agar kuch search nahi kiya to recent 5 dikhao
                    if (this.sourceOptions.length === 0) {
                        await this.searchSourceOptions();
                    }
                },

                async searchSourceOptions() {
                    if (!this.sourceType) return;

                    this.sourceSearchLoading = true;

                    try {
                        const params = new URLSearchParams({ source_type: this.sourceType });
                        if (this.sourceSearchTerm) params.set("q", this.sourceSearchTerm);

                        const res = await fetch(`{{ route('admin.production.plant-batches.source-options') }}?${params}`, {
                            headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
                        });

                        const json = await res.json();
                        this.sourceOptions = json.options ?? [];
                    } catch (err) {
                        this.sourceOptions = [];
                    } finally {
                        this.sourceSearchLoading = false;
                    }
                },

                selectSourceOption(option) {
                    this.sourceReferenceId = String(option.id);
                    this.sourceSearchTerm = option.label;
                    this.fetchPrefill();
                },

                async fetchPrefill() {
                    if (!this.sourceReferenceId || !this.sourceType) return;

                    this.prefillLoading = true;
                    this.prefillError = "";
                    this.sourceLabel = "";

                    try {
                        const params = new URLSearchParams({
                            source_type: this.sourceType,
                            source_reference_id: this.sourceReferenceId,
                        });

                        const res = await fetch(`{{ route('admin.production.plant-batches.prefill') }}?${params}`, {
                            headers: {
                                "X-Requested-With": "XMLHttpRequest",
                                Accept: "application/json",
                            },
                        });

                        const json = await res.json();

                        if (!res.ok) {
                            this.prefillError = json.message || "Could not load source data.";
                            return;
                        }

                        const data = json.prefill ?? {};

                        if (data.product_id) {
                            this.productId = String(data.product_id);
                            this.productName = data.product_name || "";
                            this.productLocked = true;
                        }

                        if (data.initial_quantity) {
                            this.initialQuantity = data.initial_quantity;
                            this.quantityLocked = true;
                        }

                        if (data.source_label) {
                            this.sourceLabel = data.source_label;
                        }
                    } catch (err) {
                        this.prefillError = "Network error. Please try again.";
                    } finally {
                        this.prefillLoading = false;
                    }
                },

                async openPlantSearch() {
                    if (this.plantOptions.length === 0) {
                        await this.searchPlantOptions();
                    }
                },

                async searchPlantOptions() {
                    this.plantSearchLoading = true;

                    try {
                        const params = new URLSearchParams();
                        if (this.plantSearchTerm) params.set("q", this.plantSearchTerm);

                        const res = await fetch(`{{ route('admin.production.plant-batches.plant-options') }}?${params}`, {
                            headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
                        });

                        const json = await res.json();
                        this.plantOptions = json.options ?? [];
                    } catch (err) {
                        this.plantOptions = [];
                    } finally {
                        this.plantSearchLoading = false;
                    }
                },

                selectPlantOption(option) {
                    this.productId = String(option.id);
                    this.productName = option.label;
                    this.plantSearchTerm = "";
                    this.productSkuId = "";

                    // Auto-fill from current stock — only when not locked, and always editable.
                    if (!this.quantityLocked) {
                        this.initialQuantity = option.stock ?? 0;
                    }
                },

                onSkuChange() {
                    if (this.quantityLocked || !this.productSkuId) return;

                    const sku = this.availableSkus.find((s) => s.id == this.productSkuId);
                    if (sku) {
                        this.initialQuantity = sku.stock ?? 0;
                    }
                },

                submitForm(e) {
                    this.submitting = true;
                    // Allow the native form POST to proceed
                    e.$el ? e.$el.submit() : e.target.submit();
                },
            };
        }
    </script>
@endpush
