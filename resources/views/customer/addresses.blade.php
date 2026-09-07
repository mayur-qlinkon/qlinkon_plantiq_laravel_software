@extends('layouts.customer')

@section('title', 'My Addresses')

@section('content')
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">

        {{-- Card Header --}}
        <div class="bg-slate-50/80 px-6 py-5 border-b border-slate-100">
            <h2 class="font-bold text-slate-900 text-lg tracking-tight">Delivery Details</h2>
            <p class="text-sm text-slate-500 mt-1">Update your default shipping address for faster checkout.</p>
        </div>

        {{-- Card Body --}}
        <div class="p-6">
            {{-- Form --}}
            <form id="address-form" class="space-y-6">

                {{-- Row 1: Contact Info --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block font-bold text-slate-700 mb-2 text-[12px] uppercase tracking-wide">
                            Full Name <span class="text-red-500 ml-0.5">*</span>
                        </label>
                        <div class="relative">
                            <i data-lucide="user"
                                class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                            <input type="text" name="name" id="name"
                                value="{{ old('name', $client?->name ?? $user->name) }}"
                                class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all bg-slate-50 focus:bg-white">
                        </div>
                        <span class="text-[11px] font-semibold text-red-500 error-msg mt-1 block" id="error-name"></span>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-2 text-[12px] uppercase tracking-wide">
                            Phone Number <span class="text-red-500 ml-0.5">*</span>
                        </label>
                        <div class="relative">
                            <i data-lucide="phone"
                                class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                            <input type="tel" name="phone" id="phone"
                                value="{{ old('phone', $client?->phone ?? $user->phone) }}" maxlength="10"
                                pattern="[0-9]{10}" inputmode="numeric"
                                oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)"
                                class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all bg-slate-50 focus:bg-white">
                        </div>
                        <span class="text-[11px] font-semibold text-red-500 error-msg mt-1 block" id="error-phone"></span>
                    </div>
                </div>

                {{-- Row 2: Full Address --}}
                <div>
                    <label class="block font-bold text-slate-700 mb-2 text-[12px] uppercase tracking-wide">
                        Complete Address <span class="text-red-500 ml-0.5">*</span>
                    </label>
                    <textarea name="address" id="address" rows="3"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all bg-slate-50 focus:bg-white resize-none"
                        placeholder="House/flat no, street, area, landmark">{{ old('address', $client?->address) }}</textarea>
                    <span class="text-[11px] font-semibold text-red-500 error-msg mt-1 block" id="error-address"></span>
                </div>

                {{-- Row 3: City & Zip --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block font-bold text-slate-700 mb-2 text-[12px] uppercase tracking-wide">
                            City <span class="text-red-500 ml-0.5">*</span>
                        </label>
                        <input type="text" name="city" id="city" value="{{ old('city', $client?->city) }}"
                            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all bg-slate-50 focus:bg-white">
                        <span class="text-[11px] font-semibold text-red-500 error-msg mt-1 block" id="error-city"></span>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-2 text-[12px] uppercase tracking-wide">
                            PIN Code / Zip <span class="text-red-500 ml-0.5">*</span>
                        </label>
                        <input type="text" name="zip_code" id="zip_code"
                            value="{{ old('zip_code', $client?->zip_code) }}" inputmode="numeric" pattern="[0-9]*"
                            maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all bg-slate-50 focus:bg-white" />
                        <span class="text-[11px] font-semibold text-red-500 error-msg mt-1 block"
                            id="error-zip_code"></span>
                    </div>
                </div>

                {{-- Row 4: State & Country --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block font-bold text-slate-700 mb-2 text-[12px] uppercase tracking-wide">
                            State / Province <span class="text-red-500 ml-0.5">*</span>
                        </label>
                        <div class="relative">
                            <select name="state_id" id="state_id"
                                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all appearance-none bg-slate-50 focus:bg-white text-slate-900">
                                <option value="" disabled {{ !$client?->state_id ? 'selected' : '' }}>Select State
                                </option>
                                @foreach ($states as $state)
                                    <option value="{{ $state->id }}"
                                        {{ $client?->state_id == $state->id ? 'selected' : '' }}>
                                        {{ $state->name }}
                                    </option>
                                @endforeach
                            </select>
                            <i data-lucide="chevron-down"
                                class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                        </div>
                        <span class="text-[11px] font-semibold text-red-500 error-msg mt-1 block"
                            id="error-state_id"></span>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-2 text-[12px] uppercase tracking-wide">
                            Country <span class="text-red-500 ml-0.5">*</span>
                        </label>
                        <div class="relative">
                            <select name="country" id="country"
                                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all appearance-none bg-slate-50 focus:bg-white">
                                <option value="India" {{ ($client?->country ?? 'India') == 'India' ? 'selected' : '' }}>
                                    India</option>
                            </select>
                            <i data-lucide="chevron-down"
                                class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                        </div>
                        <span class="text-[11px] font-semibold text-red-500 error-msg mt-1 block" id="error-country"></span>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-4 pt-6 mt-6 border-t border-slate-100">
                    <button type="button" id="save-btn"
                        class="px-6 py-2.5 bg-brand-500 hover:bg-brand-700 text-white rounded-xl text-sm font-bold shadow-md shadow-brand-500/20 transition-all flex items-center gap-2 hover:-translate-y-0.5">
                        <span>Save Address</span>
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin hidden" id="btn-loader"></i>
                    </button>
                    <button type="reset"
                        class="px-6 py-2.5 border border-slate-200 rounded-xl text-sm font-bold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                        Discard
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const btn = document.getElementById('save-btn');

                if (btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();

                        const form = document.getElementById('address-form');
                        const loader = document.getElementById('btn-loader');
                        const formData = new FormData(form);

                        // Clear errors
                        document.querySelectorAll('.error-msg').forEach(el => el.textContent = '');

                        // UI Loading
                        btn.disabled = true;
                        btn.classList.add('opacity-75');
                        loader.classList.remove('hidden');

                        // Dynamic URL fetching the tenant slug
                        const url = "{{ tenant_url('portal/addresses') }}";

                        fetch(url, {
                                method: "POST",
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                        .getAttribute('content')
                                },
                                body: formData
                            })
                            .then(async response => {
                                const data = await response.json();
                                if (!response.ok) return Promise.reject({
                                    status: response.status,
                                    data: data
                                });
                                return data;
                            })
                            .then(data => {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: data.message,
                                    showConfirmButton: false,
                                    timer: 1500,
                                    customClass: {
                                        popup: 'rounded-2xl'
                                    }
                                });
                            })
                            .catch(error => {
                                if (error.status === 422) {
                                    const errors = error.data.errors;
                                    for (const [key, value] of Object.entries(errors)) {
                                        const errorSpan = document.getElementById('error-' + key);
                                        if (errorSpan) errorSpan.textContent = value[0];
                                    }
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: error.data?.message || 'Something went wrong.',
                                        confirmButtonColor: 'var(--color-primary)',
                                        customClass: {
                                            popup: 'rounded-2xl',
                                            confirmButton: 'rounded-xl'
                                        }
                                    });
                                }
                            })
                            .finally(() => {
                                btn.disabled = false;
                                btn.classList.remove('opacity-75');
                                loader.classList.add('hidden');
                            });
                    });
                }
            });
        </script>
    @endpush
@endsection
