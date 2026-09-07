@extends('layouts.admin')

@section('title', 'Leave Types')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Leave Types</h1>
        {{-- <p class="text-xs text-gray-400 font-medium mt-0.5">Configure leave categories and policies</p> --}}
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

    <div class="pb-10" x-data="leaveTypePage()">

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
            <div class="stat-card">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total</p>
                <p class="text-2xl font-black text-gray-900">{{ $leaveTypes->total() }}</p>
            </div>
            <div class="stat-card">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Active</p>
                <p class="text-2xl font-black text-green-600">{{ $leaveTypes->where('is_active', true)->count() }}</p>
            </div>
            <div class="stat-card">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Paid Types</p>
                <p class="text-2xl font-black text-blue-600">{{ $leaveTypes->where('is_paid', true)->count() }}</p>
            </div>
            <div class="stat-card">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Unpaid Types</p>
                <p class="text-2xl font-black text-orange-600">{{ $leaveTypes->where('is_paid', false)->count() }}</p>
            </div>
        </div>

        <div class="bg-white border border-gray-100 rounded-2xl px-4 py-3 mb-4">
            <div class="flex items-center gap-3 flex-wrap">
                <div class="relative flex-1 min-w-[180px]">
                    <i data-lucide="search"
                        class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"></i>
                    <input type="text" x-model="searchQuery" placeholder="Search leave types..."
                        class="field-input pl-9 !py-2 !text-[13px]">
                </div>
                <button @click="openCreate()"
                    class="ml-auto inline-flex items-center gap-1.5 text-[12px] font-bold px-4 py-2 rounded-lg text-white hover:opacity-90 transition-opacity"
                    style="background: var(--brand-600)">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Leave Type
                </button>
            </div>
        </div>

        <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">

            {{-- 🖥️ DESKTOP VIEW (TABLE) ── --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th
                                class="px-5 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider w-[50px]">
                                #</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Leave Type</th>
                            <th class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Days/Year</th>
                            <th class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Paid</th>
                            <th class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Carry Forward</th>
                            <th class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Gender</th>
                            <th class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Status</th>
                            <th class="px-4 py-3 text-right text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaveTypes as $leaveType)
                            <tr class="table-row" x-show="matchesSearch('{{ strtolower($leaveType->name) }}')">
                                <td class="px-5 py-3 text-[12px] font-bold text-gray-400">
                                    {{ $leaveTypes->firstItem() + $loop->index }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <div>
                                            <p class="text-[13px] font-bold text-gray-800">{{ $leaveType->name }}</p>
                                            <span class="text-[10px] font-bold text-gray-400">{{ $leaveType->code }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span
                                        class="text-[13px] font-bold text-gray-700">{{ $leaveType->default_days_per_year }}</span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if ($leaveType->is_paid)
                                        <span
                                            class="text-[10px] font-extrabold uppercase tracking-wider text-green-700 bg-green-50 border border-green-200 px-2 py-0.5 rounded">Yes</span>
                                    @else
                                        <span
                                            class="text-[10px] font-extrabold uppercase tracking-wider text-gray-500 bg-gray-50 border border-gray-200 px-2 py-0.5 rounded">No</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if ($leaveType->is_carry_forward)
                                        <span
                                            class="text-[10px] font-extrabold uppercase tracking-wider text-blue-700 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded">Yes</span>
                                    @else
                                        <span
                                            class="text-[10px] font-extrabold uppercase tracking-wider text-gray-500 bg-gray-50 border border-gray-200 px-2 py-0.5 rounded">No</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span
                                        class="text-[10px] font-extrabold uppercase tracking-wider text-gray-600 bg-gray-50 border border-gray-200 px-2.5 py-1 rounded-md">{{ ucfirst($leaveType->applicable_gender) }}</span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if ($leaveType->is_active)
                                        <span
                                            class="inline-flex items-center text-[10px] font-extrabold uppercase tracking-wider text-green-700 bg-green-50 border border-green-200 px-2.5 py-1 rounded-md">Active</span>
                                    @else
                                        <span
                                            class="inline-flex items-center text-[10px] font-extrabold uppercase tracking-wider text-gray-500 bg-gray-50 border border-gray-200 px-2.5 py-1 rounded-md">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button @click="openEdit({{ $leaveType->toJson() }})"
                                            class="w-[30px] h-[30px] rounded-lg flex items-center justify-center bg-blue-50 text-blue-500 hover:bg-blue-100 hover:text-blue-700 transition-colors">
                                            <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                        </button>
                                        <button @click="confirmDelete({{ $leaveType->id }}, '{{ $leaveType->name }}')"
                                            class="w-[30px] h-[30px] rounded-lg flex items-center justify-center bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 transition-colors">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="flex flex-col items-center justify-center py-20 text-center">
                                        <div
                                            class="w-14 h-14 bg-gray-100 rounded-2xl flex items-center justify-center mb-3">
                                            <i data-lucide="calendar-off" class="w-7 h-7 text-gray-300"></i>
                                        </div>
                                        <p class="font-semibold text-gray-500 mb-1">No leave types configured</p>
                                        <p class="text-sm text-gray-400 mb-4">Set up leave categories for your organization
                                        </p>
                                        <button @click="openCreate()"
                                            class="text-sm font-bold px-4 py-2 rounded-xl text-white"
                                            style="background: var(--brand-600)">Add First Leave Type</button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 📱 MOBILE VIEW (CARDS) ── --}}
            <div class="md:hidden divide-y divide-gray-50 border-t border-gray-50 bg-white">
                @forelse($leaveTypes as $leaveType)
                    <div class="p-4 hover:bg-gray-50/50 transition-colors flex flex-col gap-3"
                        x-show="matchesSearch('{{ strtolower(addslashes($leaveType->name)) }}')">

                        {{-- Header: Name, Code & Status --}}
                        <div class="flex justify-between items-start gap-2">
                            <div class="min-w-0">
                                <p class="font-bold text-[14px] text-gray-900 truncate">{{ $leaveType->name }}</p>
                                <p class="text-[11px] text-gray-500 mt-0.5 font-mono">Code: {{ $leaveType->code }}</p>
                            </div>
                            <div class="shrink-0 flex flex-col items-end gap-1">
                                @if ($leaveType->is_active)
                                    <span
                                        class="inline-flex items-center text-[9px] font-extrabold uppercase tracking-wider text-green-700 bg-green-50 border border-green-200 px-1.5 py-0.5 rounded-md">Active</span>
                                @else
                                    <span
                                        class="inline-flex items-center text-[9px] font-extrabold uppercase tracking-wider text-gray-500 bg-gray-50 border border-gray-200 px-1.5 py-0.5 rounded-md">Inactive</span>
                                @endif
                            </div>
                        </div>

                        {{-- Context: Settings --}}
                        <div class="flex flex-col gap-2 bg-gray-50/80 px-3 py-2.5 rounded-lg border border-gray-100">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Days/Year</span>
                                <span
                                    class="text-[14px] font-black text-gray-800">{{ $leaveType->default_days_per_year }}</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-gray-100/50 mt-1">
                                @if ($leaveType->is_paid)
                                    <span
                                        class="text-[9px] font-extrabold uppercase tracking-wider text-green-700 bg-green-50 border border-green-200 px-1.5 py-0.5 rounded">Paid</span>
                                @else
                                    <span
                                        class="text-[9px] font-extrabold uppercase tracking-wider text-gray-500 bg-gray-50 border border-gray-200 px-1.5 py-0.5 rounded">Unpaid</span>
                                @endif

                                @if ($leaveType->is_carry_forward)
                                    <span
                                        class="text-[9px] font-extrabold uppercase tracking-wider text-blue-700 bg-blue-50 border border-blue-200 px-1.5 py-0.5 rounded">Carry
                                        Forward</span>
                                @endif

                                <span
                                    class="text-[9px] font-extrabold uppercase tracking-wider text-gray-600 bg-white border border-gray-200 px-1.5 py-0.5 rounded ml-auto">
                                    Gender: {{ ucfirst($leaveType->applicable_gender) }}
                                </span>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-end gap-2 pt-1 border-t border-gray-50 mt-1">
                            <button @click="openEdit({{ $leaveType->toJson() }})"
                                class="w-8 h-8 rounded-lg flex items-center justify-center bg-blue-50 text-blue-500 hover:bg-blue-100 hover:text-blue-700 transition-colors"
                                title="Edit">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </button>
                            <button @click="confirmDelete({{ $leaveType->id }}, '{{ addslashes($leaveType->name) }}')"
                                class="w-8 h-8 rounded-lg flex items-center justify-center bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 transition-colors"
                                title="Delete">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>

                    </div>
                @empty
                    <div class="p-8 text-center text-gray-400 bg-white">
                        <div class="flex flex-col items-center justify-center">
                            <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                                <i data-lucide="calendar-off" class="w-6 h-6 text-gray-300"></i>
                            </div>
                            <p class="font-semibold text-gray-500 mb-1 text-sm">No leave types configured</p>
                            <p class="text-xs mt-1 text-gray-400">Set up leave categories for your organization.</p>
                            <button @click="openCreate()"
                                class="text-[11px] font-bold px-4 py-2 mt-3 rounded-lg text-white"
                                style="background: var(--brand-600)">Add First Leave Type</button>
                        </div>
                    </div>
                @endforelse
            </div>

            @if ($leaveTypes->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">{{ $leaveTypes->links() }}</div>
            @endif
        </div>

        {{-- Modal --}}
        <div x-show="modalOpen" x-cloak @keydown.escape.window="modalOpen = false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="bg-white w-full max-w-2xl rounded-xl shadow-2xl overflow-hidden max-h-[90vh] overflow-y-auto"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100">

                <div
                    class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50 sticky top-0 z-10">
                    <h3 class="font-black text-gray-800 uppercase tracking-widest text-sm"
                        x-text="isEditing ? 'Edit Leave Type' : 'New Leave Type'"></h3>
                    <button @click="modalOpen = false" class="text-gray-400 hover:text-red-500 transition-colors"><i
                            data-lucide="x" class="w-5 h-5"></i></button>
                </div>

                <form @submit.prevent="submitForm()">
                    <div class="p-6 space-y-5">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="field-label">Leave Name <span class="text-red-400">*</span></label>
                                <input type="text" x-model="form.name" class="field-input"
                                    placeholder="e.g. Casual Leave" required>
                                <p class="field-error" x-show="errors.name" x-text="errors.name"></p>
                            </div>
                            <div>
                                <label class="field-label">Code <span class="text-red-400">*</span></label>
                                <input type="text" x-model="form.code" class="field-input" placeholder="e.g. CL"
                                    required>
                                <p class="field-error" x-show="errors.code" x-text="errors.code"></p>
                            </div>
                        </div>

                        <div>
                            <label class="field-label">Description</label>
                            <textarea x-model="form.description" class="field-input" rows="2" placeholder="Optional description"></textarea>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="field-label">Days Per Year <span class="text-red-400">*</span></label>
                                <input type="number" x-model="form.default_days_per_year" class="field-input"
                                    min="0" step="0.5" placeholder="12" required>
                                <p class="field-error" x-show="errors.default_days_per_year"
                                    x-text="errors.default_days_per_year"></p>
                            </div>
                            <div>
                                <label class="field-label">Min Days Before Apply</label>
                                <input type="number" x-model="form.min_days_before_apply" class="field-input"
                                    min="0" placeholder="e.g. 3">
                            </div>
                            <div>
                                <label class="field-label">Max Consecutive Days</label>
                                <input type="number" x-model="form.max_consecutive_days" class="field-input"
                                    min="0" step="0.5" placeholder="e.g. 5">
                            </div>
                        </div>

                        <div>
                            <label class="field-label">Applicable Gender <span class="text-red-400">*</span></label>
                            {{-- Three short choices, so a segmented toggle rather than a
                             menu — all options stay visible and it takes one tap
                             instead of two. Bound straight to form.applicable_gender,
                             so reopening the modal for another leave type shows that
                             row's value with no extra syncing. --}}
                            <div class="flex gap-1 rounded-lg border border-gray-200 bg-gray-50 p-1">
                                <button type="button" @click="form.applicable_gender = 'all'"
                                    :class="form.applicable_gender === 'all' ? 'bg-white text-[#108c2a] shadow-sm' :
                                        'text-gray-500 hover:text-gray-700'"
                                    class="flex-1 rounded-md py-2 text-[13px] font-bold transition-all">
                                    All
                                </button>
                                <button type="button" @click="form.applicable_gender = 'male'"
                                    :class="form.applicable_gender === 'male' ? 'bg-white text-[#108c2a] shadow-sm' :
                                        'text-gray-500 hover:text-gray-700'"
                                    class="flex-1 rounded-md py-2 text-[13px] font-bold transition-all">
                                    Male
                                </button>
                                <button type="button" @click="form.applicable_gender = 'female'"
                                    :class="form.applicable_gender === 'female' ? 'bg-white text-[#108c2a] shadow-sm' :
                                        'text-gray-500 hover:text-gray-700'"
                                    class="flex-1 rounded-md py-2 text-[13px] font-bold transition-all">
                                    Female
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2.5">
                                <span class="text-[12px] font-bold text-gray-600">Paid</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" x-model="form.is_paid">
                                    <span class="toggle-track"></span><span class="toggle-thumb"></span>
                                </label>
                            </div>
                            <div class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2.5">
                                <span class="text-[12px] font-bold text-gray-600">Carry Forward</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" x-model="form.is_carry_forward">
                                    <span class="toggle-track"></span><span class="toggle-thumb"></span>
                                </label>
                            </div>
                            <div class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2.5">
                                <span class="text-[12px] font-bold text-gray-600">Encashable</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" x-model="form.is_encashable">
                                    <span class="toggle-track"></span><span class="toggle-thumb"></span>
                                </label>
                            </div>
                            <div class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2.5">
                                <span class="text-[12px] font-bold text-gray-600">Requires Document</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" x-model="form.requires_document">
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

                        <div x-show="form.is_carry_forward" x-transition>
                            <label class="field-label">Max Carry Forward Days</label>
                            <input type="number" x-model="form.max_carry_forward_days" class="field-input"
                                min="0" step="0.5" placeholder="e.g. 5">
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
        window.leaveTypePage = function() {
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
                    default_days_per_year: '',
                    is_paid: true,
                    is_carry_forward: false,
                    max_carry_forward_days: '',
                    is_encashable: false,
                    requires_document: false,
                    min_days_before_apply: '',
                    max_consecutive_days: '',
                    applicable_gender: 'all',
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
                        default_days_per_year: '',
                        is_paid: true,
                        is_carry_forward: false,
                        max_carry_forward_days: '',
                        is_encashable: false,
                        requires_document: false,
                        min_days_before_apply: '',
                        max_consecutive_days: '',
                        applicable_gender: 'all',
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
                        default_days_per_year: item.default_days_per_year,
                        is_paid: item.is_paid,
                        is_carry_forward: item.is_carry_forward,
                        max_carry_forward_days: item.max_carry_forward_days || '',
                        is_encashable: item.is_encashable,
                        requires_document: item.requires_document,
                        min_days_before_apply: item.min_days_before_apply || '',
                        max_consecutive_days: item.max_consecutive_days || '',
                        applicable_gender: item.applicable_gender || 'all',
                        is_active: item.is_active
                    };
                    this.modalOpen = true;
                },
                async submitForm() {
                    this.saving = true;
                    this.errors = {};
                    const url = this.isEditing ? `{{ url('admin/hrm/leave-types') }}/${this.editId}` :
                        `{{ route('admin.hrm.leave-types.store') }}`;
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
                                is_paid: this.form.is_paid ? 1 : 0,
                                is_carry_forward: this.form.is_carry_forward ? 1 : 0,
                                is_encashable: this.form.is_encashable ? 1 : 0,
                                requires_document: this.form.requires_document ? 1 : 0,
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
                    BizAlert.confirm('Delete Leave Type', `Delete "${name}"?`, 'Delete').then(async (r) => {
                        if (!r.isConfirmed) return;
                        try {
                            const resp = await fetch(`{{ url('admin/hrm/leave-types') }}/${id}`, {
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
