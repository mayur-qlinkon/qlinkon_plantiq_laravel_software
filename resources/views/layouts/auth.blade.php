<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield ('title', config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet" />
    <link rel="icon" type="image/png"
        href="{{ get_system_setting('app_favicon') ? asset('storage/' . get_system_setting('app_favicon')) : asset('assets/icons/favicon.png') }}" />
    {{-- <script src="{{ asset('assets/js/tailwind.min.js') }}"></script> --}}
    <link rel="stylesheet" href="{{ asset_v('assets/css/tailwind.min.css') }}" />

    <script defer src="{{ asset('assets/js/alpinejs.min.js') }}"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    @stack ('styles')
    @stack ('pwa')
</head>

<body class="min-h-screen bg-white text-gray-800 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center bg-gray-50/50 p-6 sm:p-10">
        {{-- Static Qlinkon Logo --}}
        <div class="mb-8">
            {{-- Make sure to place your logo in public/assets/images/logo.png or adjust the path --}}
            @php
                $defaultLogo = asset('assets/images/logo.png');
                $loginLogo = get_system_setting('app_logo');

                $logoSrc = $defaultLogo;

                if (!empty($loginLogo)) {
                    $logoSrc = \Illuminate\Support\Str::startsWith($loginLogo, ['http://', 'https://'])
                        ? $loginLogo
                        : asset('storage/' . ltrim($loginLogo, '/'));
                }
            @endphp

            <img src="{{ $logoSrc }}" alt="Qlinkon" class="h-14 w-auto object-contain"
                onerror="this.onerror=null;this.src='{{ $defaultLogo }}';" />
        </div>

        {{-- Centered Form Card --}}
        <div class="w-full max-w-[420px] p-8 sm:p-10">
            {{-- Page heading --}}
            @hasSection('heading')
                <div class="mb-7 text-center">
                    <h2 class="font-bold text-2xl tracking-tight text-gray-900">
                        @yield('heading')
                    </h2>

                    @hasSection('subheading')
                        <p class="mt-1.5 text-sm leading-relaxed text-gray-500">
                            @yield('subheading')
                        </p>
                    @endif
                </div>
            @endif

            {{-- Flash messages --}}
            @if (session('status'))
                <div
                    class="bg-brand-50 border-brand-200 text-brand-800 mb-5 flex items-start gap-3 rounded-xl border px-4 py-3 text-sm">
                    <svg class="text-brand-600 mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    {{ session('status') }}
                </div>
            @endif
            @if (session('success'))
                <div
                    class="mb-5 flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-green-600" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div
                    class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            {{-- Form content --}}
            @yield ('content')
        </div>
    </div>

    @yield ('scripts')
    @stack ('scripts')
</body>

</html>
