{{--
    Custom Select Component
    ───────────────────────
    A fully-styled dropdown replacement for native <select>. Browsers refuse to
    style the native option-list, so this renders our own list with Alpine.js
    while keeping a real <select> in the DOM (visually hidden) so it works as
    a drop-in replacement everywhere:

      • Plain HTML forms        → submits via the hidden native <select>.
      • @change="submitForm"   → native 'change' event still fires.
      • GET filter bars        → works exactly like a native <select>.

    USAGE
    ─────
    <x-custom-select
        name="status"
        placeholder="All Statuses"
        :options="['draft' => 'Draft', 'confirmed' => 'Confirmed']"
        selected="{{ request('status') }}"
    />

    PROPS
    ─────
    name          string   required — form field name (and id, unless $id given)
    options       array    required — ['value' => 'Label', ...]
    selected      mixed    null     — pre-selected value (falls back to old($name))
    placeholder   string   'Select' — shown when nothing is selected
    required      bool     false
    disabled      bool     false
    icons         array    []       — ['value' => 'lucide-icon-name', ...] optional
    id            string   null     — defaults to $name
--}}

@props([
    'name' => '',
    'options' => [],
    'selected' => null,
    'placeholder' => 'Select',
    'required' => false,
    'disabled' => false,
    'icons' => [],
    'id' => null,
    'onchange' => null,
])

@php
    $id = $id ?? $name;
    $currentValue = old($name, $selected);
    $listboxId = $id . '-listbox-' . substr(md5($id . microtime()), 0, 6);
@endphp

<div
    x-data="customSelect({
        options: @js($options),
        icons: @js($icons),
        initial: @js((string) ($currentValue ?? '')),
        placeholder: @js($placeholder),
    })"
    class="relative w-full"
    @keydown.escape.window="open = false"
>
    {{-- Real form field. This is what actually submits / triggers @change. --}}
    <select
        name="{{ $name }}"
        id="{{ $id }}"
        x-ref="nativeSelect"
        x-model="value"
        {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        @if($onchange) onchange="{{ $onchange }}" @endif
        class="sr-only absolute pointer-events-none"
        tabindex="-1"
        aria-hidden="true"
    >
        <option value="">{{ $placeholder }}</option>
        @foreach ($options as $val => $label)
            <option value="{{ $val }}">{{ $label }}</option>
        @endforeach
    </select>

    {{-- Custom trigger button --}}
    <button
        type="button"
        id="{{ $listboxId }}-trigger"
        @click="!{{ $disabled ? 'true' : 'false' }} && (open = !open)"
        @click.away="open = false"
        :aria-expanded="open"
        :disabled="{{ $disabled ? 'true' : 'false' }}"
        role="combobox"
        aria-haspopup="listbox"
        aria-controls="{{ $listboxId }}"
        {{ $attributes->merge([
            'class' => 'w-full flex items-center justify-between gap-2 border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-left bg-white outline-none transition-all cursor-pointer hover:border-gray-300 focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a] disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-50'
        ]) }}
        :class="open ? 'border-[#108c2a] ring-1 ring-[#108c2a]' : ''"
    >
        <span class="flex items-center gap-2 truncate">
            <template x-if="icons[value]">
                <i :data-lucide="icons[value]" class="w-4 h-4 text-gray-500 shrink-0"></i>
            </template>
            <span :class="value === '' ? 'text-gray-400' : 'text-gray-800 font-medium'" x-text="label" class="truncate"></span>
        </span>
        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''"></i>
    </button>

    {{-- Custom option list --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        id="{{ $listboxId }}"
        role="listbox"
        class="absolute z-50 mt-1.5 min-w-full w-max max-w-[280px] max-h-64 overflow-y-auto bg-white border border-gray-100 rounded-xl shadow-2xl py-1.5 left-0 origin-top-left [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:bg-gray-200 [&::-webkit-scrollbar-thumb]:rounded-full hover:[&::-webkit-scrollbar-thumb]:bg-gray-300"
        style="display: none;"
    >
        <button
            type="button"
            role="option"
            @click="select('')"
            class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-left transition-colors whitespace-nowrap"
            :class="value === '' ? 'bg-[#108c2a]/10 text-[#108c2a] font-bold' : 'text-gray-600 hover:bg-gray-50'"
        >
            <span x-text="placeholder"></span>
        </button>

        <template x-for="opt in optionList" :key="opt.value">
            <button
                type="button"
                role="option"
                @click="select(opt.value)"
                class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-left transition-colors whitespace-nowrap"
                :class="value === opt.value ? 'bg-[#108c2a]/10 text-[#108c2a] font-bold' : 'text-gray-700 hover:bg-gray-50'"
            >
                <template x-if="icons[opt.value]">
                    <i :data-lucide="icons[opt.value]" class="w-4 h-4 shrink-0" :class="value === opt.value ? 'text-[#108c2a]' : 'text-gray-400'"></i>
                </template>
                <span x-text="opt.label"></span>
            </button>
        </template>
    </div>
</div>