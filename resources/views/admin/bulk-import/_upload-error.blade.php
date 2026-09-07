{{-- Upload error banner ── --}}
<div x-show="uploadError" x-transition
    class="mt-3 flex items-start gap-2.5 bg-red-50 border border-red-200 rounded-lg px-4 py-3">
    <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 shrink-0 mt-0.5"></i>
    <span class="text-[12px] font-bold text-red-700 leading-snug" x-text="uploadError"></span>
</div>