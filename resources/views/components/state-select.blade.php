@props(['selected' => null, 'name' => 'state', 'label' => 'State'])

<div class="space-y-1.5">
    <label for="{{ $name }}" class="text-[11px] font-bold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i> {{ $label }}
    </label>
    <div class="relative">
        {{-- 🌟 $attributes->merge allows x-model to pass through from the parent modal --}}
        <select name="{{ $name }}" id="{{ $name }}"
            {{ $attributes->merge(['class' => 'w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 focus:border-[#108c2a] outline-none appearance-none cursor-pointer font-medium bg-gray-50/50']) }}>
            <option value="">Select State</option>
            @foreach (\App\Models\State::where('is_active', true)->orderBy('name')->get() as $state)
                <option value="{{ $state->id }}" {{ $selected == $state->id ? 'selected' : '' }}
                    data-code="{{ $state->code }}">
                    {{ $state->name }} ({{ $state->code }})
                </option>
            @endforeach
        </select>
        <i data-lucide="chevron-down" class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
    </div>
</div>