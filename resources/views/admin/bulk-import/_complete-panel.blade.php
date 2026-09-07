{{-- Import complete state ── --}}
<div x-show="done" x-transition class="bg-emerald-50 border border-emerald-200 rounded-xl p-4">
    <div class="flex items-center gap-2.5 mb-3">
        <div class="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center">
            <i data-lucide="check" class="w-4 h-4 text-white"></i>
        </div>
        <div>
            <p class="text-[14px] font-black text-emerald-800">Import Complete</p>
            <p class="text-[11px] text-emerald-700 mt-0.5" x-text="`${successRows} row(s) processed successfully.`"></p>
        </div>
    </div>
    {{-- Auto-created references. Shown because relaxed validation means a
         typo now becomes a new record instead of an error — this is the only
         chance the user has to notice it while the context is still fresh. --}}
    <template x-if="hasCreatedRefs">
        <div class="bg-white border border-emerald-200 rounded-lg px-3.5 py-3 mb-3">
            <p class="text-[11px] font-bold text-gray-700 mb-2">
                Created automatically — check these are correct:
            </p>

            <template x-if="createdRefs.categories.length > 0">
                <div class="mb-1.5">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Categories</span>
                    <div class="flex flex-wrap gap-1 mt-1">
                        <template x-for="name in createdRefs.categories" :key="name">
                            <span class="bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded text-[11px] font-medium"
                                x-text="name"></span>
                        </template>
                    </div>
                </div>
            </template>

            <template x-if="createdRefs.units.length > 0">
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Units</span>
                    <div class="flex flex-wrap gap-1 mt-1">
                        <template x-for="name in createdRefs.units" :key="name">
                            <span class="bg-sky-50 text-sky-700 px-2 py-0.5 rounded text-[11px] font-medium"
                                x-text="name"></span>
                        </template>
                    </div>
                </div>
            </template>

            <p class="text-[10px] text-gray-400 mt-2 leading-relaxed">
                A misspelled name becomes a new entry. Rename or remove anything unexpected from its own page.
            </p>
        </div>
    </template>

    <div class="flex flex-wrap gap-2">
        <template x-if="failedRows > 0 || skippedRows > 0">
            <a :href="'{{ route('admin.bulk-import.errors', ':id') }}'.replace(':id', importId)"
                target="_blank"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-[12px] font-bold text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                Download Error Report
                <span class="bg-red-100 text-red-600 rounded-full px-1.5 py-0.5 text-[10px] font-black" x-text="failedRows + skippedRows"></span>
            </a>
        </template>
        <button @click="resetState()"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 text-[12px] font-bold text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
            New Import
        </button>
    </div>
</div>