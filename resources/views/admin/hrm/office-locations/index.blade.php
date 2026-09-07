@extends('layouts.admin')

@section('title', 'Office Locations')

@section('header-title')
    <div>        
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Office Locations</h1>        
        {{-- <p class="text-xs text-gray-400 mt-1">Manage geofence zones & generate QR codes for employee attendance</p> --}}
    </div>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .field-label { font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 5px; }
    .field-input { width: 100%; border: 1.5px solid #e5e7eb; border-radius: 10px; padding: 9px 12px; font-size: 13px; outline: none; transition: border-color 150ms ease, box-shadow 150ms ease; font-family: inherit; background: #fff; }
    .field-input:focus { border-color: var(--brand-600); box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-600) 10%, transparent); }
    .field-error { font-size: 11px; color: #dc2626; margin-top: 4px; }

    .loc-card { background: #fff; border: 1.5px solid #f1f5f9; border-radius: 16px; overflow: hidden; transition: box-shadow 150ms, border-color 150ms; }
    .loc-card:hover { border-color: #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.07); }

    .qr-modal-img svg { width: 220px; height: 220px; }

    /* Countdown ring */
    .countdown-ring { position: relative; display: inline-flex; align-items: center; justify-content: center; }
    .countdown-ring svg { transform: rotate(-90deg); }
    .countdown-ring .ring-track { fill: none; stroke: #f3f4f6; stroke-width: 4; }
    .countdown-ring .ring-fill  { fill: none; stroke: var(--brand-600); stroke-width: 4; stroke-linecap: round; transition: stroke-dashoffset 1s linear; }
    .countdown-ring .ring-text  { position: absolute; font-size: 20px; font-weight: 900; color: #1f2937; }
</style>
@endpush

@section('content')

@php
    $storesJson = $stores->map(fn($s) => [
        'id'                => $s->id,
        'name'              => $s->name,
        'office_lat'        => $s->office_lat,
        'office_lng'        => $s->office_lng,
        'gps_radius_meters' => $s->gps_radius_meters ?? 100,
        'city'              => $s->city,
    ])->values();
@endphp

<div class="pb-10" x-data="officeLocations()">

    {{-- Location Cards --}}
    <div class="grid grid-cols-1 gap-4">
        @forelse($stores as $store)
        <div class="loc-card" x-data="locationCard({{ $store->id }})">

            {{-- Card Header --}}            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 px-5 py-4 bg-gray-50/60 border-b border-gray-100">
                
                {{-- Grouped Icon & Title --}}
                <div class="flex items-center gap-4 flex-1 min-w-0">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-white border border-gray-200 flex-shrink-0">
                        <i data-lucide="building-2" class="w-5 h-5 text-gray-400"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[14px] font-black text-gray-900 truncate">{{ $store->name }}</p>
                        <p class="text-[11px] text-gray-400 truncate">
                            ID #{{ $store->id }}
                            @if($store->city)  ·  {{ $store->city }} @endif
                        </p>
                    </div>
                </div>

                {{-- Status badges --}}
                <div class="flex items-center gap-2 flex-wrap justify-start md:justify-end">
                    @if($store->office_lat && $store->office_lng)
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-green-700 bg-green-50 border border-green-200 px-2.5 py-1 rounded-full">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                            GPS Active
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-gray-500 bg-gray-100 border border-gray-200 px-2.5 py-1 rounded-full">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                            No GPS
                        </span>
                    @endif

                    <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-blue-700 bg-blue-50 border border-blue-200 px-2.5 py-1 rounded-full">
                        <i data-lucide="clock" class="w-3 h-3"></i>
                        {{ $store->gps_radius_meters ?? 100 }}m radius
                    </span>
                </div>
            </div>

            {{-- Card Body --}}
            <div class="p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:items-end">

                    {{-- Latitude --}}
                    <div>
                        <label class="field-label">Office Latitude</label>
                        <input type="text" x-model="form.office_lat"
                            class="field-input"
                            placeholder="e.g. 22.2157">
                        <p class="field-error" x-show="errors.office_lat" x-text="errors.office_lat"></p>
                    </div>

                    {{-- Longitude --}}
                    <div>
                        <label class="field-label">Office Longitude</label>
                        <input type="text" x-model="form.office_lng"
                            class="field-input"
                            placeholder="e.g. 70.5673">
                        <p class="field-error" x-show="errors.office_lng" x-text="errors.office_lng"></p>
                    </div>

                    {{-- GPS Radius --}}
                    <div>
                        <label class="field-label">GPS Radius (meters)</label>
                        <input type="number" x-model="form.gps_radius_meters"
                            class="field-input"
                            min="10" max="5000" step="10"
                            placeholder="e.g. 100">
                        <p class="field-error" x-show="errors.gps_radius_meters" x-text="errors.gps_radius_meters"></p>
                    </div>

                    {{-- Actions --}}
                    <div class="flex flex-wrap items-center gap-2 pt-2 sm:pt-0 w-full">
                        @if(has_permission('office_locations.update'))
                        <button @click="saveLocation({{ $store->id }})" :disabled="saving"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 text-[12px] font-bold px-4 py-2.5 rounded-lg text-white hover:opacity-90 transition-opacity disabled:opacity-50"
                            style="background: var(--brand-600)">
                            <template x-if="!saving">
                                <span class="flex items-center gap-1.5">
                                    <i data-lucide="save" class="w-3.5 h-3.5"></i>
                                    Save
                                </span>
                            </template>
                            <template x-if="saving">
                                <span class="flex items-center gap-1.5">
                                    <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    Saving...
                                </span>
                            </template>
                        </button>
                        @endif

                        @if(has_permission('office_locations.generate_qr'))
                        <button @click="generateQr({{ $store->id }}, '{{ $store->name }}')"
                            :disabled="qrLoading"
                            class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 text-[12px] font-bold px-4 py-2.5 rounded-lg border-2 border-green-500 text-green-600 hover:bg-green-50 transition-colors disabled:opacity-50"
                            title="Generate QR Code">
                            <template x-if="!qrLoading">
                                <span class="flex items-center gap-1.5">
                                    <i data-lucide="qr-code" class="w-3.5 h-3.5"></i>
                                    QR
                                </span>
                            </template>
                            <template x-if="qrLoading">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </template>
                        </button>
                        @endif

                        {{-- Open in Maps --}}
                        <a :href="form.office_lat && form.office_lng ? `https://maps.google.com/?q=${form.office_lat},${form.office_lng}` : '#'"
                            :class="form.office_lat && form.office_lng ? 'text-blue-500 border-blue-300 hover:bg-blue-50' : 'text-gray-300 border-gray-200 cursor-not-allowed pointer-events-none'"
                            class="inline-flex items-center justify-center w-[38px] h-[38px] rounded-lg border-2 transition-colors"
                            target="_blank" title="Open in Google Maps">
                            <i data-lucide="map-pin" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>

                {{-- Coordinates hint --}}
                <p class="text-[11px] text-gray-400 mt-3 flex items-center gap-1">
                    <i data-lucide="info" class="w-3 h-3"></i>
                    Tip: Open Google Maps → right-click your office location → copy coordinates
                </p>
            </div>
        </div>

        {{-- Per-card Alpine init --}}
        @php $lat = $store->office_lat; $lng = $store->office_lng; $radius = $store->gps_radius_meters ?? 100; @endphp
        <script>
        (function() {
            const storeId = {{ $store->id }};
            if (!window._locationCards) window._locationCards = {};
            window._locationCards[storeId] = {
                office_lat: {{ $lat ?? 'null' }},
                office_lng: {{ $lng ?? 'null' }},
                gps_radius_meters: {{ $radius }},
            };
        })();
        </script>

        @empty
        <div class="bg-white border border-gray-100 rounded-2xl p-16 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i data-lucide="building-2" class="w-8 h-8 text-gray-300"></i>
            </div>
            <p class="text-gray-500 font-bold text-lg mb-1">No stores found</p>
            <p class="text-gray-400 text-sm">Create a store first from the Stores section to manage office locations.</p>
        </div>
        @endforelse
    </div>

    {{-- ════════ QR Code Modal ════════ --}}
    <div x-show="qrModalOpen" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-end="opacity-0">

        <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

            {{-- Header --}}
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between" style="background:#166534;">
                <div>                    
                    <p class="text-[11px] text-green-200 mt-0.5" x-text="qrStoreName"></p>
                </div>
                <button @click="closeQrModal()" class="text-green-300 hover:text-white transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            {{-- QR Display --}}
            <div class="p-6 flex flex-col items-center">

                {{-- Store name --}}
                <p class="text-[16px] font-black text-gray-900 mb-1" x-text="qrStoreName"></p>
                <p class="text-[12px] text-gray-400 mb-5">Scan QR & Mark Your Attendance</p>

                {{-- QR Image --}}
                <div class="qr-modal-img mb-5 p-4 bg-white border-2 border-green-100 rounded-xl shadow-sm"
                    x-html="qrSvg">
                    {{-- Loading state --}}
                    <div x-show="!qrSvg" class="w-[220px] h-[220px] flex items-center justify-center">
                        <svg class="animate-spin w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    </div>
                </div>

                <p class="text-[11px] text-gray-400 mb-5 text-center">
                    This QR is <strong>permanent</strong> — print once and use every day
                </p>

                <div class="w-full flex flex-col sm:flex-row gap-2">
                    <button @click="printPoster()"
                        class="flex-1 inline-flex items-center justify-center gap-2 text-[13px] font-bold py-3 rounded-xl text-white hover:opacity-90 transition-opacity"
                        style="background: #166534">
                        <i data-lucide="printer" class="w-4 h-4"></i>
                        Print Poster
                    </button>
                    <button @click="closeQrModal()"
                        class="flex-1 inline-flex items-center justify-center gap-2 text-[13px] font-bold py-3 rounded-xl text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
function locationCard(storeId) {
    const saved = (window._locationCards || {})[storeId] || {};
    return {
        saving: false,
        qrLoading: false,
        errors: {},
        form: {
            office_lat:        saved.office_lat  ?? '',
            office_lng:        saved.office_lng  ?? '',
            gps_radius_meters: saved.gps_radius_meters ?? 100,
        },

        async saveLocation(id) {
            this.saving = true;
            this.errors = {};
            try {
                const resp = await fetch(`{{ url('admin/hrm/office-locations') }}/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.form),
                });
                const data = await resp.json();
                if (!resp.ok) {
                    if (resp.status === 422 && data.errors) {
                        for (const [k, m] of Object.entries(data.errors)) this.errors[k] = m[0];
                    } else {
                        BizAlert.toast(data.message || 'Failed to save', 'error');
                    }
                    return;
                }
                BizAlert.toast(data.message, 'success');
            } catch (e) {
                BizAlert.toast('Network error. Please try again.', 'error');
            } finally {
                this.saving = false;
            }
        },

        async generateQr(id, name) {
            this.qrLoading = true;
            try {
                const resp = await fetch(`{{ url('admin/hrm/office-locations') }}/${id}/generate-qr`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await resp.json();
                if (!resp.ok) { BizAlert.toast(data.message || 'Failed to generate QR', 'error'); return; }

                window._openQrModal(data.data, name, id);
            } catch (e) {
                BizAlert.toast('Network error. Please try again.', 'error');
            } finally {
                this.qrLoading = false;
            }
        },
    };
}

window.officeLocations = function() {
    return {
        qrModalOpen: false,
        qrStoreName: '',
        qrStoreId: null,
        qrSvg: '',

        init() {
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });

            window._openQrModal = (qrData, storeName, storeId) => {
                this.qrStoreName = storeName;
                this.qrStoreId   = storeId;
                this.qrSvg       = qrData.qr_svg;
                this.qrModalOpen = true;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            };
        },

        printPoster() {
            if (!this.qrStoreId) return;
            window.open(`{{ url('admin/hrm/office-locations') }}/${this.qrStoreId}/poster`, '_blank');
        },

        closeQrModal() {
            this.qrModalOpen = false;
            this.qrSvg = '';
        },
    };
};
</script>
@endpush
