@extends('layouts.admin')

@section('title', 'Salary Components')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Salary Components</h1>
        {{-- <p class="text-xs text-gray-400 font-medium mt-0.5">Manage earnings, deductions and payroll components</p> --}}
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

    <div class="pb-10" x-data="salaryComponentPage()">

        {{-- Stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
            <div class="stat-card">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total</p>
                <p class="text-2xl font-black text-gray-900">{{ $components->total() }}</p>
            </div>
            <div class="stat-card">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Earnings</p>
                <p class="text-2xl font-black text-green-600">{{ $components->where('type', 'earning')->count() }}</p>
            </div>
            <div class="stat-card">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Deductions</p>
                <p class="text-2xl font-black text-red-500">{{ $components->where('type', 'deduction')->count() }}</p>
            </div>
            <div class="stat-card">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Statutory</p>
                <p class="text-2xl font-black text-gray-900">{{ $components->where('is_statutory', true)->count() }}</p>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="bg-white border border-gray-100 rounded-2xl px-4 py-3 mb-4">
            <div class="flex items-center gap-3 flex-wrap">
                <div class="relative flex-1 min-w-[180px]">
                    <i data-lucide="search"
                        class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"></i>
                    <input type="text" x-model="searchQuery" @input.debounce.300ms="filterTable()"
                        placeholder="Search components..." class="field-input pl-9 !py-2 !text-[13px]">
                </div>
                @if (has_permission('salary_components.create'))
                    <button @click="openCreate()"
                        class="ml-auto inline-flex items-center gap-1.5 text-[12px] font-bold px-4 py-2 rounded-lg text-white hover:opacity-90 transition-opacity"
                        style="background: var(--brand-600)">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        Add Component
                    </button>
                @endif
            </div>
        </div>

        {{-- Table --}}
        <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="hidden md:table-header-group">
                        <tr class="border-b border-gray-100">
                            <th class="px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-wider w-[50px]">#
                            </th>
                            <th class="px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-wider">Component
                            </th>
                            <th class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Type</th>
                            <th class="px-3 py-3 text-[10px] font-black text-gray-400 uppercase tracking-wider">Calculation
                            </th>
                            <th class="px-3 py-3 text-right text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Amount</th>
                            <th class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Taxable</th>
                            <th class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Status</th>
                            <th class="px-4 py-3 text-right text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="block md:table-row-group">
                        @forelse($components as $component)
                            <tr class="block md:table-row border-b border-gray-100 mb-4 md:mb-0 p-4 md:p-0"
                                x-show="matchesSearch('{{ strtolower($component->name) }}', '{{ strtolower($component->code) }}')">

                                {{-- Mobile: Index & Component Header / Desktop: Index --}}
                                <td
                                    class="block md:table-cell px-2 md:px-5 py-1 md:py-3 text-[12px] font-bold text-gray-400">
                                    <div class="flex items-center justify-between md:hidden mb-2">
                                        <span>#{{ $components->firstItem() + $loop->index }}</span>
                                        <div class="flex items-center gap-1.5">
                                            @if (has_permission('salary_components.update'))
                                                <button @click="openEdit({{ $component->toJson() }})"
                                                    class="w-[28px] h-[28px] rounded-lg flex items-center justify-center bg-blue-50 text-blue-500">
                                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                                </button>
                                            @endif
                                            @if (has_permission('salary_components.delete'))
                                                <button
                                                    @click="confirmDelete({{ $component->id }}, '{{ $component->name }}')"
                                                    class="w-[28px] h-[28px] rounded-lg flex items-center justify-center bg-red-50 text-red-400">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="hidden md:inline">{{ $components->firstItem() + $loop->index }}</span>
                                </td>

                                {{-- Component Name & Details --}}
                                <td class="block md:table-cell px-2 md:px-5 py-1 md:py-3">
                                    <div>
                                        <p class="text-[13px] font-bold text-gray-800">
                                            {{ $component->name }}
                                            <span
                                                class="text-[11px] font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded ml-1.5">{{ $component->code }}</span>
                                        </p>
                                        @if ($component->description)
                                            <p class="text-[11px] text-gray-400 mt-0.5 truncate max-w-[250px]">
                                                {{ $component->description }}</p>
                                        @endif
                                    </div>
                                </td>

                                {{-- Type --}}
                                <td class="flex justify-between md:table-cell px-2 md:px-3 py-1.5 md:py-3 md:text-center">
                                    <span class="md:hidden text-[11px] font-bold text-gray-400 uppercase">Type</span>
                                    @if ($component->type === 'earning')
                                        <span
                                            class="inline-flex items-center text-[10px] font-extrabold uppercase tracking-wider text-green-700 bg-green-50 border border-green-200 px-2.5 py-1 rounded-md">Earning</span>
                                    @else
                                        <span
                                            class="inline-flex items-center text-[10px] font-extrabold uppercase tracking-wider text-red-600 bg-red-50 border border-red-200 px-2.5 py-1 rounded-md">Deduction</span>
                                    @endif
                                </td>

                                {{-- Calculation --}}
                                <td class="flex justify-between items-center md:table-cell px-2 md:px-3 py-1.5 md:py-3">
                                    <span class="md:hidden text-[11px] font-bold text-gray-400 uppercase">Calc Type</span>
                                    <div class="text-right md:text-left">
                                        <p class="text-[12px] text-gray-600 capitalize">{{ $component->calculation_type }}
                                        </p>
                                        @if ($component->calculation_type === 'percentage' && $component->percentageOfComponent)
                                            <p class="text-[11px] text-gray-400">of
                                                {{ $component->percentageOfComponent->name }}</p>
                                        @endif
                                    </div>
                                </td>

                                {{-- Amount --}}
                                <td class="flex justify-between md:table-cell px-2 md:px-3 py-1.5 md:py-3 md:text-right">
                                    <span class="md:hidden text-[11px] font-bold text-gray-400 uppercase">Amount</span>
                                    <span
                                        class="text-[12px] font-bold text-gray-700">{{ number_format($component->default_amount ?? 0, 2) }}</span>
                                </td>

                                {{-- Taxable --}}
                                <td class="flex justify-between md:table-cell px-2 md:px-3 py-1.5 md:py-3 md:text-center">
                                    <span class="md:hidden text-[11px] font-bold text-gray-400 uppercase">Taxable</span>
                                    @if ($component->is_taxable)
                                        <span
                                            class="text-[10px] font-extrabold uppercase tracking-wider text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-md">Yes</span>
                                    @else
                                        <span
                                            class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400 bg-gray-50 border border-gray-200 px-2 py-0.5 rounded-md">No</span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="flex justify-between md:table-cell px-2 md:px-3 py-1.5 md:py-3 md:text-center">
                                    <span class="md:hidden text-[11px] font-bold text-gray-400 uppercase">Status</span>
                                    @if ($component->is_active)
                                        <span
                                            class="inline-flex items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-wider text-green-700 bg-green-50 border border-green-200 px-2.5 py-1 rounded-md">Active</span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-wider text-gray-500 bg-gray-50 border border-gray-200 px-2.5 py-1 rounded-md">Inactive</span>
                                    @endif
                                </td>

                                {{-- Actions (Desktop only, mobile actions are moved to the top of the card) --}}
                                <td
                                    class="hidden md:table-cell px-4 py-3 text-right border-t md:border-t-0 mt-2 md:mt-0 pt-3 md:pt-0">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if (has_permission('salary_components.update'))
                                            <button @click="openEdit({{ $component->toJson() }})"
                                                class="w-[30px] h-[30px] rounded-lg flex items-center justify-center bg-blue-50 text-blue-500 hover:bg-blue-100 hover:text-blue-700 transition-colors">
                                                <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                            </button>
                                        @endif
                                        @if (has_permission('salary_components.delete'))
                                            <button @click="confirmDelete({{ $component->id }}, '{{ $component->name }}')"
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
                                            <i data-lucide="wallet" class="w-7 h-7 text-gray-300"></i>
                                        </div>
                                        <p class="font-semibold text-gray-500 mb-1">No salary components yet</p>
                                        <p class="text-sm text-gray-400 mb-4">Create your first salary component to build
                                            payroll structures</p>
                                        <button @click="openCreate()"
                                            class="text-sm font-bold px-4 py-2 rounded-xl text-white"
                                            style="background: var(--brand-600)">
                                            Add Component
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endempty
                </tbody>
            </table>
        </div>

        @if ($components->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                {{ $components->links() }}
            </div>
        @endif
    </div>

    {{-- Modal --}}
    <div x-show="modalOpen" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="bg-white w-full max-w-lg rounded-xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col"
            @click.away="modalOpen = false" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

            <div
                class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50 sticky top-0 z-10">
                <h3 class="font-black text-gray-800 uppercase tracking-widest text-sm"
                    x-text="isEditing ? 'Edit Component' : 'New Component'"></h3>
                <button @click="modalOpen = false" class="text-gray-400 hover:text-red-500 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form @submit.prevent="submitForm()">
                <div class="p-6 space-y-4 overflow-y-auto">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="field-label">Component Name <span class="text-red-400">*</span></label>
                            <input type="text" x-model="form.name" class="field-input"
                                placeholder="e.g. Basic Salary" required>
                            <p class="field-error" x-show="errors.name" x-text="errors.name"></p>
                        </div>
                        <div>
                            <label class="field-label">Code <span class="text-red-400">*</span></label>
                            <input type="text" x-model="form.code" class="field-input" placeholder="e.g. BASIC"
                                required>
                            <p class="field-error" x-show="errors.code" x-text="errors.code"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="field-label">Type <span class="text-red-400">*</span></label>
                            <x-alpine-select name="type" model="form.type" items="typeOptions"
                                placeholder="Select Type" :required="true" :allow-empty="true" />
                            <p class="field-error" x-show="errors.type" x-text="errors.type"></p>
                        </div>
                        <div>
                            <label class="field-label">Calculation Type <span class="text-red-400">*</span></label>
                            <x-alpine-select name="calculation_type" model="form.calculation_type"
                                items="calcTypeOptions" :required="true" :allow-empty="false" />
                            <p class="field-error" x-show="errors.calculation_type" x-text="errors.calculation_type">
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="field-label">Role <span class="text-red-400">*</span></label>
                        <x-alpine-select name="role" model="form.role" items="roleOptions" :required="true"
                            :allow-empty="false" />
                        <p class="text-[11px] text-gray-400 mt-1">The payroll engine identifies components by role, not
                            by name or code. Only one active component per role.</p>
                        <p class="field-error" x-show="errors.role" x-text="errors.role"></p>
                    </div>

                    <div x-show="form.calculation_type === 'percentage'" x-transition>
                        <label class="field-label">Percentage Of</label>
                        <x-alpine-select name="percentage_of_component_id" model="form.percentage_of_component_id"
                            items="baseComponentOptions" placeholder="Select base component" :allow-empty="true" />
                        <p class="field-error" x-show="errors.percentage_of_component_id"
                            x-text="errors.percentage_of_component_id"></p>
                    </div>

                    <div>
                        <label class="field-label">Default Amount</label>
                        <input type="number" x-model="form.default_amount" class="field-input" placeholder="0.00"
                            step="0.01" min="0">
                        <p class="field-error" x-show="errors.default_amount" x-text="errors.default_amount"></p>
                    </div>

                    <div>
                        <label class="field-label">Description</label>
                        <textarea x-model="form.description" class="field-input" rows="2"
                            placeholder="Brief description of this component"></textarea>
                    </div>

                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[13px] font-bold text-gray-700">Taxable</p>
                                <p class="text-[11px] text-gray-400">This component is subject to income tax</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" x-model="form.is_taxable">
                                <span class="toggle-track"></span>
                                <span class="toggle-thumb"></span>
                            </label>
                        </div>

                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[13px] font-bold text-gray-700">Statutory</p>
                                <p class="text-[11px] text-gray-400">Government-mandated component (PF, ESI, etc.)</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" x-model="form.is_statutory">
                                <span class="toggle-track"></span>
                                <span class="toggle-thumb"></span>
                            </label>
                        </div>

                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[13px] font-bold text-gray-700">Appears on Payslip</p>
                                <p class="text-[11px] text-gray-400">Show this component on employee payslips</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" x-model="form.appears_on_payslip">
                                <span class="toggle-track"></span>
                                <span class="toggle-thumb"></span>
                            </label>
                        </div>

                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[13px] font-bold text-gray-700">Active Status</p>
                                <p class="text-[11px] text-gray-400">Inactive components won't appear in dropdowns</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" x-model="form.is_active">
                                <span class="toggle-track"></span>
                                <span class="toggle-thumb"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3 sticky bottom-0 z-10">
                    <button type="button" @click="modalOpen = false"
                        class="px-4 py-2 text-[13px] font-bold text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
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
    window.salaryComponentPage = function() {
        return {
            modalOpen: false,
            isEditing: false,
            editId: null,
            saving: false,
            searchQuery: '',
            errors: {},

            // Select Data Options
            typeOptions: [{
                    id: 'earning',
                    name: 'Earning'
                },
                {
                    id: 'deduction',
                    name: 'Deduction'
                }
            ],
            calcTypeOptions: [{
                    id: 'fixed',
                    name: 'Fixed'
                },
                {
                    id: 'percentage',
                    name: 'Percentage'
                }
            ],
            roleOptions: [{
                    id: 'other',
                    name: 'Other'
                },
                {
                    id: 'basic',
                    name: 'Basic Salary'
                },
                {
                    id: 'da',
                    name: 'Dearness Allowance'
                },
                {
                    id: 'hra',
                    name: 'House Rent Allowance'
                },
                {
                    id: 'special_allowance',
                    name: 'Special Allowance (balancing)'
                }
            ],
            baseComponentOptions: @js(collect($baseComponents)->map(fn($b) => ['id' => $b->id, 'name' => $b->name . ' (' . $b->code . ')'])->values()),

            form: {
                name: '',
                code: '',
                type: '',
                description: '',
                calculation_type: 'fixed',
                percentage_of_component_id: '',
                role: 'other',
                default_amount: '',
                is_taxable: false,
                is_statutory: false,
                appears_on_payslip: true,
                is_active: true,
            },

            init() {
                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                });
            },

            matchesSearch(name, code) {
                if (!this.searchQuery) return true;
                const q = this.searchQuery.toLowerCase();
                return name.includes(q) || code.includes(q);
            },

            resetForm() {
                this.form = {
                    name: '',
                    code: '',
                    type: '',
                    description: '',
                    calculation_type: 'fixed',
                    percentage_of_component_id: '',
                    role: 'other',
                    default_amount: '',
                    is_taxable: false,
                    is_statutory: false,
                    appears_on_payslip: true,
                    is_active: true,
                };
                this.errors = {};
            },

            openCreate() {
                this.resetForm();
                this.isEditing = false;
                this.editId = null;
                this.modalOpen = true;
            },

            openEdit(component) {
                this.resetForm();
                this.isEditing = true;
                this.editId = component.id;
                this.form.name = component.name;
                this.form.code = component.code || '';
                this.form.type = component.type || '';
                this.form.description = component.description || '';
                this.form.calculation_type = component.calculation_type || 'fixed';
                this.form.percentage_of_component_id = component.percentage_of_component_id || '';
                this.form.role = component.role || 'other';
                this.form.default_amount = component.default_amount || '';
                this.form.is_taxable = !!component.is_taxable;
                this.form.is_statutory = !!component.is_statutory;
                this.form.appears_on_payslip = !!component.appears_on_payslip;
                this.form.is_active = !!component.is_active;
                this.modalOpen = true;
            },

            async submitForm() {
                this.saving = true;
                this.errors = {};

                const url = this.isEditing ?
                    `{{ url('admin/hrm/salary-components') }}/${this.editId}` :
                    `{{ route('admin.hrm.salary-components.store') }}`;

                const method = this.isEditing ? 'PUT' : 'POST';

                try {
                    const response = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            ...this.form,
                            is_taxable: this.form.is_taxable ? 1 : 0,
                            is_statutory: this.form.is_statutory ? 1 : 0,
                            appears_on_payslip: this.form.appears_on_payslip ? 1 : 0,
                            is_active: this.form.is_active ? 1 : 0,
                        }),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        if (response.status === 422 && data.errors) {
                            this.errors = {};
                            for (const [key, messages] of Object.entries(data.errors)) {
                                this.errors[key] = messages[0];
                            }
                        } else {
                            BizAlert.toast(data.message || 'Something went wrong', 'error');
                        }
                        return;
                    }

                    BizAlert.toast(data.message, 'success');
                    this.modalOpen = false;
                    setTimeout(() => window.location.reload(), 600);
                } catch (e) {
                    BizAlert.toast('Network error. Please try again.', 'error');
                } finally {
                    this.saving = false;
                }
            },

            confirmDelete(id, name) {
                BizAlert.confirm('Delete Component', `Are you sure you want to delete "${name}"?`, 'Delete').then(
                    async (result) => {
                        if (!result.isConfirmed) return;

                        try {
                            const response = await fetch(
                                `{{ url('admin/hrm/salary-components') }}/${id}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector(
                                            'meta[name="csrf-token"]').content,
                                        'Accept': 'application/json',
                                    },
                                });

                            const data = await response.json();

                            if (!response.ok) {
                                BizAlert.toast(data.message || 'Cannot delete', 'error');
                                return;
                            }

                            BizAlert.toast(data.message, 'success');
                            setTimeout(() => window.location.reload(), 600);
                        } catch (e) {
                            BizAlert.toast('Network error. Please try again.', 'error');
                        }
                    });
            },
        };
    };
</script>
@endpush
