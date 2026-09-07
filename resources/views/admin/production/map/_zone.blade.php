{{--
    Recursive zone renderer.

    Zones nest to any depth, so this partial includes itself. Depth only changes
    how a zone is presented, never whether it renders — a five-level tenant gets
    the same treatment at level 5 as at level 3, just indented further. Anything
    that assumed exactly three levels would silently hide spaces.

    Expects: $zone, $depth, $zonesByParent, $cellsByZone
--}}

@php
    $children = $zonesByParent[$zone->id] ?? collect();
    $zoneCells = $cellsByZone[$zone->id] ?? collect();

    // Every space beneath this zone, however deep — used to decide whether the
    // whole branch should disappear when filters exclude all of it.
    $descendantIds = collect();
    $collect = function ($z) use (&$collect, $zonesByParent, $cellsByZone, &$descendantIds) {
        $descendantIds = $descendantIds->merge(($cellsByZone[$z->id] ?? collect())->pluck('id'));
        foreach ($zonesByParent[$z->id] ?? [] as $child) {
            $collect($child);
        }
    };
    $collect($zone);

    $idsJson = $descendantIds->values()->toJson();
@endphp

@if ($descendantIds->isNotEmpty() || $children->isNotEmpty())
    @if ($depth === 1)
        {{-- Level 1 — a section band across the page --}}
        <div class="mt-6" x-show="zoneVisible({{ $idsJson }})" x-cloak>
            <div class="mb-3 flex items-center gap-3">
                <div class="h-[2px] w-8 bg-gray-900"></div>
                <span class="text-xs font-black tracking-widest text-gray-700 uppercase">{{ $zone->name }}</span>
                <span
                    class="rounded-full border border-gray-200 bg-white px-2 py-0.5 text-[10px] font-bold text-gray-500"
                >
                    {{ $descendantIds->count() }} spaces
                </span>
                <div class="h-px flex-1 bg-gray-100"></div>
            </div>

            {{-- Children become side-by-side columns, like walking an aisle --}}
            <div class="pm-scroll flex flex-col gap-4 lg:flex-row lg:overflow-x-auto lg:pb-3">
                @foreach ($children as $child)
                    @include ('admin.production.map._zone', [
                        'zone' => $child,
                        'depth' => 2,
                        'zonesByParent' => $zonesByParent,
                        'cellsByZone' => $cellsByZone,
                    ])
                @endforeach

                {{-- Spaces attached straight to this zone, not to a child --}}
                @if ($zoneCells->isNotEmpty())
                    <div class="w-full shrink-0 lg:w-[248px]">
                        <div class="pm-bin overflow-hidden rounded-xl border border-gray-900 bg-white">
                            <div
                                class="border-b border-gray-100 bg-gray-50 px-3 py-2.5 text-[11px] font-black tracking-wide text-gray-700 uppercase"
                            >
                                {{ $zone->name }} · Direct
                            </div>
                            <div class="pm-grid space-y-2.5 p-2.5">
                                @foreach ($zoneCells as $cell)
                                    @include ('admin.production.map._bin', ['cell' => $cell, 'compact' => false])
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    @elseif ($depth === 2)
        {{-- Level 2 — a column card, the visual "row" of the aisle --}}
        <div class="w-full shrink-0 lg:w-[248px]" x-show="zoneVisible({{ $idsJson }})" x-cloak>
            <div class="pm-bin overflow-hidden rounded-xl border border-gray-900 bg-white">
                <div class="h-1 w-full" style="background: var(--brand-600)"></div>
                <div class="flex items-center justify-between border-b border-gray-100 bg-white px-3 py-2.5">
                    <span class="text-[12px] font-black tracking-wide text-gray-800 uppercase">{{ $zone->name }}</span>
                    <span
                        class="rounded-full border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] font-bold text-gray-500"
                    >
                        {{ $descendantIds->count() }}
                    </span>
                </div>

                <div class="pm-grid space-y-3 p-2.5">
                    @foreach ($zoneCells as $cell)
                        @include ('admin.production.map._bin', ['cell' => $cell, 'compact' => false])
                    @endforeach

                    @foreach ($children as $child)
                        @include ('admin.production.map._zone', [
                            'zone' => $child,
                            'depth' => 3,
                            'zonesByParent' => $zonesByParent,
                            'cellsByZone' => $cellsByZone,
                        ])
                    @endforeach
                </div>
            </div>
        </div>

    @else
        {{-- Level 3 and deeper — a dashed sub-group inside its parent card.
             Depth beyond 3 keeps this same shape so nothing is ever dropped. --}}
        <div
            class="rounded-lg border border-dashed border-gray-300 bg-gray-50/60 p-2"
            x-show="zoneVisible({{ $idsJson }})"
            x-cloak
        >
            <p class="mb-2 flex items-center gap-1.5 text-[9px] font-black tracking-widest text-gray-500 uppercase">
                <span class="h-[2px] w-3 bg-gray-400"></span>
                {{ $zone->name }}
            </p>

            <div class="space-y-2">
                @foreach ($zoneCells as $cell)
                    @include ('admin.production.map._bin', ['cell' => $cell, 'compact' => true])
                @endforeach

                @foreach ($children as $child)
                    @include ('admin.production.map._zone', [
                        'zone' => $child,
                        'depth' => $depth + 1,
                        'zonesByParent' => $zonesByParent,
                        'cellsByZone' => $cellsByZone,
                    ])
                @endforeach
            </div>
        </div>
    @endif

@endif
