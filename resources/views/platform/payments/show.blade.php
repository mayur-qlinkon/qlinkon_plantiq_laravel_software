@extends('layouts.platform')

@section('title', 'Payment · ' . $payment->cf_order_id)
@section('header', 'Payment Detail')

@section('styles')
<style>
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 14px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.03em;
    }
    .status-paid     { background: #d1fae5; color: #065f46; }
    .status-pending  { background: #fef3c7; color: #92400e; }
    .status-failed   { background: #fee2e2; color: #991b1b; }
    .status-refunded { background: #e0e7ff; color: #3730a3; }

    .detail-card {
        background: white;
        border-radius: 16px;
        border: 1px solid #f1f5f9;
    }
    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        padding: 11px 0;
        border-bottom: 1px solid #f8fafc;
    }
    .detail-row:last-child { border-bottom: none; }
    .detail-label {
        font-size: 12px;
        font-weight: 600;
        color: #94a3b8;
        min-width: 140px;
        flex-shrink: 0;
    }
    .detail-value {
        font-size: 13px;
        font-weight: 500;
        color: #1e293b;
        text-align: right;
        word-break: break-all;
    }

    .json-viewer {
        font-family: 'Courier New', monospace;
        font-size: 11.5px;
        line-height: 1.7;
        background: #0f172a;
        color: #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        overflow-x: auto;
        max-height: 320px;
        overflow-y: auto;
    }
    .json-key   { color: #7dd3fc; }
    .json-str   { color: #86efac; }
    .json-num   { color: #fcd34d; }
    .json-bool  { color: #f9a8d4; }
    .json-null  { color: #94a3b8; }

    .timeline-dot {
        width: 10px; height: 10px;
        border-radius: 50%;
        border: 2px solid white;
        box-shadow: 0 0 0 2px currentColor;
        flex-shrink: 0;
    }
</style>
@endsection

@section('content')
<div class="mx-auto space-y-5">

    {{-- ── Breadcrumb + Back ── --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 text-sm text-gray-400">
            <a href="{{ route('platform.payments.index') }}" class="hover:text-brand-600 font-medium transition">Payment Logs</a>
            <i class="fa-solid fa-chevron-right text-[10px]"></i>
            <span class="font-mono text-gray-600 font-semibold text-xs">{{ $payment->cf_order_id }}</span>
        </div>
        <a href="{{ route('platform.payments.index') }}"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition">
            <i class="fa-solid fa-arrow-left text-xs"></i> Back
        </a>
    </div>

    {{-- ── Hero Card ── --}}
    <div class="detail-card overflow-hidden">
        {{-- Colored top strip based on status --}}
        @php
            $stripColor = match($payment->status) {
                'paid'     => 'bg-emerald-500',
                'pending'  => 'bg-amber-400',
                'failed'   => 'bg-red-500',
                'refunded' => 'bg-indigo-500',
                default    => 'bg-gray-300',
            };
        @endphp
        <div class="h-1.5 w-full {{ $stripColor }}"></div>

        <div class="p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                {{-- Company avatar --}}
                <div class="w-14 h-14 rounded-2xl bg-brand-500/10 text-brand-600 flex items-center justify-center text-xl font-extrabold shrink-0">
                    {{ strtoupper(substr($payment->company->name ?? '?', 0, 1)) }}
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Company</p>
                    <h2 class="text-lg font-extrabold text-gray-900 leading-tight">{{ $payment->company->name ?? '—' }}</h2>
                    <p class="text-sm text-gray-400">{{ $payment->company->email ?? '' }}</p>
                </div>
            </div>
            <div class="flex flex-col items-start sm:items-end gap-2">
                <span class="status-badge status-{{ $payment->status }}">
                    @if($payment->status === 'paid') <i class="fa-solid fa-circle-check"></i>
                    @elseif($payment->status === 'pending') <i class="fa-solid fa-clock"></i>
                    @elseif($payment->status === 'failed') <i class="fa-solid fa-circle-xmark"></i>
                    @else <i class="fa-solid fa-rotate-left"></i>
                    @endif
                    {{ ucfirst($payment->status) }}
                </span>
                <p class="text-3xl font-black text-gray-900">₹{{ number_format($payment->amount, 2) }}</p>
                <p class="text-xs text-gray-400">{{ $payment->currency }} · {{ $payment->created_at->format('d M Y, h:i A') }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- ── Left: Transaction Details ── --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Payment IDs --}}
            <div class="detail-card p-5">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">
                    <i class="fa-solid fa-fingerprint mr-1.5"></i> Transaction IDs
                </h3>
                <div class="space-y-0">
                    <div class="detail-row">
                        <span class="detail-label">CF Order ID</span>
                        <span class="detail-value font-mono text-xs font-bold">{{ $payment->cf_order_id }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">CF Payment ID</span>
                        <span class="detail-value font-mono text-xs">{{ $payment->cf_payment_id ?? '—' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">CF Transaction ID</span>
                        <span class="detail-value font-mono text-xs">{{ $payment->cf_transaction_id ?? '—' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Initiated By</span>
                        <span class="detail-value">
                            <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-lg
                                {{ $payment->initiated_by === 'web' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700' }}">
                                <i class="fa-solid {{ $payment->initiated_by === 'web' ? 'fa-globe' : 'fa-webhook' }} text-[9px]"></i>
                                {{ ucfirst($payment->initiated_by) }}
                            </span>
                        </span>
                    </div>
                </div>
            </div>

            {{-- Payment Info --}}
            <div class="detail-card p-5">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">
                    <i class="fa-solid fa-credit-card mr-1.5"></i> Payment Info
                </h3>
                <div class="space-y-0">
                    <div class="detail-row">
                        <span class="detail-label">Amount</span>
                        <span class="detail-value font-bold text-gray-900">₹{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Payment Method</span>
                        <span class="detail-value">
                            @if($payment->payment_method)
                                @php
                                    $method = is_array($payment->payment_method)
                                        ? ($payment->payment_method['type'] ?? 'N/A')
                                        : $payment->payment_method;
                                @endphp
                                <span class="inline-block text-xs font-bold px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 uppercase tracking-wide">{{ $method }}</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Plan</span>
                        <span class="detail-value">{{ $payment->plan->name ?? '—' }}</span>
                    </div>
                    @if($payment->failure_reason)
                    <div class="detail-row">
                        <span class="detail-label">Failure Reason</span>
                        <span class="detail-value text-red-600 font-semibold">{{ $payment->failure_reason }}</span>
                    </div>
                    @endif
                    <div class="detail-row">
                        <span class="detail-label">Created At</span>
                        <span class="detail-value">{{ $payment->created_at->format('d M Y, h:i:s A') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Last Updated</span>
                        <span class="detail-value">{{ $payment->updated_at->format('d M Y, h:i:s A') }}</span>
                    </div>
                </div>
            </div>

            {{-- Gateway Response JSON --}}
            @if($payment->gateway_response)
            <div class="detail-card p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                        <i class="fa-solid fa-code mr-1.5"></i> Gateway Response
                    </h3>
                    <button onclick="copyJson()" class="text-xs font-semibold text-brand-600 hover:underline flex items-center gap-1">
                        <i class="fa-regular fa-copy text-[10px]"></i> Copy JSON
                    </button>
                </div>
                <div class="json-viewer" id="json-output">
                    {!! formatJson($payment->gateway_response) !!}
                </div>
            </div>
            @endif
        </div>

        {{-- ── Right: Company + Timeline ── --}}
        <div class="space-y-5">

            {{-- Company Info --}}
            <div class="detail-card p-5">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">
                    <i class="fa-solid fa-building mr-1.5"></i> Company
                </h3>
                <div class="space-y-0">
                    <div class="detail-row">
                        <span class="detail-label">Name</span>
                        <span class="detail-value text-xs">{{ $payment->company->name ?? '—' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email</span>
                        <span class="detail-value text-xs">{{ $payment->company->email ?? '—' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Phone</span>
                        <span class="detail-value text-xs">{{ $payment->company->phone ?? '—' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Slug</span>
                        <span class="detail-value font-mono text-xs text-gray-500">{{ $payment->company->slug ?? '—' }}</span>
                    </div>
                </div>
                @if($payment->company)
                <a href="{{ route('platform.companies.show', $payment->company) }}"
                    class="mt-4 flex items-center justify-center gap-2 w-full py-2 rounded-xl bg-gray-50 hover:bg-gray-100 text-xs font-semibold text-gray-600 transition">
                    View Company <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                </a>
                @endif
            </div>

            {{-- Status Timeline --}}
            <div class="detail-card p-5">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">
                    <i class="fa-solid fa-timeline mr-1.5"></i> Status Timeline
                </h3>
                <div class="space-y-4">
                    @php
                        $timeline = [
                            ['label' => 'Payment Initiated', 'time' => $payment->created_at, 'done' => true, 'color' => 'text-gray-400'],
                            ['label' => 'Processing', 'time' => null, 'done' => in_array($payment->status, ['paid', 'failed', 'refunded']), 'color' => 'text-blue-400'],
                            ['label' => 'Completed', 'time' => $payment->status === 'paid' ? $payment->updated_at : null, 'done' => $payment->status === 'paid', 'color' => 'text-emerald-500'],
                            ['label' => 'Failed', 'time' => $payment->status === 'failed' ? $payment->updated_at : null, 'done' => $payment->status === 'failed', 'color' => 'text-red-500'],
                        ];
                        // Remove "Failed" if paid, remove "Completed" if failed
                        if ($payment->status === 'paid') {
                            $timeline = array_filter($timeline, fn($t) => $t['label'] !== 'Failed');
                        } elseif ($payment->status === 'failed') {
                            $timeline = array_filter($timeline, fn($t) => $t['label'] !== 'Completed');
                        }
                    @endphp

                    @foreach($timeline as $i => $step)
                    <div class="flex items-start gap-3">
                        <div class="flex flex-col items-center">
                            <div class="timeline-dot mt-0.5 {{ $step['done'] ? $step['color'] : 'text-gray-200' }}"></div>
                            @if(!$loop->last)
                                <div class="w-px flex-1 mt-1 mb-0 h-5 {{ $step['done'] ? 'bg-gray-200' : 'bg-gray-100' }}"></div>
                            @endif
                        </div>
                        <div class="pb-2">
                            <p class="text-xs font-semibold {{ $step['done'] ? 'text-gray-700' : 'text-gray-300' }}">
                                {{ $step['label'] }}
                            </p>
                            @if($step['time'])
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $step['time']->format('d M Y, h:i A') }}</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="detail-card p-5">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">
                    <i class="fa-solid fa-bolt mr-1.5"></i> Quick Actions
                </h3>
                <div class="space-y-2">
                    <a href="{{ route('platform.companies.show', $payment->company) }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-gray-50 hover:bg-gray-100 text-sm font-medium text-gray-700 transition">
                        <i class="fa-solid fa-building text-gray-400 w-4 text-center text-xs"></i>
                        View Company
                    </a>
                    <a href="{{ route('platform.subscriptions.index', ['company' => $payment->company_id]) }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-gray-50 hover:bg-gray-100 text-sm font-medium text-gray-700 transition">
                        <i class="fa-regular fa-credit-card text-gray-400 w-4 text-center text-xs"></i>
                        Subscriptions
                    </a>
                    <a href="{{ route('platform.payments.index', ['q' => $payment->cf_order_id]) }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-gray-50 hover:bg-gray-100 text-sm font-medium text-gray-700 transition">
                        <i class="fa-solid fa-magnifying-glass text-gray-400 w-4 text-center text-xs"></i>
                        Search This Order
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function copyJson() {
        const raw = @json($payment->gateway_response ?? []);
        navigator.clipboard.writeText(JSON.stringify(raw, null, 2))
            .then(() => {
                Swal.fire({ icon: 'success', title: 'Copied!', text: 'Gateway response copied to clipboard.', timer: 1500, showConfirmButton: false });
            });
    }
</script>
@endsection

@php
/**
 * Syntax-highlight a JSON array/object into HTML spans.
 * Called inline from Blade — keeps the view self-contained.
 */
function formatJson(mixed $data): string {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) return '<span class="json-null">null</span>';

    $json = htmlspecialchars($json, ENT_QUOTES, 'UTF-8');

    // Keys
    $json = preg_replace('/"([^"]+)"(:)/', '<span class="json-key">"$1"</span>$2', $json);
    // String values
    $json = preg_replace('/: "([^"]*)"/', ': <span class="json-str">"$1"</span>', $json);
    // Numeric values
    $json = preg_replace('/: (\d+\.?\d*)/', ': <span class="json-num">$1</span>', $json);
    // Bool
    $json = preg_replace('/: (true|false)/', ': <span class="json-bool">$1</span>', $json);
    // Null
    $json = preg_replace('/: (null)/', ': <span class="json-null">$1</span>', $json);

    return $json;
}
@endphp