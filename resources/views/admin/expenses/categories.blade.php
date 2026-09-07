@extends('layouts.admin')

@section('title', 'Expense Categories')
@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Categories</h1>
@endsection
@section('content')
    <div class="space-y-6 pb-10" x-data="categoryCrud()">

        @if (session('success'))
            <script>
                document.addEventListener('DOMContentLoaded', () => BizAlert.toast("{{ session('success') }}", 'success'));
            </script>
        @endif

        @if (session('error'))
            <script>
                document.addEventListener('DOMContentLoaded', () => BizAlert.toast("{{ session('error') }}", 'error'));
            </script>
        @endif

        {{-- Search & Actions Bar --}}
        <div
            class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col sm:flex-row gap-4 items-center justify-between">

            {{-- Search Input --}}
            <div class="relative w-full max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i data-lucide="search" class="h-5 w-5 text-gray-400"></i>
                </div>
                <input type="text" x-model="searchQuery" placeholder="Search categories by name or a/c code..."
                    class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 sm:text-sm transition-colors">
            </div>

            {{-- Action Button --}}
            @if (has_permission('expense_categories.create'))
                <button @click="openCreateModal()"
                    class="bg-brand-500 hover:bg-brand-600 w-full sm:w-auto text-white px-5 py-2.5 rounded-xl text-sm font-bold flex items-center justify-center gap-2 transition-all shadow-md active:scale-95 whitespace-nowrap">
                    <i data-lucide="plus-circle" class="w-5 h-5"></i>
                    Add New Category
                </button>
            @endif

        </div>

        {{-- Grid Layout for Categories --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @forelse ($categories as $category)
                <div x-show="searchQuery === '' || '{{ strtolower(addslashes($category->name)) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower(addslashes($category->account_code ?? '')) }}'.includes(searchQuery.toLowerCase())"
                    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col hover:shadow-lg transition-all duration-300 group relative">

                    {{-- Status Badge --}}
                    <div class="absolute top-4 right-4">
                        @if ($category->is_active)
                            <span
                                class="bg-[#dcfce7] text-[#16a34a] px-2.5 py-1 rounded-lg font-bold text-[10px] uppercase tracking-wider">Active</span>
                        @else
                            <span
                                class="bg-gray-100 text-gray-400 px-2.5 py-1 rounded-lg font-bold text-[10px] uppercase tracking-wider">Inactive</span>
                        @endif
                    </div>

                    {{-- Identity --}}
                    <div class="flex items-center gap-4 mb-6 pr-16">
                        <div class="w-12 h-12 rounded-xl border border-gray-100 overflow-hidden flex-shrink-0 shadow-sm flex items-center justify-center"
                            style="background-color: {{ $category->color ?? '#f8fafc' }}">
                            <i data-lucide="pie-chart" class="w-6 h-6 text-white mix-blend-difference opacity-70"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-lg font-bold text-[#212538] truncate">{{ $category->name }}</h3>
                            <div class="flex items-center gap-1.5 text-gray-400 text-xs font-medium mt-0.5">
                                @if ($category->parent)
                                    <i data-lucide="corner-down-right" class="w-3 h-3"></i>
                                    <span class="truncate">Sub of: {{ $category->parent->name }}</span>
                                @else
                                    <i data-lucide="folder" class="w-3 h-3"></i>
                                    <span class="truncate">Root Category</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Core Details --}}
                    <div class="space-y-3 mb-6 flex-1">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400 font-medium">Type</span>
                            <span class="text-gray-700 font-bold capitalize">{{ $category->type }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400 font-medium">A/C Code</span>
                            <span class="text-gray-700 font-mono font-bold">{{ $category->account_code ?? '—' }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400 font-medium">GST Type</span>
                            <span
                                class="text-gray-700 font-bold uppercase text-[11px] bg-gray-50 px-2 py-0.5 rounded border border-gray-100">{{ str_replace('_', ' ', $category->gst_type) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400 font-medium">Expenses Logged</span>
                            <span
                                class="inline-flex items-center justify-center bg-brand-50 text-brand-700 font-bold px-2 py-0.5 rounded-full text-xs">
                                {{ $category->expenses_count }}
                            </span>
                        </div>
                    </div>

                    {{-- Actions Footer --}}
                    <div class="flex items-center gap-2 pt-4 border-t border-gray-50">
                        @if (has_permission('expense_categories.update'))
                            <button @click="openEditModal({{ $category->toJson() }})"
                                class="flex-1 bg-gray-50 hover:bg-brand-50 text-gray-600 hover:text-brand-600 py-2.5 rounded-xl text-sm font-bold transition-colors flex items-center justify-center gap-2">
                                <i data-lucide="settings-2" class="w-4 h-4"></i> Configure
                            </button>
                        @endif

                        @if (has_permission('expense_categories.delete'))
                            <form action="{{ route('admin.expense-categories.destroy', $category->id) }}" method="POST"
                                @submit.prevent="confirmDelete($event.target, {{ $category->expenses_count }}, {{ $category->children()->count() }})">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="w-10 h-10 flex items-center justify-center rounded-xl bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all"
                                    title="Delete Category">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        @endif

                    </div>
                </div>
            @empty
                <div class="col-span-full py-20 text-center bg-white rounded-2xl border border-dashed border-gray-200">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="tags" class="w-10 h-10 text-gray-300"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">No Categories Found</h3>
                    <p class="text-gray-500 text-sm max-w-xs mx-auto mt-1">Start by creating expense categories to track
                        your business spending accurately.</p>
                </div>
            @endforelse
        </div>


        {{-- 🌟 PREMIUM PROFILE & TWO-COLUMN MODAL --}}
        <div x-show="isModalOpen" style="display: none;" @keydown.escape.window="closeModal()"
            class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/70 backdrop-blur-sm transition-opacity"
            x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="relative w-full max-w-5xl p-4 my-8">
                <div
                    class="relative bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden flex flex-col max-h-[90vh]">

                    {{-- Modal Header (Sticky) --}}
                    <div class="flex items-center justify-between p-6 border-b border-gray-100 bg-white sticky top-0 z-20">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center text-brand-600">
                                <i data-lucide="tags" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-[#212538]"
                                    x-text="modalMode === 'create' ? 'Create Category' : 'Edit Category Configuration'">
                                </h3>
                                <p class="text-xs text-gray-500 font-medium mt-0.5">Define accounting codes, tax limits, and
                                    parent hierarchy.</p>
                            </div>
                        </div>
                        <button @click="closeModal()" type="button"
                            class="text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl p-2 transition-colors">
                            <i data-lucide="x" class="w-6 h-6"></i>
                        </button>
                    </div>

                    {{-- Form Body (Scrollable) --}}
                    <form :action="formAction" method="POST" class="flex flex-col flex-1 overflow-hidden"
                        @submit="BizAlert.loading('Processing...')">
                        @csrf
                        <template x-if="modalMode === 'edit'">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <div class="overflow-y-auto flex-1 custom-scrollbar bg-gray-50/30">

                            {{-- Global Errors --}}
                            @if ($errors->any())
                                <div
                                    class="mx-6 mt-6 bg-red-50 text-red-600 border border-red-100 p-4 rounded-xl text-sm font-medium">
                                    <div class="flex items-center gap-2 mb-2 font-bold">
                                        <i data-lucide="alert-circle" class="w-4 h-4"></i> Please check the following
                                        errors:
                                    </div>
                                    <ul class="list-disc pl-5 space-y-1">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            {{-- Form Columns --}}
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">

                                {{-- LEFT COLUMN: Core Details --}}
                                <div class="space-y-5">
                                    <h4
                                        class="flex items-center gap-2 text-sm font-bold text-gray-800 border-b border-gray-200 pb-2">
                                        <i data-lucide="info" class="w-4 h-4 text-brand-500"></i> Category Identification
                                    </h4>

                                    <div>
                                        <label
                                            class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Category
                                            Name <span class="text-red-500">*</span></label>
                                        <input type="text" name="name" x-model="formData.name" required
                                            placeholder="e.g. Office Supplies"
                                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800 font-bold focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all shadow-sm">
                                    </div>

                                    <div>
                                        <label
                                            class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Parent
                                            Category</label>
                                        <x-alpine-select name="parent_id" model="formData.parent_id"
                                            items="rootCategoryOptions" placeholder="None (Make this a Root Category)"
                                            :allow-empty="true" />
                                    </div>

                                    <div>
                                        <label
                                            class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Expense
                                            Type <span class="text-red-500">*</span></label>
                                        <x-alpine-select name="type" model="formData.type"
                                            items="[{id: 'direct', name: 'Direct Expense'}, {id: 'indirect', name: 'Indirect Expense'}, {id: 'asset', name: 'Asset'}]"
                                            :allow-empty="false" :required="true" />
                                    </div>

                                    <div>
                                        <label
                                            class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Description</label>
                                        <textarea name="description" x-model="formData.description" rows="3"
                                            placeholder="Briefly describe what goes into this category..."
                                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all resize-none shadow-sm"></textarea>
                                    </div>
                                </div>

                                {{-- RIGHT COLUMN: Tax & Accounting --}}
                                <div class="space-y-5">
                                    <h4
                                        class="flex items-center gap-2 text-sm font-bold text-gray-800 border-b border-gray-200 pb-2">
                                        <i data-lucide="calculator" class="w-4 h-4 text-brand-500"></i> Tax & Accounting
                                        Info
                                    </h4>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="col-span-2">
                                            <label
                                                class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">GST
                                                Type</label>
                                            <x-alpine-select name="gst_type" model="formData.gst_type"
                                                items="[{id: 'non_taxable', name: 'Non-Taxable'}, {id: 'taxable', name: 'Taxable'}, {id: 'exempt', name: 'Exempt'}]"
                                                :allow-empty="false" />
                                        </div>

                                        <div>
                                            <label
                                                class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">A/C
                                                Code</label>
                                            <input type="text" name="account_code" x-model="formData.account_code"
                                                placeholder="e.g. EXP-001"
                                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-mono text-gray-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all shadow-sm">
                                        </div>

                                        <div>
                                            <label
                                                class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Color
                                                Label</label>
                                            <div class="flex items-center gap-2">
                                                <input type="color" name="color" x-model="formData.color"
                                                    class="h-10 w-12 rounded-xl border border-gray-200 cursor-pointer p-0.5 shadow-sm bg-white">
                                                <span class="text-xs text-gray-400 font-mono"
                                                    x-text="formData.color"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label
                                                class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">HSN/SAC
                                                Code</label>
                                            <input type="text" name="hsn_sac_code" x-model="formData.hsn_sac_code"
                                                placeholder="HSN/SAC"
                                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all shadow-sm">
                                        </div>
                                        <div>
                                            <label
                                                class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Default
                                                Tax (%)</label>
                                            <input type="number" step="0.01" name="default_tax_rate"
                                                x-model="formData.default_tax_rate" placeholder="0.00"
                                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all shadow-sm">
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        {{-- Footer (Sticky) --}}
                        <div
                            class="p-5 border-t border-gray-200 bg-white flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 sticky bottom-0 z-20">

                            {{-- Active Toggle --}}
                            <label class="relative inline-flex items-center cursor-pointer group">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" x-model="formData.is_active"
                                    class="sr-only peer">
                                <div
                                    class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-500 group-hover:shadow-sm">
                                </div>
                                <span class="ms-3 text-sm font-bold text-gray-700">Category is Active</span>
                            </label>

                            {{-- Action Buttons --}}
                            <div
                                class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
                                <button type="button" @click="closeModal()"
                                    class="w-full sm:w-auto px-6 py-2.5 rounded-xl border border-gray-200 text-gray-600 text-sm font-bold hover:bg-gray-100 hover:text-gray-900 transition-colors text-center">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="w-full sm:w-auto bg-brand-500 hover:bg-brand-600 text-white px-8 py-2.5 rounded-xl text-sm font-bold transition-all active:scale-95 flex items-center justify-center gap-2">
                                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                                    <span x-text="modalMode === 'create' ? 'Save Category' : 'Update Changes'"></span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        function categoryCrud() {
            return {
                isModalOpen: false,
                modalMode: 'create',
                formAction: '{{ route('admin.expense-categories.store') }}',
                searchQuery: '',
                rootCategoryOptions: @js(collect($rootCategories)->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values()),
                formData: {
                    name: '',
                    parent_id: '',
                    type: 'indirect',
                    gst_type: 'non_taxable',
                    account_code: '',
                    hsn_sac_code: '',
                    default_tax_rate: '',
                    description: '',
                    color: '#f8fafc',
                    is_active: true
                },

                init() {
                    @if ($errors->any())
                        this.isModalOpen = true;
                        document.body.style.overflow = 'hidden';
                    @endif
                },

                openCreateModal() {
                    this.modalMode = 'create';
                    this.formAction = '{{ route('admin.expense-categories.store') }}';
                    this.formData = {
                        name: '',
                        parent_id: '',
                        type: 'indirect',
                        gst_type: 'non_taxable',
                        account_code: '',
                        hsn_sac_code: '',
                        default_tax_rate: '',
                        description: '',
                        color: '#e2e8f0', // default subtle gray
                        is_active: true
                    };
                    this.isModalOpen = true;
                    document.body.style.overflow = 'hidden';
                },

                openEditModal(category) {
                    this.modalMode = 'edit';
                    this.formAction = `/admin/expense-categories/${category.id}`;

                    // Map existing data to form
                    this.formData = {
                        name: category.name,
                        parent_id: category.parent_id || '',
                        type: category.type || 'indirect',
                        gst_type: category.gst_type || 'non_taxable',
                        account_code: category.account_code || '',
                        hsn_sac_code: category.hsn_sac_code || '',
                        default_tax_rate: category.default_tax_rate || '',
                        description: category.description || '',
                        color: category.color || '#e2e8f0',
                        is_active: category.is_active == 1 // Convert to boolean for checkbox
                    };

                    this.isModalOpen = true;
                    document.body.style.overflow = 'hidden';
                },

                closeModal() {
                    this.isModalOpen = false;
                    document.body.style.overflow = 'auto';
                },

                confirmDelete(form, expensesCount, childrenCount) {
                    if (expensesCount > 0) {
                        BizAlert.toast('Cannot delete! There are expenses linked to this category.', 'error');
                        return;
                    }
                    if (childrenCount > 0) {
                        BizAlert.toast('Cannot delete! Reassign sub-categories first.', 'error');
                        return;
                    }

                    BizAlert.confirm(
                        'Delete Category?',
                        'Are you sure you want to permanently delete this expense category?',
                        'Yes, Delete'
                    ).then((result) => {
                        if (result.isConfirmed) {
                            BizAlert.loading('Deleting...');
                            form.submit();
                        }
                    });
                }
            }
        }
    </script>
@endpush
