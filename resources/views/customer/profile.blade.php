@extends('layouts.customer')

@section('title', 'My Profile')

@section('content')

    <div class="space-y-6">

        {{-- 1. Personal Information Card --}}
        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
            <div class="bg-slate-50/80 px-6 py-5 border-b border-slate-100">
                <h2 class="font-bold text-slate-900 text-lg tracking-tight">Personal Information</h2>
                <p class="text-sm text-slate-500 mt-1">Manage your basic account details and avatar.</p>
            </div>

            <div class="p-6">
                {{-- FORM START --}}
                <form id="profile-form" onsubmit="return false;" enctype="multipart/form-data"
                    class="flex flex-col md:flex-row gap-8 lg:gap-12">
                    @csrf

                    {{-- Avatar Section --}}
                    <div class="flex-shrink-0 flex flex-col items-center gap-3">
                        <div class="relative group">
                            <div class="w-32 h-32 rounded-full border-4 border-white shadow-md overflow-hidden bg-slate-50">
                                <img id="avatar-preview"
                                    src="{{ $user->image ? asset('storage/' . $user->image) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=f1f5f9&color=64748b&size=256' }}"
                                    alt="Profile" class="w-full h-full object-cover">
                            </div>
                            <label
                                class="absolute bottom-1 right-1 bg-slate-900 text-white p-2.5 rounded-full cursor-pointer hover:bg-brand-500 transition-all shadow-md hover:-translate-y-0.5">
                                <i data-lucide="camera" class="w-4 h-4"></i>
                                <input type="file" name="avatar" class="hidden" accept="image/jpeg,image/png,image/webp"
                                    onchange="previewImage(this)">
                            </label>
                        </div>
                        <span class="text-[11px] text-slate-400 font-medium">Allowed *.jpeg, *.png, *.webp</span>
                        <span class="text-[11px] text-red-500 font-bold error-msg" id="error-avatar"></span>
                    </div>

                    {{-- Form Fields --}}
                    <div class="flex-1 space-y-6 w-full">

                        <div>
                            <label class="block font-bold text-slate-700 mb-2 text-[12px] uppercase tracking-wide">
                                Full Name <span class="text-red-500 ml-0.5">*</span>
                            </label>
                            <div class="relative">
                                <i data-lucide="user"
                                    class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}"
                                    class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all bg-slate-50 focus:bg-white">
                            </div>
                            <span class="text-[11px] text-red-500 font-bold error-msg mt-1 block" id="error-name"></span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block font-bold text-slate-700 mb-2 text-[12px] uppercase tracking-wide">Email
                                    Address</label>
                                <div class="relative">
                                    <i data-lucide="mail"
                                        class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                                    <input type="email" name="email" value="{{ $user->email }}"
                                        class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-100 text-slate-500 cursor-not-allowed"
                                        readonly title="Email cannot be changed">
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1.5 font-medium">Contact support to change your
                                    email.</p>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-2 text-[12px] uppercase tracking-wide">Phone
                                    Number</label>
                                <div class="relative">
                                    <i data-lucide="phone"
                                        class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                                    <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}"
                                        placeholder="10-digit number" maxlength="10" inputmode="numeric" pattern="[0-9]{10}"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)"
                                        class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all bg-slate-50 focus:bg-white">
                                </div>
                                <span class="text-[11px] text-red-500 font-bold error-msg mt-1 block"
                                    id="error-phone"></span>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 mt-6">
                            <button type="button" onclick="submitProfile()" id="save-btn"
                                class="px-6 py-2.5 bg-brand-500 hover:bg-brand-700 text-white rounded-xl text-sm font-bold shadow-md shadow-brand-500/20 transition-all flex items-center gap-2 hover:-translate-y-0.5">
                                <span>Save Changes</span>
                                <i data-lucide="loader-2" class="w-4 h-4 animate-spin hidden" id="btn-loader"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function previewImage(input) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('avatar-preview').src = e.target.result;
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            }

            function submitProfile() {
                let form = document.getElementById('profile-form');
                let formData = new FormData(form);
                let btn = document.getElementById('save-btn');
                let loader = document.getElementById('btn-loader');

                // Clear errors
                document.querySelectorAll('.error-msg').forEach(el => el.textContent = '');

                // UI Loading
                btn.disabled = true;
                btn.classList.add('opacity-75');
                loader.classList.remove('hidden');

                const url = "{{ tenant_url('portal/profile') }}";

                fetch(url, {
                        method: "POST",
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: formData // Sending FormData handles the image upload natively
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
                            title: 'Saved!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false,
                            customClass: {
                                popup: 'rounded-2xl'
                            }
                        });

                        // Update avatar globally without page refresh
                        if (data.avatar_url) {
                            document.getElementById('avatar-preview').src = data.avatar_url;

                            // 🌟 ADD THIS LINE to update the top-right header avatar instantly!
                            const headerAvatar = document.getElementById('header-avatar');
                            if (headerAvatar) headerAvatar.src = data.avatar_url;
                        }
                    })
                    .catch(error => {
                        if (error.status === 422) {
                            let errors = error.data.errors;
                            for (const [key, value] of Object.entries(errors)) {
                                let errorSpan = document.getElementById('error-' + key);
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
            }
        </script>
    @endpush
@endsection
