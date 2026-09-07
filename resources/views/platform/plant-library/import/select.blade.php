@extends ('layouts.platform')

@section ('title', 'Select Plants')
@section ('header', 'Import Plants — '.$company->name)

@section ('content')
    <div
        class="mx-auto max-w-6xl pb-32"
        x-data="{
            selected: {},
            types: {},
            isSellable(id) {
                return (this.types[id] ?? 'catalog') === 'sellable';
            },
            toggle(id) {
                this.selected[id] = !this.selected[id];
            },
            selectedCount() {
                return Object.values(this.selected).filter(Boolean).length;
            },
        }"
    >
        <div class="mb-8">
            <a
                href="{{ route('platform.plant-library-import.index') }}"
                class="text-brand-600 hover:text-brand-700 mb-2 flex items-center gap-2 text-sm font-bold transition-colors"
            >
                <i class="fa-solid fa-arrow-left-long"></i> Back to Companies
            </a>
            <h1 class="text-2xl font-extrabold tracking-tight text-gray-900">Select Plants for {{ $company->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">Default is <strong>Catalog</strong> (no price needed). Switch to <strong>Sellable</strong> only when this tenant needs a real selling price.</p>
        </div>

        @if (session('success'))
            <div
                class="mb-6 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800 shadow-sm"
            >
                <i class="fa-solid fa-circle-check shrink-0 text-xl text-green-600"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div
                class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800 shadow-sm"
            >
                <i class="fa-solid fa-triangle-exclamation mt-0.5 shrink-0 text-xl text-red-600"></i>
                <div>
                    <span class="font-bold">Could not import. Please check the errors below:</span>
                    <ul class="mt-1.5 list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form id="import-form" action="{{ route('platform.plant-library-import.store', $company) }}" method="POST">
            @csrf

            @if ($plants->isEmpty())
                <div class="rounded-2xl border border-gray-200 bg-white p-12 text-center text-gray-400 shadow-sm">
                    <i class="fa-solid fa-book-open mb-3 text-3xl"></i>
                    <p class="text-sm">Library is empty — add plants to the Master Library first.</p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($plants as $plant)
                        @php $primary = $plant->primary_media; @endphp
                        <div
                            class="overflow-hidden rounded-2xl border-2 bg-white shadow-sm transition-all"
                            :class="selected[{{ $plant->id }}] ? 'border-brand-500 ring-2 ring-brand-500/10' : 'border-gray-200'"
                        >
                            {{-- Selectable header --}}
                            <label class="flex cursor-pointer items-start gap-3 p-4">
                                <input
                                    type="checkbox"
                                    name="plant_ids[]"
                                    value="{{ $plant->id }}"
                                    x-model="selected[{{ $plant->id }}]"
                                    class="text-brand-600 focus:ring-brand-500 mt-1 h-4 w-4 shrink-0 rounded border-gray-300"
                                />

                                <div
                                    class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-100 bg-gray-50"
                                >
                                    @if ($primary && $primary->media_type === 'image')
                                        <img
                                            src="{{ $primary->media_url }}"
                                            alt="{{ $plant->name }}"
                                            class="h-full w-full object-cover"
                                        />
                                    @else
                                        <i class="fa-solid fa-seedling text-xl text-gray-300"></i>
                                    @endif
                                </div>

                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-gray-900">{{ $plant->name }}</p>
                                    <p class="truncate text-xs text-gray-400">{{ $plant->category_name ?? '—' }}</p>
                                </div>
                            </label>

                            {{-- Type + Price/Cost — only meaningful once selected --}}
                            <div
                                x-show="selected[{{ $plant->id }}]"
                                x-cloak
                                class="space-y-3 border-t border-gray-100 px-4 pt-4 pb-4"
                            >
                                <div class="flex gap-2">
                                    <label
                                        class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-lg border-2 px-2 py-2 text-xs font-semibold transition-colors"
                                        :class="!isSellable({{ $plant->id }}) ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-gray-200 text-gray-500 hover:bg-gray-50'"
                                    >
                                        <input
                                            type="radio"
                                            name="product_type[{{ $plant->id }}]"
                                            value="catalog"
                                            x-model="types[{{ $plant->id }}]"
                                            class="hidden"
                                        />
                                        Catalog
                                    </label>
                                    <label
                                        class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-lg border-2 px-2 py-2 text-xs font-semibold transition-colors"
                                        :class="isSellable({{ $plant->id }}) ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-gray-200 text-gray-500 hover:bg-gray-50'"
                                    >
                                        <input
                                            type="radio"
                                            name="product_type[{{ $plant->id }}]"
                                            value="sellable"
                                            x-model="types[{{ $plant->id }}]"
                                            class="hidden"
                                        />
                                        Sellable
                                    </label>
                                </div>

                                <div x-show="isSellable({{ $plant->id }})" x-cloak class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label
                                            class="mb-1 block text-[10px] font-bold tracking-wide text-gray-500 uppercase"
                                            >Price</label
                                        >
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            name="price[{{ $plant->id }}]"
                                            :required="isSellable({{ $plant->id }})"
                                            class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-sm transition-all outline-none focus:ring-2"
                                            placeholder="0.00"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="mb-1 block text-[10px] font-bold tracking-wide text-gray-500 uppercase"
                                            >Cost</label
                                        >
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            name="cost[{{ $plant->id }}]"
                                            :required="isSellable({{ $plant->id }})"
                                            class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-sm transition-all outline-none focus:ring-2"
                                            placeholder="0.00"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </form>

        {{-- Sticky action bar --}}
        <div
            class="fixed right-0 bottom-0 left-0 z-30 border-t border-gray-200 bg-white px-6 py-4 shadow-[0_-4px_16px_rgba(0,0,0,0.06)] lg:left-64"
        >
            <div class="mx-auto flex max-w-6xl items-center justify-between">
                <p class="text-sm text-gray-600">
                    <span class="font-bold text-gray-900" x-text="selectedCount()">0</span> plant(s) selected
                </p>
                <button
                    type="submit"
                    form="import-form"
                    :disabled="selectedCount() === 0"
                    class="bg-brand-600 hover:bg-brand-700 shadow-brand-500/20 flex items-center gap-2 rounded-lg px-6 py-2.5 text-sm font-semibold text-white shadow-md transition-all disabled:cursor-not-allowed disabled:bg-gray-300"
                >
                    <i class="fa-solid fa-file-import"></i> Import Selected
                </button>
            </div>
        </div>
    </div>
@endsection
