@extends('layouts.platform')

@section('title', 'Manage Permissions - Qlinkon Super Admin')
@section('header', 'System Permissions')

@section('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        body.modal-open {
            overflow: hidden;
        }
    </style>
@endsection

@section('content')
    <div class="pb-10" x-data="permissionManager(@js($permissions))">

        {{-- ── HEADER ── --}}
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 tracking-tight">System Permissions</h1>
                <p class="text-sm text-gray-500 mt-1">Manage granular access control rights for the entire platform.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                {{-- Sync Defaults Button --}}
                <form action="{{ route('platform.permissions.sync') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit"
                        class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-4 py-2.5 rounded-lg text-sm font-bold transition-colors shadow-sm flex items-center gap-2">
                        <i data-lucide="refresh-cw" class="w-4 h-4 text-brand-600"></i> Sync Defaults
                    </button>
                </form>

                {{-- Add Permission Button --}}
                <button type="button" @click="openCreate()"
                    class="bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-lg text-sm font-bold transition-colors shadow-sm flex items-center gap-2">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i> Add Permission
                </button>
            </div>
        </div>

        {{-- ── ALERTS ── --}}
        @if (session('success'))
            <div class="bg-green-50 text-green-700 px-5 py-4 rounded-xl text-sm font-bold shadow-sm border border-green-100 mb-6 flex items-center gap-2">
                <i data-lucide="check-circle" class="w-5 h-5"></i> {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="bg-red-50 text-red-600 px-5 py-4 rounded-xl text-sm font-bold shadow-sm border border-red-100 mb-6">
                <div class="flex items-center gap-2 mb-2"><i data-lucide="alert-triangle" class="w-5 h-5"></i> Please fix the following errors:</div>
                <ul class="list-disc list-inside pl-7 text-xs font-medium space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ── GROUPED PERMISSION CARDS ── --}}
        @php
            // Group the flat permissions collection by their module_group
            $groupedPermissions = $permissions->groupBy('module_group');
        @endphp

        @forelse ($groupedPermissions as $groupName => $groupPerms)
            <div class="mb-8">
                <h2 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <i data-lucide="folder-key" class="w-4 h-4"></i>
                    {{ Str::headline($groupName) }}
                    <span class="bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full text-[10px] ml-2">{{ $groupPerms->count() }}</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach ($groupPerms as $permission)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col hover:shadow-md transition-shadow relative group">
                            
                            <div class="flex items-start justify-between mb-4">
                                <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                    <i data-lucide="key" class="w-5 h-5"></i>
                                </div>
                            </div>

                            <h3 class="text-[15px] font-bold text-gray-800 mb-1">{{ $permission->name }}</h3>
                            <p class="text-[11px] font-mono text-gray-400 mb-6 bg-gray-50 border border-gray-100 px-2 py-1 rounded inline-block self-start line-clamp-1">
                                {{ $permission->slug }}
                            </p>

                            <div class="mt-auto pt-4 border-t border-gray-100 flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button type="button" @click="openEdit({{ $permission->id }})"
                                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit Permission">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </button>
                                <button type="button" @click="openDelete({{ $permission->id }}, '{{ addslashes($permission->name) }}')"
                                    class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete Permission">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="py-16 flex flex-col items-center justify-center text-center bg-white rounded-xl border border-gray-200 border-dashed">
                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                    <i data-lucide="shield-off" class="w-8 h-8 text-gray-300"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-1">No Permissions Found</h3>
                <p class="text-sm text-gray-500 max-w-sm">Click "Sync Defaults" to auto-generate system permissions, or add your first one manually.</p>
            </div>
        @endforelse

        {{-- ══════════════════════════════════════════════
             CREATE / EDIT MODAL
        ══════════════════════════════════════════════ --}}
        <div x-cloak x-show="showFormModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeAll()" x-show="showFormModal" x-transition.opacity></div>

            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col"
                x-show="showFormModal" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">

                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-brand-100 text-brand-600 flex items-center justify-center">
                            <i data-lucide="key" class="w-4 h-4"></i>
                        </div>
                        <h3 class="text-[16px] font-bold text-gray-800 tracking-tight" x-text="isEditing ? 'Edit Permission' : 'Add New Permission'"></h3>
                    </div>
                    <button type="button" @click="closeAll()" class="text-gray-400 hover:text-red-500 transition-colors p-1">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form :action="isEditing ? `/platform/permissions/${form.id}` : '{{ route('platform.permissions.store') }}'" method="POST">
                    @csrf
                    <template x-if="isEditing">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="p-6 space-y-5">
                        
                        {{-- Module Group (With Datalist for autocomplete) --}}
                        <div>
                            <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Module Group <span class="text-red-500">*</span></label>
                            <input type="text" name="module_group" x-model="form.module_group" list="module-groups" required
                                placeholder="e.g. pos, invoices, products"
                                class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                            
                            {{-- Datalist injected from Controller --}}
                            <datalist id="module-groups">
                                @foreach($moduleGroups as $group)
                                    <option value="{{ $group }}"></option>
                                @endforeach
                            </datalist>
                            <p class="text-[10px] text-gray-400 mt-1">Select an existing group or type a new one.</p>
                        </div>

                        {{-- Action Name --}}
                        <div>
                            <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Action Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" x-model="form.name" required
                                placeholder="e.g. Create Quick Product"
                                class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                        </div>

                        {{-- Slug --}}
                        <div>
                            <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Slug <span class="text-gray-400 font-normal text-[10px]">(Optional - Auto-generated)</span></label>
                            <input type="text" name="slug" x-model="form.slug"
                                placeholder="e.g. pos_create_quick_product"
                                class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all font-mono">
                        </div>

                    </div>

                    <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50">
                        <button type="button" @click="closeAll()"
                            class="px-5 py-2 bg-white border border-gray-200 text-gray-600 rounded-lg text-sm font-bold hover:bg-gray-50 transition-colors">Cancel</button>
                        <button type="submit"
                            class="px-6 py-2 bg-brand-600 text-white rounded-lg text-sm font-bold hover:bg-brand-700 transition-colors shadow-sm"
                            x-text="isEditing ? 'Update Permission' : 'Save Permission'"></button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════
             DELETE MODAL
        ══════════════════════════════════════════════ --}}
        <div x-cloak x-show="showDeleteModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeAll()" x-show="showDeleteModal" x-transition.opacity></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm overflow-hidden text-center"
                x-show="showDeleteModal" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                <div class="p-6 pt-8">
                    <div class="w-16 h-16 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="alert-triangle" class="w-8 h-8 text-red-500"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 mb-2">Delete Permission?</h3>
                    <p class="text-sm text-gray-500">Are you sure you want to delete <strong class="text-gray-800" x-text="deleteForm.name"></strong>? This may break role assignments.</p>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex justify-center gap-3 bg-gray-50">
                    <button type="button" @click="closeAll()"
                        class="px-6 py-2 bg-white border border-gray-200 text-gray-600 rounded-lg text-sm font-bold hover:bg-gray-50 transition-colors">Cancel</button>
                    <form :action="`/platform/permissions/${deleteForm.id}`" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg text-sm font-bold hover:bg-red-700 transition-colors shadow-sm">Yes, Delete</button>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('scripts')
    <script>
        function permissionManager(allPermissions) {
            return {
                permissions: allPermissions,
                showFormModal: false,
                showDeleteModal: false,
                isEditing: false,

                form: {
                    id: '',
                    name: '',
                    module_group: '',
                    slug: ''
                },

                deleteForm: {
                    id: '',
                    name: ''
                },

                openCreate() {
                    document.body.classList.add('modal-open');
                    this.isEditing = false;
                    this.form = {
                        id: '',
                        name: '',
                        module_group: '',
                        slug: ''
                    };
                    this.showFormModal = true;
                },

                openEdit(id) {
                    let permission = this.permissions.find(p => p.id === id);
                    if (!permission) return;

                    document.body.classList.add('modal-open');
                    this.isEditing = true;

                    this.form = {
                        id: permission.id,
                        name: permission.name,
                        module_group: permission.module_group,
                        slug: permission.slug
                    };

                    this.showFormModal = true;
                },

                openDelete(id, name) {
                    document.body.classList.add('modal-open');
                    this.deleteForm = { id, name };
                    this.showDeleteModal = true;
                },

                closeAll() {
                    document.body.classList.remove('modal-open');
                    this.showFormModal = false;
                    this.showDeleteModal = false;
                }
            }
        }
    </script>
@endsection