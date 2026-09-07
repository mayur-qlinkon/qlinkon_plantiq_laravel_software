@props([
    'name' => 'payment_method_id',
    'selected' => null,
    'label' => 'Payment Method',
    'required' => true,
    'showIcons' => true,
    'xModel' => null,

    // Alpine expression deciding whether the field is required right now, e.g.
    // "global.amount_paid > 0". When given, it drives both the select's
    // required attribute and the asterisk, so the label never claims a field
    // is mandatory while the browser disagrees.
    'requiredWhen' => null,

    // 'native' (default) keeps the plain <select> every existing caller
    // renders today. 'rich' swaps in the POS-style custom dropdown while
    // keeping the same hidden <select> underneath, so x-model, form submit
    // and focusCustomSelect() all keep working unchanged.
    // 'rich' is the default now. 'native' stays as an escape hatch for any
    // screen whose container clips an absolutely-positioned dropdown.
    'variant' => 'rich',
    'placeholder' => 'Select Method',
])

@php
    $methods = \App\Models\PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();

    // Fall back to the active store's configured default when the caller did
// not pass an explicit selection. Applied here rather than in each form so
// every screen using this component behaves the same way.
// Guarded against a default that has since been deactivated or deleted.
if ($selected === null) {
    $storeDefaultId = active_store()?->default_payment_method_id;

    if ($storeDefaultId && $methods->contains('id', $storeDefaultId)) {
            $selected = $storeDefaultId;
        }
    }
@endphp

<div class="w-full">
    @if ($label)
        <label class="block text-[12px] font-bold text-gray-600 uppercase tracking-wider mb-2">
            {{ $label }}
            @if ($requiredWhen)
                <span class="text-red-500" x-show="{{ $requiredWhen }}" x-cloak>*</span>
            @elseif ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    @if ($variant === 'rich')
        <div class="relative"
            x-data="paymentMethodRich({
                methods: @js($methods->map(fn($m) => ['id' => (string) $m->id, 'name' => $m->name ?? $m->label, 'slug' => $m->slug])->values()),
                initial: @js((string) (old($name, $selected) ?? '')),
                placeholder: @js($placeholder),
            })"
            @keydown.escape.window="open = false">

            {{-- The real field. Hidden the same way x-custom-select hides its
                 own, so the form submits normally and focusCustomSelect()
                 still finds it by id. --}}
            <select name="{{ $name }}" id="{{ $name }}" x-ref="nativeSelect"
                @if ($requiredWhen) :required="{{ $requiredWhen }}"
                @elseif ($required)
                    required @endif
                @if ($xModel) x-model="{{ $xModel }}" @endif
                {{ $attributes->except('class') }}
                {{-- Invisible but still laid out and focusable. sr-only uses
                     clip-path, and browsers refuse to focus a clipped control,
                     so a failed "required" check aborted the submit silently
                     instead of showing its bubble. --}}
                class="absolute inset-0 h-full w-full opacity-0 pointer-events-none" aria-hidden="true">
                <option value="">{{ $placeholder }}</option>

                @foreach ($methods as $method)
                    <option value="{{ $method->id }}" data-slug="{{ $method->slug }}"
                        data-online="{{ $method->is_online }}"
                        {{ old($name, $selected) == $method->id ? 'selected' : '' }}>
                        {{ $method->name ?? $method->label }}
                    </option>
                @endforeach
            </select>

            <button type="button" role="combobox" aria-haspopup="listbox" :aria-expanded="open"
                @click="open = !open"
                :class="open ? 'border-[#108c2a] ring-2 ring-[#108c2a]/15' : 'border-gray-200 hover:border-gray-300'"
                class="flex h-[42px] w-full items-center gap-2.5 rounded-xl border bg-white px-3 shadow-sm outline-none transition-all">
                <i :data-lucide="icon(selected?.slug)" class="h-4 w-4 shrink-0 text-[#108c2a]"></i>
                <span class="flex-1 truncate text-left text-[13px]"
                    :class="value === '' ? 'font-medium text-gray-400' : 'font-bold text-gray-800'"
                    x-text="selectedLabel"></span>
                <i data-lucide="chevron-down" class="h-3.5 w-3.5 shrink-0 text-gray-400 transition-transform"
                    :class="open && 'rotate-180'"></i>
            </button>

            <div x-cloak x-show="open" @click.away="open = false" role="listbox"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="absolute right-0 left-0 z-50 mt-2 max-h-72 overflow-y-auto rounded-xl border border-gray-100 bg-white p-1.5 shadow-[0_10px_40px_-8px_rgba(0,0,0,0.18)]"
                style="display: none;">
                <p class="px-2.5 pt-1.5 pb-2 text-[9px] font-black tracking-widest text-gray-400 uppercase">
                    {{ $label ?: 'Payment Method' }}
                </p>

                <button type="button" role="option" @click="select('')"
                    :class="value === '' ? 'bg-[#108c2a]/10 text-[#108c2a]' : 'text-gray-700 hover:bg-gray-50'"
                    class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2.5 text-left transition-colors">
                    <i data-lucide="circle-slash" class="h-4 w-4 shrink-0"
                        :class="value === '' ? 'text-[#108c2a]' : 'text-gray-400'"></i>
                    <span class="flex-1 truncate text-[13px]"
                        :class="value === '' ? 'font-bold' : 'font-medium'">{{ $placeholder }}</span>
                    <i data-lucide="check" class="h-4 w-4 shrink-0 text-[#108c2a]" x-show="value === ''"></i>
                </button>

                <template x-for="pm in methods" :key="pm.id">
                    <button type="button" role="option" @click="select(pm.id)"
                        :class="isSelected(pm.id) ? 'bg-[#108c2a]/10 text-[#108c2a]' : 'text-gray-700 hover:bg-gray-50'"
                        class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2.5 text-left transition-colors">
                        <i :data-lucide="icon(pm.slug)" class="h-4 w-4 shrink-0"
                            :class="isSelected(pm.id) ? 'text-[#108c2a]' : 'text-gray-400'"></i>
                        <span class="flex-1 truncate text-[13px]"
                            :class="isSelected(pm.id) ? 'font-bold' : 'font-medium'" x-text="pm.name"></span>
                        <i data-lucide="check" class="h-4 w-4 shrink-0 text-[#108c2a]" x-show="isSelected(pm.id)"></i>
                    </button>
                </template>
            </div>
        </div>
    @else
    <div class="relative group">
        <select name="{{ $name }}" id="{{ $name }}"
            @if ($requiredWhen) :required="{{ $requiredWhen }}"
            @elseif ($required)
                required @endif
            @if ($xModel) x-model="{{ $xModel }}" @endif
            @if ($showIcons) data-payment-selector @endif
            {{ $attributes->merge(['class' => 'w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2.5 text-sm text-gray-800 font-medium focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a] outline-none transition-all appearance-none bg-white shadow-sm cursor-pointer']) }}>
            <option value="">Select Method</option>

            @foreach ($methods as $method)
                <option value="{{ $method->id }}" data-slug="{{ $method->slug }}"
                    data-online="{{ $method->is_online }}"
                    {{ old($name, $selected) == $method->id ? 'selected' : '' }}>
                    {{ $method->name ?? $method->label }}
                </option>
            @endforeach
        </select>

        @if ($showIcons)
            <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                id="icon-{{ $name }}">
                <i data-lucide="wallet" class="w-4 h-4"></i>
            </div>
        @endif

        <div class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
            <i data-lucide="chevron-down" class="w-4 h-4"></i>
        </div>
    </div>
    @endif
