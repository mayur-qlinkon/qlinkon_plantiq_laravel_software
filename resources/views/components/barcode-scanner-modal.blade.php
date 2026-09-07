{{-- Load the Scanner Library --}}
<script src="{{ asset('assets/js/html5-qrcode.min.js') }}"></script>

{{-- The Modal Overlay --}}
<div x-show="isScannerModalOpen" style="display: none;"
    class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/70 backdrop-blur-sm px-4"
    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

    {{-- The Modal Box --}}
    <div class="bg-white w-full max-w-lg max-h-[90dvh] rounded-2xl shadow-2xl flex flex-col overflow-hidden"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-8 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100" @click.away="closeScanner()">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 bg-gray-50">
            <h3 class="font-bold text-gray-800 flex items-center gap-2">
                <i data-lucide="scan-line" class="w-5 h-5 text-brand-500"></i>
                Scan Barcode
            </h3>
            <button @click="closeScanner()"
                class="text-gray-400 hover:text-red-500 transition-colors p-1 rounded-lg hover:bg-red-50">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        {{-- Scanner Body --}}
        <div class="p-5 flex flex-col items-center flex-1 overflow-y-auto">
            <p class="text-xs text-gray-500 mb-4 text-center">Point your camera at the product barcode. It will add to
                cart automatically.</p>

            {{-- Camera Target Container --}}
            <div id="camera-reader"
                class="w-full max-w-[400px] overflow-hidden rounded-xl border-2 border-brand-100 shadow-inner"></div>
        </div>
    </div>
</div>

<style>
    #camera-reader {
        border: none !important;
    }

    #camera-reader button {
        background-color: #008a62 !important;
        color: white !important;
        border: none !important;
        padding: 8px 16px !important;
        border-radius: 8px !important;
        font-weight: bold !important;
        cursor: pointer !important;
        margin-top: 10px !important;
    }

    #camera-reader select {
        padding: 8px !important;
        border-radius: 8px !important;
        border: 1px solid #e5e7eb !important;
        margin-bottom: 10px !important;
        width: 100%;
    }

    #camera-reader__dashboard_section_csr span {
        color: red !important;
    }
    #camera-reader video {
        width: 100% !important;
        max-height: 50vh !important;
        object-fit: cover !important;
        border-radius: 8px !important;
    }
</style>
