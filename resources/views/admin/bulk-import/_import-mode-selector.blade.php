{{-- Import Mode Selector ── --}}
<div class="mb-4 bg-gray-50 border border-gray-100 rounded-lg p-4">
    <div class="flex items-center gap-2 mb-2.5">
        <i data-lucide="settings-2" class="w-3.5 h-3.5 text-gray-500"></i>
        <p class="text-[12px] font-black text-gray-700">Import Mode</p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
        <label class="flex items-start gap-2 px-3 py-2 rounded-lg border cursor-pointer transition-colors"
            :class="importMode === 'create_or_update' ? 'border-emerald-400 bg-emerald-50' : 'border-gray-200 bg-white hover:bg-gray-50'">
            <input type="radio" name="import_mode" value="create_or_update" x-model="importMode" class="mt-0.5 hidden" checked>
            <div>
                <p class="text-[12px] font-bold text-gray-800">Create or Update</p>
                <p class="text-[10px] text-gray-500 leading-snug">Add new and update existing</p>
            </div>
        </label>
        <label class="flex items-start gap-2 px-3 py-2 rounded-lg border cursor-pointer transition-colors"
            :class="importMode === 'create_only' ? 'border-emerald-400 bg-emerald-50' : 'border-gray-200 bg-white hover:bg-gray-50'">
            <input type="radio" name="import_mode" value="create_only" x-model="importMode" class="mt-0.5 hidden">
            <div>
                <p class="text-[12px] font-bold text-gray-800">Create Only</p>
                <p class="text-[10px] text-gray-500 leading-snug">Skip rows that already exist</p>
            </div>
        </label>
        <label class="flex items-start gap-2 px-3 py-2 rounded-lg border cursor-pointer transition-colors"
            :class="importMode === 'update_only' ? 'border-emerald-400 bg-emerald-50' : 'border-gray-200 bg-white hover:bg-gray-50'">
            <input type="radio" name="import_mode" value="update_only" x-model="importMode" class="mt-0.5 hidden">
            <div>
                <p class="text-[12px] font-bold text-gray-800">Update Only</p>
                <p class="text-[10px] text-gray-500 leading-snug">Skip rows not yet in database</p>
            </div>
        </label>
    </div>
</div>
