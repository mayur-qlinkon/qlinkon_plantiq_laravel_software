@extends('layouts.admin')

@section('title', 'Units Management')

@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Units</h1>
@endsection

@section('content')
    <div class="space-y-6 pb-10" x-data="unitCrud()">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">

            @if (session('success'))
                <div
                    class="bg-[#dcfce7] text-[#16a34a] px-4 py-2 rounded-lg text-sm font-bold shadow-sm flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4"></i> {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div
                    class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm shadow-sm flex flex-col gap-2 w-full sm:w-auto">
                    <div class="flex items-center gap-2 font-bold">
                        <i data-lucide="alert-circle" class="w-4 h-4"></i>
                        <span>Please fix the following mistakes:</span>
                    </div>
                    <ul class="list-disc list-inside font-medium text-xs text-red-600">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">

            <div
                class="px-6 py-4 flex flex-col sm:flex-row justify-between items-center border-b border-gray-100 gap-4 bg-white">
                <h2 class="text-[1.15rem] font-bold text-[#212538] tracking-tight">All Units</h2>


                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <x-import-modal type="units" />
                    <div class="w-full sm:w-64 relative">
                        <input type="text" x-model="search" placeholder="Search units..."
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all placeholder:text-gray-400">
                    </div>
                    @if (has_permission('units.create'))
                        <button @click="openCreateModal()"
                            class="bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-1.5 transition-colors shadow-sm whitespace-nowrap">
                            <i data-lucide="plus" class="w-4 h-4"></i> Add
                        </button>
                    @endif


                </div>
            </div>

            {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead
                        class="text-[11px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100 bg-[#f8fafc]">
                        <tr>
                            <th class="px-6 py-4 w-1/4">ID</th>
                            <th class="px-6 py-4 w-1/4">UNIT NAME</th>
                            <th class="px-6 py-4 w-1/4">SHORT NAME</th>
                            <th class="px-6 py-4 w-1/4 text-center">STATUS</th>
                            <th class="px-6 py-4 w-1/4 text-right">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($units as $unit)
                            <tr class="hover:bg-gray-50/50 transition-colors"
                                x-show="matchesSearch('{{ strtolower($unit->name) }}', '{{ strtolower($unit->short_name) }}')">

                                <td class="px-6 py-4 text-gray-500 font-medium">
                                    #{{ $unit->id }}
                                </td>

                                <td class="px-6 py-4">
                                    <span class="font-bold text-[#475569] text-[13.5px]">{{ $unit->name }}</span>
                                </td>

                                <td class="px-6 py-4">
                                    @if ($unit->short_name)
                                        <span
                                            class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs font-mono font-bold">{{ $unit->short_name }}</span>
                                    @else
                                        <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-center">
                                    @if ($unit->is_active)
                                        <span
                                            class="bg-[#dcfce7] text-[#16a34a] px-3 py-1 rounded-md font-bold text-[11px] uppercase tracking-wider">Active</span>
                                    @else
                                        <span
                                            class="bg-gray-200 text-gray-500 px-3 py-1 rounded-md font-bold text-[11px] uppercase tracking-wider">Inactive</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2.5">
                                        @if (has_permission('units.update'))
                                            <button @click="openEditModal({{ $unit->toJson() }})"
                                                class="w-[32px] h-[32px] flex items-center justify-center rounded border border-[#108c2a] text-[#108c2a] hover:bg-green-50 transition-colors"
                                                title="Edit">
                                                <i data-lucide="edit" class="w-4 h-4"></i>
                                            </button>
                                        @endif

                                        @if (has_permission('units.delete'))
                                            <form action="{{ route('admin.units.destroy', $unit->id) }}" method="POST"
                                                @submit.prevent="deleteUnit($event.target)" class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="w-[32px] h-[32px] flex items-center justify-center rounded border border-red-400 text-red-500 hover:bg-red-50 transition-colors"
                                                    title="Delete">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-400 font-medium">
                                    No units found. Click "Add" to create one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 📱 MOBILE VIEW (CARDS) --}}
            <div class="md:hidden divide-y divide-gray-50 border-t border-gray-50">
                @forelse ($units as $unit)
                    <div class="p-4 hover:bg-gray-50/50 transition-colors flex flex-col gap-3"
                        x-show="matchesSearch('{{ strtolower($unit->name) }}', '{{ strtolower($unit->short_name) }}')">

                        {{-- Header: Unit Name & Status --}}
                        <div class="flex justify-between items-start gap-2">
                            <div class="min-w-0">
                                <p class="font-bold text-[14px] text-gray-900 truncate">{{ $unit->name }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[11px] text-gray-500 font-medium">ID: #{{ $unit->id }}</span>
                                    <span class="text-gray-300">|</span>
                                    @if ($unit->short_name)
                                        <span
                                            class="bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded text-[10px] font-mono font-bold tracking-wider">
                                            {{ $unit->short_name }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-[10px]">-</span>
                                    @endif
                                </div>
                            </div>
                            <div class="shrink-0">
                                @if ($unit->is_active)
                                    <span
                                        class="bg-[#dcfce7] text-[#16a34a] px-2 py-0.5 rounded text-[9px] font-extrabold uppercase tracking-wider">Active</span>
                                @else
                                    <span
                                        class="bg-gray-200 text-gray-500 px-2 py-0.5 rounded text-[9px] font-extrabold uppercase tracking-wider">Inactive</span>
                                @endif
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-50 mt-1">
                            @if (has_permission('units.update'))
                                <button @click="openEditModal({{ $unit->toJson() }})"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg border border-[#108c2a] text-[#108c2a] hover:bg-green-50 transition-colors"
                                    title="Edit">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </button>
                            @endif

                            @if (has_permission('units.delete'))
                                <form action="{{ route('admin.units.destroy', $unit->id) }}" method="POST"
                                    @submit.prevent="deleteUnit($event.target)" class="inline-block m-0 p-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg border border-red-400 text-red-500 hover:bg-red-50 transition-colors"
                                        title="Delete">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            @endif
                        </div>

                    </div>
                @empty
                    <div class="p-8 text-center text-sm text-gray-400 bg-white">
                        <p class="font-medium text-gray-500">No units found.</p>
                        <p class="text-xs mt-1">Click "Add" to create one.</p>
                    </div>
                @endforelse
            </div>

            <div class="hidden md:block h-12 border-t border-gray-100 bg-white w-full"></div>
        </div>

        <div x-show="isModalOpen" style="display: none;"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-black/50 backdrop-blur-sm transition-opacity"
            x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="relative w-full max-w-md p-4" @click.away="closeModal()">

                <div class="relative bg-white rounded-xl shadow-2xl border border-gray-100"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                    <div class="flex items-center justify-between p-5 border-b border-gray-100">
                        <h3 class="text-lg font-bold text-[#212538]"
                            x-text="modalMode === 'create' ? 'Add New Unit' : 'Edit Unit'"></h3>
                        <button @click="closeModal()" type="button"
                            class="text-gray-400 bg-transparent hover:bg-gray-100 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center transition-colors">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <form :action="formAction" method="POST" class="p-5">
                        @csrf
                        <template x-if="modalMode === 'edit'">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <div class="space-y-4 mb-6">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                                    Unit Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" x-model="formData.name" required
                                    placeholder="e.g. Kilogram"
                                    class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm text-gray-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all placeholder:text-gray-400">
                                @error('name')
                                    <span class="text-red-500 text-xs font-medium mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                                    Short Name
                                </label>
                                <input type="text" name="short_name" x-model="formData.short_name"
                                    placeholder="e.g. kg"
                                    class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm text-gray-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all placeholder:text-gray-400">
                                @error('short_name')
                                    <span class="text-red-500 text-xs font-medium mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="flex items-center pt-2">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" x-model="formData.is_active"
                                        class="sr-only peer">
                                    <div
                                        class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#108c2a]">
                                    </div>
                                    <span class="ms-3 text-sm font-bold text-gray-600">Active</span>
                                </label>
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full text-white bg-[#108c2a] hover:bg-[#0c6b1f] focus:ring-4 focus:outline-none focus:ring-green-300 font-bold rounded-lg text-sm px-5 py-3 text-center transition-colors shadow-sm">
                            <span x-text="modalMode === 'create' ? 'Save Unit' : 'Update Unit'"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function unitCrud() {
            return {
                search: '',
                isModalOpen: false,
                modalMode: 'create',
                formAction: '{{ route('admin.units.store') }}',

                formData: {
                    id: null,
                    name: '',
                    short_name: '',
                    is_active: true
                },

                matchesSearch(name, shortName) {
                    if (this.search === '') return true;
                    const query = this.search.toLowerCase();
                    return name.includes(query) || shortName.includes(query);
                },

                openCreateModal() {
                    this.modalMode = 'create';
                    this.formAction = '{{ route('admin.units.store') }}';
                    this.formData = {
                        id: null,
                        name: '',
                        short_name: '',
                        is_active: true
                    };
                    this.isModalOpen = true;
                },

                openEditModal(unit) {
                    this.modalMode = 'edit';
                    this.formAction = `/admin/units/${unit.id}`;
                    this.formData = {
                        id: unit.id,
                        name: unit.name,
                        short_name: unit.short_name || '',
                        is_active: unit.is_active === 1 || unit.is_active === true
                    };
                    this.isModalOpen = true;
                },

                closeModal() {
                    this.isModalOpen = false;
                },

                // NEW: SweetAlert2 Delete Handler
                deleteUnit(form) {
                    BizAlert.confirm(
                        'Delete Unit?',
                        'Are you sure you want to permanently delete this unit?',
                        'Yes, delete it!'
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
