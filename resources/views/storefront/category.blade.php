@extends('layouts.storefront')

@section('title', $category->name . ' — ' . $company->name)
@section('meta_description',
    'Shop ' .
    $category->name .
    ' at ' .
    $company->name .
    '. ' .
    $products->total() .
    '
    products available.')

@section('content')
    <div class="w-full max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8">

        {{-- ── Breadcrumb ── --}}
        <nav class="flex items-center text-[12px] sm:text-[13px] text-gray-500 font-medium mb-5 gap-2 flex-wrap">
            <a href="{{ url('/' . $company->slug) }}" class="hover:text-brand-500 transition-colors">Home</a>
            <span class="text-gray-300">/</span>
            <span class="text-gray-900 font-bold">{{ $category->name }}</span>
            <span class="text-gray-400 font-normal">({{ $products->total() }} products)</span>
        </nav>

        {{-- ── Category Banner ── --}}
        @if ($categoryBanners->isNotEmpty())
            @php $cbanner = $categoryBanners->first(); @endphp
            <div class="mb-6 rounded-2xl overflow-hidden relative shadow-sm">
                <img src="{{ asset('storage/' . $cbanner->image) }}" alt="{{ $cbanner->alt_text ?? $category->name }}"
                    class="w-full h-[140px] sm:h-[180px] object-cover"
                    onerror="this.onerror=null; this.parentElement.remove()">
                @if ($cbanner->title)
                    <div class="absolute inset-0 flex items-center px-8"
                        style="background: linear-gradient(to right, rgba(0,0,0,0.45), transparent 60%)">
                        <div class="text-white">
                            <h2 class="text-lg sm:text-2xl font-bold drop-shadow-md">{{ $cbanner->title }}</h2>
                            @if ($cbanner->subtitle)
                                <p class="text-sm opacity-90 mt-1">{{ $cbanner->subtitle }}</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <div class="flex gap-6 lg:gap-8 items-start" style="min-height: 520px; width: 100%;">

            {{-- ════════════════════════════
             LEFT SIDEBAR
        ════════════════════════════ --}}
            <aside class="hidden lg:block shrink-0 self-stretch" style="width: 210px;">
                <div class="sticky top-[88px]">

                    {{-- Categories ── --}}
                    <div class="bg-white border border-gray-100 rounded-2xl p-4 shadow-sm mb-4">
                        <h3 class="text-[11px] font-black text-gray-400 uppercase tracking-widest mb-3">Categories</h3>
                        <nav class="space-y-0.5">
                            <a href="{{ url('/' . $company->slug) }}"
                                class="flex items-center gap-2 px-3 py-2 rounded-lg text-[13px] font-semibold text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                                <i data-lucide="home" class="w-3.5 h-3.5 text-gray-400 flex-shrink-0"></i>
                                All Products
                            </a>
                            @foreach ($allCategories as $cat)
                                <a href="{{ tenant_url('c/' . $cat->slug) }}"
                                    class="flex items-center gap-2 px-3 py-2 rounded-lg text-[13px] font-semibold transition-colors
                                    {{ $cat->id === $category->id
                                        ? 'bg-brand-500 text-white'
                                        : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <img src="{{ $cat->image_url }}" class="w-5 h-5 rounded object-cover flex-shrink-0">
                                    <span class="truncate">{{ $cat->name }}</span>
                                </a>
                            @endforeach
                        </nav>
                    </div>

                </div>
            </aside>

            {{-- ════════════════════════════
             MAIN CONTENT
        ════════════════════════════ --}}
            <div style="flex: 1 1 0%; min-width: 0; overflow: hidden;">

                {{-- ── Top bar — title + sort ── --}}
                <div id="products" class="flex items-center justify-between gap-4 mb-5 flex-wrap">

                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 leading-tight">
                            {{ $category->name }}
                        </h1>
                        <p class="text-[12px] text-gray-400 font-medium mt-0.5">
                            {{ $products->total() }} {{ Str::plural('product', $products->total()) }}
                            @if ($products->total() > 0)
                                · Page {{ $products->currentPage() }} of {{ $products->lastPage() }}
                            @endif
                        </p>
                    </div>

                    {{-- Sort selector ── --}}
                    <div class="flex items-center gap-2">
                        <span class="text-[12px] text-gray-500 font-semibold hidden sm:block">Sort by:</span>
                        <select
                            onchange="window.location.href='{{ tenant_url('c/' . $category->slug) }}?sort=' + this.value + '#products'"
                            class="border border-gray-200 rounded-xl px-3 py-2 text-[13px] font-semibold text-gray-700 outline-none bg-white cursor-pointer hover:border-gray-300 transition-colors">
                            <option value="default" {{ $sortBy === 'default' ? 'selected' : '' }}>Default</option>
                            <option value="newest" {{ $sortBy === 'newest' ? 'selected' : '' }}>Newest First</option>
                            <option value="name_asc" {{ $sortBy === 'name_asc' ? 'selected' : '' }}>Name A–Z</option>
                            <option value="price_asc" {{ $sortBy === 'price_asc' ? 'selected' : '' }}>Price: Low to High
                            </option>
                            <option value="price_desc"{{ $sortBy === 'price_desc' ? 'selected' : '' }}>Price: High to Low
                            </option>
                        </select>
                    </div>
                </div>

                {{-- ── Mobile category pills ── --}}
                <div class="lg:hidden flex gap-2 overflow-x-auto no-scrollbar pb-3 mb-4">
                    <a href="{{ url('/' . $company->slug) }}"
                        class="flex-shrink-0 px-3 py-1.5 rounded-full text-[12px] font-semibold border border-gray-200 text-gray-600 hover:border-gray-400 transition-colors whitespace-nowrap bg-white">
                        All
                    </a>
                    @foreach ($allCategories as $cat)
                        <a href="{{ tenant_url('c/' . $cat->slug) }}"
                            class="flex-shrink-0 px-3 py-1.5 rounded-full text-[12px] font-semibold border transition-colors whitespace-nowrap
                            {{ $cat->id === $category->id
                                ? 'border-transparent text-white'
                                : 'border-gray-200 text-gray-600 hover:border-gray-400 bg-white' }}"
                            style="{{ $cat->id === $category->id ? 'background: var(--brand-500);' : '' }}">
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </div>

                {{-- ── Product Grid ── --}}
                @if ($products->isEmpty())
                    <div class="flex flex-col items-center justify-center text-center border border-dashed border-gray-200 rounded-2xl bg-gray-50/50"
                        style="min-height: 420px; width: 100%;">
                        <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mb-4">
                            <i data-lucide="package-x" class="w-8 h-8 text-gray-300"></i>
                        </div>
                        <p class="text-base font-bold text-gray-500 mb-1">No products in this category</p>
                        <p class="text-sm text-gray-400 mb-5">Check back soon or browse other categories</p>
                        <a href="{{ url('/' . $company->slug) }}"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white transition-colors"
                            style="background: var(--brand-600);">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            Back to Homepage
                        </a>
                    </div>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
                        @foreach ($products as $product)
                            @php
                                $firstSku = $product->skus->first();
                                $isCatalog = $product->product_type === 'catalog';
                                $showPrice = !$isCatalog && get_setting('enable_product_pricing', 1);
                                $price = $firstSku?->price ?? 0;
                                $cost = $firstSku?->cost ?? 0;
                                $discount =
                                    $showPrice && $cost > 0 && $price < $cost
                                        ? round((($cost - $price) / $cost) * 100)
                                        : 0;
                                $isNew = $product->created_at->diffInDays(now()) <= 14;
                            @endphp

                            <a href="{{ tenant_url('p/' . $product->slug) }}"
                                class="group block bg-white border border-gray-100 rounded-xl overflow-hidden hover:border-gray-200 hover:shadow-md transition-all">

                                {{-- Image ── --}}
                                <div class="relative bg-[#f8f9fa] aspect-square overflow-hidden">
                                    <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                        loading="lazy"
                                        onerror="this.onerror=null; this.src='/assets/defaults/product.svg';">

                                    {{-- Badge ── --}}
                                    @if ($discount > 0)
                                        <span
                                            class="absolute top-2 left-2 text-[9px] font-black px-2 py-0.5 rounded bg-red-100 text-red-600 uppercase tracking-wide">
                                            {{ $discount }}% off
                                        </span>
                                    @elseif($isNew)
                                        <span
                                            class="absolute top-2 left-2 text-[9px] font-black px-2 py-0.5 rounded bg-green-100 text-green-700 uppercase tracking-wide">
                                            New
                                        </span>
                                    @endif
                                </div>

                                {{-- Info ── --}}
                                <div class="p-3">
                                    <h3
                                        class="text-[13px] font-semibold text-gray-800 leading-[1.35] line-clamp-2 mb-2 group-hover:text-brand-600 transition-colors">
                                        {{ $product->name }}
                                    </h3>
                                    @if ($showPrice)
                                        <div class="flex items-baseline gap-1.5 flex-wrap">
                                            <span class="text-[15px] font-bold text-gray-900">
                                                ₹{{ number_format($price, 2) }}
                                            </span>
                                            @if ($discount > 0)
                                                <span class="text-[11px] text-gray-400 line-through">
                                                    ₹{{ number_format($cost, 2) }}
                                                </span>
                                            @endif
                                        </div>
                                    @elseif ($isCatalog)
                                        <span class="text-[12px] font-semibold text-brand-600">View Details</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>

                    {{-- ── Pagination ── --}}
                    @if ($products->hasPages())
                        <div class="mt-8 pt-6 border-t border-gray-100 flex items-center justify-between gap-4 flex-wrap">

                            {{-- Info ── --}}
                            <p class="text-[12px] text-gray-400 font-medium">
                                Showing {{ $products->firstItem() }}–{{ $products->lastItem() }}
                                of {{ $products->total() }} products
                            </p>

                            {{-- Page links ── --}}
                            <div class="flex items-center gap-1">

                                {{-- Prev ── --}}
                                @if ($products->onFirstPage())
                                    <span
                                        class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-100 text-gray-300 cursor-not-allowed">
                                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                                    </span>
                                @else
                                    <a href="{{ $products->previousPageUrl() }}"
                                        class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:border-gray-400 hover:text-gray-900 transition-colors">
                                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                                    </a>
                                @endif

                                {{-- Page numbers ── --}}
                                @foreach ($products->getUrlRange(max(1, $products->currentPage() - 2), min($products->lastPage(), $products->currentPage() + 2)) as $page => $url)
                                    @if ($page == $products->currentPage())
                                        <span
                                            class="w-9 h-9 flex items-center justify-center rounded-lg text-[13px] font-bold text-white"
                                            style="background: var(--brand-600);">
                                            {{ $page }}
                                        </span>
                                    @else
                                        <a href="{{ $url }}"
                                            class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-[13px] font-semibold text-gray-600 hover:border-gray-400 hover:text-gray-900 transition-colors">
                                            {{ $page }}
                                        </a>
                                    @endif
                                @endforeach

                                {{-- Next ── --}}
                                @if ($products->hasMorePages())
                                    <a href="{{ $products->nextPageUrl() }}"
                                        class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:border-gray-400 hover:text-gray-900 transition-colors">
                                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                                    </a>
                                @else
                                    <span
                                        class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-100 text-gray-300 cursor-not-allowed">
                                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif

                @endif

            </div>
        </div>

    </div>
@endsection
@push('scripts')
    <script>
        // If URL has #products anchor, scroll to it smoothly after page load
        // This hides the banner flash by jumping straight to product grid
        document.addEventListener('DOMContentLoaded', () => {
            if (window.location.hash === '#products') {
                const el = document.getElementById('products');
                if (el) {
                    // Small delay so page renders fully first
                    setTimeout(() => {
                        el.scrollIntoView({
                            behavior: 'instant',
                            block: 'start'
                        });
                    }, 50);
                }
            }
        });
    </script>
@endpush
