@extends('layouts.admin')

@section('title', 'Attendance Rules')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Attendance Rules</h1>
        {{-- <p class="text-xs text-gray-400 font-medium mt-0.5">Configure automated attendance policies and penalties</p> --}}
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
            transition: border-color 150ms ease, box-shadow 150ms ease;
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

        .toggle-switch {
            position: relative;
            width: 36px;
            height: 20px;
            flex-shrink: 0;
        }

        .toggle-switch input {
            display: none;
        }

        .toggle-track {
            position: absolute;
            inset: 0;
            background: #e5e7eb;
            border-radius: 20px;
            cursor: pointer;
            transition: background 200ms ease;
        }

        .toggle-switch input:checked+.toggle-track {
            background: var(--brand-600);
        }

        .toggle-thumb {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 16px;
            height: 16px;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            transition: transform 200ms ease;
            pointer-events: none;
        }

        .toggle-switch input:checked~.toggle-thumb {
            transform: translateX(16px);
        }

        .table-row {
            border-bottom: 1px solid #f8fafc;
            transition: background 100ms;
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
            transition: box-shadow 150ms, border-color 150ms;
        }

        .stat-card:hover {
            border-color: #e2e8f0;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }
    </style>
@endpush

@section('content')

    <div class="pb-10" x-data="attendanceRulePage()">

        @if (has_module('hrm'))
            {{-- Holiday Attendance Behavior --}}
            <div class="bg-white border border-gray-100 rounded-2xl px-4 py-4 mb-4">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="flex-1">
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Holiday Attendance
                            Behavior</p>
                        <p class="text-[12px] text-gray-500">Controls what happens when employees try to mark attendance on a
                            configured holiday.</p>
                    </div>
                    <div class="flex items-center gap-2 sm:min-w-[360px]">
                        <div class="flex-1" :class="savingPolicy ? 'opacity-50 pointer-events-none' : ''">
                            <x-alpine-select model="holidayPolicy" items="holidayPolicyOptions"
                                on-change="saveHolidayPolicy()" :allow-empty="false" />
                        </div>
                        <span x-show="savingPolicy"
                            class="text-[11px] font-bold text-gray-500 whitespace-nowrap">Saving…</span>
                    </div>
                </div>
            </div>
        @endif

        {{-- Stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-5">
            <div class="stat-card">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total Rules</p>
                <p class="text-2xl font-black text-gray-900">{{ $rules->total() }}</p>
            </div>
            <div class="stat-card">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Active</p>
                <p class="text-2xl font-black text-green-600">{{ $rules->where('is_active', true)->count() }}</p>
            </div>
            <div class="stat-card">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Auto-Apply</p>
                <p class="text-2xl font-black text-blue-600">{{ $rules->where('auto_apply', true)->count() }}</p>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="bg-white border border-gray-100 rounded-2xl px-4 py-3 mb-4">
            <div class="flex items-center gap-3 flex-wrap">
                <div class="relative flex-1 min-w-[180px]">
                    <i data-lucide="search"
                        class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"></i>
                    <input type="text" x-model="searchQuery" placeholder="Search rules..."
                        class="field-input pl-9 !py-2 !text-[13px]">
                </div>
                @if (has_permission('attendance_rules.create'))
                    <button @click="openCreate()"
                        class="ml-auto inline-flex items-center gap-1.5 text-[12px] font-bold px-4 py-2 rounded-lg text-white hover:opacity-90 transition-opacity"
                        style="background: var(--brand-600)">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Rule
                    </button>
                @endif
            </div>
        </div>

        {{-- Table --}}
        <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full block md:table">
                    <thead class="hidden md:table-header-group">
                        <tr class="border-b border-gray-100">
                            <th
                                class="px-5 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider w-[50px]">
                                #</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Rule</th>
                            <th class="px-3 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Type</th>
                            <th class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Threshold</th>
                            <th class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Action</th>
                            <th class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Auto Apply</th>
                            <th class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Status</th>
                            <th class="px-4 py-3 text-right text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="block md:table-row-group p-4 md:p-0">
                        @forelse($rules as $rule)
                            <tr class="block md:table-row bg-white border border-gray-200 md:border-0 md:border-b md:border-gray-100 rounded-xl md:rounded-none mb-4 md:mb-0 p-4 md:p-0 shadow-sm md:shadow-none hover:bg-gray-50 transition-colors"
                                x-show="matchesSearch('{{ strtolower($rule->name) }}')">
                                <td class="hidden md:table-cell px-5 py-3 text-[12px] font-bold text-gray-400">
                                    {{ $rules->firstItem() + $loop->index }}</td>
                                <td class="block md:table-cell md:px-5 md:py-3 mb-3 md:mb-0">
                                    <div>
                                        <p class="text-[13px] font-bold text-gray-800">{{ $rule->name }}</p>
                                        @if ($rule->description)
                                            <p class="text-[11px] text-gray-400 mt-0.5 truncate max-w-[250px]">
                                                {{ $rule->description }}</p>
                                        @endif
                                    </div>
                                </td>
                                <td
                                    class="flex justify-between items-center md:table-cell md:px-3 md:py-3 mb-2 md:mb-0 md:text-center border-b border-gray-50 md:border-none pb-2 md:pb-0">
                                    <span
                                        class="md:hidden text-[11px] font-bold text-gray-400 uppercase tracking-wider">Type</span>
                                    <span
                                        class="text-[11px] font-bold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-md whitespace-nowrap">{{ \App\Models\Hrm\AttendanceRule::TYPE_LABELS[$rule->rule_type] ?? $rule->rule_type }}</span>
                                </td>
                                <td
                                    class="flex justify-between items-center md:table-cell md:px-3 md:py-3 mb-2 md:mb-0 md:text-center border-b border-gray-50 md:border-none pb-2 md:pb-0">
                                    <span
                                        class="md:hidden text-[11px] font-bold text-gray-400 uppercase tracking-wider">Threshold</span>
                                    <span
                                        class="text-[12px] font-bold text-gray-700 whitespace-nowrap">{{ $rule->threshold_count }}
                                        per {{ $rule->threshold_period }}</span>
                                </td>
                                <td
                                    class="flex justify-between items-center md:table-cell md:px-3 md:py-3 mb-2 md:mb-0 md:text-center border-b border-gray-50 md:border-none pb-2 md:pb-0">
                                    <span
                                        class="md:hidden text-[11px] font-bold text-gray-400 uppercase tracking-wider">Action</span>
                                    <span
                                        class="text-[11px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2.5 py-1 rounded-md whitespace-nowrap">{{ \App\Models\Hrm\AttendanceRule::ACTION_LABELS[$rule->action] ?? $rule->action }}</span>
                                </td>
                                <td
                                    class="flex justify-between items-center md:table-cell md:px-3 md:py-3 mb-2 md:mb-0 md:text-center border-b border-gray-50 md:border-none pb-2 md:pb-0">
                                    <span
                                        class="md:hidden text-[11px] font-bold text-gray-400 uppercase tracking-wider">Auto
                                        Apply</span>
                                    @if ($rule->auto_apply)
                                        <span
                                            class="text-[10px] font-extrabold uppercase tracking-wider text-blue-700 bg-blue-50 border border-blue-200 px-2.5 py-1 rounded-md whitespace-nowrap">Yes</span>
                                    @else
                                        <span
                                            class="text-[10px] font-extrabold uppercase tracking-wider text-gray-500 bg-gray-50 border border-gray-200 px-2.5 py-1 rounded-md whitespace-nowrap">No</span>
                                    @endif
                                </td>
                                <td
                                    class="flex justify-between items-center md:table-cell md:px-3 md:py-3 mb-4 md:mb-0 md:text-center">
                                    <span
                                        class="md:hidden text-[11px] font-bold text-gray-400 uppercase tracking-wider">Status</span>
                                    @if ($rule->is_active)
                                        <span
                                            class="inline-flex items-center text-[10px] font-extrabold uppercase tracking-wider text-green-700 bg-green-50 border border-green-200 px-2.5 py-1 rounded-md">Active</span>
                                    @else
                                        <span
                                            class="inline-flex items-center text-[10px] font-extrabold uppercase tracking-wider text-gray-500 bg-gray-50 border border-gray-200 px-2.5 py-1 rounded-md">Inactive</span>
                                    @endif
                                </td>
                                <td
                                    class="flex justify-end items-center md:table-cell pt-3 md:pt-0 border-t border-gray-100 md:border-none md:px-4 md:py-3 md:text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if (has_permission('attendance_rules.update'))
                                            <button @click="openEdit({{ $rule->toJson() }})"
                                                class="w-[30px] h-[30px] rounded-lg flex items-center justify-center bg-blue-50 text-blue-500 hover:bg-blue-100 hover:text-blue-700 transition-colors">
                                                <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                            </button>
                                        @endif
                                        @if (has_permission('attendance_rules.delete'))
                                            <button @click="confirmDelete({{ $rule->id }}, '{{ $rule->name }}')"
                                                class="w-[30px] h-[30px] rounded-lg flex items-center justify-center bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 transition-colors">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
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
                                            class="w-14 h-14 bg-gray-100 rounded-2xl flex items-center justify-center mb-3">
                                            <i data-lucide="shield" class="w-7 h-7 text-gray-300"></i>
                                        </div>
                                        <p class="font-semibold text-gray-500 mb-1">No attendance rules configured</p>
                                        <p class="text-sm text-gray-400 mb-4">Set up rules to automate attendance policies
                                        </p>
                                        <button @click="openCreate()"
                                            class="text-sm font-bold px-4 py-2 rounded-xl text-white"
                                            style="background: var(--brand-600)">Add First Rule</button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($rules->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">{{ $rules->links() }}</div>
            @endif
        </div>

        {{-- Modal --}}
        <div x-show="modalOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            {{-- Closes only via the header button or Escape. @click.away also fired
                 when a click merely ended outside the panel — dragging to select
                 text, or releasing past the edge — which discarded a filled form. --}}
            <div class="bg-white w-[95%] sm:w-full max-w-2xl flex flex-col rounded-xl shadow-2xl overflow-hidden max-h-[90vh] m-4"
                @keydown.escape.window="modalOpen && (modalOpen = false)"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100">

                <div
                    class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50 sticky top-0 z-10">
                    <h3 class="font-black text-gray-800 uppercase tracking-widest text-sm"
                        x-text="isEditing ? 'Edit Attendance Rule' : 'New Attendance Rule'"></h3>
                    <button @click="modalOpen = false" class="text-gray-400 hover:text-red-500 transition-colors"><i
                            data-lucide="x" class="w-5 h-5"></i></button>
                </div>

                <form @submit.prevent="submitForm()" class="flex flex-col flex-1 overflow-hidden">
                    <div class="p-5 sm:p-6 space-y-5 overflow-y-auto">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="field-label">Rule Name <span class="text-red-400">*</span></label>
                                <input type="text" x-model="form.name" class="field-input"
                                    placeholder="e.g. Late to Half Day" required>
                                <p class="field-error" x-show="errors.name" x-text="errors.name"></p>
                            </div>
                            <div>
                                <label class="field-label">Code</label>
                                <input type="text" x-model="form.code" class="field-input" placeholder="e.g. LT-HD">
                                <p class="field-error" x-show="errors.code" x-text="errors.code"></p>
                            </div>
                        </div>

                        <div>
                            <label class="field-label">Rule Type <span class="text-red-400">*</span></label>
                            <x-alpine-select name="rule_type" model="form.rule_type" items="ruleTypeOptions"
                                placeholder="Select Type" :required="true" :allow-empty="true" />
                            <p class="field-error" x-show="errors.rule_type" x-text="errors.rule_type"></p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="field-label">Threshold Count <span class="text-red-400">*</span></label>
                                <input type="number" x-model="form.threshold_count" class="field-input" min="1"
                                    placeholder="e.g. 3" required>
                                <p class="field-error" x-show="errors.threshold_count" x-text="errors.threshold_count">
                                </p>
                            </div>
                            <div>
                                <label class="field-label">Threshold Period <span class="text-red-400">*</span></label>
                                {{-- Same reasoning as the Action field: four fixed options shown
                                 inline instead of a menu the scrolling modal would clip. --}}
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach (\App\Models\Hrm\AttendanceRule::PERIOD_LABELS as $val => $label)
                                        <button type="button" @click="form.threshold_period = '{{ $val }}'"
                                            :class="form.threshold_period === '{{ $val }}' ?
                                                'border-[var(--brand-600)] bg-[color-mix(in_srgb,var(--brand-600)_8%,transparent)] text-[var(--brand-600)]' :
                                                'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50'"
                                            class="flex items-center justify-center rounded-[10px] border-[1.5px] px-3 py-2.5 text-[13px] font-bold transition-all">
                                            {{ $label }}
                                        </button>
                                    @endforeach
                                </div>
                                <p class="field-error" x-show="errors.threshold_period" x-text="errors.threshold_period">
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="field-label">Action <span class="text-red-400">*</span></label>
                            {{-- Shown as a grid rather than a dropdown: the modal body scrolls,
                             which clips any floating menu, and its @click.away would close
                             the whole modal. With five mutually exclusive actions, showing
                             them all is clearer than hiding them behind a control anyway. --}}
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                @foreach (\App\Models\Hrm\AttendanceRule::ACTION_LABELS as $val => $label)
                                    <button type="button" @click="form.action = '{{ $val }}'"
                                        :class="form.action === '{{ $val }}' ?
                                            'border-[var(--brand-600)] bg-[color-mix(in_srgb,var(--brand-600)_8%,transparent)] text-[var(--brand-600)]' :
                                            'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50'"
                                        class="flex items-center justify-center rounded-[10px] border-[1.5px] px-3 py-2.5 text-[13px] font-bold transition-all">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                            <p class="field-error" x-show="errors.action" x-text="errors.action"></p>
                        </div>

                        {{-- Conditional fields for deduct_leave action --}}
                        <div class="grid grid-cols-2 gap-4" x-show="form.action === 'deduct_leave'" x-transition>
                            <div>
                                <label class="field-label">Deduction Days <span class="text-red-400">*</span></label>
                                <input type="number" x-model="form.deduction_days" class="field-input" min="0"
                                    step="0.5" placeholder="e.g. 1">
                                <p class="field-error" x-show="errors.deduction_days" x-text="errors.deduction_days"></p>
                            </div>
                            <div>
                                <label class="field-label">Leave Type Code</label>
                                <input type="text" x-model="form.leave_type_code" class="field-input"
                                    placeholder="e.g. CL">
                                <p class="field-error" x-show="errors.leave_type_code" x-text="errors.leave_type_code">
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="field-label">Description</label>
                            <textarea x-model="form.description" class="field-input" rows="2"
                                placeholder="Optional description of this rule"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2.5">
                                <span class="text-[12px] font-bold text-gray-600">Auto Apply</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" x-model="form.auto_apply">
                                    <span class="toggle-track"></span><span class="toggle-thumb"></span>
                                </label>
                            </div>
                            <div class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2.5">
                                <span class="text-[12px] font-bold text-gray-600">Active</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" x-model="form.is_active">
                                    <span class="toggle-track"></span><span class="toggle-thumb"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div
                        class="px-5 sm:px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3 flex-shrink-0">
                        <button type="button" @click="modalOpen = false"
                            class="px-4 py-2 text-[13px] font-bold text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
                        <button type="submit" :disabled="saving"
                            class="px-5 py-2 text-[13px] font-bold text-white rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50"
                            style="background: var(--brand-600)">
                            <span x-show="!saving" x-text="isEditing ? 'Update' : 'Create'"></span>
                            <span x-show="saving" class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
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
        window.attendanceRulePage = function() {
            return {
                modalOpen: false,
                isEditing: false,
                editId: null,
                saving: false,
                searchQuery: '',
                errors: {},
                holidayPolicy: @json($holidayPolicy ?? 'block'),
                savingPolicy: false,

                // Select Data Options
                holidayPolicyOptions: [{
                        id: 'block',
                        name: 'Block attendance on holidays'
                    },
                    {
                        id: 'allow',
                        name: 'Allow attendance (mark as Working on Holiday)'
                    },
                    {
                        id: 'approval',
                        name: 'Allow attendance but require approval'
                    }
                ],
                ruleTypeOptions: @js(collect(\App\Models\Hrm\AttendanceRule::TYPE_LABELS)->map(fn($label, $val) => ['id' => $val, 'name' => $label])->values()),

                form: {
                    name: '',
                    code: '',
                    description: '',
                    rule_type: '',
                    threshold_count: '',
                    threshold_period: '',
                    action: '',
                    deduction_days: '',
                    leave_type_code: '',
                    auto_apply: true,
                    is_active: true,
                },

                init() {
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                },
                async saveHolidayPolicy() {
                    this.savingPolicy = true;
                    try {
                        const resp = await fetch(`{{ route('admin.hrm.attendance-rules.holiday-policy') }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                holiday_policy: this.holidayPolicy
                            }),
                        });
                        const data = await resp.json();
                        if (!resp.ok) {
                            BizAlert.toast(data.message || 'Failed to save policy', 'error');
                            return;
                        }
                        BizAlert.toast(data.message, 'success');
                    } catch (e) {
                        BizAlert.toast('Network error', 'error');
                    } finally {
                        this.savingPolicy = false;
                    }
                },
                matchesSearch(name) {
                    if (!this.searchQuery) return true;
                    return name.includes(this.searchQuery.toLowerCase());
                },
                resetForm() {
                    this.form = {
                        name: '',
                        code: '',
                        description: '',
                        rule_type: '',
                        threshold_count: '',
                        threshold_period: '',
                        action: '',
                        deduction_days: '',
                        leave_type_code: '',
                        auto_apply: true,
                        is_active: true
                    };
                    this.errors = {};
                },
                openCreate() {
                    this.resetForm();
                    this.isEditing = false;
                    this.editId = null;
                    this.modalOpen = true;
                },
                openEdit(item) {
                    this.resetForm();
                    this.isEditing = true;
                    this.editId = item.id;
                    this.form = {
                        name: item.name,
                        code: item.code || '',
                        description: item.description || '',
                        rule_type: item.rule_type,
                        threshold_count: item.threshold_count,
                        threshold_period: item.threshold_period,
                        action: item.action,
                        deduction_days: item.deduction_days || '',
                        leave_type_code: item.leave_type_code || '',
                        auto_apply: item.auto_apply,
                        is_active: item.is_active,
                    };
                    this.modalOpen = true;
                },
                async submitForm() {
                    this.saving = true;
                    this.errors = {};
                    const url = this.isEditing ? `{{ url('admin/hrm/attendance-rules') }}/${this.editId}` :
                        `{{ route('admin.hrm.attendance-rules.store') }}`;
                    try {
                        const resp = await fetch(url, {
                            method: this.isEditing ? 'PUT' : 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                ...this.form,
                                auto_apply: this.form.auto_apply ? 1 : 0,
                                is_active: this.form.is_active ? 1 : 0
                            }),
                        });
                        const data = await resp.json();
                        if (!resp.ok) {
                            if (resp.status === 422 && data.errors) {
                                for (const [k, m] of Object.entries(data.errors)) this.errors[k] = m[0];
                            } else BizAlert.toast(data.message || 'Error', 'error');
                            return;
                        }
                        BizAlert.toast(data.message, 'success');
                        this.modalOpen = false;
                        setTimeout(() => window.location.reload(), 600);
                    } catch (e) {
                        BizAlert.toast('Network error', 'error');
                    } finally {
                        this.saving = false;
                    }
                },
                confirmDelete(id, name) {
                    BizAlert.confirm('Delete Rule', `Delete "${name}"?`, 'Delete').then(async (r) => {
                        if (!r.isConfirmed) return;
                        try {
                            const resp = await fetch(`{{ url('admin/hrm/attendance-rules') }}/${id}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector(
                                        'meta[name="csrf-token"]').content,
                                    'Accept': 'application/json'
                                }
                            });
                            const data = await resp.json();
                            if (!resp.ok) {
                                BizAlert.toast(data.message || 'Cannot delete', 'error');
                                return;
                            }
                            BizAlert.toast(data.message, 'success');
                            setTimeout(() => window.location.reload(), 600);
                        } catch (e) {
                            BizAlert.toast('Network error', 'error');
                        }
                    });
                },
            };
        };
    </script>
@endpush
