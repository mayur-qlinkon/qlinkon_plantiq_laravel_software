@extends('layouts.storefront')

@section('title', 'Order Confirmed - #' . $order->order_number)

@section('content')
<div class="bg-gray-50 min-h-screen py-12 pb-24">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- ════════ SUCCESS HEADER ════════ --}}
        <div class="bg-white rounded-3xl p-8 sm:p-12 shadow-sm border border-gray-100 text-center mb-8 relative overflow-hidden">
            {{-- Decorative background blob --}}
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-lg h-32 bg-green-50 rounded-full blur-3xl opacity-50 pointer-events-none"></div>
            
            <div class="relative z-10">
                <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i data-lucide="check-circle-2" class="w-10 h-10"></i>
                </div>
                
                <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight mb-3">
                    Thank you for your order!
                </h1>
                <p class="text-gray-500 text-base sm:text-lg max-w-2xl mx-auto mb-8">
                    Hi {{ explode(' ', $order->customer_name)[0] }}, your order <span class="font-bold text-gray-800">#{{ $order->order_number }}</span> has been successfully placed. We'll contact you shortly regarding the delivery.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    {{-- Download Receipt --}}
                    <a href="{{ tenant_url('orders/' . $order->order_number . '/receipt') }}" 
                       class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-bold text-white transition-all hover:opacity-90 shadow-sm"
                       style="background-color: var(--brand-600);">
                        <i data-lucide="download" class="w-4 h-4"></i> Download Receipt
                    </a>
                    
                    {{-- WhatsApp Support / Owner Ping --}}
                    @if($whatsapp = get_setting('whatsapp', null, $company->id))
                        @php
                            $waUrl = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $whatsapp) . '?text=' . urlencode("Hi, I just placed order #{$order->order_number}. I have a question regarding my order.");
                        @endphp
                        <a href="{{ $waUrl }}" target="_blank" 
                           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-bold bg-[#25D366] text-white hover:bg-[#1ebd5a] transition-colors shadow-sm">
                            <i data-lucide="message-circle" class="w-4 h-4"></i> Contact Support
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            {{-- ════════ LEFT COLUMN: ORDER ITEMS & TOTALS (8 cols) ════════ --}}
            <div class="lg:col-span-8 space-y-8">
                
                {{-- Order Items Card --}}
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                        <h2 class="text-lg font-bold text-gray-900">Order Items</h2>
                    </div>
                    
                    <div class="divide-y divide-gray-100">
                        @foreach($order->items as $item)
                            <div class="p-6 flex flex-col sm:flex-row gap-5 items-start sm:items-center">
                                {{-- Product Image --}}
                                <div class="w-20 h-20 rounded-xl bg-gray-50 border border-gray-100 flex-shrink-0 overflow-hidden flex items-center justify-center">
                                    @if($item->product_image)
                                        <img src="{{ asset('storage/' . $item->product_image) }}" alt="{{ $item->product_name }}" class="w-full h-full object-cover">
                                    @else
                                        <i data-lucide="image" class="w-8 h-8 text-gray-300"></i>
                                    @endif
                                </div>
                                
                                {{-- Product Details --}}
                                <div class="flex-1 min-w-0 w-full">
                                    <h3 class="text-base font-bold text-gray-900 truncate">{{ $item->product_name }}</h3>
                                    @if($item->sku_label)
                                        <p class="text-sm text-gray-500 mt-1 flex items-center gap-1.5">
                                            <i data-lucide="tag" class="w-3.5 h-3.5"></i> {{ $item->sku_label }}
                                        </p>
                                    @endif
                                    <div class="mt-2 flex items-center justify-between sm:hidden">
                                        <p class="text-sm text-gray-500">Qty: {{ $item->qty }}</p>
                                        <p class="font-bold text-gray-900">₹{{ number_format($item->line_total, 2) }}</p>
                                    </div>
                                </div>

                                {{-- Pricing Details (Desktop) --}}
                                <div class="hidden sm:flex flex-col items-end text-right ml-4">
                                    <p class="font-bold text-gray-900 text-lg">₹{{ number_format($item->line_total, 2) }}</p>
                                    <p class="text-sm text-gray-500 mt-1">{{ $item->qty }} x ₹{{ number_format($item->unit_price, 2) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Order Totals Summary --}}
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-6 space-y-4">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500 font-medium">Subtotal ({{ $order->items_count }} items)</span>
                            <span class="font-semibold text-gray-900">₹{{ number_format($order->subtotal, 2) }}</span>
                        </div>
                        
                        @if($order->discount_amount > 0)
                        <div class="flex justify-between items-center text-sm text-green-600">
                            <span class="font-medium flex items-center gap-1.5">
                                <i data-lucide="tags" class="w-4 h-4"></i> Discount
                            </span>
                            <span class="font-semibold">-₹{{ number_format($order->discount_amount, 2) }}</span>
                        </div>
                        @endif
                        
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500 font-medium">Taxes (GST)</span>
                            <span class="font-semibold text-gray-900">+₹{{ number_format($order->tax_amount, 2) }}</span>
                        </div>

                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500 font-medium">Shipping</span>
                            <span class="font-semibold text-gray-900">{{ $order->shipping_amount > 0 ? '+₹' . number_format($order->shipping_amount, 2) : 'Free' }}</span>
                        </div>

                        @if($order->round_off != 0)
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500 font-medium">Round Off</span>
                            <span class="font-semibold text-gray-900">{{ $order->round_off > 0 ? '+' : '' }}₹{{ number_format($order->round_off, 2) }}</span>
                        </div>
                        @endif
                    </div>
                    
                    <div class="px-6 py-5 bg-gray-50 border-t border-gray-100">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-bold text-gray-900">Total Amount</span>
                            <span class="text-2xl font-black" style="color: var(--brand-600);">₹{{ number_format($order->total_amount, 2) }}</span>
                        </div>
                        <p class="text-xs text-gray-400 text-right mt-1">Payment Method: <span class="uppercase font-semibold text-gray-500">{{ $order->payment_method }}</span></p>
                    </div>
                </div>

            </div>

            {{-- ════════ RIGHT COLUMN: CUSTOMER INFO (4 cols) ════════ --}}
            <div class="lg:col-span-4 space-y-6">
                
                {{-- Order Details Card --}}
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">Order Info</h3>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="hash" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <p class="text-[11px] text-gray-500 font-bold uppercase tracking-wider">Order Number</p>
                                <p class="text-sm font-bold text-gray-900">{{ $order->order_number }}</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="calendar" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <p class="text-[11px] text-gray-500 font-bold uppercase tracking-wider">Date Placed</p>
                                <p class="text-sm font-bold text-gray-900">{{ $order->created_at->format('d M Y, h:i A') }}</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="activity" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <p class="text-[11px] text-gray-500 font-bold uppercase tracking-wider">Status</p>
                                <span class="inline-flex items-center px-2 py-0.5 mt-1 rounded text-xs font-bold bg-gray-100 text-gray-800 uppercase tracking-wider">
                                    {{ $order->status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Customer Info Card --}}
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">Customer Details</h3>
                    
                    <div class="space-y-4 text-sm">
                        <div>
                            <p class="font-bold text-gray-900">{{ $order->customer_name }}</p>
                            <p class="text-gray-500 mt-1 flex items-center gap-2">
                                <i data-lucide="phone" class="w-3.5 h-3.5"></i> {{ $order->customer_phone }}
                            </p>
                            @if($order->customer_email)
                            <p class="text-gray-500 mt-1 flex items-center gap-2">
                                <i data-lucide="mail" class="w-3.5 h-3.5"></i> {{ $order->customer_email }}
                            </p>
                            @endif
                        </div>

                        @if($order->delivery_address)
                        <div class="pt-4 border-t border-gray-100">
                            <p class="text-[11px] text-gray-500 font-bold uppercase tracking-wider mb-2">Delivery Address</p>
                            <p class="text-gray-700 leading-relaxed">
                                {{ $order->delivery_address }}<br>
                                @if($order->delivery_city) {{ $order->delivery_city }}, @endif 
                                @if($order->delivery_state) {{ $order->delivery_state }} @endif 
                                @if($order->delivery_pincode) - {{ $order->delivery_pincode }} @endif
                            </p>
                        </div>
                        @endif

                        @if($order->customer_notes)
                        <div class="pt-4 border-t border-gray-100">
                            <p class="text-[11px] text-gray-500 font-bold uppercase tracking-wider mb-2">Order Notes</p>
                            <div class="p-3 bg-yellow-50 text-yellow-800 rounded-xl text-xs leading-relaxed border border-yellow-100">
                                {{ $order->customer_notes }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                
                {{-- Continue Shopping --}}
                <a href="{{ tenant_url('') }}" class="block w-full py-4 text-center text-sm font-bold text-gray-600 hover:text-gray-900 bg-white rounded-3xl shadow-sm border border-gray-100 hover:bg-gray-50 transition-colors">
                    &larr; Continue Shopping
                </a>

            </div>
        </div>

    </div>
</div>
@endsection