{{-- Dry run banner ── --}}
<div x-show="importRunIsDryRun" x-transition
    class="mb-4 flex items-center gap-2.5 bg-indigo-50 border border-indigo-200 rounded-lg px-4 py-3">
    <i data-lucide="flask-conical" class="w-4 h-4 text-indigo-500 shrink-0"></i>
    <span class="text-[12px] font-bold text-indigo-800">
        Dry run — validating only. No data will be saved to the database.
    </span>
</div>

{{-- Plan limit banner ── --}}
<div x-show="limitSkippedRows > 0" x-transition
    class="mb-4 flex items-center gap-2.5 bg-orange-50 border border-orange-200 rounded-lg px-4 py-3">
    <i data-lucide="shield-alert" class="w-4 h-4 text-orange-500 shrink-0"></i>
    <p class="text-[12px] font-bold text-orange-800">
        Product limit reached —
        <span class="font-black" x-text="limitSkippedRows"></span> row(s) skipped.
        Upgrade your plan to import more products.
    </p>
</div>

{{-- Stats grid ── --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2 mb-5">

    <div class="bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-center">
        <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1">Total</p>
        <p class="text-xl font-black text-gray-700 tabular-nums" x-text="totalRows"></p>
    </div>

    <div class="bg-sky-50 border border-sky-100 rounded-xl px-4 py-3 text-center">
        <p class="text-[10px] font-black text-sky-500 uppercase tracking-wider mb-1"
            x-text="importRunIsDryRun ? 'Would Create' : 'Created'"></p>
        <p class="text-xl font-black text-sky-700 tabular-nums" x-text="createdRows"></p>
    </div>

    <div class="bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-3 text-center">
        <p class="text-[10px] font-black text-emerald-500 uppercase tracking-wider mb-1"
            x-text="importRunIsDryRun ? 'Would Update' : 'Updated'"></p>
        <p class="text-xl font-black text-emerald-700 tabular-nums" x-text="updatedRows"></p>
    </div>

    <div class="bg-amber-50 border border-amber-100 rounded-xl px-4 py-3 text-center">
        <p class="text-[10px] font-black text-amber-500 uppercase tracking-wider mb-1">Skipped</p>
        <p class="text-xl font-black text-amber-700 tabular-nums" x-text="skippedRows"></p>
    </div>

    <div class="bg-red-50 border border-red-100 rounded-xl px-4 py-3 text-center">
        <p class="text-[10px] font-black text-red-400 uppercase tracking-wider mb-1">Failed</p>
        <p class="text-xl font-black text-red-600 tabular-nums" x-text="failedRows"></p>
    </div>

</div>