<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    {{-- Dynamic Title --}}
    <title>@yield ('title', get_system_setting('app_name', 'Qlinkon'))</title>

    {{-- Dynamic Favicon --}}
    @php $favicon = get_system_setting('app_favicon'); @endphp
    @if ($favicon)
        <link rel="icon" type="image/png" href="{{ asset('storage/' . $favicon) }}" />
    @else
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" />
    @endif

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    {{-- FontAwesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    {{-- Tailwind CSS (CDN) --}}
    <link rel="stylesheet" href="{{ asset_v('assets/css/tailwind.min.css') }}" />

    {{-- Custom Styles Stack --}}
    @stack ('styles')

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="flex min-h-screen flex-col bg-gray-50 font-sans text-gray-900 antialiased">
    {{-- ─── HEADER / NAVBAR ─── --}}
    <header x-data="{ mobileMenuOpen: false }" class="sticky top-0 z-50 border-b border-gray-200 bg-white/80 backdrop-blur-md">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-20 items-center justify-between">
                {{-- Logo Section --}}
                <div class="flex shrink-0 items-center">
                    <a href="{{ url('/') }}" class="group flex items-center gap-3">
                        @php $logo = get_system_setting('app_logo'); @endphp
                        @if ($logo)
                            <img src="{{ asset('storage/' . $logo) }}"
                                alt="{{ get_system_setting('app_name', 'Logo') }}"
                                class="h-10 w-auto object-contain transition group-hover:opacity-90" />
                        @else
                            <div
                                class="bg-brand-600 flex h-10 w-10 items-center justify-center rounded-xl text-xl font-bold text-white shadow-sm">
                                {{ substr(get_system_setting('app_name', 'Q'), 0, 1) }}
                            </div>
                            <span
                                class="text-xl font-extrabold tracking-tight text-gray-900">{{ get_system_setting('app_name', 'Qlinkon') }}</span>
                        @endif
                    </a>
                </div>

                {{-- Desktop Navigation --}}
                <nav class="hidden items-center space-x-8 md:flex">
                    <a href="{{ url('/') }}"
                        class="hover:text-brand-600 text-sm font-semibold text-gray-600 transition-colors">Home</a>
                    <a href="{{ url('/about') }}"
                        class="hover:text-brand-600 text-sm font-semibold text-gray-600 transition-colors">About Us</a>
                    <a href="{{ url('/contact') }}"
                        class="hover:text-brand-600 text-sm font-semibold text-gray-600 transition-colors">Contact</a>

                    <div class="mx-2 h-6 w-px bg-gray-200"></div>

                    @auth
                        <a href="{{ url('/dashboard') }}"
                            class="text-brand-600 hover:text-brand-700 text-sm font-bold transition-colors">Dashboard</a>
                    @else
                        <a href="{{ route('admin.login') }}"
                            class="hover:text-brand-600 text-sm font-semibold text-gray-600 transition-colors">Log in</a>
                        <a href="{{ route('welcome') }}#contact"
                            class="bg-brand-600 hover:bg-brand-700 shadow-brand-500/30 rounded-lg px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-all">Get
                            Started</a>
                    @endauth
                </nav>

                {{-- Mobile Menu Button --}}
                <div class="flex items-center md:hidden">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" type="button"
                        class="rounded-md p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none">
                        <i class="fa-solid fa-bars text-xl" x-show="!mobileMenuOpen"></i>
                        <i class="fa-solid fa-xmark text-xl" x-show="mobileMenuOpen" x-cloak></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile Navigation Dropdown --}}
        <div x-show="mobileMenuOpen" x-collapse x-cloak
            class="absolute w-full border-t border-gray-100 bg-white shadow-xl md:hidden">
            <div class="space-y-1 px-4 pt-2 pb-6">
                <a href="{{ url('/') }}"
                    class="hover:text-brand-600 block rounded-md px-3 py-3 text-base font-semibold text-gray-700 hover:bg-gray-50">Home</a>
                <a href="{{ url('/about') }}"
                    class="hover:text-brand-600 block rounded-md px-3 py-3 text-base font-semibold text-gray-700 hover:bg-gray-50">About
                    Us</a>
                <a href="{{ url('/contact') }}"
                    class="hover:text-brand-600 block rounded-md px-3 py-3 text-base font-semibold text-gray-700 hover:bg-gray-50">Contact
                    Us</a>

                <div class="my-2 border-t border-gray-100 pt-2"></div>

                @auth
                    <a href="{{ url('/dashboard') }}"
                        class="text-brand-600 hover:bg-brand-50 block rounded-md px-3 py-3 text-base font-bold">Go to
                        Dashboard</a>
                @else
                    <a href="{{ route('admin.login') }}"
                        class="hover:text-brand-600 block rounded-md px-3 py-3 text-base font-semibold text-gray-700 hover:bg-gray-50">Log
                        in</a>
                    <a href="{{ route('welcome') }}#contact"
                        class="bg-brand-600 hover:bg-brand-700 mt-2 block rounded-lg px-3 py-3 text-center font-bold text-white">Get
                        Started</a>
                @endauth
            </div>
        </div>
    </header>

    {{-- ─── MAIN CONTENT ─── --}}
    <main class="w-full flex-grow">
        @yield ('content')
    </main>

    {{-- ─── FOOTER ─── --}}
    <footer class="mt-auto border-t border-gray-200 bg-white pt-16 pb-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-12 grid grid-cols-1 gap-12 md:grid-cols-4 md:gap-8">
                {{-- Brand Info --}}
                <div class="col-span-1 md:col-span-1">
                    <a href="{{ url('/') }}" class="mb-4 flex items-center gap-3">
                        @if ($logo)
                            <img src="{{ asset('storage/' . $logo) }}" alt="Logo"
                                class="h-8 w-auto object-contain" />
                        @else
                            <div
                                class="bg-brand-600 flex h-8 w-8 items-center justify-center rounded-lg font-bold text-white shadow-sm">
                                {{ substr(get_system_setting('app_name', 'Q'), 0, 1) }}
                            </div>
                            <span
                                class="text-lg font-bold tracking-tight text-gray-900">{{ get_system_setting('app_name', 'Qlinkon') }}</span>
                        @endif
                    </a>
                    <p class="mb-6 text-sm leading-relaxed text-gray-500">Empowering plant nurseries with modern AI,
                        smart QR tagging, and seamless digital management.</p>
                    {{-- Social Links --}}
                    <div class="flex space-x-4">
                        <a href="https://wa.me/919925180106"
                            class="hover:bg-brand-50 hover:text-brand-600 flex h-8 w-8 items-center justify-center rounded-full bg-gray-50 text-gray-400 transition-colors">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z">
                                </path>
                            </svg>
                        </a>

                        <a href="https://www.instagram.com/qlinkontech/"
                            class="hover:bg-brand-50 hover:text-brand-600 flex h-8 w-8 items-center justify-center rounded-full bg-gray-50 text-gray-400 transition-colors">
                            <i class="fa-brands fa-instagram"></i>
                        </a>

                        <a href="https://www.facebook.com/qlinkontech/"
                            class="hover:bg-brand-50 hover:text-brand-600 flex h-8 w-8 items-center justify-center rounded-full bg-gray-50 text-gray-400 transition-colors">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>

                        <a href="https://www.youtube.com/@qlinkontec"
                            class="hover:bg-brand-50 hover:text-brand-600 flex h-8 w-8 items-center justify-center rounded-full bg-gray-50 text-gray-400 transition-colors">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M21.582 6.186a2.665 2.665 0 0 0-1.874-1.889C18.053 3.8 12 3.8 12 3.8s-6.053 0-7.708.497a2.665 2.665 0 0 0-1.874 1.889C2 7.854 2 12 2 12s0 4.146.418 5.814a2.665 2.665 0 0 0 1.874 1.889C6.053 20.2 12 20.2 12 20.2s6.053 0 7.708-.497a2.665 2.665 0 0 0 1.874-1.889C22 16.146 22 12 22 12s0-4.146-.418-5.814zM9.993 15.545V8.455L15.992 12l-5.999 3.545z">
                                </path>
                            </svg>
                        </a>
                    </div>
                </div>

                {{-- Links: Product --}}
                <div>
                    <h3 class="mb-4 text-sm font-bold tracking-wider text-gray-900 uppercase">Product</h3>
                    <ul class="space-y-3">
                        <li>
                            <a href="https://shop.qlinkon.com/product/e-commerce-online-shop-setup-sell-online"
                                class="hover:text-brand-600 text-sm text-gray-500 transition-colors">Ecommerce Shop</a>
                        </li>
                        <li>
                            <a href="https://shop.qlinkon.com/product/astrodesk-complete-astrologer-management-software-appointments-clients-remedies-reports-all-in-one-system"
                                class="hover:text-brand-600 text-sm text-gray-500 transition-colors">iAstro</a>
                        </li>
                        <li>
                            <a href="https://shop.qlinkon.com/product/digital-menu-and-order-inquiry-for-restaurant-hotel-rooms"
                                class="hover:text-brand-600 text-sm text-gray-500 transition-colors">Digital Menu</a>
                        </li>
                    </ul>
                </div>

                {{-- Links: Support --}}
                <div>
                    <h3 class="mb-4 text-sm font-bold tracking-wider text-gray-900 uppercase">Support</h3>
                    <ul class="space-y-3">
                        <li>
                            <a href="{{ url('/contact') }}"
                                class="hover:text-brand-600 text-sm text-gray-500 transition-colors">Contact Us</a>
                        </li>
                        @if (get_system_setting('support_email'))
                            <li>
                                <a href="mailto:{{ get_system_setting('support_email', 'hi@qlinkon.com') }}"
                                    class="hover:text-brand-600 mt-4 text-sm text-gray-500">
                                    <i class="fa-solid fa-envelope mr-1.5 text-gray-400"></i>
                                    {{ get_system_setting('support_email', 'hi@qlinkon.com') }}</a>
                            </li>
                        @endif
                        @if (get_system_setting('support_phone'))
                            <li>
                                <a href="tel:{{ get_system_setting('support_phone') }}"
                                    class="hover:text-brand-600 text-sm text-gray-500">
                                    <i class="fa-solid fa-phone mr-1.5 text-gray-400"></i>
                                    {{ get_system_setting('support_phone') }}
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>

                {{-- Links: Legal --}}
                <div>
                    <h3 class="mb-4 text-sm font-bold tracking-wider text-gray-900 uppercase">Legal</h3>
                    <ul class="space-y-3">
                        <li>
                            <a href="{{ url('/terms-of-service') }}"
                                class="hover:text-brand-600 text-sm text-gray-500 transition-colors">Terms of
                                Service</a>
                        </li>
                        <li>
                            <a href="{{ url('/privacy-policy') }}"
                                class="hover:text-brand-600 text-sm text-gray-500 transition-colors">Privacy Policy</a>
                        </li>
                        <li>
                            <a href="{{ url('/refund-policy') }}"
                                class="hover:text-brand-600 text-sm text-gray-500 transition-colors">Refund &
                                Cancellation</a>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Copyright --}}
            <div class="flex flex-col items-center justify-between gap-4 border-t border-gray-100 pt-8 md:flex-row">
                <p class="text-sm text-gray-400">&copy; {{ date('Y') }}
                    {{ get_system_setting('app_name', 'Qlinkon') }}. All rights reserved.</p>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-400">Made with</span>
                    <i class="fa-solid fa-heart text-xs text-red-500"></i>
                    <span class="text-xs font-semibold text-gray-400">in India</span>
                </div>
            </div>
        </div>
    </footer>

    {{-- ─── CORE SCRIPTS ─── --}}

    {{-- Alpine JS --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Custom Scripts Stack --}}
    @stack ('scripts')
</body>

</html>
