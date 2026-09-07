@extends ('layouts.admin')

@section ('title', 'My Salary Slips')

@section ('header-title')
    <div>
        <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">My Salary Slips</h1>
        <p class="mt-0.5 text-xs font-medium text-gray-400">View and download your salary slips</p>
    </div>
@endsection

@push ('styles')
    <style>
        .field-input {
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 13px;
            outline: none;
            background: #fff;
            font-family: inherit;
            transition: border-color 150ms ease;
        }
        .field-input:focus {
            border-color: var(--brand-600);
        }
    </style>
@endpush

@section ('content')
    <div class="space-y-6 pb-10">
        {{-- ── TIMELINE CONTAINER SLIPS DECK ── --}}
        <div class="space-y-4">
            @forelse ($slips as $slip)
                @php 
            $sc = \App\Models\Hrm\SalarySlip::STATUS_COLORS[$slip->status] ?? ['bg' => '#f3f4f6', 'text' => '#374151', 'dot' => '#9ca3af']; 
        @endphp
                <div
                    class="group flex flex-col justify-between gap-5 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition-all hover:border-gray-200 md:flex-row md:items-center"
                >
                    {{-- Left Block: Calendar Node & Meta description --}}
                    <div class="flex items-center gap-4">
                        {{-- Dynamic Block Calendar Design --}}
                        <div
                            class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-xl border border-gray-100 transition-transform group-hover:scale-105"
                            style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%)"
                        >
                            <div
                                class="w-full rounded-t-xl py-0.5 text-center text-[9px] font-black tracking-wider text-white uppercase"
                                style="background: var(--brand-600)"
                            >
                                {{ date('M', mktime(0,0,0,$slip->month,1)) }}
                            </div>
                            <div class="flex flex-1 items-center justify-center text-sm font-black text-gray-800">
                                {{ $slip->year }}
                            </div>
                        </div>
                        <div class="min-w-0">
                            <h4
                                class="text-[15px] font-black text-gray-900 transition-colors group-hover:text-gray-800"
                            >
                                {{ $slip->month_name }} {{ $slip->year }} Pay Cycle
                            </h4>
                            <p class="mt-1 flex items-center gap-1.5 text-xs font-medium text-gray-400">
                                <i data-lucide="hash" class="h-3 w-3"></i> {{ $slip->slip_number }}
                            </p>
                        </div>
                    </div>
                    {{-- Middle Block: Operational Attendance Summary Metrics --}}
                    <div
                        class="grid grid-cols-3 gap-2 rounded-xl border border-dashed border-gray-100 bg-gray-50/60 p-3 sm:flex sm:items-center sm:gap-8 md:border-0 md:bg-transparent md:p-0"
                    >
                        <div class="text-center sm:text-left">
                            <p class="mb-0.5 text-[10px] font-bold tracking-wider text-gray-400 uppercase">Present</p>
                            <p class="flex items-center justify-center gap-1 text-sm font-extrabold text-emerald-600 sm:justify-start">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>{{ $slip->present_days }}
                                <span class="text-[10px] font-medium text-gray-400">days</span>
                            </p>
                        </div>
                        <div class="text-center sm:text-left">
                            <p class="mb-0.5 text-[10px] font-bold tracking-wider text-gray-400 uppercase">Absent</p>
                            <p class="flex items-center justify-center gap-1 text-sm font-extrabold text-red-500 sm:justify-start">
                                <span class="h-1.5 w-1.5 rounded-full bg-red-400"></span>{{ $slip->absent_days }}
                                <span class="text-[10px] font-medium text-gray-400">days</span>
                            </p>
                        </div>
                        <div class="text-center sm:text-left">
                            <p class="mb-0.5 text-[10px] font-bold tracking-wider text-gray-400 uppercase">Working Cycle</p>
                            <p class="flex items-center justify-center gap-1 text-sm font-extrabold text-gray-700 sm:justify-start">
                                <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>{{ $slip->working_days }}
                                <span class="text-[10px] font-medium text-gray-400">total</span>
                            </p>
                        </div>
                    </div>
                    {{-- Right Block: Financial Status & Document Exports --}}
                    <div
                        class="flex items-center justify-between gap-6 border-t border-gray-50 pt-3 md:justify-end md:border-0 md:pt-0"
                    >
                        <div class="md:text-right">
                            <p class="mb-0.5 text-[10px] font-bold tracking-wider text-gray-400 uppercase">Take-home Salary</p>
                            <p class="text-xl font-black tracking-tight text-gray-900">₹{{ number_format($slip->net_salary, 0) }}</p>
                        </div>
                        <div class="flex min-w-[110px] flex-col items-end gap-2">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1 text-[10px] font-black tracking-wider uppercase"
                                style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }}; border-color: color-mix(in srgb, {{ $sc['text'] }} 15%, transparent)"
                            >
                                <span
                                    class="h-1.5 w-1.5 animate-pulse rounded-full"
                                    style="background: {{ $sc['dot'] }}"
                                ></span>
                                {{ $slip->status_label ?? $slip->status }}
                            </span>
                            @if (in_array($slip->status, ['approved', 'paid']))
                                <a
                                    href="{{ route('admin.hrm.my-salary-slips.pdf', $slip) }}"
                                    target="_blank"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-blue-100 bg-blue-50/60 px-3 py-2 text-xs font-bold text-blue-600 shadow-sm transition-all hover:bg-blue-100/80 hover:text-blue-800"
                                >
                                    <i data-lucide="download-cloud" class="h-3.5 w-3.5"></i> Export PDF
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-5 py-16 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-50">
                        <i data-lucide="banknote" class="h-6 w-6 text-gray-300"></i>
                    </div>
                    <p class="text-[14px] font-bold text-gray-400">No salary slips yet</p>
                    <p class="mt-1 text-[12px] text-gray-300">Your payslips will appear here once generated by HR.</p>
                </div>
            @endforelse

            @if ($slips->hasPages())
                <div class="border-t border-gray-50 px-5 py-3">{{ $slips->links() }}</div>
            @endif
        </div>

        {{-- ── 🌟 PREMIUM EXECUTIVE STATS PANEL ── --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                >
                    <i data-lucide="wallet" class="h-6 w-6"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold tracking-wider text-gray-400 uppercase">Total Earnings Logged</p>
                    <p class="mt-0.5 text-xl font-black text-gray-900">₹{{ number_format($slips->where('status', 'paid')->sum('net_salary'), 0) }}</p>
                </div>
            </div>
            <div class="flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <i data-lucide="file-check" class="h-6 w-6"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold tracking-wider text-gray-400 uppercase">Processed Slips</p>
                    <p class="mt-0.5 text-xl font-black text-gray-900">{{ $slips->whereIn('status', ['approved', 'paid'])->count() }} <span class="text-xs font-medium text-gray-400">issued</span></p>
                </div>
            </div>
            <div class="flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <i data-lucide="calendar" class="h-6 w-6"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold tracking-wider text-gray-400 uppercase">Average Attendance</p>
                    <p class="mt-0.5 text-xl font-black text-gray-900">
                        {{ $slips->count() > 0 ? round($slips->avg('present_days'), 1) : '0' }}
                        <span class="text-xs font-medium text-gray-400">days/mo</span>
                    </p>
                </div>
            </div>
        </div>
        {{-- ── MODERN FILTER CONTROL DECK ── --}}
        <div
            class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-gray-100 bg-white px-5 py-4 shadow-sm"
        >
            <form method="GET" class="flex w-full flex-wrap items-center gap-3 sm:w-auto sm:flex-nowrap">
                <div class="relative w-full min-w-[140px] sm:w-auto">
                    <select
                        name="year"
                        class="field-input w-full appearance-none bg-right bg-no-repeat pr-10"
                        style="background-position: right 12px center"
                    >
                        <option value="">All Years</option>
                        @foreach ($years as $yr)
                            <option value="{{ $yr }}" {{ request('year') == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="relative w-full min-w-[140px] sm:w-auto">
                    <select
                        name="status"
                        class="field-input w-full appearance-none bg-right bg-no-repeat pr-10"
                        style="background-position: right 12px center"
                    >
                        <option value="">All Status</option>
                        @foreach (\App\Models\Hrm\SalarySlip::STATUS_LABELS as $val => $label)
                            <option value="{{ $val }}" {{ request('status') == $val ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex w-full items-center gap-2 sm:w-auto">
                    <button
                        type="submit"
                        class="flex-1 cursor-pointer rounded-xl px-5 py-2.5 text-[12px] font-bold text-white shadow-sm transition-opacity hover:opacity-95 sm:flex-none"
                        style="background: var(--brand-600)"
                    >
                        Apply Filters
                    </button>
                    @if (request()->hasAny(['year', 'status']))
                        <a
                            href="{{ route('admin.hrm.my-salary-slips.index') }}"
                            class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-2.5 text-[12px] font-bold text-gray-500 transition-colors hover:bg-gray-100"
                        >
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

@endsection

@push ('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            if (window.lucide) lucide.createIcons();
        });
    </script>
@endpush
