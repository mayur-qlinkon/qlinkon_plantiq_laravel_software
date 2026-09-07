@extends ('layouts.auth')

@section ('title', 'Enter OTP — '.get_system_setting('app_name', config('app.name')))
@section ('heading', 'Check your inbox')
@section ('subheading', 'Enter the OTP we sent to '.$email.' and choose a new password')

@section ('panel')
    <div class="space-y-7">
        <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-white/15 bg-white/10">
            <svg class="text-brand-300 h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                />
            </svg>
        </div>

        <div>
            <h1 class="font-800 mb-3 text-3xl leading-snug tracking-tight text-white xl:text-4xl">
                Almost<br /><span class="text-brand-300">there!</span>
            </h1>
            <p class="text-brand-100/60 font-300 text-sm leading-relaxed">We've sent a one-time password to your email. It's valid for {{ get_system_setting('password_reset_expiry_minutes', 60) }} minutes.</p>
        </div>

        <div class="space-y-3.5">
            @foreach ([
                ['n' => '1', 'text' => 'Open your email inbox'],
                ['n' => '2', 'text' => 'Find the OTP from '.get_system_setting('app_name', config('app.name'))],
                ['n' => '3', 'text' => 'Enter the OTP and your new password below'],
            ] as $step)
                <div class="flex items-center gap-3">
                    <div
                        class="bg-brand-500/25 border-brand-400/25 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border"
                    >
                        <span class="font-700 text-brand-300 text-xs">{{ $step['n'] }}</span>
                    </div>
                    <span class="text-brand-100/55 font-400 text-sm">{{ $step['text'] }}</span>
                </div>
            @endforeach
        </div>

        <div class="border-t border-white/10 pt-2">
            <p class="text-brand-100/40 text-xs leading-relaxed">Didn't receive the email? Check your spam folder, or <a href="{{ route('password.request') }}" class="text-brand-300 hover:underline">request a new OTP</a>.</p>
        </div>
    </div>
@endsection

