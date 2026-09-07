{{-- Progress bar — pass $heading variable ── --}}
@php $heading = $heading ?? 'Importing…'; @endphp
<div class="mb-5">
    <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-2">
            <template x-if="!done">
                <svg class="animate-spin w-3.5 h-3.5 text-gray-400" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </template>
            <template x-if="done">
                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-500"></i>
            </template>
            <h2 class="text-[14px] font-black text-gray-800">{{ $heading }}</h2>
        </div>
        <span class="text-[11px] font-bold text-gray-400 tabular-nums" x-text="progressText"></span>
    </div>
    <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
        <div class="h-full rounded-full transition-all duration-300"
            :style="'width: ' + progressPercent + '%; background: var(--brand-600)'"></div>
    </div>
</div>