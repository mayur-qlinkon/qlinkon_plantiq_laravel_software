@extends('layouts.admin')

@section('title', 'Shifts')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Shifts</h1>
        {{-- <p class="text-xs text-gray-400 font-medium mt-0.5">Manage work shifts and timings</p> --}}
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

    <div class="pb-10" x-data="shiftPage()">

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
            <div class="stat-card">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total Shifts</p>
                <p class="text-2xl font-black text-gray-900">{{ $shifts->total() }}</p>
            </div>
            <div class="stat-card">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Active</p>
                <p class="text-2xl font-black text-green-600">{{ $shifts->where('is_active', true)->count() }}</p>
            </div>
            <div class="stat-card">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Night Shifts</p>
                <p class="text-2xl font-black text-indigo-600">{{ $shifts->where('is_night_shift', true)->count() }}</p>
            </div>
            <div class="stat-card">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Employees</p>
                <p class="text-2xl font-black text-gray-900">{{ $shifts->sum('employees_count') }}</p>
            </div>
        </div>

        <div class="bg-white border border-gray-100 rounded-2xl px-4 py-3 mb-4">
            <div class="flex items-center gap-3 flex-wrap">
                <div class="relative flex-1 min-w-[180px]">
                    <i data-lucide="search"
                        class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"></i>
                    <input type="text" x-model="searchQuery" placeholder="Search shifts..."
                        class="field-input pl-9 !py-2 !text-[13px]">
                </div>
                @if (has_permission('shifts.create'))
                    <button @click="openCreate()"
                        class="ml-auto inline-flex items-center gap-1.5 text-[12px] font-bold px-4 py-2 rounded-lg text-white hover:opacity-90 transition-opacity"
                        style="background: var(--brand-600)">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Shift
                    </button>
                @endif
            </div>
        </div>

        <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full block md:table">
                    <thead class="hidden md:table-header-group">
                        <tr class="border-b border-gray-100">
                            <th
                                class="px-5 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider w-[50px]">
                                #</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Shift</th>
                            <th class="px-3 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Timing</th>
                            <th class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Break</th>
                            <th class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Employees</th>
                            <th class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Type</th>
                            <th class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Status</th>
                            <th class="px-4 py-3 text-right text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="block md:table-row-group p-4 md:p-0">
                        @forelse($shifts as $shift)
                            <tr class="block md:table-row bg-white border border-gray-200 md:border-0 md:border-b md:border-gray-100 rounded-xl md:rounded-none mb-4 md:mb-0 p-4 md:p-0 shadow-sm md:shadow-none hover:bg-gray-50 transition-colors"
                                x-show="matchesSearch('{{ strtolower($shift->name) }}')">
                                <td class="hidden md:table-cell px-5 py-3 text-[12px] font-bold text-gray-400">
                                    {{ $shifts->firstItem() + $loop->index }}</td>
                                <td class="block md:table-cell md:px-5 md:py-3 mb-3 md:mb-0">
                                    <div class="flex items-center gap-2">
                                        <div>
                                            <p class="text-[13px] font-bold text-gray-800">{{ $shift->name }}</p>
                                            @if ($shift->code)
                                                <span class="text-[10px] font-bold text-gray-400">{{ $shift->code }}</span>
                                            @endif
                                        </div>
                                        @if ($shift->is_default)
                                            <span
                                                class="text-[9px] font-extrabold uppercase tracking-wider text-white px-1.5 py-0.5 rounded"
                                                style="background: var(--brand-600)">Default</span>
                                        @endif
                                    </div>
                                </td>
                                <td
                                    class="flex justify-between items-center md:table-cell md:px-3 md:py-3 mb-2 md:mb-0 border-b border-gray-50 md:border-none pb-2 md:pb-0">
                                    <span
                                        class="md:hidden text-[11px] font-bold text-gray-400 uppercase tracking-wider">Timing</span>
                                    <div class="text-right md:text-left">
                                        <p class="text-[12px] font-bold text-gray-700">{{ $shift->formatted_timing }}</p>
                                        @if ($shift->late_mark_after)
                                            <p class="text-[10px] text-gray-400">Late after
                                                {{ \Carbon\Carbon::parse($shift->late_mark_after)->format('h:i A') }}</p>
                                        @endif
                                    </div>
                                </td>
                                <td
                                    class="flex justify-between items-center md:table-cell md:px-3 md:py-3 mb-2 md:mb-0 md:text-center border-b border-gray-50 md:border-none pb-2 md:pb-0">
                                    <span
                                        class="md:hidden text-[11px] font-bold text-gray-400 uppercase tracking-wider">Break</span>
                                    <span class="text-[12px] text-gray-600">{{ $shift->break_duration_minutes }} min</span>
                                </td>
                                <td
                                    class="flex justify-between items-center md:table-cell md:px-3 md:py-3 mb-2 md:mb-0 md:text-center border-b border-gray-50 md:border-none pb-2 md:pb-0">
                                    <span
                                        class="md:hidden text-[11px] font-bold text-gray-400 uppercase tracking-wider">Employees</span>
                                    <span class="text-[12px] font-bold text-gray-700">{{ $shift->employees_count }}</span>
                                </td>
                                <td
                                    class="flex justify-between items-center md:table-cell md:px-3 md:py-3 mb-2 md:mb-0 md:text-center border-b border-gray-50 md:border-none pb-2 md:pb-0">
                                    <span
                                        class="md:hidden text-[11px] font-bold text-gray-400 uppercase tracking-wider">Type</span>
                                    @if ($shift->is_night_shift)
                                        <span
                                            class="inline-flex items-center gap-1 text-[10px] font-extrabold uppercase tracking-wider text-indigo-700 bg-indigo-50 border border-indigo-200 px-2.5 py-1 rounded-md">
                                            <i data-lucide="moon" class="w-3 h-3"></i> Night
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-1 text-[10px] font-extrabold uppercase tracking-wider text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-md">
                                            <i data-lucide="sun" class="w-3 h-3"></i> Day
                                        </span>
                                    @endif
                                </td>
                                <td
                                    class="flex justify-between items-center md:table-cell md:px-3 md:py-3 mb-4 md:mb-0 md:text-center">
                                    <span
                                        class="md:hidden text-[11px] font-bold text-gray-400 uppercase tracking-wider">Status</span>
                                    @if ($shift->is_active)
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
                                        @if (has_permission('shifts.update'))
                                            <button @click="openEdit({{ $shift->toJson() }})"
                                                class="w-[30px] h-[30px] rounded-lg flex items-center justify-center bg-blue-50 text-blue-500 hover:bg-blue-100 hover:text-blue-700 transition-colors">
                                                <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                            </button>
                                        @endif
                                        @if (has_permission('shifts.delete'))
                                            <button @click="confirmDelete({{ $shift->id }}, '{{ $shift->name }}')"
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
                                            <i data-lucide="clock" class="w-7 h-7 text-gray-300"></i>
                                        </div>
                                        <p class="font-semibold text-gray-500 mb-1">No shifts configured</p>
                                        <p class="text-sm text-gray-400 mb-4">Set up work shifts for your organization</p>
                                        <button @click="openCreate()"
                                            class="text-sm font-bold px-4 py-2 rounded-xl text-white"
                                            style="background: var(--brand-600)">Add First Shift</button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($shifts->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">{{ $shifts->links() }}</div>
            @endif
        </div>

        {{-- Modal --}}
        <div x-show="modalOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="bg-white w-full max-w-2xl rounded-xl shadow-2xl overflow-hidden max-h-[90vh] overflow-y-auto"
                @click.away="modalOpen = false" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

                <div
                    class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50 sticky top-0 z-10">
                    <h3 class="font-black text-gray-800 uppercase tracking-widest text-sm"
                        x-text="isEditing ? 'Edit Shift' : 'New Shift'"></h3>
                    <button @click="modalOpen = false" class="text-gray-400 hover:text-red-500 transition-colors"><i
                            data-lucide="x" class="w-5 h-5"></i></button>
                </div>

                <form @submit.prevent="submitForm()">
                    <div class="p-6 space-y-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="field-label">Shift Name <span class="text-red-400">*</span></label>
                                <input type="text" x-model="form.name" class="field-input"
                                    placeholder="e.g. General Shift" required>
                                <p class="field-error" x-show="errors.name" x-text="errors.name"></p>
                            </div>
                            <div>
                                <label class="field-label">Code</label>
                                <input type="text" x-model="form.code" class="field-input" placeholder="e.g. GEN">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="field-label">Start Time <span class="text-red-400">*</span></label>
                                <input type="time" x-model="form.start_time" class="field-input" required>
                            </div>
                            <div>
                                <label class="field-label">End Time <span class="text-red-400">*</span></label>
                                <input type="time" x-model="form.end_time" class="field-input" required>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="field-label">Late Mark After</label>
                                <input type="time" x-model="form.late_mark_after" class="field-input">
                            </div>
                            <div>
                                <label class="field-label">Early Leave Before</label>
                                <input type="time" x-model="form.early_leave_before" class="field-input">
                            </div>
                            <div>
                                <label class="field-label">Half Day After</label>
                                <input type="time" x-model="form.half_day_after" class="field-input">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="field-label">Break (minutes)</label>
                                <input type="text" inputmode="numeric" data-int data-int-min="0"
                                    x-model="form.break_duration_minutes" class="field-input" placeholder="30">
                            </div>
                            <div>
                                <label class="field-label">Min Work Hours (min)</label>
                                <input type="text" inputmode="numeric" data-int data-int-min="0"
                                    x-model="form.min_working_hours_minutes" class="field-input" placeholder="480">
                            </div>
                            <div>
                                <label class="field-label">OT After (min)</label>
                                <input type="text" inputmode="numeric" data-int data-int-min="0"
                                    x-model="form.overtime_after_minutes" class="field-input" placeholder="540">
                            </div>
                        </div>

                        <div>
                            <label class="field-label">Description</label>
                            <textarea x-model="form.description" class="field-input" rows="2" placeholder="Optional shift description"></textarea>
                        </div>

                        {{-- Weekly off roster. Payroll divides pay by the number of
                         days this roster says were owed, so these boxes decide
                         what a day is worth, not just who is expected in. --}}
                        <div class="border border-gray-100 rounded-xl p-3.5">
                            <label class="field-label">Weekly Off Days</label>
                            <div class="flex flex-wrap gap-2 mt-1">
                                @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayNum => $dayName)
                                    <label
                                        class="flex items-center gap-1.5 rounded-lg border border-gray-200 px-2.5 py-1.5 cursor-pointer text-[12px] font-semibold text-gray-600 has-[:checked]:border-green-400 has-[:checked]:bg-green-50 has-[:checked]:text-green-700">
                                        <input type="checkbox" value="{{ $dayNum }}"
                                            x-model.number="form.weekly_off_days" class="accent-green-600">
                                        {{ $dayName }}
                                    </label>
                                @endforeach
                            </div>

                            <label class="field-label mt-3.5">Alternate Saturdays Off</label>
                            <div class="flex flex-wrap gap-2 mt-1">
                                @foreach (['1st', '2nd', '3rd', '4th', '5th'] as $index => $label)
                                    <label
                                        class="flex items-center gap-1.5 rounded-lg border border-gray-200 px-2.5 py-1.5 cursor-pointer text-[12px] font-semibold text-gray-600 has-[:checked]:border-green-400 has-[:checked]:bg-green-50 has-[:checked]:text-green-700">
                                        <input type="checkbox" value="{{ $index + 1 }}"
                                            x-model.number="form.alternate_saturday_offs" class="accent-green-600">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            <p class="text-[11px] text-gray-400 mt-2">
                                Leave these clear unless only some Saturdays are off. Ticking Sat above already makes every
                                Saturday a weekly off.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2.5">
                                <span class="text-[12px] font-bold text-gray-600">Night Shift</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" x-model="form.is_night_shift">
                                    <span class="toggle-track"></span><span class="toggle-thumb"></span>
                                </label>
                            </div>
                            <div class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2.5">
                                <span class="text-[12px] font-bold text-gray-600">Default</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" x-model="form.is_default">
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

                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3 sticky bottom-0">
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
        window.shiftPage = function() {
            return {
                modalOpen: false,
                isEditing: false,
                editId: null,
                saving: false,
                searchQuery: '',
                errors: {},
                form: {
                    name: '',
                    code: '',
                    description: '',
                    start_time: '09:00',
                    end_time: '18:00',
                    late_mark_after: '',
                    early_leave_before: '',
                    half_day_after: '',
                    break_duration_minutes: 0,
                    min_working_hours_minutes: 480,
                    overtime_after_minutes: '',
                    weekly_off_days: [0],
                    alternate_saturday_offs: [],
                    is_night_shift: false,
                    is_default: false,
                    is_active: true
                },

                init() {
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
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
                        start_time: '09:00',
                        end_time: '18:00',
                        late_mark_after: '',
                        early_leave_before: '',
                        half_day_after: '',
                        break_duration_minutes: 0,
                        min_working_hours_minutes: 480,
                        overtime_after_minutes: '',
                        weekly_off_days: [0],
                        alternate_saturday_offs: [],
                        is_night_shift: false,
                        is_default: false,
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
                        start_time: item.start_time?.substring(0, 5) || '09:00',
                        end_time: item.end_time?.substring(0, 5) || '18:00',
                        late_mark_after: item.late_mark_after?.substring(0, 5) || '',
                        early_leave_before: item.early_leave_before?.substring(0, 5) || '',
                        half_day_after: item.half_day_after?.substring(0, 5) || '',
                        break_duration_minutes: item.break_duration_minutes || 0,
                        min_working_hours_minutes: item.min_working_hours_minutes || 480,
                        overtime_after_minutes: item.overtime_after_minutes || '',
                        weekly_off_days: item.weekly_off_days || [],
                        alternate_saturday_offs: item.alternate_saturday_offs || [],
                        is_night_shift: item.is_night_shift,
                        is_default: item.is_default,
                        is_active: item.is_active
                    };
                    this.modalOpen = true;
                },
                async submitForm() {
                    this.saving = true;
                    this.errors = {};
                    const url = this.isEditing ? `{{ url('admin/hrm/shifts') }}/${this.editId}` :
                        `{{ route('admin.hrm.shifts.store') }}`;
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
                                is_night_shift: this.form.is_night_shift ? 1 : 0,
                                is_default: this.form.is_default ? 1 : 0,
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
                    BizAlert.confirm('Delete Shift', `Delete "${name}"?`, 'Delete').then(async (r) => {
                        if (!r.isConfirmed) return;
                        try {
                            const resp = await fetch(`{{ url('admin/hrm/shifts') }}/${id}`, {
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
