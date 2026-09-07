@extends('layouts.admin')

@section('title', 'Edit Warehouse')

@section('header-title')
    <h1 class="text-xs sm:text-sm font-bold text-gray-400 uppercase tracking-widest">Edit Warehouse</h1>
@endsection

@section('content')
    <div class="w-full mx-auto space-y-6 pb-10" x-data="warehouseEditForm()">

        {{-- Header & Back Button --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <p class="text-sm text-gray-500 font-medium">Update storage location details and settings.</p>
            </div>
            <a href="{{ route('admin.warehouses.index') }}"
                class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg text-sm font-bold transition-colors shadow-sm shrink-0">
                Cancel & Back
            </a>
        </div>

        {{-- Error Summary --}}
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl shadow-sm text-sm">
                <div class="font-bold flex items-center gap-2 mb-1">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i> Please fix the following errors:
                </div>
                <ul class="list-disc list-inside ml-6 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form --}}
        <form action="{{ route('admin.warehouses.update', $warehouse->id) }}" method="POST" class="space-y-6"
            @submit="isSubmitting = true">
            @csrf
            @method('PUT')

            {{-- SECTION 1: Basic Info --}}
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-base font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">1. Basic Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1.5">Warehouse Name <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $warehouse->name) }}" required
                            placeholder="e.g. Main Godown"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a] outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1.5">Linked Store <span
                                class="text-red-500">*</span></label>
                        {{-- See create.blade.php: custom dropdown matching the POS store
                             switcher, with a hidden input carrying the posted value. --}}
                        <div x-data="{
                            open: false,
                            storeId: '{{ old('store_id', $warehouse->store_id) }}',
                            stores: @js($stores->map->only(['id', 'name'])->values()),
                        }" class="relative">
                            <input type="hidden" name="store_id" :value="storeId" required />

                            <button type="button" @click="open = !open"
                                :class="open ? 'border-[#108c2a] ring-1 ring-[#108c2a]' :
                                    'border-gray-300 hover:border-gray-400'"
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
                                        :class="storeId == s.id ? 'bg-[#108c2a]/10 text-[#108c2a]' :
                                            'text-gray-700 hover:bg-gray-50'"
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
                    </div>
                </div>
            </div>

            {{-- SECTION 2: Contact Details --}}
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-base font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">2. Contact Details <span
                        class="text-gray-400 font-normal text-xs">(Optional)</span></h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1.5">Manager / Contact Person</label>
                        <input type="text" name="contact_person"
                            value="{{ old('contact_person', $warehouse->contact_person) }}" placeholder="John Doe"
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
                        <input type="email" name="email" value="{{ old('email', $warehouse->email) }}"
                            placeholder="warehouse@example.com"
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
                        <input type="text" name="city" value="{{ old('city', $warehouse->city) }}"
                            placeholder="e.g. Ahmedabad"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a] outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1.5">State</label>
                        <x-custom-select name="state_id" placeholder="Select State" :options="$states->pluck('name', 'id')->map(fn($n) => (string) $n)->toArray()" :selected="old('state_id', (string) $warehouse->state_id)" />
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
                        class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a] outline-none transition-all resize-y">{{ old('address', $warehouse->address) }}</textarea>
                </div>
            </div>

            {{-- SECTION 4: Settings --}}
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-base font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">4. Settings</h2>
                <div class="flex flex-col sm:flex-row gap-8">

                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_default" value="1" class="sr-only peer"
                            {{ old('is_default', $warehouse->is_default) ? 'checked' : '' }}>
                        {{-- 🌟 FIX: 'relative' class included for proper alignment --}}
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
                            {{ old('is_active', $warehouse->is_active) ? 'checked' : '' }}>
                        {{-- 🌟 FIX: 'relative' class included for proper alignment --}}
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
                    <span x-text="isSubmitting ? 'Updating...' : 'Update Warehouse'"></span>
                </button>
            </div>

        </form>
    </div>
@endsection

@push('scripts')
    <script>
        function warehouseEditForm() {
            return {
                isSubmitting: false,
                phone: '{{ old('phone', $warehouse->phone) }}',
                zip: '{{ old('zip_code', $warehouse->zip_code) }}',
            }
        }
    </script>
@endpush
