@extends('layouts.admin')

@section('title', 'Apply Leave')

@section('header-title')
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.hrm.leaves.index') }}"
            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        </a>
        <div>
            <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Apply Leave</h1>
            <p class="text-xs text-gray-400 font-medium mt-0.5">Submit a new leave application</p>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .form-section {
        background: #fff;
        border: 1.5px solid #f1f5f9;
        border-radius: 16px;
        overflow: hidden;
        margin-bottom: 16px;
    }

    .section-head {
        padding: 13px 18px;
        border-bottom: 1px solid #f8fafc;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .section-icon {
        width: 28px; height: 28px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }

    .section-title {
        font-size: 12px;
        font-weight: 800;
        color: #374151;
        letter-spacing: 0.03em;
    }

    .section-body { padding: 18px; }

    .field-label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 5px;
    }

    .field-input {
        width: 100%;
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        padding: 9px 13px;
        font-size: 13px;
        color: #1f2937;
        outline: none;
        font-family: inherit;
        background: #fff;
        transition: border-color 150ms ease, box-shadow 150ms ease;
    }

    .field-input:focus {
        border-color: var(--brand-600);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-600) 10%, transparent);
    }

    select.field-input {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5' stroke-linecap='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 36px;
        cursor: pointer;
    }

    .field-input.has-error { border-color: #f43f5e; }

    .field-error {
        font-size: 11px;
        font-weight: 600;
        color: #f43f5e;
        margin-top: 4px;
    }

    .sticky-footer {
        position: sticky;
        bottom: 0;
        background: #fff;
        border-top: 1.5px solid #f1f5f9;
        padding: 14px 24px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        z-index: 20;
        border-radius: 0 0 16px 16px;
    }

    .balance-card {
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 30px;
        font-size: 12px;
    }
</style>
@endpush

@section('content')

<div class="pb-10" x-data="leaveForm()">

    <form method="POST" action="{{ route('admin.hrm.leaves.store') }}" enctype="multipart/form-data">
        @csrf

        {{-- ── Validation error banner ── --}}
        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 flex items-start gap-3">
                <svg class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div>
                    <p class="text-sm font-semibold text-red-700">Please fix the errors below.</p>
                </div>
            </div>
        @endif

        {{-- ══════════════════════════════════
             Section 1 — Leave Details
        ══════════════════════════════════ --}}
        <div class="form-section">
            <div class="section-head">
                <div class="section-icon" style="background: #eff6ff">
                    <i data-lucide="calendar" style="width:14px;height:14px;color:#3b82f6"></i>
                </div>
                <span class="section-title">Leave Details</span>
            </div>
            <div class="section-body">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-5 gap-y-4">

                    {{-- Employee --}}
                    <div>
                        <label class="field-label">Employee <span class="text-red-500">*</span></label>
                        <select name="employee_id" x-model="employeeId" @change="fetchBalances()"
                            class="field-input {{ $errors->has('employee_id') ? 'has-error' : '' }}">
                            <option value="">Select employee</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->user?->name }} ({{ $employee->employee_code }})
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Leave Type --}}
                    <div>
                        <label class="field-label">Leave Type <span class="text-red-500">*</span></label>
                        <select name="leave_type_id" x-model="leaveTypeId" @change="fetchBalances()"
                            class="field-input {{ $errors->has('leave_type_id') ? 'has-error' : '' }}">
                            <option value="">Select leave type</option>
                            @foreach($leaveTypes as $type)
                                <option value="{{ $type->id }}" {{ old('leave_type_id') == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('leave_type_id')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- From Date --}}
                    <div>
                        <label class="field-label">From Date <span class="text-red-500">*</span></label>
                        <input type="date" name="from_date" x-model="fromDate" @change="calculateDays()"
                            value="{{ old('from_date') }}"
                            class="field-input {{ $errors->has('from_date') ? 'has-error' : '' }}">
                        @error('from_date')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- To Date --}}
                    <div>
                        <label class="field-label">To Date <span class="text-red-500">*</span></label>
                        <input type="date" name="to_date" x-model="toDate" @change="calculateDays()"
                            value="{{ old('to_date') }}"
                            class="field-input {{ $errors->has('to_date') ? 'has-error' : '' }}">
                        @error('to_date')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Day Type --}}
                    <div>
                        <label class="field-label">Day Type <span class="text-red-500">*</span></label>
                        <select name="day_type" x-model="dayType" @change="calculateDays()"
                            class="field-input {{ $errors->has('day_type') ? 'has-error' : '' }}">
                            <option value="full_day" {{ old('day_type', 'full_day') == 'full_day' ? 'selected' : '' }}>Full Day</option>
                            <option value="first_half" {{ old('day_type') == 'first_half' ? 'selected' : '' }}>First Half</option>
                            <option value="second_half" {{ old('day_type') == 'second_half' ? 'selected' : '' }}>Second Half</option>
                        </select>
                        @error('day_type')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Total Days --}}
                    <div>
                        <label class="field-label">Total Days <span class="text-red-500">*</span></label>
                        <input type="number" name="total_days" x-model="totalDays"
                            value="{{ old('total_days') }}"
                            min="0.5" step="0.5"
                            class="field-input {{ $errors->has('total_days') ? 'has-error' : '' }}">
                        @error('total_days')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                {{-- Leave Balance Info --}}
                <template x-if="balanceInfo">
                    <div class="mt-4 balance-card">
                        <div class="flex items-center gap-6">
                            <div>
                                <span class="text-gray-400 font-medium">Entitled:</span>
                                <span class="font-bold text-gray-700" x-text="balanceInfo.entitled ?? '—'"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 font-medium">Used:</span>
                                <span class="font-bold text-gray-700" x-text="balanceInfo.used ?? '—'"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 font-medium">Remaining:</span>
                                <span class="font-bold" :class="(balanceInfo.remaining ?? 0) > 0 ? 'text-green-600' : 'text-red-600'"
                                    x-text="balanceInfo.remaining ?? '—'"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- ══════════════════════════════════
             Section 2 — Reason
        ══════════════════════════════════ --}}
        <div class="form-section">
            <div class="section-head">
                <div class="section-icon" style="background: #fdf2f8">
                    <i data-lucide="message-square" style="width:14px;height:14px;color:#ec4899"></i>
                </div>
                <span class="section-title">Reason</span>
            </div>
            <div class="section-body">
                <div>
                    <label class="field-label">Reason <span class="text-red-500">*</span></label>
                    <textarea name="reason" rows="4"
                        placeholder="Provide the reason for your leave request..."
                        class="field-input resize-none {{ $errors->has('reason') ? 'has-error' : '' }}">{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════
             Section 3 — Document
        ══════════════════════════════════ --}}
        <div class="form-section">
            <div class="section-head">
                <div class="section-icon" style="background: #f0fdf4">
                    <i data-lucide="paperclip" style="width:14px;height:14px;color:#16a34a"></i>
                </div>
                <span class="section-title">Document</span>
            </div>
            <div class="section-body">
                <div>
                    <label class="field-label">Supporting Document <span class="text-gray-400 font-normal">(optional, max 5MB)</span></label>
                    <input type="file" name="document"
                        class="field-input {{ $errors->has('document') ? 'has-error' : '' }}"
                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    @error('document')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════
             Sticky Footer — Cancel + Submit
        ══════════════════════════════════ --}}
        <div class="sticky-footer">
            <a href="{{ route('admin.hrm.leaves.index') }}"
                class="flex items-center justify-center px-5 py-2.5 rounded-xl text-[13px] font-bold text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button type="submit"
                class="flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl text-[14px] font-bold text-white transition-opacity hover:opacity-90"
                style="background: var(--brand-600)">
                <i data-lucide="send" style="width:16px;height:16px"></i>
                Submit Leave
            </button>
        </div>

    </form>

