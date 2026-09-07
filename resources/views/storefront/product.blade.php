@extends ('layouts.storefront')

@section('title', $product->name)
@push('styles')
    <style>
        /* ── Hide ALL Google Translate UI chrome ── */
        .goog-te-banner-frame,
        .goog-te-balloon-frame,
        .goog-tooltip,
        .goog-tooltip-content,
        #goog-gt-tt,
        .goog-te-ftab-float,
        .goog-te-menu-value:hover,
        .goog-te-gadget-icon {
            display: none !important;
        }

        /* ── Prevent body shift from translate bar ── */
        body {
            top: 0 !important;
            position: static !important;
        }

        /* ── Hide the injected iframe bar ── */
        .skiptranslate {
            display: none !important;
        }

        /* ── Remove font changes Google Translate injects ── */
        font {
            background-color: transparent !important;
        }
    </style>
@endpush
@section('content')
    @php
        // ── Prepare data for Alpine ──
        $isCatalog = $product->isCatalog();
        $images = $product->media->where('media_type', 'image')->values();
        $firstImg = $images->first()?->media_path;
        $youtubeVideos = $product->media->where('media_type', 'youtube')->values();
        $hasSku = $product->skus->isNotEmpty();
        $firstSku = $hasSku ? $product->skus->first() : null;
        $minPrice = $hasSku ? $product->skus->min('price') ?? 0 : 0;
        $maxPrice = $hasSku ? $product->skus->max('price') ?? 0 : 0;
        $inStock = $hasSku ? $product->skus->sum(fn($s) => $s->stocks->sum('qty')) > 0 : false;

        // Group SKU attributes for variant selector
        $attributes = collect();

        foreach ($product->skus as $sku) {
            foreach ($sku->skuValues as $sv) {
                // 1. Guard clause: Skip this loop if the related attributeValue is missing
                if (!$sv->attributeValue) {
                    continue;
                }

                // 2. Use null-safe operator (?->) in case the parent attribute is missing
                $attrName = $sv->attribute?->name ?? 'Variant';

                if (!$attributes->has($attrName)) {
                    $attributes[$attrName] = collect();
                }

                // 3. We now know $sv->attributeValue is safe to access
                if (!$attributes[$attrName]->contains('id', $sv->attributeValue->id)) {
                    $attributes[$attrName]->push([
                        'id' => $sv->attributeValue->id,
                        'value' => $sv->attributeValue->value,
                    ]);
                }
            }
        }

        // Build a flat SKU list the frontend can filter against.
        // Each entry: { id, price, mrp, in_stock, values: { [attrName]: attrValueId } }
        // This lets the Alpine selector compute "which options are still valid?"
        // purely client-side by attribute-map equality — Shopify-style.
        $skuList = [];
        foreach ($product->skus as $sku) {
            $values = [];
            foreach ($sku->skuValues as $sv) {
                if (!$sv->attributeValue || !$sv->attribute) {
                    continue;
                }
                $values[$sv->attribute->name] = $sv->attributeValue->id;
            }

            $skuList[] = [
                'id' => $sku->id,
                'price' => (float) $sku->price,
                'mrp' => $sku->mrp !== null ? (float) $sku->mrp : 0,
                'in_stock' => (bool) $sku->is_in_stock,
                // Whether the shown price already contains GST. The page said
                // "Inclusive of all taxes" for every product regardless.
                'tax_type' => $sku->tax_type ?? 'exclusive',
                'values' => $values,
            ];
        }

        // Pick the initial SKU — prefer first in-stock, fall back to first.
        $initialSku = collect($skuList)->firstWhere('in_stock', true) ?? ($skuList[0] ?? null);
    @endphp

    {{-- No: Alpine v3 calls a component's init() method
         automatically, so declaring it here ran the whole thing twice. --}}
    <div class="mx-auto max-w-[1400px] px-4 py-6 sm:px-6 lg:px-8 lg:py-10" x-data="productPage()">
        <nav
            class="no-scrollbar mb-6 flex overflow-x-auto text-[12px] font-medium whitespace-nowrap text-gray-500 sm:text-[13px] lg:mb-8">
            @if ($hasStorefront)
                @php
                    $breadCat = $product->categories->first();
                @endphp
                <a href="{{ url('/' . $company->slug) }}" class="hover:text-brand-500 transition-colors"> Home </a>
                @if ($breadCat)
                    <span class="mx-2 text-gray-300 sm:mx-3">/</span>

                    <a href="{{ tenant_url('c/' . $breadCat->slug) }}" class="hover:text-brand-500 transition-colors">
                        {{ $breadCat->name }}
                    </a>
                @endif
                <span class="mx-2 text-gray-300 sm:mx-3">/</span>
            @endif
            <span class="truncate font-bold text-gray-900"> {{ $product->name }} </span>
        </nav>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:gap-12">
            <div class="flex flex-col gap-4 lg:col-span-6 xl:col-span-5">
                <div class="relative flex w-full items-center justify-center overflow-hidden rounded-2xl border border-gray-100 bg-[#f8f9fa] shadow-sm"
                    style="min-height: 400px; aspect-ratio: 1/1">
                    {{-- <button
                        class="absolute top-4 right-4 w-9 h-9 bg-white/80 backdrop-blur rounded-full flex items-center justify-center text-gray-600 hover:text-gray-900 transition-colors shadow-sm z-10">
                        <i data-lucide="zoom-in" class="w-4 h-4"></i>
                    </button> --}}
                    {{-- Product image — shown when no YouTube active ── --}}
                    <template x-if="!youtubeActive">
                        <img :src="activeImage || '{{ $product->primary_image_url }}'" alt="{{ $product->name }}"
                            class="h-full w-full object-cover mix-blend-multiply transition-all duration-300"
                            style="position: absolute; inset: 0; width: 100%; height: 100%"
                            onerror="this.onerror=null; this.src='{{ asset('assets/defaults/product.svg') }}'" />
                    </template>

                    {{-- YouTube embed — shown when video thumbnail clicked ── --}}
                    <template x-if="youtubeActive">
                        <div class="absolute inset-0 h-full w-full bg-black">
                            <iframe :src="'https://www.youtube.com/embed/' + youtubeActive + '?autoplay=1'"
                                class="h-full w-full" style="position: absolute; inset: 0; width: 100%; height: 100%"
                                frameborder="0" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen>
                            </iframe>
                        </div>
                    </template>
                </div>

                <div class="no-scrollbar flex snap-x gap-3 overflow-x-auto pb-2">
                    {{-- Product images ── --}}
                    @foreach ($images as $index => $media)
                        <button @click="activeImage = '{{ asset('storage/' . $media->media_path) }}'; youtubeActive = null"
                            class="h-[72px] w-[72px] shrink-0 snap-center overflow-hidden rounded-xl border-2 bg-[#f8f9fa] transition-all duration-200 sm:h-[84px] sm:w-[84px]"
                            :class="activeImage === '{{ asset('storage/' . $media->media_path) }}'
                                ?
                                'border-gray-900 scale-[0.98]' :
                                'border-transparent opacity-70 hover:opacity-100'">
                            <img src="{{ asset('storage/' . $media->media_path) }}"
                                class="h-full w-full object-cover mix-blend-multiply" loading="lazy"
                                onerror="this.onerror=null; this.src='{{ asset('assets/defaults/product.svg') }}'" />
                        </button>
                    @endforeach

                    {{-- YouTube videos ── --}}
                    @foreach ($youtubeVideos as $video)
                        @php
                            // Updated regex catches standard links, youtu.be, shorts, and embeds
                            preg_match(
                                '/(?:v=|youtu\.be\/|shorts\/|embed\/)([a-zA-Z0-9_-]{11})/',
                                $video->media_path,
                                $m,
                            );
                            $ytId = $m[1] ?? null;
                        @endphp
                        @if ($ytId)
                            <button @click="playYoutube('{{ $ytId }}')"
                                class="relative h-[72px] w-[72px] shrink-0 snap-center overflow-hidden rounded-xl border-2 bg-black transition-all duration-200 sm:h-[84px] sm:w-[84px]"
                                :class="youtubeActive === '{{ $ytId }}'
                                    ?
                                    'border-gray-900 scale-[0.98] opacity-100' :
                                    'border-transparent opacity-80 hover:opacity-100'">
                                <img src="https://img.youtube.com/vi/{{ $ytId }}/mqdefault.jpg"
                                    class="h-full w-full object-cover opacity-60" />
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-red-600 shadow-md backdrop-blur">
                                        <i data-lucide="play" class="ml-0.5 h-3.5 w-3.5 fill-current"></i>
                                    </div>
                                </div>
                            </button>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col pt-2 lg:col-span-6 lg:pl-4 xl:col-span-7">
                <div class="mb-4 flex items-center">
                    @if ($isCatalog)
                        {{-- Catalog products are not sellable — never show a stock badge --}}
                    @elseif ($product->type === 'variable')
                        {{-- Reactive badge for variable products — reflects the currently selected variant's stock. --}}
                        <span x-show="currentInStock" x-cloak
                            class="rounded-md bg-[#e6fcf5] px-3 py-1.5 text-[11px] font-black tracking-wider text-[#108c2a] uppercase">
                            In Stock
                        </span>
                        <span x-show="!currentInStock" x-cloak
                            class="rounded-md bg-red-50 px-3 py-1.5 text-[11px] font-black tracking-wider text-red-600 uppercase">
                            Out of Stock
                        </span>
                    @elseif ($inStock)
                        <span
                            class="rounded-md bg-[#e6fcf5] px-3 py-1.5 text-[11px] font-black tracking-wider text-[#108c2a] uppercase">
                            In Stock
                        </span>
                    @else
                        <span
                            class="rounded-md bg-red-50 px-3 py-1.5 text-[11px] font-black tracking-wider text-red-600 uppercase">
                            Out of Stock
                        </span>
                    @endif
                    <button @click="shareProduct()"
                        class="ml-auto flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 text-gray-500 shadow-sm transition-colors hover:bg-gray-50 hover:text-gray-900">
                        <i data-lucide="share-2" class="h-4 w-4"></i>
                    </button>
                </div>

                <h1 class="mb-5 text-2xl leading-[1.2] font-bold tracking-tight text-[#1f2937] sm:text-3xl lg:text-[34px]">
                    {{ $product->name }}
                </h1>

                @if (!$isCatalog && get_setting('enable_product_pricing', 1))
                    <div class="mb-8">
                        <div class="mb-1.5 flex flex-wrap items-end gap-3">
                            <span class="text-3xl leading-none font-bold text-gray-800 sm:text-[40px]"
                                x-text="
                                    '₹' + parseFloat(currentPrice).toLocaleString('en-IN', { minimumFractionDigits: 2 })
                                ">
                                ₹{{ number_format($minPrice, 2) }}
                            </span>
                            <template x-if="currentMrp > 0 && currentMrp > currentPrice">
                                <span class="mb-1 text-lg font-medium text-gray-400 line-through sm:text-xl"
                                    x-text="
                                        '₹' +
                                        parseFloat(currentMrp).toLocaleString('en-IN', { minimumFractionDigits: 2 })
                                    ">
                                </span>
                            </template>
                            <template x-if="currentMrp > 0 && currentMrp > currentPrice">
                                <span
                                    class="mb-1.5 ml-1 rounded bg-[#e6fcf5] px-2.5 py-1 text-[12px] font-bold text-[#108c2a]"
                                    x-text="Math.round(((currentMrp - currentPrice) / currentMrp) * 100) + '% OFF'">
                                </span>
                            </template>
                        </div>
                        <p class="text-[12px] font-medium text-gray-500">
                            {{-- Was hardcoded, so an exclusive SKU told the customer
                                 tax was already included and then added it at checkout. --}}
                            <span
                                x-text="currentTaxType === 'inclusive' ? 'Inclusive of all taxes' : 'Excluding taxes'"></span>
                            @if ($product->saleUnit)
                                · Per {{ $product->saleUnit->name }}
                            @endif
                        </p>
                    </div>
                @endif

                @if ($product->type === 'variable' && $attributes->isNotEmpty())
                    <div class="mb-8 space-y-5">
                        @foreach ($attributes as $attrName => $values)
                            <div>
                                <h3 class="mb-3 text-[13px] font-bold text-gray-900">
                                    {{ $attrName }}
                                    <span class="ml-1 font-medium text-gray-400"
                                        x-text="selectedAttrs['{{ $attrName }}'] ? ': ' + selectedAttrs['{{ $attrName }}'].value : ''"></span>
                                </h3>
                                <div class="flex flex-wrap gap-2.5">
                                    @foreach ($values as $val)
                                        <button type="button" data-attr="{{ $attrName }}"
                                            data-value-id="{{ $val['id'] }}" data-value-label="{{ $val['value'] }}"
                                            @click="selectAttr('{{ $attrName }}', {{ $val['id'] }}, '{{ addslashes($val['value']) }}')"
                                            :disabled="!isOptionValid('{{ $attrName }}', {{ $val['id'] }})"
                                            :aria-disabled="!isOptionValid('{{ $attrName }}', {{ $val['id'] }})"
                                            class="relative rounded-xl border-2 px-5 py-2.5 text-[13px] font-bold transition-all disabled:cursor-not-allowed"
                                            :class="{
                                                'border-gray-900 bg-gray-900 text-white': selectedAttrs[
                                                    '{{ $attrName }}']?.id === {{ $val['id'] }},
                                                'border-gray-200 bg-white text-gray-700 hover:border-gray-400': isOptionValid(
                                                        '{{ $attrName }}', {{ $val['id'] }}) && selectedAttrs[
                                                        '{{ $attrName }}']?.id !== {{ $val['id'] }} && !
                                                    isOptionOutOfStock('{{ $attrName }}', {{ $val['id'] }}),
                                                'border-gray-200 bg-gray-50 text-gray-400 line-through': isOptionValid(
                                                        '{{ $attrName }}', {{ $val['id'] }}) &&
                                                    isOptionOutOfStock('{{ $attrName }}', {{ $val['id'] }}) &&
                                                    selectedAttrs['{{ $attrName }}']?.id !== {{ $val['id'] }},
                                                'border-gray-100 bg-gray-50 text-gray-300 line-through opacity-60': !
                                                    isOptionValid('{{ $attrName }}', {{ $val['id'] }}),
                                            }">
                                            {{ $val['value'] }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Quantity selector ── --}}
                <div class="mb-6 flex items-center gap-4">
                    <span class="text-[13px] font-bold text-gray-900">Quantity</span>
                    <div class="flex items-center overflow-hidden rounded-xl border border-gray-200">
                        <button @click="qty = Math.max(1, qty - 1)"
                            class="flex h-10 w-10 items-center justify-center text-gray-600 transition-colors hover:bg-gray-50">
                            <i data-lucide="minus" class="h-4 w-4"></i>
                        </button>
                        <span class="w-12 text-center text-[15px] font-bold" x-text="qty"></span>
                        <button @click="qty++"
                            class="flex h-10 w-10 items-center justify-center text-gray-600 transition-colors hover:bg-gray-50">
                            <i data-lucide="plus" class="h-4 w-4"></i>
                        </button>
                    </div>
                    @if ($product->quantity_limitation)
                        <span class="text-[11px] text-gray-400">Max {{ $product->quantity_limitation }} per order</span>
                    @endif
                </div>

                @if ($isCatalog)
                    {{-- ── Catalog: Send Inquiry button ── --}}
                    <div class="mt-auto flex flex-col gap-3">
                        <button @click="showInquiry = !showInquiry"
                            class="flex w-full items-center justify-center gap-2.5 rounded-xl bg-teal-600 py-4 text-[15px] font-bold text-white shadow-sm transition-all hover:bg-teal-700">
                            <i data-lucide="message-circle" class="h-5 w-5"></i> Send Inquiry
                        </button>
                    </div>

                    {{-- ── Inquiry Section ── --}}
                    <div x-show="showInquiry" x-cloak x-transition
                        class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4">
                        @if (session('success'))
                            {{-- Success State UI: Clean and Professional --}}
                            <div class="px-4 py-6 text-center">
                                <div
                                    class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-teal-100 text-teal-600 shadow-sm">
                                    <i data-lucide="check-circle-2" class="h-10 w-10"></i>
                                </div>
                                <h4 class="mb-2 text-lg font-bold text-gray-900">Inquiry Sent!</h4>
                                <p class="text-sm leading-relaxed text-gray-600">{{ session('success') }}</p>
                                <button @click="showInquiry = false"
                                    class="mt-5 text-sm font-bold text-teal-600 underline decoration-2 underline-offset-4 hover:text-teal-700">
                                    Close
                                </button>
                            </div>
                        @else
                            {{-- Standard Inquiry Form --}}
                            <form method="POST" action="{{ tenant_url('inquiry') }}">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}" />
                                <input type="hidden" name="product_name" value="{{ $product->name }}" />

                                <div class="space-y-3">
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <input type="text" name="customer_name" placeholder="Your Name *" required
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm outline-none focus:border-teal-500" />
                                        <input type="email" name="customer_email" placeholder="Email Address *"
                                            required
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm outline-none focus:border-teal-500" />
                                    </div>
                                    <input type="text" name="customer_phone" placeholder="Phone Number (10 Digits)"
                                        inputmode="numeric" maxlength="10" pattern="[0-9]{10}"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm outline-none focus:border-teal-500" />
                                    <textarea name="customer_notes" rows="3" placeholder="Your message or inquiry..."
                                        class="w-full resize-none rounded-lg border border-gray-300 px-3 py-2.5 text-sm outline-none focus:border-teal-500"></textarea>

                                    <button type="submit"
                                        class="w-full rounded-lg bg-teal-600 py-3.5 text-sm font-bold text-white shadow-md transition-all hover:bg-teal-700 active:scale-[0.98]">
                                        Submit Inquiry
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                @else
                    {{-- ── Sellable: Cart buttons ── --}}
                    <div class="mt-auto flex flex-col gap-3 sm:flex-row sm:gap-4">
                        <button type="button" @click="addToCart()" :disabled="!currentInStock"
                            class="flex flex-1 items-center justify-center gap-2.5 rounded-xl border-2 border-[#111827] bg-white py-4 text-[15px] font-bold text-[#111827] shadow-sm transition-all hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-white">
                            <i data-lucide="shopping-cart" class="h-5 w-5"></i>
                            <span x-text="currentInStock ? 'Add to Cart' : 'Out of Stock'"></span>
                        </button>
                        <button type="button" @click="buyNow()" :disabled="!currentInStock"
                            class="flex flex-1 items-center justify-center gap-2.5 rounded-xl bg-[#111827] py-4 text-[15px] font-bold text-white shadow-xl shadow-gray-300 transition-all hover:bg-black disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-[#111827]">
                            <i data-lucide="zap" class="h-5 w-5 fill-current"></i>
                            <span x-text="currentInStock ? 'Buy Now' : 'Unavailable'"></span>
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-12 border-t border-gray-200 pt-10 lg:mt-16">
            <div class="w-full max-w-5xl min-w-0">
                {{-- ── Also Add Section (Placed before Description) ── --}}
                @if (isset($addonGroups) && $addonGroups->isNotEmpty())
                    {{-- Using grid grid-cols-1 creates a strict boundary that prevents flex blowout issues --}}
                    <div class="mb-12 grid grid-cols-1" x-data="addonSection()">
                        <div class="w-full min-w-0">
                            <h2 class="mb-5 flex items-center gap-2.5 text-2xl font-bold tracking-tight text-gray-900">
                                <span
                                    class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-50 text-blue-500">
                                    <i data-lucide="plus-circle" class="h-5 w-5"></i>
                                </span>
                                People Also Buy
                            </h2>

                            {{-- Category Pills --}}
                            <div class="no-scrollbar mb-6 flex w-full snap-x gap-2 overflow-x-auto pb-2">
                                @foreach ($addonGroups as $groupName => $groupProducts)
                                    <button type="button" @click="activeTab = '{{ addslashes($groupName) }}'"
                                        :class="activeTab === '{{ addslashes($groupName) }}' ?
                                            'bg-[#0f172a] text-white shadow-sm' :
                                            'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                        class="flex-none snap-start rounded-full px-5 py-2 text-[13px] font-bold whitespace-nowrap transition-all">
                                        {{ $groupName }}
                                    </button>
                                @endforeach
                            </div>

                            {{-- Product Cards Grid --}}
                            @foreach ($addonGroups as $groupName => $groupProducts)
                                <div x-show="activeTab === '{{ addslashes($groupName) }}'" x-cloak
                                    class="no-scrollbar flex w-full snap-x snap-mandatory gap-4 overflow-x-auto pb-4">
                                    @foreach ($groupProducts as $addon)
                                        @php
                                            $addonSku =
                                                $addon->skus->first(fn($s) => $s->is_in_stock) ?? $addon->skus->first();
                                            $addonInStock = $addonSku?->is_in_stock ?? false;
                                        @endphp
                                        <div
                                            class="flex w-[160px] flex-none snap-start flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:shadow-md sm:w-[200px]">
                                            <a href="{{ tenant_url('p/' . $addon->slug) }}"
                                                class="relative block aspect-square w-full overflow-hidden bg-[#f8f9fa]">
                                                <img src="{{ $addon->primary_image_url }}" alt="{{ $addon->name }}"
                                                    class="h-full w-full object-cover transition-transform duration-500 hover:scale-105"
                                                    loading="lazy"
                                                    onerror="this.onerror=null; this.src='{{ asset('assets/defaults/product.svg') }}'" />
                                            </a>
                                            <div class="flex flex-1 flex-col p-4">
                                                <a href="{{ tenant_url('p/' . $addon->slug) }}"
                                                    class="mb-1 line-clamp-1 block text-[14px] font-bold text-gray-900 transition-colors hover:text-blue-600">
                                                    {{ $addon->name }}
                                                </a>
                                                @if ($addonSku)
                                                    <p class="mb-3 text-[15px] font-extrabold text-gray-900">
                                                        ₹{{ number_format($addonSku->price, 2) }}
                                                    </p>
                                                @endif
                                                <div class="mt-auto">
                                                    <button type="button"
                                                        :disabled="isAdding === {{ $addon->id }} || !
                                                            {{ $addonInStock ? 'true' : 'false' }}"
                                                        @click="addAddon({{ $addon->id }}, {{ $addonSku?->id ?? 'null' }}, '{{ addslashes($addon->name) }}', {{ $addonSku?->price ?? 0 }}, '{{ $addon->primary_image_url }}')"
                                                        class="w-full rounded-xl border-2 border-gray-900 py-2 text-[13px] font-bold text-gray-900 transition-all hover:bg-gray-900 hover:text-white active:scale-95 disabled:cursor-not-allowed disabled:opacity-40">
                                                        <template x-if="addedIds.includes({{ $addon->id }})">
                                                            <span>✓ Added</span>
                                                        </template>
                                                        <template x-if="!addedIds.includes({{ $addon->id }})">
                                                            <span>{{ $addonInStock ? 'Add' : 'Out of Stock' }}</span>
                                                        </template>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Description ── --}}
                @if ($product->description)
                    <div class="mb-10">
                        <h2 class="mb-5 flex items-center gap-2.5 text-[18px] font-bold text-gray-900 sm:text-[22px]">
                            <i data-lucide="align-left" class="h-5 w-5 text-[#6DBE6A]"></i>
                            Description
                        </h2>
                        <div class="prose prose-sm sm:prose-base max-w-none leading-relaxed text-gray-600">
                            {!! nl2br(e($product->description)) !!}
                        </div>
                    </div>
                @endif

                {{-- Product Guide / Plant Education sections ── --}}
                @if ($product->product_guide && count($product->product_guide))
                    @if ($company && $company->hasModule('plant_education'))
                        @php
                            $plantCareMap = collect($product->product_guide)
                                ->whereIn('title', ['Sunlight', 'Watering'])
                                ->keyBy('title');
                            // NOTE: ->values() is deliberately NOT called here.
                            // The original array keys are the indexes stored in
                            // product_guide, and the TTS endpoint resolves text
                            // by that index. Re-indexing would make the Listen
                            // button request the wrong section.
                            $extraGuides = collect($product->product_guide)->whereNotIn('title', [
                                'Sunlight',
                                'Watering',
                            ]);
                        @endphp

                        {{-- Plant Care Cards: simple open display, no accordion, no speak ── --}}
                        @if ($plantCareMap->isNotEmpty())
                            <div class="mb-10">
                                <h2
                                    class="mb-5 flex items-center gap-2.5 text-[20px] font-bold tracking-tight text-gray-900 sm:text-[24px]">
                                    <i data-lucide="leaf" class="h-5.5 w-5.5 text-green-600"></i>
                                    Plant Education
                                </h2>
                                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                    @if ($plantCareMap->has('Sunlight') && ($plantCareMap->get('Sunlight')['description'] ?? ''))
                                        <div
                                            class="rounded-2xl border border-amber-200/80 bg-gradient-to-br from-amber-50/60 via-amber-50/10 to-white p-5 shadow-sm transition-all duration-300 hover:shadow-md sm:p-6">
                                            <div class="flex items-center gap-2.5">
                                                <span class="text-2xl select-none">☀️</span>
                                                <span
                                                    class="text-[13px] font-extrabold tracking-wider text-amber-900 uppercase sm:text-[14px]">Sunlight
                                                    Needs</span>
                                            </div>
                                            <p
                                                class="text-[15.5px] leading-[1] font-medium whitespace-pre-line text-gray-800 sm:text-[16.5px]">
                                                {{ trim($plantCareMap->get('Sunlight')['description']) }}</p>
                                        </div>
                                    @endif
                                    @if ($plantCareMap->has('Watering') && ($plantCareMap->get('Watering')['description'] ?? ''))
                                        <div
                                            class="rounded-2xl border border-blue-200/80 bg-gradient-to-br from-blue-50/60 via-blue-50/10 to-white p-5 shadow-sm transition-all duration-300 hover:shadow-md sm:p-6">
                                            <div class="flex items-center gap-2.5">
                                                <span class="text-2xl select-none">💧</span>
                                                <span
                                                    class="text-[13px] font-extrabold tracking-wider text-blue-900 uppercase sm:text-[14px]">Watering
                                                    Routine</span>
                                            </div>
                                            <p
                                                class="text-[15.5px] leading-[1] font-medium whitespace-pre-line text-gray-800 sm:text-[16.5px]">
                                                {{ trim($plantCareMap->get('Watering')['description']) }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Additional guide sections in accordion ── --}}
                        @if ($extraGuides->isNotEmpty())
                            <div class="mb-10">
                                @if (!(isset($company) && $company->hasModule('plant_education')))
                                    <h2
                                        class="mb-5 flex items-center gap-2.5 text-[20px] font-bold tracking-tight text-gray-900 sm:text-[24px]">
                                        <i data-lucide="book-open" class="h-5.5 w-5.5 text-[#3ba2e3]"></i>
                                        Product Guide
                                    </h2>
                                @endif
                                <div class="space-y-4">
                                    @foreach ($extraGuides as $guideIndex => $guide)
                                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                                            <div
                                                class="flex w-full items-center justify-between bg-gray-50/50 px-5 py-4 transition-colors select-none hover:bg-gray-50 sm:px-6 sm:py-5">
                                                <span
                                                    class="pr-4 text-[15.5px] font-bold tracking-tight text-gray-900 sm:text-[16.5px]">{{ $guide['title'] ?? '' }}</span>

                                                <div class="flex flex-shrink-0 items-center gap-3 sm:gap-4">
                                                    <button type="button" @click="toggleSpeak({{ $guideIndex }})"
                                                        :disabled="loadingKey === {{ $guideIndex }}"
                                                        class="flex items-center gap-1.5 rounded-full border px-3 py-3 shadow-sm transition-all duration-200 sm:rounded-lg sm:px-3.5 sm:py-2"
                                                        :class="loadingKey === {{ $guideIndex }} ?
                                                            'bg-gray-50 text-gray-500 border-gray-200 cursor-wait' :
                                                            speakingKey === {{ $guideIndex }} ?
                                                            'bg-red-50 text-red-600 border-red-200 hover:bg-red-100' :
                                                            'bg-blue-50 text-blue-600 border-blue-200 hover:bg-blue-100'"
                                                        :title="loadingKey === {{ $guideIndex }} ? 'Preparing audio' :
                                                            speakingKey === {{ $guideIndex }} ? 'Stop Reading' :
                                                            'Listen'">
                                                        {{-- Idle --}}
                                                        <i data-lucide="volume-2" class="h-4.5 w-4.5"
                                                            x-show="speakingKey !== {{ $guideIndex }} && loadingKey !== {{ $guideIndex }}"></i>
                                                        {{-- Loading: first play of a section needs a network round-trip --}}
                                                        <svg class="h-4.5 w-4.5 animate-spin" fill="none"
                                                            viewBox="0 0 24 24"
                                                            x-show="loadingKey === {{ $guideIndex }}"
                                                            style="display: none">
                                                            <circle class="opacity-25" cx="12" cy="12"
                                                                r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor"
                                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                        </svg>
                                                        {{-- Playing --}}
                                                        <i data-lucide="square" class="h-4.5 w-4.5 fill-current"
                                                            x-show="speakingKey === {{ $guideIndex }}"
                                                            style="display: none"></i>
                                                        <span
                                                            class="hidden text-[12px] font-black tracking-wider uppercase sm:inline"
                                                            x-text="loadingKey === {{ $guideIndex }} ? 'Wait' :
                                                                speakingKey === {{ $guideIndex }} ? 'Stop' : 'Listen'"></span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                    @endif

                @endif

                {{-- Related Products ── --}}
                @if ($related->isNotEmpty())
                    <div class="mt-10 border-t border-gray-200 pt-10">
                        <h2 class="mb-6 flex items-center gap-2.5 text-[18px] font-bold text-gray-900 sm:text-[22px]">
                            <i data-lucide="sparkles" class="h-5 w-5 text-[#3ba2e3]"></i>
                            Related Products
                        </h2>
                        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                            @foreach ($related as $rel)
                                @php
                                    $relSku = $rel->skus->first();
                                    $relIsCatalog = $rel->product_type === 'catalog';
                                    $relShowPrice = !$relIsCatalog && get_setting('enable_product_pricing', 1);
                                    $relPrice = $relSku?->price ?? 0;
                                @endphp
                                <a href="{{ tenant_url('p/' . $rel->slug) }}"
                                    class="group block overflow-hidden rounded-xl border border-gray-100 bg-white transition-all hover:border-gray-200 hover:shadow-md">
                                    <div class="aspect-square overflow-hidden bg-gray-50">
                                        <img src="{{ $rel->primary_image_url }}" alt="{{ $rel->name }}"
                                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                            loading="lazy"
                                            onerror="this.onerror=null; this.src='{{ asset('assets/defaults/product.svg') }}'" />
                                    </div>
                                    <div class="p-3">
                                        <p
                                            class="group-hover:text-brand-600 mb-1 line-clamp-2 text-[13px] font-semibold text-gray-800 transition-colors">
                                            {{ $rel->name }}
                                        </p>
                                        @if ($relShowPrice)
                                            <p class="text-[14px] font-bold text-gray-900">
                                                ₹{{ number_format($relPrice, 2) }}</p>
                                        @elseif ($relIsCatalog)
                                            <p class="text-brand-600 text-[12px] font-semibold">View Details</p>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Google Translate floating button ── --}}
    <div id="google_translate_element" style="display: none"></div>

    <div class="fixed right-6 bottom-24 z-50" x-data="{ open: false }">
        <button @click="open = !open"
            class="flex h-12 w-12 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600 shadow-lg transition-all hover:shadow-xl"
            title="Translate page">
            <img src="{{ asset('assets/icons/translate.svg') }}" alt="Translate" />
        </button>

        {{-- Language picker ── --}}
        <div x-show="open" x-cloak @click.away="open = false" x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute right-0 bottom-14 w-44 overflow-hidden rounded-2xl border border-gray-100 bg-white py-1.5 shadow-xl">
            <p class="px-3 py-1.5 text-[10px] font-black tracking-widest text-gray-400 uppercase">Select Language</p>

            @php
                $languages = [
                    'en' => '🇬🇧 English',
                    'hi' => '🇮🇳 Hindi',
                    'gu' => '🇮🇳 Gujarati',
                    'mr' => '🇮🇳 Marathi',
                    'ta' => '🇮🇳 Tamil',
                    'te' => '🇮🇳 Telugu',
                    'bn' => '🇮🇳 Bengali',
                    'kn' => '🇮🇳 Kannada',
                    'pa' => '🇮🇳 Punjabi',
                    'ar' => '🇸🇦 Arabic',
                    'zh-CN' => '🇨🇳 Chinese',
                ];
            @endphp

            @foreach ($languages as $code => $label)
                <button @click="translatePage('{{ $code }}'); open = false"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-[13px] font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>
