<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'My Account') | {{ $company->name ?? config('app.name') }}</title>

    <link rel="icon" type="image/png"
        href="{{ get_setting('favicon') ? asset('storage/' . get_setting('favicon')) : asset('assets/icons/favicon.png') }}">

    @php
        $companySlug = request()->route('slug') ?? auth()->user()->company?->slug;

        // Consistent Admin Variables
        $primary = get_setting('primary_color', '#FF6B35');
        $hover = get_setting('primary_hover_color', '#E55A2B');
    @endphp

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    {{-- Assets --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    {{-- 1. Load Tailwind FIRST --}}
    <link rel="stylesheet" href="{{ asset_v('assets/css/tailwind.min.css') }}" />


    <script src="{{ asset('assets/js/lucide.min.js') }}"></script>
    <script src="{{ asset('assets/js/sweetalert2.js') }}"></script>
    <script defer src="{{ asset('assets/js/alpinejs.min.js') }}"></script>

    {{-- Dynamic Theme Configuration matching Admin Layout --}}
    <style>
        :root {
            /* ─────────────────────────────
                Brand Colors
                ───────────────────────────── */
            --primary: {{ $primary }};
            --primaryDark: {{ $hover }};
            --brand-500: {{ $primary }};
            --brand-600: {{ $hover }};
            --brand-700: {{ $hover }};

            /* RGB Version (Required for glow/shadows) */
            --brand-rgb: {{ implode(' ', sscanf(ltrim($primary, '#'), '%02x%02x%02x')) }};

            /* ─────────────────────────────
                Light Brand Variations
                ───────────────────────────── */
            --color-brand-50: color-mix(in srgb, var(--brand-500) 10%, white);
            --color-brand-100: color-mix(in srgb, var(--brand-500) 20%, white);
            --color-brand-200: color-mix(in srgb, var(--brand-500) 35%, white);

            /* ─────────────────────────────
                Layout Variables
                ───────────────────────────── */
            --bg-page: #f4f6f9;
            --ease: cubic-bezier(0.4, 0, 0.2, 1);
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* Active Sidebar Link Logic */
        .nav-link.active {
            color: var(--brand-500);
            background-color: color-mix(in srgb, var(--brand-500) 10%, white);
            font-weight: 700;
            border-left: 3px solid var(--brand-500);
        }

        .swal2-confirm {
            background-color: var(--brand-500) !important;
        }

        body {
            padding-bottom: 80px;
        }

        @media (min-width: 1024px) {
            body {
                padding-bottom: 0;
            }
        }
    </style>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        // Maps the admin CSS variables to our portal's utility classes
                        primary: 'var(--brand-500)',
                        primaryDark: 'var(--brand-600)',
                        neutralBg: 'var(--bg-page)',
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif']
                    },
                }
            }
        }
    </script>
</head>

