@extends('layouts.platform')
@section('title', 'Create Addon')
@section('header', 'Create Addon')

@section('content')
    <div class="mb-6">
        <a href="{{ route('platform.addons.index') }}" class="text-sm text-brand-600 hover:text-brand-700 font-medium flex items-center gap-2 mb-2">
            <i class="fa-solid fa-arrow-left"></i> Back to Addons
        </a>
        <h2 class="text-xl font-semibold text-gray-800">New Addon</h2>
    </div>

    @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-start gap-3">
            <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
            <ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('platform.addons.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Main --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-semibold text-gray-800 border-b pb-3 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-circle-info text-gray-400"></i> Basic Info
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                            <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Nursery Website" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Price (₹) *</label>
                            <input type="number" name="price" value="{{ old('price', 0) }}" min="0" step="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Slug <span class="text-gray-400 font-normal">(auto-generated if blank)</span>
                        </label>
                        <input type="text" name="slug" value="{{ old('slug') }}"
                            placeholder="e.g. nursery-website"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm font-mono {{ $errors->has('slug') ? 'border-red-400' : '' }}">
                        @error('slug') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        <p class="text-xs text-gray-400 mt-1">Lowercase letters, numbers and hyphens only.</p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm" placeholder="Short description shown on checkout...">{{ old('description') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Icon <span class="text-gray-400 font-normal">(Font Awesome class)</span></label>
                        <input type="text" name="icon" value="{{ old('icon') }}" placeholder="e.g. fa-solid fa-globe" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm font-mono">
                    </div>
                </div>

                {{-- Module assignment --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-semibold text-gray-800 border-b pb-3 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-cubes-stacked text-gray-400"></i> Included Modules
                        <span class="text-xs text-gray-400 font-normal ml-1">Tick all modules this addon unlocks</span>
                    </h3>
                    @if($modules->isEmpty())
                        <p class="text-sm text-gray-400">No active modules found. Create modules first.</p>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($modules as $module)
                                <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:bg-gray-50 cursor-pointer transition has-[:checked]:border-brand-300 has-[:checked]:bg-brand-50/50">
                                    <input type="checkbox" name="modules[]" value="{{ $module->id }}"
                                        {{ in_array($module->id, old('modules', [])) ? 'checked' : '' }}
                                        class="w-4 h-4 rounded text-brand-600 border-gray-300 focus:ring-brand-500">
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">{{ $module->name }}</p>
                                        <p class="text-xs text-gray-400 font-mono">{{ $module->slug }}</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Sidebar --}}
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
                        <span class="text-sm font-medium text-gray-700">Active (visible on checkout)</span>
                    </label>
                </div>
                <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition shadow-sm">
                    <i class="fa-solid fa-floppy-disk mr-1"></i> Create Addon
                </button>
            </div>
        </div>
    </form>
@endsection