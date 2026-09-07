@extends('layouts.admin') 

@section('title', 'OCR Scanner')

@section('header-title')
    <div class="flex items-center gap-3">       
        <div>
            <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">OCR Document Scanner</h1>            
        </div>
    </div>
@endsection

@section('content')
<div x-data="ocrScanner()" class="w-full pb-10">

    {{-- Top Action Bar --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 text-xs sm:text-sm font-semibold">
            <span :class="step >= 1 ? 'text-brand-600' : 'text-gray-400'" class="flex items-center gap-1 sm:gap-1.5 whitespace-nowrap">
                <div class="w-4 h-4 sm:w-5 sm:h-5 rounded-full flex items-center justify-center text-[9px] sm:text-[10px] border-2 shrink-0" :class="step >= 1 ? 'border-brand-600 bg-brand-50' : 'border-gray-300'">1</div> 
                Upload
            </span>
            
            <i data-lucide="chevron-right" class="w-3 h-3 sm:w-4 sm:h-4 text-gray-300 shrink-0"></i>
            
            <span :class="step >= 2 ? 'text-brand-600' : 'text-gray-400'" class="flex items-center gap-1 sm:gap-1.5 whitespace-nowrap">
                <div class="w-4 h-4 sm:w-5 sm:h-5 rounded-full flex items-center justify-center text-[9px] sm:text-[10px] border-2 shrink-0" :class="step >= 2 ? 'border-brand-600 bg-brand-50' : 'border-gray-300'">2</div> 
                Process
            </span>
            
            <i data-lucide="chevron-right" class="w-3 h-3 sm:w-4 sm:h-4 text-gray-300 shrink-0"></i>
            
            <span :class="step === 3 ? 'text-brand-600' : 'text-gray-400'" class="flex items-center gap-1 sm:gap-1.5 whitespace-nowrap">
                <div class="w-4 h-4 sm:w-5 sm:h-5 rounded-full flex items-center justify-center text-[9px] sm:text-[10px] border-2 shrink-0" :class="step === 3 ? 'border-brand-600 bg-brand-50' : 'border-gray-300'">3</div> 
                <span class="hidden sm:inline">Review & Save</span>
                <span class="sm:hidden">Review</span>
            </span>
        </div>

        <a href="{{ route('admin.ocr-scanner.history') ?? '#' }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-bold text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors shadow-sm">
            <i data-lucide="history" class="w-4 h-4"></i> View History
        </a>
    </div>

    {{-- ==========================================
         STEP 1: UPLOAD & SCAN
         ========================================== --}}
    <div x-show="step === 1" x-transition.opacity class="space-y-6">
        
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-sm font-bold text-gray-800 mb-3 uppercase tracking-wider">1. Select Document Type</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <template x-for="type in types" :key="type.id">
                    <label class="relative cursor-pointer group">
                        <input type="radio" name="scan_type" :value="type.id" x-model="scanType" class="peer sr-only">
                        <div class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all"
                             :class="scanType === type.id ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-gray-100 bg-gray-50 text-gray-500 hover:border-gray-200 hover:bg-gray-100'">
                            <i :data-lucide="type.icon" class="w-6 h-6" :class="scanType === type.id ? 'text-brand-600' : 'text-gray-400'"></i>
                            <span class="text-xs font-bold" x-text="type.name"></span>
                        </div>
                    </label>
                </template>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-sm font-bold text-gray-800 mb-3 uppercase tracking-wider">2. Upload or Capture</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                
                <div class="md:col-span-2 lg:col-span-1">
                    <button @click="triggerCamera" class="w-full h-full min-h-[160px] flex flex-col items-center justify-center gap-3 bg-gray-900 hover:bg-gray-800 text-white rounded-2xl transition-transform active:scale-[0.98] shadow-md border-4 border-transparent hover:border-gray-700">
                        <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center">
                            <i data-lucide="camera" class="w-7 h-7 text-white"></i>
                        </div>
                        <div class="text-center">
                            <p class="text-lg font-bold">Open Camera</p>
                            <p class="text-xs text-gray-300 mt-1 font-medium">Scan directly from your device</p>
                        </div>
                    </button>
                    <input type="file" id="camera-upload" accept="image/*" capture="environment" class="hidden" @change="handleFileSelect">
                </div>

                <div class="md:col-span-2 lg:col-span-1">
                    <div @dragover.prevent="isDragging = true" 
                         @dragleave.prevent="isDragging = false" 
                         @drop.prevent="handleFileSelect($event)"
                         @click="triggerFileSelect"
                         :class="isDragging ? 'border-brand-500 bg-brand-50 scale-[1.02]' : 'border-gray-300 bg-gray-50 hover:bg-gray-100 hover:border-gray-400'"
                         class="w-full h-full min-h-[160px] flex flex-col items-center justify-center p-6 border-2 border-dashed rounded-2xl cursor-pointer transition-all text-center group">
                        
                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-sm mb-3 group-hover:scale-110 transition-transform">
                            <i data-lucide="upload-cloud" class="w-6 h-6 text-gray-400 group-hover:text-brand-500"></i>
                        </div>
                        <p class="text-sm font-bold text-gray-700">Click or drag image here</p>
                        <p class="text-[11px] font-medium text-gray-400 mt-1">Supports JPG, PNG, WEBP (Auto-compressed to < 1MB)</p>
                    </div>
                    <input type="file" id="file-upload" accept="image/jpeg, image/png, image/webp" class="hidden" @change="handleFileSelect">
                </div>
            </div>
        </div>
    </div>

    {{-- ==========================================
         STEP 2: PROCESSING / LOADING
         ========================================== --}}
    <div x-show="step === 2" x-cloak class="bg-white rounded-2xl shadow-sm border border-gray-100 p-10 flex flex-col items-center justify-center min-h-[400px]">
        
        <div class="relative w-24 h-24 mb-6">
            <svg class="animate-spin w-full h-full text-gray-100" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                <path fill="var(--brand-500)" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <div class="absolute inset-0 flex items-center justify-center text-brand-600 animate-pulse">
                <i data-lucide="cpu" class="w-8 h-8"></i>
            </div>
        </div>

        <h2 class="text-xl font-black text-gray-800" x-text="loadingTitle">Processing...</h2>
        <p class="text-sm font-medium text-gray-500 mt-2 text-center max-w-sm" x-text="loadingDesc"></p>
        
        <div class="w-full max-w-xs bg-gray-100 rounded-full h-1.5 mt-6 overflow-hidden">
            <div class="bg-brand-500 h-1.5 rounded-full animate-[pulse_2s_ease-in-out_infinite]" style="width: 75%"></div>
        </div>
    </div>

    {{-- ==========================================
         STEP 3: REVIEW & EDIT
         ========================================== --}}
    <div x-show="step === 3" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <div class="lg:col-span-5 flex flex-col gap-4">
            <div class="bg-gray-900 rounded-2xl overflow-hidden shadow-lg relative border border-gray-800 group">
                <div class="absolute top-3 left-3 bg-black/60 backdrop-blur-md px-3 py-1.5 rounded-lg text-xs font-bold text-white flex items-center gap-2">
                    <i data-lucide="image" class="w-3.5 h-3.5"></i> Scanned Image
                </div>
                <img :src="previewUrl" class="w-full h-auto max-h-[600px] object-contain block">
            </div>

            <div x-data="{ showRaw: false }" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <button @click="showRaw = !showRaw" class="w-full px-4 py-3 flex items-center justify-between text-sm font-bold text-gray-700 bg-gray-50 hover:bg-gray-100 transition-colors">
                    <span class="flex items-center gap-2"><i data-lucide="file-text" class="w-4 h-4"></i> View Raw OCR Text</span>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" :class="showRaw ? 'rotate-180' : ''"></i>
                </button>
                <div x-show="showRaw" x-transition class="p-4 border-t border-gray-100">
                    <textarea readonly x-model="rawText" class="w-full h-40 text-xs font-mono text-gray-600 bg-gray-50 border border-gray-200 rounded-lg p-3 focus:outline-none resize-none"></textarea>
                </div>
            </div>
        </div>

        <div class="lg:col-span-7">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:p-6">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-5">
                    <div>
                        <h2 class="text-lg font-black text-gray-900 flex items-center gap-2">
                            <i data-lucide="check-square" class="w-5 h-5 text-brand-600"></i> Review Extracted Data
                        </h2>
                        <p class="text-xs text-gray-500 font-medium mt-1">Please verify and correct the AI-extracted fields below.</p>
                    </div>
                </div>

                <form @submit.prevent="saveData" class="space-y-4">
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <template x-for="(value, key) in extractedData" :key="key">
                            <div x-show="typeof value !== 'object'">
                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5 ml-1" x-text="formatKey(key)"></label>
                                <input type="text" x-model="extractedData[key]" 
                                       class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 text-gray-900 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all text-sm font-medium">
                            </div>
                        </template>
                    </div>

                    <div class="pt-4 mt-4 border-t border-gray-100">
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5 ml-1">Additional Notes</label>
                        <textarea x-model="notes" rows="3" placeholder="Add any context or notes regarding this scan..."
                                  class="w-full px-4 py-3 bg-gray-50 border border-gray-200 text-gray-900 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all text-sm resize-none"></textarea>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row items-center gap-3 pt-6 mt-2 border-t border-gray-100">
                        <button type="button" @click="resetScanner" class="w-full sm:w-auto px-6 py-3 border border-gray-200 text-gray-600 rounded-xl text-sm font-bold hover:bg-gray-50 transition-colors">
                            Discard & Rescan
                        </button>
                        
                        <button type="submit" :disabled="isSaving" class="w-full sm:w-auto sm:ml-auto px-8 py-3 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-sm font-black shadow-md shadow-brand-500/20 transition-all active:scale-[0.98] disabled:opacity-70 flex items-center justify-center gap-2">
                            <span x-show="!isSaving">Save Document</span>
                            <span x-show="isSaving" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity="0.25"></circle>
                                    <path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                Saving...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function ocrScanner() {
    return {
        step: 1,
        scanType: 'business_card',
        types: [
            { id: 'business_card', name: 'Business Card', icon: 'contact' },
            { id: 'invoice',       name: 'Invoice',       icon: 'file-text' },
            { id: 'receipt',       name: 'Receipt',       icon: 'receipt' },
            { id: 'expense',       name: 'Expense Bill',  icon: 'wallet' },
            { id: 'gst_bill',      name: 'GST Bill',      icon: 'indian-rupee' },
            { id: 'general',       name: 'General Docs',  icon: 'file-type-2' },
        ],
        
        isDragging: false,
        
        // Data states
        file: null,
        previewUrl: null,
        scanId: null,
        extractedData: {},
        rawText: '',
        notes: '',
        
        // Loading states
        loadingTitle: '',
        loadingDesc: '',
        isSaving: false,

        triggerFileSelect() { document.getElementById('file-upload').click(); },
        triggerCamera() { document.getElementById('camera-upload').click(); },

        async handleFileSelect(event) {
            let selectedFile = event.target.files?.[0] || event.dataTransfer?.files?.[0];
            if (!selectedFile) return;

            // Basic validation
            if (!selectedFile.type.startsWith('image/')) {
                if(typeof BizAlert !== 'undefined') BizAlert.toast('Only image files (JPG, PNG, WEBP) are supported.', 'error');
                else alert('Only image files are supported.');
                return;
            }

            // Create temporary preview
            this.previewUrl = URL.createObjectURL(selectedFile);
            this.step = 2;
            
            try {
                // 1. Client-Side Compression
                this.loadingTitle = 'Optimizing Image...';
                this.loadingDesc = 'Compressing image for faster processing without losing text clarity.';
                const compressedFile = await this.compressImage(selectedFile);

                // 2. OCR Processing
                this.loadingTitle = 'PlantIQ OCR...';
                this.loadingDesc = 'PlantIQ is reading your document and extracting structured fields.';
                await this.processOcr(compressedFile);

            } catch (error) {
                console.error(error);
                if(typeof BizAlert !== 'undefined') BizAlert.toast(error.message || 'Failed to process document.', 'error');
                else alert(error.message || 'Failed to process document');
                this.resetScanner();
            } finally {
                // Clear inputs to allow re-selection of the same file if needed
                document.getElementById('file-upload').value = '';
                document.getElementById('camera-upload').value = '';
                setTimeout(() => window.initIcons && window.initIcons(), 50); // Re-init Lucide for step 3
            }
        },

        /**
         * Client-side compression ensuring the file is <= 1000KB
         */
        compressImage(file) {
            const MAX_SIZE = 1000 * 1024; // ~1MB
            const ACCEPTED = ['image/jpeg', 'image/png', 'image/webp'];

            // A small file still needs the canvas re-encode when its type is
            // not one the server accepts. iPhone camera uploads arrive as
            // HEIC, and anything under 1MB used to skip compression entirely
            // and get rejected by the mimes rule.
            if (file.size <= MAX_SIZE && ACCEPTED.includes(file.type)) {
                return Promise.resolve(file);
            }

            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = (event) => {
                    const img = new Image();
                    img.src = event.target.result;
                    img.onload = () => {
                        const canvas = document.createElement('canvas');
                        let width = img.width;
                        let height = img.height;

                        // Max dimensions for OCR (2500px is usually plenty for high-res text)
                        const MAX_DIM = 2500;
                        if (width > height && width > MAX_DIM) {
                            height = Math.round(height * (MAX_DIM / width));
                            width = MAX_DIM;
                        } else if (height > MAX_DIM) {
                            width = Math.round(width * (MAX_DIM / height));
                            height = MAX_DIM;
                        }

                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);

                        // Stepped in tenths as an integer: 0.9 - 0.1 lands on
                        // 0.8000000000000001 and the drift let the loop slip
                        // past the intended 0.3 floor down to ~0.2.
                        let quality = 9;
                        
                        // Recursive compression loop to hit target size
                        const compress = () => {
                            canvas.toBlob((blob) => {
                                if (!blob) return reject(new Error('Canvas compression failed'));
                                
                                if (blob.size > MAX_SIZE && quality > 3) {
                                    quality -= 1;
                                    compress(); // try again
                                } else {
                                    // Finished compressing
                                    resolve(new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".jpg", {
                                        type: 'image/jpeg',
                                        lastModified: Date.now()
                                    }));
                                }
                            }, 'image/jpeg', quality / 10);
                        };
                        
                        compress();
                    };
                    img.onerror = () => reject(new Error('Failed to load image for compression'));
                };
                reader.onerror = () => reject(new Error('Failed to read file'));
            });
        },

        async processOcr(file) {
            const formData = new FormData();
            formData.append('image', file);
            formData.append('scan_type', this.scanType);
            formData.append('store_image', '1');

            const token = document.querySelector('meta[name="csrf-token"]')?.content;

            // Adjust this route to match your actual named route
            const res = await fetch("{{ route('admin.ocr-scanner.process') ?? '/admin/ocr-scanner/process' }}", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                body: formData
            });

            const data = await res.json();
            
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'OCR Engine failed to process image.');
            }

            // Populate state
            this.scanId = data.scan_id;
            this.extractedData = data.extracted_data || {};
            this.rawText = data.raw_text || 'No readable text found.';
            
            // Move to review step
            this.step = 3;
        },

        async saveData() {
            this.isSaving = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                
                const payload = {
                    scan_id: this.scanId,
                    edited_data: this.extractedData,
                    notes: this.notes
                };

                const res = await fetch("{{ route('admin.ocr-scanner.save') ?? '/admin/ocr-scanner/save' }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Failed to save scan data.');
                }

                if(typeof BizAlert !== 'undefined') BizAlert.toast('Document saved successfully!', 'success');
                
                // Redirect to history or stay (for now, reset scanner for next scan)
                setTimeout(() => {
                    if(typeof navigate === 'function') {
                        // Use your SPA navigator if available
                        navigate("{{ route('admin.ocr-scanner.history') ?? '#' }}");
                    } else {
                        window.location.href = "{{ route('admin.ocr-scanner.history') ?? '#' }}";
                    }
                }, 1000);

            } catch (error) {
                if(typeof BizAlert !== 'undefined') BizAlert.toast(error.message, 'error');
                else alert(error.message);
            } finally {
                this.isSaving = false;
            }
        },

        resetScanner() {
            this.step = 1;
            this.file = null;
            if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
            this.previewUrl = null;
            this.scanId = null;
            this.extractedData = {};
            this.rawText = '';
            this.notes = '';
            setTimeout(() => window.initIcons && window.initIcons(), 50);
        },

        // Helper to turn 'job_title' into 'Job Title'
        formatKey(str) {
            if (!str) return '';
            return str.replace(/_/g, ' ')
                      .replace(/\w\S*/g, function(txt){
                          return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
                      });
        }
    };
}
</script>
@endpush
@endsection