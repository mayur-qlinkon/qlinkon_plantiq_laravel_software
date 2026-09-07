@extends ('layouts.admin')

@section ('title', 'Label Printing')

@section ('header-title')
    <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Labels</h1>
@endsection

@section ('content')
    <div class="space-y-6 pb-10" x-data="labelGenerator()">
        <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            <div>
                {{-- <h1 class="text-2xl font-bold text-[#212538] tracking-tight">Label Print</h1> --}}
                <p class="mt-1 text-sm font-medium text-gray-500">Generate QR & Barcode labels — select products, configure, preview and print</p>
            </div>
            <div
                class="flex w-full items-center gap-2 rounded-xl border border-gray-200 bg-white p-1.5 shadow-sm md:w-auto"
            >
                <button
                    @click="setLabelType('qr')"
                    :class="labelType === 'qr'
                        ? 'bg-gray-100 text-gray-800 border-gray-200'
                        : 'bg-transparent text-gray-500 hover:text-gray-700 border-transparent'"
                    class="flex flex-1 items-center justify-center gap-2 rounded-lg border px-4 py-1.5 text-sm font-bold transition-all md:flex-none"
                >
                    <i data-lucide="qr-code" class="h-4 w-4"></i> QR Code
                </button>
                <button
                    @click="setLabelType('barcode')"
                    :class="labelType === 'barcode'
                        ? 'bg-brand-500 text-white border-brand-500 shadow-sm'
                        : 'bg-transparent text-gray-500 hover:text-gray-700 border-transparent'"
                    class="flex flex-1 items-center justify-center gap-2 rounded-lg border px-4 py-1.5 text-sm font-bold transition-all md:flex-none"
                >
                    <i data-lucide="barcode" class="h-4 w-4"></i> Barcode
                </button>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex w-full flex-wrap items-center gap-3">
                {{-- Search Box with stable flex container vertical alignment --}}
                <div class="relative min-w-[250px] flex-1">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                        <i data-lucide="search" class="h-4 w-4 text-gray-400"></i>
                    </div>
                    <input
                        type="text"
                        x-model="search"
                        @keydown.enter="fetchProducts(1)"
                        placeholder="Search by product name, SKU or barcode..."
                        class="w-full rounded-lg border border-gray-200 bg-gray-50/50 py-2.5 pr-4 pl-10 text-sm text-gray-700 transition-all outline-none placeholder:text-gray-400 focus:border-[#108c2a] focus:ring-2 focus:ring-[#108c2a]/20"
                    />
                </div>

                {{-- Upgraded Category Filter using premium custom select component --}}
                <div
                    class="w-full shrink-0 md:w-56"
                    @change="
                        categoryId = $event.target.value;
                        fetchProducts(1);
                    "
                >
                    <x-custom-select
                        name="category_id"
                        placeholder="All Categories"
                        :options="collect($categories)->pluck('name', 'id')->toArray()"
                        selected=""
                    />
                </div>

                <button
                    @click="fetchProducts(1)"
                    class="bg-brand-500 hover:bg-brand-600 flex shrink-0 items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-all"
                >
                    <i data-lucide="search" class="h-4 w-4"></i> Search
                </button>

                <button
                    @click="resetFilters()"
                    class="flex shrink-0 items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-600 transition-colors hover:bg-gray-50 hover:text-gray-800"
                >
                    <i data-lucide="x-circle" class="h-4 w-4"></i> Reset
                </button>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex w-full flex-wrap items-center gap-x-8 gap-y-5">
                {{-- Premium Paper Size Configuration Custom Select --}}
                <div class="flex min-w-[220px] shrink-0 flex-col gap-1.5" @change="cfg.pageSize = $event.target.value">
                    <label
                        class="mb-0.5 flex items-center gap-1.5 text-[11px] font-bold tracking-wider text-gray-500 uppercase"
                    >
                        <i data-lucide="file-text" class="h-3.5 w-3.5"></i> Paper Size
                    </label>
                    <x-custom-select
                        name="page_size"
                        placeholder="Select Paper Size"
                        :options="[
                            'thermal_50x25' => 'Thermal Roll — 50×25 mm',
                            'thermal_3x2' => 'Thermal Roll — 3×2 inch',
                            'thermal_4x3' => 'Thermal Roll — 4×3 inch',
                            'a5'          => 'A5 Sheet',
                            'a4'          => 'A4 Sheet'
                        ]"
                        selected="thermal_50x25"
                    />
                </div>

                <div class="hidden h-10 w-px bg-gray-200 lg:block"></div>

                <template
                    x-for="
                        toggle in
                        [
                            { id: 'showStore', label: 'Store Name' },
                            { id: 'showName', label: 'Product Name' },
                            { id: 'showPrice', label: 'Price' },
                            { id: 'showBorder', label: 'Border' },
                        ]
                    "
                    :key="toggle.id"
                >
                    <div class="flex flex-col gap-2">
                        <span
                            class="text-[11px] font-bold tracking-wider text-gray-500 uppercase"
                            x-text="toggle.label"
                        ></span>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" class="peer sr-only" x-model="cfg[toggle.id]" />
                            <div
                                class="peer h-5 w-10 rounded-full bg-gray-200 peer-checked:bg-[#108c2a] peer-focus:outline-none after:absolute after:top-[2px] after:left-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white"
                            ></div>
                            <span
                                class="ml-2 text-xs font-bold text-gray-600"
                                x-text="cfg[toggle.id] ? 'Show' : 'Hide'"
                            ></span>
                        </label>
                    </div>
                </template>

                <div class="hidden h-10 w-px bg-gray-200 lg:block"></div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold tracking-wider text-gray-500 uppercase">Price Font Size</label>
                    <input
                        type="number"
                        x-model="cfg.fontSize"
                        min="8"
                        max="24"
                        class="w-16 rounded-lg border border-gray-200 px-2 py-1.5 text-center text-sm outline-none focus:border-[#108c2a]"
                    />
                </div>
            </div>
        </div>

        <div
            class="flex flex-col items-center justify-between gap-4 rounded-t-xl border border-b-0 border-gray-200 bg-white px-4 py-3 sm:px-5 md:flex-row"
        >
            <div class="flex items-center gap-3">
                <button
                    @click="selectAll(true)"
                    class="flex items-center gap-1.5 text-xs font-bold text-[#108c2a] hover:underline"
                >
                    <i data-lucide="check-square" class="h-4 w-4"></i> Select All
                </button>
                <span class="text-gray-300">|</span>
                <button
                    @click="selectAll(false)"
                    class="flex items-center gap-1.5 text-xs font-bold text-gray-400 hover:text-gray-600 hover:underline"
                >
                    <i data-lucide="square" class="h-4 w-4"></i> Deselect All
                </button>

                <div
                    x-show="selectedCount > 0"
                    x-cloak
                    class="ml-2 flex items-center gap-1.5 rounded-full border border-[#bce3c6] bg-[#e6f4ea] px-3 py-1 text-xs font-bold text-[#108c2a]"
                >
                    <i data-lucide="check-circle-2" class="h-3.5 w-3.5"></i>
                    <span x-text="selectedCount"></span> selected
                </div>
            </div>

            <div class="flex w-full flex-wrap items-center justify-center gap-3 md:w-auto md:justify-end">
                <div
                    class="flex w-full items-center justify-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 sm:w-auto"
                >
                    <span class="text-xs font-bold text-gray-500">Set all copies:</span>
                    <input
                        type="number"
                        x-model="globalCopy"
                        @change="applyGlobalCopy()"
                        min="1"
                        max="99"
                        class="w-14 rounded border border-gray-300 px-1 py-1 text-center text-xs font-bold outline-none focus:border-[#108c2a]"
                    />
                </div>

                <button
                    @click="triggerPrint(true)"
                    class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-[#6366f1] px-4 py-2 text-sm font-bold text-white shadow-sm transition-colors hover:bg-[#4f46e5] sm:flex-none"
                >
                    <i data-lucide="eye" class="h-4 w-4"></i> Preview
                </button>
                <button
                    @click="downloadPDF()"
                    class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-gray-800 px-4 py-2 text-sm font-bold text-white shadow-sm transition-colors hover:bg-gray-900 sm:flex-none"
                >
                    <i data-lucide="download" class="h-4 w-4"></i> PDF
                </button>
                @if (has_permission('labels.print'))
                    <button
                        @click="triggerPrint(false)"
                        class="bg-brand-500 hover:bg-brand-600 flex flex-1 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition-colors sm:flex-none"
                    >
                        <i data-lucide="printer" class="h-4 w-4"></i> Print
                    </button>
                @endif
            </div>
        </div>

        <div class="overflow-hidden rounded-b-xl border border-gray-200 bg-white shadow-sm">
            {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
            <div class="hidden min-h-[400px] overflow-x-auto md:block">
                <table class="w-full min-w-[850px] text-left text-sm whitespace-nowrap">
                    <thead
                        class="border-b border-gray-200 bg-gray-50 text-[11px] font-bold tracking-wider text-gray-400 uppercase"
                    >
                        <tr>
                            <th class="w-10 px-5 py-4">
                                <input
                                    type="checkbox"
                                    class="h-4 w-4 cursor-pointer rounded border-gray-300 text-[#108c2a] focus:ring-[#108c2a]"
                                    :checked="selectedCount > 0 && selectedCount === products.length"
                                    :indeterminate="selectedCount > 0 && selectedCount < products.length"
                                    @change="selectAll($event.target.checked)"
                                />
                            </th>
                            <th class="w-12 px-4 py-4 text-center">#</th>
                            <th class="px-4 py-4">Product</th>
                            <th class="px-4 py-4 text-center">SKU</th>
                            <th class="px-4 py-4">Category</th>
                            <th class="px-4 py-4 text-right">Price</th>
                            <th class="w-24 px-4 py-4 text-center">Copies</th>
                            <th class="w-24 px-4 py-4 text-center">Label</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr x-show="isLoading" x-cloak>
                            <td colspan="8" class="py-20 text-center">
                                <i data-lucide="loader-2" class="mx-auto mb-3 h-8 w-8 animate-spin text-[#108c2a]"></i>
                                <p class="font-medium text-gray-500">Loading products...</p>
                            </td>
                        </tr>

                        <tr x-show="!isLoading && products.length === 0" x-cloak>
                            <td colspan="8" class="py-20 text-center">
                                <i data-lucide="package-x" class="mx-auto mb-3 h-10 w-10 text-gray-300"></i>
                                <p class="font-medium text-gray-500">No products found.</p>
                            </td>
                        </tr>

                        <template x-for="(product, index) in products" :key="product.unique_id">
                            <tr
                                class="transition-colors hover:bg-gray-50/50"
                                x-show="!isLoading"
                                :class="product._selected ? 'bg-[#f0fdf4]' : ''"
                            >
                                <td class="px-5 py-3">
                                    <input
                                        type="checkbox"
                                        x-model="product._selected"
                                        class="h-4 w-4 cursor-pointer rounded border-gray-300 text-[#108c2a] focus:ring-[#108c2a]"
                                    />
                                </td>
                                <td
                                    class="px-4 py-3 text-center text-xs font-bold text-gray-400"
                                    x-text="(pagination.current_page - 1) * pagination.per_page + index + 1"
                                ></td>
                                <td class="px-4 py-3">
                                    <div
                                        class="text-[13.5px] font-bold text-[#212538]"
                                        x-text="getDisplayName(product)"
                                    ></div>
                                    <div
                                        class="mt-0.5 text-[11px] text-gray-400"
                                        x-show="product.attributes && product.attributes.length > 0"
                                        x-text="formatAttrs(product.attributes)"
                                    ></div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        x-show="product.sku"
                                        class="rounded-md border border-blue-100 bg-blue-50 px-2.5 py-1 font-mono text-[11px] font-bold tracking-wide text-blue-600"
                                        x-text="product.sku"
                                    ></span>
                                    <span
                                        x-show="!product.sku"
                                        class="rounded-md border border-gray-200 bg-gray-50 px-2.5 py-1 font-mono text-[11px] text-gray-400"
                                        >-</span
                                    >
                                </td>
                                <td
                                    class="px-4 py-3 text-xs font-medium text-gray-500"
                                    x-text="product.category_name"
                                ></td>
                                <td
                                    class="px-4 py-3 text-right text-[13px] font-bold text-gray-800"
                                    x-text="'₹' + parseFloat(product.display_price).toFixed(0)"
                                ></td>
                                <td class="px-4 py-3 text-center">
                                    <input
                                        type="number"
                                        min="1"
                                        max="99"
                                        x-model.number="product._copies"
                                        class="w-16 rounded-md border border-gray-200 bg-gray-50 px-2 py-1 text-center text-xs font-bold outline-none focus:border-[#108c2a] focus:bg-white"
                                    />
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div
                                        @click="triggerPrint(true, product)"
                                        title="Click to Quick Preview"
                                        class="mx-auto h-10 w-14 cursor-pointer overflow-hidden rounded border border-gray-200 bg-white p-1 shadow-sm transition-colors hover:border-[#108c2a] hover:shadow"
                                    >
                                        <img
                                            :src="getLabelUrl(product.label_value, 80)"
                                            class="h-full w-full object-contain"
                                        />
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- 📱 MOBILE VIEW (CARDS) --}}
            <div class="divide-y divide-gray-50 border-t border-gray-50 bg-white md:hidden">
                {{-- Loading State --}}
                <div x-show="isLoading" x-cloak class="py-16 text-center">
                    <i data-lucide="loader-2" class="mx-auto mb-3 h-8 w-8 animate-spin text-[#108c2a]"></i>
                    <p class="text-sm font-medium text-gray-500">Loading products...</p>
                </div>

                {{-- Empty State --}}
                <div x-show="!isLoading && products.length === 0" x-cloak class="py-16 text-center">
                    <i data-lucide="package-x" class="mx-auto mb-3 h-10 w-10 text-gray-300"></i>
                    <p class="text-sm font-medium text-gray-500">No products found.</p>
                </div>

                {{-- Data Cards --}}
                <template x-for="(product, index) in products" :key="product.unique_id">
                    <div
                        class="flex flex-col gap-3 p-4 transition-colors hover:bg-gray-50/50"
                        x-show="!isLoading"
                        :class="product._selected ? 'bg-[#f0fdf4]' : ''"
                    >
                        {{-- Header: Checkbox, Name, Price --}}
                        <div class="flex items-start gap-3">
                            <div class="pt-0.5">
                                <input
                                    type="checkbox"
                                    x-model="product._selected"
                                    class="h-4 w-4 cursor-pointer rounded border-gray-300 text-[#108c2a] focus:ring-[#108c2a]"
                                />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div
                                    class="text-[14px] leading-tight font-bold text-[#212538]"
                                    x-text="getDisplayName(product)"
                                ></div>
                                <div
                                    class="mt-0.5 text-[11px] text-gray-500"
                                    x-show="product.attributes && product.attributes.length > 0"
                                    x-text="formatAttrs(product.attributes)"
                                ></div>
                            </div>
                            <div class="shrink-0 text-right">
                                <div
                                    class="text-[14px] font-black text-gray-800"
                                    x-text="'₹' + parseFloat(product.display_price).toFixed(0)"
                                ></div>
                            </div>
                        </div>

                        {{-- Badges: SKU & Category --}}
                        <div class="flex flex-wrap items-center gap-2 pl-7">
                            <span
                                x-show="product.sku"
                                class="rounded border border-blue-100 bg-blue-50 px-1.5 py-0.5 font-mono text-[10px] font-bold tracking-wide text-blue-600"
                                x-text="product.sku"
                            ></span>
                            <span
                                x-show="!product.sku"
                                class="rounded border border-gray-200 bg-gray-50 px-1.5 py-0.5 font-mono text-[10px] text-gray-400"
                                >-</span
                            >
                            <span
                                class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-semibold text-gray-500"
                                x-text="product.category_name"
                            ></span>
                        </div>

                        {{-- Footer: Copies & Label Preview --}}
                        <div class="mt-1 flex items-center justify-between border-t border-gray-100/50 pt-2 pl-7">
                            <div class="flex items-center gap-2">
                                <label class="text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                                    >Copies:</label
                                >
                                <input
                                    type="number"
                                    min="1"
                                    max="99"
                                    x-model.number="product._copies"
                                    class="w-16 rounded-md border border-gray-200 bg-gray-50 px-2 py-1 text-center text-xs font-bold shadow-inner transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
                                />
                            </div>

                            <div
                                @click="triggerPrint(true, product)"
                                title="Click to Quick Preview"
                                class="h-10 w-16 shrink-0 cursor-pointer overflow-hidden rounded border border-gray-200 bg-white p-1 shadow-sm transition-colors hover:border-[#108c2a] hover:shadow"
                            >
                                <img :src="getLabelUrl(product.label_value, 80)" class="h-full w-full object-contain" />
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div
                class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4"
                x-show="!isLoading && pagination.total_pages > 1"
                x-cloak
            >
                <span
                    class="text-sm font-medium text-gray-500"
                    x-text="`Showing ${products.length} of ${pagination.total} SKUs`"
                ></span>
                <div class="flex gap-1">
                    <button
                        @click="fetchProducts(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        class="rounded border border-gray-200 bg-white px-3 py-1 text-sm font-medium hover:bg-gray-100 disabled:opacity-50"
                    >
                        Prev
                    </button>
                    <span
                        class="px-3 py-1 text-sm font-bold text-gray-700"
                        x-text="`Page ${pagination.current_page} of ${pagination.total_pages}`"
                    ></span>
                    <button
                        @click="fetchProducts(pagination.current_page + 1)"
                        :disabled="pagination.current_page === pagination.total_pages"
                        class="rounded border border-gray-200 bg-white px-3 py-1 text-sm font-medium hover:bg-gray-100 disabled:opacity-50"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>

    </div>

    <template id="print-template">
        <!DOCTYPE html>
        <html>
            <head>
                <title>Label Print</title>
                <style>
                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
                    }

                    /* Screen preview wrapper */
                    body {
                        background: #525659;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        gap: 10px;
                        padding: 20px;
                    }
                    #pdf-root {
                        display: flex;
                        flex-direction: column;
                        gap: 10px;
                        width: __PAGE_WIDTH__;
                    }

                    /* Print specific overrides */
                    @media print {
                        body {
                            background: #fff;
                            padding: 0;
                            display: block;
                        }
                        #pdf-root {
                            gap: 0;
                            display: block;
                        }
                        @page {
                            size: __PAGE_WIDTH__ __PAGE_HEIGHT__;
                            margin: 0;
                        }
                    }

                    .label {
                        background: #fff;
                        width: __PAGE_WIDTH__;
                        height: __PAGE_HEIGHT__;
                        padding: __PADDING__;
                        overflow: hidden;
                        page-break-after: always;
                        break-after: page;
                        position: relative;
                        margin: 0 auto;
                    }

                    /* --- BARCODE LAYOUT (CENTERED) --- */
                    .layout-barcode {
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        align-items: center;
                        text-align: center;
                        height: 100%;
                        width: 100%;
                    }
                    .layout-barcode .header-text {
                        line-height: 1.1;
                        width: 100%;
                    }
                    .layout-barcode .store-name {
                        font-size: 5pt;
                        font-weight: normal;
                        color: #555;
                        text-transform: uppercase;
                        letter-spacing: 0.2px;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }
                    .layout-barcode .product-name {
                        font-size: 7.5pt;
                        font-weight: normal;
                        color: #000;
                        margin-top: 0.5mm;
                        line-height: 1.3;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }

                    .layout-barcode .barcode-container {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        width: 100%;
                        margin-top: 0px;
                    }
                    .layout-barcode .barcode-img {
                        height: __IMAGE_PX__;
                        max-width: 95%;
                        object-fit: contain;
                    }
                    .layout-barcode .barcode-number {
                        font-size: 5pt;
                        font-family: monospace;
                        font-weight: normal;
                        letter-spacing: 0.5px;
                        margin-top: 1px;
                        color: #000;
                    }

                    .layout-barcode .price {
                        font-size: 9pt;
                        font-weight: normal;
                        color: #000;
                        margin-top: 0.5mm;
                    }

                    /* --- QR LAYOUT (SIDE-BY-SIDE) --- */
                    .layout-qr {
                        display: flex;
                        flex-direction: row;
                        align-items: flex-start; /* Stops Flexbox from randomly shifting things vertically */
                        justify-content: flex-start;
                        gap: 3mm;
                        width: 100%;
                        margin-top: 3.5mm; /* 🌟 MATHEMATICAL CENTER: (22mm container - 15mm content) / 2 = 3.5mm. Perfectly matches PDF! */
                        padding-left: 0.5mm; /* 🌟 PDF has 2mm padding total (.label provides 1.5mm + 0.5mm here = 2mm) */
                    }
                    .layout-qr .qr-left {
                        flex: 0 0 __IMAGE_PX__;
                        height: __IMAGE_PX__;
                        display: flex;
                        align-items: center;
                    }
                    .layout-qr .qr-left .barcode-img {
                        width: 100%;
                        height: 100%;
                        object-fit: contain;
                        display: block;
                    }

                    .layout-qr .text-right {
                        flex: 1;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        line-height: 1.2;
                        overflow: hidden;
                    }
                    .layout-qr .store-name {
                        font-size: 5pt;
                        font-weight: normal;
                        color: #555;
                        text-transform: uppercase;
                        margin-bottom: 0.5mm;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }
                    .layout-qr .product-name {
                        font-size: 7.5pt;
                        font-weight: normal;
                        color: #000;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }
                    .layout-qr .variant-name {
                        font-size: 5pt;
                        color: #555;
                        margin-top: 1px;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }
                    .layout-qr .price {
                        font-size: 9pt;
                        font-weight: normal;
                        color: #000;
                        margin-top: 2mm;
                    }
                </style>
            </head>
            <body>
                <div id="pdf-root">__BODY__</div>
            </body>
        </html>
    </template>

@endsection

@push ('scripts')
    <!-- Include html2pdf.js for direct PDF downloads -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <script>
        window.LABEL_APP_GLOBALS = {
            storeName: @json ($storeName ?? ''),
            renderRoute: "{{ route('admin.labels.render-image') }}",
            searchRoute: "{{ route('admin.labels.fetch-products') }}",
        };
    </script>

    @verbatim
        <script>
            function labelGenerator() {
                return {
                    labelType: 'barcode',
                    search: '',
                    categoryId: '',
                    products: [],
                    isLoading: false,
                    storeName: window.LABEL_APP_GLOBALS.storeName,
                    pagination: {
                        current_page: 1,
                        total_pages: 1,
                        total: 0,
                        per_page: 30
                    },

                    cfg: {
                        pageSize: 'thermal_50x25',
                        showStore: true,
                        showName: true,
                        showPrice: true,
                        showBorder: false,
                        fontSize: 12
                    },
                    globalCopy: 1,

                    get selectedCount() {
                        return this.products.filter(p => p._selected).length;
                    },

                    pageSizes: {
                        thermal_50x25: {
                            w: '50mm',
                            h: '25mm',
                            padding: '1.5mm',
                            barcodeHeight: '7mm', // 🌟 Shrunk to 7mm for descender letters
                            qrSize: '15mm',       // 🌟 Synced to 15mm to match the PDF exactly
                            format: [50, 25]      // Used for JS PDF generation
                        },
                        thermal_3x2: {
                            bodyW: '3in',
                            bodyH: '2in',
                            labelW: '2.8in',
                            padding: '3mm',
                            imgPx: 80
                        },
                        thermal_4x3: {
                            bodyW: '4in',
                            bodyH: '3in',
                            labelW: '3.8in',
                            padding: '4mm',
                            imgPx: 100
                        },
                        a5: {
                            bodyW: '148mm',
                            bodyH: '210mm',
                            labelW: '200px',
                            padding: '8mm',
                            imgPx: 70
                        },
                        a4: {
                            bodyW: '210mm',
                            bodyH: '297mm',
                            labelW: '240px',
                            padding: '10mm',
                            imgPx: 70
                        },
                    },

                    init() {
                        this.fetchProducts(1);
                    },

                    setLabelType(type) {
                        this.labelType = type;
                    },

                    selectAll(status) {
                        this.products.forEach(p => p._selected = status);
                    },

                    applyGlobalCopy() {
                        let n = Math.max(1, Math.min(99, parseInt(this.globalCopy) || 1));
                        this.globalCopy = n;
                        this.products.forEach(p => p._copies = n);
                    },

                    resetFilters() {
                        this.search = '';
                        this.categoryId = '';

                        // Programmatically target and cycle through layout selectors to broadcast the filter wipe event
                        document.querySelectorAll('.bg-white select').forEach(el => {
                            if (el.name === 'category_id') {
                                el.value = '';
                                el.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        });

                        this.fetchProducts(1);
                    },

                    getLabelUrl(value, size) {
                        const baseUrl = window.LABEL_APP_GLOBALS.renderRoute;
                        return `${baseUrl}?type=${this.labelType}&value=${encodeURIComponent(value)}&size=${size}`;
                    },

                    // 🌟 NEW: Computes POS-style full name (e.g. "Product - Variant 1 - Variant 2")
                    getDisplayName(p) {
                        if (!p.attributes || p.attributes.length === 0) return p.name;
                        const variantValues = p.attributes.map(a => a.value).filter(Boolean).join(' - ');
                        return variantValues ? `${p.name} - ${variantValues}` : p.name;
                    },

                    /**
                     * Format structured attributes for display.
                     * 1 attr  → "Color: Red"
                     * N attrs → "Size: L, Color: Red"
                     * none    → "" (falsy — callers use this to skip rendering)
                     */
                    formatAttrs(attrs) {
                        if (!attrs || attrs.length === 0) return '';
                        return attrs.map(a => `${a.name}: ${a.value}`).join(', ');
                    },

                    async fetchProducts(page) {
                        this.isLoading = true;

                        const stateMemory = {};
                        this.products.forEach(p => {
                            stateMemory[p.unique_id] = {
                                sel: p._selected,
                                cop: p._copies
                            };
                        });

                        try {
                            const url = new URL(window.LABEL_APP_GLOBALS.searchRoute, window.location.origin);
                            url.searchParams.append('page', page);
                            url.searchParams.append('per_page', this.pagination.per_page);
                            url.searchParams.append('search', this.search);
                            url.searchParams.append('category_id', this.categoryId);

                            const res = await fetch(url);
                            const json = await res.json();

                            if (json.status === 'success') {
                                this.products = json.data.map(p => ({
                                    ...p,
                                    _selected: stateMemory[p.unique_id] ? stateMemory[p.unique_id].sel : false,
                                    _copies: stateMemory[p.unique_id] ? stateMemory[p.unique_id].cop : 1
                                }));
                                this.pagination = json.meta;
                            }
                        } catch (err) {
                            console.error(err);
                            if (typeof BizAlert !== 'undefined') BizAlert.toast('Failed to fetch labels', 'error');
                        } finally {
                            this.isLoading = false;
                            setTimeout(() => {
                                if (typeof lucide !== 'undefined') lucide.createIcons();
                            }, 50);
                        }
                    },

                    generateHTML(selected, ps) {
                        let labelsHtml = '';
                        
                        selected.forEach(p => {
                            // Dynamic image constraint based on layout type
                            const imgPxSize = this.labelType === 'qr' ? 150 : 80;
                            const imgUrl = this.getLabelUrl(p.label_value, imgPxSize);
                            const price = p.display_price ? `₹${parseFloat(p.display_price).toFixed(0)}` : '';
                            let varText = this.formatAttrs(p.attributes);

                            for (let i = 0; i < p._copies; i++) {
                                if (this.labelType === 'barcode') {
                                    labelsHtml += `
                                    <div class="label" style="${this.cfg.showBorder ? 'border: 1px solid #ccc;' : ''}">
                                        <div class="layout-barcode">
                                            <div class="header-text">
                                                ${this.cfg.showStore ? `<div class="store-name">${this.storeName}</div>` : ''}
                                                ${this.cfg.showName ? `<div class="product-name">${p.name}</div>` : ''}
                                            </div>
                                            <div class="barcode-container">
                                                <img class="barcode-img" src="${imgUrl}" />
                                                <div class="barcode-number">${p.label_value}</div>
                                            </div>
                                            ${this.cfg.showPrice && price ? `<div class="price" style="font-size:${this.cfg.fontSize}px">${price}</div>` : ''}
                                        </div>
                                    </div>`;
                                } else {
                                    // QR Code Layout
                                    labelsHtml += `
                                    <div class="label" style="${this.cfg.showBorder ? 'border: 1px solid #ccc;' : ''}">
                                        <div class="layout-qr">
                                            <div class="qr-left">
                                                <img class="barcode-img" src="${imgUrl}" />
                                            </div>
                                            <div class="text-right">
                                                ${this.cfg.showStore ? `<div class="store-name">${this.storeName}</div>` : ''}
                                                ${this.cfg.showName ? `<div class="product-name">${p.name}</div>` : ''}
                                                ${varText ? `<div class="variant-name">${varText}</div>` : ''}
                                                ${this.cfg.showPrice && price ? `<div class="price" style="font-size:${this.cfg.fontSize}px">${price}</div>` : ''}
                                            </div>
                                        </div>
                                    </div>`;
                                }
                            }
                        });

                        let popupHtml = document.getElementById('print-template').innerHTML;
                        popupHtml = popupHtml.replace(/__PAGE_WIDTH__/g, ps.w || '50mm');
                        popupHtml = popupHtml.replace(/__PAGE_HEIGHT__/g, ps.h || '25mm');
                        popupHtml = popupHtml.replace(/__PADDING__/g, ps.padding);
                        popupHtml = popupHtml.replace(/__IMAGE_PX__/g, this.labelType === 'qr' ? (ps.qrSize || '16mm') : (ps.barcodeHeight || '8mm'));
                        popupHtml = popupHtml.replace(/__BORDER__/g, this.cfg.showBorder ? '1px solid #ccc' : 'none');
                        popupHtml = popupHtml.replace('__BODY__', labelsHtml);
                        
                        return popupHtml;
                    },

                    async triggerPrint(isPreview = false, singleProduct = null) {
                        const selected = singleProduct ? [singleProduct] : this.products.filter(p => p._selected);
                        if (selected.length === 0) {
                            Swal.fire('Error', 'Please select at least one product.', 'error');
                            return;
                        }

                        // 🌟 NATIVE FLUTTER BRIDGE — data only.
                        //
                        // The app renders and prints the label itself through the printer's
                        // vendor SDK; this side just describes what goes on it. Nothing here
                        // decides layout, so the printed label cannot drift out of sync with
                        // the app because of a change made in the browser.
                        if (!isPreview && window.AndroidLabelPrinter && typeof window.AndroidLabelPrinter.printLabels === "function") {
                            try {
                                const payload = selected.map(p => ({
                                    name: this.cfg.showName ? (p.name || '') : '',
                                    attributes: this.formatAttrs(p.attributes) || '',
                                    label_value: p.label_value || '',
                                    price: (this.cfg.showPrice && p.display_price)
                                        ? parseFloat(p.display_price).toFixed(0)
                                        : '',
                                    copies: Math.max(1, Math.min(99, parseInt(p._copies) || 1)),
                                    type: this.labelType,
                                    // An empty store_name is how "hide the store row" is expressed.
                                    store_name: this.cfg.showStore ? this.storeName : '',
                                    show_border: !!this.cfg.showBorder,
                                    font_size: parseInt(this.cfg.fontSize) || 12,
                                }));

                                window.AndroidLabelPrinter.printLabels(JSON.stringify(payload));

                                const totalCopies = payload.reduce((n, l) => n + l.copies, 0);
                                const Toast = Swal.mixin({
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 2500,
                                    timerProgressBar: true
                                });
                                Toast.fire({ icon: 'success', title: `Printing ${totalCopies} label(s)…` });

                                return;

                            } catch (err) {
                                console.error("Native label printing failed:", err);
                                Swal.fire('Print Failed', err.message || 'Could not reach the printer.', 'error');
                                return;
                            }
                        }

                        // 🌐 FALLBACK: STANDARD BROWSER PRINT (PC / Web)
                        // (Ye same rahega jaisa tha, Web walon ke liye)
                        const ps = this.pageSizes[this.cfg.pageSize] || this.pageSizes['thermal_50x25'];
                        const finalHtml = this.generateHTML(selected, ps);

                        const pw = window.open('', '_blank', 'width=800,height=600');
                        if (!pw) return Swal.fire('Error', 'Popup blocked! Please allow popups.', 'error');

                        pw.document.write(finalHtml);
                        pw.document.close();

                        if (!isPreview) {
                            pw.onload = () => {
                                setTimeout(() => {
                                    pw.focus();
                                    pw.print();
                                    pw.onafterprint = () => pw.close();
                                }, 800); 
                            };
                        }
                    },

                    downloadPDF() {
                        const selected = this.products.filter(p => p._selected);
                        if (selected.length === 0) return BizAlert.toast('Please select at least one product.', 'warning');;

                        // Map the data we need to send to the server
                        const payload = selected.map(p => ({
                            name: p.name,
                            attributes: this.formatAttrs(p.attributes),
                            label_value: p.label_value,
                            price: p.display_price,
                            copies: p._copies
                        }));

                        // Create a hidden form to submit the POST request for the download
                        const form = document.createElement('form');
                        form.method = 'POST';                        
                        form.action = "/admin/labels/download-pdf"; 
                        
                        // Add CSRF Token
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                                          || document.querySelector('input[name="_token"]')?.value;
                                          
                        if(csrfToken) {
                            const csrfInput = document.createElement('input');
                            csrfInput.type = 'hidden';
                            csrfInput.name = '_token';
                            csrfInput.value = csrfToken;
                            form.appendChild(csrfInput);
                        }

                        // Add Payload
                        const dataInput = document.createElement('input');
                        dataInput.type = 'hidden';
                        dataInput.name = 'labels_data';
                        dataInput.value = JSON.stringify(payload);
                        form.appendChild(dataInput);

                        // Add Label Type
                        const typeInput = document.createElement('input');
                        typeInput.type = 'hidden';
                        typeInput.name = 'label_type';
                        typeInput.value = this.labelType;
                        form.appendChild(typeInput);

                        // Add Store Name
                        const storeInput = document.createElement('input');
                        storeInput.type = 'hidden';
                        storeInput.name = 'store_name';
                        storeInput.value = this.storeName;
                        form.appendChild(storeInput);

                        document.body.appendChild(form);
                        form.submit();
                        document.body.removeChild(form);
                    }
                }
            }
        </script>
    @endverbatim
@endpush
