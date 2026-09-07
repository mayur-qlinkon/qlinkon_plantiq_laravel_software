@extends('layouts.customer')

@section('title', 'My Orders')

@section('content')
    <div class="space-y-6 lg:space-y-8">

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-2">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Order History</h1>
                <p class="text-slate-500 text-sm mt-1.5">Track your recent orders, view details, and download receipts.</p>
            </div>
        </div>

        {{-- Orders List --}}
        <div class="space-y-6">
            @forelse ($orders as $order)
                @php
                    // Map your DB statuses to semantic UI colors
                    $statusColor = match ($order->status) {
                        'delivered' => 'emerald',
                        'shipped' => 'blue',
                        'cancelled' => 'red',
                        'processing' => 'indigo',
                        'confirmed' => 'teal',
                        default => 'amber', // 'inquiry', 'pending'
                    };

                    // Logical Timeline Progression for Storefront
                    $steps = ['inquiry', 'confirmed', 'processing', 'shipped', 'delivered'];
                    $currentStepIndex = array_search($order->status, $steps);

                    if ($order->status === 'cancelled') {
                        $currentStepIndex = -1; // Cancelled breaks the timeline
                    } elseif ($currentStepIndex === false) {
                        $currentStepIndex = 0; // Fallback
                    }
                @endphp

                <div
                    class="bg-white border border-slate-200 rounded-2xl overflow-hidden hover:shadow-md transition-shadow duration-300">

                    {{-- 1. Card Header: Summary & Actions --}}
                    <div
                        class="bg-slate-50/80 px-5 sm:px-6 py-4 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100">
                        <div class="flex flex-wrap sm:flex-nowrap items-center gap-x-6 gap-y-3">
                            <div>
                                <span
                                    class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Order
                                    ID</span>
                                <p class="font-black text-slate-900 text-base sm:text-lg tracking-tight">
                                    #{{ $order->order_number }}</p>
                            </div>
                            <div class="hidden sm:block w-px h-8 bg-slate-200"></div>
                            <div>
                                <span
                                    class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Date
                                    Placed</span>
                                <p class="font-semibold text-slate-700 text-sm sm:text-base">
                                    {{ $order->created_at->format('M d, Y') }}</p>
                            </div>
                            <div class="hidden sm:block w-px h-8 bg-slate-200"></div>
                            <div>
                                <span
                                    class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Total
                                    Amount</span>
                                <p class="font-black text-slate-900 text-sm sm:text-base">
                                    ₹{{ number_format($order->total_amount, 2) }}</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between md:justify-end gap-4 w-full md:w-auto mt-2 md:mt-0">
                            {{-- Status Badge --}}
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-[11px] font-black uppercase bg-{{ $statusColor }}-50 text-{{ $statusColor }}-700 border border-{{ $statusColor }}-100 tracking-wide">
                                @if (!in_array($order->status, ['delivered', 'cancelled']))
                                    <span class="w-1.5 h-1.5 rounded-full bg-{{ $statusColor }}-500 animate-pulse"></span>
                                @endif
                                {{ $order->status === 'inquiry' ? 'Pending Confirmation' : ucfirst($order->status) }}
                            </span>

                            {{-- Receipt Button (Uses correct route) --}}
                            <a href="{{ tenant_url('orders/' . $order->order_number . '/receipt') }}" target="_blank"
                                class="group flex items-center gap-2 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-600 uppercase tracking-wide hover:border-brand-500 hover:text-brand-500 hover:bg-brand-500/5 transition-all shadow-sm"
                                title="Download Receipt">
                                <i data-lucide="file-down"
                                    class="w-4 h-4 text-slate-400 group-hover:text-brand-500 transition-colors"></i>
                                <span class="hidden sm:inline">Receipt</span>
                            </a>
                        </div>
                    </div>

                    {{-- 2. Card Body: Grid Layout --}}
                    <div class="p-5 sm:p-6 grid grid-cols-1 lg:grid-cols-3 gap-8">

                        {{-- Left Col: Timeline & Payment --}}
                        <div class="lg:col-span-1 space-y-6">

                            {{-- Visual Tracker --}}
                            @if ($order->status !== 'cancelled')
                                <div>
                                    <h4 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-4">
                                        Tracking Status</h4>
                                    <div class="relative flex flex-col gap-4 border-l-2 border-slate-100 pl-5 ml-2.5">
                                        @foreach ($steps as $index => $step)
                                            <div class="relative">
                                                <div
                                                    class="absolute -left-[27px] top-0.5 w-2.5 h-2.5 rounded-full ring-4 ring-white {{ $index <= $currentStepIndex ? "bg-{$statusColor}-500" : 'bg-slate-200' }}">
                                                </div>
                                                <p
                                                    class="text-xs font-bold {{ $index <= $currentStepIndex ? 'text-slate-900' : 'text-slate-400' }} uppercase tracking-wide">
                                                    {{ $step === 'inquiry' ? 'Placed' : ucfirst($step) }}
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="bg-red-50 border border-red-100 rounded-xl p-4">
                                    <h4 class="text-[11px] font-black text-red-600 uppercase tracking-widest mb-1">Order
                                        Cancelled</h4>
                                    <p class="text-xs text-red-500 font-medium">This order was cancelled and will not be
                                        fulfilled.</p>
                                </div>
                            @endif

                            {{-- Payment Info --}}
                            <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-semibold text-slate-500">Payment Status</span>
                                    <span
                                        class="text-[10px] font-black px-2 py-0.5 rounded-md uppercase tracking-wide {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ ucfirst($order->payment_status) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-slate-500">Method</span>
                                    <span
                                        class="text-xs font-bold text-slate-700 uppercase">{{ $order->payment_method }}</span>
                                </div>
                                @if ($order->coupon_code)
                                    <div class="flex items-center justify-between pt-2 mt-2 border-t border-slate-200">
                                        <span class="text-xs font-semibold text-slate-500">Coupon</span>
                                        <span
                                            class="text-xs font-mono font-black text-green-600">{{ $order->coupon_code }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Right Col: Items List --}}
                        <div class="lg:col-span-2">
                            <h4 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-3">
                                Items ({{ $order->items->count() }})
                            </h4>
                            <div class="space-y-2">
                                @foreach ($order->items as $item)
                                    <div
                                        class="flex items-center justify-between p-3 rounded-xl bg-white border border-slate-100 shadow-sm hover:border-brand-500/30 transition-colors group">
                                        <div class="flex items-center gap-4">
                                            {{-- Use actual product image if available, else placeholder --}}
                                            @if ($item->product_image)
                                                <img src="{{ asset('storage/' . $item->product_image) }}" alt="Product"
                                                    class="w-12 h-12 object-cover rounded-lg bg-slate-50 border border-slate-100 shrink-0">
                                            @else
                                                <div
                                                    class="w-12 h-12 bg-slate-50 rounded-lg border border-slate-100 flex items-center justify-center text-slate-300 shrink-0">
                                                    <i data-lucide="package" class="w-5 h-5"></i>
                                                </div>
                                            @endif

                                            <div>
                                                <p
                                                    class="font-bold text-slate-900 text-sm group-hover:text-brand-500 transition-colors line-clamp-1">
                                                    {{ $item->product_name }}
                                                </p>
                                                @if ($item->sku_label)
                                                    <p class="text-[11px] text-slate-400 font-medium mt-0.5">
                                                        {{ $item->sku_label }}</p>
                                                @endif
                                                <p class="text-xs font-semibold text-slate-500 mt-0.5">Qty:
                                                    {{ $item->qty }}</p>
                                            </div>
                                        </div>

                                        <div class="font-black text-slate-900 text-sm shrink-0">
                                            ₹{{ number_format($item->line_total, 2) }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- 3. Card Footer: Help --}}
                    <div
                        class="bg-slate-50/80 px-5 sm:px-6 py-3 border-t border-slate-100 flex justify-between items-center">
                        <p class="text-xs font-semibold text-slate-500">Need help with this order?</p>
                        @php
                            $whatsapp = get_setting('whatsapp', null, $order->company_id);
                        @endphp
                        @if ($whatsapp)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}?text=Hi, I need help with my order #{{ $order->order_number }}"
                                target="_blank"
                                class="text-xs font-bold text-brand-500 hover:text-brand-700 transition-colors flex items-center gap-1.5">
                                <i data-lucide="message-circle" class="w-3.5 h-3.5"></i> Support
                            </a>
                        @else
                            <span class="text-xs font-medium text-slate-400">Contact Store Admin</span>
                        @endif
                    </div>
                </div>
            @empty
                {{-- Empty State (Uses Dynamic brand-500 Theme) --}}
                <div class="text-center py-20 bg-white rounded-2xl border border-dashed border-slate-200 shadow-sm">
                    <div class="w-20 h-20 bg-brand-500/10 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="shopping-bag" class="w-10 h-10 text-brand-500"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-900">No orders found</h3>
                    <p class="text-slate-500 mt-2 max-w-sm mx-auto font-medium text-sm">It looks like you haven't placed any
                        orders yet. Start shopping to fill this page!</p>
                    <a href="{{ tenant_url('') }}"
                        class="inline-flex items-center gap-2 mt-8 bg-brand-500 hover:bg-brand-700 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5">
                        <i data-lucide="store" class="w-4 h-4"></i> Start Shopping
                    </a>
                </div>
            @endforelse
        </div>

        {{-- Pagination Links --}}
        @if ($orders->hasPages())
            <div class="mt-8 pt-4">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    {{-- Note: Icons are initialized in the layout blade automatically, no need to duplicate the script tag here --}}
@endsection
