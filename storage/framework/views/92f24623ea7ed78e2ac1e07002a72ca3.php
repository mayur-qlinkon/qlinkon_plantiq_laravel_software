<html lang="en">

<head>
    <meta charset="UTF-8" />
    <?php
        // Database se settings fetch kar rahe hain, nahi toh default text display hoga
        $seoTitle = get_system_setting('seo_title') ?: 'Plantiq — AI Business Software for India | Qlinkon Technology';
        $seoDescription =
            get_system_setting('seo_description') ?:
            'Manage your nursery from AI plant guidance and billing to inventory, CRM, staff management, online selling —all from a single mobile or desktop platform. Built by Qlinkon Technology, Junagadh & Ahmedabad, Gujarat.';
        $seoKeywords =
            get_system_setting('seo_keywords') ?:
            'Software Comapany Ahmedabad, Marketing Agency Ahmedabad, Qlinkon Technology';

        // Image check: seo_meta_image ya seo_image dono me se jo bhi set ho
        $seoImageKey = get_system_setting('seo_meta_image') ?: get_system_setting('seo_image');
        $seoImage = $seoImageKey ? asset('storage/' . $seoImageKey) : get_system_setting('app_logo');
    ?>

    <title><?php echo e($seoTitle); ?></title>
    <meta name="description" content="<?php echo e($seoDescription); ?>" />
    <meta name="keywords" content="<?php echo e($seoKeywords); ?>" />
    <meta name="author" content="Qlinkon Technology" />
    <meta name="robots" content="index,follow" />

    <!-- Open Graph / Facebook / WhatsApp -->
    <meta property="og:type" content="article" />
    <meta property="og:url" content="https://qlinkon.com/plantiq" />
    <meta property="og:title" content="<?php echo e($seoTitle); ?>" />
    <meta property="og:description" content="<?php echo e($seoDescription); ?>" />
    <meta property="og:image" content="<?php echo e($seoImage); ?>" />
    <meta property="og:site_name" content="Qlinkon Technology" />

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?php echo e($seoTitle); ?>" />
    <meta name="twitter:description" content="<?php echo e($seoDescription); ?>" />
    <meta name="twitter:image" content="<?php echo e($seoImage); ?>" />

    <meta name="google-site-verification" content="O3TJTaz9kN9SgstguN5mP8XNJNnq4iTPKg_uaQ0QqRg" />

    <link rel="canonical" href="https://qlinkon.com/plantiq" />

    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/png"
        href="<?php echo e(get_system_setting('app_favicon') ? asset('storage/' . get_system_setting('app_favicon')) : asset('assets/icons/favicon.png')); ?>" />

    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>" />
    <link rel="stylesheet" href="<?php echo e(asset('assets/css/welcome.css')); ?>" />

    <!-- SweetAlert2 -->
    <script src="<?php echo e(asset('assets/js/sweetalert2.js')); ?>"></script>
    <!-- Alpine.js (Keep Only One) -->
    <script defer="" src="<?php echo e(asset('assets/js/alpinejs.min.js')); ?>"></script>

    <style>
        html {
            scroll-behavior: smooth;
        }

        body {
            background-color: #f7f7f5;
            font-family: "Thicccboi", system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
            user-select: none;
        }

        [x-cloak] {
            display: none !important;
        }

        /* Custom Animations */
        /* Scroll-reveal — elements start hidden, JS adds .is-visible when in viewport */
        .reveal {
            opacity: 0;
            transform: translateY(28px);
            transition:
                opacity 0.65s cubic-bezier(0.16, 1, 0.3, 1),
                transform 0.65s cubic-bezier(0.16, 1, 0.3, 1);
            will-change: opacity, transform;
        }

        .reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .reveal-delay-1 {
            transition-delay: 80ms;
        }

        .reveal-delay-2 {
            transition-delay: 160ms;
        }

        .reveal-delay-3 {
            transition-delay: 240ms;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Keep for hero (fires immediately — correct behaviour above the fold) */
        .fade-in-up {
            animation: fadeInUp 0.7s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .delay-100 {
            animation-delay: 80ms;
        }

        .delay-200 {
            animation-delay: 160ms;
        }

        .delay-300 {
            animation-delay: 240ms;
        }

        /* Custom Play Button Wrapper and Styles with Hover Ping Effect */
        .slider-play-btn-pos {
            position: absolute;
            bottom: 2.5rem;
            left: 2.5rem;
            z-index: 30;
        }

        @media (min-width: 768px) {
            .slider-play-btn-pos {
                bottom: 4rem;
                left: 4rem;
            }
        }

        .premium-circle-play-btn {
            position: relative;
            display: flex !important;
            align-items: center;
            justify-content: center;
            width: 3rem !important;
            height: 3rem !important;
            background-color: #e11d48 !important;
            border-radius: 9999px !important;
            box-shadow: 0 25px 50px -12px rgba(225, 29, 72, 0.5) !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            cursor: pointer;
            flex-shrink: 0 !important;
            aspect-ratio: 1 / 1 !important;
            border: none !important;
        }

        @media (min-width: 640px) {
            .premium-circle-play-btn {
                width: 4rem !important;
                height: 4rem !important;
            }
        }

        @media (min-width: 768px) {
            .premium-circle-play-btn {
                width: 5rem !important;
                height: 5rem !important;
            }
        }

        .premium-circle-play-btn:hover {
            transform: scale(1.05) !important;
            background-color: #be123c !important;
        }

        /* Base Normal State Pulse */
        .premium-circle-play-btn .pulse-ring {
            position: absolute;
            inset: 0;
            border-radius: 9999px !important;
            background-color: #e11d48 !important;
            opacity: 0.3;
            animation: custom-pulse-anim 2s cubic-bezier(0.4, 0, 0.6, 1) infinite !important;
        }

        /* Trigger Fast Scaling Ping Effect on Hover */
        .premium-circle-play-btn:hover .pulse-ring {
            animation: custom-ping-anim 1s cubic-bezier(0, 0, 0.2, 1) infinite !important;
        }

        /* Normal Breathing Pulse Animation */
        @keyframes custom-pulse-anim {

            0%,
            100% {
                opacity: 0.3;
                transform: scale(1);
            }

            50% {
                opacity: 0.12;
                transform: scale(1.06);
            }
        }

        /* Tailwind Equivalent Ping (Scale up and Fade out) Animation */
        /* Tailwind Equivalent Ping (Scale up and Fade out) Animation */
        @keyframes custom-ping-anim {

            75%,
            100% {
                transform: scale(1.8);
                opacity: 0;
            }
        }

        /* Seamless Infinite Marquee */
        @keyframes marquee {
            0% {
                transform: translateX(0%);
            }

            100% {
                transform: translateX(-100%);
            }
        }

        .animate-marquee {
            display: flex;
            animation: marquee 30s linear infinite;
        }

        .marquee-wrapper:hover .animate-marquee {
            animation-play-state: paused;
        }

        @keyframes pulseSlow {

            0%,
            100% {
                box-shadow: 0 4px 14px rgba(0, 174, 239, 0.25);
            }

            50% {
                box-shadow: 0 4px 22px rgba(0, 174, 239, 0.55);
            }
        }

        .animate-pulse-slow {
            animation: pulseSlow 2.2s ease-in-out infinite;
        }
    </style>
</head>

<body class="overflow-x-hidden text-gray-900 antialiased" x-data="{
    pageData: null,
    isLoading: true,
    fetchData() {
        // Replace 'plantiq' with your dynamic slug if needed
        fetch('https://qlinkon.com/api/landing-page/plantiq')
            .then((res) => res.json())
            .then((data) => {
                if (data.success) {
                    this.pageData = data.data;
                }
                this.isLoading = false;
            })
            .catch((err) => {
                console.error('Error fetching page data:', err);
                this.isLoading = false;
            });
    },
}" x-init="fetchData()">
    <?php
        $isLoggedIn = auth()->check();
        $user = $isLoggedIn ? auth()->user() : null;

        // Does this logged-in owner already have an active subscription?
        $hasActiveSubscription = false;
        $dashboardUrl = route('admin.login');

        if ($isLoggedIn) {
            $companySlug = $user->company->slug ?? (request()->route('slug') ?? 'store');

            if ($user->client) {
                $dashboardUrl = route('storefront.portal.dashboard', ['slug' => $companySlug]);
                $hasActiveSubscription = true; // customers aren't gated by this CTA
    } elseif (is_super_admin()) {
        $dashboardUrl = route('platform.dashboard');
        $hasActiveSubscription = true;
    } else {
        $dashboardUrl = route('admin.dashboard');

        $hasActiveSubscription =
            $user->company &&
            \App\Models\CompanySubscription::where('company_id', $user->company_id)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                        })
                        ->exists();
            }
        }
    ?>

    <nav
        class="fade-in-up relative top-0 z-50 mx-auto flex max-w-7xl items-center justify-between border-b border-gray-200/40 bg-[#F7F7F5]/90 px-4 py-3 backdrop-blur-md sm:px-6 sm:py-6">
        <a href="https://qlinkon.com"
            class="static z-10 block flex items-center justify-center sm:absolute sm:left-1/2 sm:-translate-x-1/2">
            <img src="https://qlinkon.com/storage/landing_pages/logos/yCxXgXAF70U6hZF7i7oyGNF2uQUT4pxD0ljqyAkH.webp"
                alt="Plantiq" class="h-8 w-auto max-w-[135px] object-contain sm:h-10 sm:max-w-[180px] md:h-12" />
        </a>

        <div class="relative z-20 ml-auto flex items-center gap-2 sm:gap-3">
            <?php if($isLoggedIn && $hasActiveSubscription): ?>
                
                <a href="<?php echo e($dashboardUrl); ?>"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-900/10 bg-white/50 px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm backdrop-blur transition-all hover:text-gray-600 sm:text-base">
                    <span>Dashboard</span>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </a>
            <?php elseif($isLoggedIn): ?>
                <div x-data="{ open: false }" class="relative inline-block">
                    <button @click="open = !open"
                        class="flex items-center gap-2.5 rounded-lg px-2 py-1.5 transition-colors hover:bg-gray-50 focus:outline-none">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full border border-indigo-100 bg-indigo-50 text-xs font-bold text-indigo-600">
                            <?php echo e(strtoupper(substr($user->name, 0, 1))); ?>

                        </div>

                        <div class="hidden text-left sm:block">
                            <span
                                class="mb-0.5 block text-[10px] leading-none font-medium tracking-wide text-gray-400 uppercase">
                                Signed in as
                            </span>
                            <span class="block max-w-[100px] truncate text-sm leading-none font-semibold text-gray-800">
                                <?php echo e(Str::of($user->name)->before(' ')); ?>

                            </span>
                        </div>

                        <svg class="h-4 w-4 text-gray-400 transition-transform duration-200"
                            :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95" style="display: none"
                        class="absolute right-0 z-50 mt-1 w-32 overflow-hidden rounded-lg border border-gray-100 bg-white shadow-sm">
                        <form method="POST" action="<?php echo e(route('logout')); ?>" class="m-0">
                            <?php echo csrf_field(); ?>
                            <button type="submit"
                                class="w-full px-4 py-2.5 text-left text-sm font-medium text-gray-600 transition-colors hover:bg-red-50 hover:text-red-600">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                
                <a href="<?php echo e(route('login')); ?>"
                    class="inline-flex items-center gap-1.5 rounded-2xl border border-gray-900/10 bg-green-500 px-4 py-2 text-sm font-semibold text-white shadow-sm backdrop-blur transition-all hover:bg-green-600 sm:text-base">
                    <span>Log in</span>
                </a>
                <a href="#contact"
                    class="inline-flex items-center gap-1.5 rounded-2xl border border-gray-900/10 bg-gray-200 px-4 py-2 text-sm font-semibold shadow-sm backdrop-blur transition-all hover:bg-gray-300 sm:text-base">
                    <span>Get in Touch</span>
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="mx-auto max-w-7xl px-4 pt-10 pb-8 text-center sm:px-6 sm:pt-14 sm:pb-12 md:pt-16 lg:px-8">
        <h1
            class="fade-in-up text-[2.4rem] leading-[1.15] font-semibold tracking-tight delay-100 sm:text-5xl sm:leading-tight md:text-6xl lg:text-7xl">
            All-in-One Plant Nursery<br />
            Automation with AI
        </h1>
        <p class="fade-in-up mx-auto mt-6 max-w-2xl text-lg text-gray-500 delay-200">Manage your nursery from AI plant
            guidance and billing to inventory, CRM, staff management, online selling —all from a single mobile or
            desktop platform.</p>

        <div class="fade-in-up mt-10 flex items-center justify-center gap-4 delay-300">
            <button type="button" x-on:click="$store.ui.inquiryModal = true"
                class="shadow-black-500/30 block w-64 cursor-pointer rounded-lg bg-black py-3.5 text-center font-medium text-white shadow-lg transition-all hover:-translate-y-1 hover:bg-gray-900">
                Make an Inquiry
            </button>
        </div>
    </main>

    <section class="fade-in-up relative mx-auto mt-12 max-w-7xl px-4 pb-24 delay-300 sm:px-6 lg:px-8">
        <div
            class="absolute inset-x-0 top-0 mx-2 h-[75%] overflow-hidden rounded-[28px] bg-gray-300 opacity-100 shadow-lg sm:mx-6 sm:rounded-[40px] md:h-[80%]">
            <div class="absolute inset-0 opacity-10"
                style="
                    background-image: repeating-linear-gradient(
                        90deg,
                        transparent,
                        transparent 20px,
                        #ffffff 20px,
                        #ffffff 21px
                    );
                ">
            </div>
        </div>
        <div class="relative z-10 pt-8 md:pt-12">
            <div class="relative z-20 mb-10 flex justify-center px-2">
                <div class="mx-auto flex max-w-full items-center gap-1 overflow-x-auto rounded-full border border-white/80 bg-white p-1.5 shadow-lg sm:gap-4 sm:p-2"
                    style="-ms-overflow-style: none; scrollbar-width: none">
                    <button onclick="switchSlide(1, true)" id="tab-1"
                        class="tab-btn flex items-center gap-1.5 rounded-full px-3 py-2 text-xs font-medium whitespace-nowrap text-gray-500 transition-all sm:gap-2.5 sm:px-6 sm:py-2.5 sm:text-sm">
                        <span class="dot h-2 w-2 rounded-full bg-gray-300"></span>
                        <svg class="check-icon hidden h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M8.603 3.799A4.49 4.49 0 0112 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 013.498 1.307 4.491 4.491 0 011.307 3.497A4.49 4.49 0 0121.75 12a4.49 4.49 0 01-1.549 3.397 4.491 4.491 0 01-1.307 3.497 4.491 4.491 0 01-3.497 1.307A4.49 4.49 0 0112 21.75a4.49 4.49 0 01-3.397-1.549 4.49 4.49 0 01-3.498-1.306 4.491 4.491 0 01-1.307-3.498A4.49 4.49 0 012.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 011.307-3.497 4.49 4.49 0 013.497-1.307zm7.007 6.387a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z"
                                clip-rule="evenodd"></path>
                        </svg>
                        All Device Friendly
                    </button>
                    <button onclick="switchSlide(2, true)" id="tab-2"
                        class="tab-btn flex items-center gap-1.5 rounded-full px-3 py-2 text-xs font-medium whitespace-nowrap text-gray-500 transition-all hover:text-gray-900 sm:gap-2.5 sm:px-6 sm:py-2.5 sm:text-sm">
                        <span class="dot h-2 w-2 rounded-full bg-gray-300"></span>
                        <svg class="check-icon hidden h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M8.603 3.799A4.49 4.49 0 0112 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 013.498 1.307 4.491 4.491 0 011.307 3.497A4.49 4.49 0 0121.75 12a4.49 4.49 0 01-1.549 3.397 4.491 4.491 0 01-1.307 3.497 4.491 4.491 0 01-3.497 1.307A4.49 4.49 0 0112 21.75a4.49 4.49 0 01-3.397-1.549 4.49 4.49 0 01-3.498-1.306 4.491 4.491 0 01-1.307-3.498A4.49 4.49 0 012.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 011.307-3.497 4.49 4.49 0 013.497-1.307zm7.007 6.387a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z"
                                clip-rule="evenodd"></path>
                        </svg>
                        Nursery Online
                    </button>
                    <button onclick="switchSlide(3, true)" id="tab-3"
                        class="tab-btn flex items-center gap-1.5 rounded-full px-3 py-2 text-xs font-semibold whitespace-nowrap text-gray-900 transition-all hover:text-gray-900 sm:gap-2.5 sm:px-6 sm:py-2.5 sm:text-sm">
                        <span class="dot hidden h-2 w-2 rounded-full bg-gray-300"></span>
                        <svg class="check-icon h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M8.603 3.799A4.49 4.49 0 0112 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 013.498 1.307 4.491 4.491 0 011.307 3.497A4.49 4.49 0 0121.75 12a4.49 4.49 0 01-1.549 3.397 4.491 4.491 0 01-1.307 3.497 4.491 4.491 0 01-3.497 1.307A4.49 4.49 0 0112 21.75a4.49 4.49 0 01-3.397-1.549 4.49 4.49 0 01-3.498-1.306 4.491 4.491 0 01-1.307-3.498A4.49 4.49 0 012.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 011.307-3.497 4.49 4.49 0 013.497-1.307zm7.007 6.387a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z"
                                clip-rule="evenodd"></path>
                        </svg>
                        Voice Plant Guide
                    </button>
                </div>
            </div>

            <div
                class="relative mx-auto max-w-6xl rounded-[1.5rem] p-2 transition-all duration-500 sm:rounded-[2rem] sm:p-4 lg:p-8">
                <div class="relative w-full overflow-hidden rounded-lg sm:rounded-xl">
                    <div id="slide-1"
                        class="slide-content pointer-events-none absolute z-0 w-full opacity-0 transition-opacity duration-700">
                        <img src="https://qlinkon.com/storage/landing_pages/showcase_tab_1_images/LPD8ZZKtU3IhdcgHpwV2AFrYtXPmG0z6N5fQq5V5.webp"
                            alt="All Device Friendly"
                            class="block h-auto w-full rounded-lg object-contain sm:rounded-xl" fetchpriority="high"
                            loading="eager" />

                        <div class="slider-play-btn-pos">
                            <button type="button" onclick="openVideoModal()" class="premium-circle-play-btn">
                                <span class="pulse-ring"></span>
                                <svg class="relative z-10 fill-current text-white" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg"
                                    style="width: 38%; height: 38%; transform: translateX(2px)">
                                    <path d="M8 5v14l11-7z" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div id="slide-2"
                        class="slide-content pointer-events-none absolute inset-0 z-0 w-full opacity-0 transition-opacity duration-700">
                        <img src="https://qlinkon.com/storage/landing_pages/showcase_tab_2_images/rPieoCyF4OQHJ6Wyndtkxxp36rn4bDLgSCb7Kqia.webp"
                            alt="Nursery Online" class="block h-auto w-full rounded-lg object-contain sm:rounded-xl"
                            loading="lazy" decoding="async" />

                        <div class="slider-play-btn-pos">
                            <button type="button" onclick="openVideoModal()" class="premium-circle-play-btn">
                                <span class="pulse-ring"></span>
                                <svg class="relative z-10 fill-current text-white" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg"
                                    style="width: 38%; height: 38%; transform: translateX(2px)">
                                    <path d="M8 5v14l11-7z" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div id="slide-3"
                        class="slide-content relative inset-0 z-10 w-full opacity-100 transition-opacity duration-700">
                        <img src="https://qlinkon.com/storage/landing_pages/showcase_tab_3_images/9yJ4Yy9YtVAq7gH7OAsnFJsCERgeke2Na74aj96k.webp"
                            alt="Voice Plant Guide"
                            class="block h-auto w-full rounded-lg object-contain sm:rounded-xl" loading="lazy"
                            decoding="async" />

                        <div class="slider-play-btn-pos">
                            <button type="button" onclick="openVideoModal()" class="premium-circle-play-btn">
                                <span class="pulse-ring"></span>
                                <svg class="relative z-10 fill-current text-white" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg"
                                    style="width: 38%; height: 38%; transform: translateX(2px)">
                                    <path d="M8 5v14l11-7z" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    

    <section class="fade-in-up mx-auto max-w-7xl px-4 py-16 delay-200 sm:px-6 lg:px-8 lg:py-24">
        <h2 class="mb-10 text-center text-3xl font-bold tracking-tight text-gray-900 sm:mb-16 sm:text-4xl md:text-5xl">
            Grow 10x faster than your competitors
        </h2>

        <div class="grid gap-8 lg:grid-cols-2 lg:gap-12">
            <div
                class="group relative flex min-h-[380px] flex-col overflow-hidden rounded-2xl p-6 shadow-[0_20px_50px_-12px_rgba(0,0,0,0.1)] sm:min-h-[480px] sm:p-8 md:p-10">
                <div class="absolute inset-0 bg-gray-700 transition-transform duration-1000 group-hover:scale-105">
                </div>

                <div class="relative z-10 flex h-full flex-col">
                    <div class="mb-6 flex items-center gap-2 text-sm font-medium tracking-wide text-white/95">
                        <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M19 9l1.25-2.75L23 5l-2.75-1.25L19 1l-1.25 2.75L15 5l2.75 1.25L19 9zm-7.5.5L9 4 6.5 9.5 1 12l5.5 2.5L9 20l2.5-5.5L17 12l-5.5-2.5zM19 15l-1.25 2.75L15 19l2.75 1.25L19 23l1.25-2.75L23 19l-2.75-1.25L19 15z">
                            </path>
                        </svg>
                        India's First
                    </div>

                    <h3
                        class="mb-auto text-[2rem] leading-[1.15] font-semibold tracking-tight text-white drop-shadow-sm md:text-[2.25rem]">
                        Manage Your Entire Nursery — Online &amp; Offline — from One Smart Dashboard
                    </h3>

                    <div class="mt-10 flex flex-col items-start gap-3">
                        <span
                            class="cursor-default rounded-full bg-white px-5 py-2.5 text-sm font-medium text-gray-800 shadow-[0_4px_14px_0_rgba(0,0,0,0.05)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_6px_20px_rgba(0,0,0,0.1)]">
                            Simple &amp; AI-Powered Features
                        </span>
                        <span
                            class="cursor-default rounded-full bg-white px-5 py-2.5 text-sm font-medium text-gray-800 shadow-[0_4px_14px_0_rgba(0,0,0,0.05)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_6px_20px_rgba(0,0,0,0.1)]">
                            Built Exclusively for Nurseries
                        </span>
                        <span
                            class="cursor-default rounded-full bg-white px-5 py-2.5 text-sm font-medium text-gray-800 shadow-[0_4px_14px_0_rgba(0,0,0,0.05)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_6px_20px_rgba(0,0,0,0.1)]">
                            Let AI Handle the Heavy Lifting
                        </span>
                    </div>
                </div>
            </div>

            <div
                class="relative flex min-h-[380px] items-center justify-center overflow-hidden rounded-2xl border border-gray-100 bg-white p-6 shadow-[0_20px_50px_-12px_rgba(0,0,0,0.05)] sm:min-h-[480px] sm:p-8 md:p-10">
                <div
                    class="relative z-10 h-44 w-44 overflow-hidden rounded-[2.5rem] bg-gray-50 shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-transform duration-500 hover:scale-105">
                    <img src="https://qlinkon.com/storage/landing_pages/float_profile_images/LXnfwa729SWmT7Tld3QhLxHmcCwYq4MrrFcdU6C3.webp"
                        alt="User Profile" class="h-full w-full object-cover" loading="lazy" decoding="async" />
                </div>

                <div class="pointer-events-none absolute inset-0 z-20 h-full w-full">
                    <span
                        class="pointer-events-auto absolute top-[5%] left-[50%] -translate-x-1/2 rotate-[6deg] cursor-default rounded-full border border-gray-100 bg-white/95 px-3 py-1.5 text-[11px] font-medium whitespace-nowrap text-gray-700 shadow-[0_4px_12px_rgba(0,0,0,0.06)] backdrop-blur-md transition-opacity duration-200 hover:opacity-75 md:top-[18%] md:px-5 md:py-2.5 md:text-[13px]">
                        Plants Profile
                    </span>

                    <span
                        class="pointer-events-auto absolute top-[15%] left-[2%] -rotate-[12deg] cursor-default rounded-full border border-gray-100 bg-white/95 px-3 py-1.5 text-[11px] font-medium whitespace-nowrap text-gray-700 shadow-[0_4px_12px_rgba(0,0,0,0.06)] backdrop-blur-md transition-opacity duration-200 hover:opacity-75 sm:left-[8%] md:top-[30%] md:left-[15%] md:px-5 md:py-2.5 md:text-[13px]">
                        Inventory
                    </span>

                    <span
                        class="pointer-events-auto absolute top-[22%] right-[2%] rotate-[10deg] cursor-default rounded-full border border-gray-100 bg-white/95 px-3 py-1.5 text-[11px] font-medium whitespace-nowrap text-gray-700 shadow-[0_4px_12px_rgba(0,0,0,0.06)] backdrop-blur-md transition-opacity duration-200 hover:opacity-75 sm:right-[8%] md:top-[30%] md:right-[12%] md:px-5 md:py-2.5 md:text-[13px]">
                        CRM
                    </span>

                    <span
                        class="pointer-events-auto absolute bottom-[25%] left-[2%] rotate-[8deg] cursor-default rounded-full border border-gray-100 bg-white/95 px-3 py-1.5 text-[11px] font-medium whitespace-nowrap text-gray-700 shadow-[0_4px_12px_rgba(0,0,0,0.06)] backdrop-blur-md transition-opacity duration-200 hover:opacity-75 sm:left-[8%] md:bottom-[32%] md:left-[18%] md:px-5 md:py-2.5 md:text-[13px]">
                        HRM
                    </span>

                    <span
                        class="pointer-events-auto absolute right-[2%] bottom-[15%] -rotate-[14deg] cursor-default rounded-full border border-gray-100 bg-white/95 px-3 py-1.5 text-[11px] font-medium whitespace-nowrap text-gray-700 shadow-[0_4px_12px_rgba(0,0,0,0.06)] backdrop-blur-md transition-opacity duration-200 hover:opacity-75 sm:right-[8%] md:right-[15%] md:bottom-[30%] md:px-5 md:py-2.5 md:text-[13px]">
                        Projects
                    </span>

                    <span
                        class="pointer-events-auto absolute bottom-[5%] left-[50%] -translate-x-1/2 -rotate-[4deg] cursor-default rounded-full border border-gray-100 bg-white/95 px-3 py-1.5 text-[11px] font-medium whitespace-nowrap text-gray-700 shadow-[0_4px_12px_rgba(0,0,0,0.06)] backdrop-blur-md transition-opacity duration-200 hover:opacity-75 md:bottom-[18%] md:px-5 md:py-2.5 md:text-[13px]">
                        E-Commerce
                    </span>
                </div>
            </div>
        </div>
    </section>

    <section class="relative mx-auto max-w-7xl px-4 pb-20 sm:px-6 lg:px-8 lg:pb-32">
        <div
            class="pointer-events-none top-0 z-50 -mx-4 bg-gradient-to-b from-[#F7F7F5] from-80% to-transparent px-4 sm:-mx-6 sm:px-6 md:sticky lg:-mx-8 lg:px-8">
            <div class="pt-16 pb-12 md:pt-28 md:pb-28">
                <h2
                    class="pointer-events-auto text-center text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl md:text-5xl">
                    Plantiq Tools to Scale Your Nursery
                </h2>
            </div>
        </div>

        <div class="relative mt-4 md:mt-8">
            <div class="reveal relative top-auto z-10 mb-10 w-full md:sticky md:[top:var(--card-top)] md:[z-index:var(--card-z)] md:mb-[15vh]"
                style="--card-top: 120px; --card-z: 10">
                <!-- Card Container -->
                <div
                    class="grid min-h-[400px] overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-md transition-shadow duration-300 hover:shadow-xl md:grid-cols-2">
                    <!-- Text Section -->
                    <div class="order-last flex flex-col justify-center p-8 sm:p-10 md:order-first md:p-12 lg:p-14">
                        <h3
                            class="mb-3 text-2xl font-bold tracking-tight text-gray-900 sm:mb-4 sm:text-3xl md:text-4xl">
                            Smart Talking QR Plant Profiles
                        </h3>
                        <p class="mb-8 text-[16px] leading-relaxed text-gray-500 md:text-lg">Let every plant explain
                            itself — no staff needed on the floor.</p>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">Let your plants talk to customers</span>
                            </li>
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">Multilingual audio stories.</span>
                            </li>
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">QR tags on fiber sticks Instant Kit</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Image Section: Mobile aspect ratio fix -->
                    <div
                        class="relative order-first aspect-[4/3] w-full bg-gradient-to-br from-sky-300 via-blue-200 to-cyan-100 sm:aspect-video md:order-last md:aspect-auto md:h-full">
                        <img src="https://qlinkon.com/storage/landing_pages/features/hox3W3QpDZQPZBoM4HbeqJfPO0O41qZt01vdNbyJ.webp"
                            alt="Smart Talking QR Plant Profiles" class="absolute inset-0 h-full w-full object-cover"
                            loading="lazy" decoding="async" />
                    </div>
                </div>
            </div>

            <div class="reveal relative top-auto z-10 mb-10 w-full md:sticky md:[top:var(--card-top)] md:[z-index:var(--card-z)] md:mb-[15vh]"
                style="--card-top: 150px; --card-z: 20">
                <!-- Card Container -->
                <div
                    class="grid min-h-[400px] overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-md transition-shadow duration-300 hover:shadow-xl md:grid-cols-2">
                    <!-- Text Section -->
                    <div class="order-last flex flex-col justify-center p-8 sm:p-10 md:p-12 lg:p-14">
                        <h3
                            class="mb-3 text-2xl font-bold tracking-tight text-gray-900 sm:mb-4 sm:text-3xl md:text-4xl">
                            POS, Invoice, Quotation &amp; Delivery Challan
                        </h3>
                        <p class="mb-8 text-[16px] leading-relaxed text-gray-500 md:text-lg">Bill in seconds from
                            counter or anywhere across your nursery.</p>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">Superfast QR scan billing</span>
                            </li>
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">GST &amp; Non-GST support</span>
                            </li>
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">Share bills on WhatsApp or Print it</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Image Section: Mobile aspect ratio fix -->
                    <div
                        class="relative order-first aspect-[4/3] w-full bg-gradient-to-br from-blue-200 via-sky-200 to-indigo-100 sm:aspect-video md:aspect-auto md:h-full">
                        <img src="https://qlinkon.com/storage/landing_pages/features/I3JrVEF82OtHk2a9DKcXSrCrgV9XnpHKweLMUjvw.webp"
                            alt="POS, Invoice, Quotation &amp; Delivery Challan"
                            class="absolute inset-0 h-full w-full object-cover" loading="lazy" decoding="async" />
                    </div>
                </div>
            </div>

            <div class="reveal relative top-auto z-10 mb-10 w-full md:sticky md:[top:var(--card-top)] md:[z-index:var(--card-z)] md:mb-[15vh]"
                style="--card-top: 180px; --card-z: 30">
                <!-- Card Container -->
                <div
                    class="grid min-h-[400px] overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-md transition-shadow duration-300 hover:shadow-xl md:grid-cols-2">
                    <!-- Text Section -->
                    <div class="order-last flex flex-col justify-center p-8 sm:p-10 md:order-first md:p-12 lg:p-14">
                        <h3
                            class="mb-3 text-2xl font-bold tracking-tight text-gray-900 sm:mb-4 sm:text-3xl md:text-4xl">
                            Digital Catalog &amp; E-Commerce
                        </h3>
                        <p class="mb-8 text-[16px] leading-relaxed text-gray-500 md:text-lg">Sell plants, pots &amp;
                            fertilizers online without building a website.</p>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">Digital catalog with order inquiry</span>
                            </li>
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">Book garden maintenance services</span>
                            </li>
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">online selling with payment collection</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Image Section: Mobile aspect ratio fix -->
                    <div
                        class="relative order-first aspect-[4/3] w-full bg-gradient-to-br from-cyan-200 via-sky-100 to-blue-200 sm:aspect-video md:order-last md:aspect-auto md:h-full">
                        <img src="https://qlinkon.com/storage/landing_pages/features/bblOkEfCKi7cjuPig08U0nN2nBIs7piZPlqMLtWi.webp"
                            alt="Digital Catalog &amp; E-Commerce" class="absolute inset-0 h-full w-full object-cover"
                            loading="lazy" decoding="async" />
                    </div>
                </div>
            </div>

            <div class="reveal relative top-auto z-10 mb-10 w-full md:sticky md:[top:var(--card-top)] md:[z-index:var(--card-z)] md:mb-[15vh]"
                style="--card-top: 210px; --card-z: 40">
                <!-- Card Container -->
                <div
                    class="grid min-h-[400px] overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-md transition-shadow duration-300 hover:shadow-xl md:grid-cols-2">
                    <!-- Text Section -->
                    <div class="order-last flex flex-col justify-center p-8 sm:p-10 md:p-12 lg:p-14">
                        <h3
                            class="mb-3 text-2xl font-bold tracking-tight text-gray-900 sm:mb-4 sm:text-3xl md:text-4xl">
                            AI Business Assistant
                        </h3>
                        <p class="mb-8 text-[16px] leading-relaxed text-gray-500 md:text-lg">Your nursery business
                            assistant available 24/7 — just ask in plain language.</p>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">Instant daily sales reports</span>
                            </li>
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">No manual searches needed</span>
                            </li>
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">Talk directly to your business data</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Image Section: Mobile aspect ratio fix -->
                    <div
                        class="relative order-first aspect-[4/3] w-full bg-gradient-to-br from-sky-300 via-blue-200 to-cyan-100 sm:aspect-video md:aspect-auto md:h-full">
                        <img src="https://qlinkon.com/storage/landing_pages/features/xgY0hjlH1dY9vFY9sQREkO7eeDgPGTJXZGkVYgIJ.webp"
                            alt="AI Business Assistant" class="absolute inset-0 h-full w-full object-cover"
                            loading="lazy" decoding="async" />
                    </div>
                </div>
            </div>

            <div class="reveal relative top-auto z-10 mb-10 w-full md:sticky md:[top:var(--card-top)] md:[z-index:var(--card-z)] md:mb-[15vh]"
                style="--card-top: 240px; --card-z: 50">
                <!-- Card Container -->
                <div
                    class="grid min-h-[400px] overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-md transition-shadow duration-300 hover:shadow-xl md:grid-cols-2">
                    <!-- Text Section -->
                    <div class="order-last flex flex-col justify-center p-8 sm:p-10 md:order-first md:p-12 lg:p-14">
                        <h3
                            class="mb-3 text-2xl font-bold tracking-tight text-gray-900 sm:mb-4 sm:text-3xl md:text-4xl">
                            Inventory &amp; Stock Management
                        </h3>
                        <p class="mb-8 text-[16px] leading-relaxed text-gray-500 md:text-lg">Inventory tracking, what's
                            selling, and what's running low.</p>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">Multi-store &amp; warehouse tracking.</span>
                            </li>
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">Automated low-stock alerts.</span>
                            </li>
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">slow-moving product analysis reports.</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Image Section: Mobile aspect ratio fix -->
                    <div
                        class="relative order-first aspect-[4/3] w-full bg-gradient-to-br from-blue-200 via-sky-200 to-indigo-100 sm:aspect-video md:order-last md:aspect-auto md:h-full">
                        <img src="https://qlinkon.com/storage/landing_pages/features/gTvXKltC93exzPOUzQRKCU5gcQryUu1VBtfj9rl4.webp"
                            alt="Inventory &amp; Stock Management" class="absolute inset-0 h-full w-full object-cover"
                            loading="lazy" decoding="async" />
                    </div>
                </div>
            </div>

            <div class="reveal relative top-auto z-10 mb-10 w-full md:sticky md:[top:var(--card-top)] md:[z-index:var(--card-z)] md:mb-[15vh]"
                style="--card-top: 270px; --card-z: 60">
                <!-- Card Container -->
                <div
                    class="grid min-h-[400px] overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-md transition-shadow duration-300 hover:shadow-xl md:grid-cols-2">
                    <!-- Text Section -->
                    <div class="order-last flex flex-col justify-center p-8 sm:p-10 md:p-12 lg:p-14">
                        <h3
                            class="mb-3 text-2xl font-bold tracking-tight text-gray-900 sm:mb-4 sm:text-3xl md:text-4xl">
                            CRM — Capture &amp; Convert Every Lead
                        </h3>
                        <p class="mb-8 text-[16px] leading-relaxed text-gray-500 md:text-lg">Never lose a customer
                            inquiry from walk-in, exhibition or online.</p>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">Lead allocate to staff with follow-up tracking</span>
                            </li>
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">Ai Doc Scanner instantly scan Invoice to Text</span>
                            </li>
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">Bulk lead management system</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Image Section: Mobile aspect ratio fix -->
                    <div
                        class="relative order-first aspect-[4/3] w-full bg-gradient-to-br from-cyan-200 via-sky-100 to-blue-200 sm:aspect-video md:aspect-auto md:h-full">
                        <img src="https://qlinkon.com/storage/landing_pages/features/6O0LgRyORFWQr6VcbTHoIBhQTrzOe6Y8jV1Lleql.webp"
                            alt="CRM — Capture &amp; Convert Every Lead"
                            class="absolute inset-0 h-full w-full object-cover" loading="lazy" decoding="async" />
                    </div>
                </div>
            </div>

            <div class="reveal relative top-auto z-10 mb-10 w-full md:sticky md:[top:var(--card-top)] md:[z-index:var(--card-z)] md:mb-[15vh]"
                style="--card-top: 300px; --card-z: 70">
                <!-- Card Container -->
                <div
                    class="grid min-h-[400px] overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-md transition-shadow duration-300 hover:shadow-xl md:grid-cols-2">
                    <!-- Text Section -->
                    <div class="order-last flex flex-col justify-center p-8 sm:p-10 md:order-first md:p-12 lg:p-14">
                        <h3
                            class="mb-3 text-2xl font-bold tracking-tight text-gray-900 sm:mb-4 sm:text-3xl md:text-4xl">
                            HRM, Task &amp; Attendance Management
                        </h3>
                        <p class="mb-8 text-[16px] leading-relaxed text-gray-500 md:text-lg">Manage your nursery team
                            without paperwork or WhatsApp chasing.</p>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">Geotagged QR attendance system</span>
                            </li>
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">Staff Daily task &amp; work logs</span>
                            </li>
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">Team announcements, Leave application, &amp; employee access
                                    Panel</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Image Section: Mobile aspect ratio fix -->
                    <div
                        class="relative order-first aspect-[4/3] w-full bg-gradient-to-br from-sky-300 via-blue-200 to-cyan-100 sm:aspect-video md:order-last md:aspect-auto md:h-full">
                        <img src="https://qlinkon.com/storage/landing_pages/features/B9yDRriwXUdcvItKyzKD6W1q3vR5Ak4Ek0aACWLD.webp"
                            alt="HRM, Task &amp; Attendance Management"
                            class="absolute inset-0 h-full w-full object-cover" loading="lazy" decoding="async" />
                    </div>
                </div>
            </div>

            <div class="reveal relative top-auto z-10 mb-10 w-full md:sticky md:[top:var(--card-top)] md:[z-index:var(--card-z)] md:mb-[15vh]"
                style="--card-top: 330px; --card-z: 80">
                <!-- Card Container -->
                <div
                    class="grid min-h-[400px] overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-md transition-shadow duration-300 hover:shadow-xl md:grid-cols-2">
                    <!-- Text Section -->
                    <div class="order-last flex flex-col justify-center p-8 sm:p-10 md:p-12 lg:p-14">
                        <h3
                            class="mb-3 text-2xl font-bold tracking-tight text-gray-900 sm:mb-4 sm:text-3xl md:text-4xl">
                            Project &amp; Service Subscription Tracker
                        </h3>
                        <p class="mb-8 text-[16px] leading-relaxed text-gray-500 md:text-lg">Manage garden contracts
                            and recurring service clients effortlessly.</p>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">Track project stages, milestones and deadlines</span>
                            </li>
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">subscription with renewal reminders built in</span>
                            </li>
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">Payment collection record per project</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Image Section: Mobile aspect ratio fix -->
                    <div
                        class="relative order-first aspect-[4/3] w-full bg-gradient-to-br from-blue-200 via-sky-200 to-indigo-100 sm:aspect-video md:aspect-auto md:h-full">
                        <img src="https://qlinkon.com/storage/landing_pages/features/BHqtOGv1hMJcbBU2z8ExwaJwYT4x677MO1JhlpZy.webp"
                            alt="Project &amp; Service Subscription Tracker"
                            class="absolute inset-0 h-full w-full object-cover" loading="lazy" decoding="async" />
                    </div>
                </div>
            </div>

            <div class="reveal relative top-auto z-10 mb-10 w-full md:sticky md:[top:var(--card-top)] md:[z-index:var(--card-z)] md:mb-[15vh]"
                style="--card-top: 360px; --card-z: 90">
                <!-- Card Container -->
                <div
                    class="grid min-h-[400px] overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-md transition-shadow duration-300 hover:shadow-xl md:grid-cols-2">
                    <!-- Text Section -->
                    <div class="order-last flex flex-col justify-center p-8 sm:p-10 md:order-first md:p-12 lg:p-14">
                        <h3
                            class="mb-3 text-2xl font-bold tracking-tight text-gray-900 sm:mb-4 sm:text-3xl md:text-4xl">
                            Multi-Store &amp; Role-Based Access
                        </h3>
                        <p class="mb-8 text-[16px] leading-relaxed text-gray-500 md:text-lg">Run 2, 3 or more nursery
                            branches from a single login.</p>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">Separate sales, inventory &amp; reports per branch</span>
                            </li>
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">Each staff gets login with defined permissions only</span>
                            </li>
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">Owner gets a unified dashboard across all stores at
                                    once</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Image Section: Mobile aspect ratio fix -->
                    <div
                        class="relative order-first aspect-[4/3] w-full bg-gradient-to-br from-cyan-200 via-sky-100 to-blue-200 sm:aspect-video md:order-last md:aspect-auto md:h-full">
                        <img src="https://qlinkon.com/storage/landing_pages/features/Cfy6CCySdUgXjmkzlRm36pXVqVIwkP6M95QTrsDC.webp"
                            alt="Multi-Store &amp; Role-Based Access"
                            class="absolute inset-0 h-full w-full object-cover" loading="lazy" decoding="async" />
                    </div>
                </div>
            </div>

            <div class="reveal relative top-auto z-10 mb-4 w-full md:sticky md:[top:var(--card-top)] md:[z-index:var(--card-z)] md:pb-20"
                style="--card-top: 390px; --card-z: 100">
                <!-- Card Container -->
                <div
                    class="grid min-h-[400px] overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-md transition-shadow duration-300 hover:shadow-xl md:grid-cols-2">
                    <!-- Text Section -->
                    <div class="order-last flex flex-col justify-center p-8 sm:p-10 md:p-12 lg:p-14">
                        <h3
                            class="mb-3 text-2xl font-bold tracking-tight text-gray-900 sm:mb-4 sm:text-3xl md:text-4xl">
                            Reports &amp; Business Insights
                        </h3>
                        <p class="mb-8 text-[16px] leading-relaxed text-gray-500 md:text-lg">Make smarter decisions
                            with data — not guesswork.</p>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">Sales, inventory, expense &amp; project reports</span>
                            </li>
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">Best-selling &amp; slow-moving product analysis</span>
                            </li>
                            <li class="flex items-start gap-3 text-[15px] font-medium text-gray-700 md:text-base">
                                <svg class="text-ink mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="leading-snug">All reports Share with team or accountant easily</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Image Section: Mobile aspect ratio fix -->
                    <div
                        class="relative order-first aspect-[4/3] w-full bg-gradient-to-br from-sky-300 via-blue-200 to-cyan-100 sm:aspect-video md:aspect-auto md:h-full">
                        <img src="https://qlinkon.com/storage/landing_pages/features/yi7Sk0tifyw4DHG0M7RePC73nYdfiZzgpk845ZqA.webp"
                            alt="Reports &amp; Business Insights" class="absolute inset-0 h-full w-full object-cover"
                            loading="lazy" decoding="async" />
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="fade-in-up mx-auto mb-24 max-w-7xl px-4 py-12 delay-300 sm:px-6 lg:px-8">
        <div class="mb-32 rounded-[2rem] border border-gray-100 bg-white p-10 shadow-sm md:p-16">
            <div
                class="grid grid-cols-1 gap-8 divide-y divide-gray-100 sm:grid-cols-2 sm:divide-y-0 md:grid-cols-3 md:gap-0 md:divide-x">
                <div class="flex flex-col justify-center md:px-12">
                    <h3 class="mb-3 text-5xl font-bold tracking-tight text-gray-900">150+</h3>
                    <h4 class="mb-5 text-xl font-medium text-gray-900">Nurseries</h4>
                    <p class="text-sm leading-relaxed text-gray-500">Trusted Across India</p>
                </div>
                <div class="flex flex-col justify-center md:px-12">
                    <h3 class="mb-3 text-5xl font-bold tracking-tight text-gray-900">20+</h3>
                    <h4 class="mb-5 text-xl font-medium text-gray-900">Software Features</h4>
                    <p class="text-sm leading-relaxed text-gray-500">Built for Nurseries</p>
                </div>
                <div class="flex flex-col justify-center md:px-12">
                    <h3 class="mb-3 text-5xl font-bold tracking-tight text-gray-900">3+</h3>
                    <h4 class="mb-5 text-xl font-medium text-gray-900">Years</h4>
                    <p class="text-sm leading-relaxed text-gray-500">Serving Indian Nursery Owners</p>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-7xl">
            <h2
                class="mb-12 text-center text-3xl font-bold tracking-tight text-gray-900 sm:mb-16 sm:text-4xl md:mb-20 md:text-5xl">
                Why Plantiq?
            </h2>

            <div class="grid grid-cols-1 gap-8 divide-gray-200 sm:grid-cols-2 lg:grid-cols-4 lg:gap-0 lg:divide-x">
                <div
                    class="flex flex-col px-0 py-6 sm:px-6 sm:py-8 sm:pl-0 md:min-h-[320px] md:justify-between md:px-8">
                    <h3 class="text-lg leading-snug font-medium text-gray-900 sm:text-xl">100% Mobile Friendly</h3>
                    <p class="mt-2 text-[13px] leading-relaxed text-gray-500 sm:mt-3 md:mt-auto">Run your entire
                        business from your smartphone. No computer or technical knowledge needed.</p>
                </div>
                <div class="flex flex-col px-0 py-6 sm:px-6 sm:py-8 md:min-h-[320px] md:justify-between md:px-8">
                    <h3 class="text-lg leading-snug font-medium text-gray-900 sm:text-xl">Smart QR Plant Profile</h3>
                    <p class="mt-2 text-[13px] leading-relaxed text-gray-500 sm:mt-3 md:mt-auto">Customers scan the QR
                        code to see photos, videos, and hear the plant tell its own story and care tips in any local
                        language.</p>
                </div>
                <div class="flex flex-col px-0 py-6 sm:px-6 sm:py-8 md:min-h-[320px] md:justify-between md:px-8">
                    <h3 class="text-lg leading-snug font-medium text-gray-900 sm:text-xl">Save Manpower Costs</h3>
                    <p class="mt-2 text-[13px] leading-relaxed text-gray-500 sm:mt-3 md:mt-auto">Eliminate the need for
                        dedicated guides. Let AI educate your customers and capture leads automatically.</p>
                </div>
                <div
                    class="flex flex-col px-0 py-6 sm:px-6 sm:py-8 sm:pr-0 md:min-h-[320px] md:justify-between md:px-8">
                    <h3 class="text-lg leading-snug font-medium text-gray-900 sm:text-xl">Automated Operations</h3>
                    <p class="mt-2 text-[13px] leading-relaxed text-gray-500 sm:mt-3 md:mt-auto">Track multi-warehouse
                        inventory, customer ledgers, sales leads, and employee attendance automatically.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="reveal relative w-full overflow-hidden bg-gray-400">
        <div
            class="relative z-10 flex flex-col items-center justify-center px-4 py-16 text-center sm:px-6 sm:py-20 lg:px-8 lg:py-24">
            <h2
                class="mx-auto mb-4 max-w-3xl text-center text-3xl leading-tight font-bold tracking-tight text-white sm:text-4xl md:text-5xl">
                Let's Convert your Nursery in Digital And Smart Today!
            </h2>
            <p class="mx-auto mb-4 max-w-xl text-lg leading-relaxed text-white/80 md:text-xl">Digital India Ki Smart
                Nursery</p>
            <button type="button" x-on:click="$store.ui.inquiryModal = true"
                class="inline-block cursor-pointer rounded-xl bg-black px-10 py-4 text-base font-semibold text-white shadow-xl shadow-black/40 transition-all hover:-translate-y-1 hover:bg-gray-900">
                Make an Inquiry
            </button>
        </div>
    </section>

    <footer class="fade-in-up mx-auto mt-12 max-w-6xl border-t border-gray-200/60 px-6 py-16 delay-300">
        <div class="mb-16 grid grid-cols-1 items-start gap-10 sm:grid-cols-2 md:grid-cols-5 md:gap-8">
            <div class="flex items-center md:items-start md:pt-1">
                <a href="https://qlinkon.com/quiz/business-growth-test"
                    class="flex h-36 w-36 shrink-0 items-center justify-center rounded-full bg-gray-900 text-center text-white shadow-lg transition-all duration-300 hover:scale-105 hover:bg-black">
                    <span class="px-4 text-sm leading-tight font-bold tracking-wide uppercase">
                        START<br />YOUR<br />BUSINESS<br />TEST
                    </span>
                </a>
            </div>

            <div>
                <h4 class="mb-6 font-bold text-gray-900">Useful links</h4>
                <ul class="space-y-4 text-sm font-medium text-gray-500">
                    <li><a href="<?php echo e(url('/about')); ?>" class="transition-colors hover:text-gray-900">About Us</a></li>
                    <li>
                        <a href="https://qlinkon.com/careers"
                            class="transition-colors hover:text-gray-900">Careers</a>
                    </li>
                    <li><a href="https://qlinkon.com/blog" class="transition-colors hover:text-gray-900">Blog</a></li>
                    <li><a href="<?php echo e(url('/contact')); ?>" class="transition-colors hover:text-gray-900">Contact</a>
                    </li>
                    <li>
                        <a href="https://qlinkon.com/downloads"
                            class="transition-colors hover:text-gray-900">Downloads</a>
                    </li>
                </ul>
            </div>

            <div>
                <h4 class="mb-6 font-bold text-gray-900">Legal</h4>
                <ul class="space-y-4 text-sm font-medium text-gray-500">
                    <li>
                        <a href="<?php echo e(url('https://qlinkon.com/terms')); ?>"
                            class="hover:text-brand-600 text-sm text-gray-500 transition-colors">Terms of Service</a>
                    </li>
                    <li>
                        <a href="<?php echo e(url('https://qlinkon.com/privacy')); ?>"
                            class="hover:text-brand-600 text-sm text-gray-500 transition-colors">Privacy Policy</a>
                    </li>
                </ul>
            </div>

            <div x-data="{
                sysSettings: null,
                fetchSettings() {
                    // Fetch system settings API
                    fetch('https://qlinkon.com/api/system-settings')
                        .then((res) => res.json())
                        .then((data) => {
                            if (data.success) {
                                this.sysSettings = data.data;
                            }
                        })
                        .catch((err) => console.error('Error fetching system settings:', err));
                },
            }" x-init="fetchSettings()">
                <h4 class="mb-6 font-bold text-gray-900" x-show="sysSettings">Stay connected</h4>

                <div class="flex flex-wrap gap-3 md:gap-4" x-show="sysSettings" style="display: none" x-transition>
                    <template x-if="sysSettings?.social_whatsapp">
                        <a :href="sysSettings.social_whatsapp" target="_blank" rel="noopener"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-900 text-white shadow-md transition-all hover:scale-110 hover:bg-gray-900">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z">
                                </path>
                            </svg>
                        </a>
                    </template>

                    <template x-if="sysSettings?.social_instagram">
                        <a :href="sysSettings.social_instagram" target="_blank" rel="noopener"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-900 text-white shadow-md transition-all hover:scale-110 hover:bg-gray-900">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z">
                                </path>
                            </svg>
                        </a>
                    </template>

                    <template x-if="sysSettings?.social_facebook">
                        <a :href="sysSettings.social_facebook" target="_blank" rel="noopener"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-900 text-white shadow-md transition-all hover:scale-110 hover:bg-gray-900">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M22 12C22 6.477 17.523 2 12 2S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.99 22 12z">
                                </path>
                            </svg>
                        </a>
                    </template>

                    <template x-if="sysSettings?.social_youtube">
                        <a :href="sysSettings.social_youtube" target="_blank" rel="noopener"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-900 text-white shadow-md transition-all hover:scale-110 hover:bg-gray-900">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M21.582 6.186a2.665 2.665 0 0 0-1.874-1.889C18.053 3.8 12 3.8 12 3.8s-6.053 0-7.708.497a2.665 2.665 0 0 0-1.874 1.889C2 7.854 2 12 2 12s0 4.146.418 5.814a2.665 2.665 0 0 0 1.874 1.889C6.053 20.2 12 20.2 12 20.2s6.053 0 7.708-.497a2.665 2.665 0 0 0 1.874-1.889C22 16.146 22 12 22 12s0-4.146-.418-5.814zM9.993 15.545V8.455L15.992 12l-5.999 3.545z">
                                </path>
                            </svg>
                        </a>
                    </template>
                </div>
            </div>
        </div>

        <div class="mt-8 flex flex-col items-start gap-4 pt-4">
            <a href="https://qlinkon.com">
                <img src="https://qlinkon.com/assets/logo.svg" alt="Qlinkon"
                    class="mb-1 h-10 w-auto object-contain md:h-[2.3rem]" />
            </a>
            <p class="text-ink text-[15px] font-semibold tracking-wide uppercase md:text-base">WE LOVE YOUR GROWTH</p>
            <p class="mt-1 text-sm font-medium text-gray-400">© 2026 Qlinkon Technology. All rights reserved.</p>
        </div>
    </footer>

    <script>
        let currentSlide = 1;
        let slideInterval;

        function startSlider() {
            slideInterval = setInterval(() => {
                currentSlide = currentSlide >= 3 ? 1 : currentSlide + 1;
                switchSlide(currentSlide, false);
            }, 3500);
        }

        function switchSlide(targetId, isManual = false) {
            if (isManual) {
                clearInterval(slideInterval);
                currentSlide = targetId;
                startSlider();
            }

            // 1. Reset all Tabs to inactive replica style
            document.querySelectorAll(".tab-btn").forEach((btn) => {
                btn.classList.remove("text-gray-900", "font-semibold");
                btn.classList.add("text-gray-500", "font-medium");
                btn.querySelector(".check-icon").classList.add("hidden");
                btn.querySelector(".dot").classList.remove("hidden");
            });

            // 2. Set Active Tab to black verified badge style
            const activeBtn = document.getElementById(`tab-${targetId}`);
            if (activeBtn) {
                activeBtn.classList.remove("text-gray-500", "font-medium");
                activeBtn.classList.add("text-gray-900", "font-semibold");
                activeBtn.querySelector(".check-icon").classList.remove("hidden");
                activeBtn.querySelector(".dot").classList.add("hidden");
            }

            // 3. Hide all Slides
            document.querySelectorAll(".slide-content").forEach((slide) => {
                slide.classList.remove("opacity-100", "z-10", "relative");
                slide.classList.add("opacity-0", "pointer-events-none", "z-0", "absolute");
            });

            // 4. Show Target Slide
            const activeSlide = document.getElementById(`slide-${targetId}`);
            if (activeSlide) {
                activeSlide.classList.remove("opacity-0", "pointer-events-none", "z-0", "absolute");
                activeSlide.classList.add("opacity-100", "z-10", "relative");
            }
        }

        document.addEventListener("DOMContentLoaded", () => {
            switchSlide(1, false);
            startSlider();

            // Scroll reveal
            const revealObserver = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add("is-visible");
                            revealObserver.unobserve(entry.target);
                        }
                    });
                }, {
                    threshold: 0.12,
                    rootMargin: "0px 0px -40px 0px"
                },
            );
            document.querySelectorAll(".reveal").forEach((el) => revealObserver.observe(el));
        });
    </script>

    
    <?php echo $__env->make('partials.inquiry-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div id="videoModal"
        class="fixed inset-0 z-[100] flex hidden items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
        style="display: none">
        <div class="absolute inset-0" onclick="closeVideoModal()"></div>

        <div class="relative z-10 aspect-video w-full max-w-4xl overflow-hidden rounded-2xl bg-black shadow-2xl">
            <button type="button" onclick="closeVideoModal()"
                class="absolute top-0 right-0 z-50 flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-black/40 text-white transition-colors hover:bg-black/60 focus:outline-none">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <iframe id="modalIframe" class="h-full w-full" src="" frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen></iframe>
        </div>
    </div>

    <script>
        function openVideoModal() {
            const staticVideoId = "tXbO927EW8k";

            const modal = document.getElementById("videoModal");
            const iframe = document.getElementById("modalIframe");
            if (modal && iframe) {
                iframe.src = `https://www.youtube.com/embed/${staticVideoId}?autoplay=1&mute=0&rel=0`;
                modal.classList.remove("hidden");
                modal.style.display = "flex";
                document.body.style.overflow = "hidden"; // Stop background scroll
            }
        }

        function closeVideoModal() {
            const modal = document.getElementById("videoModal");
            const iframe = document.getElementById("modalIframe");
            if (modal && iframe) {
                iframe.src = ""; // Kill player source to freeze audio instantly
                modal.classList.add("hidden");
                modal.style.display = "none";
                document.body.style.overflow = ""; // Unlock background scroll
            }
        }
    </script>

    <script>
        document.addEventListener("alpine:init", () => {
            /*
             * Guard: welcome.blade.php also registers Alpine.store('ui').
             * The flag ensures it only happens once per page regardless of
             * how many times the component is included.
             */
            if (!window.__qlUiStoreInit) {
                window.__qlUiStoreInit = true;
                Alpine.store("ui", {
                    mobileNav: false,
                    inquiryModal: false,
                    init() {
                        Alpine.effect(() => {
                            document.body.style.overflow = this.mobileNav || this.inquiryModal ?
                                "hidden" : "";
                        });
                    },
                });
            }
        });
    </script>
</body>

</html>
<?php /**PATH C:\Users\qlinkongraphics\Desktop\MyLab\plantiq-local\resources\views/welcome.blade.php ENDPATH**/ ?>