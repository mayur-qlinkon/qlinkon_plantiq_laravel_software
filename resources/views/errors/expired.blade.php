<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Subscription Expired | Plantiq — Qlinkon Technology</title>
    <meta
        name="description"
        content="Your subscription has expired. Please renew your plan to continue accessing your nursery automation dashboard."
    />
    <meta name="robots" content="noindex, follow" />

    <script src="{{ asset('assets/js/tailwind.min.js') }}"></script>

    <script defer src="{{ asset('assets/js/alpinejs.min.js') }}"></script>

    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    />
    <link
        rel="icon"
        type="image/png"
        href="{{ get_setting('favicon') ? asset('storage/' . get_setting('favicon')) : asset('assets/icons/favicon.png') }}"
    />
    <style>
        body {
            font-family: "Plus Jakarta Sans", sans-serif;
            background-color: #f7f7f5;
        }
        /* Fade-in animation for smooth page presentation */
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
        .fade-in-up {
            animation: fadeInUp 0.7s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>
<body class="overflow-x-hidden text-gray-900 antialiased">
    <nav
        class="fade-in-up relative top-0 z-50 mx-auto flex max-w-7xl items-center justify-center border-b border-gray-200/40 bg-[#F7F7F5]/90 px-4 py-5 backdrop-blur-md sm:px-6 sm:py-6"
    >
        <a href="https://qlinkon.com" class="flex items-center justify-center">
            <img
                src="https://qlinkon.com/storage/landing_pages/logos/yCxXgXAF70U6hZF7i7oyGNF2uQUT4pxD0ljqyAkH.webp"
                alt="Plantiq"
                class="h-10 w-auto max-w-[160px] object-contain sm:h-12 sm:max-w-[200px] md:h-14"
            />
        </a>
    </nav>

    <main
        class="mx-auto flex max-w-7xl flex-grow flex-col items-center px-4 pt-12 pb-12 text-center sm:px-6 sm:pt-16 lg:px-8"
        x-data='renewal(@json($currentPlan?->price ?? 0), @json($currentPlan?->name ?? "Current Plan"))'
    >
        <div class="fade-in-up w-full">
            <h1 class="mb-4 text-4xl leading-tight font-bold tracking-tight text-gray-900 sm:text-5xl md:text-6xl">
                Your Subscription<br />Has Expired
            </h1>
            <p class="mx-auto mb-8 max-w-2xl text-base leading-relaxed text-gray-500 sm:text-lg">To continue managing your nursery and access your work, please renew your plan.</p>
        </div>

        <div class="fade-in-up mb-12 w-full">
            <button
                @click="showModal = true"
                class="inline-flex w-auto items-center justify-center gap-2.5 rounded-xl bg-[#00aeef] px-12 py-3 text-base font-semibold text-white shadow-md shadow-[#00aeef]/20 transition-all duration-200 hover:bg-[#009bdf] active:scale-[0.99] sm:px-14 sm:py-4 sm:text-lg sm:shadow-lg"
            >
                <span>Renew Now</span>
            </button>
        </div>

        {{-- 🌟 Renewal modal — shows amount + promo code BEFORE Cashfree opens --}}
        <div
            x-show="showModal"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div
                @click.outside="!isProcessing && (showModal = false)"
                class="w-full max-w-md overflow-hidden rounded-2xl bg-white text-left shadow-2xl"
            >
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5">
                    <h3 class="text-lg font-bold text-gray-900">Renew Your Plan</h3>
                    <button
                        @click="!isProcessing && (showModal = false)"
                        class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-5 px-6 py-6">
                    <div class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3.5">
                        <div>
                            <p
                                class="text-xs font-semibold tracking-wide text-gray-400 uppercase"
                                x-text="planName"
                            ></p>
                            <p class="mt-0.5 text-sm text-gray-500">Renewal amount</p>
                        </div>
                        <p class="text-xl font-bold text-gray-900" x-text="'₹' + planAmount.toFixed(2)"></p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-bold tracking-wide text-gray-500 uppercase"
                            >Promo Code</label
                        >
                        <div class="flex gap-2">
                            <input
                                type="text"
                                x-model="couponCode"
                                @input="couponError = ''"
                                :disabled="couponApplied"
                                placeholder="Enter code"
                                class="w-full rounded-lg border border-gray-200 bg-white px-3.5 py-2.5 text-sm font-semibold tracking-wide text-gray-800 uppercase transition-colors outline-none placeholder:font-normal placeholder:text-gray-400 placeholder:normal-case focus:border-[#00aeef] focus:ring-2 focus:ring-[#00aeef]/20 disabled:bg-gray-50 disabled:text-gray-400"
                            />
                            <button
                                x-show="!couponApplied"
                                @click="applyCoupon()"
                                :disabled="isApplyingCoupon || !couponCode.trim()"
                                class="shrink-0 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-black disabled:opacity-40"
                            >
                                <span x-show="!isApplyingCoupon">Apply</span>
                                <span x-show="isApplyingCoupon">...</span>
                            </button>
                            <button
                                x-show="couponApplied"
                                @click="removeCoupon()"
                                class="shrink-0 rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-bold text-gray-500 transition-colors hover:bg-gray-50"
                            >
                                Remove
                            </button>
                        </div>
                        <p
                            x-show="couponError"
                            x-text="couponError"
                            class="mt-1.5 text-xs font-medium text-red-500"
                        ></p>
                        <p
                            x-show="couponApplied"
                            class="mt-1.5 flex items-center gap-1 text-xs font-semibold text-emerald-600"
                        >
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                            Coupon applied — you saved ₹<span x-text="couponDiscount.toFixed(2)"></span>
                        </p>
                    </div>

                    <div class="flex items-center justify-between border-t border-gray-100 pt-4">
                        <span class="text-sm font-semibold text-gray-500">Payable Amount</span>
                        <span
                            class="text-2xl font-extrabold text-gray-900"
                            x-text="'₹' + payableAmount.toFixed(2)"
                        ></span>
                    </div>
                </div>

                <div class="border-t border-gray-100 px-6 py-5">
                    <button
                        @click="renew()"
                        :disabled="isProcessing"
                        class="flex w-full items-center justify-center gap-2.5 rounded-xl bg-[#00aeef] px-6 py-3.5 text-base font-semibold text-white shadow-md shadow-[#00aeef]/20 transition-all duration-200 hover:bg-[#009bdf] active:scale-[0.99] disabled:opacity-60"
                    >
                        <template x-if="!isProcessing">
                            <span x-text="payableAmount <= 0 ? 'Activate Renewal' : 'Proceed to Payment'"></span>
                        </template>
                        <template x-if="isProcessing">
                            <span class="inline-flex items-center gap-2.5">
                                <svg class="h-5 w-5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processing...
                            </span>
                        </template>
                    </button>
                </div>
            </div>
        </div>

        <div
            class="fade-in-up group mx-auto w-full max-w-5xl overflow-hidden rounded-[20px] shadow-[0_12px_30px_-6px_rgba(0,0,0,0.08)] sm:rounded-[32px] md:rounded-[40px]"
        >
            <img
                src="https://qlinkon.com/storage/landing_pages/showcase_tab_3_images/9yJ4Yy9YtVAq7gH7OAsnFJsCERgeke2Na74aj96k.webp"
                alt="Leading Solutions for Business Growth"
                class="block h-auto w-full object-cover transition-transform duration-500 group-hover:scale-[1.005]"
                onerror="
                    this.style.display = 'none';
                    this.nextElementSibling.style.display = 'flex';
                "
            />

            <div
                class="hidden flex-col items-center justify-center border border-gray-100 bg-white px-6 py-24 text-gray-400"
            >
                <svg class="mb-3 h-12 w-12 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"></path>
                </svg>
                <p class="text-sm font-semibold">Showcase Banner Image</p>
            </div>
        </div>

        <div class="fade-in-up mt-16 w-full sm:mt-24">
            <h2
                class="text-4xl leading-[1.15] font-extrabold tracking-tight text-gray-900 uppercase sm:text-5xl md:text-6xl"
            >
                WE LOVE YOUR<br />GROWTH
            </h2>
        </div>

        <div
            class="fade-in-up mt-12 mb-8 w-full max-w-xl rounded-2xl border border-gray-100 bg-white px-6 py-8 shadow-sm"
        >
            <p class="mb-3 text-sm font-semibold tracking-wide text-gray-400 uppercase">Need instant assistance? Contact Support</p>
            <a
                href="tel:9925180106"
                class="mb-1 block text-2xl font-bold tracking-wide text-gray-900 transition-colors duration-200 hover:text-[#00aeef] sm:text-3xl"
            >
                99251 80106
            </a>
            <a
                href="mailto:hi@qlinkon.com"
                class="block text-base font-medium text-gray-600 transition-colors duration-200 hover:text-[#00aeef] sm:text-lg"
            >
                hi@qlinkon.com
            </a>
        </div>
    </main>

    <footer class="fade-in-up mx-auto max-w-7xl border-t border-gray-200/60 bg-[#F7F7F5] px-6 py-16">
        <div class="mb-16 grid grid-cols-1 items-start gap-10 sm:grid-cols-2 md:grid-cols-5 md:gap-8">
            <div class="flex items-center md:items-start md:pt-1">
                <a
                    href="https://qlinkon.com/quiz/business-growth-test"
                    class="flex h-36 w-36 shrink-0 items-center justify-center rounded-full bg-gray-900 text-center text-white shadow-lg transition-all duration-300 hover:scale-105 hover:bg-black"
                >
                    <span class="px-4 text-sm leading-tight font-bold tracking-wide uppercase">
                        START<br />YOUR<br />BUSINESS<br />TEST
                    </span>
                </a>
            </div>

            <div>
                <h4 class="mb-6 font-bold text-gray-900">Useful links</h4>
                <ul class="space-y-4 text-sm font-medium text-gray-500">
                    <li>
                        <a href="https://qlinkon.com/about" class="transition-colors duration-155 hover:text-gray-900"
                            >About Us</a
                        >
                    </li>
                    <li>
                        <a href="https://qlinkon.com/careers" class="transition-colors duration-155 hover:text-gray-900"
                            >Careers</a
                        >
                    </li>
                    <li>
                        <a href="https://qlinkon.com/blog" class="transition-colors duration-155 hover:text-gray-900"
                            >Blog</a
                        >
                    </li>
                    <li>
                        <a href="https://qlinkon.com/contact" class="transition-colors duration-155 hover:text-gray-900"
                            >Contact</a
                        >
                    </li>
                    <li>
                        <a
                            href="https://qlinkon.com/downloads"
                            class="transition-colors duration-155 hover:text-gray-900"
                            >Downloads</a
                        >
                    </li>
                </ul>
            </div>

            <div>
                <h4 class="mb-6 font-bold text-gray-900">Legal</h4>
                <ul class="space-y-4 text-sm font-medium text-gray-500">
                    <li>
                        <a href="https://qlinkon.com/privacy" class="transition-colors duration-155 hover:text-gray-900"
                            >Privacy Policy</a
                        >
                    </li>
                    <li>
                        <a href="https://qlinkon.com/terms" class="transition-colors duration-155 hover:text-gray-900"
                            >Terms of Service</a
                        >
                    </li>
                </ul>
            </div>

            <div class="sm:col-span-2">
                <h4 class="mb-6 font-bold text-gray-900">Stay connected</h4>
                <div class="flex flex-wrap gap-3 md:gap-4">
                    <a
                        href="https://wa.me/919925180106"
                        target="_blank"
                        rel="noopener"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-900 text-white shadow-md transition-all hover:scale-110 hover:bg-[#25D366]"
                    >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"></path>
                        </svg>
                    </a>

                    <a
                        href="https://www.instagram.com/qlinkontech/"
                        target="_blank"
                        rel="noopener"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-900 text-white shadow-md transition-all hover:scale-110 hover:bg-[#E1306C]"
                    >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"></path>
                        </svg>
                    </a>

                    <a
                        href="https://www.facebook.com/qlinkontech/"
                        target="_blank"
                        rel="noopener"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-900 text-white shadow-md transition-all hover:scale-110 hover:bg-[#1877F2]"
                    >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path d="M22 12C22 6.477 17.523 2 12 2S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.99 22 12z"></path>
                        </svg>
                    </a>

                    <a
                        href="https://www.youtube.com/@qlinkontec"
                        target="_blank"
                        rel="noopener"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-900 text-white shadow-md transition-all hover:scale-110 hover:bg-[#FF0000]"
                    >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21.582 6.186a2.665 2.665 0 0 0-1.874-1.889C18.053 3.8 12 3.8 12 3.8s-6.053 0-7.708.497a2.665 2.665 0 0 0-1.874 1.889C2 7.854 2 12 2 12s0 4.146.418 5.814a2.665 2.665 0 0 0 1.874 1.889C6.053 20.2 12 20.2 12 20.2s6.053 0 7.708-.497a2.665 2.665 0 0 0 1.874-1.889C22 16.146 22 12 22 12s0-4.146-.418-5.814zM9.993 15.545V8.455L15.992 12l-5.999 3.545z"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-8 flex flex-col items-start gap-4 pt-4">
            <a href="https://qlinkon.com">
                <img
                    src="https://qlinkon.com/assets/logo.svg"
                    alt="Qlinkon"
                    class="mb-1 h-10 w-auto object-contain md:h-[2.3rem]"
                />
            </a>
            <p class="text-[15px] font-semibold tracking-wide text-gray-900 uppercase md:text-base">WE LOVE YOUR GROWTH</p>
            <p class="mt-1 text-sm font-medium text-gray-400">© 2026 Qlinkon Technology. All rights reserved.</p>
        </div>
    </footer>

    <script>
        /**
         * Cashfree Drop.js Renewal Flow
         *
         * 1. POST /subscription/renew/init  → get payment_session_id from server
         * 2. Open Cashfree Drop.js checkout
         * 3. On success callback → POST /subscription/renew/confirm with cf_order_id
         * 4. Server fetches status from Cashfree API → renews subscription
         *
         * NEVER pass cf_order_id as "proof of payment" to confirm.
         * The server independently fetches order status from Cashfree.
         */
        function renewal(planAmount, planName) {
            let amount = parseFloat(planAmount) || 0;
            return {
                isProcessing: false,
                showModal: false,

                // Promo code state
                couponCode: "",
                couponApplied: false,
                couponDiscount: 0,
                couponError: "",
                isApplyingCoupon: false,

                planAmount: amount,
                planName: planName,
                payableAmount: amount,

                async applyCoupon() {
                    const code = this.couponCode.trim();
                    if (!code) return;

                    this.isApplyingCoupon = true;
                    this.couponError = "";
                    const csrf = document.querySelector('meta[name="csrf-token"]').content;

                    try {
                        const res = await fetch("{{ route('subscription.renew.apply-coupon') }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": csrf,
                                Accept: "application/json",
                            },
                            body: JSON.stringify({ coupon: code }),
                        });

                        const data = await res.json();

                        if (!res.ok || !data.valid) {
                            this.couponError = data.error || "Invalid promo code.";
                            return;
                        }

                        this.couponApplied = true;
                        this.couponDiscount = data.discount;
                        this.payableAmount = data.payable_amount;
                    } catch (e) {
                        console.error("[Coupon] Apply failed:", e);
                        this.couponError = "Could not validate code. Please try again.";
                    } finally {
                        this.isApplyingCoupon = false;
                    }
                },

                removeCoupon() {
                    this.couponCode = "";
                    this.couponApplied = false;
                    this.couponDiscount = 0;
                    this.couponError = "";
                    this.payableAmount = this.planAmount;
                },

                async renew() {
                    this.isProcessing = true;
                    const csrf = document.querySelector('meta[name="csrf-token"]').content;

                    try {
                        // -------------------------------------------------------
                        // Step 1: Create Cashfree order on the server
                        // Returns: { payment_session_id, cf_order_id, amount, plan }
                        //      or: { free: true, redirect } when a coupon covers
                        //          the full renewal amount — no gateway needed
                        // -------------------------------------------------------
                        const initRes = await fetch("{{ route('subscription.renew.init') }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": csrf,
                                Accept: "application/json",
                            },
                            body: JSON.stringify({
                                coupon: this.couponApplied ? this.couponCode.trim() : null,
                            }),
                        });

                        const initData = await initRes.json();

                        if (!initRes.ok) {
                            alert(initData.error || "Could not start renewal. Please contact support.");
                            this.isProcessing = false;
                            return;
                        }

                        // Fully covered by the coupon — server already renewed it.
                        if (initData.free) {
                            window.location.href = initData.redirect;
                            return;
                        }

                        const { payment_session_id, cf_order_id } = initData;

                        // -------------------------------------------------------
                        // Step 2: Initialize Cashfree Drop.js SDK
                        // Mode: "sandbox" or "production" — passed from controller
                        // -------------------------------------------------------
                        const cashfree = Cashfree({
                            mode: @json ($cashfreeEnv), // "sandbox" or "production"
                        });

                        // -------------------------------------------------------
                        // Step 3: Open Cashfree checkout
                        // -------------------------------------------------------
                        const checkoutOptions = {
                            paymentSessionId: payment_session_id,
                            redirectTarget: "_modal", // Opens as modal popup (like Razorpay)
                        };

                        const result = await cashfree.checkout(checkoutOptions);

                        // result.error  → payment failed / dismissed
                        // result.redirect → Cashfree redirected (shouldn't happen in _modal mode)
                        // result.paymentDetails → success (but DO NOT trust this alone)

                        if (result.error) {
                            // User dismissed or payment failed on Cashfree checkout
                            console.warn("[Cashfree] Checkout error:", result.error);
                            alert(result.error.message || "Payment was not completed.");
                            this.isProcessing = false;
                            return;
                        }

                        // -------------------------------------------------------
                        // Step 4: Confirm payment on the server
                        // Server fetches status from Cashfree API independently
                        // NEVER trust result.paymentDetails as proof of payment
                        // -------------------------------------------------------
                        const confirmRes = await fetch("{{ route('subscription.renew.confirm') }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": csrf,
                                Accept: "application/json",
                            },
                            body: JSON.stringify({ cf_order_id }),
                        });

                        const confirmData = await confirmRes.json();

                        if (confirmRes.ok && confirmData.success) {
                            window.location.href = confirmData.redirect;
                        } else {
                            alert(
                                confirmData.error ||
                                    "Payment verification failed. If money was deducted, please contact support.",
                            );
                            this.isProcessing = false;
                        }
                    } catch (e) {
                        console.error("[Cashfree] Unexpected error:", e);
                        alert("Something went wrong. Please try again or contact support.");
                        this.isProcessing = false;
                    }
                },
            };
        }
    </script>
</body>
</html>
