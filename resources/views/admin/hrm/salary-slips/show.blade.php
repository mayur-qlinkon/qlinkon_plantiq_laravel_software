@extends('layouts.admin')

@section('title', 'Salary Slip')

@section('header-title')
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.hrm.salary-slips.index') }}"
            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div>
            <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Salary Slip</h1>
            <p class="text-xs text-gray-400 font-medium mt-0.5">{{ $salarySlip->employee->user?->name }} · {{ $salarySlip->slip_number }}</p>
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
    .comp-table { width: 100%; border-collapse: collapse; }
    .comp-table th { font-size: 10px; font-weight: 800; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.06em; padding: 8px 10px; border-bottom: 1px solid #f3f4f6; text-align: left; }
    .comp-table th:last-child { text-align: right; }
    .comp-table td { font-size: 13px; color: #374151; padding: 9px 10px; border-bottom: 1px solid #f9fafb; }
    .comp-table td:last-child { text-align: right; font-weight: 600; }
    .comp-table tr:last-child td { border-bottom: none; }
</style>
@endpush

@section('content')

@php
    $sc = \App\Models\Hrm\SalarySlip::STATUS_COLORS[$salarySlip->status] ?? ['bg' => '#f3f4f6', 'text' => '#374151'];
    $empName = $salarySlip->employee->user?->name ?? '?';
    $avatarColors = ['#3b82f6','#8b5cf6','#10b981','#f59e0b','#ef4444','#06b6d4'];
    $avatarBg = $avatarColors[abs(crc32($empName)) % 6];
    $months = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
    $isEditable = $salarySlip->isEditable();
    $canEdit = $isEditable && has_permission('salary_slips.edit');

    // Flat JSON payload for Alpine — kept in sort_order so UI mirrors DB row order.
    $itemsJson = $salarySlip->items->map(fn ($i) => [
        'id' => $i->id,
        'type' => $i->type,
        'component_name' => $i->component_name,
        'calculation_detail' => $i->calculation_detail,
        'amount' => (float) $i->amount,
    ])->values();
@endphp

<div class="pb-10 w-full"
    x-data="salarySlipShow({
        canEdit: {{ $canEdit ? 'true' : 'false' }},
        items: {{ $itemsJson->toJson() }},
        roundOff: {{ (float) ($salarySlip->round_off ?? 0) }},
    })">

    {{-- Employee Profile Card --}}
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
                                {{ $salarySlip->employee->employee_code }}
                                @if($salarySlip->employee->department)
                                    &nbsp;·&nbsp;{{ $salarySlip->employee->department->name }}
                                @endif
                                @if($salarySlip->employee->designation)
                                    &nbsp;·&nbsp;{{ $salarySlip->employee->designation->name }}
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-extrabold uppercase tracking-wider px-3 py-1.5 rounded-lg"
                                style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }}">
                                {{ \App\Models\Hrm\SalarySlip::STATUS_LABELS[$salarySlip->status] }}
                            </span>
                            <span class="text-[11px] font-bold text-gray-500 bg-gray-100 px-2.5 py-1.5 rounded-lg">
                                {{ $salarySlip->slip_number }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Slip Period Card --}}
    <div class="detail-card">
        <div class="card-header">
            <div class="card-icon bg-blue-50"><i data-lucide="calendar" class="w-4 h-4 text-blue-500"></i></div>
            <span class="card-title">Pay Period</span>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                <div class="bg-gray-50 rounded-xl px-4 py-3">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Month / Year</p>
                    <p class="text-[13px] font-black text-gray-800">{{ $months[$salarySlip->month] ?? $salarySlip->month }} {{ $salarySlip->year }}</p>
                </div>
                <div class="bg-gray-50 rounded-xl px-4 py-3">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Working Days</p>
                    <p class="text-[13px] font-black text-gray-800">{{ $salarySlip->working_days ?? '—' }}</p>
                </div>
                <div class="bg-gray-50 rounded-xl px-4 py-3">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Present Days</p>
                    <p class="text-[13px] font-black text-green-700">{{ $salarySlip->present_days ?? '—' }}</p>
                </div>
                <div class="bg-gray-50 rounded-xl px-4 py-3">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Absent Days</p>
                    <p class="text-[13px] font-black text-red-600">{{ $salarySlip->absent_days ?? '—' }}</p>
                </div>
                <div class="bg-gray-50 rounded-xl px-4 py-3">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Overtime Hrs</p>
                    <p class="text-[13px] font-black text-gray-800">{{ $salarySlip->overtime_hours ?? '—' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Mode Banner --}}
    <div x-show="editing" x-cloak
        class="mb-4 flex items-center gap-3 px-4 py-3 rounded-xl border border-amber-200 bg-amber-50">
        <i data-lucide="pencil" class="w-4 h-4 text-amber-600"></i>
        <p class="text-[12px] font-bold text-amber-800">
            You are editing this slip. Changes apply only while it is still
            <span class="font-extrabold">Draft</span> or <span class="font-extrabold">Generated</span>.
        </p>
    </div>

    {{-- Earnings & Deductions --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

        {{-- Earnings --}}
        <div class="detail-card !mb-0">
            <div class="card-header">
                <div class="card-icon bg-green-50"><i data-lucide="trending-up" class="w-4 h-4 text-green-500"></i></div>
                <span class="card-title">Earnings</span>
            </div>
            <div class="card-body !p-0">
                <table class="comp-table">
                    <thead>
                        <tr>
                            <th>Component</th>
                            <th>Amount</th>
                            <th x-show="editing" x-cloak style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, idx) in items" :key="row._uid">
                            <tr x-show="row.type === 'earning'">
                                <td>
                                    <div x-show="!editing">
                                        <span x-text="row.component_name"></span>
                                        {{-- How the figure was reached. Without it a pro-rated
                                             line reads as the wrong salary having been paid. --}}
                                        <p x-show="row.calculation_detail" class="text-[11px] text-gray-400 mt-0.5"
                                            x-text="row.calculation_detail"></p>
                                    </div>
                                    <input x-show="editing" x-cloak type="text"
                                        x-model="row.component_name"
                                        class="w-full border border-gray-200 rounded-md px-2 py-1 text-[13px] focus:border-green-500 focus:outline-none"
                                        placeholder="Component name">
                                </td>
                                <td>
                                    <span x-show="!editing">₹<span x-text="formatAmount(row.amount)"></span></span>
                                    <input x-show="editing" x-cloak type="number" min="0" step="0.01"
                                        x-model.number="row.amount"
                                        class="w-32 border border-gray-200 rounded-md px-2 py-1 text-[13px] text-right focus:border-green-500 focus:outline-none">
                                </td>
                                <td x-show="editing" x-cloak>
                                    <button type="button" @click="removeItem(idx)"
                                        class="text-gray-400 hover:text-red-500 transition-colors">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="!editing && earningsCount() === 0">
                            <td colspan="2" class="text-center text-gray-400 py-6 text-[12px]">No earnings components</td>
                        </tr>
                        <tr x-show="editing" x-cloak>
                            <td colspan="3" class="!text-left">
                                <button type="button" @click="addRow('earning')"
                                    class="inline-flex items-center gap-1.5 text-[12px] font-bold text-green-600 hover:text-green-700">
                                    <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i> Add Earning
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Deductions --}}
        <div class="detail-card !mb-0">
            <div class="card-header">
                <div class="card-icon bg-red-50"><i data-lucide="trending-down" class="w-4 h-4 text-red-500"></i></div>
                <span class="card-title">Deductions</span>
            </div>
            <div class="card-body !p-0">
                <table class="comp-table">
                    <thead>
                        <tr>
                            <th>Component</th>
                            <th>Amount</th>
                            <th x-show="editing" x-cloak style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, idx) in items" :key="row._uid">
                            <tr x-show="row.type === 'deduction'">
                                <td>
                                    <div x-show="!editing">
                                        <span x-text="row.component_name"></span>
                                        <p x-show="row.calculation_detail" class="text-[11px] text-gray-400 mt-0.5"
                                            x-text="row.calculation_detail"></p>
                                    </div>
                                    <input x-show="editing" x-cloak type="text"
                                        x-model="row.component_name"
                                        class="w-full border border-gray-200 rounded-md px-2 py-1 text-[13px] focus:border-red-500 focus:outline-none"
                                        placeholder="Component name">
                                </td>
                                <td class="!text-red-600">
                                    <span x-show="!editing">₹<span x-text="formatAmount(row.amount)"></span></span>
                                    <input x-show="editing" x-cloak type="number" min="0" step="0.01"
                                        x-model.number="row.amount"
                                        class="w-32 border border-gray-200 rounded-md px-2 py-1 text-[13px] text-right focus:border-red-500 focus:outline-none">
                                </td>
                                <td x-show="editing" x-cloak>
                                    <button type="button" @click="removeItem(idx)"
                                        class="text-gray-400 hover:text-red-500 transition-colors">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="!editing && deductionsCount() === 0">
                            <td colspan="2" class="text-center text-gray-400 py-6 text-[12px]">No deductions</td>
                        </tr>
                        <tr x-show="editing" x-cloak>
                            <td colspan="3" class="!text-left">
                                <button type="button" @click="addRow('deduction')"
                                    class="inline-flex items-center gap-1.5 text-[12px] font-bold text-red-600 hover:text-red-700">
                                    <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i> Add Deduction
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Left: Summary + Status + Actions --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Salary Summary --}}
            <div class="detail-card">
                <div class="card-header">
                    <div class="card-icon bg-purple-50"><i data-lucide="calculator" class="w-4 h-4 text-purple-500"></i></div>
                    <span class="card-title">Salary Summary</span>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <span class="info-label">Gross Earnings</span>
                        <span class="info-value">₹<span x-text="formatAmount(grossEarnings())"></span></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total Deductions</span>
                        <span class="info-value text-red-600">— ₹<span x-text="formatAmount(totalDeductions())"></span></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Round Off</span>
                        <span x-show="!editing" class="info-value text-gray-500">
                            <span x-text="roundOff >= 0 ? '+' : ''"></span>₹<span x-text="formatAmount(roundOff)"></span>
                        </span>
                        <input x-show="editing" x-cloak type="number" step="0.01" x-model.number="roundOff"
                            class="w-32 border border-gray-200 rounded-md px-2 py-1 text-[13px] text-right focus:border-purple-500 focus:outline-none">
                    </div>
                    <div class="mt-4 bg-gray-50 rounded-xl px-5 py-4 flex items-center justify-between">
                        <p class="text-[12px] font-bold text-gray-500 uppercase tracking-widest">Net Salary</p>
                        <p class="text-[26px] font-black text-gray-900">₹<span x-text="formatAmount(netSalary())"></span></p>
                    </div>
                </div>
            </div>

            {{-- Status & Payment Info --}}
            <div class="detail-card">
                <div class="card-header">
                    <div class="card-icon bg-teal-50"><i data-lucide="info" class="w-4 h-4 text-teal-500"></i></div>
                    <span class="card-title">Status & Payment Info</span>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="inline-flex items-center text-[10px] font-extrabold uppercase tracking-wider px-2.5 py-1 rounded-md"
                            style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }}">
                            {{ \App\Models\Hrm\SalarySlip::STATUS_LABELS[$salarySlip->status] }}
                        </span>
                    </div>
                    @if($salarySlip->generatedByUser)
                    <div class="info-row">
                        <span class="info-label">Generated By</span>
                        <span class="info-value">{{ $salarySlip->generatedByUser->name }}</span>
                    </div>
                    @endif
                    @if($salarySlip->generated_at)
                    <div class="info-row">
                        <span class="info-label">Generated On</span>
                        <span class="info-value">{{ $salarySlip->generated_at->format('d M Y, h:i A') }}</span>
                    </div>
                    @endif
                    @if($salarySlip->approvedByUser)
                    <div class="info-row">
                        <span class="info-label">Approved By</span>
                        <span class="info-value">{{ $salarySlip->approvedByUser->name }}</span>
                    </div>
                    @endif
                    @if($salarySlip->approved_at)
                    <div class="info-row">
                        <span class="info-label">Approved On</span>
                        <span class="info-value">{{ $salarySlip->approved_at->format('d M Y, h:i A') }}</span>
                    </div>
                    @endif
                    @if($salarySlip->payment_label)
                    <div class="info-row">
                        <span class="info-label">Payment Method</span>
                        <span class="info-value capitalize">{{ $salarySlip->payment_label }}</span>
                    </div>
                    @endif
                    @if($salarySlip->payment_reference)
                    <div class="info-row">
                        <span class="info-label">Payment Ref.</span>
                        <span class="info-value font-mono">{{ $salarySlip->payment_reference }}</span>
                    </div>
                    @endif
                    @if($salarySlip->payment_date)
                    <div class="info-row">
                        <span class="info-label">Payment Date</span>
                        <span class="info-value">{{ \Carbon\Carbon::parse($salarySlip->payment_date)->format('d M Y') }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Action Panel --}}
            @if(in_array($salarySlip->status, ['draft', 'generated', 'approved']))
            <div class="detail-card">
                <div class="card-header">
                    <div class="card-icon bg-green-50"><i data-lucide="zap" class="w-4 h-4 text-green-500"></i></div>
                    <span class="card-title">Actions</span>
                </div>
                <div class="card-body">
                    <div class="flex flex-wrap gap-3">

                        @if($canEdit)
                        <button x-show="!editing" @click="startEdit()" :disabled="saving"
                            class="inline-flex items-center gap-2 px-5 py-2.5 text-[13px] font-bold text-white rounded-xl hover:opacity-90 transition-opacity disabled:opacity-50"
                            style="background: #6366f1">
                            <i data-lucide="pencil" class="w-4 h-4"></i> Edit Slip
                        </button>

                        <button x-show="editing" x-cloak @click="saveEdit()" :disabled="saving"
                            class="inline-flex items-center gap-2 px-5 py-2.5 text-[13px] font-bold text-white rounded-xl hover:opacity-90 transition-opacity disabled:opacity-50"
                            style="background: #10b981">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span x-show="!saving">Save Changes</span>
                            <span x-show="saving">Saving...</span>
                        </button>

                        <button x-show="editing" x-cloak @click="cancelEdit()" :disabled="saving" type="button"
                            class="inline-flex items-center gap-2 px-5 py-2.5 text-[13px] font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors disabled:opacity-50">
                            <i data-lucide="x" class="w-4 h-4"></i> Cancel
                        </button>
                        @endif

                        @if($salarySlip->status === 'generated' && has_permission('salary_slips.approve'))
                        <button x-show="!editing" @click="approveSlip()" :disabled="saving"
                            class="inline-flex items-center gap-2 px-5 py-2.5 text-[13px] font-bold text-white rounded-xl hover:opacity-90 transition-opacity disabled:opacity-50"
                            style="background: #f59e0b">
                            <i data-lucide="check-circle" class="w-4 h-4"></i> Approve Slip
                        </button>
                        @endif

                        @if($salarySlip->status === 'approved'&& has_permission('salary_slips.mark_paid'))
                        <button x-show="!editing" @click="payModalOpen = true" :disabled="saving"
                            class="inline-flex items-center gap-2 px-5 py-2.5 text-[13px] font-bold text-white rounded-xl hover:opacity-90 transition-opacity disabled:opacity-50"
                            style="background: #10b981">
                            <i data-lucide="banknote" class="w-4 h-4"></i> Mark as Paid
                        </button>
                        @endif

                    </div>
                </div>
            </div>
            @endif

        </div>

        {{-- Right: Quick Actions --}}
        <div class="space-y-4">
            @if(has_permission('salary_slips.download_pdf'))
            <div class="detail-card">
                <div class="card-header">
                    <div class="card-icon bg-gray-50"><i data-lucide="download" class="w-4 h-4 text-gray-500"></i></div>
                    <span class="card-title">Download</span>
                </div>
                <div class="card-body">
                    <a href="{{ route('admin.hrm.salary-slips.pdf', $salarySlip) }}" target="_blank"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-[13px] font-bold text-white rounded-xl hover:opacity-90 transition-opacity"
                        style="background: var(--brand-600)">
                        <i data-lucide="file-down" class="w-4 h-4"></i>
                        Download PDF
                    </a>
                </div>
            </div>
            @endif

            <div class="detail-card">
                <div class="card-header">
                    <div class="card-icon bg-gray-50"><i data-lucide="hash" class="w-4 h-4 text-gray-500"></i></div>
                    <span class="card-title">Details</span>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <span class="info-label">Slip No.</span>
                        <span class="info-value font-mono text-[12px]">{{ $salarySlip->slip_number }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Created</span>
                        <span class="info-value">{{ $salarySlip->created_at->format('d M Y') }}</span>
                    </div>
                    @if($salarySlip->employee->pan_number)
                    <div class="info-row">
                        <span class="info-label">PAN</span>
                        <span class="info-value font-mono text-[12px]">{{ $salarySlip->employee->pan_number }}</span>
                    </div>
                    @endif
                    @if($salarySlip->employee->bank_account_number)
                    <div class="info-row">
                        <span class="info-label">Account</span>
                        <span class="info-value font-mono text-[12px]">****{{ substr($salarySlip->employee->bank_account_number, -4) }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Mark Paid Modal --}}
    <div x-show="payModalOpen" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="bg-white w-full max-w-md rounded-xl shadow-2xl" @click.away="payModalOpen = false"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-xl">
                <h3 class="font-black text-gray-800 uppercase tracking-widest text-sm">Mark as Paid</h3>
                <button @click="payModalOpen = false" class="text-gray-400 hover:text-red-500 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form @submit.prevent="submitPay()">
                <div class="p-6 space-y-4">
                    <div>
                        <x-payment-method-select
                            name="payment_method_id"
                            :selected="null"
                            label="Payment Method"
                            :required="true"
                            xModel="payForm.payment_method_id"
                        />
                        <p class="text-xs text-red-500 mt-1" x-show="payErrors.payment_method_id" x-text="payErrors.payment_method_id"></p>
                    </div>
                    <div>
                        <label class="field-label">Payment Reference</label>
                        <input type="text" x-model="payForm.payment_reference" class="field-input"
                            placeholder="Transaction ID, cheque no., etc.">
                        <p class="field-error" x-show="payErrors.payment_reference" x-text="payErrors.payment_reference"></p>
                    </div>
                    <div>
                        <label class="field-label">Payment Date <span class="text-red-400">*</span></label>
                        <input type="date" x-model="payForm.payment_date" class="field-input" required>
                        <p class="field-error" x-show="payErrors.payment_date" x-text="payErrors.payment_date"></p>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3">
                    <button type="button" @click="payModalOpen = false"
                        class="px-4 py-2 text-[13px] font-bold text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" :disabled="saving"
                        class="px-5 py-2 text-[13px] font-bold text-white rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50"
                        style="background: #10b981">
                        <span x-show="!saving">Mark Paid</span>
                        <span x-show="saving" class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
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
window.salarySlipShow = function(opts = {}) {
    // Tag every row with a stable _uid so Alpine's x-for key survives edits/reorders.
    let _uidSeq = 0;
    const seed = (opts.items || []).map(r => ({ ...r, _uid: ++_uidSeq }));

    return {
        saving: false,
        payModalOpen: false,
        payErrors: {},
        payForm: { payment_method_id: '', payment_reference: '', payment_date: '' },

        // ── Edit mode state ──
        canEdit: !!opts.canEdit,
        editing: false,
        items: seed,
        roundOff: Number(opts.roundOff ?? 0),
        _originalSnapshot: null,

        init() {
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            // Re-render icons whenever edit mode toggles (trash, pencil, etc.)
            this.$watch('editing', () => {
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            });
        },

        // ── Computed ──
        formatAmount(n) {
            const v = Number(n || 0);
            return v.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        earningsCount() { return this.items.filter(r => r.type === 'earning').length; },
        deductionsCount() { return this.items.filter(r => r.type === 'deduction').length; },
        grossEarnings() {
            return this.items.filter(r => r.type === 'earning').reduce((s, r) => s + Number(r.amount || 0), 0);
        },
        totalDeductions() {
            return this.items.filter(r => r.type === 'deduction').reduce((s, r) => s + Number(r.amount || 0), 0);
        },
        netSalary() {
            return this.grossEarnings() - this.totalDeductions() + Number(this.roundOff || 0);
        },

        // ── Edit actions ──
        startEdit() {
            if (!this.canEdit) return;
            this._originalSnapshot = JSON.stringify({ items: this.items, roundOff: this.roundOff });
            this.editing = true;
        },
        cancelEdit() {
            if (this._originalSnapshot) {
                const s = JSON.parse(this._originalSnapshot);
                this.items = s.items;
                this.roundOff = s.roundOff;
            }
            this.editing = false;
        },
        addRow(type) {
            this.items.push({
                id: null,
                type: type,
                component_name: '',
                amount: 0,
                _uid: ++_uidSeq,
            });
        },
        removeItem(idx) {
            this.items.splice(idx, 1);
        },
        async saveEdit() {
            // Client-side guard: names must be non-empty, amounts non-negative.
            for (const r of this.items) {
                if (!r.component_name || !r.component_name.trim()) {
                    BizAlert.toast('Every row needs a component name.', 'error');
                    return;
                }
                if (Number(r.amount) < 0 || isNaN(Number(r.amount))) {
                    BizAlert.toast('Amounts must be zero or positive.', 'error');
                    return;
                }
            }
            if (this.items.length === 0) {
                BizAlert.toast('A salary slip must have at least one row.', 'error');
                return;
            }

            this.saving = true;
            try {
                const payload = {
                    items: this.items.map(r => ({
                        id: r.id,
                        component_name: r.component_name,
                        type: r.type,
                        amount: Number(r.amount || 0),
                    })),
                    round_off: Number(this.roundOff || 0),
                };
                const resp = await fetch('{{ route("admin.hrm.salary-slips.update", $salarySlip) }}', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });
                const data = await resp.json();
                if (!resp.ok) { BizAlert.toast(data.message || 'Error', 'error'); return; }
                BizAlert.toast(data.message, 'success');
                setTimeout(() => window.location.reload(), 600);
            } catch(e) { BizAlert.toast('Network error', 'error'); } finally { this.saving = false; }
        },

        async approveSlip() {
            const result = await BizAlert.confirm('Approve Salary Slip', 'Are you sure you want to approve this salary slip?', 'Approve');
            if (!result.isConfirmed) return;
            this.saving = true;
            try {
                const resp = await fetch('{{ route("admin.hrm.salary-slips.approve", $salarySlip) }}', {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await resp.json();
                if (!resp.ok) { BizAlert.toast(data.message || 'Error', 'error'); return; }
                BizAlert.toast(data.message, 'success');
                setTimeout(() => window.location.reload(), 700);
            } catch(e) { BizAlert.toast('Network error', 'error'); } finally { this.saving = false; }
        },

        async submitPay() {
            this.saving = true;
            this.payErrors = {};
            try {
                const resp = await fetch('{{ route("admin.hrm.salary-slips.pay", $salarySlip) }}', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
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
                        BizAlert.toast(data.message || 'Error', 'error');
                    }
                    return;
                }
                BizAlert.toast(data.message, 'success');
                this.payModalOpen = false;
                setTimeout(() => window.location.reload(), 700);
            } catch(e) { BizAlert.toast('Network error', 'error'); } finally { this.saving = false; }
        },
    };
};
</script>
@endpush
