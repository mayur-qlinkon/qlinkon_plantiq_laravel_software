@extends('layouts.platform')

@section('title', 'Create Profile Kit')
@section('header', 'Create Profile Kit')

@section('content')
    <div class="mx-auto">
        
        {{-- Header & Actions --}}
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <a href="{{ route('platform.profile-kits.index') }}" class="text-sm text-brand-600 hover:text-brand-700 font-bold flex items-center gap-2 mb-2 transition-colors">
                    <i class="fa-solid fa-arrow-left-long"></i> Back to Hardware Kits
                </a>
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">New Profile Kit</h1>
                <p class="text-sm text-gray-500 mt-1">Add a new physical tag or kit option for nursery subscriptions.</p>
            </div>
            <button type="submit" form="create-kit-form" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2.5 rounded-lg text-sm font-semibold transition-all shadow-md shadow-brand-500/20 flex items-center justify-center gap-2 self-start sm:self-end">
                <i class="fa-solid fa-floppy-disk"></i> Save Kit
            </button>
        </div>

        {{-- Validation Errors Banner --}}
        @if ($errors->any())
            <div class="mb-6 flex items-start gap-3 bg-red-50 border border-red-200 text-red-800 text-sm px-5 py-4 rounded-xl shadow-sm">
                <i class="fa-solid fa-triangle-exclamation text-red-600 text-xl shrink-0 mt-0.5"></i>
                <div>
                    <span class="font-bold">Could not save the kit. Please check the errors below:</span>
                    <ul class="list-disc list-inside mt-1.5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form id="create-kit-form" action="{{ route('platform.profile-kits.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                {{-- Left Column: Core Details (Wider) --}}
                <div class="lg:col-span-2 space-y-8">
                    
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
                        <h2 class="text-lg font-bold text-gray-900 mb-6 border-b border-gray-100 pb-4">Kit Details</h2>
                        
                        <div class="space-y-6">
                            {{-- Name --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Kit Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. NFC Waterproof Fiber Tag" 
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all @error('name') border-red-300 ring-red-100 @enderror">
                                @error('name')<p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>@enderror
                            </div>

                            {{-- Description --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                                <textarea name="description" rows="4" placeholder="Briefly describe what is included in this hardware kit..." 
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all resize-none @error('description') border-red-300 ring-red-100 @enderror">{{ old('description') }}</textarea>
                                @error('description')<p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>@enderror
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2">
                                {{-- Price --}}
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Price Per Unit (₹) <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <span class="text-gray-500 font-medium">₹</span>
                                        </div>
                                        <input type="number" step="0.01" name="price" value="{{ old('price', 0) }}" required min="0" 
                                            class="w-full border border-gray-300 rounded-lg pl-9 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all @error('price') border-red-300 ring-red-100 @enderror">
                                    </div>
                                    @error('price')<p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>@enderror
                                </div>
                                
                                {{-- Sort Order --}}
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Display Order</label>
                                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" placeholder="0" 
                                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all @error('sort_order') border-red-300 ring-red-100 @enderror">
                                    <p class="text-xs text-gray-400 mt-1.5">Lower numbers display first (e.g. 0, 1, 2).</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right Column: Image & Status --}}
                <div class="space-y-8">
                    
                    {{-- Image Upload Card --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8" x-data="{ preview: null, isDragging: false }">
                        <h2 class="text-lg font-bold text-gray-900 mb-6 border-b border-gray-100 pb-4">Product Image</h2>
                        
                        <div class="relative group"
                             @dragover.prevent="isDragging = true"
                             @dragleave.prevent="isDragging = false"
                             @drop.prevent="isDragging = false; const file = $event.dataTransfer.files[0]; if(file && file.type.startsWith('image/')) { preview = URL.createObjectURL(file); $refs.fileInput.files = $event.dataTransfer.files; }">
                             
                            <label :class="{'border-brand-500 bg-brand-50': isDragging, 'border-gray-300 bg-gray-50 hover:bg-gray-100': !isDragging}" 
                                class="flex flex-col items-center justify-center border-2 border-dashed rounded-xl p-6 transition-colors cursor-pointer overflow-hidden min-h-[200px] relative">
                                
                                {{-- Preview Image --}}
                                <div x-show="preview" class="absolute inset-0 p-4 flex items-center justify-center bg-white z-10" x-cloak>
                                    <img :src="preview" class="max-h-full object-contain rounded" alt="Kit Preview">
                                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-2">
                                        <i class="fa-solid fa-camera-rotate text-white text-2xl"></i>
                                        <span class="text-white text-sm font-bold">Change Image</span>
                                    </div>
                                </div>

                                {{-- Upload Prompt --}}
                                <div x-show="!preview" class="text-center z-0">
                                    <div class="w-12 h-12 bg-white rounded-full shadow-sm flex items-center justify-center mx-auto mb-3">
                                        <i class="fa-solid fa-cloud-arrow-up text-brand-600 text-lg"></i>
                                    </div>
                                    <span class="text-sm font-bold text-gray-700 block">Click or drag image here</span>
                                    <span class="text-xs text-gray-400 mt-1.5 block leading-relaxed">
                                        Supports WEBP, PNG, JPG<br>Max file size: 2MB
                                    </span>
                                </div>

                                <input x-ref="fileInput" type="file" name="image" accept="image/png, image/jpeg, image/webp" class="hidden" 
                                    @change="if($event.target.files.length) preview = URL.createObjectURL($event.target.files[0])">
                            </label>
                        </div>
                        @error('image')<p class="text-red-500 text-xs mt-2 font-medium">{{ $message }}</p>@enderror
                    </div>

                    {{-- Status Card --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
                        <h2 class="text-lg font-bold text-gray-900 mb-6 border-b border-gray-100 pb-4">Availability</h2>
                        
                        <label class="flex items-center justify-between p-4 border border-gray-200 rounded-xl hover:bg-gray-50 hover:border-gray-300 cursor-pointer transition-all">
                            <div>
                                <p class="text-sm font-bold text-gray-900">Active Status</p>
                                <p class="text-xs text-gray-500 mt-1">Show this kit on the pricing page.</p>
                            </div>
                            
                            {{-- Modern Pure Tailwind Toggle --}}
                            <div class="relative inline-flex items-center cursor-pointer ml-4 shrink-0">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" class="sr-only peer" {{ old('is_active', true) ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                            </div>
                        </label>
                    </div>

                </div>
            </div>
        </form>
    </div>
@endsection