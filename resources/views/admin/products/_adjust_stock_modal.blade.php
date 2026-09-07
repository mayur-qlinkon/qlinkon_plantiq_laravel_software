{{--
    _adjust_stock_modal.blade.php
    Included once at the bottom of admin/products/show.blade.php.
    Opened via a window event dispatched by each "Adjust Stock" button in the SKU table.
--}}
<div
    x-data="{
        show:         false,
        skuId:        null,
        skuCode:      '',
        currentQty:   0,
        warehouseId:  '',
        type:         'add',
        qty:          1,
        reason:       'correction',
        note:         '',
        loading:      false,
        error:        '',

        open(detail) {
            this.skuId       = detail.skuId;
            this.skuCode     = detail.skuCode;
            this.currentQty  = detail.currentQty;
            this.warehouseId = '';
            this.type        = 'add';
            this.qty         = 1;
            this.reason      = 'correction';
            this.note        = '';
            this.error       = '';
            this.show        = true;
        },

        close() {
            if (this.loading) return;
            this.show = false;
        },

        get newQtyPreview() {
            const q = parseInt(this.qty) || 0;
            if (this.type === 'add')    return this.currentQty + q;
            if (this.type === 'remove') return Math.max(0, this.currentQty - q);
            if (this.type === 'set')    return q;
            return this.currentQty;
        },

        get previewColor() {
            const preview = this.newQtyPreview;
            if (preview > this.currentQty) return 'text-green-600';
            if (preview < this.currentQty) return 'text-red-500';
            return 'text-gray-600';
        },

        async submit() {
            if (!this.warehouseId) { this.error = 'Please select a warehouse.'; return; }
            if (this.qty === '' || this.qty === null) { this.error = 'Quantity is required.'; return; }

            this.loading = true;
            this.error   = '';

            try {
                const baseUrl  = '{{ url("admin/products/" . $product->id . "/skus") }}';
                const response = await fetch(`${baseUrl}/${this.skuId}/adjust-stock`, {
                    method:  'POST',
                    headers: {
                        'Content-Type':  'application/json',
                        'X-CSRF-TOKEN':  document.querySelector('meta[name=csrf-token]').content,
                        'Accept':        'application/json',
                    },
                    body: JSON.stringify({
                        warehouse_id: this.warehouseId,
                        type:         this.type,
                        qty:          this.qty,
                        reason:       this.reason,
                        note:         this.note,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    this.error = data.message || 'Adjustment failed. Please try again.';
                    return;
                }

                // Update the total qty cell for this SKU in the table
                const totalEl = document.getElementById('sku-total-qty-' + this.skuId);
                if (totalEl) totalEl.textContent = data.new_total_qty + ' units';

                // Update the per-warehouse badge if it exists
                const whEl = document.getElementById('sku-wh-qty-' + this.skuId + '-' + data.warehouse_id);
                if (whEl) whEl.textContent = data.new_warehouse_qty;

                BizAlert.toast(data.message, 'success');
                this.show = false;

            } catch (e) {
                this.error = 'Network error. Please check your connection.';
            } finally {
                this.loading = false;
            }
        }
    }"
    x-on:open-adjust-modal.window="open($event.detail)"
    x-cloak
>
    {{-- ── Backdrop ── --}}
    <div
        x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/40 z-40"
        @click="close()"
    ></div>

    {{-- ── Modal Panel ── --}}
    <div
        x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-4 sm:translate-y-0"
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 sm:p-0"
    >
        <div class="bg-white rounded-2xl sm:rounded-xl shadow-xl w-full max-w-md border border-gray-100 max-h-[90vh] flex flex-col overflow-hidden" @click.stop>
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                <div>
                    <h3 class="text-base font-bold text-[#212538]">Adjust Stock</h3>
                    <p class="text-xs text-gray-400 mt-0.5 font-mono" x-text="'SKU: ' + skuCode"></p>
                </div>
                <button @click="close()" class="text-gray-400 hover:text-gray-600 transition-colors" :disabled="loading">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            {{-- Body --}}
            <div class="px-6 py-5 space-y-4 overflow-y-auto custom-scrollbar">

                {{-- Error alert --}}
                <div x-show="error" class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg flex items-start gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                    <span x-text="error"></span>
                </div>

                {{-- Current stock context --}}
                <div class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 flex items-center justify-between">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Current Total Stock</span>
                    <span class="text-lg font-bold text-[#212538]" x-text="currentQty + ' units'"></span>
                </div>

                {{-- Warehouse --}}
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1.5">Warehouse <span class="text-red-500">*</span></label>
                    <select
                        x-model="warehouseId"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#108c2a]/30 focus:border-[#108c2a] bg-white"
                    >
                        <option value="">— Select Warehouse —</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Adjustment Type --}}
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1.5">Adjustment Type <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button"
                            @click="type = 'add'"
                            :class="type === 'add' ? 'bg-green-600 text-white border-green-600' : 'bg-white text-gray-600 border-gray-200 hover:border-green-400'"
                            class="border rounded-lg py-2 text-xs font-bold transition-all flex flex-col items-center gap-1">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i> Add
                        </button>
                        <button type="button"
                            @click="type = 'remove'"
                            :class="type === 'remove' ? 'bg-red-500 text-white border-red-500' : 'bg-white text-gray-600 border-gray-200 hover:border-red-400'"
                            class="border rounded-lg py-2 text-xs font-bold transition-all flex flex-col items-center gap-1">
                            <i data-lucide="minus-circle" class="w-4 h-4"></i> Remove
                        </button>
                        <button type="button"
                            @click="type = 'set'"
                            :class="type === 'set' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:border-blue-400'"
                            class="border rounded-lg py-2 text-xs font-bold transition-all flex flex-col items-center gap-1">
                            <i data-lucide="target" class="w-4 h-4"></i> Set to
                        </button>
                    </div>
                </div>

                {{-- Quantity --}}
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1.5">
                        <span x-text="type === 'set' ? 'Set Exact Quantity' : 'Quantity'"></span>
                        <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text" inputmode="numeric" data-int data-int-min="0"
                        x-model.number="qty"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-mono text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#108c2a]/30 focus:border-[#108c2a]"
                        placeholder="0"
                    >
                    {{-- Live preview --}}
                    <div class="flex items-center justify-between mt-2 px-1">
                        <span class="text-xs text-gray-400">New stock after adjustment:</span>
                        <span class="text-sm font-bold" :class="previewColor" x-text="newQtyPreview + ' units'"></span>
                    </div>
                </div>

                {{-- Reason --}}
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1.5">Reason <span class="text-red-500">*</span></label>
                    <select
                        x-model="reason"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#108c2a]/30 focus:border-[#108c2a] bg-white"
                    >
                        <option value="correction">Data Correction</option>
                        <option value="physical_count">Physical Count</option>
                        <option value="damage">Damaged Goods</option>
                        <option value="expiry">Expired Goods</option>
                        <option value="theft">Theft / Loss</option>
                        <option value="opening_stock">Opening Stock</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                {{-- Note --}}
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1.5">Note <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input
                        type="text"
                        x-model="note"
                        maxlength="255"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#108c2a]/30 focus:border-[#108c2a]"
                        placeholder="Brief explanation..."
                    >
                </div>

            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
                <button
                    type="button"
                    @click="close()"
                    :disabled="loading"
                    class="px-4 py-2 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    @click="submit()"
                    :disabled="loading"
                    class="px-5 py-2 text-sm font-bold text-white bg-[#108c2a] hover:bg-[#0c6b1f] rounded-lg transition-colors disabled:opacity-60 flex items-center gap-2"
                >
                    <template x-if="loading">
                        <svg class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                    </template>
                    <span x-text="loading ? 'Saving...' : 'Save Adjustment'"></span>
                </button>
            </div>

        </div>
    </div>
</div>