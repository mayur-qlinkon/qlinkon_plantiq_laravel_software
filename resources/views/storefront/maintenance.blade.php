<!doctype html>
<html lang="en">

@php
    use App\Models\Company;
    $slug = request()->route('slug') ?? request()->segment(1) ?? null;
    $company = $company ?? ($slug ? Company::where('slug', $slug)->first() : null);
    $companyId = $company?->id;
    $tenantSetting = function (string $key, mixed $default = null) use ($companyId) {
            try {
                if (function_exists('get_setting')) {
                    return get_setting($key, $default, $companyId);
                }
            } catch (\Throwable $e) {
                // Silent fallback
            }

            return $default;
    };
    // ==============================
    // 🔧 EASY CONFIG (EDIT HERE ONLY)
    // ==============================
    $config = [
        'store_name' => $company?->name ?? "storefront",
        'title' => 'Store is currently unavailable',
        'message' => 'This store is temporarily closed. Please check back later.',
        'show_eta' => false,
        'eta' => 'Back soon',

        'primary_color' => '#10b981',

        'show_logo' => false,
        'logo_url' => '',

        'home_url' => '/',
        'show_home_button' => true,

        'badge_text' => 'Store Closed',

        // New Contact Information Configurations
        'phone' => $company?->phone ?? $tenantSetting('company_phone') ?? '',
        'email' => $company?->email ?? $tenantSetting('company_email') ?? '',
        'address' => $company?->address ?? $tenantSetting('company_address') ?? '',
    ];
@endphp

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $config['store_name'] }} — Closed</title>

<script src="{{ asset('assets/js/tailwind.min.js') }}"></script>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">

<style>
body {
    font-family: 'Inter', sans-serif;
    background:
        radial-gradient(circle at 20% 20%, rgba(16,185,129,0.25), transparent 40%),
        radial-gradient(circle at 80% 0%, rgba(99,102,241,0.25), transparent 40%),
        #0f172a;
    color: white;
    overflow: hidden;
}

/* floating blobs */
.blob {
    position:absolute;
    border-radius:50%;
    filter: blur(90px);
    opacity:.45;
    animation: float 14s infinite ease-in-out;
}

.blob2 { animation-delay: 3s; }
.blob3 { animation-delay: 6s; }

@keyframes float {
    0%,100% { transform: translateY(0); }
    50% { transform: translateY(-50px); }
}

/* pulse */
.pulse {
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0% { transform: scale(1); opacity:.6;}
    50% { transform: scale(1.4); opacity:0;}
    100% { transform: scale(1.4); opacity:0;}
}
</style>
</head>

<body class="flex items-center justify-center min-h-screen">

<div class="blob w-72 h-72 bg-emerald-400 top-10 left-10"></div>
<div class="blob blob2 w-96 h-96 bg-indigo-500 top-40 right-10"></div>
<div class="blob blob3 w-[28rem] h-[28rem] bg-cyan-400 bottom-0 left-1/3"></div>

<div class="relative z-10 text-center max-w-xl px-6">

    @if($config['show_logo'] && $config['logo_url'])
        <div class="flex justify-center mb-6">
            <img src="{{ $config['logo_url'] }}" class="h-14">
        </div>
    @endif

    <div class="inline-flex items-center gap-2 mb-6">
        <span class="relative flex h-3 w-3">
            <span class="pulse absolute inline-flex h-full w-full rounded-full bg-emerald-400"></span>
            <span class="relative inline-flex h-3 w-3 rounded-full bg-emerald-500"></span>
        </span>
        <span class="text-xs tracking-widest uppercase text-gray-300">
            {{ $config['badge_text'] }}
        </span>
    </div>

    <h1 class="text-4xl md:text-5xl font-black leading-tight mb-4">
        {{ $config['title'] }}
    </h1>

    <p class="text-gray-300 text-lg leading-relaxed mb-6">
        {{ $config['message'] }}
    </p>

    @if($config['show_eta'])
        <p class="text-sm text-gray-400 mb-8">
            Expected: <span class="text-white font-semibold">{{ $config['eta'] }}</span>
        </p>
    @endif

    <div class="flex justify-center gap-4">
        <button onclick="location.reload()"
            class="px-6 py-3 rounded-xl font-semibold text-white transition hover:scale-105"
            style="background: {{ $config['primary_color'] }}">
            Try Again
        </button>

        @if($config['show_home_button'])
        <a href="{{ $config['home_url'] }}"
           class="px-6 py-3 rounded-xl border border-gray-600 text-gray-200 hover:bg-white/10 transition">
            Home
        </a>
        @endif
    </div>

    @if($config['phone'] || $config['email'] || $config['address'])
        <div class="mt-10 pt-6 border-t border-white/10 text-sm text-gray-400 flex flex-col items-center gap-3">
            <div class="flex flex-wrap justify-center items-center gap-x-6 gap-y-2">
                @if($config['phone'])
                    <a href="tel:{{ $config['phone'] }}" class="flex items-center gap-2 hover:text-white transition group">
                        <svg class="w-4 h-4 transition text-emerald-400 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <span>{{ $config['phone'] }}</span>
                    </a>
                @endif

                @if($config['email'])
                    <a href="mailto:{{ $config['email'] }}" class="flex items-center gap-2 hover:text-white transition group">
                        <svg class="w-4 h-4 transition text-emerald-400 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span>{{ $config['email'] }}</span>
                    </a>
                @endif
            </div>

            @if($config['address'])
                <div class="flex items-center justify-center gap-2 text-center max-w-md text-xs sm:text-sm text-gray-400/90 mt-1">
                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>{{ $config['address'] }}</span>
                </div>
            @endif
        </div>
    @endif

</div>

</body>
</html>