@extends('layouts.platform')

@section('title', 'Plant Profiles')
@section('header', 'Plant Profiles Management')

@section('content')
    <div class="max-w-7xl mx-auto pb-12">
        
        {{-- Top Action Bar --}}
        <div class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Plant Profiles (Tiers)</h1>
                <p class="text-sm text-gray-500 mt-1">Manage the base plant capacity tiers for nursery subscriptions.</p>
            </div>
            <a href="{{ route('platform.plant-profiles.create') }}" class="bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition-all shadow-md shadow-brand-500/20 flex items-center justify-center gap-2 shrink-0">
                <i class="fa-solid fa-plus"></i> Create Profile Tier
            </a>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mb-6 flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 text-sm px-5 py-4 rounded-xl shadow-sm">
                <i class="fa-solid fa-circle-check text-green-600 text-xl shrink-0"></i> 
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 flex items-start gap-3 bg-red-50 border border-red-200 text-red-800 text-sm px-5 py-4 rounded-xl shadow-sm">
                <i class="fa-solid fa-triangle-exclamation text-red-600 text-xl shrink-0 mt-0.5"></i> 
                <span class="font-medium">{{ session('error') }}</span>
            </div>
        @endif

        {{-- Grid View --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @forelse($profiles as $profile)
                {{-- Individual Profile Card --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col hover:shadow-md transition-shadow group">
                    
                    {{-- Graphic Header (Squared) --}}
                    <div class="aspect-square bg-gray-50 relative overflow-hidden border-b border-gray-100 flex items-center justify-center">
                        @if($profile->image)
                            <img src="{{ asset('storage/' . $profile->image) }}" alt="{{ $profile->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        @else
                            <div class="absolute inset-0 bg-gradient-to-br from-green-50 to-green-100/50 flex items-center justify-center">
                                {{-- Subtle background pattern/icon --}}
                                <i class="fa-solid fa-seedling text-[5rem] text-green-200/50 absolute -bottom-4 -right-4 transform -rotate-12 group-hover:scale-110 transition-transform duration-700"></i>
                                
                                {{-- Main centered icon --}}
                                <div class="w-20 h-20 bg-white/80 backdrop-blur-sm rounded-full shadow-sm flex items-center justify-center group-hover:scale-110 transition-transform duration-500 z-10">
                                    <i class="fa-solid fa-seedling text-3xl text-green-500"></i>
                                </div>
                            </div>
                        @endif

                        {{-- Floating Badges --}}
                        <div class="absolute top-3 left-3 z-20">
                            @if($profile->is_active)
                                <span class="bg-green-500 text-white text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded shadow-sm">Active</span>
                            @else
                                <span class="bg-gray-500 text-white text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded shadow-sm">Inactive</span>
                            @endif
                        </div>
                        <div class="absolute top-3 right-3 z-20">
                            <span class="bg-white/90 backdrop-blur-sm text-gray-700 text-[10px] font-bold px-2 py-1 rounded shadow-sm flex items-center gap-1 border border-gray-200/50" title="Sort Order">
                                <i class="fa-solid fa-layer-group text-brand-600"></i> {{ $profile->sort_order }}
                            </span>
                        </div>
                    </div>

                    {{-- Card Content --}}
                    <div class="p-5 flex flex-col flex-1">
                        <h3 class="text-lg font-bold text-gray-900 line-clamp-1 mb-3" title="{{ $profile->name }}">{{ $profile->name }}</h3>
                        
                        {{-- Capacity Highlight Box --}}
                        <div class="flex items-center gap-3 bg-gray-50 border border-gray-100 rounded-lg px-3 py-2.5 mb-5 flex-1">
                            <div class="w-8 h-8 rounded bg-white shadow-sm flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-leaf text-gray-400 text-sm"></i>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Capacity</span>
                                <span class="text-sm font-semibold text-gray-700">
                                    {{ $profile->plant_limit > 0 ? number_format($profile->plant_limit) . ' Plants' : 'Unlimited' }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-end justify-between mb-5 mt-auto">
                            <div>
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-0.5">Base Price</span>
                                <span class="text-xl font-extrabold text-brand-600">₹{{ number_format($profile->price, 2) }}</span>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="grid grid-cols-4 gap-2 pt-4 border-t border-gray-100">
                            <a href="{{ route('platform.plant-profiles.edit', $profile->id) }}" class="col-span-3 bg-gray-50 hover:bg-brand-50 hover:border-brand-200 hover:text-brand-700 border border-gray-200 text-gray-700 rounded-xl py-2.5 text-sm font-semibold transition-colors flex items-center justify-center gap-2">
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </a>
                            <form action="{{ route('platform.plant-profiles.destroy', $profile->id) }}" method="POST" class="col-span-1 block" onsubmit="return confirm('Are you sure you want to permanently delete this tier? It might break existing subscriptions relying on it.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full h-full bg-gray-50 hover:bg-red-50 hover:border-red-200 hover:text-red-600 border border-gray-200 text-gray-500 rounded-xl flex items-center justify-center transition-colors" title="Delete Tier">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                {{-- Empty State (Spans full grid) --}}
                <div class="col-span-1 sm:col-span-2 lg:col-span-3 xl:col-span-4 bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center flex flex-col items-center justify-center min-h-[400px]">
                    <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mb-5 text-green-500 border border-green-100 shadow-sm">
                        <i class="fa-solid fa-seedling text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">No Plant Profiles Found</h3>
                    <p class="text-sm text-gray-500 mb-6 max-w-md mx-auto">You haven't added any capacity tiers yet. Create your first profile tier (e.g., "50 Plants" or "Unlimited") to get started.</p>
                    <a href="{{ route('platform.plant-profiles.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand-600 text-white text-sm font-semibold rounded-lg hover:bg-brand-700 transition-colors shadow-sm">
                        <i class="fa-solid fa-plus"></i> Create First Tier
                    </a>
                </div>
            @endforelse
        </div>
        
        {{-- Pagination --}}
        @if($profiles->hasPages())
            <div class="mt-8">
                {{ $profiles->links() }}
            </div>
        @endif
    </div>
@endsection