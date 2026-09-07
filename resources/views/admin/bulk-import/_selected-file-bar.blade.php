{{-- Selected file indicator ── --}}
<div x-show="selectedFile" x-transition
    class="mt-4 flex items-center justify-between bg-emerald-50 border border-emerald-200 rounded-lg px-4 py-3">
    <div class="flex items-center gap-2.5 min-w-0">
        <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0">
            <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-600"></i>
        </div>
        <div class="min-w-0">
            <p class="text-[13px] font-bold text-gray-800 truncate" x-text="selectedFile?.name"></p>
            <p class="text-[11px] text-gray-500" x-text="formatFileSize(selectedFile?.size)"></p>
        </div>
    </div>
    <button @click="selectedFile = null"
        class="shrink-0 ml-3 w-7 h-7 rounded-full flex items-center justify-center text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors">
        <i data-lucide="x" class="w-4 h-4"></i>
    </button>
</div>