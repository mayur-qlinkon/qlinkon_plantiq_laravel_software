{{--
    Upload + process panel for the ZIP-based product image import.

    Deliberately not an ImportType: that contract is built around CSV rows
    (headers, sample rows, per-row unique keys) and forcing a file-based
    import into it would mean a handful of empty stub methods. What genuinely
    is shared — the modal shell, the chunked engine, and the progress,
    stats and completion partials below — is shared.
--}}

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">

    {{-- ── Upload Section ── --}}
    <div class="p-6" x-show="!importId">

        <div class="flex items-start justify-between gap-4 mb-3">
            <h2 class="text-[16px] font-black text-gray-800 leading-tight">Import Product Images</h2>

            <a href="{{ route('admin.bulk-import.product-images.guide') }}" target="_blank"
                class="shrink-0 inline-flex items-center gap-1.5 text-[11px] font-bold px-3 py-1.5 rounded-lg border border-emerald-200 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition-colors">
                <i data-lucide="download" class="w-3 h-3"></i>
                Naming Guide
            </a>
        </div>

        {{-- The naming rule is the entire contract of this import, so it is
             stated plainly rather than buried in a help notice. --}}
        <div class="mb-4">
            <p class="text-[11px] text-gray-500 mb-1.5">Name each image after its product slug, then a number:</p>
            <div class="flex flex-wrap gap-1.5">
                <code class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded text-[10px] font-mono">aloe-vera-1.jpg</code>
                <code class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded text-[10px] font-mono">aloe-vera-2.jpg</code>
                <code
                    class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded text-[10px] font-mono">rose-plant-1.png</code>
            </div>
            <p class="text-[11px] text-gray-400 mt-1.5">
                Image <strong>-1</strong> becomes the product's main photo. Supported: jpg, png, webp.
            </p>
        </div>

        <div class="flex items-start gap-2.5 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 mb-4">
            <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-500 shrink-0 mt-0.5"></i>
            <p class="text-[12px] text-amber-800 leading-relaxed">
                Products must exist first — an image whose slug matches nothing is skipped.
                Download the naming guide to get every slug with example filenames already filled in.
            </p>
        </div>

        <div class="flex items-start gap-2.5 bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 mb-5">
            <i data-lucide="shield-check" class="w-4 h-4 text-gray-400 shrink-0 mt-0.5"></i>
            <p class="text-[12px] text-gray-600 leading-relaxed">
                Maximum {{ round(max_upload_bytes() / 1024 / 1024) }} MB per ZIP. Larger sets are fine —
                split them into several ZIPs and upload one after another.
            </p>
        </div>

        {{-- Drop Zone ── --}}
        <div class="border-2 border-dashed rounded-xl p-8 text-center transition-colors"
            :class="dragOver ? 'border-blue-400 bg-blue-50' : 'border-gray-200 hover:border-gray-300'"
            @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false"
            @drop.prevent="dragOver = false; handleFileDrop($event)">

            <i data-lucide="upload-cloud" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
            <p class="text-[13px] font-bold text-gray-600 mb-1">Drop your ZIP file here</p>
            <p class="text-[11px] text-gray-400 mb-4">or click to browse</p>

            <label
                class="inline-flex items-center gap-2 px-4 py-2 text-[12px] font-bold text-white rounded-lg cursor-pointer hover:opacity-90 transition-opacity"
                style="background: var(--brand-600)">
                <i data-lucide="file-archive" class="w-3.5 h-3.5"></i>
                Choose ZIP
                <input type="file" accept=".zip,application/zip,application/x-zip-compressed" class="hidden"
                    @change="handleFileSelect($event)">
            </label>
        </div>

        @include('admin.bulk-import._selected-file-bar')
        @include('admin.bulk-import._upload-error')

        <div class="mt-4 flex justify-end" x-show="selectedFile && !uploading">
            <button @click="startUpload()"
                class="flex items-center gap-2 px-5 py-2.5 text-[13px] font-bold text-white rounded-lg hover:opacity-90 transition-opacity"
                style="background: var(--brand-600)">
                <i data-lucide="play" class="w-4 h-4"></i>
                Start Import
            </button>
        </div>

        @include('admin.bulk-import._uploading-spinner', ['label' => 'Extracting ZIP…'])
    </div>

    {{-- ── Processing Section ── --}}
    <div class="p-6" x-show="importId" x-transition>
        @include('admin.bulk-import._progress-section', ['heading' => 'Importing Product Images…'])
        @include('admin.bulk-import._stats-cards')
        @include('admin.bulk-import._chunk-error-resume')
        @include('admin.bulk-import._complete-panel')
    </div>
</div>