</div>
@endsection

@push('scripts')
<script>
function leaveForm() {
    return {
        employeeId: '{{ old('employee_id', '') }}',
        leaveTypeId: '{{ old('leave_type_id', '') }}',
        fromDate: '{{ old('from_date', '') }}',
        toDate: '{{ old('to_date', '') }}',
        dayType: '{{ old('day_type', 'full_day') }}',
        totalDays: '{{ old('total_days', '') }}',
        balanceInfo: null,

        calculateDays() {
            if (!this.fromDate || !this.toDate) return;

            const from = new Date(this.fromDate);
            const to = new Date(this.toDate);
            if (to < from) return;

            const diffTime = Math.abs(to - from);
            let days = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

            if (this.dayType !== 'full_day') {
                days = days > 1 ? days - 0.5 : 0.5;
            }

            this.totalDays = days;
        },

        async fetchBalances() {
            if (!this.employeeId) {
                this.balanceInfo = null;
                return;
            }

            try {
                const res = await fetch(`{{ url('admin/hrm/leaves/balances') }}/${this.employeeId}?year={{ date('Y') }}`, {
                    headers: {
                        'Accept': 'application/json',
                    },
                });
                const json = await res.json();
                if (json.success && json.data && this.leaveTypeId) {
                    const match = json.data.find(b => String(b.leave_type_id) === String(this.leaveTypeId));
                    this.balanceInfo = match || null;
                } else if (json.success && json.data) {
                    this.balanceInfo = null;
                }
            } catch (e) {
                this.balanceInfo = null;
            }
        }
    };
}
</script>
@endpush
