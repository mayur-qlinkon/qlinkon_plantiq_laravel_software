@extends('layouts.admin')

@section('title', 'My Attendance')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">My Attendance</h1>
        <p class="text-xs text-gray-400 font-medium mt-0.5">{{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}</p>
    </div>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .field-input {
        border: 1.5px solid #e5e7eb; border-radius: 10px; padding: 8px 14px;
        font-size: 13px; outline: none; background: #fff; font-family: inherit;
        transition: border-color 150ms ease;
    }
    .field-input:focus { border-color: var(--brand-600); }
</style>
@endpush

@section('content')

<div class="space-y-5 pb-10">

    {{-- ── Filter Header ── --}}
    <form method="GET" action="{{ route(Route::currentRouteName()) }}" class="bg-white p-4 border border-gray-100 rounded-2xl flex flex-wrap items-center justify-between gap-3 shadow-sm">
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">From Date</label>
                <input type="date" name="from" value="{{ request('from', $from->format('Y-m-d')) }}" class="field-input">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">To Date</label>
                <input type="date" name="to" value="{{ request('to', $to->format('Y-m-d')) }}" class="field-input">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Status</label>
                <select name="status" class="field-input">
                    <option value="">All Statuses</option>
                    <option value="present" {{ request('status') === 'present' ? 'selected' : '' }}>Present</option>
                    <option value="absent" {{ request('status') === 'absent' ? 'selected' : '' }}>Absent</option>
                    <option value="late" {{ request('status') === 'late' ? 'selected' : '' }}>Late</option>
                    <option value="half_day" {{ request('status') === 'half_day' ? 'selected' : '' }}>Half Day</option>
                    <option value="on_leave" {{ request('status') === 'on_leave' ? 'selected' : '' }}>On Leave</option>
                </select>
            </div>
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto justify-end">
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition-all">
                Filter Logs
            </button>
            <a href="{{ route(Route::currentRouteName()) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-bold px-4 py-2.5 rounded-xl transition-all">
                Reset
            </a>
        </div>
    </form>

    {{-- ── Attendance Log Table ── --}}
    <div class="md:bg-white md:border md:border-gray-100 md:rounded-2xl md:overflow-hidden space-y-4 md:space-y-0">       

        {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-gray-50">
                        <th class="px-5 py-3 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Date</th>
                        <th class="px-5 py-3 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Check In</th>
                        <th class="px-5 py-3 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Check Out</th>
                        <th class="px-5 py-3 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Worked</th>
                        <th class="px-5 py-3 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Overtime</th>
                        <th class="px-5 py-3 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Method</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($attendances as $att)
                    @php $sc = \App\Models\Hrm\Attendance::STATUS_COLORS[$att->status] ?? ['bg'=>'#f3f4f6','text'=>'#374151','dot'=>'#9ca3af']; @endphp
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-5 py-3.5">
                            <p class="text-[13px] font-bold text-gray-800">{{ $att->date->format('d M Y') }}</p>
                            <p class="text-[11px] text-gray-400">{{ $att->date->format('l') }}</p>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-extrabold uppercase tracking-wider px-2.5 py-1 rounded-lg"
                                style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }}">
                                <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background: {{ $sc['dot'] }}"></span>
                                {{ \App\Models\Hrm\Attendance::STATUS_LABELS[$att->status] ?? $att->status }}
                            </span>
                            @if($att->is_overridden)
                            <span class="ml-1 text-[10px] font-bold text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded">Edited</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-[13px] text-gray-700">
                            {{ $att->check_in_time ? $att->check_in_time->format('h:i A') : '—' }}
                        </td>
                        <td class="px-5 py-3.5 text-[13px] text-gray-700">
                            {{ $att->check_out_time ? $att->check_out_time->format('h:i A') : '—' }}
                        </td>
                        <td class="px-5 py-3.5 text-[13px] font-semibold text-gray-700">
                            {{ $att->worked_hours ? number_format($att->worked_hours, 1) . ' hrs' : '—' }}
                        </td>
                        <td class="px-5 py-3.5 text-[13px] text-gray-500">
                            {{ $att->overtime_hours > 0 ? number_format($att->overtime_hours, 1) . ' hrs' : '—' }}
                        </td>
                        <td class="px-5 py-3.5 text-[12px] text-gray-500 capitalize">
                            {{ $att->check_in_method ?? '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-sm text-gray-400">
                            No attendance records found for selected period.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- 📱 MOBILE VIEW (CARDS) --}}
        <div class="md:hidden space-y-4">
            @forelse($attendances as $att)
                @php $sc = \App\Models\Hrm\Attendance::STATUS_COLORS[$att->status] ?? ['bg'=>'#f3f4f6','text'=>'#374151','dot'=>'#9ca3af']; @endphp
                <div class="bg-white border border-gray-100 rounded-2xl p-4 shadow-sm hover:shadow-md transition-all">
                    
                    {{-- Header: Date & Status --}}
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <p class="text-[13px] font-bold text-gray-800">{{ $att->date->format('d M Y') }}</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">{{ $att->date->format('l') }}</p>
                        </div>
                        <div class="text-right flex flex-col items-end gap-1">
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-md"
                                style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }}">
                                <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background: {{ $sc['dot'] }}"></span>
                                {{ \App\Models\Hrm\Attendance::STATUS_LABELS[$att->status] ?? $att->status }}
                            </span>
                            @if($att->is_overridden)
                                <span class="text-[9px] font-bold text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded inline-block">Edited</span>
                            @endif
                        </div>
                    </div>

                    {{-- Context: Check-in / Check-out --}}
                    <div class="flex items-center justify-between bg-gray-50/80 px-3 py-2.5 rounded-lg border border-gray-100 mb-3">
                        <div>
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Check In</p>
                            <p class="text-[12px] font-bold text-gray-700">{{ $att->check_in_time ? $att->check_in_time->format('h:i A') : '—' }}</p>
                        </div>
                        <i data-lucide="arrow-right" class="w-4 h-4 text-gray-300"></i>
                        <div class="text-right">
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Check Out</p>
                            <p class="text-[12px] font-bold text-gray-700">{{ $att->check_out_time ? $att->check_out_time->format('h:i A') : '—' }}</p>
                        </div>
                    </div>

                    {{-- Footer: Hours & Method --}}
                    <div class="flex items-center justify-between text-[11px] pt-1">
                        <div class="flex items-center gap-3">
                            <div>
                                <span class="text-gray-400 font-medium">Worked:</span>
                                <span class="font-bold text-gray-700 ml-0.5">{{ $att->worked_hours ? number_format($att->worked_hours, 1) . ' hrs' : '—' }}</span>
                            </div>
                            @if($att->overtime_hours > 0)
                            <div>
                                <span class="text-gray-400 font-medium">OT:</span>
                                <span class="font-black text-[#108c2a] ml-0.5">{{ number_format($att->overtime_hours, 1) }} hrs</span>
                            </div>
                            @endif
                        </div>
                        <span class="text-gray-400 capitalize font-medium flex items-center gap-1">
                            <i data-lucide="smartphone" class="w-3 h-3"></i> {{ $att->check_in_method ?? '—' }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-sm text-gray-400 bg-white">
                    <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center mx-auto mb-3 border border-gray-100">
                        <i data-lucide="calendar-x" class="w-5 h-5 text-gray-300"></i>
                    </div>
                    <p class="font-semibold text-gray-500 mb-1">No attendance records</p>
                    <p class="text-xs text-gray-400">No logs found for the selected period.</p>
                </div>
            @endforelse
        </div>

        @if($attendances->hasPages())
        <div class="px-5 py-3 border-t border-gray-50">{{ $attendances->links() }}</div>
        @endif
    </div>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush
