{{-- Duplicate Mode Selector ── --}}
<div class="mb-4 bg-gray-50 border border-gray-100 rounded-lg p-4">
    <div class="flex items-center gap-2 mb-2.5">
        <i data-lucide="copy" class="w-3.5 h-3.5 text-gray-500"></i>
        <p class="text-[12px] font-black text-gray-700">Handle Duplicates Within File</p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
        <label class="flex items-start gap-2 px-3 py-2 rounded-lg border cursor-pointer transition-colors"
            :class="duplicateMode === 'skip' ? 'border-emerald-400 bg-emerald-50' : 'border-gray-200 bg-white hover:bg-gray-50'">
            <input type="radio" name="duplicate_mode" value="skip" x-model="duplicateMode" class="mt-0.5 hidden" checked>
            <div>
                <p class="text-[12px] font-bold text-gray-800">Skip Duplicates</p>
                <p class="text-[10px] text-gray-500 leading-snug">Keep first, silently skip repeats</p>
            </div>
        </label>
        <label class="flex items-start gap-2 px-3 py-2 rounded-lg border cursor-pointer transition-colors"
            :class="duplicateMode === 'update' ? 'border-emerald-400 bg-emerald-50' : 'border-gray-200 bg-white hover:bg-gray-50'">
            <input type="radio" name="duplicate_mode" value="update" x-model="duplicateMode" class="mt-0.5 hidden">
            <div>
                <p class="text-[12px] font-bold text-gray-800">Update (Last Wins)</p>
                <p class="text-[10px] text-gray-500 leading-snug">Keep last occurrence, skip earlier</p>
            </div>
        </label>
        <label class="flex items-start gap-2 px-3 py-2 rounded-lg border cursor-pointer transition-colors"
            :class="duplicateMode === 'error' ? 'border-emerald-400 bg-emerald-50' : 'border-gray-200 bg-white hover:bg-gray-50'">
            <input type="radio" name="duplicate_mode" value="error" x-model="duplicateMode" class="mt-0.5 hidden">
            <div>
                <p class="text-[12px] font-bold text-gray-800">Error on Duplicate</p>
                <p class="text-[10px] text-gray-500 leading-snug">Log repeats in error report</p>
            </div>
        </label>
    </div>
</div>
