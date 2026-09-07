@extends('layouts.admin')

@section('title', $announcement->title)

@section('header-title')
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.hrm.announcements.index') }}"
            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        </a>
        <div>
            <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest truncate max-w-[400px]">{{ $announcement->title }}</h1>
            <p class="text-xs text-gray-400 font-medium mt-0.5">Announcement details</p>
        </div>
    </div>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }

    .detail-card {
        background: #fff;
        border: 1.5px solid #f1f5f9;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(0,0,0,0.01);
    }

    .detail-card-head {
        padding: 13px 18px;
        border-bottom: 1px solid #f8fafc;
        display: flex;
        flex-wrap: wrap; /* Allows the right-side badge to drop down if it's too tight */
        align-items: center;
        gap: 10px;
    }

    .detail-card-icon {
        width: 28px; height: 28px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }

    .detail-card-title {
        font-size: 12px;
        font-weight: 800;
        color: #374151;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }

    .detail-card-body { padding: 18px; }

    .info-row {
        display: flex;
        flex-wrap: wrap; /* Allows the value to drop down gracefully on iPads */
        gap: 4px 8px;
        padding: 10px 0;
        border-bottom: 1px solid #f8fafc;
    }
    .info-row:last-child { border-bottom: none; padding-bottom: 0; }
    .info-row:first-child { padding-top: 0; }

    .info-label {
        font-size: 11px;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        min-width: 110px; /* Reduced from 140px to give the value more room */
        flex-shrink: 0;
    }

    .info-value {
        font-size: 13px;
        color: #374151;
        font-weight: 600;
    }

    .table-row { border-bottom: 1px solid #f8fafc; transition: background 100ms; }
    .table-row:hover { background: #fafbfc; }
    .table-row:last-child { border-bottom: none; }
</style>
@endpush

@section('content')

@php
    $typeBadge = match($announcement->type) {
        'policy'      => 'bg-blue-50 text-blue-700 border-blue-200',
        'event'       => 'bg-purple-50 text-purple-700 border-purple-200',
        'holiday'     => 'bg-red-50 text-red-700 border-red-200',
        'urgent'      => 'bg-orange-50 text-orange-700 border-orange-200',
        'celebration' => 'bg-green-50 text-green-700 border-green-200',
        default       => 'bg-gray-50 text-gray-600 border-gray-200',
    };
    $priorityBadge = match($announcement->priority) {
        'high'     => 'bg-amber-50 text-amber-700 border-amber-200',
        'critical' => 'bg-red-50 text-red-700 border-red-200',
        'normal'   => 'bg-blue-50 text-blue-700 border-blue-200',
        default    => 'bg-gray-50 text-gray-500 border-gray-200',
    };
    $typeLabels     = ['general' => 'General', 'policy' => 'Policy', 'event' => 'Event', 'holiday' => 'Holiday', 'urgent' => 'Urgent', 'celebration' => 'Celebration'];
    $priorityLabels = ['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'critical' => 'Critical'];
@endphp

<div class="pb-10" x-data="announcementShow()">

    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        
        {{-- Badges --}}
        <div class="flex items-center gap-2 flex-wrap">
            <span class="inline-flex items-center text-[10px] font-extrabold uppercase tracking-wider border px-2.5 py-1 rounded-md {{ $typeBadge }}">
                {{ $typeLabels[$announcement->type] ?? $announcement->type }}
            </span>
            <span class="inline-flex items-center text-[10px] font-extrabold uppercase tracking-wider border px-2.5 py-1 rounded-md {{ $priorityBadge }}">
                {{ $priorityLabels[$announcement->priority] ?? $announcement->priority }}
            </span>
            @if($announcement->is_pinned)
                <span class="inline-flex items-center gap-1 text-[10px] font-extrabold uppercase tracking-wider border border-yellow-200 text-yellow-700 bg-yellow-50 px-2.5 py-1 rounded-md">
                    <i data-lucide="pin" class="w-3 h-3"></i> Pinned
                </span>
            @endif
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center gap-2 flex-wrap">
            @if($announcement->status !== 'published')
                <button @click="publishAnnouncement({{ $announcement->id }}, '{{ addslashes($announcement->title) }}')"
                    class="inline-flex items-center gap-1.5 text-[12px] font-bold px-4 py-2 rounded-xl shadow-sm text-white bg-green-500 hover:bg-green-600 transition-colors">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i> Publish Now
                </button>
            @endif

            <a href="{{ route('admin.hrm.announcements.edit', $announcement) }}"
                class="inline-flex items-center gap-1.5 text-[12px] font-bold px-4 py-2 rounded-xl text-blue-600 bg-blue-50 hover:bg-blue-100 transition-colors border border-blue-100 shadow-sm">
                <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit
            </a>

            <button @click="confirmDelete({{ $announcement->id }}, '{{ addslashes($announcement->title) }}')"
                class="inline-flex items-center gap-1.5 text-[12px] font-bold px-4 py-2 rounded-xl text-red-600 bg-red-50 hover:bg-red-100 transition-colors border border-red-100 shadow-sm">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete
            </button>
        </div>
    </div>

    {{-- Main Layout Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">

        {{-- 🟢 LEFT PANE (Spans 2 columns on PC) --}}
        <div class="md:col-span-1 lg:col-span-2 space-y-6 min-w-0">
            
            {{-- Content Card --}}
            <div class="detail-card">
                <div class="detail-card-head">
                    <div class="detail-card-icon" style="background: #eff6ff">
                        <i data-lucide="file-text" style="width:14px;height:14px;color:#3b82f6"></i>
                    </div>
                    <span class="detail-card-title">Message Content</span>
                </div>
                <div class="detail-card-body">
                    <h2 class="text-xl font-black text-gray-800 mb-4">{{ $announcement->title }}</h2>
                    <div class="prose prose-sm max-w-none text-gray-700 text-[14px] leading-relaxed whitespace-pre-wrap">{{ $announcement->content }}</div>
                </div>
            </div>

            {{-- Attachment Card (Displays only if attachment exists) --}}
            @if($announcement->attachment)
            <div class="detail-card">
                <div class="detail-card-head">
                    <div class="detail-card-icon" style="background: #fdf4ff">
                        <i data-lucide="paperclip" style="width:14px;height:14px;color:#c026d3"></i>
                    </div>
                    <span class="detail-card-title">Attached Media</span>
                </div>
                <div class="detail-card-body">
                    <div class="flex flex-wrap items-center justify-between p-4 bg-gray-50 border border-gray-200 rounded-xl gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-lg bg-white border border-gray-200 text-brand-500 flex items-center justify-center shadow-sm flex-shrink-0">
                                <i data-lucide="file" class="w-6 h-6"></i>
                            </div>
                            <div class="overflow-hidden">
                                <p class="text-[13px] font-bold text-gray-800 truncate">{{ $announcement->attachment_name ?? 'Attached Document' }}</p>
                                <p class="text-[11px] font-medium text-gray-500 mt-0.5">Click the button to download this secure file.</p>
                            </div>
                        </div>
                        
                        {{-- 🌟 CHANGED: Now points to the secure download route --}}
                        <a href="{{ route('admin.hrm.announcements.download', $announcement) }}"
                                download
                                target="_blank"
                                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-[12px] font-bold text-white bg-gray-800 hover:bg-gray-900 rounded-xl transition-colors shadow-sm flex-shrink-0">
                                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                Download File
                            </a>
                    </div>
                </div>
            </div>
            @endif

        </div>

        {{-- 🟢 RIGHT PANE (Spans 1 column on PC) --}}
        <div class="space-y-6 min-w-0">

            {{-- Info Card --}}
            <div class="detail-card">
                <div class="detail-card-head">
                    <div class="detail-card-icon" style="background: #f9fafb">
                        <i data-lucide="info" style="width:14px;height:14px;color:#6b7280"></i>
                    </div>
                    <span class="detail-card-title">Configuration details</span>
                </div>
                <div class="detail-card-body">
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="info-value">
                            @if($announcement->status === 'published')
                                <span class="inline-flex items-center gap-1 text-[10px] font-extrabold uppercase tracking-wider text-green-700 bg-green-50 border border-green-200 px-2 py-0.5 rounded-md">
                                    <i data-lucide="check-circle" class="w-3 h-3"></i> Published
                                </span>
                            @elseif($announcement->status === 'scheduled')
                                <span class="inline-flex items-center gap-1 text-[10px] font-extrabold uppercase tracking-wider text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded-md">
                                    <i data-lucide="clock" class="w-3 h-3"></i> Scheduled
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-[10px] font-extrabold uppercase tracking-wider text-gray-600 bg-gray-100 border border-gray-200 px-2 py-0.5 rounded-md">
                                    <i data-lucide="file-edit" class="w-3 h-3"></i> Draft
                                </span>
                            @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Target Audience</span>
                        <span class="info-value">
                            {{ \App\Models\Hrm\Announcement::TARGET_LABELS[$announcement->target_audience] ?? 'All Employees' }}
                            @if($announcement->target_audience !== 'all' && !empty($announcement->target_ids))
                                <span class="text-[10px] text-gray-400 ml-1">({{ count($announcement->target_ids) }} selected)</span>
                            @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Publish Date</span>
                        <span class="info-value">{{ $announcement->publish_at ? $announcement->publish_at->format('d M Y, h:i A') : ($announcement->published_at ? $announcement->published_at->format('d M Y, h:i A') : '—') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Expiry Date</span>
                        <span class="info-value">{{ $announcement->expire_at ? $announcement->expire_at->format('d M Y, h:i A') : 'Never' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Author</span>
                        <span class="info-value">{{ $announcement->createdByUser?->name ?? 'System' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Created</span>
                        <span class="info-value">{{ $announcement->created_at->format('d M Y, h:i A') }}</span>
                    </div>
                </div>
            </div>

            {{-- Acknowledgements Card --}}
            <div class="detail-card">
                <div class="detail-card-head">
                    <div class="detail-card-icon" style="background: #f0fdf4">
                        <i data-lucide="check-square" style="width:14px;height:14px;color:#16a34a"></i>
                    </div>
                    <span class="detail-card-title">Acknowledgements</span>
                    @if($announcement->requires_acknowledgement)
                        <span class="ml-auto text-[11px] font-bold text-gray-400 bg-gray-50 px-2 py-0.5 rounded-md border border-gray-100">
                            {{ $announcement->acknowledgements->count() }} Signed
                        </span>
                    @endif
                </div>
                
                @if($announcement->requires_acknowledgement)
                    <div class="detail-card-body !p-0">
                        @if($announcement->acknowledgements->isNotEmpty())
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="border-b border-gray-100 bg-gray-50/50">
                                            <th class="px-4 py-2.5 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">Employee</th>
                                            <th class="px-4 py-2.5 text-right text-[10px] font-black text-gray-400 uppercase tracking-wider">Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($announcement->acknowledgements as $ack)
                                            <tr class="table-row">
                                                <td class="px-4 py-3 text-[13px] font-bold text-gray-700">
                                                    {{ $ack->user?->name ?? 'Unknown User' }}
                                                </td>
                                                <td class="px-4 py-3 text-[11px] font-medium text-gray-500 text-right">
                                                    {{ $ack->created_at?->format('d M, h:i A') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center py-10 text-center px-4">
                                <div class="w-12 h-12 bg-gray-50 border border-gray-100 rounded-full flex items-center justify-center mb-3">
                                    <i data-lucide="clock" class="w-5 h-5 text-gray-400"></i>
                                </div>
                                <p class="text-[13px] font-bold text-gray-700">Waiting for signatures</p>
                                <p class="text-[11px] font-medium text-gray-500 mt-1">Employees will appear here once they acknowledge this announcement.</p>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="detail-card-body flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="info" class="w-4 h-4 text-gray-400"></i>
                        </div>
                        <p class="text-[12px] font-medium text-gray-500 leading-tight">
                            Digital acknowledgement is not required for this specific update.
                        </p>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
window.announcementShow = function() {
    return {
        init() {
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        publishAnnouncement(id, title) {
            BizAlert.confirm('Publish Announcement', `Are you sure you want to publish "${title}" immediately?`, 'Yes, Publish').then(async (result) => {
                if (!result.isConfirmed) return;

                try {
                    const response = await fetch(`{{ url('admin/hrm/announcements') }}/${id}/publish`, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        BizAlert.toast(data.message || 'Something went wrong', 'error');
                        return;
                    }

                    BizAlert.toast(data.message || 'Announcement published', 'success');
                    setTimeout(() => window.location.reload(), 600);
                } catch (e) {
                    BizAlert.toast('Network error. Please try again.', 'error');
                }
            });
        },

        confirmDelete(id, title) {
            BizAlert.confirm('Delete Announcement', `Are you sure you want to delete "${title}"?`, 'Delete').then(async (result) => {
                if (!result.isConfirmed) return;

                try {
                    const response = await fetch(`{{ url('admin/hrm/announcements') }}/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        BizAlert.toast(data.message || 'Cannot delete', 'error');
                        return;
                    }

                    BizAlert.toast(data.message || 'Announcement deleted', 'success');
                    window.location.href = '{{ route('admin.hrm.announcements.index') }}';
                } catch (e) {
                    BizAlert.toast('Network error. Please try again.', 'error');
                }
            });
        },
    };
};
</script>
@endpush