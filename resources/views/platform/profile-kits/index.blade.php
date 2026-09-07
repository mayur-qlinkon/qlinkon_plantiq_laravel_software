@extends('layouts.platform')

@section('title', 'Profile Kits')
@section('header', 'Profile Kits Management')

@section('content')
    <div class="max-w-7xl mx-auto pb-12">
        
        {{-- Top Action Bar --}}
        <div class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Hardware & Kits</h1>
                <p class="text-sm text-gray-500 mt-1">Manage physical QR/NFC tags, pricing, and printed kits inventory.</p>
            </div>
            <a href="{{ route('platform.profile-kits.create') }}" class="bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition-all shadow-md shadow-brand-500/20 flex items-center justify-center gap-2 shrink-0">
                <i class="fa-solid fa-plus"></i> Create New Kit
            </a>
        </div>

        {{-- Alerts --}}
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
            @forelse($kits as $kit)
                {{-- Individual Kit Card --}}
                <div class="bg-white rounded shadow-sm border border-gray-200 overflow-hidden flex flex-col hover:shadow-md transition-shadow group">
                    
                    {{-- Image Header (Squared) --}}
                    <div class="aspect-square bg-gray-50 relative overflow-hidden border-b border-gray-100">
                        @if($kit->image)
                            <img src="{{ asset('storage/' . $kit->image) }}" alt="{{ $kit->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        @else
                            <div class="w-full h-full flex flex-col items-center justify-center text-gray-400 bg-gray-100/50 group-hover:scale-105 transition-transform duration-500">
                                <i class="fa-solid fa-tags text-4xl mb-3 text-gray-300"></i>
                                <span class="text-xs font-medium">No Image Provided</span>
                            </div>
                        @endif

                        {{-- Floating Badges --}}
                        <div class="absolute top-3 left-3">
                            @if($kit->is_active)
                                <span class="bg-green-500 text-white text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded shadow-sm">Active</span>
                            @else
                                <span class="bg-gray-500 text-white text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded shadow-sm">Inactive</span>
                            @endif
                        </div>
                        <div class="absolute top-3 right-3">
                            <span class="bg-white/90 backdrop-blur-sm text-gray-700 text-[10px] font-bold px-2 py-1 rounded shadow-sm flex items-center gap-1 border border-gray-200/50" title="Sort Order">
                                <i class="fa-solid fa-layer-group text-brand-600"></i> {{ $kit->sort_order }}
                            </span>
                        </div>
                    </div>

                    {{-- Card Content --}}
                    <div class="p-5 flex flex-col flex-1">
                        <h3 class="text-lg font-bold text-gray-900 line-clamp-1 mb-1" title="{{ $kit->name }}">{{ $kit->name }}</h3>
                        
                        @if($kit->description)
                            <p class="text-sm text-gray-500 line-clamp-2 mb-4 flex-1" title="{{ $kit->description }}">{{ $kit->description }}</p>
                        @else
                            <p class="text-sm text-gray-400 italic mb-4 flex-1">No description provided.</p>
                        @endif

                        <div class="flex items-end justify-between mb-5 mt-auto">
                            <div>
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-0.5">Price Per Unit</span>
                                <span class="text-xl font-extrabold text-brand-600">₹{{ number_format($kit->price, 2) }}</span>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="grid grid-cols-4 gap-2 pt-4 border-t border-gray-100">
                            <a href="{{ route('platform.profile-kits.edit', $kit->id) }}" class="col-span-3 bg-gray-50 hover:bg-brand-50 hover:border-brand-200 hover:text-brand-700 border border-gray-200 text-gray-700 rounded-xl py-2.5 text-sm font-semibold transition-colors flex items-center justify-center gap-2">
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </a>
                            <form action="{{ route('platform.profile-kits.destroy', $kit->id) }}" method="POST" class="col-span-1 block" onsubmit="return confirm('Are you sure you want to permanently delete this hardware kit?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full h-full bg-gray-50 hover:bg-red-50 hover:border-red-200 hover:text-red-600 border border-gray-200 text-gray-500 rounded-xl flex items-center justify-center transition-colors" title="Delete Kit">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                {{-- Empty State (Spans full grid) --}}
                <div class="col-span-1 sm:col-span-2 lg:col-span-3 xl:col-span-4 bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center flex flex-col items-center justify-center min-h-[400px]">
                    <div class="w-20 h-20 bg-brand-50 rounded-full flex items-center justify-center mb-5 text-brand-600 border border-brand-100 shadow-sm">
                        <i class="fa-solid fa-tags text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">No Profile Kits Yet</h3>
                    <p class="text-sm text-gray-500 mb-6 max-w-md mx-auto">You haven't added any physical hardware kits or tags. Create your first kit to offer it during the subscription checkout process.</p>
                    <a href="{{ route('platform.profile-kits.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand-600 text-white text-sm font-semibold rounded-lg hover:bg-brand-700 transition-colors shadow-sm">
                        <i class="fa-solid fa-plus"></i> Create First Kit
                    </a>
                </div>
            @endforelse
        </div>
        
        {{-- Pagination --}}
        @if($kits->hasPages())
            <div class="mt-8">
                {{ $kits->links() }}
            </div>
        @endif
    </div>
@endsection