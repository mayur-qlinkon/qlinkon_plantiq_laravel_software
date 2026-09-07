@extends('layouts.platform')

@section('title', 'Promotion Details - Super Admin')
@section('header', 'Promotion Details')

@section('content')
    {{-- Top Action Bar --}}
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <a href="{{ route('platform.promotions.index') }}" class="text-sm text-brand-600 hover:text-brand-700 font-medium flex items-center gap-2 mb-2 transition">
                <i class="fa-solid fa-arrow-left"></i> Back to Promotions
            </a>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-bold text-gray-800">
                    {{ $promotion->code ?? $promotion->name }}
                </h2>
                @if($promotion->is_active)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 border border-green-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactive
                    </span>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('platform.promotions.edit', $promotion->id) }}" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2.5 rounded-lg text-sm font-medium transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-pen-to-square"></i> Edit
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        
        {{-- Left Column: Core Details --}}
        <div class="lg:col-span-2 space-y-6">
            
            {{-- Overview Card --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-circle-info text-brand-600"></i> Configuration
                    </h3>
                    @if($promotion->auto_apply)
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                            <i class="fa-solid fa-bolt"></i> Auto-Applies
                        </span>
                    @endif
                </div>
                <div class="p-5">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Internal Name</dt>
                            <dd class="text-sm font-semibold text-gray-900">{{ $promotion->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Scope</dt>
                            <dd class="text-sm font-semibold text-gray-900">
                                @if($promotion->company_id)
                                    <i class="fa-solid fa-building text-gray-400 mr-1"></i> {{ $promotion->company->name ?? 'Specific Tenant' }}
                                @else
                                    <i class="fa-solid fa-globe text-gray-400 mr-1"></i> Platform Wide
                                @endif
                            </dd>
                        </div>
                        <div class="sm:col-span-2 border-t border-gray-100 pt-4 mt-2">
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Description</dt>
                            <dd class="text-sm text-gray-700">{{ $promotion->description ?: 'No description provided.' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Rules & Limits Card --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-scale-balanced text-brand-600"></i> Rules & Limits
                    </h3>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <span class="block text-xs font-medium text-gray-500 mb-1">Order Amount</span>
                            <div class="text-sm font-semibold text-gray-900">
                                @if($promotion->minimum_order_amount || $promotion->maximum_order_amount)
                                    {{ $promotion->minimum_order_amount ? '₹'.number_format($promotion->minimum_order_amount, 2) : '₹0' }} 
                                    - 
                                    {{ $promotion->maximum_order_amount ? '₹'.number_format($promotion->maximum_order_amount, 2) : 'No Max' }}
                                @else
                                    Any Amount
                                @endif
                            </div>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <span class="block text-xs font-medium text-gray-500 mb-1">Global Usage Limit</span>
                            <div class="text-sm font-semibold text-gray-900">
                                {{ $promotion->usage_limit ? number_format($promotion->usage_limit) . ' Total Uses' : 'Unlimited' }}
                            </div>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <span class="block text-xs font-medium text-gray-500 mb-1">Per Customer Limit</span>
                            <div class="text-sm font-semibold text-gray-900">
                                {{ $promotion->usage_per_customer ? $promotion->usage_per_customer . ' Time(s)' : 'Unlimited' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Stats & Value --}}
        <div class="space-y-6">
            
            {{-- Value Card --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-center gap-4">
                    <div class="w-12 h-12 bg-brand-50 rounded-full flex items-center justify-center text-brand-600 text-xl">
                        <i class="fa-solid fa-gift"></i>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-0.5">Discount Value</p>
                        <p class="text-xl font-bold text-gray-900">
                            @if($promotion->discount_type === 'percentage')
                                {{ floatval($promotion->discount_value) }}% Off
                            @elseif($promotion->discount_type === 'fixed')
                                ₹{{ number_format($promotion->discount_value, 2) }} Off
                            @else
                                <span class="capitalize">{{ str_replace('_', ' ', $promotion->discount_type) }}</span>
                            @endif
                        </p>
                    </div>
                </div>
                @if($promotion->discount_type === 'percentage' && $promotion->max_discount)
                    <div class="px-5 py-3 bg-yellow-50 text-yellow-800 text-xs font-medium border-t border-yellow-100 flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation"></i> Capped at maximum ₹{{ number_format($promotion->max_discount, 2) }}
                    </div>
                @endif
            </div>

            {{-- Stats & Schedule Card --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden p-5">
                
                <div class="mb-6">
                    <div class="flex justify-between items-end mb-2">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Times Used</span>
                        <span class="text-lg font-bold text-brand-600">{{ number_format($promotion->times_used) }}</span>
                    </div>
                    @if($promotion->usage_limit)
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-brand-500 h-2 rounded-full" style="width: {{ min(100, ($promotion->times_used / $promotion->usage_limit) * 100) }}%"></div>
                        </div>
                    @endif
                </div>

                <div class="space-y-4 border-t border-gray-100 pt-4">
                    <div>
                        <span class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                            <i class="fa-regular fa-clock"></i> Starts
                        </span>
                        <span class="text-sm font-semibold text-gray-900">
                            {{ $promotion->starts_at ? $promotion->starts_at->format('d M Y, h:i A') : 'Immediately' }}
                        </span>
                    </div>
                    <div>
                        <span class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                            <i class="fa-solid fa-hourglass-end"></i> Expires
                        </span>
                        <span class="text-sm font-semibold text-gray-900">
                            {{ $promotion->expires_at ? $promotion->expires_at->format('d M Y, h:i A') : 'Never (No Expiry)' }}
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Usage History / Audit Trail --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-5 border-b border-gray-200 flex items-center justify-between bg-gray-50/80">
            <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-gray-400"></i> Usage History
            </h3>
            <span class="bg-white border border-gray-200 text-gray-600 text-xs font-semibold px-2.5 py-1 rounded-md shadow-sm">
                {{ $promotion->usages->count() }} Records
            </span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap">
                <thead class="bg-white border-b border-gray-200">
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-4">Date & Time</th>
                        <th class="px-6 py-4">Client</th>
                        <th class="px-6 py-4">Applied To</th>
                        <th class="px-6 py-4 text-right">Discount Given</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($promotion->usages->sortByDesc('created_at') as $usage)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <span class="text-sm font-medium text-gray-900">{{ $usage->created_at->format('d M Y') }}</span>
                                <span class="text-xs text-gray-500 block">{{ $usage->created_at->format('h:i A') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                @if($usage->client)
                                    <div class="flex items-center gap-2 text-sm font-medium text-brand-600">
                                        <i class="fa-solid fa-user-circle text-gray-400"></i>
                                        {{ $usage->client->name }}
                                    </div>
                                @else
                                    <span class="text-sm text-gray-500 italic">Guest / Unknown</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                @if($usage->usable_type && $usage->usable_id)
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-gray-100 text-gray-600 text-xs font-medium border border-gray-200">
                                        {{ class_basename($usage->usable_type) }} #{{ $usage->usable_id }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm font-bold text-green-600">
                                    + ₹{{ number_format($usage->discount_amount, 2) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500 text-sm">
                                <i class="fa-solid fa-receipt text-gray-300 text-2xl mb-2 block"></i>
                                No one has used this promotion yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection