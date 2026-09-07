@extends('layouts.admin')

@section('title', 'Warehouse Details')

@section('header-title')
    <h1 class="text-xs sm:text-sm font-bold text-gray-400 uppercase tracking-widest">Inventory / Warehouse Details</h1>
@endsection

@section('content')
    <div class="w-full mx-auto space-y-6 pb-10">

        {{-- 1. Header & Actions --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-[#212538] tracking-tight">{{ $warehouse->name }}</h2>
                <div class="flex flex-wrap items-center gap-2 mt-1.5 text-xs sm:text-sm text-gray-500 font-medium">
                    <span class="flex items-center gap-1"><i data-lucide="store" class="w-4 h-4"></i>
                        {{ $warehouse->store->name ?? 'Primary Store' }}</span>
                    <span class="hidden sm:inline text-gray-300">•</span>
                    <span class="flex items-center gap-1"><i data-lucide="map-pin" class="w-4 h-4"></i>
                        {{ $warehouse->city ?? 'Location not set' }}</span>
                </div>
            </div>
            <a href="{{ route('admin.warehouses.index') }}"
                class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg text-sm font-bold transition-colors shadow-sm shrink-0 flex items-center gap-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to List
            </a>
        </div>

        @php
            $isBatchEnabled = function_exists('batch_enabled') && batch_enabled();
        @endphp

        {{-- 2. Main Stock List Card (Matches Screenshot UI) --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">

            {{-- Table/List Header --}}
            <div class="px-5 sm:px-6 py-4 flex justify-between items-center border-b border-gray-100 bg-[#f8fafc]">
                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">PRODUCT</span>
                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">QUANTITY</span>
            </div>

            {{-- Stock Rows --}}
            <div class="divide-y divide-gray-100 bg-white">
                @forelse ($stocks as $stock)
                    @php
                        // Resolve image
                        $imagePath = $stock->sku->product->media->first()?->media_path;
                        $imageUrl = $imagePath ? asset('storage/' . $imagePath) : null;

                        // Bulletproof unit resolution
                        $unitName =
                            $stock->sku->unit->name ??
                            ($stock->sku->product->saleUnit->name ??
                                ($stock->sku->product->productUnit->name ?? 'Unit'));

                        // Check if this specific row has batches to display
                        $hasActiveBatches =
                            $isBatchEnabled &&
                            $stock->sku->relationLoaded('batches') &&
                            $stock->sku->batches->isNotEmpty();
                    @endphp

                    {{-- Alpine Component for Row Expansion --}}
                    <div x-data="{ expanded: false }" class="flex flex-col transition-colors duration-200"
                        :class="expanded ? 'bg-gray-50/50' : 'hover:bg-gray-50/30'">

                        {{-- 🌟 MAIN ROW (Visible) --}}
                        <div class="px-5 sm:px-6 py-4 flex items-center justify-between gap-4 {{ $hasActiveBatches ? 'cursor-pointer' : '' }}"
                            @if ($hasActiveBatches) @click="expanded = !expanded" @endif>

                            {{-- Product Left Side (Image & Name) --}}
                            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                                {{-- Circular Image --}}
                                <div
                                    class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-gray-100 border border-gray-200 overflow-hidden shrink-0 flex items-center justify-center">
                                    @if ($imageUrl)
                                        <img src="{{ $imageUrl }}" alt="{{ $stock->sku->product->name }}"
                                            class="w-full h-full object-cover">
                                    @else
                                        <i data-lucide="package" class="w-5 h-5 text-gray-400"></i>
                                    @endif
                                </div>

                                {{-- Name & SKU --}}
                                <div class="flex flex-col min-w-0">
                                    <span
                                        class="font-bold text-gray-700 text-sm sm:text-[15px] truncate">{{ $stock->sku->product->name }}</span>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span
                                            class="text-[10px] sm:text-[11px] text-gray-400 font-mono tracking-widest uppercase">{{ $stock->sku->sku }}</span>
                                        @if ($stock->sku->skuValues->isNotEmpty())
                                            <span
                                                class="text-[10px] sm:text-[11px] text-brand-500 font-medium bg-brand-50 px-1.5 rounded truncate">
                                                {{ $stock->sku->skuValues->map(fn($v) => $v->attributeValue->value)->implode(' / ') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Quantity Right Side (Badges) --}}
                            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                                {{-- Qty Badge (Light Blue) --}}
                                <span
                                    class="bg-[#e0f2fe] text-[#0284c7] px-2.5 py-1 rounded-md text-xs sm:text-[13px] font-black shadow-sm">
                                    {{ number_format($stock->qty, 0) }}
                                </span>

                                {{-- Unit Badge (Light Green) --}}
                                <span
                                    class="bg-[#dcfce7] text-[#16a34a] px-2 sm:px-3 py-1 rounded-md text-[10px] sm:text-xs font-bold lowercase tracking-wide shadow-sm hidden xs:inline-block">
                                    {{ $unitName }}
                                </span>

                                {{-- Expand Chevron (Only if batches exist) --}}
                                @if ($hasActiveBatches)
                                    <button
                                        class="w-6 h-6 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-transform duration-300 ml-1"
                                        :class="expanded ? 'rotate-180' : ''">
                                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- 🌟 EXPANDABLE BATCH TABLE (Hidden by default) --}}
                        @if ($hasActiveBatches)
                            <div x-show="expanded" x-collapse x-cloak>
                                {{-- 🌟 ROOT FIX 1: Reduced mobile padding (px-3 instead of px-5) to give the table more room --}}
                                <div class="px-3 sm:px-16 pb-4 pt-2 bg-gray-50/50">

                                    {{-- 🌟 ROOT FIX 2: Changed 'overflow-hidden' to 'overflow-x-auto' --}}
                                    <div class="border border-brand-100 rounded-lg overflow-x-auto bg-white shadow-sm">

                                        {{-- 🌟 ROOT FIX 3: Reduced mobile font size to 11px and added min-w-[380px] to force smooth horizontal scrolling --}}
                                        <table
                                            class="w-full text-left text-[11px] sm:text-xs whitespace-nowrap min-w-[380px]">

                                            <thead class="bg-brand-50 text-brand-700 font-bold border-b border-brand-100">
                                                <tr>
                                                    <th class="px-3 sm:px-4 py-2.5">Batch No.</th>
                                                    <th class="px-3 sm:px-4 py-2.5 hidden sm:table-cell">Mfg Date</th>
                                                    <th class="px-3 sm:px-4 py-2.5">Expiry Date</th>
                                                    <th class="px-3 sm:px-4 py-2.5 text-right">Available Qty</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 text-gray-600">
                                                @foreach ($stock->sku->batches as $batch)
                                                    <tr class="hover:bg-gray-50 transition-colors">
                                                        <td class="px-3 sm:px-4 py-2.5 font-mono font-bold text-gray-800">
                                                            {{ $batch->batch_number ?? 'N/A' }}</td>
                                                        <td class="px-3 sm:px-4 py-2.5 hidden sm:table-cell">
                                                            {{ $batch->manufacturing_date ? $batch->manufacturing_date->format('M d, Y') : '-' }}
                                                        </td>
                                                        <td class="px-3 sm:px-4 py-2.5">
                                                            @if ($batch->expiry_date)
                                                                @php
                                                                    $daysToExpiry = now()
                                                                        ->startOfDay()
                                                                        ->diffInDays(
                                                                            $batch->expiry_date->startOfDay(),
                                                                            false,
                                                                        );
                                                                    $expiryClass =
                                                                        $daysToExpiry <= 30
                                                                            ? 'text-red-500 font-bold'
                                                                            : 'text-gray-600';
                                                                @endphp
                                                                <span class="{{ $expiryClass }}">
                                                                    {{ $batch->expiry_date->format('M d, Y') }}
                                                                </span>
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                        <td
                                                            class="px-3 sm:px-4 py-2.5 text-right font-black text-[#0284c7]">
                                                            {{ number_format($batch->remaining_qty, 0) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                    </div>
                @empty
                    <div class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center justify-center text-gray-400">
                            <i data-lucide="package-open" class="w-12 h-12 mb-3 opacity-20"></i>
                            <p class="font-medium text-sm text-gray-500">This warehouse is currently empty.</p>
                            <p class="text-xs mt-1">Stock will appear here when purchases or transfers are made.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if ($stocks->hasPages())
                <div class="px-5 sm:px-6 py-4 border-t border-gray-100 bg-white">
                    {{ $stocks->links() }}
                </div>
            @endif

        </div>
    </div>
@endsection

{{-- The icon re-init block that stood here did nothing. Its Alpine.effect() read
     no reactive state, so it ran once at boot and never again on expand — and it
     was not needed anyway: the expandable panel uses x-show with x-collapse, so
     its icons are in the DOM from the first render and the layout's initIcons()
     has already processed them. --}}
