@extends ('layouts.platform')

@section ('title', 'Add Plant')
@section ('header', 'Add Plant to Library')

@section ('content')
    <div
        class="mx-auto"
        x-data="{
            guide: [{ title: '', description: '' }],
            addRow() {
                this.guide.push({ title: '', description: '' });
            },
            removeRow(index) {
                this.guide.splice(index, 1);
                if (this.guide.length === 0) this.addRow();
            },
            loadTemplate() {
                this.guide = [
                    { title: 'Sunlight', description: '' },
                    { title: 'Watering', description: '' },
                    { title: 'About Me', description: '' },
                    { title: 'Planting Guide', description: '' },
                    { title: 'Fertilizer', description: '' },
                    { title: 'Ideal Temperature', description: '' },
                    { title: 'Origin', description: '' },
                    { title: 'Scientific Details', description: '' },
                ];
            },
        }"
    >
        {{-- Header & Actions --}}
        <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <a
                    href="{{ route('platform.plant-library.index') }}"
                    class="text-brand-600 hover:text-brand-700 mb-2 flex items-center gap-2 text-sm font-bold transition-colors"
                >
                    <i class="fa-solid fa-arrow-left-long"></i> Back to Library
                </a>
                <h1 class="text-2xl font-extrabold tracking-tight text-gray-900">Add Plant to Library</h1>
                <p class="mt-1 text-sm text-gray-500">Photos/videos can be added after saving.</p>
            </div>
            <button
                type="submit"
                form="create-plant-form"
                class="bg-brand-600 hover:bg-brand-700 shadow-brand-500/20 flex items-center justify-center gap-2 self-start rounded-lg px-6 py-2.5 text-sm font-semibold text-white shadow-md transition-all sm:self-end"
            >
                <i class="fa-solid fa-floppy-disk"></i> Save &amp; Continue
            </button>
        </div>

        {{-- Validation Errors Banner --}}
        @if ($errors->any())
            <div
                class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800 shadow-sm"
            >
                <i class="fa-solid fa-triangle-exclamation mt-0.5 shrink-0 text-xl text-red-600"></i>
                <div>
                    <span class="font-bold">Could not save. Please check the errors below:</span>
                    <ul class="mt-1.5 list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form id="create-plant-form" action="{{ route('platform.plant-library.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                {{-- Left Column --}}
                <div class="space-y-8 lg:col-span-2">
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                        <h2 class="mb-6 border-b border-gray-100 pb-4 text-lg font-bold text-gray-900">
                            Plant Details
                        </h2>

                        <div class="space-y-6">
                            {{-- Name --}}
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700"
                                    >Plant Name <span class="text-red-500">*</span></label
                                >
                                <input
                                    type="text"
                                    name="name"
                                    value="{{ old('name') }}"
                                    required
                                    placeholder="e.g. Money Plant"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all @error('name') border-red-300 ring-red-100 @enderror"
                                />
                                <p class="mt-1.5 text-xs text-gray-400">A URL-friendly slug is generated from this automatically.</p>
                                @error ('name')
                                    <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                {{-- Category Name --}}
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-gray-700"
                                        >Category <span class="text-red-500">*</span></label
                                    >
                                    <input
                                        type="text"
                                        name="category_name"
                                        value="{{ old('category_name') }}"
                                        required
                                        placeholder="e.g. Indoor Plants"
                                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all @error('category_name') border-red-300 ring-red-100 @enderror"
                                    />
                                    <p class="mt-1.5 text-xs text-gray-400">Suggested label — tenant can rename their own copy.</p>
                                    @error ('category_name')
                                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Unit --}}
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-gray-700">Unit</label>
                                    <input
                                        type="text"
                                        name="unit_short_name"
                                        value="{{ old('unit_short_name', 'pcs') }}"
                                        placeholder="e.g. pcs"
                                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all @error('unit_short_name') border-red-300 ring-red-100 @enderror"
                                    />
                                    @error ('unit_short_name')
                                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                {{-- Type --}}
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-gray-700"
                                        >Type <span class="text-red-500">*</span></label
                                    >
                                    <select
                                        name="type"
                                        required
                                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all @error('type') border-red-300 ring-red-100 @enderror"
                                    >
                                        <option
                                            value="single"
                                            {{ old('type', 'single') === 'single' ? 'selected' : '' }}
                                        >
                                            Single
                                        </option>
                                        <option value="variable" {{ old('type') === 'variable' ? 'selected' : '' }}>
                                            Variable
                                        </option>
                                    </select>
                                    @error ('type')
                                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Product Type --}}
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-gray-700"
                                        >Product Type <span class="text-red-500">*</span></label
                                    >
                                    <select
                                        name="product_type"
                                        required
                                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all @error('product_type') border-red-300 ring-red-100 @enderror"
                                    >
                                        <option
                                            value="sellable"
                                            {{ old('product_type', 'sellable') === 'sellable' ? 'selected' : '' }}
                                        >
                                            Sellable
                                        </option>
                                        <option
                                            value="catalog"
                                            {{ old('product_type') === 'catalog' ? 'selected' : '' }}
                                        >
                                            Catalog (no price)
                                        </option>
                                    </select>
                                    @error ('product_type')
                                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Description --}}
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700">Description</label>
                                <textarea
                                    name="description"
                                    rows="4"
                                    placeholder="Short description shown to tenants…"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all @error('description') border-red-300 ring-red-100 @enderror"
                                    >{{ old('description') }}</textarea
                                >
                                @error ('description')
                                    <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Care Guide Repeater --}}
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                        <div class="mb-6 flex items-center justify-between border-b border-gray-100 pb-4">
                            <div>
                                <h2 class="text-lg font-bold text-gray-900">Care Guide</h2>
                                <p class="mt-1 text-xs text-gray-500">Title + description pairs (e.g. "Watering" → "Once a week…").</p>
                            </div>
                            <button
                                type="button"
                                @click="loadTemplate()"
                                class="flex shrink-0 items-center gap-1.5 text-sm font-semibold text-amber-600 transition-colors hover:text-amber-700"
                            >
                                <i class="fa-solid fa-wand-magic-sparkles"></i> Load Template
                            </button>
                        </div>

                        <div class="space-y-4">
                            <template x-for="(row, index) in guide" :key="index">
                                <div
                                    class="flex flex-col items-start gap-3 rounded-xl border border-gray-100 bg-gray-50 p-4 sm:flex-row"
                                >
                                    <input
                                        type="text"
                                        :name="'guide_title[' + index + ']'"
                                        x-model="row.title"
                                        placeholder="Title (e.g. Watering)"
                                        class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm transition-all outline-none focus:ring-2 sm:w-1/3"
                                    />
                                    <textarea
                                        :name="'guide_description[' + index + ']'"
                                        x-model="row.description"
                                        rows="2"
                                        placeholder="Description"
                                        class="focus:ring-brand-500/20 focus:border-brand-500 w-full flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm transition-all outline-none focus:ring-2"
                                    ></textarea>
                                    <button
                                        type="button"
                                        @click="removeRow(index)"
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                        title="Remove"
                                    >
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </template>
                        </div>

                        {{-- Action button placed at the bottom right for enhanced UX flow --}}
                        <div class="mt-4 flex justify-end">
                            <button
                                type="button"
                                @click="addRow()"
                                class="text-brand-600 hover:text-brand-700 flex items-center gap-1.5 text-sm font-semibold transition-colors"
                            >
                                <i class="fa-solid fa-plus"></i> Add Row
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Right Column --}}
                <div class="space-y-8">
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                        <h2 class="mb-6 border-b border-gray-100 pb-4 text-lg font-bold text-gray-900">Visibility</h2>

                        <label
                            class="mb-6 flex cursor-pointer items-center justify-between rounded-xl border border-gray-200 p-4 transition-all hover:border-gray-300 hover:bg-gray-50"
                        >
                            <div>
                                <p class="text-sm font-bold text-gray-900">Active Status</p>
                                <p class="mt-1 text-xs text-gray-500">Only active plants are visible to tenants.</p>
                            </div>
                            <div class="relative ml-4 inline-flex shrink-0 cursor-pointer items-center">
                                <input type="hidden" name="is_active" value="0" />
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    class="peer sr-only"
                                    {{ old('is_active', true) ? 'checked' : '' }}
                                />
                                <div
                                    class="peer peer-checked:bg-brand-600 h-6 w-11 rounded-full bg-gray-200 peer-focus:outline-none after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white"
                                ></div>
                            </div>
                        </label>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-gray-700">Display Order</label>
                            <input
                                type="number"
                                name="sort_order"
                                value="{{ old('sort_order', 0) }}"
                                placeholder="0"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all @error('sort_order') border-red-300 ring-red-100 @enderror"
                            />
                            <p class="mt-1.5 text-xs text-gray-400">Lower numbers display first.</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-blue-100 bg-blue-50 p-6 text-sm text-blue-800">
                        <i class="fa-solid fa-circle-info mr-1.5"></i>
                        Photos and YouTube videos can be added right after you save this plant.
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