@section ('content')
    @php $otpLength = (int) get_system_setting('otp_length', 6); @endphp

    <form
        method="POST"
        action="{{ route('password.verify.store') }}"
        novalidate
        class="space-y-5"
        x-data="{
            showPwd: false,
            showConfirm: false,
            password: '',
            confirm: '',
            get strength() {
                if (!this.password.length) return 0;
                let s = 0;
                if (this.password.length >= 8) s++;
                if (/[A-Z]/.test(this.password)) s++;
                if (/[0-9]/.test(this.password)) s++;
                if (/[^A-Za-z0-9]/.test(this.password)) s++;
                return s;
            },
            get strengthLabel() {
                return ['', 'Weak', 'Fair', 'Good', 'Strong'][this.strength];
            },
            get strengthColor() {
                return ['', '#ef4444', '#f59e0b', '#3b82f6', '#22c55e'][this.strength];
            },
        }"
    >
        @csrf

        {{-- Email (read-only display) --}}
        <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
            <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                />
            </svg>
            <span class="font-400 flex-1 truncate text-sm text-gray-600">{{ $email }}</span>
            <span class="font-600 text-brand-600 bg-brand-50 border-brand-200 rounded-full border px-2 py-0.5 text-xs"
                >OTP sent</span
            >
        </div>

        {{-- OTP Input --}}
        <div class="space-y-1.5">
            <label for="otp" class="font-600 block text-xs tracking-wider text-gray-600 uppercase">
                One-Time Password (OTP)
            </label>
            <input
                id="otp"
                type="text"
                name="otp"
                value="{{ old('otp') }}"
                inputmode="numeric"
                pattern="\d*"
                maxlength="{{ $otpLength }}"
                placeholder="{{ str_repeat('•', $otpLength) }}"
                required
                autofocus
                autocomplete="one-time-code"
                class="w-full rounded-xl text-center text-2xl font-700 tracking-[0.5em] py-3.5 border outline-none transition-all
                {{ $errors->has('otp') ? 'border-red-400 bg-red-50 text-red-700' : 'border-gray-300 bg-white text-gray-900 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10' }}"
            />
            @error ('otp')
                <p class="mt-1 flex items-center gap-1.5 text-xs text-red-600">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        {{-- Divider --}}
        <div class="flex items-center gap-3">
            <div class="h-px flex-1 bg-gray-200"></div>
            <span class="font-500 text-xs text-gray-400">New Password</span>
            <div class="h-px flex-1 bg-gray-200"></div>
        </div>

        {{-- New Password --}}
        <div class="space-y-1.5">
            <label for="password" class="font-600 block text-xs tracking-wider text-gray-600 uppercase">
                New Password
            </label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                        />
                    </svg>
                </span>
                <input
                    id="password"
                    :type="showPwd ? 'text' : 'password'"
                    name="password"
                    x-model="password"
                    placeholder="Min. 8 characters"
                    required
                    autocomplete="new-password"
                    class="w-full rounded-xl text-sm pl-10 pr-10 py-3 border outline-none transition-all
                    {{ $errors->has('password') ? 'border-red-400 bg-red-50 text-red-700' : 'border-gray-300 bg-white text-gray-900 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10' }}"
                />
                <button
                    type="button"
                    @click="showPwd = !showPwd"
                    class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 transition-colors hover:text-gray-600"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path
                            x-show="!showPwd"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                        />
                        <path
                            x-show="showPwd"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                        />
                    </svg>
                </button>
            </div>

            {{-- Strength meter --}}
            <div x-show="password.length > 0" x-cloak class="space-y-1 pt-0.5">
                <div class="flex gap-1">
                    <template x-for="i in 4" :key="i">
                        <div
                            class="h-1 flex-1 rounded-full transition-all duration-300"
                            :style="'background:' + (i <= strength ? strengthColor : '#e5e7eb')"
                        ></div>
                    </template>
                </div>
                <p class="font-500 text-xs" :style="'color:' + strengthColor" x-text="strengthLabel"></p>
            </div>

            @error ('password')
                <p class="mt-1 flex items-center gap-1.5 text-xs text-red-600">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        {{-- Confirm Password --}}
        <div class="space-y-1.5">
            <label for="password_confirmation" class="font-600 block text-xs tracking-wider text-gray-600 uppercase">
                Confirm Password
            </label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                        />
                    </svg>
                </span>
                <input
                    id="password_confirmation"
                    :type="showConfirm ? 'text' : 'password'"
                    name="password_confirmation"
                    x-model="confirm"
                    placeholder="Repeat password"
                    required
                    autocomplete="new-password"
                    class="w-full rounded-xl border py-3 pr-10 pl-10 text-sm transition-all outline-none"
                    :class="confirm.length > 2
                        ? password === confirm
                            ? 'border-green-400 bg-green-50/50 focus:ring-2 focus:ring-green-500/10'
                            : 'border-red-400 bg-red-50'
                        : 'border-gray-300 bg-white focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10'"
                />
                <button
                    type="button"
                    @click="showConfirm = !showConfirm"
                    class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 transition-colors hover:text-gray-600"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path
                            x-show="!showConfirm"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                        />
                        <path
                            x-show="showConfirm"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                        />
                    </svg>
                </button>
            </div>

            {{-- Match hint --}}
            <p
                x-show="confirm.length > 2 && password !== confirm"
                x-cloak
                class="mt-1 flex items-center gap-1.5 text-xs text-red-600"
            >
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Passwords do not match
            </p>
            <p
                x-show="confirm.length > 2 && password === confirm"
                x-cloak
                class="mt-1 flex items-center gap-1.5 text-xs text-green-600"
            >
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Passwords match
            </p>
        </div>

        {{-- Submit --}}
        <button
            type="submit"
            class="bg-brand-600 hover:bg-brand-700 active:bg-brand-800 font-600 mt-1 w-full rounded-xl py-3 text-sm text-white shadow-sm transition-colors"
        >
            Reset Password
        </button>

        {{-- Back --}}
        <div class="pt-1 text-center">
            <a
                href="{{ route('password.request') }}"
                class="hover:text-brand-600 font-500 inline-flex items-center gap-1.5 text-sm text-gray-500 transition-colors"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Request a new OTP
            </a>
        </div>
    </form>
@endsection
