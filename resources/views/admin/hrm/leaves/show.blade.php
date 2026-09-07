@extends('layouts.admin')

@section('title', 'Leave Request')

@section('header-title')
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.hrm.leaves.index') }}"
            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div>
            <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Leave Request</h1>
            <p class="text-xs text-gray-400 font-medium mt-0.5">{{ $leave->employee->user?->name }} · {{ $leave->leaveType?->name }}</p>
        </div>
    </div>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .detail-card { background: #fff; border: 1.5px solid #f1f5f9; border-radius: 16px; overflow: hidden; margin-bottom: 16px; }
    .card-header { padding: 13px 18px; border-bottom: 1px solid #f8fafc; display: flex; align-items: center; gap: 10px; }
    .card-icon { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .card-title { font-size: 12px; font-weight: 800; color: #374151; letter-spacing: 0.03em; }
    .card-body { padding: 18px; }
    .info-row { display: flex; justify-content: space-between; align-items: flex-start; padding: 9px 0; border-bottom: 1px solid #f9fafb; }
    .info-row:last-child { border-bottom: none; padding-bottom: 0; }
    .info-label { font-size: 11px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; }
    .info-value { font-size: 13px; font-weight: 600; color: #1f2937; text-align: right; max-width: 65%; }
    .field-label { font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
    .field-input { width: 100%; border: 1.5px solid #e5e7eb; border-radius: 10px; padding: 10px 30px; font-size: 13.5px; outline: none; transition: border-color 150ms ease; font-family: inherit; background: #fff; }
    .field-input:focus { border-color: var(--brand-600); box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-600) 10%, transparent); }
    .timeline-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; margin-top: 4px; }
    .timeline-line { width: 2px; background: #f3f4f6; flex: 1; min-height: 24px; margin: 4px auto 4px; }
</style>
@endpush

@section('content')

@php
    $sc = \App\Models\Hrm\Leave::STATUS_COLORS[$leave->status] ?? ['bg' => '#f3f4f6', 'text' => '#374151', 'dot' => '#9ca3af'];
    $empName = $leave->employee->user?->name ?? '?';
    $avatarColors = ['#3b82f6','#8b5cf6','#10b981','#f59e0b','#ef4444','#06b6d4'];
    $avatarBg = $avatarColors[abs(crc32($empName)) % 6];
@endphp

<div class="w-full" x-data="leaveShow()">

    {{-- Top: Employee + Leave Summary --}}
    <div class="detail-card">
        <div class="card-body">
            <div class="flex items-start gap-5 flex-wrap">
                {{-- Avatar --}}
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-white text-xl font-black flex-shrink-0"
                    style="background: {{ $avatarBg }}">
                    {{ strtoupper(substr($empName, 0, 1)) }}
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-4 flex-wrap">
                        <div>
                            <p class="text-[18px] font-black text-gray-900">{{ $empName }}</p>
                            <p class="text-[12px] text-gray-500 mt-0.5">
                                {{ $leave->employee->employee_code }} &nbsp;·&nbsp;
                                {{ $leave->employee->designation?->name }} &nbsp;·&nbsp;
                                {{ $leave->employee->department?->name }}
                            </p>
                        </div>
                        <span class="text-[11px] font-extrabold uppercase tracking-wider px-3 py-1.5 rounded-lg"
                            style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }}">
                            {{ \App\Models\Hrm\Leave::STATUS_LABELS[$leave->status] }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5">
                        <div class="bg-gray-50 rounded-xl px-4 py-3">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Leave Type</p>
                            <p class="text-[13px] font-black text-gray-800">{{ $leave->leaveType?->name }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-xl px-4 py-3">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Duration</p>
                            <p class="text-[13px] font-black text-gray-800">{{ $leave->total_days }} {{ $leave->total_days == 1 ? 'day' : 'days' }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-xl px-4 py-3">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">From</p>
                            <p class="text-[13px] font-black text-gray-800">{{ $leave->from_date->format('d M Y') }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-xl px-4 py-3">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">To</p>
                            <p class="text-[13px] font-black text-gray-800">{{ $leave->to_date->format('d M Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Left: Details --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Reason --}}
            <div class="detail-card">
                <div class="card-header">
                    <div class="card-icon bg-blue-50"><i data-lucide="message-square" class="w-4 h-4 text-blue-500"></i></div>
                    <span class="card-title">Reason</span>
                </div>
                <div class="card-body">
                    <p class="text-[13px] text-gray-700 leading-relaxed">{{ $leave->reason }}</p>
                </div>
            </div>

            {{-- Document --}}
            @if($leave->document)
            <div class="detail-card">
                <div class="card-header">
                    <div class="card-icon bg-amber-50"><i data-lucide="paperclip" class="w-4 h-4 text-amber-500"></i></div>
                    <span class="card-title">Supporting Document</span>
                </div>
                <div class="card-body">
                    <a href="{{ route('admin.hrm.leaves.document', $leave->id) }}" target="_blank"
                        class="inline-flex items-center gap-2 text-[13px] font-bold text-blue-600 hover:text-blue-800 transition-colors">
                        <i data-lucide="download" class="w-4 h-4"></i> View Document
                    </a>
                </div>
            </div>
            @endif

            {{-- Admin Remarks --}}
            @if($leave->admin_remarks)
            <div class="detail-card">
                <div class="card-header">
                    <div class="card-icon bg-purple-50"><i data-lucide="shield" class="w-4 h-4 text-purple-500"></i></div>
                    <span class="card-title">Admin Remarks</span>
                </div>
                <div class="card-body">
                    <p class="text-[13px] text-gray-700 leading-relaxed">{{ $leave->admin_remarks }}</p>
                </div>
            </div>
            @endif

            {{-- Approve / Reject Actions (only for pending) --}}
            @if($leave->status === 'pending')
            <div class="detail-card">
                <div class="card-header">
                    <div class="card-icon bg-green-50"><i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i></div>
                    <span class="card-title">Take Action</span>
                </div>
                <div class="card-body space-y-4">
                    <div>
                        <label class="field-label">Remarks (optional)</label>
                        <textarea x-model="remarks" class="field-input" rows="3" placeholder="Add remarks for the employee..."></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button @click="approveLeave()" :disabled="saving"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-[13px] font-bold text-white rounded-xl hover:opacity-90 transition-opacity disabled:opacity-50"
                            style="background: #10b981">
                            <i data-lucide="check" class="w-4 h-4"></i> Approve Leave
                        </button>
                        <button @click="rejectLeave()" :disabled="saving"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-[13px] font-bold text-white rounded-xl hover:opacity-90 transition-opacity disabled:opacity-50"
                            style="background: #ef4444">
                            <i data-lucide="x" class="w-4 h-4"></i> Reject Leave
                        </button>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Right: Timeline --}}
        <div class="space-y-4">
            <div class="detail-card">
                <div class="card-header">
                    <div class="card-icon bg-gray-50"><i data-lucide="activity" class="w-4 h-4 text-gray-500"></i></div>
                    <span class="card-title">Timeline</span>
                </div>
                <div class="card-body space-y-0">

                    {{-- Applied --}}
                    <div class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <div class="timeline-dot bg-blue-500"></div>
                            <div class="timeline-line"></div>
                        </div>
                        <div class="pb-4">
                            <p class="text-[12px] font-bold text-gray-800">Applied</p>
                            <p class="text-[11px] text-gray-400">{{ $leave->created_at->format('d M Y, h:i A') }}</p>
                        </div>
                    </div>

                    {{-- Approved / Rejected --}}
                    @if($leave->approved_at)
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div class="timeline-dot {{ $leave->status === 'approved' ? 'bg-green-500' : 'bg-red-500' }}"></div>
                                @if($leave->cancelled_at) <div class="timeline-line"></div> @endif
                            </div>
                            <div class="{{ $leave->cancelled_at ? 'pb-4' : '' }}">
                                <p class="text-[12px] font-bold text-gray-800 capitalize">{{ $leave->status }}</p>
                                <p class="text-[11px] text-gray-400">{{ $leave->approved_at->format('d M Y, h:i A') }}</p>
                                @if($leave->approvedByUser)
                                    <p class="text-[11px] text-gray-500 mt-0.5">by {{ $leave->approvedByUser->name }}</p>
                                @endif
                            </div>
                        </div>
                    @elseif($leave->status === 'pending')
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div class="timeline-dot bg-amber-400"></div>
                            </div>
                            <div>
                                <p class="text-[12px] font-bold text-amber-700">Awaiting Approval</p>
                                <p class="text-[11px] text-gray-400">Pending review</p>
                            </div>
                        </div>
                    @endif

                    {{-- Cancelled --}}
                    @if($leave->cancelled_at)
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div class="timeline-dot bg-gray-400"></div>
                            </div>
                            <div>
                                <p class="text-[12px] font-bold text-gray-700">Cancelled</p>
                                <p class="text-[11px] text-gray-400">{{ $leave->cancelled_at->format('d M Y, h:i A') }}</p>
                                @if($leave->cancellation_reason)
                                    <p class="text-[11px] text-gray-500 mt-0.5">{{ $leave->cancellation_reason }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Leave Details --}}
            <div class="detail-card">
                <div class="card-header">
                    <div class="card-icon bg-teal-50"><i data-lucide="info" class="w-4 h-4 text-teal-500"></i></div>
                    <span class="card-title">Details</span>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <span class="info-label">Day Type</span>
                        <span class="info-value capitalize">{{ str_replace('_', ' ', $leave->day_type) }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Paid Leave</span>
                        <span class="info-value">{{ $leave->leaveType?->is_paid ? 'Yes' : 'No' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Applied On</span>
                        <span class="info-value">{{ $leave->created_at->format('d M Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
window.leaveShow = function() {
    return {
        remarks: '',
        saving: false,
        init() { this.$nextTick(() => { if (window.lucide) lucide.createIcons(); }); },

        async approveLeave() {
            this.saving = true;
            try {
                const resp = await fetch('{{ route("admin.hrm.leaves.approve", $leave) }}', {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ remarks: this.remarks }),
                });
                const data = await resp.json();
                if (!resp.ok) { BizAlert.toast(data.message || 'Error', 'error'); return; }
                BizAlert.toast(data.message, 'success');
                setTimeout(() => window.location.reload(), 700);
            } catch(e) { BizAlert.toast('Network error', 'error'); } finally { this.saving = false; }
        },

        rejectLeave() {
            BizAlert.confirm('Reject Leave', 'Are you sure you want to reject this leave request?', 'Reject').then(async (r) => {
                if (!r.isConfirmed) return;
                this.saving = true;
                try {
                    const resp = await fetch('{{ route("admin.hrm.leaves.reject", $leave) }}', {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                        body: JSON.stringify({ remarks: this.remarks }),
                    });
                    const data = await resp.json();
                    if (!resp.ok) { BizAlert.toast(data.message || 'Error', 'error'); return; }
                    BizAlert.toast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 700);
                } catch(e) { BizAlert.toast('Network error', 'error'); } finally { this.saving = false; }
            });
        },
    };
};
</script>
@endpush
