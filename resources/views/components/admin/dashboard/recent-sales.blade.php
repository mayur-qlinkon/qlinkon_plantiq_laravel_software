{{--
    Reusable "Recent Sales" dashboard widget — Invoicing module.
    Usage: <x-admin.dashboard.recent-sales :sales="$tables['recent_sales']" />
--}}
@props (['sales'])

@php
    $formatAmt = fn ($amount) => number_format((float) $amount, 2, '.', ',');
@endphp

<div class="mb-8 overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50/50 px-6 py-4">
        <div class="flex items-center gap-2">
            <i data-lucide="shopping-cart" class="h-5 w-5 text-gray-400"></i>
            <h2 class="text-sm font-black tracking-wider text-gray-800 uppercase">Recent Sales</h2>
        </div>
        <a
            href="{{ route('admin.invoices.index') }}"
            class="text-brand-600 hover:text-brand-700 text-xs font-bold transition-colors"
        >
            View All &rarr;
        </a>
    </div>
    <div class="custom-scrollbar overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead
                class="border-b border-gray-100 bg-white text-[10px] font-black tracking-wider text-gray-400 uppercase"
            >
                <tr>
                    <th class="px-6 py-4">Reference</th>
                    <th class="px-6 py-4">Customer</th>
                    <th class="px-6 py-4 text-center">Status</th>
                    <th class="px-6 py-4 text-right">Grand Total</th>
                    <th class="px-6 py-4 text-right">Paid</th>
                    <th class="px-6 py-4 text-right">Due</th>
                    <th class="px-6 py-4 text-center">Payment Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($sales as $sale)
                    <tr class="transition-colors hover:bg-gray-50/50">
                        <td class="px-6 py-4 font-bold text-gray-700">{{ $sale['reference'] }}</td>
                        <td class="px-6 py-4 font-semibold text-gray-600">{{ $sale['customer'] }}</td>
                        <td class="px-6 py-4 text-center">
                            <span
                                class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider
                                {{ $sale['status'] === 'confirmed' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}"
                            >
                                {{ $sale['status'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right font-black text-gray-900">
                            ₹ {{ $formatAmt($sale['grand_total']) }}
                        </td>
                        <td class="px-6 py-4 text-right font-semibold text-gray-600">
                            ₹ {{ $formatAmt($sale['paid']) }}
                        </td>
                        <td
                            class="px-6 py-4 text-right font-bold {{ $sale['due'] > 0 ? 'text-red-500' : 'text-gray-400' }}"
                        >
                            ₹ {{ $formatAmt($sale['due']) }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            @php
                                $payBadge = match ($sale['payment_status']) {
                                    'paid' => 'bg-green-100 text-green-700',
                                    'partial' => 'bg-amber-100 text-amber-700',
                                    default => 'bg-red-100 text-red-700',
                                };
                            @endphp
                            <span
                                class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider {{ $payBadge }}"
                            >
                                {{ $sale['payment_status'] }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-sm font-medium text-gray-400">
                            No recent sales found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
