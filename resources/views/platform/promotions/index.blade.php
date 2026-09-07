@extends('layouts.platform')

@section('title', 'Promotions')
@section('header', 'Promotions Management')

@section('content')
    {{-- Top Action Bar --}}
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">All Promotions</h2>
            <p class="text-sm text-gray-500">Manage platform-wide and tenant-specific discount coupons.</p>
        </div>
        <a href="{{ route('platform.promotions.create') }}" class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-plus"></i> Create Promotion
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-lg"></i> 
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-3">
            <i class="fa-solid fa-circle-exclamation text-lg"></i> 
            <span class="text-sm font-medium">{{ session('error') }}</span>
        </div>
    @endif

    {{-- Main Table Card --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap">
                <thead class="bg-gray-50/80 border-b border-gray-200">
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-4">Code / Name</th>
                        <th class="px-6 py-4">Scope</th>
                        <th class="px-6 py-4">Discount</th>
                        <th class="px-6 py-4">Usage</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($promotions as $promo)
                        <tr class="hover:bg-gray-50/50 transition">
                            
                            {{-- Code & Name --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                                        <i class="fa-solid fa-ticket"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-800">{{ $promo->code ?? 'NO-CODE' }}</p>
                                        <p class="text-xs text-gray-500">{{ $promo->name }}</p>
                                    </div>
                                </div>
                            </td>

                            {{-- Scope (Global vs Company) --}}
                            <td class="px-6 py-4">
                                @if($promo->company_id)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                        <i class="fa-solid fa-building"></i> {{ $promo->company->name ?? 'Tenant' }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-purple-50 text-purple-700 border border-purple-100">
                                        <i class="fa-solid fa-globe"></i> Platform
                                    </span>
                                @endif
                            </td>

                            {{-- Discount Type & Value --}}
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-semibold text-gray-800 capitalize">
                                        {{ str_replace('_', ' ', $promo->discount_type) }}
                                    </span>
                                    @if(in_array($promo->discount_type, ['fixed', 'percentage']) && $promo->discount_value)
                                        <span class="text-xs text-gray-500 font-medium">
                                            {{ $promo->discount_type === 'percentage' ? intval($promo->discount_value) . '%' : '₹' . number_format($promo->discount_value, 2) }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Usage Stats --}}
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-600 flex items-center gap-1">
                                    <span class="font-semibold text-gray-900">{{ number_format($promo->times_used) }}</span> 
                                    <span class="text-gray-400">/</span> 
                                    <span>{{ $promo->usage_limit ? number_format($promo->usage_limit) : '∞' }}</span>
                                </div>
                            </td>

                            {{-- Active Status --}}
                            <td class="px-6 py-4">
                                @if($promo->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 border border-green-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactive
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('platform.promotions.show', $promo->id) }}" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-brand-600 hover:bg-brand-50 rounded-md transition" title="View Details">
                                        <i class="fa-solid fa-eye text-sm"></i>
                                    </a>
                                    <a href="{{ route('platform.promotions.edit', $promo->id) }}" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-md transition" title="Edit">
                                        <i class="fa-solid fa-pen-to-square text-sm"></i>
                                    </a>
                                    
                                    <form action="{{ route('platform.promotions.destroy', $promo->id) }}" method="POST" class="inline-block m-0" onsubmit="return confirm('Are you sure you want to delete this promotion? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition" title="Delete">
                                            <i class="fa-solid fa-trash-can text-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        {{-- Empty State --}}
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 mx-auto bg-gray-50 rounded-full flex items-center justify-center mb-4 text-gray-400 border border-gray-100 shadow-sm">
                                    <i class="fa-solid fa-ticket text-2xl"></i>
                                </div>
                                <h3 class="text-base font-semibold text-gray-900 mb-1">No Promotions Found</h3>
                                <p class="text-sm text-gray-500 mb-4">You haven't created any discount codes or promotions yet.</p>
                                <a href="{{ route('platform.promotions.create') }}" class="inline-flex items-center gap-2 text-sm text-brand-600 hover:text-brand-700 font-semibold transition">
                                    <i class="fa-solid fa-plus"></i> Create Your First Promotion
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        @if($promotions->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">
                {{ $promotions->links() }}
            </div>
        @endif
    </div>
@endsection