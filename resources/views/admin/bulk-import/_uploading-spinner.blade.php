{{-- Uploading spinner — pass $label variable ── --}}
@php $label = $label ?? 'Uploading…'; @endphp
<div x-show="uploading" class="mt-4 flex items-center justify-center gap-2.5 py-2">
    <svg class="animate-spin w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
    </svg>
    <span class="text-[13px] font-bold text-gray-500">{{ $label }}</span>
</div>