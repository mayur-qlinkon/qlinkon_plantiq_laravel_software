{{-- Dry Run Toggle ── --}}
<div class="mb-4 rounded-lg p-3 border cursor-pointer transition-colors"
    :class="isDryRun ? 'bg-indigo-50 border-indigo-200' : 'bg-gray-50 border-gray-100'"
    @click="isDryRun = !isDryRun">
    <label class="flex items-center gap-3 cursor-pointer" @click.stop>
        <div class="relative">
            <input type="checkbox" x-model="isDryRun" class="sr-only">
            <div class="w-9 h-5 rounded-full transition-colors"
                :class="isDryRun ? 'bg-indigo-500' : 'bg-gray-300'"></div>
            <div class="dot absolute left-0.5 top-0.5 bg-white w-4 h-4 rounded-full transition-transform"
                :class="isDryRun ? 'translate-x-4' : ''"></div>
        </div>
        <div class="flex-1">
            <div class="flex items-center gap-1.5">
                <i data-lucide="flask-conical" class="w-3.5 h-3.5" :class="isDryRun ? 'text-indigo-600' : 'text-gray-500'"></i>
                <p class="text-[12px] font-black" :class="isDryRun ? 'text-indigo-800' : 'text-gray-700'">Dry Run Mode</p>
            </div>
            <p class="text-[10px] leading-snug" :class="isDryRun ? 'text-indigo-700' : 'text-gray-500'">
                Validate and preview what would happen — nothing is actually saved to the database.
            </p>
        </div>
    </label>
</div>
