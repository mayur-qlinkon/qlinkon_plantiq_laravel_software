{{--
    One growing space.

    Every batch inside gets its own row and its own link — the whole point of
    this screen is that a grower can see what is where without opening anything,
    so nothing hides behind a "+2 more".

    Expects: $cell, $compact
--}}

@php
    $status = $cell['status'];

    // Border carries the same signal as the legend, so a bin can be read at a
    // glance without tracing the thin fill rail on its left edge.
    $border = match ($status) {
        'over' => 'border-red-500',
        'empty' => 'border-dashed border-gray-300',
        'partial' => 'border-emerald-300',
        default => 'border-gray-900',
    };

    // The left rail fills to show how full the space is. Area and length units
    // cannot express a ratio, so they read as solid-or-nothing instead.
    $fillPct = $cell['is_count_based']
        ? min(100, $cell['pct'] ?? 0)
        : ($status === 'empty' ? 0 : 100);

    $fillColor = match ($status) {
        'over' => '#ef4444',
        'partial' => 'var(--brand-600)',
        'empty' => 'transparent',
        default => '#111827',
    };
@endphp

<div
    id="pm-space-{{ $cell['id'] }}"
    x-show="show({{ $cell['id'] }})"
    x-cloak
    class="relative overflow-hidden rounded-lg border-[1.5px] bg-white transition-shadow hover:shadow-md {{ $border }} {{ $compact ? 'pl-2.5' : 'pl-3.5' }} {{ $compact ? 'py-2 pr-2' : 'py-2.5 pr-2.5' }}"
>
    {{-- Fill rail --}}
    <div
        class="absolute top-0 bottom-0 left-0 border-r border-gray-100 bg-gray-50 {{ $compact ? 'w-[5px]' : 'w-[7px]' }}"
    >
        <div class="absolute bottom-0 w-full" style="height: {{ $fillPct }}%; background: {{ $fillColor }}"></div>
    </div>

    @if ($status === 'over')
        <span class="absolute top-0 right-0 rounded-bl bg-red-500 px-1 py-0.5 text-[8px] font-black text-white"
            >OVER</span
        >
    @endif

    {{-- Space header --}}
    <div class="flex items-start justify-between gap-2">
        <div class="min-w-0">
            <p class="truncate font-mono text-[11px] font-bold text-gray-900">{{ $cell['code'] }}</p>
            @unless ($compact)
                <p class="mt-0.5 text-[9px] font-bold tracking-widest text-gray-400 uppercase">{{ $cell['type'] }}</p>
            @endunless
        </div>

        <div class="flex shrink-0 items-center gap-1">
            @if ($cell['has_stale'])
                <span
                    class="flex h-4 w-4 items-center justify-center rounded-full bg-amber-400"
                    title="Batch over 180 days old"
                >
                    <i data-lucide="clock" class="h-2.5 w-2.5 text-white"></i>
                </span>
            @endif
            @if ($cell['batch_count'] > 1)
                <span class="rounded bg-gray-900 px-1.5 py-0.5 text-[9px] font-black text-white">
                    {{ $cell['batch_count'] }}
                </span>
            @endif
        </div>
    </div>

    {{-- Capacity line --}}
    @unless ($compact)
        <p class="mt-1 font-mono text-[10px] text-gray-500">
            @if ($cell['is_count_based'])
                <span
                    class="font-bold {{ $status === 'over' ? 'text-red-600' : 'text-gray-700' }}"
                    >{{ $cell['occupied'] }}</span
                >
                <span>/</span>
                <span>{{ (int) $cell['capacity'] }} {{ $cell['unit'] }}</span>
            @else
                {{ $status === 'empty' ? 'Empty' : 'Occupied' }} · {{ rtrim(rtrim(number_format($cell['capacity'], 2), '0'), '.') }} {{ $cell['unit'] }}
            @endif
        </p>
    @endunless

    {{-- Batches — one row each, every one a link --}}
    <div class="mt-2 space-y-1">
        @forelse ($cell['batches'] as $batch)
            <a
                href="{{ $batch['url'] }}"
                target="_blank"
                rel="noopener"
                class="block rounded border border-gray-100 bg-gray-50/70 px-2 py-1.5 transition-colors hover:border-gray-300 hover:bg-white"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="truncate text-[11px] font-bold text-gray-800">{{ $batch['species'] }}</span>
                    @if ($batch['is_stale'])
                        <span class="shrink-0 rounded bg-amber-100 px-1 py-0.5 text-[8px] font-black text-amber-700">
                            {{ $batch['age_days'] }}d
                        </span>
                    @endif
                </div>
                <div class="mt-0.5 flex items-center justify-between gap-2 font-mono text-[9px] text-gray-400">
                    <span>{{ $batch['code'] }}</span>
                    <span class="font-bold text-gray-600">{{ number_format($batch['qty']) }}</span>
                </div>
            </a>
        @empty
            <p class="text-[11px] text-gray-400 italic">
                {{ $cell['empty_days'] !== null ? 'Empty · ' . $cell['empty_days'] . 'd' : 'Empty' }}
            </p>
        @endforelse
    </div>
</div>
