@extends('layouts.admin')

@section('title', 'OCR Scan History')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">OCR Scan History</h1>
    </div>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }

    .filter-input {
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        padding: 8px 12px;
        font-size: 13px;
        color: #1f2937;
        outline: none;
        background: #fff;
        transition: border-color 150ms;
        height: 38px;
    }
    .filter-input:focus { border-color: var(--brand-500); }

    .scan-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 14px 16px;
        transition: box-shadow 0.15s;
    }
    .scan-card:hover { box-shadow: 0 2px 12px rgba(0,0,0,.07); }

    .badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .badge-saved      { background: #d1fae5; color: #059669; }
    .badge-completed  { background: #dbeafe; color: #2563eb; }
    .badge-pending    { background: #fef3c7; color: #d97706; }
    .badge-failed     { background: #fee2e2; color: #dc2626; }
    .badge-card       { background: #ede9fe; color: #7c3aed; }
    .badge-invoice    { background: #fce7f3; color: #db2777; }
    .badge-receipt    { background: #ffedd5; color: #ea580c; }

    .detail-row {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        margin-bottom: 6px;
    }
    .detail-label {
        font-size: 11px;
        font-weight: 600;
        color: #9ca3af;
        width: 80px;
        flex-shrink: 0;
        text-transform: uppercase;
        padding-top: 2px;
    }
    .detail-value {
        font-size: 13px;
        color: #1f2937;
        font-weight: 500;
        word-break: break-word;
    }

    /* Modal overlay */
    .modal-backdrop {
        position: fixed; inset: 0;
        background: rgba(0,0,0,.45);
        z-index: 999;
        display: flex; align-items: center; justify-content: center;
        padding: 16px;
    }
    .modal-box {
        background: #fff;
        border-radius: 18px;
        width: 100%;
        max-width: 480px;
        max-height: 90vh;
        overflow-y: auto;
        padding: 20px;
    }
</style>
@endpush

@section('content')
<div class="p-4 md:p-6" x-data="ocrHistory()">

    {{-- ── Header ─────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                 style="background: var(--brand-500)">
                <i data-lucide="clock" class="w-4 h-4 text-white"></i>
            </div>
            <div>
                <h2 class="text-base font-bold text-gray-800">Scan History</h2>
                <p class="text-xs text-gray-400">{{ $scans->total() }} scan(s) found</p>
            </div>
        </div>
        <a href="{{ route('admin.ocr-scanner.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-white text-sm font-semibold"
           style="background: var(--brand-500)">
            <i data-lucide="scan-line" class="w-4 h-4"></i>
            New Scan
        </a>
    </div>

    {{-- ── Filters ─────────────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('admin.ocr-scanner.history') }}"
          class="bg-white p-3 rounded-2xl border border-gray-100 shadow-sm flex flex-wrap items-center gap-3 mb-6 w-full">
        
        {{-- Search Group (સ્ટેબલ આઇકોન પોઝિશન સાથે) --}}
        <div class="relative flex-1 min-w-[250px]">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <i data-lucide="search" class="w-4 h-4 text-gray-400"></i>
            </div>
            <input type="text" name="search" value="{{ request('search') }}"
                   class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none"
                   placeholder="Search names, emails, or raw text...">
        </div>

        {{-- Document Type Custom Select --}}
        <div class="w-full sm:w-[190px] shrink-0">
            <x-custom-select
                name="scan_type"
                placeholder="All Document Types"
                :options="[
                    'business_card' => 'Business Card',
                    'invoice'       => 'Invoice',
                    'receipt'       => 'Receipt',
                    'general'       => 'General'
                ]"
                selected="{{ request('scan_type') }}"
            />
        </div>

        {{-- Status Custom Select --}}
        <div class="w-full sm:w-[160px] shrink-0">
            <x-custom-select
                name="status"
                placeholder="All Statuses"
                :options="[
                    'saved'     => 'Saved',
                    'completed' => 'Completed',
                    'failed'    => 'Failed'
                ]"
                selected="{{ request('status') }}"
            />
        </div>

        {{-- Filter Buttons Group (જમણી બાજુ પ્રોપર અલાઈનમેન્ટ) --}}
        <div class="flex items-center gap-2 w-full sm:w-auto sm:ml-auto">
            <button type="submit"
                    class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-bold shadow-sm transition-transform active:scale-95 whitespace-nowrap h-[42px]"
                    style="background: var(--brand-600)">
                Filter
            </button>

            @if(request()->hasAny(['search', 'scan_type', 'status']))
                <a href="{{ route('admin.ocr-scanner.history') }}"
                   class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1 px-4 py-2.5 rounded-xl text-gray-500 text-sm font-bold border border-gray-200 bg-white hover:bg-gray-50 transition-colors whitespace-nowrap h-[42px]">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i> Clear
                </a>
            @endif
        </div>
    </form>

    {{-- ── Scan Cards ──────────────────────────────────────────────── --}}
    @if ($scans->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 px-4 text-center">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4 bg-gray-50 border border-gray-100 shadow-sm">
                <i data-lucide="scan-line" class="w-8 h-8 text-gray-400"></i>
            </div>
            <h3 class="text-base font-bold text-gray-700 mb-1">No scan history found</h3>
            <p class="text-sm text-gray-400 max-w-sm mb-6">You haven't scanned any documents yet, or no scans match your current filters.</p>
            
            <a href="{{ route('admin.ocr-scanner.index') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-bold shadow-sm transition-transform active:scale-95 hover:opacity-90"
               style="background: var(--brand-500)">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Start New Scan
            </a>
        </div>
    @else
        <div class="grid gap-3">
            @foreach ($scans as $scan)
                @php
                    $finalData = $scan->final_data;
                    $typeBadge = match($scan->scan_type) {
                        'business_card' => ['badge-card', '🪪 Business Card'],
                        'invoice'       => ['badge-invoice', '🧾 Invoice'],
                        'receipt'       => ['badge-receipt', '🏷️ Receipt'],
                        default         => ['badge-completed', '📄 General'],
                    };
                    $statusBadge = match($scan->status) {
                        'saved'      => 'badge-saved',
                        'completed'  => 'badge-completed',
                        'failed'     => 'badge-failed',
                        default      => 'badge-pending',
                    };
                @endphp
                <div class="scan-card">
                    <div class="flex items-start gap-3">
                        {{-- Image thumbnail --}}
                        <div class="w-12 h-12 rounded-xl bg-gray-50 border border-gray-100 flex-shrink-0 overflow-hidden flex items-center justify-center relative">
                            @if ($scan->image_path)
                                <img src="{{ $scan->image_url }}"
                                     onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'24\' height=\'24\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%23d1d5db\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><rect x=\'3\' y=\'3\' width=\'18\' height=\'18\' rx=\'2\' ry=\'2\'/><circle cx=\'8.5\' cy=\'8.5\' r=\'1.5\'/><polyline points=\'21 15 16 10 5 21\'/></svg>'; this.className='w-6 h-6 object-contain';"
                                     class="w-full h-full object-cover" alt="scan">
                            @else
                                <i data-lucide="image" class="w-5 h-5 text-gray-300"></i>
                            @endif
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <p class="text-sm font-bold text-gray-800 truncate">
                                    {{ $scan->display_name }}
                                </p>
                                <span class="badge {{ $typeBadge[0] }}">{{ $typeBadge[1] }}</span>
                                <span class="badge {{ $statusBadge }}">{{ ucfirst($scan->status) }}</span>
                            </div>

                            @if (!empty($finalData['email']))
                                <p class="text-xs text-gray-500 truncate">
                                    <i data-lucide="mail" class="w-3 h-3 inline mr-1"></i>{{ $finalData['email'] }}
                                </p>
                            @endif
                            @if (!empty($finalData['phone']))
                                <p class="text-xs text-gray-500 truncate">
                                    <i data-lucide="phone" class="w-3 h-3 inline mr-1"></i>{{ $finalData['phone'] }}
                                </p>
                            @endif

                            <p class="text-xs text-gray-400 mt-1">
                                {{ $scan->created_at->diffForHumans() }}
                                · by {{ $scan->user->name ?? 'Unknown' }}
                            </p>
                        </div>

                        {{-- Actions --}}
                        <div class="flex flex-col gap-1 flex-shrink-0">
                            <button @click="openDetail({{ $scan->id }})"
                                    class="p-2 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-700 transition-colors">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            <button @click="archiveScan({{ $scan->id }})"
                                    class="p-2 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-500 transition-colors">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Compact data preview --}}
                    @if (!empty($finalData))
                        <div class="mt-3 pt-3 border-t border-gray-50 flex flex-wrap gap-x-4 gap-y-1">
                            @foreach (array_slice($finalData, 0, 4) as $k => $v)
                                @if ($v)
                                    <span class="text-xs text-gray-500">
                                        <span class="text-gray-400 uppercase text-[10px]">{{ $k }}</span>
                                        {{ Str::limit($v, 25) }}
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $scans->links() }}
        </div>
    @endif

    {{-- ── Detail Modal ────────────────────────────────────────────── --}}
    <div x-show="showModal" x-cloak class="modal-backdrop" @click.self="showModal = false">
        <div class="modal-box">
            <div x-show="loadingDetail" class="text-center py-8 text-gray-400">
                <svg class="animate-spin w-6 h-6 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                Loading…
            </div>

            <div x-show="!loadingDetail && detail" x-cloak>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-gray-800" x-text="detail?.scan_type?.replace('_', ' ').toUpperCase()"></h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-700">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                {{-- Image --}}
                <template x-if="detail?.image_url">
                    <div class="mb-5 rounded-xl overflow-hidden bg-gray-50 border border-gray-100 flex items-center justify-center p-2">
                        <img :src="detail.image_url" 
                             onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'24\' height=\'24\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%239ca3af\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><line x1=\'15\' y1=\'9\' x2=\'9\' y2=\'15\'/><line x1=\'9\' y1=\'9\' x2=\'15\' y2=\'15\'/></svg>'; this.className='w-12 h-12 py-10 opacity-50';"
                             class="w-full object-contain max-h-48 rounded-lg shadow-sm">
                    </div>
                </template>

                {{-- Final fields --}}
                <div class="mb-4">
                    <p class="text-xs font-bold text-gray-400 uppercase mb-2">Extracted Data</p>
                    <template x-for="(val, key) in detail?.final_data" :key="key">
                        <div x-show="val" class="detail-row">
                            <span class="detail-label" x-text="key.replace(/_/g,' ')"></span>
                            <span class="detail-value" x-text="val"></span>
                        </div>
                    </template>
                </div>

                {{-- Notes --}}
                <template x-if="detail?.notes">
                    <div class="mb-4 p-3 bg-amber-50 rounded-xl border border-amber-100">
                        <p class="text-xs font-semibold text-amber-700 mb-1">Notes</p>
                        <p class="text-sm text-amber-800" x-text="detail.notes"></p>
                    </div>
                </template>

                {{-- Raw text --}}
                <div x-data="{ open: false }" class="mt-2">
                    <button @click="open = !open"
                            class="flex items-center gap-1.5 text-[11px] font-bold text-gray-400 hover:text-gray-600 uppercase tracking-wide transition-colors mb-2">
                        <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                        <span x-text="open ? 'Hide Raw OCR Text' : 'View Raw OCR Text'"></span>
                    </button>
                    <div x-show="open" x-transition x-cloak>
                        <pre class="text-[11px] leading-relaxed text-gray-600 bg-gray-50 border border-gray-100 rounded-xl p-3 max-h-40 overflow-y-auto whitespace-pre-wrap font-mono shadow-inner"
                             x-text="detail?.raw_text || 'No raw text available.'"></pre>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between text-xs text-gray-400">
                    <span x-text="'Scanned ' + detail?.created_at"></span>
                    <span class="badge badge-saved" x-text="detail?.status"></span>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function ocrHistory() {
    return {
        showModal    : false,
        loadingDetail: false,
        detail       : null,

        init() {
            lucide.createIcons();
            
            // UX Win: Prevent background scrolling when modal is open
            this.$watch('showModal', (value) => {
                if (value) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            });
        },

        async openDetail(id) {
            this.showModal     = true;
            this.loadingDetail = true;
            this.detail        = null;

            try {
                const res  = await fetch(`/admin/ocr-scanner/${id}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.success) {
                    this.detail = data.scan;
                    this.$nextTick(() => lucide.createIcons());
                }
            } catch (e) {
                this.showModal = false;
                if(typeof BizAlert !== 'undefined') BizAlert.toast('Could not load scan detail.', 'error');
                else alert('Could not load scan detail.');
            } finally {
                this.loadingDetail = false;
            }
        },

        async archiveScan(id) {
            if(typeof BizAlert === 'undefined') return;

            const result = await BizAlert.confirm(
                'Archive this scan?',
                'It will be removed from your history.',
                'Yes, Archive',
                'warning'
            );

            if (!result.isConfirmed) return;

            try {
                const res = await fetch(`/admin/ocr-scanner/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN'    : document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await res.json();

                if (data.success) {
                    BizAlert.toast('Scan archived successfully.', 'success');
                    // Add a slight delay before reload so the user sees the toast
                    setTimeout(() => window.location.reload(), 1000); 
                }
            } catch (e) {
                BizAlert.toast('Could not archive scan.', 'error');
            }
        },
    };
}
</script>
@endpush
