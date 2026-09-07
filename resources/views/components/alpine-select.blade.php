{{--
    Alpine Select Component
    ───────────────────────
    A polished dropdown for option lists that live in Alpine state rather
    than in PHP.

    x-custom-select takes a PHP $options array, so it cannot render a list
    built at runtime (a catalog, outstanding charges, available payments).
    This one takes Alpine expressions instead and evaluates them in the
    parent scope, so any array already on the page works with no extra
    plumbing.

    THREE DELIBERATE CHOICES
    ────────────────────────
    1. Inline x-data, no window.* factory. x-custom-select needs one so the
       SPA layer's Alpine.initTree() can re-init it; plain inline x-data is
       re-initialised correctly on its own, so this component adds nothing
       to layouts/admin.blade.php.

    2. Inline SVG, never data-lucide. window.initIcons() runs on page load
       and SPA navigation only — there is no MutationObserver — so an icon
       Alpine generates when the dropdown opens would never be replaced.

    3. Validation rides on a hidden <input>, not a hidden <select>, and it
       is hidden with opacity-0 rather than sr-only. With x-for options the
       model is often set before the options exist, which resets a select
       to "" and fires `required` on a field that does have a value; and a
       clipped (sr-only) control cannot be focused, so Chrome blocks the
       submit with a console error instead of showing the bubble.

    REQUIREMENTS
    ────────────
    Must sit inside a parent x-data scope — `model` and `items` are Alpine
    expressions resolved there. For a page with no Alpine state, use
    x-custom-select instead.

    USAGE
    ─────
    <x-alpine-select
        model="forms.service.service_id"
        items="catalog"
        item-key="id"
        item-label="item.name + ' · ₹' + item.price"
        placeholder="One-off service, not in the catalog"
        on-change="applyCatalogDefaults()"
    />

    PROPS
    ─────
    model        string  required — Alpine path to read/write, e.g. "forms.x.y"
    items        string  required — Alpine expression returning an array
    itemKey      string  'id'     — property on each item used as the value
    itemLabel    string  required — expression built from the loop variable.
                                    SINGLE QUOTES ONLY: the attribute that
                                    carries it is delimited with double ones.
    as           string  'item'   — loop variable name. Rename it when this
                                    component sits inside another x-for that
                                    already binds `item`.
    placeholder  string  'Select' — shown when nothing is selected
    emptyText    string           — shown when the list is empty
    required     bool    false
    allowEmpty   bool    true     — render the placeholder as a choosable row
    name         string  null     — form field name, when the form posts natively
    onChange     string  null     — Alpine expression run after a selection
--}}

@props([
    'model' => '',
    'items' => '[]',
    'itemKey' => 'id',
    'itemLabel' => 'item.name',
    'as' => 'item',
    'placeholder' => 'Select',
    'emptyText' => 'Nothing to choose from yet.',
    'required' => false,
    'allowEmpty' => true,
    'name' => null,
    'onChange' => null,
])

@php
    // Selecting is one expression in three places, so it is built once.
    $onSelect = fn($value) => "open = false; {$model} = {$value};" .
        ($onChange ? " \$nextTick(() => { {$onChange} });" : '');
@endphp

<div class="relative w-full" x-data="{ open: false }" @keydown.escape.window="open = false">

    @if ($required)
        <input type="text" x-model="{{ $model }}" required
            @if ($name) name="{{ $name }}" @endif
            class="absolute inset-0 h-full w-full opacity-0 pointer-events-none" aria-hidden="true" />
    @elseif ($name)
        <input type="hidden" name="{{ $name }}" x-model="{{ $model }}" />
    @endif

    <button type="button" role="combobox" aria-haspopup="listbox" :aria-expanded="open" @click="open = !open"
        :class="open ? 'border-[#108c2a] ring-2 ring-[#108c2a]/15' : 'border-gray-200 hover:border-gray-300'"
        class="flex h-[42px] w-full items-center gap-2.5 rounded-lg border bg-white px-3 text-left shadow-sm outline-none transition-all">

        <span class="flex-1 truncate text-sm">
            {{-- One span per item, only the matching one shown. A closure over
                 the parent scope would be shorter but would not stay reactive. --}}
            <span x-show="!{{ $model }}" class="text-gray-400">{{ $placeholder }}</span>
            <template x-for="{{ $as }} in {{ $items }}" :key="{{ $as }}.{{ $itemKey }}">
                <span x-show="String({{ $model }}) === String({{ $as }}.{{ $itemKey }})"
                    class="font-semibold text-gray-800" x-text="{{ $itemLabel }}"></span>
            </template>
        </span>

        <svg class="h-3.5 w-3.5 shrink-0 text-gray-400 transition-transform" :class="open && 'rotate-180'"
            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
        </svg>
    </button>

    <div x-cloak x-show="open" @click.away="open = false" role="listbox"
        x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="absolute right-0 left-0 z-50 mt-2 max-h-64 overflow-y-auto rounded-xl border border-gray-100 bg-white p-1.5 shadow-[0_10px_40px_-8px_rgba(0,0,0,0.18)]"
        style="display: none;">

        @if ($allowEmpty)
            <button type="button" role="option" @click="{{ $onSelect("''") }}"
                :class="!{{ $model }} ? 'bg-[#108c2a]/10 text-[#108c2a] font-bold' : 'text-gray-700 hover:bg-gray-50'"
                class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2.5 text-left text-sm transition-colors">
                <span class="flex-1 truncate">{{ $placeholder }}</span>
                <svg class="h-4 w-4 shrink-0" x-show="!{{ $model }}" fill="none" stroke="currentColor"
                    stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                </svg>
            </button>
        @endif

        <template x-for="{{ $as }} in {{ $items }}" :key="{{ $as }}.{{ $itemKey }}">
            <button type="button" role="option" @click="{{ $onSelect("{$as}.{$itemKey}") }}"
                :class="String({{ $model }}) === String({{ $as }}.{{ $itemKey }}) ?
                    'bg-[#108c2a]/10 text-[#108c2a] font-bold' : 'text-gray-700 hover:bg-gray-50'"
                class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2.5 text-left text-sm transition-colors">
                <span class="flex-1 truncate" x-text="{{ $itemLabel }}"></span>
                <svg class="h-4 w-4 shrink-0"
                    x-show="String({{ $model }}) === String({{ $as }}.{{ $itemKey }})"
                    fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                </svg>
            </button>
        </template>

        <p x-show="({{ $items }}).length === 0" class="px-2.5 py-3 text-center text-xs text-gray-400">
            {{ $emptyText }}
        </p>
    </div>
</div>
