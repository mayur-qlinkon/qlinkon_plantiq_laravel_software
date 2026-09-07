@extends('layouts.admin')

@section('title', 'Employees')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Employees</h1>
        {{-- <p class="text-xs text-gray-400 font-medium mt-0.5">Manage your workforce and employee records</p> --}}
    </div>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }

    .stat-card {
        background: #fff;
        border: 1.5px solid #f1f5f9;
        border-radius: 14px;
        padding: 14px 16px;
        transition: box-shadow 150ms, border-color 150ms;
    }

    .stat-card:hover {
        border-color: #e2e8f0;
        box-shadow: 0 3px 12px rgba(0,0,0,0.06);
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .type-badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        background: #f0f9ff;
        color: #0369a1;
    }

    .filter-input {
        border: 1.5px solid #e5e7eb;
        border-radius: 9px;
        padding: 7px 10px;
        font-size: 12px;
        color: #374151;
        outline: none;
        background: #fff;
        font-family: inherit;
        transition: border-color 150ms;
    }
    .search-input{
        padding-left: 30px;
    }

    .filter-input:focus { border-color: var(--brand-600); }

    .table-row {
        border-bottom: 1px solid #f8fafc;
        transition: background 100ms;
    }

    .table-row:hover { background: #fafbfc; }
    .table-row:last-child { border-bottom: none; }

    .emp-avatar {
        width: 34px; height: 34px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 800;
        flex-shrink: 0;
        color: #fff;
    }
</style>
@endpush

@section('content')

@php
    $statusColors = \App\Models\Hrm\Employee::STATUS_COLORS;
    $statusLabels = \App\Models\Hrm\Employee::STATUS_LABELS;
    $typeLabels   = \App\Models\Hrm\Employee::TYPE_LABELS;

    $totalCount      = $employees->total();
    $activeCount     = $employees->where('status', 'active')->count();
    $onNoticeCount   = $employees->where('status', 'on_notice')->count();
    $terminatedCount = $employees->where('status', 'terminated')->count();
@endphp

<div class="pb-10" x-data="{ confirmDelete(id, name) {
    BizAlert.confirm('Delete Employee', `Are you sure you want to delete &quot;${name}&quot;?`, 'Delete').then(async (result) => {
        if (!result.isConfirmed) return;
        try {
            const res = await fetch(`{{ url('admin/hrm/employees') }}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=&quot;csrf-token&quot;]').content,
                    'Accept': 'application/json',
                },
            });
            const data = await res.json();
            if (!res.ok) {
                BizAlert.toast(data.message || 'Cannot delete', 'error');
                return;
            }
            BizAlert.toast(data.message, 'success');
            setTimeout(() => window.location.reload(), 600);
        } catch (e) {
            BizAlert.toast('Network error. Please try again.', 'error');
        }
    });
} }">

    {{-- ════════ STATS BAR ════════ --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">

        <a href="{{ route('admin.hrm.employees.index') }}" class="stat-card block">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total</p>
            <p class="text-2xl font-black text-gray-900">{{ number_format($totalCount) }}</p>
        </a>

        <a href="{{ route('admin.hrm.employees.index', ['status' => 'active']) }}" class="stat-card block">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Active</p>
            <p class="text-2xl font-black text-green-600">{{ number_format($activeCount) }}</p>
        </a>

        <a href="{{ route('admin.hrm.employees.index', ['status' => 'on_notice']) }}" class="stat-card block">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">On Notice</p>
            <p class="text-2xl font-black text-amber-600">{{ number_format($onNoticeCount) }}</p>
        </a>

        <a href="{{ route('admin.hrm.employees.index', ['status' => 'terminated']) }}" class="stat-card block">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Terminated</p>
            <p class="text-2xl font-black text-red-600">{{ number_format($terminatedCount) }}</p>
        </a>

    </div>

    {{-- ════════ TOOLBAR ════════ --}}
    <div class="bg-white border border-gray-100 rounded-2xl px-4 py-3 mb-4">
        <form method="GET" action="{{ route('admin.hrm.employees.index') }}" id="filter-form">

            <div class="flex flex-col lg:flex-row lg:items-center gap-3 flex-wrap">

                {{-- Search ── --}}
                <div class="relative w-full lg:flex-1 lg:min-w-[180px]">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <input type="text" name="q" value="{{ request('q') }}"
                        placeholder="Search name, employee code..."
                        class="filter-input search-input pl-8 w-full">
                </div>

                <div class="grid grid-cols-2 lg:flex lg:flex-row gap-3 w-full lg:w-auto">
                    {{-- Department ── --}}
                    <x-custom-select
                        name="department_id"
                        placeholder="All Departments"
                        :options="$departments->pluck('name', 'id')"
                        selected="{{ request('department_id') }}"
                        onchange="this.form.submit()"
                    />

                    {{-- Status ── --}}
                    <div class="w-full col-span-2 sm:col-span-1 lg:col-span-auto">
                        <x-custom-select
                            name="status"
                            placeholder="All Status"
                            :options="$statusLabels"
                            selected="{{ request('status') }}"
                            onchange="this.form.submit()"
                        />
                    </div>
                </div>

                {{-- Clear filters ── --}}
                @if(request()->hasAny(['q', 'department_id', 'store_id', 'status']))
                    <a href="{{ route('admin.hrm.employees.index') }}"
                        class="text-[11px] font-bold px-3 py-1.5 rounded-lg border border-red-200 text-red-500 hover:bg-red-50 transition-colors">
                        Clear
                    </a>
                @endif

                {{-- Search submit ── --}}
                <button type="submit"
                    class="text-[12px] font-bold px-4 py-2 rounded-lg text-white hover:opacity-90 transition-opacity"
                    style="background: var(--brand-600)">
                    Search
                </button>

                {{-- Add Employee ── --}}
                @if($canAddMore)
                    @if(has_permission('employees.create'))
                    <a href="{{ route('admin.hrm.employees.create') }}"
                        class="w-full lg:w-auto lg:ml-auto flex justify-center items-center gap-1.5 text-[12px] font-bold px-4 py-2 rounded-lg text-white hover:opacity-90 transition-opacity"
                        style="background: var(--brand-600)">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Add Employee
                    </a>
                    @endif
                @else
                    <div class="ml-auto flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-red-50 text-red-600 text-xs font-bold border border-red-100">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            Employee Limit Reached
                        </span>
                        <button type="button" disabled
                            class="inline-flex items-center gap-1.5 text-[12px] font-bold px-4 py-2 rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed"
                            title="Upgrade your plan to add more employees.">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Add Employee
                        </button>
                    </div>
                @endif

            </div>
        </form>
    </div>

    {{-- ════════ TABLE ════════ --}}
    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">

        {{-- Table header ── --}}
        <div class="px-5 py-3 border-b border-gray-50 flex items-center justify-between flex-wrap gap-2">
            <p class="text-[12px] font-bold text-gray-500">
                {{ $employees->total() }} employee{{ $employees->total() !== 1 ? 's' : '' }}
                @if(request()->hasAny(['q', 'department_id', 'store_id', 'status']))
                    <span class="text-gray-400 font-medium">— filtered</span>
                @endif
            </p>
        </div>

        @if($employees->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-14 h-14 bg-gray-100 rounded-2xl flex items-center justify-center mb-3">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <p class="font-semibold text-gray-500 mb-1">No employees found</p>
                <p class="text-sm text-gray-400 mb-4">
                    @if(request()->hasAny(['q', 'department_id', 'store_id', 'status']))
                        Try adjusting your filters
                    @else
                        Start adding employees to your organization
                    @endif
                </p>
                <a href="{{ route('admin.hrm.employees.create') }}"
                    class="text-sm font-bold px-4 py-2 rounded-xl text-white"
                    style="background: var(--brand-600)">
                    Add First Employee
                </a>
            </div>
        @else

            {{-- 🖥️ DESKTOP VIEW (TABLE) ── --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full min-w-[900px] whitespace-nowrap">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="px-5 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider w-[50px]">#</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider w-[260px]">Employee</th>
                            <th class="px-3 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">Department</th>
                            <th class="px-3 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">Designation</th>
                            <th class="px-3 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">Store</th>
                            <th class="px-3 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">Type</th>
                            <th class="px-3 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-3 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">Joined</th>
                            <th class="px-4 py-3 text-right text-[10px] font-black text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $employee)
                            @php
                                $empName     = $employee->user->name ?? 'Unknown';
                                $initials    = strtoupper(substr($empName, 0, 1));
                                $avatarColors = ['#3b82f6','#8b5cf6','#10b981','#f59e0b','#ef4444','#06b6d4'];
                                $avatarBg    = $avatarColors[crc32($empName) % count($avatarColors)];
                                $sColor      = $statusColors[$employee->status] ?? $statusColors['inactive'];
                            @endphp
                            <tr class="table-row">

                                {{-- # ── --}}
                                <td class="px-5 py-3 text-[12px] font-bold text-gray-400">
                                    {{ $employees->firstItem() + $loop->index }}
                                </td>

                                {{-- Employee ── --}}
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="emp-avatar" style="background: {{ $avatarBg }}">
                                            {{ $initials }}
                                        </div>
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.hrm.employees.show', $employee->id) }}"
                                                class="text-[13px] font-bold text-gray-900 hover:underline block truncate max-w-[160px]">
                                                {{ $empName }}
                                            </a>
                                            <p class="text-[11px] text-gray-400 font-medium truncate">
                                                {{ $employee->employee_code ?? '—' }}
                                                @if($employee->user->email ?? null)
                                                    &middot; {{ $employee->user->email }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Department ── --}}
                                <td class="px-3 py-3">
                                    <span class="text-[12px] text-gray-500 font-medium">
                                        {{ $employee->department->name ?? '—' }}
                                    </span>
                                </td>

                                {{-- Designation ── --}}
                                <td class="px-3 py-3">
                                    <span class="text-[12px] text-gray-500 font-medium">
                                        {{ $employee->designation->name ?? '—' }}
                                    </span>
                                </td>

                                {{-- Store ── --}}
                                <td class="px-3 py-3">
                                    <span class="text-[12px] text-gray-500 font-medium">
                                        {{ $employee->store->name ?? '—' }}
                                    </span>
                                </td>

                                {{-- Type ── --}}
                                <td class="px-3 py-3">
                                    <span class="type-badge">
                                        {{ $typeLabels[$employee->employment_type] ?? ucfirst($employee->employment_type ?? '—') }}
                                    </span>
                                </td>

                                {{-- Status ── --}}
                                <td class="px-3 py-3">
                                    <span class="status-badge"
                                        style="background: {{ $sColor['bg'] }}; color: {{ $sColor['text'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                            style="background: {{ $sColor['dot'] }}"></span>
                                        {{ $statusLabels[$employee->status] ?? ucfirst($employee->status) }}
                                    </span>
                                </td>

                                {{-- Joined ── --}}
                                <td class="px-3 py-3">
                                    @if($employee->date_of_joining)
                                        <span class="text-[12px] text-gray-500 font-medium">
                                            {{ $employee->date_of_joining->format('d M Y') }}
                                        </span>
                                    @else
                                        <span class="text-gray-300 text-sm">—</span>
                                    @endif
                                </td>

                                {{-- Actions ── --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1 justify-end">

                                        {{-- View ── --}}
                                        @if(has_permission('employees.view'))
                                            <a href="{{ route('admin.hrm.employees.show', $employee->id) }}"
                                                class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                                title="View">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                            </a>
                                        @endif

                                        {{-- Edit ── --}}
                                        @if(has_permission('employees.update'))
                                            <a href="{{ route('admin.hrm.employees.edit', $employee->id) }}"
                                                class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                                title="Edit">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </a>
                                        @endif

                                        {{-- Delete ── --}}
                                        @if(has_permission('employees.delete'))
                                            <button
                                                @click="confirmDelete({{ $employee->id }}, '{{ addslashes($empName) }}')"
                                                class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                                title="Delete">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                            </button>
                                        @endif

                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- 📱 MOBILE VIEW (CARDS) ── --}}
            <div class="md:hidden divide-y divide-gray-50 border-t border-gray-50 bg-white">
                @foreach($employees as $employee)
                    @php
                        $empName     = $employee->user->name ?? 'Unknown';
                        $initials    = strtoupper(substr($empName, 0, 1));
                        $avatarColors = ['#3b82f6','#8b5cf6','#10b981','#f59e0b','#ef4444','#06b6d4'];
                        $avatarBg    = $avatarColors[crc32($empName) % count($avatarColors)];
                        $sColor      = $statusColors[$employee->status] ?? $statusColors['inactive'];
                    @endphp
                    <div class="p-4 hover:bg-gray-50/50 transition-colors flex flex-col gap-3">
                        
                        {{-- Header: Avatar, Name & Status --}}
                        <div class="flex justify-between items-start gap-2">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-full text-white flex items-center justify-center text-sm font-bold shrink-0" style="background: {{ $avatarBg }}">
                                    {{ $initials }}
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('admin.hrm.employees.show', $employee->id) }}" class="font-bold text-[14px] text-gray-900 hover:underline truncate block">
                                        {{ $empName }}
                                    </a>
                                    <p class="text-[11px] text-gray-500 mt-0.5 truncate">
                                        {{ $employee->employee_code ?? '—' }} 
                                        @if($employee->user->email ?? null)
                                            &middot; {{ $employee->user->email }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="shrink-0">
                                <span class="status-badge px-2 py-0.5 text-[9px] flex items-center gap-1" style="background: {{ $sColor['bg'] }}; color: {{ $sColor['text'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full" style="background: {{ $sColor['dot'] }}"></span>
                                    {{ $statusLabels[$employee->status] ?? ucfirst($employee->status) }}
                                </span>
                            </div>
                        </div>

                        {{-- Details: Grid for Roles & Dates --}}
                        <div class="flex flex-col gap-2 bg-gray-50/80 px-3 py-2.5 rounded-lg border border-gray-100">
                            <div class="grid grid-cols-2 gap-y-2 gap-x-3 mb-1">
                                <div>
                                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Department</p>
                                    <p class="text-[12px] font-semibold text-gray-700 truncate">{{ $employee->department->name ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Designation</p>
                                    <p class="text-[12px] font-semibold text-gray-700 truncate">{{ $employee->designation->name ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Store</p>
                                    <p class="text-[12px] font-semibold text-gray-700 truncate">{{ $employee->store->name ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Joined</p>
                                    <p class="text-[12px] font-semibold text-gray-700 truncate">
                                        {{ $employee->date_of_joining?->format('d M Y') ?? '—' }}
                                    </p>
                                </div>
                            </div>
                            <div class="pt-2 border-t border-gray-100/50">
                                <span class="type-badge !text-[9px] !px-1.5 !py-0.5">
                                    {{ $typeLabels[$employee->employment_type] ?? ucfirst($employee->employment_type ?? '—') }}
                                </span>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-end gap-2 pt-1 border-t border-gray-50 mt-1">
                            @if(has_permission('employees.view'))
                                <a href="{{ route('admin.hrm.employees.show', $employee->id) }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:text-blue-600 hover:bg-blue-50 transition-colors" title="View">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                            @endif

                            @if(has_permission('employees.update'))
                                <a href="{{ route('admin.hrm.employees.edit', $employee->id) }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-blue-200 text-blue-500 hover:bg-blue-50 transition-colors" title="Edit">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                            @endif

                            @if(has_permission('employees.delete'))
                                <button @click="confirmDelete({{ $employee->id }}, '{{ addslashes($empName) }}')" class="w-8 h-8 flex items-center justify-center rounded-lg border border-red-200 text-red-500 hover:bg-red-50 transition-colors" title="Delete">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination ── --}}
            @if($employees->hasPages())
                <div class="px-5 py-4 border-t border-gray-50 flex items-center justify-between flex-wrap gap-3">
                    <p class="text-[12px] text-gray-400 font-medium">
                        Showing {{ $employees->firstItem() }}–{{ $employees->lastItem() }} of {{ $employees->total() }}
                    </p>
                    <div class="flex items-center gap-1">
                        {{-- Prev ── --}}
                        @if($employees->onFirstPage())
                            <span class="px-3 py-1.5 rounded-lg text-[12px] font-bold text-gray-300 cursor-not-allowed">← Prev</span>
                        @else
                            <a href="{{ $employees->previousPageUrl() }}"
                                class="px-3 py-1.5 rounded-lg text-[12px] font-bold text-gray-600 hover:bg-gray-100 transition-colors">← Prev</a>
                        @endif

                        {{-- Pages ── --}}
                        @foreach($employees->getUrlRange(max(1, $employees->currentPage()-2), min($employees->lastPage(), $employees->currentPage()+2)) as $page => $url)
                            <a href="{{ $url }}"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-[12px] font-bold transition-colors
                                {{ $page == $employees->currentPage() ? 'text-white' : 'text-gray-600 hover:bg-gray-100' }}"
                                style="{{ $page == $employees->currentPage() ? 'background: var(--brand-600)' : '' }}">
                                {{ $page }}
                            </a>
                        @endforeach

                        {{-- Next ── --}}
                        @if($employees->hasMorePages())
                            <a href="{{ $employees->nextPageUrl() }}"
                                class="px-3 py-1.5 rounded-lg text-[12px] font-bold text-gray-600 hover:bg-gray-100 transition-colors">Next →</a>
                        @else
                            <span class="px-3 py-1.5 rounded-lg text-[12px] font-bold text-gray-300 cursor-not-allowed">Next →</span>
                        @endif
                    </div>
                </div>
            @endif

        @endif
    </div>

</div>
@endsection
