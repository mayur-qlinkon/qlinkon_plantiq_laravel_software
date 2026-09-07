{{--
    Bulk import launcher + modal(s).

    Usage:
        <x-import-modal type="categories" />
        <x-import-modal :types="['product_with_skus', 'product_images']" />

    One type renders a plain button; two or more render a dropdown, so pages
    with a single import never pay an extra click for a menu of one.

    Each modal is its own Alpine component and opens by listening for a window
    event. Nesting several engines inside one x-data would break them: the
    engine calls $nextTick on itself, which only exists on a root component.
--}}
@props([
    'type' => null,
    'types' => null,
    'label' => 'Bulk Import',
    'triggerClass' =>
        'inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold whitespace-nowrap text-gray-700 shadow-sm transition-colors hover:bg-gray-50',
])

@php
    $registry = app(\App\Imports\ImportTypeRegistry::class);

    // Product images is not an ImportType — that contract is built around CSV
    // rows. It is described here instead of being forced into the registry.
    $zipTypes = [
        'product_images' => [
            'key' => 'product_images',
            'segment' => 'product-images',
            'label' => 'Product Images',
            'icon' => 'image',
            'panel' => 'admin.bulk-import._panel-zip',
            'permission' => 'images_import',
            'isZip' => true,
            'planLimited' => false,
        ],
    ];

    $requested = $types ?? array_filter([$type]);
    $entries = [];

    foreach ($requested as $key) {
        if (isset($zipTypes[$key])) {
            $entry = $zipTypes[$key];
        } else {
            $importType = $registry->get($key);
            $entry = [
                'key' => $importType->key(),
                'segment' => $importType->key(),
                'label' => $importType->label(),
                'icon' => 'file-spreadsheet',
                'panel' => 'admin.bulk-import._panel',
                'permission' => $importType->permission(),
                'isZip' => false,
                'planLimited' => $importType->isPlanLimited(),
                'type' => $importType,
            ];
        }

        if ($entry['permission'] !== null && !has_permission($entry['permission'])) {
            continue;
        }

        $entries[] = $entry;
    }

    // The product cap is the only plan limit any import cares about, so it is
    // resolved once here rather than per entry.
    $productLimit = null;
    $productCount = 0;
    $limitReached = false;

    if (collect($entries)->contains('planLimited', true)) {
        $companyId = auth()->user()->company_id;
        $productCount = \App\Models\Product::withoutGlobalScopes()->where('company_id', $companyId)->count();
        $productLimit = auth()->user()->company->subscription?->plan?->product_limit;
        $limitReached = $productLimit !== null && $productCount >= $productLimit;
    }

    $maxBytes = max_upload_bytes();
@endphp

@if (has_module('bulk_import') && $entries)
    @include('admin.bulk-import._engine-script')

    {{-- ── Launcher ── --}}
    @if (count($entries) === 1)
        <button type="button" class="{{ $triggerClass }}" @click="$dispatch('open-import', '{{ $entries[0]['key'] }}')">
            <i data-lucide="upload-cloud" class="h-4 w-4 text-[var(--brand-600)]"></i>
            {{ $label }}
        </button>
    @else
        <div class="relative" x-data="{ menuOpen: false }" @click.outside="menuOpen = false">
            <button type="button" class="{{ $triggerClass }}" @click="menuOpen = !menuOpen">
                <i data-lucide="upload-cloud" class="h-4 w-4 text-[var(--brand-600)]"></i>
                {{ $label }}
                <i data-lucide="chevron-down" class="h-3.5 w-3.5 opacity-50 transition-transform"
                    :class="menuOpen ? 'rotate-180' : ''"></i>
            </button>

            <div x-show="menuOpen" x-cloak x-transition
                class="absolute right-0 z-20 mt-1 w-56 overflow-hidden rounded-lg border border-gray-100 bg-white shadow-lg">
                @foreach ($entries as $entry)
                    <button type="button" @click="menuOpen = false; $dispatch('open-import', '{{ $entry['key'] }}')"
                        class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-[13px] font-bold text-gray-700 transition-colors hover:bg-gray-50">
                        <i data-lucide="{{ $entry['icon'] }}" class="h-4 w-4 text-gray-400"></i>
                        {{ $entry['label'] }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ── Modals ── --}}
    @foreach ($entries as $entry)
        <div x-data="importModal({
            type: '{{ $entry['segment'] }}',
            isZip: {{ $entry['isZip'] ? 'true' : 'false' }},
            maxBytes: {{ $maxBytes }},
            productLimit: {{ $entry['planLimited'] ? $productLimit ?? 'null' : 'null' }},
            productCount: {{ $entry['planLimited'] ? $productCount : 0 }},
            limitExceeded: {{ $entry['planLimited'] && $limitReached ? 'true' : 'false' }}
        })" @open-import.window="if ($event.detail === '{{ $entry['key'] }}') openModal()">

            <div x-show="open" x-cloak @keydown.escape.window="requestClose()"
                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:p-6">

                <div x-show="open" x-transition.opacity @click="requestClose()" class="fixed inset-0 bg-gray-900/50">
                </div>

                <div x-show="open" x-transition class="relative my-8 w-full max-w-2xl">
                    <button type="button" @click="requestClose()"
                        class="absolute -top-3 -right-3 z-10 flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 bg-white shadow-sm transition-colors hover:bg-gray-50">
                        <i data-lucide="x" class="h-4 w-4 text-gray-500"></i>
                    </button>

                    @include($entry['panel'], isset($entry['type']) ? ['type' => $entry['type']] : [])
                </div>
            </div>
        </div>
    @endforeach
@endif
