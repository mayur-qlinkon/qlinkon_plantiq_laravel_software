{{-- 🖥️ DESKTOP VIEW --}}
<div class="hidden md:block overflow-x-auto">
    <table class="w-full text-left border-collapse min-w-[850px]">
        <thead class="bg-gray-50 border-b border-gray-200 text-[10px] font-bold text-gray-500 uppercase tracking-wider">
            <tr>
                <th class="px-4 py-3">Date & Time</th>
                <th class="px-4 py-3">Product</th>
                <th class="px-4 py-3">Warehouse</th>
                <th class="px-4 py-3">Movement Type</th>
                <th class="px-4 py-3 text-right">Qty</th>
                <th class="px-4 py-3 text-right">Balance</th>
                <th class="px-4 py-3">User</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($movements as $log)
                @php
                    $variantStr  = $log->sku?->skuValues?->map(fn($v) => $v->attributeValue?->value)->filter()->implode(' - ');
                    $displayName = $variantStr ? ($log->sku->product?->name . ' - ' . $variantStr) : ($log->sku->product?->name ?? 'Unknown Product');
                    $badgeClass  = match($log->movement_type) {
                        'transfer_in', 'opening' => 'bg-blue-50 text-blue-700 border-blue-200',
                        'transfer_out'            => 'bg-purple-50 text-purple-700 border-purple-200',
                        'sale'                    => 'bg-green-50 text-green-700 border-green-200',
                        'adjustment'              => 'bg-orange-50 text-orange-700 border-orange-200',
                        default                   => 'bg-gray-50 text-gray-700 border-gray-200',
                    };
                @endphp
                <tr class="hover:bg-gray-50 transition-colors text-sm">
                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                        <div class="font-bold text-gray-800">{{ $log->created_at->format('d M Y') }}</div>
                        <div class="text-[10px]">{{ $log->created_at->format('h:i A') }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-bold text-gray-900 line-clamp-1">{{ $displayName }}</div>
                        <div class="text-[10px] text-gray-500 font-mono mt-0.5">SKU: {{ $log->sku->sku ?? '-' }}</div>
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-700">{{ $log->warehouse->name ?? 'Unknown' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-block border px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-widest {{ $badgeClass }}">
                            {{ str_replace('_', ' ', $log->movement_type) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right font-black text-lg {{ $log->quantity > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $log->quantity > 0 ? '+' : '' }}{{ (float) $log->quantity }}
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-gray-900">{{ (float) $log->balance_after }}</td>
                    <td class="px-4 py-3 text-xs text-gray-500 font-medium">{{ $log->user->name ?? 'System' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500 font-medium">No movements found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- 📱 MOBILE VIEW --}}
<div class="md:hidden divide-y divide-gray-50 bg-white">
    @forelse ($movements as $log)
        @php
            $variantStr  = $log->sku?->skuValues?->map(fn($v) => $v->attributeValue?->value)->filter()->implode(' - ');
            $displayName = $variantStr ? ($log->sku->product?->name . ' - ' . $variantStr) : ($log->sku->product?->name ?? 'Unknown Product');
            $badgeClass  = match($log->movement_type) {
                'transfer_in', 'opening' => 'bg-blue-50 text-blue-700 border-blue-200',
                'transfer_out'            => 'bg-purple-50 text-purple-700 border-purple-200',
                'sale'                    => 'bg-green-50 text-green-700 border-green-200',
                'adjustment'              => 'bg-orange-50 text-orange-700 border-orange-200',
                default                   => 'bg-gray-50 text-gray-700 border-gray-200',
            };
        @endphp
        <div class="p-4 flex flex-col gap-2.5">
            <div class="flex justify-between items-start">
                <div class="min-w-0">
                    <p class="font-bold text-gray-900 text-[13px] leading-tight truncate">{{ $displayName }}</p>
                    <p class="text-[10px] text-gray-500 font-mono mt-0.5">SKU: {{ $log->sku->sku ?? '-' }}</p>
                </div>
                <span class="shrink-0 border px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-widest {{ $badgeClass }}">
                    {{ str_replace('_', ' ', $log->movement_type) }}
                </span>
            </div>
            <div class="flex items-center justify-between text-[11px] text-gray-500">
                <span class="flex items-center gap-1"><i data-lucide="clock" class="w-3 h-3"></i> {{ $log->created_at->format('d M y, h:i A') }}</span>
                <span class="flex items-center gap-1"><i data-lucide="map-pin" class="w-3 h-3"></i> {{ $log->warehouse->name ?? 'Unknown' }}</span>
            </div>
            <div class="flex items-center justify-between bg-gray-50/80 px-3 py-2 rounded-lg border border-gray-100 mt-1">
                <div>
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider block mb-0.5">Qty Change</span>
                    <span class="font-black text-[14px] {{ $log->quantity > 0 ? 'text-green-600' : 'text-red-600' }}">{{ $log->quantity > 0 ? '+' : '' }}{{ (float) $log->quantity }}</span>
                </div>
                <div class="text-right">
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider block mb-0.5">New Balance</span>
                    <span class="font-bold text-gray-900 text-[13px]">{{ (float) $log->balance_after }}</span>
                </div>
            </div>
            <p class="text-[9px] text-gray-400 text-right">Logged by: {{ $log->user->name ?? 'System' }}</p>
        </div>
    @empty
        <div class="p-8 text-center text-gray-500 font-medium text-sm">No movements found.</div>
    @endforelse
</div>

{{-- Pagination --}}
<div class="p-4 border-t border-gray-100">
    {{ $movements->appends(['tab' => 'ledger', 'search_movement' => request('search_movement')])->links() }}
</div>