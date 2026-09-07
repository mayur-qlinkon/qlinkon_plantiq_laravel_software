<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield ('title', 'Super Admin - Plantiq')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <link rel="icon" type="image/png"
        href="{{ get_system_setting('app_favicon') ? asset('storage/' . get_system_setting('app_favicon')) : asset('assets/icons/favicon.png') }}" />
    {{-- 1. Load Tailwind FIRST --}}
    {{-- <script src="{{ asset('assets/js/tailwind.min.js') }}"></script> --}}
    <link rel="stylesheet" href="{{ asset_v('assets/css/tailwind.min.css') }}" />


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script src="{{ asset('assets/js/sweetalert2.js') }}"></script>
    <script src="{{ asset('assets/js/swal.js') }}"></script>
    <script defer src="{{ asset('assets/js/alpinejs.min.js') }}"></script>

    <style>
        :root {
            --brand-50: #f0fdfa;
            --brand-100: #ccfbf1;
            --brand-500: #0f766e;
            --brand-600: #115e59;
            --brand-700: #134e4a;
        }

        [x-cloak] {
            display: none !important;
        }

        body {
            font-family: Poppins, sans-serif;
        }
    </style>

    @yield ('styles')
</head>

<body class="bg-gray-100 text-gray-800">
    <div x-data="{ sidebar: false }" class="flex h-screen overflow-hidden">
        {{-- Mobile overlay --}}
        <div x-show="sidebar" x-cloak @click="sidebar = false" class="fixed inset-0 z-40 bg-black/40 lg:hidden"></div>
        {{-- Sidebar --}}

        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-64 transform flex-col border-r border-gray-200 bg-white transition-transform lg:static lg:translate-x-0"
            :class="sidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
            <div class="flex h-16 shrink-0 items-center  px-6">
                <div class="flex items-center gap-2">
                    <div class="bg-brand-600 flex h-8 w-8 items-center justify-center rounded-lg text-white">
                        <i class="fa-solid fa-shield-halved text-sm"></i>
                    </div>

                    <div>
                        <p class="font-bold text-gray-800">Qlinkon</p>
                        <p class="text-xs font-medium text-gray-400">SUPER ADMIN</p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto p-4 text-sm font-medium">
                <div class="pb-1">
                    <p class="px-3 text-[10px] font-bold tracking-wider text-gray-400 uppercase">Operations</p>
                </div>
                <a href="{{ route('platform.dashboard') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('platform.dashboard') ? 'bg-gray-100 text-brand-600' : 'hover:bg-gray-100' }}">
                    <i class="fa-solid fa-gauge-high fa-fw text-sm"></i>
                    Dashboard
                </a>

                <a href="{{ route('platform.tenants.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('platform.tenants.*') ? 'bg-gray-100 text-brand-600' : 'hover:bg-gray-100' }}">
                    <i class="fa-solid fa-users-rectangle fa-fw text-sm"></i>
                    Companies
                </a>

                <a href="{{ route('platform.promotions.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg
                        {{ request()->routeIs('platform.promotions.*') ? 'bg-gray-100 text-brand-600' : 'hover:bg-gray-100' }}">
                    <i class="fa-solid fa-ticket fa-fw text-sm"></i>
                    Promotions
                </a>

                {{-- <a href="{{ route('platform.companies.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('platform.companies.*') ? 'bg-gray-100 text-brand-600' : 'hover:bg-gray-100' }}">
                    <i class="fa-solid fa-building fa-fw text-sm"></i>
                    Companies
                </a>     --}}

                {{-- <a href="{{ route('platform.subscriptions.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('platform.subscriptions.*') ? 'bg-gray-100 text-brand-600' : 'hover:bg-gray-100' }}">
                    <i class="fa-regular fa-credit-card fa-fw text-sm"></i>
                    Subscriptions
                </a>

                <a href="{{ route('platform.plans.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('platform.plans.*') ? 'bg-gray-100 text-brand-600' : 'hover:bg-gray-100' }}">
                    <i class="fa-solid fa-layer-group fa-fw text-sm"></i>
                    Plans
                </a> --}}

                <div class="pb-1">
                    <p class="px-3 text-[10px] font-bold tracking-wider text-gray-400 uppercase">History</p>
                </div>

                <a href="{{ route('platform.payments.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg
                         {{ request()->routeIs('platform.payments.*') ? 'bg-gray-100 text-brand-600' : 'hover:bg-gray-100' }}">
                    <i class="fa-solid fa-money-bill-wave fa-fw text-sm"></i>
                    Payments
                </a>

                <a href="{{ route('platform.promotion-usages.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg
                         {{ request()->routeIs('platform.promotion-usages.*') ? 'bg-gray-100 text-brand-600' : 'hover:bg-gray-100' }}">
                    <i class="fa-solid fa-tags fa-fw text-sm"></i>
                    Coupon Usage
                </a>

                {{-- ── Help Center Links ── --}}
                <div class="pt-4 pb-2">
                    <p class="px-3 text-[10px] font-bold tracking-wider text-gray-400 uppercase">Help Center</p>
                </div>

                <a href="{{ route('platform.inquiries.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('platform.inquiries.*') ? 'bg-gray-100 text-brand-600' : 'hover:bg-gray-100' }}">
                    <i class="fa-solid fa-message fa-fw text-sm"></i>
                    Inquiries
                </a>

                <a href="{{ route('platform.help.feedback') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('platform.help.feedback') ? 'bg-gray-100 text-brand-600' : 'hover:bg-gray-100' }}">
                    <i class="fa-solid fa-comment-dots fa-fw text-sm"></i>
                    Feedback
                </a>

                <a href="{{ route('platform.help.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('platform.help.index') ? 'bg-gray-100 text-brand-600' : 'hover:bg-gray-100' }}">
                    <i class="fa-solid fa-file-lines fa-fw text-sm"></i>
                    Articles
                </a>

                <a href="{{ route('platform.help.categories') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('platform.help.categories') ? 'bg-gray-100 text-brand-600' : 'hover:bg-gray-100' }}">
                    <i class="fa-solid fa-sitemap fa-fw text-sm"></i>
                    Categories
                </a>

                <div class="pb-1">
                    <p class="px-3 text-[10px] font-bold tracking-wider text-gray-400 uppercase">Setup</p>
                </div>

                <a href="{{ route('platform.plant-library.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg
                        {{ request()->routeIs('platform.plant-library.*') ? 'bg-gray-100 text-brand-600' : 'hover:bg-gray-100' }}">
                    <i class="fa-solid fa-book-open fa-fw text-sm"></i>
                    Plant Library
                </a>
                <a href="{{ route('platform.plant-library-import.index') }}"
                    class="flex items-center gap-3 pl-9 pr-3 py-2 rounded-lg text-sm
                        {{ request()->routeIs('platform.plant-library-import.*') ? 'bg-gray-100 text-brand-600' : 'hover:bg-gray-100 text-gray-600' }}">
                    <i class="fa-solid fa-file-import fa-fw text-xs"></i>
                    Import to Tenant
                </a>
                <a href="{{ route('platform.plan-calculator.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('platform.plan-calculator.*') ? 'bg-gray-100 text-brand-600' : 'hover:bg-gray-100' }}">
                    <i class="fa-solid fa-calculator fa-fw text-sm"></i>
                    Plan Calculator
                </a>

                <a href="{{ route('platform.addons.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('platform.addons.*') ? 'bg-gray-100 text-brand-600' : 'hover:bg-gray-100' }}">
                    <i class="fa-solid fa-puzzle-piece w-4 text-center"></i>
                    <span>Addons</span>
                </a>
                <a href="{{ route('platform.plant-profiles.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg
                        {{ request()->routeIs('platform.plant-profiles.*') ? 'bg-gray-100 text-brand-600' : 'hover:bg-gray-100' }}">
                    <i class="fa-solid fa-seedling fa-fw text-sm"></i>
                    Plant Profiles
                </a>

                <a href="{{ route('platform.profile-kits.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg
                        {{ request()->routeIs('platform.profile-kits.*') ? 'bg-gray-100 text-brand-600' : 'hover:bg-gray-100' }}">
                    <i class="fa-solid fa-tags fa-fw text-sm"></i>
                    Profile Kits
                </a>

                <a href="{{ route('platform.core-features.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('platform.core-features.*') ? 'bg-gray-100 text-brand-600' : 'hover:bg-gray-100' }}">
                    <i class="fa-solid fa-star w-4 text-center"></i>
                    <span>Core Features</span>
                </a>

                <a href="{{ url('/platform/seeders') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('platform.seeders.index') ? 'bg-gray-100 text-brand-600' : 'hover:bg-gray-100' }}">
                    <i class="fa-solid fa-database fa-fw text-sm"></i>
                    Seeders
                </a>


                <div class="pt-4 pb-2">
                    <p class="px-3 text-[10px] font-bold tracking-wider text-gray-400 uppercase">System</p>
                </div>

                <a href="{{ route('platform.modules.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('platform.modules.*') ? 'bg-gray-100 text-brand-600' : 'hover:bg-gray-100' }}">
                    <i class="fa-solid fa-cubes fa-fw text-sm"></i>
                    Modules
                </a>

                <a href="{{ route('platform.system.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('platform.system.*') ? 'bg-gray-100 text-brand-600' : 'hover:bg-gray-100' }}">
                    <i class="fa-solid fa-gear fa-fw text-sm"></i>
                    Settings
                </a>

                <div class="pt-4 pb-2">
                    <p class="px-3 text-[10px] font-bold tracking-wider text-gray-400 uppercase">Public</p>
                </div>
                <a href="{{ url('/') }}" target="_blank"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-100">
                    <i class="fa-solid fa-arrow-up-right-from-square fa-fw text-sm"></i>
                    Visit Page
                </a>
            </nav>
        </aside>
        {{-- Main --}}
        <div class="flex flex-1 flex-col overflow-hidden">
            {{-- Topbar --}}
            <header class="flex h-16 items-center justify-between  bg-white px-6">
                <div class="flex items-center gap-4">
                    <button @click="sidebar = !sidebar" class="text-gray-600 lg:hidden">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>

                    <h1 class="font-semibold text-gray-700">
                        @yield ('header', 'Dashboard')
                    </h1>
                </div>

                {{-- Profile Dropdown --}}
                <div x-data="{ open: false }" class="relative">
                    {{-- Trigger Button --}}
                    <button @click="open = !open" @keydown.escape.window="open = false"
                        class="flex items-center gap-3 rounded-full py-1 pl-1 pr-3 transition-colors hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-1">

                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-600 text-white shadow-sm">
                            <i class="fa-solid fa-user text-sm"></i>
                        </div>

                        {{-- Show name on medium screens and up --}}
                        <div class="hidden flex-col items-start md:flex">
                            <span
                                class="text-sm font-semibold text-gray-700">{{ Auth::user()->name ?? 'Administrator' }}</span>
                        </div>

                        <i class="fa-solid fa-chevron-down text-xs text-gray-500 transition-transform duration-200"
                            :class="{ 'rotate-180': open }"></i>
                    </button>

                    {{-- Dropdown Panel --}}
                    <div x-show="open" @click.outside="open = false" x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                        class="absolute right-0 top-full z-50 mt-2 w-56 rounded-xl border border-gray-100 bg-white shadow-lg focus:outline-none">

                        {{-- User Info Header --}}
                        <div class="border-b border-gray-100 px-4 py-3">
                            <p class="text-sm font-semibold text-gray-900">{{ Auth::user()->name ?? 'Administrator' }}
                            </p>
                            <p class="truncate text-xs text-gray-500">{{ Auth::user()->email ?? 'admin@example.com' }}
                            </p>
                        </div>

                        {{-- Menu Links --}}
                        <div class="p-1">
                            <a href="{{ route('platform.system.index') }}"
                                class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-50 hover:text-brand-600">
                                <i
                                    class="fa-solid fa-gear w-4 text-center text-gray-400 group-hover:text-brand-600"></i>
                                Settings
                            </a>
                        </div>

                        {{-- Logout Form & Action --}}
                        <div class="border-t border-gray-100 p-1">
                            <form method="POST" action="{{ route('logout') }}" id="logout-form">
                                @csrf
                                <button type="button" onclick="confirmLogout()"
                                    class="group flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm text-red-600 transition-colors hover:bg-red-50">
                                    <i class="fa-solid fa-arrow-right-from-bracket w-4 text-center"></i>
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>


            </header>

            {{-- Content --}}
            <main class="flex-1 overflow-y-auto p-6">
                @yield ('content')
            </main>
        </div>
    </div>


    {{-- SweetAlert2 Confirmation Script --}}
    <script>
        function confirmLogout() {
            Swal.fire({
                title: 'Ready to leave?',
                text: 'You will be logged out of your current session.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444', // Tailwind red-500
                cancelButtonColor: '#9ca3af', // Tailwind gray-400
                confirmButtonText: 'Yes, log out',
                cancelButtonText: 'Cancel',
                customClass: {
                    popup: 'rounded-xl',
                    confirmButton: 'rounded-lg px-4 py-2',
                    cancelButton: 'rounded-lg px-4 py-2'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('logout-form').submit();
                }
            });
        }
    </script>
    @yield ('scripts')
    @stack ('scripts')
</body>

</html>
