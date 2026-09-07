@extends('layouts.storefront')

@section('title', storefront_setting('seo_title', 'Home'))

@section('meta')
    {{-- seo_title/seo_description live on the store; keywords and og_image have
         no store column and stay company-level. --}}
    <meta name="description" content="{{ storefront_setting('seo_description', '') }}">
    <meta name="keywords" content="{{ get_setting('seo_keywords', '') }}">
    <meta name="author" content="{{ storefront_store()?->name ?? get_setting('site_name', 'Plantiq') }}">

    {{-- Open Graph --}}
    <meta property="og:title" content="@yield('title', storefront_setting('seo_title', 'Home'))">
    <meta property="og:description" content="{{ storefront_setting('seo_description', '') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image"
        content="{{ get_setting('og_image') ? asset('storage/' . get_setting('og_image')) : asset('assets/images/og-default.jpg') }}">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', storefront_setting('seo_title', 'Home'))">
    <meta name="twitter:description" content="{{ storefront_setting('seo_description', '') }}">
    <meta name="twitter:image"
        content="{{ get_setting('og_image') ? asset('storage/' . get_setting('og_image')) : asset('assets/images/og-default.jpg') }}">
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <style>
        /* 🚫 Force hide scrollbar for a clean mobile look */
        .hide-scrollbar::-webkit-scrollbar {
            display: none !important;
        }

        .hide-scrollbar {
            -ms-overflow-style: none !important;
            scrollbar-width: none !important;
        }

        /* Swiper Customization specific to this page */
        .hero-swiper .swiper-button-next,
        .hero-swiper .swiper-button-prev {
            background-color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: #6b7280;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        .hero-swiper .swiper-button-next:after,
        .hero-swiper .swiper-button-prev:after {
            font-size: 16px;
            font-weight: bold;
        }

        .hero-swiper .swiper-button-next {
            right: 20px;
        }

        .hero-swiper .swiper-button-prev {
            left: 20px;
        }

        .hero-swiper .swiper-pagination-bullet {
            background-color: #d1d5db;
            opacity: 1;
            width: 18px;
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s;
        }

        .hero-swiper .swiper-pagination-bullet-active {
            background-color: #4b5563;
            width: 24px;
        }

        /* 1. Added: Responsive Banner Content & Gradients */
        .banner-content-wrapper {
            position: absolute;
            inset: 0;
            z-index: 20;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding-left: 6%;
            padding-right: 45%;
            pointer-events: none;
            /* Let clicks pass through to the slide */
            /* background: linear-gradient(90deg, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.2) 40%, rgba(0,0,0,0) 100%); */
        }

        @media (max-width: 640px) {
            .banner-content-wrapper {
                padding: 1.25rem;
                padding-right: 1.25rem;
                justify-content: flex-end;
                /* Text sits at the bottom on mobile */
                /* background: linear-gradient(0deg, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.4) 50%, rgba(0,0,0,0) 100%); */
            }
        }

        .banner-title {
            font-size: clamp(1.1rem, 4.5vw, 2.5rem);
            font-weight: 800;
            line-height: 1.2;
            color: white;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.6);
            max-width: 100%;
        }

        .banner-subtitle {
            font-size: clamp(0.7rem, 2.8vw, 1.1rem);
            color: rgba(255, 255, 255, 0.95);
            font-weight: 500;
            text-shadow: 0 1px 6px rgba(0, 0, 0, 0.6);
            line-height: 1.3;
            max-width: 100%;
        }

        @media (max-width: 640px) {

            .banner-title,
            .banner-subtitle {
                width: 75%;
            }
        }
    </style>
@endpush

