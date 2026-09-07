{{-- Chunk error + resume button ── --}}
<div x-show="chunkError && !done" x-transition
    class="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3.5 flex items-center justify-between gap-3">
    <div class="flex items-center gap-2.5 min-w-0">
        <i data-lucide="wifi-off" class="w-4 h-4 text-red-500 shrink-0"></i>
        <p class="text-[12px] font-bold text-red-700 leading-snug">
            Processing interrupted. Progress is saved —
            click <strong>Resume</strong> to continue from where it stopped.
        </p>
    </div>
    <button @click="processNextChunk(currentType, currentOffset)"
        class="shrink-0 flex items-center gap-1.5 px-3.5 py-2 text-[12px] font-bold text-white bg-red-500 rounded-lg hover:bg-red-600 transition-colors">
        <i data-lucide="rotate-cw" class="w-3.5 h-3.5"></i>
        Resume
    </button>
</div>