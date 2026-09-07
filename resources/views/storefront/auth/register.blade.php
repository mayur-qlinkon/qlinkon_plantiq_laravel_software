@extends('layouts.storefront')
@section('title', 'Create Account - ' . ($company->name ?? 'Store'))

@section('content')
<div class="flex-1 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gray-50/50">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Create Account</h2>
            <p class="text-sm text-gray-500 mt-2">Join <span class="font-bold text-gray-800">{{ $company->name }}</span> for faster checkout</p>
        </div>

        <form action="{{ tenant_url('register') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-[12px] font-bold text-gray-700 uppercase tracking-wide mb-1.5">Full Name <span class="text-red-500">*</span></label>
                <div class="relative">
                    <i data-lucide="user" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus
                        class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none @error('name') border-red-300 @enderror">
                </div>
                @error('name') <p class="text-[11px] font-semibold text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-[12px] font-bold text-gray-700 uppercase tracking-wide mb-1.5">Email Address <span class="text-red-500">*</span></label>
                <div class="relative">
                    <i data-lucide="mail" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required
                        class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none @error('email') border-red-300 @enderror">
                </div>
                @error('email') <p class="text-[11px] font-semibold text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phone" class="block text-[12px] font-bold text-gray-700 uppercase tracking-wide mb-1.5">Phone Number</label>
                <div class="relative">
                    <i data-lucide="phone" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                    <input type="tel" name="phone" id="phone" value="{{ old('phone') }}"
                        pattern="[0-9]{10}"
                        maxlength="10"
                        minlength="10"
                        inputmode="numeric"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                        class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none @error('phone') border-red-300 @enderror">
                </div>
                @error('phone') <p class="text-[11px] font-semibold text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4 pt-1">
                <div x-data="{ show: false }">
                    <label for="password" class="block text-[12px] font-bold text-gray-700 uppercase tracking-wide mb-1.5">Password <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input :type="show ? 'text' : 'password'" name="password" id="password" required
                            class="w-full px-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none @error('password') border-red-300 @enderror">
                        <button type="button" @click="show = !show" tabindex="-1"
                            :aria-label="show ? 'Hide password' : 'Show password'"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                            <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                        </button>
                    </div>
                </div>
                <div x-data="{ show: false }">
                    <label for="password_confirmation" class="block text-[12px] font-bold text-gray-700 uppercase tracking-wide mb-1.5">Confirm <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input :type="show ? 'text' : 'password'" name="password_confirmation" id="password_confirmation" required
                            class="w-full px-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none">
                        <button type="button" @click="show = !show" tabindex="-1"
                            :aria-label="show ? 'Hide password' : 'Show password'"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                            <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                        </button>
                    </div>
                </div>
            </div>
            @error('password') <p class="text-[11px] font-semibold text-red-500 mt-1">{{ $message }}</p> @enderror

            <div class="pt-4">
                <button type="submit" class="w-full py-3 px-4 rounded-xl text-sm font-bold text-white transition-all hover:shadow-lg hover:-translate-y-0.5" style="background: var(--brand-600);">
                    Create Account
                </button>
            </div>
        </form>

        <p class="mt-8 text-center text-sm text-gray-600">
            Already have an account? 
            <a href="{{ tenant_url('login') }}" class="font-bold text-brand-600 hover:text-brand-700 transition-colors">Log in</a>
        </p>
    </div>
</div>
@endsection