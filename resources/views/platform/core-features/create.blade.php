@extends('layouts.platform')
@section('title', 'Add Core Feature')
@section('header', 'Add Core Feature')

@section('content')
    <div class="mb-6">
        <a href="{{ route('platform.core-features.index') }}" class="text-sm text-brand-600 hover:text-brand-700 font-medium flex items-center gap-2 mb-2">
            <i class="fa-solid fa-arrow-left"></i> Back to Core Features
        </a>
        <h2 class="text-xl font-semibold text-gray-800">New Core Feature</h2>
    </div>

    @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-start gap-3">
            <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
            <ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('platform.core-features.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
                    <h3 class="text-base font-semibold text-gray-800 border-b pb-3 flex items-center gap-2">
                        <i class="fa-solid fa-star text-gray-400"></i> Feature Details
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Feature Name *</label>
                            <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. AI Assistant" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Value <span class="text-gray-400 font-normal">(shown next to name)</span></label>
                            <input type="text" name="value" value="{{ old('value') }}" placeholder="e.g. Included, 20 GB, Daily" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Icon <span class="text-gray-400 font-normal">(Font Awesome class)</span></label>
                        <input type="text" name="icon" value="{{ old('icon', 'fa-solid fa-check') }}" placeholder="e.g. fa-solid fa-robot" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                        <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm" placeholder="More detail if needed...">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="space-y-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-semibold text-gray-800 border-b pb-3 mb-4">Settings</h3>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                    </div>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }} class="w-4 h-4 rounded text-brand-600 border-gray-300">
                        <span class="text-sm font-medium text-gray-700">Active</span>
                    </label>
                </div>
                <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition shadow-sm">
                    <i class="fa-solid fa-floppy-disk mr-1"></i> Save Feature
                </button>
            </div>
        </div>
    </form>
@endsection