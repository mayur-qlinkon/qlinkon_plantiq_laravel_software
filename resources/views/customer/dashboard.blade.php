@extends('layouts.customer')
@section('title', 'Dashboard')

@section('content')
    {{-- Welcome Header --}}
    <div class="mb-6 lg:mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Welcome back, {{ explode(' ', $user->name)[0] }}!</h1>
        <p class="text-sm text-slate-500 mt-1.5">Manage your orders and account details for <span class="font-semibold text-slate-700">{{ $company->name }}</span>.</p>
    </div>

    <div class="space-y-6">
        
        {{-- Quick Stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-5 transition-shadow hover:shadow-md">
                <div class="w-14 h-14 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                    <i data-lucide="shopping-bag" class="w-6 h-6 text-primary"></i>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Orders</p>
                    <p class="text-3xl font-black text-slate-900 leading-none">{{ $totalOrdersCount }}</p>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-5 transition-shadow hover:shadow-md">
                <div class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center shrink-0">
                    <i data-lucide="truck" class="w-6 h-6 text-blue-500"></i>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">Active Deliveries</p>
                    <p class="text-3xl font-black text-slate-900 leading-none">0</p>
                </div>
            </div>
        </div>

        {{-- Recent Orders Widget --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-50 flex items-center justify-between bg-slate-50/50">
                <h2 class="text-base font-bold text-slate-900">Recent Orders</h2>
                @if($recentOrders->count() > 0)
                    <a href="{{ tenant_url('portal/orders') }}" class="text-sm font-bold text-primary hover:text-primaryDark transition-colors">View All &rarr;</a>
                @endif
            </div>
            
            @if($recentOrders->count() > 0)
                <div class="divide-y divide-slate-50">
                    @foreach($recentOrders as $order)
                        <div class="px-6 py-5 hover:bg-slate-50/80 transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-3 mb-1.5">
                                    <span class="font-mono font-bold text-slate-900">{{ $order->order_number }}</span>
                                    
                                    {{-- Status Badges --}}
                                    @if($order->payment_status === 'paid')
                                        <span class="px-2.5 py-0.5 rounded-md bg-green-50 text-green-700 text-[10px] font-black uppercase tracking-wider border border-green-100">Paid</span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-md bg-amber-50 text-amber-700 text-[10px] font-black uppercase tracking-wider border border-amber-100">Pending</span>
                                    @endif
                                </div>
                                <p class="text-sm text-slate-500 font-medium">
                                    {{ $order->created_at->format('M d, Y \a\t h:i A') }} • 
                                    <span class="text-slate-400">{{ $order->items_count }} {{ Str::plural('item', $order->items_count) }}</span>
                                </p>
                            </div>
                            
                            <div class="flex items-center justify-between sm:justify-end gap-6 w-full sm:w-auto mt-2 sm:mt-0">
                                <p class="font-black text-slate-900 text-lg tracking-tight">₹{{ number_format($order->total_amount, 2) }}</p>
                                <a href="{{ tenant_url('orders/'.$order->order_number.'/receipt') }}" 
                                   class="w-9 h-9 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-primary hover:border-primary hover:bg-primary/5 transition-all shadow-sm"
                                   title="Download Receipt" target="_blank">
                                    <i data-lucide="file-down" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-6 py-16 text-center">
                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-5">
                        <i data-lucide="shopping-cart" class="w-8 h-8 text-slate-300"></i>
                    </div>
                    <h3 class="text-slate-900 font-bold text-lg mb-1">No orders yet</h3>
                    <p class="text-sm text-slate-500 mb-8 max-w-sm mx-auto">Looks like you haven't placed any orders with us yet. Start shopping to see them here!</p>
                    <a href="{{ tenant_url('') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-bold text-white bg-black hover:bg-gray-900 transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5">
                        <i data-lucide="store" class="w-4 h-4"></i> Start Shopping
                    </a>
                </div>
            @endif
        </div>
        
    </div>
@endsection