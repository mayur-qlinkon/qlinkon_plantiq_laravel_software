@extends('layouts.admin')

@section('title', 'Notification History')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Notifications / History</h1>
        {{-- <p class="text-xs text-gray-400 font-medium mt-1">Review all system alerts and activity logs</p> --}}
    </div>
@endsection

@push('styles')
<style>
    .notif-row { border-bottom: 1px solid #f8fafc; transition: background 150ms; }
    .notif-row:hover { background: #fafbfc; }
    .notif-row:last-child { border-bottom: none; }
    .unread-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--brand-600); }
    .filter-input { border: 1.5px solid #e5e7eb; border-radius: 9px; padding: 8px 12px; font-size: 13px; color: #374151; outline: none; background: #fff; transition: border-color 150ms; }
    .filter-input:focus { border-color: var(--brand-600); }
</style>
@endpush

@section('content')
<div class="pb-10" x-data="notifHistory()">

    {{-- ════════ TOOLBAR ════════ --}}
    <div class="bg-white border border-gray-100 rounded-2xl px-4 py-3 mb-5 shadow-sm">
        <form method="GET" action="{{ route('admin.notifications.index') }}" class="flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="relative flex-1 min-w-[240px]">                
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search title or message..." class="filter-input pl-4 w-full">
            </div>

            <select name="type" class="filter-input min-w-[160px]" onchange="this.form.submit()">
                <option value="">All Categories</option>
                <option value="new_order" {{ request('type') == 'new_order' ? 'selected' : '' }}>Orders</option>
                <option value="crm_update" {{ request('type') == 'crm_update' ? 'selected' : '' }}>CRM Leads</option>
                <option value="announcement" {{ request('type') == 'announcement' ? 'selected' : '' }}>Announcements</option>
            </select>

            <button type="submit" class="px-6 py-2 rounded-xl text-[13px] font-bold text-white transition-all hover:opacity-90" style="background: #212538;">Search</button>

            <button type="button" @click="markAllAsRead()" 
            class="w-full sm:w-auto justify-center inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-[13px] font-bold text-white transition-all hover:opacity-95" 
            style="background: var(--brand-600);">
                <i data-lucide="check-check" class="w-4 h-4"></i> Mark All Read
            </button>
        </form>
    </div>

    {{-- ════════ TABLE ════════ --}}
    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="hidden lg:table-header-group">
                    <tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="px-6 py-4 text-[11px] font-black text-gray-400 uppercase tracking-widest w-[160px]">Category</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray-400 uppercase tracking-widest">Notification</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray-400 uppercase tracking-widest w-[120px]">Status</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray-400 uppercase tracking-widest w-[160px]">Date</th>
                        <th class="px-6 py-4 text-[11px] font-black text-gray-400 uppercase tracking-widest text-right w-[100px]">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notifications as $notif)
                        @php $data = $notif->data; @endphp
                        <tr class="notif-row flex flex-col lg:table-row p-5 lg:p-0 relative {{ !$notif->read_at ? 'bg-indigo-50/20' : '' }}">
                            {{-- Category & Type --}}
                            <td class="px-0 lg:px-6 py-1 lg:py-4 border-none lg:border-b lg:border-gray-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" 
                                        style="background-color: color-mix(in srgb, {{ $data['color'] ?? '#3b82f6' }} 10%, white)">
                                        <i data-lucide="{{ $data['icon'] ?? 'bell' }}" class="w-4 h-4" style="color: {{ $data['color'] ?? '#3b82f6' }}"></i>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">{{ str_replace('_', ' ', $data['type'] ?? 'System') }}</span>
                                        <span class="lg:hidden text-[13px] font-bold text-gray-800">{{ $data['title'] ?? 'Alert' }}</span>
                                    </div>
                                </div>
                            </td>

                            {{-- Notification Content --}}
                            <td class="px-0 lg:px-6 py-2 lg:py-4">
                                <div class="max-w-xl">
                                    <p class="hidden lg:block text-[14px] font-bold text-gray-800 mb-0.5">{{ $data['title'] ?? 'No Title' }}</p>
                                    <p class="text-[12px] text-gray-500 leading-relaxed">{{ $data['message'] ?? '' }}</p>
                                </div>
                            </td>

                            {{-- Status Badge --}}
                            <td class="px-0 lg:px-6 py-2 lg:py-4 flex lg:table-cell items-center justify-between lg:justify-start">
                                <span class="lg:hidden text-[10px] font-bold text-gray-400 uppercase tracking-widest">Status</span>
                                @if(!$notif->read_at)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[9px] font-black bg-indigo-100 text-indigo-700 uppercase border border-indigo-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-600 animate-pulse"></span> New
                                    </span>
                                @else
                                    <span class="text-[10px] font-bold text-gray-300 uppercase tracking-widest">Seen</span>
                                @endif
                            </td>

                            {{-- Time & Date --}}
                            <td class="px-0 lg:px-6 py-2 lg:py-4 flex lg:table-cell items-center justify-between lg:justify-start border-b border-gray-50 lg:border-none pb-4 lg:pb-4">
                                <span class="lg:hidden text-[10px] font-bold text-gray-400 uppercase tracking-widest">Received</span>
                                <div class="text-right lg:text-left">
                                    <p class="text-[12px] font-bold text-gray-700">{{ $notif->created_at->format('d M, Y') }}</p>
                                    <p class="text-[10px] text-gray-400 font-medium">{{ $notif->created_at->diffForHumans() }}</p>
                                </div>
                            </td>

                            {{-- Action (The Crash Fix is here) --}}
                            <td class="px-0 lg:px-6 py-3 lg:py-4 text-right">
                                @php $actionUrl = $data['link'] ?? $data['url'] ?? '#'; @endphp
                                <a href="{{ $actionUrl }}" 
                                @click="markRead('{{ $notif->id }}')" 
                                class="w-full lg:w-9 lg:h-9 flex items-center justify-center gap-2 lg:gap-0 px-4 py-2 lg:p-0 rounded-xl bg-gray-50 lg:bg-transparent text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition-all border border-gray-100 lg:border-none font-bold text-xs lg:text-base">
                                    <span class="lg:hidden">View Details</span>
                                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr class="lg:table-row flex">
                            <td colspan="5" class="px-6 py-20 text-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4"><i data-lucide="bell-off" class="w-8 h-8 text-gray-300"></i></div>
                                <p class="text-gray-500 font-bold text-sm">No history found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($notifications->hasPages())
            <div class="px-6 py-4 border-t border-gray-50 bg-gray-50/30">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function notifHistory() {
    return {
        async markRead(id) {
            try {
                await fetch(`/admin/notifications/${id}/read`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }
                });
            } catch (e) {}
        },
        async markAllAsRead() {
            const confirmed = await Swal.fire({
                title: 'Clear all notifications?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Mark All Read',
                confirmButtonColor: 'var(--brand-600)'
            });
            if (!confirmed.isConfirmed) return;
            try {
                await fetch(`/admin/notifications/mark-all-read`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }
                });
                location.reload();
            } catch (e) {
                Swal.fire('Error', 'Action failed', 'error');
            }
        }
    }
}
</script>
@endpush