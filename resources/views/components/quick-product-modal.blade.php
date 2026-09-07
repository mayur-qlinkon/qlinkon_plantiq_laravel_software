@props(['categories', 'units'])

{{-- Closed only from the buttons or Escape. A stray click on the backdrop used
     to discard a half-filled product, which at a busy counter meant retyping
     everything. --}}
<div x-show="isProductModalOpen" style="display: none;" @keydown.escape.window="isProductModalOpen = false"
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center bg-gray-900/70 backdrop-blur-sm sm:p-4"
    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

    {{-- The Modal / Bottom Sheet --}}
    <div class="bg-white w-full sm:max-w-2xl rounded-t-3xl sm:rounded-2xl shadow-2xl flex flex-col overflow-hidden max-h-[90vh] sm:max-h-[85vh]"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-full sm:translate-y-8 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">

        {{-- Mobile Pull Indicator (Only visible on small screens) --}}
        <div class="w-full flex justify-center pt-3 pb-1 sm:hidden shrink-0 bg-white">
            <div class="w-12 h-1.5 bg-gray-200 rounded-full"></div>
        </div>

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-gray-100 bg-white shrink-0">
            <div>
                <h3 class="text-base sm:text-lg font-bold text-gray-800 flex items-center gap-2">
                    <i data-lucide="package-plus" class="w-5 h-5 text-brand-500"></i>
                    Quick Add Product
                </h3>
                <p class="text-[11px] text-gray-400 font-medium hidden sm:block mt-0.5">Enter product details to
                    immediately add to POS</p>
            </div>
            <button @click="isProductModalOpen = false"
                class="text-gray-400 hover:text-red-500 transition-colors p-2 rounded-xl hover:bg-red-50 bg-gray-50 sm:bg-transparent">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        {{-- Scrollable Body --}}
        <div class="p-5 sm:p-6 overflow-y-auto no-scrollbar flex-1 bg-gray-50/30">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">

                {{-- Product Name --}}
                <div class="col-span-1 sm:col-span-2">
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Product
                        Name <span class="text-red-500">*</span></label>
                    <input type="text" x-model="newProduct.name"
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 sm:py-2.5 text-sm focus:ring-4 focus:ring-brand-500/10 focus:border-brand-500 outline-none transition-all bg-white"
                        placeholder="e.g., Ficus Plant">
                </div>

                {{-- Category --}}
                <div class="col-span-1">
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Category
                        <span class="text-red-500">*</span></label>
                    {{-- Same custom dropdown as Unit. A category list is longer,
                         so this one keeps a single column and adds a filter. --}}
                    <div x-data="{
                        open: false,
                        search: '',
                        categories: @js($categories->map->only(['id', 'name'])->values()),
                        get filtered() {
                            const q = this.search.trim().toLowerCase();
                            return q === '' ? this.categories : this.categories.filter(c => c.name.toLowerCase().includes(q));
                        },
                    }" class="relative">
                        <button @click="open = !open; if (open) $nextTick(() => $refs.catSearch?.focus())"
                            type="button"
                            :class="open ? 'border-brand-500 ring-4 ring-brand-500/10' : 'border-gray-200 hover:border-gray-300'"
                            class="flex w-full items-center gap-2 rounded-xl border bg-white px-4 py-3 text-left text-sm transition-all sm:py-2.5">
                            <span class="flex-1 truncate"
                                :class="newProduct.category_id ? 'font-medium text-gray-800' : 'text-gray-400'"
                                x-text="categories.find(c => c.id == newProduct.category_id)?.name || 'Select Category'"></span>
                            <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-gray-400 transition-transform"
                                :class="open && 'rotate-180'"></i>
                        </button>

                        <div x-cloak x-show="open" @click.away="open = false; search = ''"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="absolute right-0 left-0 z-50 mt-2 overflow-hidden rounded-xl border border-gray-100 bg-white shadow-[0_10px_40px_-8px_rgba(0,0,0,0.18)]">
                            <div class="border-b border-gray-100 p-1.5">
                                <input type="text" x-ref="catSearch" x-model="search"
                                    placeholder="Search categories..." @keydown.escape.stop="open = false; search = ''"
                                    class="w-full rounded-lg bg-gray-50 px-3 py-2 text-[12px] font-medium outline-none placeholder:text-gray-400">
                            </div>
                            <div class="no-scrollbar max-h-[180px] overflow-y-auto p-1.5">
                                <template x-for="c in filtered" :key="c.id">
                                    <button type="button"
                                        @click="newProduct.category_id = c.id; open = false; search = ''"
                                        :class="newProduct.category_id == c.id ? 'bg-brand-50 text-brand-700' :
                                            'text-gray-700 hover:bg-gray-50'"
                                        class="flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left transition-colors">
                                        <span class="flex-1 truncate text-[13px]"
                                            :class="newProduct.category_id == c.id ? 'font-bold' : 'font-medium'"
                                            x-text="c.name"></span>
                                        <i data-lucide="check" class="text-brand-600 h-4 w-4 shrink-0"
                                            x-show="newProduct.category_id == c.id"></i>
                                    </button>
                                </template>
                                <p x-show="filtered.length === 0"
                                    class="px-2.5 py-3 text-center text-[12px] font-medium text-gray-400">
                                    No matching category.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Unit --}}
                {{-- A custom dropdown rather than a native select: a select opens
                     the operating system's own list, which looks nothing like the
                     store, warehouse and payment pickers next to it. --}}
                <div class="col-span-1" x-data="{ open: false, units: @js($units->map->only(['id', 'name'])->values()) }">
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Unit <span
                            class="text-red-500">*</span></label>
                    <div class="relative">
                        <button @click="open = !open" type="button"
                            :class="open ? 'border-brand-500 ring-4 ring-brand-500/10' : 'border-gray-200 hover:border-gray-300'"
                            class="flex w-full items-center gap-2 rounded-xl border bg-white px-4 py-3 text-left text-sm transition-all sm:py-2.5">
                            <span class="flex-1 truncate text-left"
                                :class="newProduct.unit_id ? 'font-medium text-gray-800' : 'text-gray-400'"
                                x-text="units.find(u => u.id == newProduct.unit_id)?.name || 'Select Unit'"></span>
                            <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-gray-400 transition-transform"
                                :class="open && 'rotate-180'"></i>
                        </button>

                        <div x-cloak x-show="open" @click.away="open = false"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="absolute right-0 left-0 z-50 mt-2 overflow-hidden rounded-xl border border-gray-100 bg-white shadow-[0_10px_40px_-8px_rgba(0,0,0,0.18)]">
                            {{-- Two columns keep eight or so units on screen at
                                 once, so the list does not need a scrollbar
                                 running down the middle of a short modal. --}}
                            <div class="no-scrollbar grid max-h-[200px] grid-cols-2 gap-1 overflow-y-auto p-1.5">
                                <template x-for="u in units" :key="u.id">
                                    <button type="button" @click="newProduct.unit_id = u.id; open = false"
                                        :class="newProduct.unit_id == u.id ?
                                            'bg-brand-500 text-white shadow-sm' :
                                            'bg-gray-50 text-gray-600 hover:bg-gray-100'"
                                        class="truncate rounded-lg px-3 py-2 text-center text-[12px] font-bold transition-all"
                                        x-text="u.name"></button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Opening Stock & HSN Code --}}
                <div class="col-span-1">
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Opening
                        Stock</label>
                    {{-- text + inputmode rather than type="number". A number
                         input accepts "-", "+" and "e" by spec, and reports an
                         unparseable value as an empty string — so a minus sign
                         arrived at the server as a silent blank. --}}
                    <input type="text" inputmode="numeric" x-model="newProduct.opening_stock"
                        @input="newProduct.opening_stock = cleanInteger($event.target.value)"
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 sm:py-2.5 text-sm focus:ring-4 focus:ring-brand-500/10 focus:border-brand-500 outline-none transition-all bg-white"
                        placeholder="0">
                </div>

                <div class="col-span-1">
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">HSN Code
                        <span class="text-[9px] font-medium text-gray-400">(Optional)</span></label>
                    {{-- Digits only, but kept as text: an HSN is a code, not a
                         quantity. type="number" would drop the leading zero
                         from codes like 06029000 (live plants). --}}
                    <input type="text" inputmode="numeric" maxlength="8" x-model="newProduct.hsn_code"
                        @input="newProduct.hsn_code = cleanHsn($event.target.value)"
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 sm:py-2.5 text-sm focus:ring-4 focus:ring-brand-500/10 focus:border-brand-500 outline-none transition-all bg-white"
                        placeholder="e.g., 06029000">
                </div>

                {{-- Divider for Mobile --}}
                <div class="col-span-1 sm:col-span-2 h-px bg-gray-200/60 my-1"></div>

                {{-- Cost & Price --}}
                <div class="col-span-1">
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Cost Price
                        <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span
                            class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-medium text-sm">₹</span>
                        <input type="text" inputmode="numeric" x-model="newProduct.cost"
                            @input="newProduct.cost = cleanInteger($event.target.value)"
                            class="w-full border border-gray-200 rounded-xl pl-8 pr-4 py-3 sm:py-2.5 text-sm focus:ring-4 focus:ring-brand-500/10 focus:border-brand-500 outline-none transition-all bg-white"
                            placeholder="0">
                    </div>
                </div>

                <div class="col-span-1">
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Selling
                        Price <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span
                            class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-medium text-sm">₹</span>
                        <input type="text" inputmode="numeric" x-model="newProduct.price"
                            @input="newProduct.price = cleanInteger($event.target.value)"
                            class="w-full border border-gray-200 rounded-xl pl-8 pr-4 py-3 sm:py-2.5 text-sm focus:ring-4 focus:ring-brand-500/10 focus:border-brand-500 outline-none transition-all bg-white"
                            placeholder="0">
                    </div>
                </div>

                {{-- Tax Settings --}}
                <div class="col-span-1">
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Tax
                        Percent
                        (%)</label>
                    {{-- GST slabs are whole numbers: 0, 5, 12, 18, 28. --}}
                    <input type="text" inputmode="numeric" x-model="newProduct.tax_percent"
                        @input="newProduct.tax_percent = cleanInteger($event.target.value, 100)"
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 sm:py-2.5 text-sm focus:ring-4 focus:ring-brand-500/10 focus:border-brand-500 outline-none transition-all bg-white"
                        placeholder="0">
                </div>

                <div class="col-span-1">
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Tax
                        Type</label>
                    {{-- Two choices, so a toggle rather than a menu — and both
                         options stay visible, which matters here because the
                         difference changes what the customer is charged. --}}
                    <div class="flex gap-1 rounded-xl border border-gray-200 bg-gray-50 p-1">
                        <button type="button" @click="newProduct.tax_type = 'exclusive'"
                            :class="newProduct.tax_type === 'exclusive' ?
                                'bg-white text-brand-600 shadow-sm' :
                                'text-gray-500 hover:text-gray-700'"
                            class="flex-1 rounded-lg py-2 text-[12px] font-bold transition-all">Exclusive</button>
                        <button type="button" @click="newProduct.tax_type = 'inclusive'"
                            :class="newProduct.tax_type === 'inclusive' ?
                                'bg-white text-brand-600 shadow-sm' :
                                'text-gray-500 hover:text-gray-700'"
                            class="flex-1 rounded-lg py-2 text-[12px] font-bold transition-all">Inclusive</button>
                    </div>
                </div>

                {{-- Image --}}
                <div class="col-span-1 sm:col-span-2 mt-2">
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Product
                        Image <span class="text-[9px] font-medium text-gray-400">(Optional)</span></label>
                    <input type="file" x-ref="productImageFile" accept="image/*"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm bg-white
                        file:mr-4 file:py-1.5 file:px-4 file:rounded-full file:border-0 file:text-[11px] file:font-bold file:bg-brand-50 file:text-brand-600 hover:file:bg-brand-100 cursor-pointer transition-all">
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="p-4 sm:p-5 border-t border-gray-100 bg-white flex flex-col sm:flex-row justify-end gap-3 shrink-0">
            <button @click="isProductModalOpen = false"
                class="w-full sm:w-auto px-6 py-3 sm:py-2.5 text-sm font-bold text-gray-600 bg-gray-50 border border-gray-200 hover:bg-gray-100 rounded-xl transition-colors order-2 sm:order-1">
                Cancel
            </button>
            <button @click="saveQuickProduct()"
                class="w-full sm:w-auto px-6 py-3 sm:py-2.5 text-sm font-bold text-white bg-brand-500 hover:bg-brand-600 rounded-xl shadow-lg shadow-brand-500/20 transition-all flex items-center justify-center gap-2 order-1 sm:order-2">
                <i data-lucide="check-circle" class="w-4 h-4"></i> Save & Add
            </button>
        </div>

    </div>
</div>
