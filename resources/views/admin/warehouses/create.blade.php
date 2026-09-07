@extends('layouts.admin')

@section('title', 'Add Warehouse')

@section('header-title')
    {{-- The back link sits in the header alongside the title, where it is on
         every other create screen. Floating on the right of the page body it
         overlapped the first card and had nothing to align to. --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.warehouses.index') }}"
            class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round">
                <path d="M19 12H5M12 5l-7 7 7 7" />
            </svg>
        </a>
        <div>
            <h1 class="text-xs sm:text-sm font-bold text-gray-400 uppercase tracking-widest">Add Warehouse</h1>
            <p class="mt-0.5 text-xs font-medium text-gray-400">Create a new storage location</p>
        </div>
    </div>
@endsection

@section('content')
    <div class="w-full space-y-6 pb-10" x-data="warehouseForm()">

        {{-- Error Summary --}}
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl shadow-sm text-sm">
                <div class="font-bold flex items-center gap-2 mb-1">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i> Please fix the following mistakes:
                </div>
                <ul class="list-disc list-inside ml-6 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form --}}
        <form action="{{ route('admin.warehouses.store') }}" method="POST" class="space-y-6" @submit="isSubmitting = true">
            @csrf

            {{-- SECTION 1: Basic Info --}}
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-base font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">1. Basic Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1.5">Warehouse Name <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            placeholder="e.g. Main Godown"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a] outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1.5">Linked Store <span
                                class="text-red-500">*</span></label>
                        @php
                            // Defaults to the store already being viewed, since a
                            // warehouse is almost always being added for it. A
                            // submitted value still wins so a validation bounce
                            // does not throw away the choice.
                            $selectedStoreId = old('store_id', active_store()?->id);
                        @endphp
                        {{-- Same custom dropdown as the POS store switcher — the browser
                             refuses to style a native option list, so the menu never
                             matched the rest of the app. The hidden input keeps the
                             posted field name and the required rule intact. --}}
                        <div x-data="{
                            open: false,
                            storeId: '{{ $selectedStoreId }}',
                            stores: @js($stores->map->only(['id', 'name'])->values()),
                        }" class="relative">
                            <input type="hidden" name="store_id" :value="storeId" required />

                            <button type="button" @click="open = !open"
                                :class="open ? 'border-[#108c2a] ring-1 ring-[#108c2a]' : 'border-gray-300 hover:border-gray-400'"
                                class="flex w-full items-center gap-2 rounded-md border bg-white px-3.5 py-2.5 text-left transition-all">
                                <i data-lucide="store" class="h-4 w-4 shrink-0 text-[#108c2a]"></i>
                                <span class="flex-1 truncate text-sm"
                                    :class="storeId ? 'font-semibold text-gray-800' : 'text-gray-400'"
                                    x-text="stores.find(s => s.id == storeId)?.name || 'Select a Store'"></span>
                                <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-gray-400 transition-transform"
                                    :class="open && 'rotate-180'"></i>
                            </button>

                            <div x-cloak x-show="open" @click.away="open = false" @keydown.escape.window="open = false"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="absolute left-0 z-50 mt-2 max-h-56 w-full overflow-y-auto rounded-xl border border-gray-100 bg-white p-1.5 shadow-[0_10px_40px_-8px_rgba(0,0,0,0.18)]">
                                <p class="px-2.5 pt-1.5 pb-2 text-[9px] font-black tracking-widest text-gray-400 uppercase">
                                    Select Store
                                </p>
                                <template x-for="s in stores" :key="s.id">
                                    <button type="button" @click="storeId = s.id; open = false"
                                        :class="storeId == s.id ? 'bg-[#108c2a]/10 text-[#108c2a]' : 'text-gray-700 hover:bg-gray-50'"
                                        class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2.5 text-left transition-colors">
                                        <i data-lucide="store" class="h-4 w-4 shrink-0"
                                            :class="storeId == s.id ? 'text-[#108c2a]' : 'text-gray-400'"></i>
                                        <span class="flex-1 truncate text-[13px]"
                                            :class="storeId == s.id ? 'font-bold' : 'font-medium'" x-text="s.name"></span>
                                        <i data-lucide="check" class="h-4 w-4 shrink-0 text-[#108c2a]"
                                            x-show="storeId == s.id"></i>
                                    </button>
                                </template>
                            </div>
                        </div>
                        @if (active_store() && !old('store_id'))
                            <p class="mt-1.5 text-[11px] font-medium text-gray-400">
                                Defaulted to the store you're currently viewing.
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- SECTION 2: Contact Details --}}
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-base font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">2. Contact Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1.5">Manager / Contact Person</label>
                        <input type="text" name="contact_person" value="{{ old('contact_person') }}"
                            placeholder="John Doe"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a] outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1.5">Phone Number</label>
                        <input type="tel" name="phone" x-model="phone" maxlength="10" placeholder="0000000000"
                            inputmode="numeric" @input="phone = phone.replace(/\D/g, '').slice(0,10)"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a] outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1.5">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="warehouse@example.com"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a] outline-none transition-all">
                    </div>
                </div>
            </div>

            {{-- SECTION 3: Location --}}
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-base font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">3. Location Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1.5">City</label>
                        <input type="text" name="city" value="{{ old('city') }}" placeholder="e.g. Ahmedabad"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a] outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1.5">State</label>
                        <x-custom-select
                            name="state_id"
                            placeholder="Select State"
                            :options="$states->pluck('name', 'id')->map(fn ($n) => (string) $n)->toArray()"
                            :selected="old('state_id')"
                        />
                    </div>
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1.5">Pincode / Zip</label>
                        <input type="text" name="zip_code" x-model="zip" maxlength="6" placeholder="380001"
                            inputmode="numeric" @input="zip = zip.replace(/\D/g, '').slice(0,6)"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a] outline-none transition-all">
                    </div>
                </div>
                <div>
                    <label class="block text-[13px] font-bold text-gray-700 mb-1.5">Full Address</label>
                    <textarea name="address" rows="2" placeholder="Exact storage site location..."
                        class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a] outline-none transition-all resize-y">{{ old('address') }}</textarea>
                </div>
            </div>

            {{-- SECTION 4: Settings --}}
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-base font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">4. Settings</h2>
                <div class="flex flex-col sm:flex-row gap-8">

                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_default" value="1" class="sr-only peer"
                            {{ old('is_default') ? 'checked' : '' }}>
                        {{-- 🌟 FIX: Added 'relative' class to this div so the white circle stays inside --}}
                        <div
                            class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600">
                        </div>
                        <div class="ms-3">
                            <span class="block text-[13px] font-bold text-gray-800">Primary Hub</span>
                            <span class="block text-xs text-gray-400 font-normal">Auto-route new items here</span>
                        </div>
                    </label>

                    <label class="inline-flex items-center cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="sr-only peer"
                            {{ old('is_active', true) ? 'checked' : '' }}>
                        {{-- 🌟 FIX: Added 'relative' class here as well --}}
                        <div
                            class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#108c2a]">
                        </div>
                        <div class="ms-3">
                            <span class="block text-[13px] font-bold text-gray-800">Active Status</span>
                            <span class="block text-xs text-gray-400 font-normal">Visible in dropdowns</span>
                        </div>
                    </label>

                </div>
            </div>

            {{-- Submit --}}
            <div class="flex justify-end pt-2">
                <button type="submit" :disabled="isSubmitting"
                    class="bg-[#108c2a] hover:bg-[#0c6b1f] text-white px-8 py-3 rounded-xl text-sm font-bold shadow-md flex items-center justify-center gap-2 transition-all disabled:opacity-70">
                    <i data-lucide="save" class="w-4 h-4" x-show="!isSubmitting"></i>
                    <i data-lucide="loader-2" class="w-4 h-4 animate-spin" x-show="isSubmitting" x-cloak></i>
                    <span x-text="isSubmitting ? 'Saving...' : 'Save Warehouse'"></span>
                </button>
            </div>

        </form>
    </div>
@endsection

@push('scripts')
    <script>
        function warehouseForm() {
            return {
                isSubmitting: false,
                phone: '{{ old('phone') }}',
                zip: '{{ old('zip_code') }}',
            }
        }
    </script>
@endpush
