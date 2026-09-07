{{--
    Reusable "Stock Alerts" dashboard widget — Inventory module.
    Usage: <x-admin.dashboard.stock-alerts :skus="$tables['low_stock_skus']" />
--}}
@props (['skus'])

<div class="overflow-hidden rounded-xl border border-red-100 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-red-100 bg-red-50/50 px-6 py-4">
        <div class="flex items-center gap-2">
            <i data-lucide="siren" class="h-5 w-5 text-red-500"></i>
            <h2 class="text-sm font-black tracking-wider text-red-800 uppercase">Stock Alerts</h2>
        </div>
        @if ($skus->count() > 0)
            <a
                href="{{ route('admin.inventory.reports.index') }}"
                class="text-xs font-bold text-red-600 transition-colors hover:text-red-700"
            >
                Inventory Report &rarr;
            </a>
        @endif
    </div>

    <div class="custom-scrollbar overflow-x-auto">
        @if ($skus->isEmpty())
            <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                <i data-lucide="check-circle-2" class="mb-3 h-12 w-12 text-emerald-400 opacity-50"></i>
                <span class="text-sm font-bold">All inventory levels are healthy!</span>
            </div>
        @else
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead
                    class="border-b border-gray-100 bg-white text-[10px] font-black tracking-wider text-gray-400 uppercase"
                >
                    <tr>
                        <th class="px-6 py-4">SKU</th>
                        <th class="px-6 py-4">Product</th>
                        <th class="px-6 py-4 text-center">Current Quantity</th>
                        <th class="px-6 py-4 text-center">Alert Quantity</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($skus as $sku)
                        <tr class="transition-colors hover:bg-red-50/30">
                            <td class="px-6 py-4 font-mono text-xs font-bold text-gray-500">{{ $sku->sku }}</td>
                            <td class="px-6 py-4 font-bold text-gray-800">{{ $sku->product->name ?? 'Unknown' }}</td>
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="px-2.5 py-1 rounded-md text-xs font-bold {{ $sku->current_stock <= 0 ? 'bg-red-100 text-red-700' : 'bg-cyan-100 text-cyan-700' }}"
                                >
                                    {{ (float) $sku->current_stock }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="rounded-md bg-rose-100 px-2.5 py-1 text-xs font-bold text-rose-700">
                                    {{ (float) $sku->stock_alert }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
