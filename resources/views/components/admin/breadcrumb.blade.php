<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    
    {{-- Left: Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-400 font-medium">
        @foreach ($items as $index => $item)
            @if ($index > 0)
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            @endif

            @if (isset($item['url']))
                <a href="{{ $item['url'] }}" class="hover:text-brand-600 transition-colors">
                    {{ $item['label'] }}
                </a>
            @else
                <span class="text-gray-700 font-semibold truncate max-w-[200px]">
                    {{ $item['label'] }}
                </span>
            @endif
        @endforeach
    </div>

    {{-- Right: Actions --}}
    <div class="flex items-center gap-2">
        {{ $right ?? '' }}
    </div>

</div>