@endsection
@push('scripts')
    {{-- Google Translate ── --}}
    <script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    <script>
        // ── Server-side TTS endpoint ──
        // Slug routes and custom-domain routes have different prefixes, so the
        // URL is resolved server-side rather than assumed in JS.
        const TTS_ENDPOINT =
            '{{ request()->route('slug') ? url(request()->route('slug') . '/tts/product-guide') : url('/tts/product-guide') }}';
        const TTS_PRODUCT_ID = {{ $product->id }};

        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                    pageLanguage: "en",
                    autoDisplay: false,
                },
                "google_translate_element",
            );

            // Remove the injected banner after translate loads
            const killBanner = setInterval(() => {
                const banner = document.querySelector(".goog-te-banner-frame");
                if (banner) {
                    banner.remove();
                    document.body.style.top = "0";
                    console.log("[Translate] Banner removed");
                }
                const skip = document.querySelector(".skiptranslate");
                if (skip) skip.style.display = "none";
            }, 500);

            // Stop checking after 5 seconds
            setTimeout(() => clearInterval(killBanner), 5000);
        }

        function translatePage(langCode) {
            // Find the Google Translate select element and change it
            const select = document.querySelector(".goog-te-combo");
            if (select) {
                select.value = langCode;
                select.dispatchEvent(new Event("change"));
                console.log("[Translate] Language changed to:", langCode);
            } else {
                console.warn("[Translate] Google Translate not ready yet");
                // Retry once after short delay
                setTimeout(() => translatePage(langCode), 800);
            }
        }
    </script>
    <script>
        function productPage() {
            return {
                // ── Image state ──
                activeImage: '{{ $product->primary_image_url }}',
                youtubeActive: null,
                // ── Speak state ──
                // speakingKey : index currently playing
                // loadingKey  : index currently being fetched (network round-trip)
                // audioPlayer : the single active Audio instance, so a new
                //               request can always stop the previous one
                speakingKey: null,
                loadingKey: null,
                audioPlayer: null,

                // ── Inquiry state ──
                showInquiry: false,

                // ── Variant state ──
                // selectedAttrs[attrName] = { id: attrValueId, value: 'Red' }
                selectedAttrs: {},
                currentPrice: @json($initialSku['price'] ?? $minPrice),
                currentMrp: @json($initialSku['mrp'] ?? ($firstSku?->mrp ?? 0)),
                currentSkuId: @json($initialSku['id'] ?? ($firstSku?->id ?? null)),
                currentInStock: @json(isset($initialSku) ? (bool) $initialSku['in_stock'] : (bool) $inStock),
                currentTaxType: @json($initialSku['tax_type'] ?? 'exclusive'),
                qty: 1,

                // ── Full SKU list from server (see @php block at top) ──
                // Shape: [{ id, price, mrp, in_stock, values: { attrName: attrValueId } }, ...]
                skus: @json($skuList),

                // Attribute display order — we always want the same order in the combinator
                // regardless of JS object key insertion quirks.
                attrOrder: @json($attributes->keys()->values()),

                init() {
                    // Check if there is a flash message from the server
                    @if (session('success'))
                        this.showInquiry = true;
                    @endif
                    // Seed selectedAttrs from the initial SKU (first in-stock, else first).
                    // Falls back to per-attribute first value if the product has no SKU
                    // linkage at all (legacy/edge data).
                    const initial = @json($initialSku);

                    if (initial && initial.values) {
                        for (const [attrName, attrValueId] of Object.entries(initial.values)) {
                            const display = this.lookupValueName(attrName, attrValueId);
                            this.selectedAttrs[attrName] = {
                                id: attrValueId,
                                value: display
                            };
                        }
                    } else {
                        @foreach ($attributes as $attrName => $values)
                            @if ($values->isNotEmpty())
                                this.selectedAttrs['{{ $attrName }}'] = {
                                    id: @json($values->first()['id']),
                                    value: "{{ addslashes($values->first()['value']) }}",
                                };
                            @endif
                        @endforeach
                    }

                    this.updateMatch();

                    console.log('[Product] Initialized', {
                        product: '{{ $product->slug }}',
                        type: '{{ $product->type }}',
                        skus: this.skus.length,
                        initial: this.currentSkuId,
                        inStock: this.currentInStock,
                    });
                },

                /**
                 * Resolve a human-readable value name for a given (attr, valueId) pair.
                 * Walks the SKU list because that's the source of truth we have on the client.
                 */
                lookupValueName(attrName, attrValueId) {
                    for (const sku of this.skus) {
                        if (sku.values[attrName] === attrValueId) {
                            // We don't ship the value labels in the JSON payload (keeps it slim);
                            // instead we read the label from the DOM button on first render.
                            const btn = document.querySelector(
                                `[data-attr="${attrName}"][data-value-id="${attrValueId}"]`
                            );
                            if (btn) return btn.dataset.valueLabel || btn.innerText.trim();
                        }
                    }
                    return '';
                },

                /**
                 * Handle a click on an attribute option.
                 * — Ignore clicks on options that aren't a valid combination with the current state.
                 * — After setting the clicked attr, auto-heal OTHER attrs if needed so the
                 *   final combination resolves to a real SKU (prefer in-stock).
                 */
                selectAttr(attrName, id, value) {
                    if (!this.isOptionValid(attrName, id)) {
                        console.warn('[Product] Ignored click on invalid option:', attrName, id);
                        return;
                    }

                    this.selectedAttrs[attrName] = {
                        id,
                        value
                    };

                    // If the new combination doesn't fully match a SKU, fix up the other attrs.
                    if (!this.findMatchingSku(this.selectedAttrs)) {
                        this.healOtherAttrs(attrName);
                    }

                    this.updateMatch();
                    console.log('[Product] Attr selected:', attrName, value, '→ sku:', this.currentSkuId);
                },

                /**
                 * Recompute the matched SKU (price/mrp/id/stock) from selectedAttrs.
                 * If nothing matches (shouldn't happen after heal), fall back gracefully.
                 */
                updateMatch() {
                    const match = this.findMatchingSku(this.selectedAttrs);

                    if (match) {
                        this.currentPrice = match.price;
                        this.currentMrp = match.mrp;
                        this.currentSkuId = match.id;
                        this.currentInStock = !!match.in_stock;
                        this.currentTaxType = match.tax_type || 'exclusive';
                        return;
                    }

                    // No full match — keep previous price but mark out of stock to be safe.
                    // Disables cart actions so we never sell a non-existent variant.
                    console.warn('[Product] No SKU matched selectedAttrs — buttons disabled.', this.selectedAttrs);
                    this.currentSkuId = null;
                    this.currentInStock = false;
                },

                /**
                 * Find a SKU whose `values` map equals the given attribute selection.
                 * All selected attrs must match; unmatched attrs mean no full match.
                 */
                findMatchingSku(attrs) {
                    const names = this.attrOrder.length ? this.attrOrder : Object.keys(attrs);
                    const preferInStock = this.skus.filter(s => s.in_stock);
                    const pools = [preferInStock, this.skus]; // prefer in-stock, fall back to any

                    for (const pool of pools) {
                        for (const sku of pool) {
                            let ok = true;
                            for (const n of names) {
                                if (!(n in attrs)) {
                                    ok = false;
                                    break;
                                }
                                if (sku.values[n] !== attrs[n].id) {
                                    ok = false;
                                    break;
                                }
                            }
                            if (ok) return sku;
                        }
                    }
                    return null;
                },

                /**
                 * After the user picks (attrName → id), adjust OTHER attribute selections
                 * so the combination resolves to a real SKU. Prefers in-stock matches.
                 */
                healOtherAttrs(lockedAttr) {
                    // Candidate SKUs that satisfy the locked attribute.
                    const lockedId = this.selectedAttrs[lockedAttr]?.id;
                    const candidates = this.skus.filter(s => s.values[lockedAttr] === lockedId);
                    if (candidates.length === 0) return;

                    // Sort: in-stock first.
                    candidates.sort((a, b) => (b.in_stock === true) - (a.in_stock === true));

                    // Pick the candidate whose values differ LEAST from current selection.
                    const current = this.selectedAttrs;
                    let best = candidates[0];
                    let bestDiff = Infinity;
                    for (const sku of candidates) {
                        let diff = 0;
                        for (const n of this.attrOrder) {
                            if (n === lockedAttr) continue;
                            if (current[n] && sku.values[n] !== current[n].id) diff++;
                        }
                        if (diff < bestDiff) {
                            best = sku;
                            bestDiff = diff;
                            if (diff === 0) break;
                        }
                    }

                    for (const [n, vid] of Object.entries(best.values)) {
                        if (n === lockedAttr) continue;
                        const label = this.lookupValueName(n, vid);
                        this.selectedAttrs[n] = {
                            id: vid,
                            value: label
                        };
                    }
                },

                /**
                 * Partial-match validity check.
                 *
                 * An option (attrName=valueId) is VALID if it appears in at least one SKU
                 * in the product's SKU list.  We intentionally do NOT filter by the other
                 * currently-selected attributes here because that causes "diagonal" combos
                 * (e.g. Small+Plastic / Large+Ceramic) to disable perfectly reachable
                 * options.  `healOtherAttrs()` already reconciles the other attrs after
                 * the user makes a selection, so the only thing we need to confirm at
                 * click-guard time is that the value actually exists somewhere.
                 *
                 * An option is DISABLED (returns false) only when it does not exist in
                 * any SKU at all — i.e. it was probably removed from the catalogue after
                 * the page was last rebuilt.
                 */
                isOptionValid(attrName, valueId) {
                    return this.skus.some(sku => sku.values[attrName] === valueId);
                },

                /**
                 * True when the option exists but every SKU that has it is out of stock.
                 * Used for the "line-through" / strikethrough visual — the button remains
                 * clickable (healOtherAttrs will still resolve to the best available match)
                 * but the user can see upfront that stock is limited.
                 *
                 * We also narrow by the OTHER currently-selected attrs so the OOS indicator
                 * reflects the actual combination the user is building towards, not the
                 * aggregate across all size/color combinations in the catalogue.
                 * If no narrowed match exists we fall back to the full-SKU aggregate.
                 */
                isOptionOutOfStock(attrName, valueId) {
                    // Narrow: SKUs with this value that also satisfy other selected attrs
                    const others = Object.entries(this.selectedAttrs).filter(([n]) => n !== attrName);
                    let pool = this.skus.filter(sku => {
                        if (sku.values[attrName] !== valueId) return false;
                        return others.length === 0 ||
                            others.some(([n, sel]) => sku.values[n] === sel.id);
                    });

                    // Fallback: no narrowed match → check across all SKUs with this value
                    if (!pool.length) {
                        pool = this.skus.filter(sku => sku.values[attrName] === valueId);
                    }

                    return pool.length > 0 && pool.every(sku => !sku.in_stock);
                },

                playYoutube(ytId) {
                    this.youtubeActive = ytId;
                    // Don't null activeImage — keep it for when video is closed
                    console.log('[Product] YouTube playing:', ytId);
                },

                addToCart() {
                    if (!this.currentSkuId) {
                        BizAlert?.toast('Please select a variant', 'error') || alert('Please select a variant');
                        return;
                    }
                    if (!this.currentInStock) {
                        BizAlert?.toast('This variant is out of stock', 'error') || alert('This variant is out of stock');
                        return;
                    }

                    // Build variant label from selected attrs
                    const variantLabel = Object.values(this.selectedAttrs)
                        .map(a => a.value).join(' / ');

                    window.addToCart(
                        @json($product->id),
                        this.currentSkuId,
                        '{{ addslashes($product->name) }}',
                        variantLabel,
                        this.currentPrice,
                        "{{ $firstImg ? asset('storage/' . $firstImg) : asset('assets/defaults/product.svg') }}",
                        this.qty,
                    );

                    console.log('[Product] Added to cart:', this.currentSkuId, 'qty:', this.qty);
                    // ── Toast notification ──
                    if (window.__alpineCart) {
                        window.__alpineCart.showToast('{{ addslashes($product->name) }}');
                    }
                },
                buyNow() {
                    if (!this.currentSkuId) {
                        alert('Please select a variant');
                        return;
                    }
                    if (!this.currentInStock) {
                        alert('This variant is out of stock');
                        return;
                    }

                    const variantLabel = Object.values(this.selectedAttrs)
                        .map(a => a.value).join(' / ');

                    // ── Clear cart and add only this item ──
                    window.clearCart();
                    window.addToCart(
                        @json($product->id),
                        this.currentSkuId,
                        '{{ addslashes($product->name) }}',
                        variantLabel,
                        this.currentPrice,
                        "{{ $firstImg ? asset('storage/' . $firstImg) : asset('assets/defaults/product.svg') }}",
                        1, // always 1 for buy now
                    );

                    // ── Open drawer directly on checkout view ──
                    if (window.__alpineCart) {
                        window.__alpineCart.cartView = 'checkout';
                        window.__alpineCart.cartOpen = true;
                        window.__alpineCart.syncFromStorage();
                    }

                    console.log('[Product] Buy Now:', this.currentSkuId);
                },

                // ── Stop whatever is currently playing and reset state ──
                stopSpeaking() {
                    if (this.audioPlayer) {
                        this.audioPlayer.pause();
                        this.audioPlayer.onended = null;
                        this.audioPlayer.onerror = null;
                        this.audioPlayer = null;
                    }
                    this.speakingKey = null;
                },

                async toggleSpeak(index) {
                    // Same section tapped again — stop.
                    if (this.speakingKey === index) {
                        this.stopSpeaking();
                        return;
                    }

                    // Already fetching this one — ignore repeat taps.
                    if (this.loadingKey === index) return;

                    // Different section tapped — stop the old one first.
                    this.stopSpeaking();

                    this.loadingKey = index;

                    try {
                        const response = await fetch(TTS_ENDPOINT, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ??
                                    '',
                            },
                            body: JSON.stringify({
                                product_id: TTS_PRODUCT_ID,
                                index: index,
                                lang: this.getSpeechLang(),
                            }),
                        });

                        const data = await response.json();

                        if (!response.ok || !data.success || !data.url) {
                            throw new Error(data.message || 'Audio unavailable.');
                        }

                        // The user may have tapped another section while this
                        // request was in flight — discard a stale response.
                        if (this.loadingKey !== index) return;

                        const player = new Audio(data.url);

                        player.onended = () => {
                            if (this.speakingKey === index) this.stopSpeaking();
                        };

                        player.onerror = () => {
                            if (this.speakingKey === index) this.stopSpeaking();
                        };

                        this.audioPlayer = player;
                        this.speakingKey = index;

                        // play() rejects if the browser blocks autoplay. This is
                        // user-initiated so it should not happen, but the promise
                        // must still be handled or it surfaces as unhandled.
                        await player.play().catch(() => this.stopSpeaking());

                    } catch (error) {
                        console.error('[Speak] Failed:', error);

                        if (typeof BizAlert !== 'undefined') {
                            BizAlert.toast('Could not play audio for this section.', 'error');
                        } else if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Audio Unavailable',
                                text: 'Could not play audio for this section. Please try again.',
                            });
                        }

                        this.stopSpeaking();
                    } finally {
                        if (this.loadingKey === index) this.loadingKey = null;
                    }
                },
                getSpeechLang() {
                    // Read Google Translate cookie — format: /en/hi or /auto/gu
                    const cookie = document.cookie
                        .split('; ')
                        .find(row => row.startsWith('googtrans='));

                    if (!cookie) return 'en-IN'; // default

                    const langCode = cookie.split('=')[1]?.split('/')[2] ?? 'en';

                    // Map Google Translate codes → BCP-47 speech codes
                    const langMap = {
                        'en': 'en-IN',
                        'hi': 'hi-IN',
                        'gu': 'gu-IN',
                        'mr': 'mr-IN',
                        'ta': 'ta-IN',
                        'te': 'te-IN',
                        'bn': 'bn-IN',
                        'kn': 'kn-IN',
                        'pa': 'pa-IN',
                        'ar': 'ar-SA',
                        'zh-CN': 'zh-CN',
                        'fr': 'fr-FR',
                        'de': 'de-DE',
                        'es': 'es-ES',
                    };

                    const resolved = langMap[langCode] ?? 'en-IN';
                    console.log('[Speak] Language resolved:', langCode, '→', resolved);
                    return resolved;
                },

                // Audio keeps playing after navigation unless it is explicitly
                // stopped, which is jarring on SPA-style page transitions.
                initSpeakCleanup() {
                    window.addEventListener('pagehide', () => this.stopSpeaking());
                },

                shareProduct() {
                    const url = window.location.href;
                    const title = '{{ addslashes($product->name) }}';
                    const text = '{{ addslashes($product->short_description ?? $product->name) }}';

                    // ── Native share sheet (mobile browsers) ──
                    if (navigator.share) {
                        navigator.share({
                                title,
                                text,
                                url
                            })
                            .catch(err => {
                                // User cancelled — not an error
                                if (err.name !== 'AbortError') {
                                    console.error('[Share] Native share failed:', err);
                                }
                            });
                        return;
                    }

                    // ── Clipboard fallback (desktop browsers) ──
                    if (navigator.clipboard?.writeText) {
                        navigator.clipboard.writeText(url)
                            .then(() => {
                                BizAlert?.toast('Link copied to clipboard!', 'success') ??
                                    console.log('[Share] Copied:', url);
                            })
                            .catch(err => {
                                console.error('[Share] Clipboard failed:', err);
                                this._legacyCopy(url);
                            });
                        return;
                    }
                    this._legacyCopy(url);
                },

                _legacyCopy(url) {
                    try {
                        const el = document.createElement('textarea');
                        el.value = url;
                        el.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0;';
                        document.body.appendChild(el);
                        el.select();
                        document.execCommand('copy');
                        document.body.removeChild(el);
                        BizAlert?.toast('Link copied to clipboard!', 'success') ??
                            console.log('[Share] Legacy copy done:', url);
                    } catch (err) {
                        console.error('[Share] All copy methods failed:', err);
                        BizAlert?.toast('Could not copy link. Please copy from the address bar.', 'error');
                    }
                },
            }
        }

        // ── "Also Add" cross-sell section — direct add-to-cart with the product's default SKU.
        // NOTE: Multi-variant addon products currently add their first in-stock SKU directly.
        // Future upgrade point: swap addAddon() for a small variant-picker popover if needed.
        function addonSection() {
            return {
                activeTab: '{{ addslashes($addonGroups->keys()->first() ?? '') }}',
                addedIds: [],
                isAdding: null,

                addAddon(productId, skuId, name, price, image) {
                    if (!skuId) {
                        BizAlert?.toast('This item is currently out of stock', 'error') || alert(
                            'This item is out of stock');
                        return;
                    }

                    this.isAdding = productId;
                    window.addToCart(productId, skuId, name, '', price, image, 1);
                    this.isAdding = null;

                    // Brief "✓ Added" confirmation on the button itself
                    this.addedIds.push(productId);
                    setTimeout(() => {
                        this.addedIds = this.addedIds.filter(id => id !== productId);
                    }, 1500);

                    if (window.__alpineCart) {
                        window.__alpineCart.showToast(name);
                    }
                },
            }
        }
    </script>
@endpush