@section('content')

    {{-- 📱 MOBILE CATEGORY SLIDER: Root Alignment Fix --}}
    <div class="lg:hidden bg-white border-b border-gray-100/50 sticky top-[72px] z-30">
        {{-- 
            1. px-4: Provides the visual gap on the left.
            2. scroll-pl-4: Crucial! Tells the 'snap' engine to stop 16px before the edge.
            3. after:content-['']: A trick to ensure the right-side padding also works.
        --}}
        <div class="flex overflow-x-auto py-4 hide-scrollbar gap-2.5 snap-x px-4 scroll-pl-4 after:shrink-0 after:w-4">

            {{-- 1. "All" Pill --}}
            <a href="{{ tenant_url('') }}"
                class="snap-start flex-shrink-0 whitespace-nowrap px-5 py-2 rounded-xl text-[13px] font-bold transition-all border-2
                {{ !request()->route('categorySlug')
                    ? 'bg-slate-900 border-slate-900 text-white shadow-md'
                    : 'bg-white border-gray-100 text-gray-500 hover:border-gray-200' }}">
                All
            </a>

            {{-- 2. Dynamic Categories Loop --}}
            @foreach ($navCategories as $cat)
                @php
                    $isActive = request()->route('categorySlug') === $cat->slug;
                @endphp
                <a href="{{ tenant_url('c/' . $cat->slug) }}"
                    class="snap-start flex-shrink-0 whitespace-nowrap px-5 py-2 rounded-xl text-[13px] font-bold transition-all border-2
                    {{ $isActive
                        ? 'bg-slate-900 border-slate-900 text-white shadow-sm'
                        : 'bg-white border-gray-100 text-gray-600 hover:border-gray-200' }}">
                    {{ $cat->name }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- MAIN CONTAINER: Reduced top padding for mobile to 0 --}}
    <div
        class="flex-1 max-w-[1400px] w-full mx-auto px-4 sm:px-6 lg:px-8 pt-0 pb-6 lg:py-10 flex flex-col lg:flex-row gap-8 lg:gap-10">

        <aside class="hidden lg:block w-[220px] shrink-0">
            <div class="sticky top-[100px] bg-white border border-gray-200 p-4 rounded-2xl shadow-sm">
                <h3 class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-4">Categories</h3>
                <nav class="grid gap-2">
                    {{-- 1. "All Categories" Link --}}
                    <a href="{{ tenant_url('') }}"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-[13px] font-semibold transition-colors {{ !request()->route('categorySlug') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-brand-100' }}">
                        All Categories
                    </a>

                    {{-- 2. Dynamic Categories Loop --}}
                    @foreach ($navCategories as $cat)
                        @php
                            $isActive = request()->route('categorySlug') === $cat->slug;
                        @endphp
                        <a href="{{ tenant_url('c/' . $cat->slug) }}"
                            class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border text-[13px] transition-colors {{ $isActive ? 'bg-gray-100 text-gray-900 border-gray-200' : 'border-transparent text-gray-700 hover:bg-gray-50' }}">
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </aside>

        <div class="flex-1 min-w-0 pb-12">

            <div class="swiper hero-swiper rounded-2xl overflow-hidden mb-6 shadow-sm relative group">
                <div class="swiper-wrapper">
                    @forelse($heroBanners as $banner)
                        {{-- 2. Updated: 16/9 mobile, 21/9 desktop, slide is fully clickable --}}
                        {{-- Responsive Aspect Ratio: Taller on mobile if mobile_image exists --}}
                        <div class="swiper-slide relative bg-gray-100 w-full overflow-hidden cursor-pointer {{ !empty($banner->mobile_image) ? 'aspect-[4/5] sm:aspect-[16/9]' : 'aspect-[16/9]' }} md:aspect-[21/9]"
                            @if ($banner->link) onclick="window.open('{{ $banner->link }}', '{{ $banner->target === '_blank' ? '_blank' : '_self' }}')" @endif>

                            {{-- HTML5 Picture Element for native, zero-JS responsive image switching --}}
                            <picture class="absolute inset-0 w-full h-full block">
                                @if (!empty($banner->mobile_image))
                                    {{-- Serve mobile image on screens up to 767px (Tailwind 'md' breakpoint is 768px) --}}
                                    <source media="(max-width: 767px)"
                                        srcset="{{ asset('storage/' . $banner->mobile_image) }}">
                                @endif

                                {{-- Default/Desktop Image Fallback --}}
                                <img src="{{ asset('storage/' . $banner->image) }}"
                                    alt="{{ $banner->alt_text ?? 'Banner' }}" class="w-full h-full object-cover"
                                    loading="eager"
                                    onerror="this.onerror=null; this.src='{{ asset('assets/images/banners/default_banner.webp') }}'">
                            </picture>

                            {{-- 3. Added: Title and Subtitle Overlay with Gradient --}}
                            <div class="banner-content-wrapper">
                                @if (!empty($banner->title))
                                    <h2 class="banner-title">{!! nl2br(e($banner->title)) !!}</h2>
                                @endif
                                @if (!empty($banner->subtitle))
                                    <p class="banner-subtitle">{{ $banner->subtitle }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div
                            class="swiper-slide bg-gray-100 aspect-[4/3] md:aspect-[21/9] flex items-center justify-center">
                            <img src="{{ asset('assets/images/banners/default_banner.webp') }}" alt=""
                                class="w-full h-full object-cover" loading="eager"
                                onerror="this.onerror=null; this.src='{{ asset('assets/images/banners/default_banner.webp') }}'">
                        </div>
                    @endforelse
                </div>
                <div class="swiper-button-next opacity-0 group-hover:opacity-100 transition-opacity hidden md:flex"></div>
                <div class="swiper-button-prev opacity-0 group-hover:opacity-100 transition-opacity hidden md:flex"></div>
                <div class="swiper-pagination !-bottom-8"></div>
            </div>

            {{-- ══ APPOINTMENT BOOKING BANNER ══ --}}
            <x-appointment-banner :company="$company" />

            {{-- ══ APPOINTMENT BOOKING MODAL ══ --}}
            <x-appointment-modal :company="$company" />

            {{-- ════════════════════════════════════
                    STOREFRONT SECTIONS — fully dynamic
                ════════════════════════════════════ --}}
            @forelse($sections as $section)
                @php
                    $sectionProducts = $section->getRelation('resolved_products');
                    $cols = $section->columns ?? 4;
                @endphp

                <section class="mb-10" x-data
                    x-intersect.once="
                        fetch('{{ tenant_url('analytics/section/view') }}', {
                            method: 'POST',
                            headers: { 
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content 
                            },
                            body: JSON.stringify({ section_id: {{ $section->id }} })
                        })
                    ">

                    {{-- Section header ── --}}
                    @if ($section->show_section_title)
                        <div class="flex items-center justify-between mb-5">
                            <div>
                                <h2 class="text-lg sm:text-xl font-bold text-gray-800 tracking-tight">
                                    {{ $section->title }}
                                </h2>
                                @if ($section->subtitle)
                                    <p class="text-sm text-gray-400 font-medium mt-0.5">{{ $section->subtitle }}</p>
                                @endif
                            </div>
                            @if ($section->show_view_all && $section->resolved_view_all_url)
                                <a href="{{ $section->resolved_view_all_url }}"
                                    class="text-[13px] font-bold flex items-center gap-1 transition-colors hover:opacity-80"
                                    style="color: var(--brand-600);">
                                    View All <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                </a>
                            @endif
                        </div>
                    @endif

                    {{-- Custom HTML type ── --}}
                    @if ($section->type === 'custom_html' && $section->custom_html)
                        <div class="w-full prose prose-sm sm:prose-base max-w-none">
                            {!! $section->custom_html !!}
                        </div>
                        {{-- Banner section renders as image strip ── --}}
                        {{-- Banner section — supports grid and stacked layouts ── --}}
                    @elseif($section->type === 'banner' && $sectionProducts->isNotEmpty())
                        @php
                            $bannerCols = $sectionProducts->count() > 1 ? min($cols, $sectionProducts->count()) : 1;
                            $useGrid = $sectionProducts->count() > 1;
                            $bannerHeight = $useGrid ? '160px' : '220px';
                        @endphp

                        <div class="{{ $useGrid ? 'grid gap-3' : 'space-y-3' }}"
                            @if ($useGrid) style="grid-template-columns: repeat({{ $bannerCols }}, 1fr)" @endif>

                            @foreach ($sectionProducts as $banner)
                                @php $bannerLink = $banner->link ?? null; @endphp

                                <div class="relative rounded-2xl overflow-hidden shadow-sm group"
                                    style="height: {{ $bannerHeight }}; background: #f3f4f6;">

                                    {{-- Full click wrapper ── --}}
                                    @if ($bannerLink && !$banner->button_text)
                                        <a href="{{ $bannerLink }}"
                                            {{ $banner->target === '_blank' ? 'target=_blank rel=noopener' : '' }}
                                            class="absolute inset-0 z-10">
                                        </a>
                                    @endif

                                    {{-- Image ── --}}
                                    <picture class="w-full h-full block">
                                        @if (!empty($banner->mobile_image))
                                            <source media="(max-width: 767px)"
                                                srcset="{{ asset('storage/' . $banner->mobile_image) }}">
                                        @endif
                                        <img src="{{ asset('storage/' . $banner->image) }}"
                                            alt="{{ $banner->alt_text ?? ($banner->title ?? '') }}"
                                            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                            loading="lazy" onerror="this.parentElement.style.display='none'">
                                    </picture>

                                    {{-- Text overlay ── --}}
                                    @if ($banner->title || $banner->subtitle || $banner->button_text)
                                        <div class="absolute inset-0 flex items-center px-6 z-5"
                                            style="background: linear-gradient(to right, rgba(0,0,0,0.42), transparent 65%)">
                                            <div class="text-white max-w-sm">
                                                @if ($banner->title)
                                                    <h3
                                                        class="text-base sm:text-xl font-bold drop-shadow-md leading-tight mb-1">
                                                        {{ $banner->title }}
                                                    </h3>
                                                @endif
                                                @if ($banner->subtitle)
                                                    <p class="text-xs sm:text-sm opacity-90 mb-2">{{ $banner->subtitle }}
                                                    </p>
                                                @endif
                                                @if ($banner->button_text && $bannerLink)
                                                    <a href="{{ $bannerLink }}"
                                                        {{ $banner->target === '_blank' ? 'target=_blank rel=noopener' : '' }}
                                                        class="inline-flex items-center gap-1.5 bg-white text-gray-900 px-3 py-1.5 rounded-full text-xs font-bold shadow-md hover:-translate-y-0.5 transition-all relative z-20">
                                                        {{ $banner->button_text }}
                                                        <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Product grid — all other types ── --}}
                    @elseif($sectionProducts->isNotEmpty())
                        <div
                            class="grid grid-cols-2 md:grid-cols-3 {{ $cols >= 4 ? 'lg:grid-cols-4' : 'lg:grid-cols-' . $cols }} gap-4 sm:gap-5">
                            @foreach ($sectionProducts as $product)
                                @php
                                    $firstSku = $product->skus->first();
                                    $isCatalog = $product->product_type === 'catalog';
                                    $showPrice = !$isCatalog && get_setting('enable_product_pricing', 1);
                                    $price = $firstSku?->price ?? 0; // Our Selling Price
                                    $mrp = $firstSku?->mrp ?? 0; // Maximum Retail Price

                                    $isFeatured = $product->categoryPivots->first()?->is_featured ?? false;
                                    $isNew = $product->created_at->diffInDays(now()) <= 14;

                                    // Calculate discount based on MRP vs Selling Price
                                    $discount =
                                        $showPrice && $mrp > 0 && $price < $mrp
                                            ? round((($mrp - $price) / $mrp) * 100)
                                            : 0;
                                @endphp

                                <a href="{{ tenant_url('p/' . $product->slug) }}"
                                    @click="fetch('{{ tenant_url('analytics/section/' . $section->id . '/click') }}', {
                                            method: 'POST',
                                            keepalive: true,
                                            headers: {
                                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                                'Accept': 'application/json'
                                            }
                                        })"
                                    class="group block cursor-pointer bg-white rounded-xl border border-gray-100 hover:border-gray-200 hover:shadow-md transition-all overflow-hidden">

                                    {{-- Product image ── --}}
                                    <div class="bg-[#f8f9fa] aspect-square overflow-hidden relative">
                                        <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}"
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                            loading="lazy"
                                            onerror="this.onerror=null; this.src='/assets/defaults/product.svg';">

                                        {{-- Badge ── --}}
                                        @if ($isFeatured)
                                            <span
                                                class="absolute top-2 left-2 text-[9px] font-black px-2 py-0.5 rounded bg-yellow-100 text-yellow-700 uppercase tracking-wide">⭐
                                                Featured</span>
                                        @elseif($discount > 0)
                                            <span
                                                class="absolute top-2 left-2 text-[9px] font-black px-2 py-0.5 rounded bg-red-100 text-red-600 uppercase tracking-wide">{{ $discount }}%
                                                off</span>
                                        @elseif($isNew)
                                            <span
                                                class="absolute top-2 left-2 text-[9px] font-black px-2 py-0.5 rounded bg-green-100 text-green-700 uppercase tracking-wide">New</span>
                                        @endif
                                    </div>

                                    {{-- Product info ── --}}
                                    <div class="p-3">
                                        <h3
                                            class="text-[13px] sm:text-[14px] font-semibold text-gray-800 leading-[1.3] line-clamp-2 mb-2 group-hover:text-brand-600 transition-colors">
                                            {{ $product->name }}
                                        </h3>
                                        @if ($showPrice)
                                            <div class="flex items-baseline gap-1.5 flex-wrap">
                                                <span class="font-bold text-[15px] text-gray-900">
                                                    ₹{{ number_format($price, 2) }}
                                                </span>
                                                @if ($discount > 0)
                                                    <span
                                                        class="text-[11px] text-gray-400 line-through">₹{{ number_format($mrp, 2) }}</span>
                                                    <span
                                                        class="text-[11px] font-bold text-green-600">{{ $discount }}%
                                                        off</span>
                                                @endif
                                            </div>
                                        @elseif ($isCatalog)
                                            <span class="text-[12px] font-semibold text-brand-600">View Details</span>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>

                        {{-- Empty section ── --}}
                    @else
                        <div
                            class="flex items-center justify-center py-10 border border-dashed border-gray-200 rounded-xl text-gray-400 text-sm font-medium gap-2 bg-gray-50">
                            <i data-lucide="package-x" class="w-4 h-4"></i>
                            No products in this section yet
                        </div>
                    @endif

                </section>
            @empty
                {{-- No sections configured ── --}}
                <div class="py-16 text-center text-gray-400">
                    <i data-lucide="layout-dashboard" class="w-10 h-10 mx-auto mb-3 opacity-30"></i>
                    <p class="font-medium">Storefront sections not configured yet.</p>
                    <p class="text-sm mt-1">Go to Admin → Storefront Sections to set up your homepage.</p>
                </div>
            @endforelse

        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Swiper Banner
            var swiper = new Swiper(".hero-swiper", {
                loop: true,
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false,
                },
                pagination: {
                    el: ".swiper-pagination",
                    clickable: true,
                },
                navigation: {
                    nextEl: ".swiper-button-next",
                    prevEl: ".swiper-button-prev",
                },
            });
        });
    </script>
@endpush