</div>

@once
    <script>
        /**
         * Alpine factory for the 'rich' variant. It never owns the value —
         * the hidden <select> does. Every selection is written through that
         * element and re-broadcast as a native change event, so the parent's
         * x-model, plain form submits and any @change listener all still see
         * exactly what they saw with the native variant.
         */
        window.paymentMethodRich = function(config) {
            return {
                open: false,
                methods: config.methods || [],
                placeholder: config.placeholder || 'Select Method',
                value: config.initial || '',

                init() {
                    // Resync when the value is changed from outside — a parent
                    // x-model write, a form reset, or our own select().
                    this.$refs.nativeSelect.addEventListener('change', () => {
                        this.value = this.$refs.nativeSelect.value;
                        this.refreshIcons();
                    });
                    this.refreshIcons();
                },

                get selected() {
                    return this.methods.find((m) => String(m.id) === String(this.value)) || null;
                },

                get selectedLabel() {
                    return this.selected ? this.selected.name : this.placeholder;
                },

                isSelected(id) {
                    return String(this.value) === String(id);
                },

                icon(slug) {
                    const map = {
                        cash: 'banknote',
                        upi: 'qr-code',
                        card: 'credit-card',
                        credit_card: 'credit-card',
                        bank_transfer: 'landmark',
                        cheque: 'scroll-text',
                    };
                    return map[slug] || 'wallet';
                },

                select(id) {
                    this.open = false;
                    this.$refs.nativeSelect.value = String(id ?? '');
                    this.$refs.nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                },

                refreshIcons() {
                    this.$nextTick(() => {
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    });
                },
            };
        };

        document.addEventListener('change', function(e) {
            if (e.target.matches('select[data-payment-selector]')) {
                const select = e.target;
                const container = document.getElementById('icon-' + select.name);
                const slug = select.options[select.selectedIndex].getAttribute('data-slug');

                let iconName = 'wallet';
                if (slug === 'cash') iconName = 'banknote';
                if (slug === 'upi') iconName = 'qr-code';
                if (slug === 'card') iconName = 'credit-card';
                if (slug === 'bank_transfer') iconName = 'landmark';

                if (container) {
                    container.innerHTML = `<i data-lucide="${iconName}" class="w-4 h-4"></i>`;
                    lucide.createIcons();
                }
            }
        });
    </script>
@endonce