<body class="bg-neutralBg text-slate-900 font-sans min-h-screen flex flex-col">

    {{-- ================= HEADER ================= --}}
    <header class="sticky top-0 z-50 bg-white border-b border-slate-100 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex justify-between items-center">

            <a href="{{ tenant_url('') }}" class="inline-flex items-center transition-opacity hover:opacity-80">
                @if (get_setting('logo', null, auth()->user()->company_id))
                    <img src="{{ asset('storage/' . get_setting('logo', null, auth()->user()->company_id)) }}"
                        alt="{{ $company->name ?? 'Store' }}" class="h-10 sm:h-12 object-contain">
                @else
                    <div
                        class="h-10 sm:h-12 flex items-center justify-center font-black text-xl tracking-tight text-slate-800">
                        {{ $company->name ?? 'Qlinkon' }}
                    </div>
                @endif
            </a>

            <div class="flex items-center gap-4">
                <a href="{{ tenant_url('') }}"
                    class="hidden sm:flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-primary transition-colors pr-4 border-r border-slate-200">
                    <i data-lucide="store" class="w-4 h-4"></i> Back to Store
                </a>

                {{-- Dynamic User Avatar --}}
                <div
                    class="w-9 h-9 rounded-full overflow-hidden border border-slate-200 shadow-sm shrink-0 bg-slate-50">
                    <img id="header-avatar"
                        src="{{ Auth::user()->image ? asset('storage/' . Auth::user()->image) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name ?? 'User') . '&background=f1f5f9&color=64748b&size=128' }}"
                        alt="Profile" class="w-full h-full object-cover">
                </div>
            </div>
        </div>
    </header>

    {{-- ================= BREADCRUMB ================= --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 w-full hidden sm:block">
        <nav class="flex text-sm text-slate-500 font-medium">
            <a href="{{ tenant_url('portal/dashboard') }}" class="hover:text-primary transition-colors">Portal</a>
            <span class="mx-2.5 text-slate-300">/</span>
            <span class="text-slate-900">@yield('title')</span>
        </nav>
    </div>

    {{-- ================= MAIN CONTAINER ================= --}}
    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12 w-full pt-4 sm:pt-0">
        <div class="flex flex-col lg:flex-row gap-8 lg:gap-10">

            {{-- ================= SIDEBAR MENU ================= --}}
            <aside
                class="w-full lg:w-64 flex-shrink-0 bg-white lg:bg-transparent rounded-2xl lg:rounded-none shadow-sm lg:shadow-none p-2 lg:p-0">
                <nav class="flex flex-row lg:flex-col gap-1 overflow-x-auto lg:overflow-visible no-scrollbar">

                    <a href="{{ tenant_url('portal/dashboard') }}"
                        class="nav-link {{ request()->routeIs('*.portal.dashboard') ? 'active' : 'border-l-[3px] border-transparent' }} flex items-center gap-3 px-4 py-3 text-sm text-slate-600 hover:bg-slate-50 hover:text-primary transition-all rounded-r-lg whitespace-nowrap">
                        <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
                    </a>

                    <a href="{{ tenant_url('portal/orders') }}"
                        class="nav-link {{ request()->routeIs('*.portal.orders*') ? 'active' : 'border-l-[3px] border-transparent' }} flex items-center gap-3 px-4 py-3 text-sm text-slate-600 hover:bg-slate-50 hover:text-primary transition-all rounded-r-lg whitespace-nowrap">
                        <i data-lucide="shopping-bag" class="w-5 h-5"></i> My Orders
                    </a>

                    <a href="{{ tenant_url('portal/addresses') }}"
                        class="nav-link {{ request()->routeIs('*.portal.addresses*') ? 'active' : 'border-l-[3px] border-transparent' }} flex items-center gap-3 px-4 py-3 text-sm text-slate-600 hover:bg-slate-50 hover:text-primary transition-all rounded-r-lg whitespace-nowrap">
                        <i data-lucide="map-pin" class="w-5 h-5"></i> Addresses
                    </a>

                    <a href="{{ tenant_url('portal/profile') }}"
                        class="nav-link {{ request()->routeIs('*.portal.profile*') ? 'active' : 'border-l-[3px] border-transparent' }} flex items-center gap-3 px-4 py-3 text-sm text-slate-600 hover:bg-slate-50 hover:text-primary transition-all rounded-r-lg whitespace-nowrap">
                        <i data-lucide="user" class="w-5 h-5"></i> Profile
                    </a>

                    @if ($companySlug)
                        <a href="{{ tenant_url('') }}" target="_blank"
                            class="nav-link flex items-center gap-3 px-4 py-3 text-sm text-slate-600 hover:bg-slate-50 hover:text-primary transition-all rounded-r-lg whitespace-nowrap">
                            <i data-lucide="external-link" class="w-5 h-5"></i> Visit Site
                        </a>
                    @endif
                    <div class="hidden lg:block my-4 border-t border-slate-200 mx-4"></div>

                    {{-- Logout Form --}}
                    <form action="{{ tenant_url('portal/logout') }}" method="POST" id="logout-form"
                        class="inline-block lg:block">
                        @csrf
                        <button type="button" onclick="confirmLogout()"
                            class="w-full nav-link border-l-[3px] border-transparent flex items-center gap-3 px-4 py-3 text-sm font-semibold text-slate-600 hover:bg-red-50 hover:text-red-600 transition-all rounded-r-lg whitespace-nowrap">
                            <i data-lucide="log-out" class="w-5 h-5"></i> Sign Out
                        </button>
                    </form>

                </nav>
            </aside>

            {{-- ================= DYNAMIC CONTENT ================= --}}
            <div class="flex-1 min-w-0">
                @yield('content')
            </div>
        </div>
    </main>

    {{-- ================= MOBILE BOTTOM NAV ================= --}}
    <nav
        class="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-md border-t border-slate-200 py-2.5 px-6 flex justify-between items-center z-[100] lg:hidden pb-safe">
        <a href="{{ tenant_url('') }}"
            class="flex flex-col items-center gap-1 text-slate-400 hover:text-primary transition-colors w-16">
            <i data-lucide="store" class="w-5 h-5"></i>
            <span class="text-[10px] font-bold uppercase tracking-wider">Store</span>
        </a>
        <a href="{{ tenant_url('portal/dashboard') }}"
            class="flex flex-col items-center gap-1 {{ request()->routeIs('*.portal.dashboard') ? 'text-primary' : 'text-slate-400 hover:text-primary' }} transition-colors w-16">
            <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
            <span class="text-[10px] font-bold uppercase tracking-wider">Home</span>
        </a>
        <a href="{{ tenant_url('portal/orders') }}"
            class="flex flex-col items-center gap-1 {{ request()->routeIs('*.portal.orders*') ? 'text-primary' : 'text-slate-400 hover:text-primary' }} transition-colors w-16">
            <i data-lucide="shopping-bag" class="w-5 h-5"></i>
            <span class="text-[10px] font-bold uppercase tracking-wider">Orders</span>
        </a>
        <button type="button" onclick="confirmLogout()"
            class="flex flex-col items-center gap-1 text-slate-400 hover:text-red-500 transition-colors w-16">
            <i data-lucide="log-out" class="w-5 h-5"></i>
            <span class="text-[10px] font-bold uppercase tracking-wider">Exit</span>
        </button>
    </nav>

    {{-- ================= SCRIPTS ================= --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
        });

        function confirmLogout() {
            Swal.fire({
                title: 'Sign Out?',
                text: "Are you sure you want to log out of your account?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: 'var(--brand-500)',
                cancelButtonColor: '#cbd5e1',
                confirmButtonText: 'Yes, Sign Out',
                cancelButtonText: 'Cancel',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'rounded-xl font-bold px-6 py-2.5',
                    cancelButton: 'rounded-xl font-bold px-6 py-2.5'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('logout-form').submit();
                }
            })
        }
    </script>
    @stack('scripts')
</body>

</html>
