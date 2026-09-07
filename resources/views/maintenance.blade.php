<!doctype html>
<html lang="en">
@php    
    // ==============================
    // 🔧 SYSTEM SETTINGS FETCH
    // Replace '\App\Models\Setting' with your actual Settings Model or Helper
    // ==============================
    $supportEmail = \App\Models\Platform\SystemSetting::where('key', 'support_email')->value('value') ?? config('mail.from.address', 'support@yourdomain.com');
    $supportPhone = \App\Models\Platform\SystemSetting::where('key', 'support_phone')->value('value') ?? '+1 (800) 000-0000';

    $default = config('app.name','Qlinkon');
    $config = [
        'store_name' => $default,
        'title' => 'We\'ll be right back',
        'message' => 'We are currently performing routine maintenance to improve your experience. We expect to be back online shortly. If you need immediate assistance, please reach out to our support team.',
        
        'primary_color' => '#6366f1', // Indigo-500 for a trustworthy, calm look

        'show_logo' => false,
        'logo_url' => '',

        'home_url' => '/',
        'show_home_button' => true,

        'badge_text' => 'System Maintenance'
    ];
@endphp

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $config['store_name'] }} — {{ $config['badge_text'] }}</title>

    <script src="{{ asset('assets/js/tailwind.min.js') }}"></script>
    <!-- Fallback CDN in case local assets fail during maintenance -->
    <script src="https://cdn.tailwindcss.com"></script>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap"
        rel="stylesheet"
    />

    <style>
        body {
            font-family: "Inter", sans-serif;
            background:
                radial-gradient(circle at 20% 20%, rgba(99, 102, 241, 0.2), transparent 40%),
                /* Indigo */ radial-gradient(circle at 80% 0%, rgba(16, 185, 129, 0.15), transparent 40%),
                /* Emerald */ #0f172a; /* Slate 900 */
            color: white;
            overflow: hidden;
        }

        /* floating blobs */
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(90px);
            opacity: 0.45;
            animation: float 14s infinite ease-in-out;
        }

        .blob2 {
            animation-delay: 3s;
        }
        .blob3 {
            animation-delay: 6s;
        }

        @keyframes float {
            0%,
            100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-50px);
            }
        }

        /* pulse */
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 0.6;
            }
            50% {
                transform: scale(1.4);
                opacity: 0;
            }
            100% {
                transform: scale(1.4);
                opacity: 0;
            }
        }
    </style>
</head>

<body class="relative flex min-h-screen items-center justify-center">
    <!-- animated background -->
    <div class="blob top-10 left-10 h-72 w-72 bg-indigo-500"></div>
    <div class="blob blob2 top-40 right-10 h-96 w-96 bg-emerald-500"></div>
    <div class="blob blob3 bottom-0 left-1/3 h-[28rem] w-[28rem] bg-blue-500"></div>

    <!-- content -->
    <div class="relative z-10 max-w-3xl px-6 py-12 text-center">
        <!-- logo -->
        @if ($config['show_logo'] && $config['logo_url'])
            <div class="mb-8 flex justify-center">
                <img src="{{ $config['logo_url'] }}" class="h-16 drop-shadow-lg" alt="{{ $config['store_name'] }}" />
            </div>
        @endif

        <!-- badge -->
        <div
            class="mb-6 inline-flex items-center gap-2 rounded-full border border-indigo-500/20 bg-indigo-500/10 px-4 py-1.5 backdrop-blur-sm"
        >
            <span class="relative flex h-3 w-3">
                <span class="pulse absolute inline-flex h-full w-full rounded-full bg-indigo-400"></span>
                <span class="relative inline-flex h-3 w-3 rounded-full bg-indigo-500"></span>
            </span>
            <span class="text-xs font-semibold tracking-widest text-indigo-300 uppercase">
                {{ $config['badge_text'] }}
            </span>
        </div>

        <!-- title -->
        <h1
            class="mb-6 bg-gradient-to-r from-white to-gray-400 bg-clip-text text-4xl leading-tight font-black text-transparent md:text-5xl"
        >
            {{ $config['title'] }}
        </h1>

        <!-- message -->
        <p class="mx-auto mb-10 max-w-2xl text-lg leading-relaxed text-gray-300">{{ $config['message'] }}</p>

        <!-- Friendly Contact Options for Tenants -->
        <div class="mx-auto mb-10 grid max-w-2xl gap-4 sm:grid-cols-2">
            <!-- Email Card -->
            <a
                href="mailto:{{ $supportEmail }}"
                class="group block rounded-2xl border border-white/10 bg-white/5 p-6 text-left backdrop-blur-md transition duration-300 hover:border-indigo-400/50 hover:bg-white/10"
            >
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-500/20 text-indigo-400 transition group-hover:bg-indigo-500/30"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <h3 class="mb-1 text-lg font-semibold text-white">Email Us</h3>
                        <p class="text-sm font-medium break-all text-indigo-300">{{ $supportEmail }}</p>
                    </div>
                </div>
            </a>

            <!-- Phone Card -->
            <a
                href="tel:{{ $supportPhone }}"
                class="group block rounded-2xl border border-white/10 bg-white/5 p-6 text-left backdrop-blur-md transition duration-300 hover:border-emerald-400/50 hover:bg-white/10"
            >
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400 transition group-hover:bg-emerald-500/30"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                    </div>
                    <div>
                        <h3 class="mb-1 text-lg font-semibold text-white">Call Support</h3>
                        <p class="text-sm font-medium text-emerald-300">{{ $supportPhone }}</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- buttons -->
        <div class="flex flex-col justify-center gap-4 sm:flex-row">
            <button
                onclick="location.reload()"
                class="rounded-xl px-8 py-3.5 font-semibold text-white shadow-lg shadow-indigo-500/25 transition duration-300 hover:scale-105"
                style="background: {{ $config['primary_color'] }}"
            >
                Try Reloading Page
            </button>

            {{-- @if($config['show_home_button'])
        <a href="{{ $config['home_url'] }}"
           class="px-8 py-3.5 rounded-xl border border-gray-600 text-gray-200 hover:bg-white/10 hover:text-white transition duration-300">
            Return to Homepage
        </a>
        @endif --}}
        </div>
    </div>
</body>
</html>
