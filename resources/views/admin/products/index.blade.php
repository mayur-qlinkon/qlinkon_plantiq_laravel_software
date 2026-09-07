@extends ('layouts.admin')

@section('title', 'Products Management')
@section('header-title')
    <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Products</h1>
@endsection
@section('content')
    <div class="space-y-6 pb-10" x-data="productTable()">
        {{-- Alerts --}}
        @if (session('success'))
            <div
                class="mb-6 flex items-center gap-2 rounded-xl border border-green-100 bg-green-50 px-5 py-4 text-sm font-bold text-green-700 shadow-sm">
                <i data-lucide="check-circle" class="h-5 w-5"></i> {{ session('success') }}
            </div>
            <script>
                document.addEventListener("DOMContentLoaded", () => BizAlert.toast("{{ session('success') }}", "success"));
            </script>
        @endif
        @if (session('error'))
            <div
                class="mb-6 flex items-center gap-2 rounded-xl border border-red-100 bg-red-50 px-5 py-4 text-sm font-bold text-red-700 shadow-sm">
                <i data-lucide="alert-octagon" class="h-5 w-5"></i> {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700 shadow-sm">
                <div class="mb-2 flex items-center gap-2 font-bold">
                    <i data-lucide="alert-triangle" class="h-5 w-5"></i> We found a few issues:
                </div>
                <ul class="list-inside list-disc space-y-1 pl-7 text-[13px] font-medium">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-4 flex justify-end">
            <form id="product-filter-form" action="{{ route('admin.products.index') }}" method="GET"
                @submit.prevent="submitForm" @change="submitForm"
                class="flex w-full flex-col flex-wrap items-stretch gap-3 sm:flex-row sm:items-center md:w-auto">
                {{-- Search Bar & Clear Group --}}
                <div class="flex w-full gap-2 sm:w-auto sm:flex-1 lg:w-[320px] lg:flex-none">
                    <div class="relative flex-1">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <i data-lucide="search" class="h-4 w-4 text-gray-400"></i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}"
                            @input.debounce.400ms="submitForm" placeholder="Search Product Name or SKU..."
                            class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-xl border border-gray-200 bg-white py-2.5 pr-4 pl-10 text-sm text-gray-700 outline-none placeholder:text-gray-400 transition-all focus:ring-2" />
                    </div>

                    <button type="button" @click="clearFilters" x-show="hasActiveFilters" x-cloak
                        class="flex shrink-0 items-center gap-1.5 rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-bold text-red-600 transition-colors hover:bg-red-100 hover:text-red-700"
                        title="Clear Filters">
                        <i data-lucide="x-circle" class="h-4 w-4"></i> Clear
                    </button>
                </div>

                {{-- Premium Upgraded Category Filter Custom Select Component --}}
                <div class="w-full shrink-0 sm:w-auto sm:flex-1 lg:w-[220px] lg:flex-none">
                    <x-custom-select name="category_id" placeholder="All Categories" :options="collect($categories)->pluck('name', 'id')->toArray()"
                        selected="{{ request('category_id') }}" />
                </div>
            </form>
        </div>

        <div id="products-list-container"
            class="flex flex-col overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm"
            @click="handlePaginationClick($event)">
            <div
                class="flex flex-col gap-4 border-b border-gray-100 bg-white px-4 py-4 sm:px-6 md:flex-row md:items-center md:justify-between">
                <h2 class="text-sm font-bold tracking-widest text-gray-500 uppercase">
                    Product Catalog
                    <span class="ml-1 text-sm font-medium text-gray-400">({{ $products->total() }} items)</span>
                </h2>

                <div class="flex w-full flex-wrap items-center gap-2 md:w-auto md:justify-end">
                    {{-- Quick Setup Actions --}}
                    @if (has_permission('categories.view'))
                        <a href="{{ route('admin.categories.index') }}"
                            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-bold text-gray-800 shadow-sm transition-all hover:border-[var(--brand-500)] hover:text-[var(--brand-600)] hover:shadow">
                            <i data-lucide="folder" class="h-4 w-4 text-[var(--brand-600)]"></i>
                            Categories
                        </a>
                    @endif



                    @if (has_permission('units.view'))
                        <a href="{{ route('admin.units.index') }}"
                            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-bold text-gray-800 shadow-sm transition-all hover:border-[var(--brand-500)] hover:text-[var(--brand-600)] hover:shadow">
                            <i data-lucide="ruler" class="h-4 w-4 text-[var(--brand-600)]"></i>
                            Units
                        </a>
                    @endif
                    @if (has_permission('attributes.view'))
                        <a href="{{ route('admin.attributes.index') }}"
                            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-bold text-gray-800 shadow-sm transition-all hover:border-[var(--brand-500)] hover:text-[var(--brand-600)] hover:shadow">
                            <i data-lucide="sliders-horizontal" class="h-4 w-4 text-[var(--brand-600)]"></i>
                            Attributes
                        </a>
                    @endif
                    {{-- Two imports live here, so the launcher renders as a
                         dropdown. triggerClass mirrors the Quick Setup buttons
                         above so the group reads as one row of equals. --}}
                    <x-import-modal :types="['product_with_skus', 'product_images']" label="Import"
                        triggerClass="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-bold text-gray-800 shadow-sm transition-all hover:border-[var(--brand-500)] hover:text-[var(--brand-600)] hover:shadow" />

                    {{-- Bulk Actions --}}
                    <button x-show="selected.length > 0" x-transition.opacity x-cloak @click="confirmBulkDelete()"
                        class="flex w-full items-center justify-center gap-1.5 rounded-lg bg-[#ef4444] px-4 py-2.5 text-sm font-bold whitespace-nowrap text-white shadow-sm transition-colors hover:bg-red-600 md:w-auto">
                        <i data-lucide="trash-2" class="h-4 w-4"></i> Bulk Delete (<span x-text="selected.length"></span>)
                    </button>



                    {{-- Add Product CTA --}}
                    @if (check_plan_limit('products'))
                        @if (has_permission('products.create'))
                            <a href="{{ route('admin.products.create') }}"
                                class="bg-brand-500 hover:bg-brand-600 flex w-full items-center justify-center gap-1.5 rounded-lg px-4 py-2.5 text-sm font-bold whitespace-nowrap text-white shadow-sm transition-colors md:w-auto">
                                <i data-lucide="plus" class="h-4 w-4"></i> Add Product
                            </a>
                        @endif
                    @else
                        <span
                            class="inline-flex items-center gap-1 rounded-full border border-red-100 bg-red-50 px-3 py-1.5 text-xs font-bold text-red-600">
                            <i data-lucide="alert-triangle" class="h-3.5 w-3.5"></i>
                            Product Limit Reached
                        </span>
                    @endif
                </div>
            </div>

            {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
            <div class="hidden min-h-[400px] overflow-x-auto md:block">
                <table class="w-full min-w-[1000px] text-left text-sm whitespace-nowrap">
                    <thead
                        class="border-b border-gray-100 bg-[#f8fafc] text-[11px] font-bold tracking-wider text-gray-400 uppercase">
                        <tr
                            class="border-b border-gray-100 bg-[#f8fafc] text-[11px] font-bold tracking-wider text-gray-400 uppercase">
                            <th class="w-10 px-6 py-4">
                                <input type="checkbox"
                                    @change="selected = $event.target.checked ? [{{ $products->pluck('id')->join(',') }}] : []"
                                    :checked="selected.length > 0 && selected.length === {{ $products->count() }}"
                                    class="h-4 w-4 cursor-pointer rounded border-gray-300 text-[#108c2a] focus:ring-[#108c2a]" />
                            </th>
                            <th class="px-6 py-4">IMAGE</th>
                            <th class="px-6 py-4">NAME</th>
                            <th class="px-6 py-4">CATEGORY</th>
                            <th class="px-6 py-4">UNIT</th>
                            <th class="px-6 py-4">IN STOCK</th>
                            <th class="px-6 py-4">PRICE</th>
                            <th class="px-6 py-4">CREATED ON</th>
                            <th class="px-6 py-4 text-right">ACTION</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($products as $product)
                            <tr
                                class="border-b border-gray-50 text-[13px] text-gray-600 transition-colors hover:bg-gray-50/50">
                                {{-- Checkbox --}}
                                <td class="px-6 py-4">
                                    <input type="checkbox" x-model.number="selected" value="{{ $product->id }}"
                                        class="h-4 w-4 cursor-pointer rounded border-gray-300 text-[#108c2a] focus:ring-[#108c2a]" />
                                </td>

                                {{-- Product Image --}}
                                <td class="px-6 py-4">
                                    <a href="{{ route('admin.products.show', $product->id) }}"
                                        class="block h-10 w-10 overflow-hidden rounded border border-gray-200 bg-gray-50 transition hover:ring-2 hover:ring-indigo-200">
                                        <img src="{{ $product->primary_image_url }}" alt="Img"
                                            onerror="this.onerror=null; this.src='/assets/defaults/product.svg';"
                                            class="h-full w-full object-cover" />
                                    </a>
                                </td>

                                {{-- Math Setup --}}
                                @php
                                    $hasSku = $product->skus->isNotEmpty();
                                    $minPrice = $hasSku ? $product->skus->min('price') ?? 0 : 0;
                                    $maxPrice = $hasSku ? $product->skus->max('price') ?? 0 : 0;
                                    $totalVariants = $product->skus->count();
                                    $totalStock = $hasSku
                                        ? $product->skus->sum(fn($sku) => $sku->stocks->sum('qty'))
                                        : 0;
                                @endphp

                                {{-- Name --}}
                                <td class="px-6 py-4">
                                    <a href="{{ route('admin.products.show', $product->id) }}"
                                        class="hover:text-brand-500 font-semibold text-gray-800 transition-colors">
                                        {{ $product->name }}
                                    </a>
                                </td>

                                {{-- Category (Replacing Brand) --}}
                                <td class="px-6 py-4 text-gray-500">{{ $product->category->name ?? 'N/A' }}</td>

                                {{-- Product Unit --}}
                                <td class="px-6 py-4 text-gray-500">{{ $product->productUnit->short_name ?? 'Pc' }}</td>

                                {{-- In Stock --}}
                                <td
                                    class="px-6 py-4 font-medium {{ $totalStock > 0 ? 'text-[#108c2a]' : 'text-red-500' }}">
                                    {{ $totalStock }}
                                </td>

                                {{-- Price --}}
                                <td class="px-6 py-4 font-medium text-gray-700">
                                    @if ($minPrice == $maxPrice)
                                        ₹{{ number_format($minPrice, 2) }}
                                    @else
                                        ₹{{ number_format($minPrice, 2) }} - ₹{{ number_format($maxPrice, 2) }}
                                    @endif
                                </td>

                                {{-- Created On --}}
                                <td class="px-6 py-4 text-gray-500">{{ $product->created_at->format('d M, Y') }}</td>

                                {{-- Action --}}
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3 text-gray-400">
                                        @if (has_permission('products.update'))
                                            <a href="{{ route('admin.products.edit', $product->id) }}"
                                                class="transition-colors hover:text-blue-500" title="Edit">
                                                <i data-lucide="edit" class="h-4 w-4"></i>
                                            </a>
                                        @endif

                                        @if (has_permission('products.duplicate'))
                                            <form action="{{ route('admin.products.duplicate', $product->id) }}"
                                                method="POST"
                                                @submit.prevent="confirmDuplicate($event.target, '{{ addslashes($product->name) }}')"
                                                class="m-0 inline-block p-0">
                                                @csrf
                                                <button type="submit" class="transition-colors hover:text-amber-500"
                                                    title="Duplicate">
                                                    <i data-lucide="copy" class="h-4 w-4"></i>
                                                </button>
                                            </form>
                                        @endif

                                        @if (has_permission('products.delete'))
                                            <form action="{{ route('admin.products.destroy', $product->id) }}"
                                                method="POST" @submit.prevent="confirmDelete($event.target)"
                                                class="m-0 inline-block p-0">
                                                @csrf
                                                @method ('DELETE')
                                                <button type="submit" class="transition-colors hover:text-red-500"
                                                    title="Delete">
                                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <i data-lucide="package-open" class="mb-3 h-12 w-12 opacity-20"></i>
                                        <p class="font-medium text-gray-500">No products found in your inventory.</p>
                                        <a href="{{ route('admin.products.create') }}"
                                            class="mt-2 font-bold text-[#108c2a] hover:underline">Add your first
                                            product</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 📱 MOBILE VIEW (CARDS) --}}
            <div class="space-y-3 bg-gray-50/60 p-3 md:hidden">
                @forelse ($products as $product)
                    @php
                        $hasSku = $product->skus->isNotEmpty();
                        $minPrice = $hasSku ? $product->skus->min('price') ?? 0 : 0;
                        $maxPrice = $hasSku ? $product->skus->max('price') ?? 0 : 0;
                        $totalVariants = $product->skus->count();
                        $totalStock = $hasSku ? $product->skus->sum(fn($sku) => $sku->stocks->sum('qty')) : 0;
                    @endphp
                    <div
                        class="flex flex-col gap-3 rounded-xl border border-gray-200/80 bg-white p-4 shadow-sm transition-all hover:shadow-md">
                        {{-- Header: Checkbox, Image, Name, Category --}}
                        <div class="flex items-start gap-3">
                            <div class="pt-1">
                                <input type="checkbox" x-model.number="selected" value="{{ $product->id }}"
                                    class="h-4 w-4 cursor-pointer rounded border-gray-300 text-[#108c2a] focus:ring-[#108c2a]" />
                            </div>
                            <a href="{{ route('admin.products.show', $product->id) }}"
                                class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 transition hover:ring-2 hover:ring-indigo-200">
                                <img src="{{ $product->primary_image_url }}" alt="Img"
                                    class="h-full w-full object-cover" />
                            </a>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('admin.products.show', $product->id) }}"
                                    class="block truncate text-[14px] leading-tight font-bold text-gray-900 transition hover:text-indigo-600">
                                    {{ $product->name }}
                                </a>
                                <p class="mt-0.5 text-[11px] font-medium text-gray-500">
                                    {{ $product->category->name ?? 'Uncategorized' }} •
                                    {{ $product->productUnit->short_name ?? 'Pc' }}
                                </p>
                            </div>
                        </div>

                        {{-- Stats: Price, Stock, Variants --}}
                        <div
                            class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50/80 px-3 py-2.5">
                            <div>
                                <p class="mb-0.5 text-[9px] font-bold tracking-wider text-gray-400 uppercase">Price</p>
                                <p class="text-[12px] font-bold text-gray-800">
                                    @if ($minPrice == $maxPrice)
                                        ₹{{ number_format($minPrice, 2) }}
                                    @else
                                        ₹{{ number_format($minPrice, 2) }} - ₹{{ number_format($maxPrice, 2) }}
                                    @endif
                                </p>
                            </div>
                            <div class="text-center">
                                <p class="mb-0.5 text-[9px] font-bold tracking-wider text-gray-400 uppercase">Type</p>
                                <p class="text-[11px] font-bold text-gray-600">
                                    {{ $product->type === 'variable' ? $totalVariants . ' Variants' : 'Single' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="mb-0.5 text-[9px] font-bold tracking-wider text-gray-400 uppercase">Stock</p>
                                <p
                                    class="text-[12px] font-black {{ $totalStock > 0 ? 'text-[#108c2a]' : 'text-red-500' }}">
                                    {{ $totalStock }}
                                </p>
                            </div>
                        </div>

                        {{-- Actions & Created Date --}}
                        <div class="mt-1 flex items-center justify-between border-t border-gray-50 pt-2">
                            <span class="text-[10px] font-medium text-gray-400">Added:
                                {{ $product->created_at->format('d M, y') }}</span>
                            <div class="flex items-center justify-end gap-1.5">
                                @if (has_permission('products.view'))
                                    <a href="{{ route('admin.products.show', $product->id) }}"
                                        class="block flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-indigo-600 transition-colors hover:bg-blue-100 sm:hidden"
                                        title="View">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </a>
                                @endif
                                @if (has_permission('products.update'))
                                    <a href="{{ route('admin.products.edit', $product->id) }}"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 transition-colors hover:bg-blue-100"
                                        title="Edit">
                                        <i data-lucide="edit" class="h-4 w-4"></i>
                                    </a>
                                @endif

                                @if (has_permission('products.duplicate'))
                                    <form action="{{ route('admin.products.duplicate', $product->id) }}" method="POST"
                                        @submit.prevent="confirmDuplicate($event.target, '{{ addslashes($product->name) }}')"
                                        class="m-0 inline-block p-0">
                                        @csrf
                                        <button type="submit"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 transition-colors hover:bg-amber-100"
                                            title="Duplicate">
                                            <i data-lucide="copy" class="h-4 w-4"></i>
                                        </button>
                                    </form>
                                @endif

                                @if (has_permission('products.delete'))
                                    <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST"
                                        @submit.prevent="confirmDelete($event.target)" class="m-0 inline-block p-0">
                                        @csrf
                                        @method ('DELETE')
                                        <button type="submit"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-500 transition-colors hover:bg-red-100"
                                            title="Delete">
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-white p-8 text-center text-gray-400">
                        <div class="flex flex-col items-center justify-center">
                            <i data-lucide="package-open" class="mb-3 h-12 w-12 opacity-20"></i>
                            <p class="text-sm font-medium text-gray-500">No products found in your inventory.</p>
                            <a href="{{ route('admin.products.create') }}"
                                class="mt-2 text-sm font-bold text-[#108c2a] hover:underline">Add your first product</a>
                        </div>
                    </div>
                @endforelse
            </div>

            @if (method_exists($products, 'links') && $products->hasPages())
                <div
                    class="flex flex-col items-center justify-between gap-4 border-t border-gray-100 bg-gray-50 px-4 py-4 sm:flex-row sm:px-6">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function productTable() {
            return {
                selected: [], // Array to hold selected product IDs

                // SPA-Safe Search & Filter Logic
                hasActiveFilters: false,

                init() {
                    this.checkActiveFilters();
                    // Expose safely for external components if needed
                    window.submitProductForm = () => this.submitForm();
                },

                handlePaginationClick(e) {
                    const pageLink = e.target.closest('a[href*="?page="]');
                    if (pageLink) {
                        e.preventDefault();
                        this.fetchResults(pageLink.href);
                    }
                },

                checkActiveFilters() {
                    const form = document.getElementById("product-filter-form");
                    if (!form) return;
                    const formData = new FormData(form);
                    this.hasActiveFilters = [...formData.entries()].some(([, v]) => v && String(v).trim() !== "");
                },

                submitForm() {
                    const form = document.getElementById("product-filter-form");
                    if (!form) return;
                    const url = new URL(form.action);
                    new FormData(form).forEach((v, k) => {
                        if (v) url.searchParams.set(k, v);
                    });

                    this.fetchResults(url.toString());
                },

                clearFilters() {
                    const form = document.getElementById("product-filter-form");
                    if (form) {
                        form.querySelectorAll('input[type="text"], input[type="search"], select').forEach((el) => {
                            el.value = "";
                            // Dispatch standard tracking change events so custom selectors reset to default placeholder values
                            el.dispatchEvent(
                                new Event("change", {
                                    bubbles: true,
                                }),
                            );
                        });
                        this.fetchResults(form.action);
                    }
                },

                fetchResults(url) {
                    const targetContainer = document.getElementById("products-list-container");
                    if (!targetContainer) return;

                    targetContainer.style.opacity = "0.5";
                    targetContainer.style.pointerEvents = "none";

                    // Clear selections when fetching new data
                    this.selected = [];

                    fetch(url, {
                            headers: {
                                "X-Requested-With": "XMLHttpRequest",
                            },
                        })
                        .then((res) => res.text())
                        .then((html) => {
                            const doc = new DOMParser().parseFromString(html, "text/html");
                            const newContainer = doc.getElementById("products-list-container");

                            if (newContainer) {
                                targetContainer.innerHTML = newContainer.innerHTML;
                            }

                            targetContainer.style.opacity = "1";
                            targetContainer.style.pointerEvents = "auto";
                            window.history.pushState({}, "", url);

                            this.checkActiveFilters();

                            if (typeof lucide !== "undefined") lucide.createIcons();
                        })
                        .catch(() => {
                            targetContainer.style.opacity = "1";
                            targetContainer.style.pointerEvents = "auto";
                        });
                },

                // New Bulk Delete Function
                confirmBulkDelete() {
                    BizAlert.confirm(
                        "Delete Selected Products?",
                        `You are about to archive ${this.selected.length} products. This action cannot be undone.`,
                        "Yes, Archive Them",
                    ).then(async (result) => {
                        if (result.isConfirmed) {
                            BizAlert.loading("Archiving...");

                            try {
                                const response = await fetch("{{ route('admin.products.bulk-delete') }}", {
                                    method: "POST",
                                    headers: {
                                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                        "Content-Type": "application/json",
                                        Accept: "application/json",
                                    },
                                    body: JSON.stringify({
                                        ids: this.selected,
                                    }),
                                });

                                const data = await response.json();

                                if (data.success) {
                                    // Let the session flash message handle the success alert on reload
                                    window.location.reload();
                                } else {
                                    BizAlert.toast(data.message || "Failed to delete products", "error");
                                }
                            } catch (error) {
                                console.error(error);
                                BizAlert.toast("Network error. Try again.", "error");
                            }
                        }
                    });
                },

                confirmDelete(form) {
                    BizAlert.confirm(
                        "Delete Product?",
                        "This product will be archived. Historical sales data will remain intact.",
                        "Yes, Archive it",
                    ).then((result) => {
                        if (result.isConfirmed) {
                            BizAlert.loading("Archiving...");
                            form.submit();
                        }
                    });
                },

                confirmDuplicate(form, productName) {
                    BizAlert.confirm(
                        "Duplicate Product?",
                        `Are you sure you want to duplicate "${productName}" as a new draft item?`,
                        "Yes, Duplicate",
                    ).then((result) => {
                        if (result.isConfirmed) {
                            BizAlert.loading("Duplicating...");
                            form.submit();
                        }
                    });
                },

                // The fully functional AJAX Toggle!
                async toggleStatus(productId, isActive) {
                    try {
                        const response = await fetch(`/admin/products/${productId}/toggle-status`, {
                            method: "PATCH",
                            headers: {
                                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                "Content-Type": "application/json",
                                Accept: "application/json",
                            },
                        });

                        const data = await response.json();

                        if (data.success) {
                            const statusText = data.is_active ? "Activated" : "Deactivated";
                            BizAlert.toast(`Product has been ${statusText}`, "success");
                        } else {
                            BizAlert.toast("Failed to update status", "error");
                            // Revert the toggle visually if it failed on the server
                            event.target.checked = !isActive;
                        }
                    } catch (error) {
                        console.error(error);
                        BizAlert.toast("Network error. Try again.", "error");
                        event.target.checked = !isActive;
                    }
                },
            };
        }
    </script>
@endpush
