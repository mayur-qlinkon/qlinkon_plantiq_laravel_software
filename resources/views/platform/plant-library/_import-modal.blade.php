{{--
    Bulk CSV import for the plant library master catalog.

    Super admin only — the whole file is processed in one POST, so the row cap
    enforced server-side (2000) is mirrored in the copy below. Kept as its own
    partial so index.blade.php stays a listing page.
--}}
<div x-data="plantLibraryImport()" @open-plant-import.window="openModal()">
    <div x-show="open" x-cloak @keydown.escape.window="requestClose()"
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:p-6">
        <div x-show="open" x-transition.opacity @click="requestClose()" class="fixed inset-0 bg-gray-900/50"></div>

        <div x-show="open" x-transition class="relative my-8 w-full max-w-2xl">
            <button type="button" @click="requestClose()"
                class="absolute -top-3 -right-3 z-10 flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 bg-white shadow-sm transition-colors hover:bg-gray-50">
                <i class="fa-solid fa-xmark text-sm text-gray-500"></i>
            </button>

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl">
                {{-- Header --}}
                <div class="border-b border-gray-100 px-6 py-5">
                    <h2 class="flex items-center gap-2.5 text-lg font-bold text-gray-900">
                        <i class="fa-solid fa-file-csv text-brand-600"></i>
                        Bulk Import Plants
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Upload a CSV to add plants to the master library. Up to
                        {{ number_format($maxImportRows) }} rows per file.
                    </p>
                </div>

                <div class="px-6 py-5">
                    {{-- ── Step 1: form ── --}}
                    <template x-if="!result">
                        <div class="space-y-5">
                            {{-- Sample CSV --}}
                            <div
                                class="flex items-center justify-between gap-3 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3">
                                <div class="text-sm">
                                    <p class="font-semibold text-blue-900">Not sure about the format?</p>
                                    <p class="mt-0.5 text-blue-700">Download the sample CSV — it has every supported
                                        column.</p>
                                </div>

                                <a href="{{ route('platform.plant-library.import.sample') }}"
                                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-blue-200 bg-white px-3 py-2 text-[13px] font-bold text-blue-700 transition-colors hover:bg-blue-100">
                                    <i class="fa-solid fa-download"></i> Sample CSV
                                </a>
                            </div>

                            {{-- Column reference --}}
                            <div
                                class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-[13px] leading-relaxed">
                                <p class="font-semibold text-gray-800">Columns</p>
                                <p class="mt-1 text-gray-600">
                                    <span class="font-semibold text-red-600">Required:</span>
                                    <code class="rounded bg-white px-1.5 py-0.5 text-gray-700">name</code>,
                                    <code class="rounded bg-white px-1.5 py-0.5 text-gray-700">category_name</code>
                                </p>
                                <p class="mt-1 text-gray-600">
                                    <span class="font-semibold text-gray-700">Optional:</span>
                                    <code class="rounded bg-white px-1.5 py-0.5 text-gray-700">type</code>,
                                    <code class="rounded bg-white px-1.5 py-0.5 text-gray-700">product_type</code>,
                                    <code class="rounded bg-white px-1.5 py-0.5 text-gray-700">unit_short_name</code>,
                                    <code class="rounded bg-white px-1.5 py-0.5 text-gray-700">description</code>,
                                    <code class="rounded bg-white px-1.5 py-0.5 text-gray-700">is_active</code>,
                                    <code class="rounded bg-white px-1.5 py-0.5 text-gray-700">sort_order</code>,
                                    <code class="rounded bg-white px-1.5 py-0.5 text-gray-700">title1</code>/<code
                                        class="rounded bg-white px-1.5 py-0.5 text-gray-700">value1</code>
                                    … up to <code
                                        class="rounded bg-white px-1.5 py-0.5 text-gray-700">title10</code>/<code
                                        class="rounded bg-white px-1.5 py-0.5 text-gray-700">value10</code>
                                </p>
                            </div>

                            {{-- Drop zone --}}
                            <div @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
                                @drop.prevent="handleDrop($event)" @click="$refs.fileInput.click()"
                                class="cursor-pointer rounded-xl border-2 border-dashed px-6 py-8 text-center transition-colors"
                                :class="dragging ? 'border-brand-500 bg-brand-50' :
                                    'border-gray-300 hover:border-gray-400 hover:bg-gray-50'">
                                <i class="fa-solid fa-cloud-arrow-up mb-2 text-2xl text-gray-400"></i>
                                <p class="text-sm font-semibold text-gray-700">
                                    <template x-if="!file">
                                        <span>Drop your CSV here, or click to browse</span>
                                    </template>
                                    <template x-if="file">
                                        <span x-text="file.name"></span>
                                    </template>
                                </p>
                                <p class="mt-1 text-xs text-gray-500">CSV only, max
                                    {{ round($maxUploadBytes / 1048576, 1) }} MB</p>
                            </div>

                            <input type="file" accept=".csv,text/csv" x-ref="fileInput" class="hidden"
                                @change="handleSelect($event)" />

                            {{-- Duplicate mode --}}
                            <div>
                                <p class="mb-2 text-[13px] font-bold text-gray-700">If a plant already exists</p>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <label
                                        class="flex cursor-pointer items-start gap-2.5 rounded-lg border px-3.5 py-3 transition-colors"
                                        :class="duplicateMode === 'skip' ? 'border-brand-500 bg-brand-50' :
                                            'border-gray-200 hover:bg-gray-50'">
                                        <input type="radio" value="skip" x-model="duplicateMode"
                                            class="mt-0.5 accent-[var(--brand-600)]" />
                                        <span class="text-[13px]">
                                            <span class="block font-bold text-gray-800">Skip it</span>
                                            <span class="block text-gray-500">Leave the existing plant untouched</span>
                                        </span>
                                    </label>
                                    <label
                                        class="flex cursor-pointer items-start gap-2.5 rounded-lg border px-3.5 py-3 transition-colors"
                                        :class="duplicateMode === 'update' ? 'border-brand-500 bg-brand-50' :
                                            'border-gray-200 hover:bg-gray-50'">
                                        <input type="radio" value="update" x-model="duplicateMode"
                                            class="mt-0.5 accent-[var(--brand-600)]" />
                                        <span class="text-[13px]">
                                            <span class="block font-bold text-gray-800">Update it</span>
                                            <span class="block text-gray-500">Overwrite with the values from the
                                                CSV</span>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            {{-- Upload error --}}
                            <template x-if="errorMessage">
                                <div
                                    class="flex items-start gap-2.5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-[13px] text-red-800">
                                    <i class="fa-solid fa-triangle-exclamation mt-0.5 shrink-0 text-red-600"></i>
                                    <span class="font-medium" x-text="errorMessage"></span>
                                </div>
                            </template>

                            {{-- Actions --}}
                            <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4">
                                <button type="button" @click="requestClose()" :disabled="uploading"
                                    class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 disabled:opacity-50">
                                    Cancel
                                </button>
                                <button type="button" @click="submit()" :disabled="!file || uploading"
                                    class="bg-brand-600 hover:bg-brand-700 inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-colors disabled:cursor-not-allowed disabled:opacity-50">
                                    <i class="fa-solid" :class="uploading ? 'fa-spinner fa-spin' : 'fa-upload'"></i>
                                    <span x-text="uploading ? 'Importing…' : 'Start Import'"></span>
                                </button>
                            </div>
                        </div>
                    </template>

                    {{-- ── Step 2: result ── --}}
                    <template x-if="result">
                        <div class="space-y-5">
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div class="rounded-xl border border-green-100 bg-green-50 px-4 py-3 text-center">
                                    <p class="text-xl font-extrabold text-green-700" x-text="result.created"></p>
                                    <p class="text-xs font-bold text-green-600">Created</p>
                                </div>
                                <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-center">
                                    <p class="text-xl font-extrabold text-blue-700" x-text="result.updated"></p>
                                    <p class="text-xs font-bold text-blue-600">Updated</p>
                                </div>
                                <div class="rounded-xl border border-amber-100 bg-amber-50 px-4 py-3 text-center">
                                    <p class="text-xl font-extrabold text-amber-700" x-text="result.skipped"></p>
                                    <p class="text-xs font-bold text-amber-600">Skipped</p>
                                </div>
                                <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-center">
                                    <p class="text-xl font-extrabold text-gray-700" x-text="result.total"></p>
                                    <p class="text-xs font-bold text-gray-500">Total Rows</p>
                                </div>
                            </div>

                            {{-- New category names — the cheapest way to spot a typo
                                 like "Indoor Plant" vs "Indoor Plants". --}}
                            <template x-if="result.new_categories && result.new_categories.length">
                                <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
                                    <p class="text-[13px] font-bold text-gray-800">
                                        New category names in this file
                                        (<span x-text="result.new_categories.length"></span>)
                                    </p>
                                    <p class="mt-0.5 text-xs text-gray-500">Check these for typos — each one becomes a
                                        separate category.</p>
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        <template x-for="name in result.new_categories" :key="name">
                                            <span
                                                class="rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700"
                                                x-text="name"></span>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            {{-- Row errors --}}
                            <template x-if="result.errors && result.errors.length">
                                <div class="rounded-xl border border-red-200 bg-red-50">
                                    <p class="border-b border-red-200 px-4 py-2.5 text-[13px] font-bold text-red-800">
                                        Rows that could not be imported
                                        (<span x-text="result.errors.length"></span>)
                                    </p>
                                    <div class="max-h-52 overflow-y-auto px-4 py-2">
                                        <template x-for="err in result.errors" :key="err.row">
                                            <div
                                                class="flex gap-2 border-b border-red-100 py-1.5 text-[13px] last:border-0">
                                                <span class="shrink-0 font-bold text-red-700">Row <span
                                                        x-text="err.row"></span></span>
                                                <span class="text-red-700" x-text="err.message"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4">
                                <button type="button" @click="reset()"
                                    class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50">
                                    Import Another File
                                </button>
                                <button type="button" @click="closeAndRefresh()"
                                    class="bg-brand-600 hover:bg-brand-700 rounded-lg px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-colors">
                                    Done
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        // Registered on window rather than through Alpine.data(): Alpine is
        // deferred, so this inline script runs first and the factory is ready
        // by the time Alpine walks the DOM.
        window.plantLibraryImport = function() {
            return {
                open: false,
                dragging: false,
                uploading: false,
                file: null,
                duplicateMode: 'skip',
                errorMessage: '',
                result: null,
                maxBytes: {{ $maxUploadBytes }},

                openModal() {
                    this.reset();
                    this.open = true;
                },

                reset() {
                    this.file = null;
                    this.result = null;
                    this.errorMessage = '';
                    this.dragging = false;
                    this.uploading = false;

                    if (this.$refs.fileInput) {
                        this.$refs.fileInput.value = '';
                    }
                },

                requestClose() {
                    if (this.uploading) {
                        return; // A transaction is open server-side — let it finish.
                    }

                    // Closing after a successful import must refresh, otherwise
                    // the grid behind the modal still shows the old list.
                    if (this.result && (this.result.created > 0 || this.result.updated > 0)) {
                        this.closeAndRefresh();
                        return;
                    }

                    this.open = false;
                },

                closeAndRefresh() {
                    this.open = false;
                    window.location.reload();
                },

                handleDrop(event) {
                    this.dragging = false;
                    this.acceptFile(event.dataTransfer.files[0] ?? null);
                },

                handleSelect(event) {
                    this.acceptFile(event.target.files[0] ?? null);
                },

                acceptFile(file) {
                    this.errorMessage = '';

                    if (!file) {
                        return;
                    }

                    if (!/\.csv$/i.test(file.name)) {
                        this.errorMessage = 'Please choose a .csv file.';
                        return;
                    }

                    if (file.size > this.maxBytes) {
                        this.errorMessage = 'That file is larger than the upload limit.';
                        return;
                    }

                    this.file = file;
                },

                async submit() {
                    if (!this.file || this.uploading) {
                        return;
                    }

                    this.uploading = true;
                    this.errorMessage = '';

                    const body = new FormData();
                    body.append('file', this.file);
                    body.append('duplicate_mode', this.duplicateMode);

                    try {
                        const res = await fetch('{{ route('platform.plant-library.import') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                Accept: 'application/json',
                            },
                            body: body,
                        });

                        const data = await res.json().catch(() => ({}));

                        if (!res.ok) {
                            // Laravel validation errors arrive under `errors`,
                            // our own rejections under `message`.
                            this.errorMessage =
                                data.message ||
                                (data.errors ? Object.values(data.errors).flat().join(' ') : '') ||
                                'The import failed. Please check the file and try again.';
                            return;
                        }

                        this.result = data;

                        if (data.created > 0 || data.updated > 0) {
                            BizAlert.toast(`Imported ${data.created} new, updated ${data.updated}.`, 'success');
                        } else {
                            BizAlert.toast('No plants were imported.', 'warning');
                        }
                    } catch (e) {
                        this.errorMessage = 'Could not reach the server. Please try again.';
                    } finally {
                        this.uploading = false;
                    }
                },
            };
        };
    </script>
@endpush
