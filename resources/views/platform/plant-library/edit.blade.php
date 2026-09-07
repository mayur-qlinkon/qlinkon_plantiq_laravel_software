@extends ('layouts.platform')

@section ('title', 'Edit Plant')
@section ('header', 'Edit Plant Library Entry')

@section ('content')
    <div
        class="mx-auto"
        x-data="{
        guide: @js($plantLibrary->product_guide ?: [['title' => '', 'description' => '']]),
        addRow() { this.guide.push({ title: '', description: '' }); },
        removeRow(index) { this.guide.splice(index, 1); if (this.guide.length === 0) this.addRow(); },
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
        }
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
                <h1 class="text-2xl font-extrabold tracking-tight text-gray-900">{{ $plantLibrary->name }}</h1>
                <p class="mt-1 text-sm text-gray-500">Slug: {{ $plantLibrary->slug }}</p>
            </div>
            <button
                type="submit"
                form="edit-plant-form"
                class="bg-brand-600 hover:bg-brand-700 shadow-brand-500/20 flex items-center justify-center gap-2 self-start rounded-lg px-6 py-2.5 text-sm font-semibold text-white shadow-md transition-all sm:self-end"
            >
                <i class="fa-solid fa-floppy-disk"></i> Save Changes
            </button>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div
                class="mb-6 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800 shadow-sm"
            >
                <i class="fa-solid fa-circle-check shrink-0 text-xl text-green-600"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        @endif

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

        <form id="edit-plant-form" action="{{ route('platform.plant-library.update', $plantLibrary) }}" method="POST">
            @csrf
            @method ('PUT')

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
                                    value="{{ old('name', $plantLibrary->name) }}"
                                    required
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all @error('name') border-red-300 ring-red-100 @enderror"
                                />
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
                                        value="{{ old('category_name', $plantLibrary->category_name) }}"
                                        required
                                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all @error('category_name') border-red-300 ring-red-100 @enderror"
                                    />
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
                                        value="{{ old('unit_short_name', $plantLibrary->unit_short_name) }}"
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
                                            {{ old('type', $plantLibrary->type) === 'single' ? 'selected' : '' }}
                                        >
                                            Single
                                        </option>
                                        <option
                                            value="variable"
                                            {{ old('type', $plantLibrary->type) === 'variable' ? 'selected' : '' }}
                                        >
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
                                            {{ old('product_type', $plantLibrary->product_type) === 'sellable' ? 'selected' : '' }}
                                        >
                                            Sellable
                                        </option>
                                        <option
                                            value="catalog"
                                            {{ old('product_type', $plantLibrary->product_type) === 'catalog' ? 'selected' : '' }}
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
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all @error('description') border-red-300 ring-red-100 @enderror"
                                    >{{ old('description', $plantLibrary->description) }}</textarea
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
                                    {{ old('is_active', $plantLibrary->is_active) ? 'checked' : '' }}
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
                                value="{{ old('sort_order', $plantLibrary->sort_order) }}"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all @error('sort_order') border-red-300 ring-red-100 @enderror"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </form>

        {{-- Danger Zone — intentionally OUTSIDE edit-plant-form above.       --}}
        {{-- A <form> can never be nested inside another <form> in HTML;     --}}
        {{-- doing so causes browsers to merge them, so a "Delete Plant"     --}}
        {{-- click can end up submitting the wrong action. Keep it separate. --}}
        <div class="mt-8 max-w-sm rounded-2xl border border-red-100 bg-white p-6 shadow-sm">
            <h2 class="mb-3 text-sm font-bold text-red-600">Danger Zone</h2>
            <form
                action="{{ route('platform.plant-library.destroy', $plantLibrary) }}"
                method="POST"
                onsubmit="return confirm('Delete this plant permanently? This cannot be undone.');"
            >
                @csrf
                @method ('DELETE')
                <button
                    type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-lg border border-red-200 bg-red-50 py-2.5 text-sm font-semibold text-red-600 transition-colors hover:bg-red-100"
                >
                    <i class="fa-solid fa-trash-can"></i> Delete Plant
                </button>
            </form>
        </div>

        {{-- ────────────────────────────────────────────────────────────── --}}
        {{-- Media Management (separate from the form above — needs the    --}}
        {{-- record to already exist, and file uploads don't mix well      --}}
        {{-- with the Alpine-driven text repeater above).                  --}}
        {{-- ────────────────────────────────────────────────────────────── --}}
        <div class="mt-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="mb-6 border-b border-gray-100 pb-4 text-lg font-bold text-gray-900">Photos &amp; Videos</h2>

            {{-- Existing Media Grid --}}
            @if ($plantLibrary->media->isNotEmpty())
                <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($plantLibrary->media as $media)
                        <div
                            class="group relative aspect-square overflow-hidden rounded-xl border border-gray-200 bg-gray-50"
                        >
                            @if ($media->media_type === 'image')
                                <img
                                    src="{{ $media->media_url }}"
                                    alt="Plant media"
                                    class="h-full w-full object-cover"
                                />
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-red-50">
                                    <i class="fa-brands fa-youtube text-4xl text-red-500"></i>
                                </div>
                            @endif

                            @if ($media->is_primary)
                                <span
                                    class="bg-brand-600 absolute top-2 left-2 rounded px-2 py-1 text-[9px] font-bold tracking-wider text-white uppercase shadow-sm"
                                    >Primary</span
                                >
                            @endif

                            <div
                                class="absolute inset-0 flex items-center justify-center gap-2 bg-black/60 opacity-0 transition-opacity group-hover:opacity-100"
                            >
                                @if (!$media->is_primary)
                                    <form
                                        action="{{ route('platform.plant-library.media.primary', [$plantLibrary, $media]) }}"
                                        method="POST"
                                    >
                                        @csrf
                                        <button
                                            type="submit"
                                            class="text-brand-600 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 hover:bg-white"
                                            title="Set as primary"
                                        >
                                            <i class="fa-solid fa-star text-xs"></i>
                                        </button>
                                    </form>
                                @endif
                                <form
                                    action="{{ route('platform.plant-library.media.destroy', [$plantLibrary, $media]) }}"
                                    method="POST"
                                    onsubmit="return confirm('Remove this media item?');"
                                >
                                    @csrf
                                    @method ('DELETE')
                                    <button
                                        type="submit"
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-red-600 hover:bg-white"
                                        title="Delete"
                                    >
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mb-8 text-sm text-gray-400">No photos or videos added yet.</p>
            @endif

            {{-- Add Media Form --}}
            <div class="border-t border-gray-100 pt-6" x-data="{ mediaType: 'image' }">
                <h3 class="mb-4 text-sm font-bold text-gray-900">Add Photo or Video</h3>

                <form
                    action="{{ route('platform.plant-library.media.store', $plantLibrary) }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="space-y-4"
                >
                    @csrf

                    {{-- Type Toggle --}}
                    <div class="flex gap-3">
                        <label
                            class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg border-2 px-4 py-2.5 text-sm font-semibold transition-colors"
                            :class="mediaType === 'image'
                                ? 'border-brand-500 bg-brand-50 text-brand-700'
                                : 'border-gray-200 text-gray-500 hover:bg-gray-50'"
                        >
                            <input type="radio" name="media_type" value="image" x-model="mediaType" class="hidden" />
                            <i class="fa-solid fa-image"></i> Image
                        </label>
                        <label
                            class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg border-2 px-4 py-2.5 text-sm font-semibold transition-colors"
                            :class="mediaType === 'youtube'
                                ? 'border-brand-500 bg-brand-50 text-brand-700'
                                : 'border-gray-200 text-gray-500 hover:bg-gray-50'"
                        >
                            <input type="radio" name="media_type" value="youtube" x-model="mediaType" class="hidden" />
                            <i class="fa-brands fa-youtube"></i> YouTube Link
                        </label>
                    </div>

                    {{-- Image Upload --}}
                    <div x-show="mediaType === 'image'" x-cloak>
                        <input
                            type="file"
                            name="file"
                            accept="image/png, image/jpeg, image/webp"
                            class="file:bg-brand-50 file:text-brand-700 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm file:mr-4 file:rounded-md file:border-0 file:px-3 file:py-1.5 file:text-xs file:font-semibold"
                        />
                        <p class="mt-1.5 text-xs text-gray-400">JPG, PNG or WEBP. Max 2MB.</p>
                        @error ('file')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- YouTube URL --}}
                    <div x-show="mediaType === 'youtube'" x-cloak>
                        <input
                            type="url"
                            name="media_path"
                            placeholder="https://youtube.com/watch?v=…"
                            class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm transition-all outline-none focus:ring-2"
                        />
                        @error ('media_path')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-600">
                        <input
                            type="checkbox"
                            name="is_primary"
                            value="1"
                            class="text-brand-600 focus:ring-brand-500 rounded border-gray-300"
                        />
                        Set as primary/cover
                    </label>

                    <button
                        type="submit"
                        class="bg-brand-600 hover:bg-brand-700 flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all"
                    >
                        <i class="fa-solid fa-plus"></i> Add Media
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
