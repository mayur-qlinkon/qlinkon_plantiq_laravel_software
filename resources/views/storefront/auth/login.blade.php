@extends ('layouts.storefront')
{{-- Ensure this points to your main storefront layout --}}
@section ('title', 'Login - ' . ($company->name ?? 'Store'))

@section ('content')
    <div class="flex flex-1 items-center justify-center bg-gray-50/50 px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full max-w-md rounded-2xl border border-gray-100 bg-white p-8 shadow-sm">
            <div class="mb-8 text-center">
                <h2 class="text-2xl font-bold tracking-tight text-gray-900">Welcome Back</h2>
                <p class="mt-2 text-sm text-gray-500">Log in to manage your orders at <span class="font-bold text-gray-800">{{ $company->name }}</span></p>
            </div>

            {{-- Global Error Message --}}
            @if (session('error'))
                <div class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4">
                    <i data-lucide="alert-circle" class="mt-0.5 h-5 w-5 shrink-0 text-red-500"></i>
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            @endif

            <form action="{{ tenant_url('login') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="mb-1.5 block text-[12px] font-bold tracking-wide text-gray-700 uppercase"
                        >Email Address</label
                    >
                    <div class="relative">
                        <i
                            data-lucide="mail"
                            class="absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-gray-400"
                        ></i>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none @error('email') border-red-300 @enderror"
                        />
                    </div>
                    @error ('email')
                        <p class="mt-1.5 text-[11px] font-semibold text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="mb-1.5 flex items-center justify-between">
                        <label for="password" class="block text-[12px] font-bold tracking-wide text-gray-700 uppercase"
                            >Password</label
                        >
                        <a
                            href="{{ route('password.request') }}"
                            class="text-brand-600 hover:text-brand-700 text-[12px] font-bold"
                            >Forgot Password?</a
                        >
                    </div>
                    <div class="relative" x-data="{ show: false }">
                        <i
                            data-lucide="lock"
                            class="absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-gray-400"
                        ></i>
                        <input
                            :type="show ? 'text' : 'password'"
                            name="password"
                            id="password"
                            required
                            class="w-full pl-10 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none @error('password') border-red-300 @enderror"
                        />
                        <button
                            type="button"
                            @click="show = !show"
                            tabindex="-1"
                            :aria-label="show ? 'Hide password' : 'Show password'"
                            class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 transition-colors hover:text-gray-600"
                        >
                            <svg
                                x-show="!show"
                                xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                            <svg
                                x-show="show"
                                x-cloak
                                xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
                                <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
                                <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
                                <line x1="2" x2="22" y1="2" y2="22" />
                            </svg>
                        </button>
                    </div>
                    @error ('password')
                        <p class="mt-1.5 text-[11px] font-semibold text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center">
                    <input
                        type="checkbox"
                        name="remember"
                        id="remember"
                        class="text-brand-600 focus:ring-brand-500 h-4 w-4 cursor-pointer rounded border-gray-300"
                    />
                    <label for="remember" class="ml-2 block cursor-pointer text-sm text-gray-600">Remember me</label>
                </div>

                <button
                    type="submit"
                    class="w-full rounded-xl px-4 py-3 text-sm font-bold text-white transition-all hover:-translate-y-0.5 hover:shadow-lg"
                    style="background: var(--brand-600)"
                >
                    Log In
                </button>
            </form>

            <p class="mt-8 text-center text-sm text-gray-600">
                Don't have an account?
                <a
                    href="{{ tenant_url('register') }}"
                    class="text-brand-600 hover:text-brand-700 font-bold transition-colors"
                    >Create one</a
                >
            </p>
        </div>
    </div>
@endsection
