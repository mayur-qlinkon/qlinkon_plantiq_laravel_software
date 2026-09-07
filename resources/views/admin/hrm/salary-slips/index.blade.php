@extends ('layouts.admin')

@section('title', 'Salary Slips')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Salary Slips</h1>
        {{-- <p class="text-xs text-gray-400 font-medium mt-0.5">Manage employee payroll and salary disbursements</p> --}}
    </div>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        .field-label {
            font-size: 11px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 6px;
        }

        .field-input {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 30px;
            font-size: 13.5px;
            outline: none;
            transition:
                border-color 150ms ease,
                box-shadow 150ms ease;
            font-family: inherit;
            background: #fff;
        }

        .field-input:focus {
            border-color: var(--brand-600);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-600) 10%, transparent);
        }

        .field-error {
            font-size: 11px;
            color: #dc2626;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .table-row {
            border-bottom: 1px solid #f8fafc;
            transition: background 100ms;
        }

        .table-row td {
            white-space: nowrap;
            vertical-align: middle;
        }

        .table-row:hover {
            background: #fafbfc;
        }

        .table-row:last-child {
            border-bottom: none;
        }

        .stat-card {
            background: #fff;
            border: 1.5px solid #f1f5f9;
            border-radius: 14px;
            padding: 14px 16px;
            transition:
                box-shadow 150ms,
                border-color 150ms;
        }

        .stat-card:hover {
            border-color: #e2e8f0;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }
    </style>
@endpush

@section('content')
    @php
        $months = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];
        $currentYear = now()->year;
    @endphp

    <div class="pb-10" x-data="salarySlipsPage()">
        {{-- Stats --}}
        <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="stat-card">
                <p class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase">Total Slips</p>
                <p class="text-2xl font-black text-gray-900">{{ $slips->total() }}</p>
            </div>
            <div class="stat-card">
                <p class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase">Draft</p>
                <p class="text-2xl font-black text-gray-500">{{ $stats['draft'] ?? 0 }}</p>
            </div>
            <div class="stat-card">
                <p class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase">Approved</p>
                <p class="text-2xl font-black text-amber-600">{{ $stats['approved'] ?? 0 }}</p>
            </div>
            <div class="stat-card">
                <p class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase">Paid</p>
                <p class="text-2xl font-black text-green-600">{{ $stats['paid'] ?? 0 }}</p>
            </div>
        </div>

        {{-- Filter Bar --}}
        <form method="GET" action="{{ route('admin.hrm.salary-slips.index') }}"
            class="mb-4 rounded-2xl border border-gray-100 bg-white px-4 py-3">
            <div class="flex flex-col flex-wrap items-stretch gap-3 sm:flex-row sm:items-center">
                <div class="w-full sm:w-[130px]">
                    <x-custom-select name="month" placeholder="All Months" :options="$months"
                        selected="{{ request('month') }}" onchange="this.form.submit()" />
                </div>

                @php
                    // Five years back covers historical payroll; one year ahead
                    // allows generating slips in advance.
                    $yearOptions = [];
                    for ($y = $currentYear + 1; $y >= $currentYear - 5; $y--) {
                        $yearOptions[$y] = (string) $y;
                    }
                @endphp
                <div class="w-full sm:w-[110px]">
                    <x-custom-select name="year" placeholder="All Years" :options="$yearOptions"
                        selected="{{ request('year') }}" onchange="this.form.submit()" />
                </div>

                <div class="w-full sm:w-[140px]">
                    <x-custom-select name="status" placeholder="All Statuses" :options="\App\Models\Hrm\SalarySlip::STATUS_LABELS"
                        selected="{{ request('status') }}" onchange="this.form.submit()" />
                </div>

                <div class="relative w-full sm:min-w-[180px] sm:flex-1" @click.away="employeeSearchOpen = false">
                    <input type="hidden" name="employee_id" x-model="selectedEmployeeId" />
                    <input type="text" x-model="employeeSearchQuery" @focus="employeeSearchOpen = true"
                        @input="employeeSearchOpen = true" @keydown.enter.prevent="$el.closest('form').submit()"
                        placeholder="Search & select employee..." autocomplete="off" class="field-input w-full" />

                    <div x-show="employeeSearchOpen" x-cloak
                        class="absolute left-0 z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-gray-100 bg-white shadow-lg">
                        <template x-for="emp in filteredEmployees" :key="emp.id">
                            <div @click="
                                    selectedEmployeeId = emp.id;
                                    employeeSearchQuery = emp.name;
                                    employeeSearchOpen = false;
                                    $nextTick(() => {
                                        $el.closest('form').submit();
                                    });
                                "
                                class="flex cursor-pointer items-center justify-between border-b border-gray-50 px-4 py-2.5 transition-colors last:border-0 hover:bg-gray-50">
                                <span class="text-[13px] font-bold text-gray-800" x-text="emp.name"></span>
                                <span class="rounded-md bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-500"
                                    x-text="emp.code"></span>
                            </div>
                        </template>

                        <div x-show="filteredEmployees.length === 0" class="px-4 py-6 text-center">
                            <i data-lucide="user-x" class="mx-auto mb-2 h-8 w-8 text-gray-300"></i>
                            <p class="text-xs font-semibold text-gray-500">No employees found</p>
                        </div>
                    </div>
                </div>

                <div class="mt-2 flex w-full flex-wrap items-center gap-2 sm:mt-0 sm:w-auto">


                    <a href="{{ route('admin.hrm.salary-slips.index') }}"
                        class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-gray-100 px-4 py-2 text-[12px] font-bold text-gray-600 transition-colors hover:bg-gray-200 sm:flex-none">
                        <i data-lucide="x" class="h-3.5 w-3.5"></i> Clear
                    </a>

                    @if (has_permission('salary_slips.generate'))
                        <button type="button" @click="generateModalOpen = true"
                            class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg px-4 py-2 text-[12px] font-bold text-white transition-opacity hover:opacity-90 sm:ml-auto sm:w-auto"
                            style="background: #10b981">
                            <i data-lucide="file-plus" class="h-3.5 w-3.5"></i> Generate Slips
                        </button>
                    @endif
                </div>
            </div>
        </form>

        {{-- Table --}}
        <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white">
            {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
            <div class="hidden w-full overflow-x-auto pb-2 md:block">
                <table class="w-full min-w-[1000px]">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th
                                class="w-[50px] px-5 py-3 text-left text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                #
                            </th>
                            <th class="px-5 py-3 text-left text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                Employee
                            </th>
                            <th class="px-3 py-3 text-left text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                Month / Year
                            </th>
                            <th class="px-3 py-3 text-right text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                Gross
                            </th>
                            <th class="px-3 py-3 text-right text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                Deductions
                            </th>
                            <th class="px-3 py-3 text-right text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                Net Salary
                            </th>
                            <th class="px-3 py-3 text-center text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                Status
                            </th>
                            <th class="px-4 py-3 text-right text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($slips as $slip)
                            @php
                                $sc = \App\Models\Hrm\SalarySlip::STATUS_COLORS[$slip->status] ?? [
                                    'bg' => '#f3f4f6',
                                    'text' => '#374151',
                                ];
                                $empName = $slip->employee->user?->name ?? '—';
                            @endphp
                            <tr class="table-row">
                                <td class="px-5 py-3 text-[12px] font-bold text-gray-400">
                                    {{ $slips->firstItem() + $loop->index }}
                                </td>
                                <td class="px-5 py-3">
                                    <div>
                                        <p class="text-[13px] font-bold text-gray-800">{{ $empName }}</p>
                                        <p class="mt-0.5 text-[11px] text-gray-400">
                                            <span class="font-bold">{{ $slip->employee->employee_code }}</span>
                                            @if ($slip->employee->department)
                                                &nbsp;·&nbsp;{{ $slip->employee->department->name }}
                                            @endif
                                        </p>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-[13px] text-gray-700">
                                    {{ $months[$slip->month] ?? $slip->month }} {{ $slip->year }}
                                </td>
                                <td class="px-3 py-3 text-right text-[13px] text-gray-700">
                                    ₹{{ number_format($slip->gross_salary, 2) }}
                                </td>
                                <td class="px-3 py-3 text-right text-[13px] text-red-600">
                                    ₹{{ number_format($slip->total_deductions, 2) }}
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <span
                                        class="inline-block rounded-lg border border-green-200 bg-green-50 px-2.5 py-1 text-[13px] font-black text-green-700">
                                        ₹{{ number_format($slip->net_salary, 2) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span
                                        class="inline-flex items-center rounded-md px-2.5 py-1 text-[10px] font-extrabold tracking-wider uppercase"
                                        style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }}">
                                        {{ \App\Models\Hrm\SalarySlip::STATUS_LABELS[$slip->status] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        {{-- View --}}
                                        @if (has_permission('salary_slips.view'))
                                            <a href="{{ route('admin.hrm.salary-slips.show', $slip) }}"
                                                class="flex h-[30px] w-[30px] items-center justify-center rounded-lg bg-blue-50 text-blue-500 transition-colors hover:bg-blue-100 hover:text-blue-700"
                                                title="View">
                                                <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                            </a>
                                        @endif

                                        {{-- Approve (only for generated) --}}
                                        @if ($slip->status === 'generated' && has_permission('salary_slips.approve'))
                                            <button @click="approveSlip({{ $slip->id }})"
                                                class="flex h-[30px] w-[30px] items-center justify-center rounded-lg bg-amber-50 text-amber-600 transition-colors hover:bg-amber-100 hover:text-amber-800"
                                                title="Approve">
                                                <i data-lucide="check-circle" class="h-3.5 w-3.5"></i>
                                            </button>
                                        @endif

                                        {{-- Mark Paid (only for approved) --}}
                                        @if ($slip->status === 'approved' && has_permission('salary_slips.mark_paid'))
                                            <button @click="openPayModal({{ $slip->id }})"
                                                class="flex h-[30px] w-[30px] items-center justify-center rounded-lg bg-green-50 text-green-600 transition-colors hover:bg-green-100 hover:text-green-800"
                                                title="Mark Paid">
                                                <i data-lucide="banknote" class="h-3.5 w-3.5"></i>
                                            </button>
                                        @endif

                                        {{-- Download PDF --}}
                                        @if (has_permission('salary_slips.download_pdf'))
                                            <a href="{{ route('admin.hrm.salary-slips.pdf', $slip) }}" target="_blank"
                                                class="flex h-[30px] w-[30px] items-center justify-center rounded-lg bg-gray-50 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700"
                                                title="Download PDF">
                                                <i data-lucide="download" class="h-3.5 w-3.5"></i>
                                            </a>
                                        @endif

                                        {{-- Hidden once a slip is approved, not merely refused on
                                             submit. The controller still blocks it, but offering a
                                             button that cannot work is the wrong way to say no. --}}
                                        @if (!in_array($slip->status, ['approved', 'paid'], true) && has_permission('salary_slips.delete'))
                                            <button @click="deleteSlip({{ $slip->id }}, $el.closest('tr'))"
                                                class="flex h-[30px] w-[30px] items-center justify-center rounded-lg bg-red-50 text-red-400 transition-colors hover:bg-red-100 hover:text-red-600"
                                                title="Delete">
                                                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="flex flex-col items-center justify-center py-20 text-center">
                                        <div
                                            class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100">
                                            <i data-lucide="file-text" class="h-7 w-7 text-gray-300"></i>
                                        </div>
                                        <p class="mb-1 font-semibold text-gray-500">No salary slips found</p>
                                        <p class="mb-4 text-sm text-gray-400">Generate slips for a pay period to get
                                            started</p>
                                        <button @click="generateModalOpen = true"
                                            class="rounded-xl px-4 py-2 text-sm font-bold text-white"
                                            style="background: var(--brand-600)">
                                            Generate Slips
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 📱 MOBILE VIEW (CARDS) --}}
            <div class="divide-y divide-gray-50 border-t border-gray-50 bg-white md:hidden">
                @forelse ($slips as $slip)
                    @php
                        $sc = \App\Models\Hrm\SalarySlip::STATUS_COLORS[$slip->status] ?? [
                            'bg' => '#f3f4f6',
                            'text' => '#374151',
                        ];
                        $empName = $slip->employee->user?->name ?? '—';
                    @endphp
                    <div class="flex flex-col gap-3 p-4 transition-colors hover:bg-gray-50/50">
                        {{-- Header: Employee & Net Salary --}}
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[14px] leading-tight font-bold text-gray-900">{{ $empName }}
                                </p>
                                <p class="mt-0.5 truncate text-[11px] text-gray-500">
                                    <span class="font-bold">{{ $slip->employee->employee_code }}</span>
                                    @if ($slip->employee->department)
                                        &nbsp;·&nbsp;{{ $slip->employee->department->name }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex shrink-0 flex-col items-end text-right">
                                <span
                                    class="text-[16px] font-black text-green-700">₹{{ number_format($slip->net_salary, 2) }}</span>
                                <span
                                    class="mt-0.5 text-[10px] font-bold tracking-wider text-gray-400 uppercase">{{ $months[$slip->month] ?? $slip->month }}
                                    {{ $slip->year }}</span>
                            </div>
                        </div>

                        {{-- Breakdown: Gross & Deductions --}}
                        <div
                            class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50/80 px-3 py-2.5">
                            <div>
                                <p class="mb-0.5 text-[9px] font-bold tracking-wider text-gray-400 uppercase">Gross Pay</p>
                                <p class="text-[12px] font-bold text-gray-700">
                                    ₹{{ number_format($slip->gross_salary, 2) }}</p>
                            </div>
                            <i data-lucide="minus" class="h-3 w-3 text-gray-300"></i>
                            <div class="text-right">
                                <p class="mb-0.5 text-[9px] font-bold tracking-wider text-gray-400 uppercase">Deductions
                                </p>
                                <p class="text-[12px] font-bold text-red-600">
                                    ₹{{ number_format($slip->total_deductions, 2) }}</p>
                            </div>
                        </div>

                        {{-- Footer: Status & Actions --}}
                        <div class="mt-1 flex items-center justify-between border-t border-gray-50 pt-1">
                            <span
                                class="inline-flex items-center rounded-md px-2.5 py-1 text-[10px] font-extrabold tracking-wider uppercase"
                                style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }}">
                                {{ \App\Models\Hrm\SalarySlip::STATUS_LABELS[$slip->status] }}
                            </span>

                            <div class="flex items-center justify-end gap-2">
                                @if (has_permission('salary_slips.view'))
                                    <a href="{{ route('admin.hrm.salary-slips.show', $slip) }}"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-600 transition-colors hover:bg-blue-100"
                                        title="View">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </a>
                                @endif

                                @if ($slip->status === 'generated' && has_permission('salary_slips.approve'))
                                    <button @click="approveSlip({{ $slip->id }})"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-600 transition-colors hover:bg-amber-100"
                                        title="Approve">
                                        <i data-lucide="check-circle" class="h-4 w-4"></i>
                                    </button>
                                @endif

                                @if ($slip->status === 'approved' && has_permission('salary_slips.mark_paid'))
                                    <button @click="openPayModal({{ $slip->id }})"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-green-200 bg-green-50 text-green-600 transition-colors hover:bg-green-100"
                                        title="Mark Paid">
                                        <i data-lucide="banknote" class="h-4 w-4"></i>
                                    </button>
                                @endif

                                @if (has_permission('salary_slips.download_pdf'))
                                    <a href="{{ route('admin.hrm.salary-slips.pdf', $slip) }}" target="_blank"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-600 transition-colors hover:bg-indigo-100"
                                        title="Download PDF">
                                        <i data-lucide="download" class="h-4 w-4"></i>
                                    </a>
                                @endif

                                @if (!in_array($slip->status, ['approved', 'paid'], true) && has_permission('salary_slips.delete'))
                                    <button @click="deleteSlip({{ $slip->id }}, $el.closest('.p-4'))"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-500 transition-colors hover:bg-red-100"
                                        title="Delete">
                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-white p-8 text-center">
                        <div class="flex flex-col items-center justify-center py-6 text-center">
                            <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100">
                                <i data-lucide="file-text" class="h-7 w-7 text-gray-300"></i>
                            </div>
                            <p class="mb-1 text-sm font-semibold text-gray-500">No salary slips found</p>
                            <p class="mb-4 text-xs text-gray-400">Generate slips for a pay period to get started</p>
                            <button @click="generateModalOpen = true"
                                class="rounded-xl px-4 py-2 text-xs font-bold text-white"
                                style="background: var(--brand-600)">
                                Generate Slips
                            </button>
                        </div>
                    </div>
                @endforelse
            </div>

            @if ($slips->hasPages())
                <div class="border-t border-gray-100 bg-gray-50/50 px-6 py-4">{{ $slips->links() }}</div>
            @endif
        </div>

        {{-- Generate Slips Modal --}}
        <div x-show="generateModalOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="mx-4 w-full max-w-md rounded-xl bg-white shadow-2xl"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100">
                <div class="flex items-center justify-between rounded-t-xl border-b border-gray-100 bg-gray-50 px-6 py-4">
                    <h3 class="text-sm font-black tracking-widest text-gray-800 uppercase">Generate Salary Slips</h3>
                    <button @click="generateModalOpen = false" class="text-gray-400 transition-colors hover:text-red-500">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <form @submit.prevent="submitGenerate()">
                    <div class="space-y-4 p-6">
                        <div class="grid grid-cols-2 gap-4">
                            {{-- Custom dropdowns matching the Employee search below: the
                                 browser will not style a native option list, so those two
                                 menus were the only unstyled surface left in this modal. --}}
                            <div>
                                <label class="field-label">Month <span class="text-red-400">*</span></label>
                                <div class="relative" x-data="{ open: false, months: @js($months) }" @click.away="open = false">
                                    <button type="button" @click="open = !open"
                                        class="field-input flex w-full items-center justify-between text-left">
                                        <span :class="genForm.month ? 'text-gray-800 font-semibold' : 'text-gray-400'"
                                            x-text="months[genForm.month] || 'Select Month'"></span>
                                        <i data-lucide="chevron-down"
                                            class="h-4 w-4 shrink-0 text-gray-400 transition-transform"
                                            :class="open && 'rotate-180'"></i>
                                    </button>

                                    <div x-show="open" x-cloak
                                        class="absolute left-0 z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-gray-100 bg-white p-1.5 shadow-lg">
                                        <template x-for="(name, num) in months" :key="num">
                                            <button type="button" @click="genForm.month = num; open = false"
                                                :class="genForm.month == num ?
                                                    'bg-[color-mix(in_srgb,var(--brand-600)_10%,transparent)] text-[var(--brand-600)] font-bold' :
                                                    'text-gray-700 hover:bg-gray-50'"
                                                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-[13px] transition-colors">
                                                <span x-text="name"></span>
                                                <i data-lucide="check" class="h-4 w-4 shrink-0 text-[var(--brand-600)]"
                                                    x-show="genForm.month == num"></i>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <p class="field-error" x-show="genErrors.month" x-text="genErrors.month"></p>
                            </div>
                            <div>
                                <label class="field-label">Year <span class="text-red-400">*</span></label>
                                <div class="relative" x-data="{ open: false, years: @js(range($currentYear + 1, $currentYear - 5)) }" @click.away="open = false">
                                    <button type="button" @click="open = !open"
                                        class="field-input flex w-full items-center justify-between text-left">
                                        <span :class="genForm.year ? 'text-gray-800 font-semibold' : 'text-gray-400'"
                                            x-text="genForm.year || 'Select Year'"></span>
                                        <i data-lucide="chevron-down"
                                            class="h-4 w-4 shrink-0 text-gray-400 transition-transform"
                                            :class="open && 'rotate-180'"></i>
                                    </button>

                                    <div x-show="open" x-cloak
                                        class="absolute left-0 z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-gray-100 bg-white p-1.5 shadow-lg">
                                        <template x-for="y in years" :key="y">
                                            <button type="button" @click="genForm.year = y; open = false"
                                                :class="genForm.year == y ?
                                                    'bg-[color-mix(in_srgb,var(--brand-600)_10%,transparent)] text-[var(--brand-600)] font-bold' :
                                                    'text-gray-700 hover:bg-gray-50'"
                                                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-[13px] transition-colors">
                                                <span x-text="y"></span>
                                                <i data-lucide="check" class="h-4 w-4 shrink-0 text-[var(--brand-600)]"
                                                    x-show="genForm.year == y"></i>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <p class="field-error" x-show="genErrors.year" x-text="genErrors.year"></p>
                            </div>
                        </div>

                        <div class="relative" @click.away="genEmployeeSearchOpen = false">
                            <label class="field-label">Employee
                                <span class="font-normal text-gray-400 normal-case">(leave blank for all)</span></label>
                            <input type="text" x-model="genEmployeeSearchQuery" @focus="genEmployeeSearchOpen = true"
                                @input="genEmployeeSearchOpen = true" placeholder="All Employees (Bulk)"
                                autocomplete="off" class="field-input w-full" />

                            <div x-show="genEmployeeSearchOpen" x-cloak
                                class="absolute left-0 z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-gray-100 bg-white shadow-lg">
                                <div @click="
                                        genForm.employee_id = '';
                                        genEmployeeSearchQuery = '';
                                        genEmployeeSearchOpen = false;
                                    "
                                    class="flex cursor-pointer items-center border-b border-gray-200 bg-gray-50 px-4 py-2.5 transition-colors hover:bg-gray-100">
                                    <span class="text-[13px] font-bold text-gray-900" style="color: var(--brand-600)">All
                                        Employees (Bulk)</span>
                                </div>

                                <template x-for="emp in filteredGenEmployees" :key="emp.id">
                                    <div @click="
                                            genForm.employee_id = emp.id;
                                            genEmployeeSearchQuery = emp.name;
                                            genEmployeeSearchOpen = false;
                                        "
                                        class="flex cursor-pointer items-center justify-between border-b border-gray-50 px-4 py-2.5 transition-colors last:border-0 hover:bg-gray-50">
                                        <span class="text-[13px] font-bold text-gray-800" x-text="emp.name"></span>
                                        <span
                                            class="rounded-md bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-500"
                                            x-text="emp.code"></span>
                                    </div>
                                </template>

                                <div x-show="filteredGenEmployees.length === 0" class="px-4 py-6 text-center">
                                    <i data-lucide="user-x" class="mx-auto mb-2 h-8 w-8 text-gray-300"></i>
                                    <p class="text-xs font-semibold text-gray-500">No employees found</p>
                                </div>
                            </div>
                        </div>

                        <div x-show="genMessage" x-cloak class="rounded-lg px-3 py-2 text-[12px] font-semibold"
                            :class="genSuccess ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'"
                            x-text="genMessage"></div>
                    </div>

                    <div class="flex justify-end gap-3 rounded-b-xl border-t border-gray-100 bg-gray-50 px-6 py-4">
                        <button type="button" @click="generateModalOpen = false"
                            class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-[13px] font-bold text-gray-600 transition-colors hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" :disabled="genSaving"
                            class="rounded-lg px-5 py-2 text-[13px] font-bold text-white transition-opacity hover:opacity-90 disabled:opacity-50"
                            style="background: #10b981">
                            <span x-show="!genSaving">Generate</span>
                            <span x-show="genSaving" class="flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                Generating...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Mark Paid Modal --}}
        <div x-show="payModalOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="w-full max-w-md rounded-xl bg-white shadow-2xl" @click.away="payModalOpen = false"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100">
                <div class="flex items-center justify-between rounded-t-xl border-b border-gray-100 bg-gray-50 px-6 py-4">
                    <h3 class="text-sm font-black tracking-widest text-gray-800 uppercase">Mark as Paid</h3>
                    <button @click="payModalOpen = false" class="text-gray-400 transition-colors hover:text-red-500">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <form @submit.prevent="submitPay()">
                    <div class="space-y-4 p-6">
                        <div>
                            <label class="field-label">Payment Mode <span class="text-red-400">*</span></label>
                            <select x-model="payForm.payment_mode" class="field-input" required>
                                <option value="">Select Mode</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                                <option value="upi">UPI</option>
                            </select>
                            <p class="field-error" x-show="payErrors.payment_mode" x-text="payErrors.payment_mode"></p>
                        </div>
                        <div>
                            <label class="field-label">Payment Reference</label>
                            <input type="text" x-model="payForm.payment_reference" class="field-input"
                                placeholder="Transaction ID, cheque no., etc." />
                            <p class="field-error" x-show="payErrors.payment_reference"
                                x-text="
                                    payErrors.payment_reference
                                ">
                            </p>
                        </div>
                        <div>
                            <label class="field-label">Payment Date <span class="text-red-400">*</span></label>
                            <input type="date" x-model="payForm.payment_date" class="field-input" required />
                            <p class="field-error" x-show="payErrors.payment_date" x-text="payErrors.payment_date"></p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 rounded-b-xl border-t border-gray-100 bg-gray-50 px-6 py-4">
                        <button type="button" @click="payModalOpen = false"
                            class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-[13px] font-bold text-gray-600 transition-colors hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" :disabled="paySaving"
                            class="rounded-lg px-5 py-2 text-[13px] font-bold text-white transition-opacity hover:opacity-90 disabled:opacity-50"
                            style="background: #10b981">
                            <span x-show="!paySaving">Mark Paid</span>
                            <span x-show="paySaving" class="flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                Saving...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        window.salarySlipsPage = function() {
            return {
                employees: @json ($employeeOptions),

                selectedEmployeeId: "{{ request('employee_id') }}",
                employeeSearchQuery: "",
                employeeSearchOpen: false,

                genEmployeeSearchQuery: "",
                genEmployeeSearchOpen: false,

                get filteredEmployees() {
                    if (this.employeeSearchQuery.trim() === "") return this.employees;
                    const q = this.employeeSearchQuery.toLowerCase();
                    return this.employees.filter((e) => e.name.toLowerCase().includes(q) || e.code.toLowerCase()
                        .includes(q));
                },

                get filteredGenEmployees() {
                    if (this.genEmployeeSearchQuery.trim() === "") return this.employees;
                    const q = this.genEmployeeSearchQuery.toLowerCase();
                    return this.employees.filter((e) => e.name.toLowerCase().includes(q) || e.code.toLowerCase()
                        .includes(q));
                },

                generateModalOpen: false,
                genSaving: false,
                genErrors: {},
                genMessage: "",
                genSuccess: false,
                genForm: {
                    month: "",
                    year: "",
                    employee_id: ""
                },

                payModalOpen: false,
                paySaving: false,
                payErrors: {},
                paySlipId: null,
                payForm: {
                    payment_mode: "",
                    payment_reference: "",
                    payment_date: ""
                },

                init() {
                    if (this.selectedEmployeeId) {
                        const emp = this.employees.find((e) => e.id == this.selectedEmployeeId);
                        if (emp) this.employeeSearchQuery = emp.name;
                    }

                    this.$watch("generateModalOpen", (val) => {
                        if (val) {
                            if (this.genForm.employee_id) {
                                const emp = this.employees.find((e) => e.id == this.genForm.employee_id);
                                this.genEmployeeSearchQuery = emp ? emp.name : "";
                            } else {
                                this.genEmployeeSearchQuery = "";
                            }
                        }
                    });

                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                },

                openPayModal(id) {
                    this.paySlipId = id;
                    this.payForm = {
                        payment_mode: "",
                        payment_reference: "",
                        payment_date: ""
                    };
                    this.payErrors = {};
                    this.payModalOpen = true;
                },

                async approveSlip(id) {
                    const result = await BizAlert.confirm(
                        "Approve Salary Slip",
                        "Are you sure you want to approve this salary slip?",
                        "Approve",
                    );
                    if (!result.isConfirmed) return;

                    try {
                        const resp = await fetch(`{{ url('admin/hrm/salary-slips') }}/${id}/approve`, {
                            method: "PATCH",
                            headers: {
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                Accept: "application/json",
                            },
                        });
                        const data = await resp.json();
                        if (!resp.ok) {
                            BizAlert.toast(data.message || "Error approving slip", "error");
                            return;
                        }
                        BizAlert.toast(data.message, "success");
                        setTimeout(() => window.location.reload(), 600);
                    } catch (e) {
                        BizAlert.toast("Network error. Please try again.", "error");
                    }
                },

                async submitGenerate() {
                    this.genSaving = true;
                    this.genErrors = {};
                    this.genMessage = "";

                    try {
                        const resp = await fetch("{{ route('admin.hrm.salary-slips.generate') }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                Accept: "application/json",
                            },
                            body: JSON.stringify(this.genForm),
                        });
                        const data = await resp.json();

                        if (!resp.ok) {
                            if (resp.status === 422 && data.errors) {
                                for (const [key, messages] of Object.entries(data.errors)) {
                                    this.genErrors[key] = messages[0];
                                }
                            } else {
                                this.genMessage = data.message || "Something went wrong";
                                this.genSuccess = false;
                            }
                            return;
                        }

                        this.genMessage = data.message;
                        this.genSuccess = true;
                        setTimeout(() => {
                            this.generateModalOpen = false;
                            window.location.reload();
                        }, 900);
                    } catch (e) {
                        this.genMessage = "Network error. Please try again.";
                        this.genSuccess = false;
                    } finally {
                        this.genSaving = false;
                    }
                },

                async deleteSlip(id, row) {
                    const result = await BizAlert.confirm(
                        "Delete Salary Slip",
                        "This will permanently delete the salary slip and all its line items. Continue?",
                    );
                    if (!result.isConfirmed) return;

                    try {
                        const resp = await fetch(`{{ url('admin/hrm/salary-slips') }}/${id}`, {
                            method: "DELETE",
                            headers: {
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                Accept: "application/json",
                            },
                        });
                        const data = await resp.json();
                        if (!resp.ok) {
                            BizAlert.toast(data.message || "Could not delete slip", "error");
                            return;
                        }
                        BizAlert.toast(data.message, "success");
                        row.style.transition = "opacity 300ms";
                        row.style.opacity = "0";
                        setTimeout(() => row.remove(), 300);
                    } catch (e) {
                        BizAlert.toast("Network error. Please try again.", "error");
                    }
                },

                async submitPay() {
                    this.paySaving = true;
                    this.payErrors = {};

                    try {
                        const resp = await fetch(`{{ url('admin/hrm/salary-slips') }}/${this.paySlipId}/pay`, {
                            method: "PATCH",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                Accept: "application/json",
                            },
                            body: JSON.stringify(this.payForm),
                        });
                        const data = await resp.json();

                        if (!resp.ok) {
                            if (resp.status === 422 && data.errors) {
                                for (const [key, messages] of Object.entries(data.errors)) {
                                    this.payErrors[key] = messages[0];
                                }
                            } else {
                                BizAlert.toast(data.message || "Something went wrong", "error");
                            }
                            return;
                        }

                        BizAlert.toast(data.message, "success");
                        this.payModalOpen = false;
                        setTimeout(() => window.location.reload(), 600);
                    } catch (e) {
                        BizAlert.toast("Network error. Please try again.", "error");
                    } finally {
                        this.paySaving = false;
                    }
                },
            };
        };
    </script>
@endpush
