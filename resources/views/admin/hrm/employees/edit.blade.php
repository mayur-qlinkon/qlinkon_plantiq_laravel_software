@extends ('layouts.admin')

@section('title', 'Edit Employee')

@section('header-title')
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.hrm.employees.show', $employee) }}"
            class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round">
                <path d="M19 12H5M12 5l-7 7 7 7" />
            </svg>
        </a>
        <div>
            <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Edit Employee</h1>
            <p class="mt-0.5 text-xs font-medium text-gray-400">Update employee record for {{ $employee->user->name }}</p>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .form-section {
            background: #fff;
            border: 1.5px solid #f1f5f9;
            border-radius: 16px;
            margin-bottom: 16px;
            position: relative;
            /* 🌟 Custom select dropdowns ki stacking context locks ko setup karne ke liye */
        }

        {{-- 🌟 rounded corners ki beauty bani rahegi aur dropdowns bhi aaram se bahaar open honge --}} .section-head {
            border-radius: 14px 14px 0 0;
        }

        .section-body:last-child {
            border-radius: 0 0 14px 14px;
        }

        .section-head {
            padding: 13px 18px;
            border-bottom: 1px solid #f8fafc;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .section-title {
            font-size: 12px;
            font-weight: 800;
            color: #374151;
            letter-spacing: 0.03em;
        }

        .section-body {
            padding: 18px;
        }

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
            transition:
                border-color 150ms ease,
                box-shadow 150ms ease;
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

        .field-input.has-error {
            border-color: #f43f5e;
        }

        .field-error {
            font-size: 11px;
            font-weight: 600;
            color: #f43f5e;
            margin-top: 4px;
        }

        .field-readonly {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 9px 13px;
            font-size: 13px;
            color: #6b7280;
            background: #f9fafb;
            font-family: inherit;
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
    </style>
@endpush

@section('content')
    @php
        // Map dynamic collections into clean string-keyed associative arrays for edit-state pre-selection
        $departmentOptions = [];
        foreach ($departments ?? [] as $dept) {
            $departmentOptions[(string) $dept->id] = $dept->name;
        }

        $designationOptions = [];
        foreach ($designations ?? [] as $desig) {
            $designationOptions[(string) $desig->id] = $desig->name;
        }

        $storeOptions = [];
        foreach ($stores ?? [] as $store) {
            $storeOptions[(string) $store->id] = $store->name;
        }

        $shiftOptions = [];
        foreach ($shifts ?? [] as $shift) {
            $shiftOptions[(string) $shift->id] = $shift->name;
        }

        // Cast database values to safe comparable string literals
        $selectedManager = old('reporting_to', $employee->reporting_to)
            ? (string) old('reporting_to', $employee->reporting_to)
            : '';
    @endphp

    <div class="pb-10" x-data="{ status: '{{ old('status', $employee->status) }}' }">
        <form method="POST" action="{{ route('admin.hrm.employees.update', $employee) }}" enctype="multipart/form-data">
            @csrf
            @method ('PUT')

            {{-- ── Validation error banner ── --}}
            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600 shadow-sm">
                    <div class="mb-1 flex items-center gap-2 font-bold">
                        <i data-lucide="alert-circle" class="h-4 w-4"></i> Please fix the following errors:
                    </div>
                    <ul class="ml-6 list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ══════════════════════════════════
             Section 1 — Basic Information
        ══════════════════════════════════ --}}
            <div class="form-section">
                <div class="section-head">
                    <div class="section-icon" style="background: #eff6ff">
                        <i data-lucide="user" style="width: 14px; height: 14px; color: #3b82f6"></i>
                    </div>
                    <span class="section-title">Basic Information</span>
                </div>
                <div class="section-body">
                    <div class="grid grid-cols-1 gap-x-5 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                        @if ($employee->user)
                            <div class="col-span-1 sm:col-span-2 lg:col-span-3">
                                <h5 class="text-sm font-bold text-gray-700">Linked Account</h5>
                                <div
                                    class="mt-3 flex flex-col justify-between rounded-xl border border-gray-200 bg-gray-50 p-4 sm:flex-row sm:items-center">
                                    <div>
                                        <p class="text-sm font-bold text-gray-800">{{ $employee->user->name }}</p>
                                        <p class="text-xs font-medium text-gray-500">{{ $employee->user->email }}</p>

                                        <div class="mt-2 flex flex-wrap gap-1">
                                            @if ($employee->user->roles->isEmpty() && $employee->user->modules->isEmpty())
                                                <span class="text-[10px] font-medium text-gray-400">No role or module access
                                                    assigned yet.</span>
                                            @else
                                                @foreach ($employee->user->roles as $role)
                                                    <span
                                                        class="rounded bg-blue-100 px-1.5 py-0.5 text-[10px] font-bold text-blue-700">{{ $role->name }}</span>
                                                @endforeach
                                                @foreach ($employee->user->modules as $mod)
                                                    <span
                                                        class="rounded bg-purple-100 px-1.5 py-0.5 text-[10px] font-bold text-purple-700">{{ $mod->name }}</span>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                    <div class="mt-4 sm:mt-0">
                                        <a href="{{ route('admin.users.edit', $employee->user) }}" target="_blank"
                                            class="flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-gray-600 shadow-sm transition-colors hover:bg-gray-50">
                                            <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                                            Manage access
                                        </a>
                                    </div>
                                </div>
                                <div
                                    class="mt-3 flex items-start gap-2 rounded-lg bg-gray-50 px-3 py-2 text-xs font-medium text-gray-500">
                                    <i data-lucide="info" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-gray-400"></i>
                                    <p>Name, email, password, role and module access are managed from the Users screen.</p>
                                </div>
                            </div>
                        @endif

                        {{-- Employment Details Header --}}
                        <div class="col-span-1 border-b border-gray-100 pt-4 pb-2 sm:col-span-2 lg:col-span-3">
                            <h5 class="text-sm font-bold text-gray-700">Employment Details</h5>
                        </div>
                        {{-- Employee Code (editable + live AJAX uniqueness check) --}}
                        <div x-data="empCodeCheck()">
                            <label class="field-label">Employee Code</label>
                            <div class="relative">
                                <input type="text" name="employee_code" x-model="code"
                                    x-on:input.debounce.600ms="check()"
                                    value="{{ old('employee_code', $employee->employee_code) }}" maxlength="30"
                                    class="field-input pr-9 {{ $errors->has('employee_code') ? 'has-error' : '' }}" />
                                {{-- Status icon --}}
                                <span class="absolute top-1/2 right-2.5 -translate-y-1/2 text-sm" x-show="status !== ''">
                                    <span x-show="status === 'checking'" class="text-gray-400">
                                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4" />
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                        </svg>
                                    </span>
                                    <span x-show="status === 'available'" class="text-green-500">✓</span>
                                    <span x-show="status === 'taken'" class="text-red-500">✗</span>
                                </span>
                            </div>
                            <p x-show="message !== ''" x-text="message"
                                :class="status === 'available' ? 'text-green-600' : 'text-red-500'"
                                class="mt-1 text-xs font-medium"></p>
                            @error('employee_code')
                                <p class="field-error">{{ $message }}</p>
                            @endError
                        </div>

                        {{-- Date of Joining --}}
                        <div>
                            <label class="field-label">Date of Joining <span class="text-red-500">*</span></label>
                            <input type="date" name="date_of_joining"
                                value="{{ old('date_of_joining', $employee->date_of_joining?->format('Y-m-d')) }}"
                                class="field-input {{ $errors->has('date_of_joining') ? 'has-error' : '' }}" />
                            @error('date_of_joining')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Employment Type --}}
                        <div>
                            <label class="field-label">Employment Type</label>
                            <x-custom-select name="employment_type" placeholder="Select type" :options="[
                                'full_time' => 'Full Time',
                                'part_time' => 'Part Time',
                                'contract' => 'Contract',
                                'intern' => 'Intern',
                                'freelancer' => 'Freelancer',
                            ]"
                                :selected="old('employment_type', $employee->employment_type)" ::class="$errors - > has('employment_type') ? 'has-error' : ''" />
                            @error('employment_type')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Department --}}
                        <div>
                            <label class="field-label">Department</label>
                            <x-custom-select name="department_id" placeholder="Select department" :options="$departmentOptions"
                                :selected="old(
                                    'department_id',
                                    $employee->department_id ? (string) $employee->department_id : '',
                                )" ::class="$errors - > has('department_id') ? 'has-error' : ''" />
                            @error('department_id')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Designation --}}
                        <div>
                            <label class="field-label">Designation</label>
                            <x-custom-select name="designation_id" placeholder="Select designation" :options="$designationOptions"
                                :selected="old(
                                    'designation_id',
                                    $employee->designation_id ? (string) $employee->designation_id : '',
                                )" ::class="$errors - > has('designation_id') ? 'has-error' : ''" />
                            @error('designation_id')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Store --}}
                        <div>
                            <label class="field-label">Primary Branch / Store <span class="text-red-500">*</span></label>
                            <x-custom-select name="store_id" placeholder="Select Primary Store" :options="$storeOptions"
                                :selected="old('store_id', (string) $employee->store_id)" ::class="$errors - > has('store_id') ? 'has-error' : ''" required />
                            @error('store_id')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Shift --}}
                        <div>
                            <label class="field-label">Shift</label>
                            <x-custom-select name="shift_id" placeholder="Select shift" :options="$shiftOptions"
                                :selected="old('shift_id', $employee->shift_id ? (string) $employee->shift_id : '')" ::class="$errors - > has('shift_id') ? 'has-error' : ''" />
                            @error('shift_id')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Reporting Manager --}}
                        <div>
                            <label class="field-label">Reporting Manager</label>
                            <x-custom-select name="reporting_to" placeholder="Select manager" :options="$managerOptions"
                                :selected="$selectedManager" ::class="$errors - > has('reporting_to') ? 'has-error' : ''" />
                            @error('reporting_to')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Status --}}
                        <div>
                            <label class="field-label">Status <span class="text-red-500">*</span></label>
                            <div @change="status = $event.target.value">
                                <x-custom-select name="status" :options="[
                                    'active' => 'Active',
                                    'inactive' => 'Inactive',
                                    'terminated' => 'Terminated',
                                    'on_notice' => 'On Notice',
                                    'absconding' => 'Absconding',
                                ]" :selected="old('status', $employee->status)" ::class="$errors - > has('status') ? 'has-error' : ''" />
                            </div>
                            @error('status')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Exit Reason (shown for terminated / absconding) --}}
                    <div x-show="status === 'terminated' || status === 'absconding'" x-cloak class="mt-4">
                        <label class="field-label">Exit Reason</label>
                        <textarea name="exit_reason" rows="3" placeholder="Reason for termination or absconding..."
                            class="field-input resize-none {{ $errors->has('exit_reason') ? 'has-error' : '' }}">{{ old('exit_reason', $employee->exit_reason) }}</textarea>
                        @error('exit_reason')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════
             Section 2 — Personal Details
        ══════════════════════════════════ --}}
            <div class="form-section">
                <div class="section-head">
                    <div class="section-icon" style="background: #fdf2f8">
                        <i data-lucide="heart" style="width: 14px; height: 14px; color: #ec4899"></i>
                    </div>
                    <span class="section-title">Personal Details</span>
                </div>
                <div class="section-body">
                    <div class="grid grid-cols-1 gap-x-5 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                        {{-- Date of Birth --}}
                        <div>
                            <label class="field-label">Date of Birth</label>
                            <input type="date" name="date_of_birth"
                                value="{{ old('date_of_birth', $employee->date_of_birth?->format('Y-m-d')) }}"
                                class="field-input {{ $errors->has('date_of_birth') ? 'has-error' : '' }}" />
                            @error('date_of_birth')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Gender --}}
                        <div>
                            <label class="field-label">Gender</label>
                            <x-custom-select name="gender" placeholder="Select gender" :options="['male' => 'Male', 'female' => 'Female', 'other' => 'Other']"
                                :selected="old('gender', $employee->gender)" ::class="$errors - > has('gender') ? 'has-error' : ''" />
                            @error('gender')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Marital Status --}}
                        <div>
                            <label class="field-label">Marital Status</label>
                            <x-custom-select name="marital_status" placeholder="Select status" :options="[
                                'single' => 'Single',
                                'married' => 'Married',
                                'divorced' => 'Divorced',
                                'widowed' => 'Widowed',
                            ]"
                                :selected="old('marital_status', $employee->marital_status)" ::class="$errors - > has('marital_status') ? 'has-error' : ''" />
                            @error('marital_status')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Blood Group --}}
                        <div>
                            <label class="field-label">Blood Group</label>
                            <input type="text" name="blood_group"
                                value="{{ old('blood_group', $employee->blood_group) }}" placeholder="e.g. O+"
                                maxlength="5"
                                class="field-input {{ $errors->has('blood_group') ? 'has-error' : '' }}" />
                            @error('blood_group')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════
             Section 3 — Salary Information
        ══════════════════════════════════ --}}
            <div class="form-section">
                <div class="section-head">
                    <div class="section-icon" style="background: #f0fdf4">
                        <i data-lucide="indian-rupee" style="width: 14px; height: 14px; color: #16a34a"></i>
                    </div>
                    <span class="section-title">Salary Information</span>
                </div>
                <div class="section-body">
                    <div class="grid grid-cols-1 gap-x-5 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                        {{-- Salary Type --}}
                        <div>
                            <label class="field-label">Salary Type</label>
                            <x-custom-select name="salary_type" placeholder="Select type" :options="['monthly' => 'Monthly', 'daily' => 'Daily (per-day rate)']"
                                :selected="old('salary_type', $employee->salary_type)" ::class="$errors - > has('salary_type') ? 'has-error' : ''" />
                            @error('salary_type')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div
                        class="mt-4 flex items-start gap-2 rounded-lg bg-gray-50 px-3 py-2 text-xs font-medium text-gray-500">
                        <i data-lucide="info" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-gray-400"></i>
                        <p>Pay amounts are managed from the Salary Structure card on this employee's profile.</p>
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════
                Section 4 — Bank Details
            ══════════════════════════════════ --}}
            <div class="form-section">
                <div class="section-head">
                    <div class="section-icon" style="background: #fffbeb">
                        <i data-lucide="landmark" style="width: 14px; height: 14px; color: #d97706"></i>
                    </div>
                    <span class="section-title">Bank Details</span>
                </div>
                <div class="section-body">
                    <div class="grid grid-cols-1 gap-x-5 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                        {{-- Bank Name --}}
                        <div>
                            <label class="field-label">Bank Name</label>
                            <input type="text" name="bank_name" value="{{ old('bank_name', $employee->bank_name) }}"
                                placeholder="Bank name"
                                class="field-input {{ $errors->has('bank_name') ? 'has-error' : '' }}" />
                            @error('bank_name')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Account Number --}}
                        <div>
                            <label class="field-label">Account Number</label>
                            <input type="text" name="bank_account_number"
                                value="{{ old('bank_account_number', $employee->bank_account_number) }}"
                                placeholder="Account number"
                                class="field-input {{ $errors->has('bank_account_number') ? 'has-error' : '' }}" />
                            @error('bank_account_number')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- IFSC Code --}}
                        <div>
                            <label class="field-label">IFSC Code</label>
                            <input type="text" name="bank_ifsc" value="{{ old('bank_ifsc', $employee->bank_ifsc) }}"
                                placeholder="IFSC code"
                                class="field-input {{ $errors->has('bank_ifsc') ? 'has-error' : '' }}" />
                            @error('bank_ifsc')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Branch --}}
                        <div>
                            <label class="field-label">Branch</label>
                            <input type="text" name="bank_branch"
                                value="{{ old('bank_branch', $employee->bank_branch) }}" placeholder="Branch name"
                                class="field-input {{ $errors->has('bank_branch') ? 'has-error' : '' }}" />
                            @error('bank_branch')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════
             Section 5 — Statutory Information
        ══════════════════════════════════ --}}
            <div class="form-section">
                <div class="section-head">
                    <div class="section-icon" style="background: #faf5ff">
                        <i data-lucide="shield" style="width: 14px; height: 14px; color: #a855f7"></i>
                    </div>
                    <span class="section-title">Statutory Information</span>
                </div>
                <div class="section-body">
                    <div class="grid grid-cols-1 gap-x-5 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                        {{-- PAN Number --}}
                        <div>
                            <label class="field-label">PAN Number</label>
                            <input type="text" name="pan_number"
                                value="{{ old('pan_number', $employee->pan_number) }}" placeholder="ABCDE1234F"
                                maxlength="10" class="field-input {{ $errors->has('pan_number') ? 'has-error' : '' }}" />
                            @error('pan_number')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Aadhaar Number --}}
                        <div>
                            <label class="field-label">Aadhaar Number</label>
                            <input type="text" name="aadhaar_number"
                                value="{{ old('aadhaar_number', $employee->aadhaar_number) }}"
                                placeholder="12-digit Aadhaar" maxlength="12"
                                class="field-input {{ $errors->has('aadhaar_number') ? 'has-error' : '' }}" />
                            @error('aadhaar_number')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- UAN Number --}}
                        <div>
                            <label class="field-label">UAN Number</label>
                            <input type="text" name="uan_number"
                                value="{{ old('uan_number', $employee->uan_number) }}"
                                placeholder="Universal Account Number"
                                class="field-input {{ $errors->has('uan_number') ? 'has-error' : '' }}" />
                            @error('uan_number')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- ESI Number --}}
                        <div>
                            <label class="field-label">ESI Number</label>
                            <input type="text" name="esi_number"
                                value="{{ old('esi_number', $employee->esi_number) }}" placeholder="ESI number"
                                class="field-input {{ $errors->has('esi_number') ? 'has-error' : '' }}" />
                            @error('esi_number')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- PF Number --}}
                        <div>
                            <label class="field-label">PF Number</label>
                            <input type="text" name="pf_number" value="{{ old('pf_number', $employee->pf_number) }}"
                                placeholder="PF number"
                                class="field-input {{ $errors->has('pf_number') ? 'has-error' : '' }}" />
                            @error('pf_number')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════
             Section 6 — Address
        ══════════════════════════════════ --}}
            <div class="form-section">
                <div class="section-head">
                    <div class="section-icon" style="background: #f0fdfa">
                        <i data-lucide="map-pin" style="width: 14px; height: 14px; color: #14b8a6"></i>
                    </div>
                    <span class="section-title">Address</span>
                </div>
                <div class="section-body">
                    <div class="grid grid-cols-1 gap-x-5 gap-y-4 sm:grid-cols-2">
                        {{-- Current Address --}}
                        <div>
                            <label class="field-label">Current Address</label>
                            <textarea name="current_address" rows="3" placeholder="Current residential address"
                                class="field-input resize-none {{ $errors->has('current_address') ? 'has-error' : '' }}">{{ old('current_address', $employee->current_address) }}</textarea>
                            @error('current_address')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Permanent Address --}}
                        <div>
                            <label class="field-label">Permanent Address</label>
                            <textarea name="permanent_address" rows="3" placeholder="Permanent residential address"
                                class="field-input resize-none {{ $errors->has('permanent_address') ? 'has-error' : '' }}">{{ old('permanent_address', $employee->permanent_address) }}</textarea>
                            @error('permanent_address')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════
             Section 7 — Emergency Contact
        ══════════════════════════════════ --}}
            <div class="form-section">
                <div class="section-head">
                    <div class="section-icon" style="background: #fef2f2">
                        <i data-lucide="phone" style="width: 14px; height: 14px; color: #ef4444"></i>
                    </div>
                    <span class="section-title">Emergency Contact</span>
                </div>
                <div class="section-body">
                    <div class="grid grid-cols-1 gap-x-5 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                        {{-- Name --}}
                        <div>
                            <label class="field-label">Name</label>
                            <input type="text" name="emergency_contact_name"
                                value="{{ old('emergency_contact_name', $employee->emergency_contact_name) }}"
                                placeholder="Contact person name"
                                class="field-input {{ $errors->has('emergency_contact_name') ? 'has-error' : '' }}" />
                            @error('emergency_contact_name')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Phone --}}
                        <div>
                            <label class="field-label">Phone</label>
                            <input type="text" name="emergency_contact_phone"
                                value="{{ old('emergency_contact_phone', $employee->emergency_contact_phone) }}"
                                placeholder="Contact phone number"
                                class="field-input {{ $errors->has('emergency_contact_phone') ? 'has-error' : '' }}" />
                            @error('emergency_contact_phone')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Relation --}}
                        <div>
                            <label class="field-label">Relation</label>
                            <input type="text" name="emergency_contact_relation"
                                value="{{ old('emergency_contact_relation', $employee->emergency_contact_relation) }}"
                                placeholder="e.g. Spouse, Parent, Sibling"
                                class="field-input {{ $errors->has('emergency_contact_relation') ? 'has-error' : '' }}" />
                            @error('emergency_contact_relation')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════
             Section 8 — Notes
        ══════════════════════════════════ --}}
            <div class="form-section">
                <div class="section-head">
                    <div class="section-icon" style="background: #f9fafb">
                        <i data-lucide="file-text" style="width: 14px; height: 14px; color: #6b7280"></i>
                    </div>
                    <span class="section-title">Notes</span>
                </div>
                <div class="section-body">
                    <textarea name="notes" rows="3" placeholder="Any additional notes about this employee..."
                        class="field-input resize-none w-full {{ $errors->has('notes') ? 'has-error' : '' }}">{{ old('notes', $employee->notes) }}</textarea>
                    @error('notes')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- ══════════════════════════════════
             Section 9 — Documents & Identity
        ══════════════════════════════════ --}}
            <div class="form-section">
                <div class="section-head">
                    <div class="section-icon" style="background: #f3e8ff">
                        <i data-lucide="file-badge" style="width: 14px; height: 14px; color: #9333ea"></i>
                    </div>
                    <span class="section-title">Documents & Identity</span>
                </div>
                <div class="section-body">
                    <div class="grid grid-cols-1 gap-x-5 gap-y-4 sm:grid-cols-3">
                        {{-- Profile Photo --}}
                        <div>
                            <label class="field-label">Profile Photo</label>
                            @if ($employee->photo)
                                <div class="mb-2 flex items-center gap-3">
                                    <img src="{{ Storage::url($employee->photo) }}" alt="Photo"
                                        class="h-10 w-10 rounded-full border border-gray-200 object-cover" />
                                    <span class="text-[10px] font-medium text-gray-500">Upload new to replace</span>
                                </div>
                            @endif
                            <input type="file" name="photo" accept="image/*"
                                class="field-input {{ $errors->has('photo') ? 'has-error' : '' }}" />
                            @error('photo')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- ID Proof --}}
                        <div>
                            <label class="field-label">ID Proof (PAN/Aadhaar)</label>
                            @if ($employee->id_proof)
                                <div class="mb-2">
                                    <a href="{{ route('admin.hrm.employees.document', [$employee->id, 'id_proof']) }}"
                                        target="_blank"
                                        class="flex items-center gap-1 text-xs font-bold text-blue-600 hover:underline">
                                        <i data-lucide="external-link" class="h-3 w-3"></i> View Current Document
                                    </a>
                                </div>
                            @endif
                            <input type="file" name="id_proof" accept=".pdf,image/*"
                                class="field-input {{ $errors->has('id_proof') ? 'has-error' : '' }}" />
                            @error('id_proof')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Address Proof --}}
                        <div>
                            <label class="field-label">Address Proof</label>
                            @if ($employee->address_proof)
                                <div class="mb-2">
                                    <a href="{{ route('admin.hrm.employees.document', [$employee->id, 'address_proof']) }}"
                                        target="_blank"
                                        class="flex items-center gap-1 text-xs font-bold text-blue-600 hover:underline">
                                        <i data-lucide="external-link" class="h-3 w-3"></i> View Current Document
                                    </a>
                                </div>
                            @endif
                            <input type="file" name="address_proof" accept=".pdf,image/*"
                                class="field-input {{ $errors->has('address_proof') ? 'has-error' : '' }}" />
                            @error('address_proof')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════
             Sticky Footer — Cancel + Update
        ══════════════════════════════════ --}}
            <div class="sticky-footer">
                <a href="{{ route('admin.hrm.employees.show', $employee) }}"
                    class="flex items-center justify-center rounded-xl border border-gray-200 px-5 py-2.5 text-[13px] font-bold text-gray-600 transition-colors hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit"
                    class="flex items-center justify-center gap-2 rounded-xl px-6 py-2.5 text-[14px] font-bold text-white transition-opacity hover:opacity-90"
                    style="background: var(--brand-600)">
                    <i data-lucide="check" style="width: 16px; height: 16px"></i>
                    Update Employee
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        function empCodeCheck() {
            return {
                code: "{{ old('employee_code', $employee->employee_code) }}",
                original: "{{ $employee->employee_code }}",
                excludeId: {{ $employee->id }},
                status: "",
                message: "",
                checkUrl: "{{ route('admin.hrm.employees.check-code') }}",

                init() {
                    // No check on load — original value is already valid for this employee.
                },

                async check() {
                    const val = this.code.trim();

                    // Unchanged from original — no need to check.
                    if (val === this.original) {
                        this.status = "";
                        this.message = "";
                        return;
                    }

                    if (val === "") {
                        this.status = "taken";
                        this.message = "Code cannot be empty.";
                        return;
                    }

                    this.status = "checking";
                    this.message = "";

                    try {
                        const url = `${this.checkUrl}?code=${encodeURIComponent(val)}&exclude=${this.excludeId}`;
                        const res = await fetch(url, {
                            headers: {
                                Accept: "application/json"
                            }
                        });
                        const data = await res.json();
                        this.status = data.available ? "available" : "taken";
                        this.message = data.message;
                    } catch (e) {
                        this.status = "";
                        this.message = "";
                    }
                },
            };
        }
    </script>
@endpush
