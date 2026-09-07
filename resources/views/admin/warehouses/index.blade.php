@extends('layouts.admin')

@section('title', 'Warehouses')

@section('header-title')
    <h1 class="text-xs sm:text-sm font-bold text-gray-400 uppercase tracking-widest">Warehouses</h1>
@endsection

@section('content')
    <div class="space-y-4 sm:space-y-6 pb-10" x-data="warehouseIndex()">

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

        {{-- 🌟 RESPONSIVE HEADER: Title on left, Search & Add side-by-side on right --}}
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <div class="w-full lg:w-auto">
                <p class="text-xs sm:text-sm text-gray-500 font-medium">Manage stock storage locations across your different
                    branches.</p>
            </div>

            <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
                {{-- Search Bar --}}
                <div class="relative w-full sm:w-64 shrink-0">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" x-model="search" placeholder="Search name or city..."
                        class="w-full border border-gray-200 rounded-xl pl-9 pr-3 py-2.5 text-sm text-gray-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all placeholder:text-gray-400 shadow-sm bg-white">
                </div>

                {{-- Add Button (Redirects to Create Page) --}}
                @if (has_permission('warehouses.create'))
                    <a href="{{ route('admin.warehouses.create') }}"
                        class="w-full sm:w-auto bg-brand-500 hover:bg-brand-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold flex items-center justify-center gap-2 transition-all shadow-md active:scale-95 whitespace-nowrap shrink-0">
                        <i data-lucide="plus-circle" class="w-5 h-5"></i> Add Warehouse
                    </a>
                @endif
            </div>
        </div>

        {{-- 🌟 MAIN TABLE CARD --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">

            {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
            <div class="hidden md:block overflow-x-auto custom-scrollbar">
                <table class="w-full text-left text-sm whitespace-nowrap min-w-[800px]">
                    <thead
                        class="text-[10px] sm:text-[11px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100 bg-[#f8fafc]">
                        <tr>
                            <th class="px-4 sm:px-6 py-3 sm:py-4">WAREHOUSE / STORE</th>
                            <th class="px-4 sm:px-6 py-3 sm:py-4">CONTACT PERSON</th>
                            <th class="px-4 sm:px-6 py-3 sm:py-4">LOCATION</th>
                            <th class="px-4 sm:px-6 py-3 sm:py-4 text-center">STATUS</th>
                            <th class="px-4 sm:px-6 py-3 sm:py-4 text-right">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($warehouses as $warehouse)
                            <tr class="hover:bg-gray-50/50 transition-colors"
                                x-show="matchesSearch('{{ strtolower(addslashes($warehouse->name)) }}', '{{ strtolower(addslashes($warehouse->city)) }}')">

                                <td class="px-4 sm:px-6 py-3 sm:py-4">
                                    <div class="flex flex-col">
                                        <span
                                            class="font-bold text-[#475569] text-xs sm:text-[13.5px]">{{ $warehouse->name }}</span>
                                        <span
                                            class="text-[9px] sm:text-[10px] bg-brand-50 text-brand-600 px-1.5 py-0.5 rounded font-black uppercase mt-1 w-fit">
                                            Store: {{ $warehouse->store->name ?? 'N/A' }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 sm:px-6 py-3 sm:py-4">
                                    <div class="flex flex-col">
                                        <span
                                            class="font-bold text-gray-700 text-xs sm:text-[13px]">{{ $warehouse->contact_person ?? 'Not Set' }}</span>
                                        <span
                                            class="text-[11px] sm:text-[12px] text-gray-400 font-medium">{{ $warehouse->phone ?? '-' }}</span>
                                    </div>
                                </td>

                                <td class="px-4 sm:px-6 py-3 sm:py-4">
                                    <div
                                        class="flex items-center gap-1.5 text-gray-500 font-medium italic text-xs sm:text-sm">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-gray-300"></i>
                                        {{ $warehouse->city ?? 'N/A' }}
                                    </div>
                                </td>

                                <td class="px-4 sm:px-6 py-3 sm:py-4 text-center">
                                    <div class="flex flex-col items-center gap-1">
                                        @if ($warehouse->is_default)
                                            <span
                                                class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded-md font-black text-[8px] sm:text-[9px] uppercase tracking-tighter border border-blue-100">
                                                Primary Hub
                                            </span>
                                        @endif

                                        @if ($warehouse->is_active)
                                            <span
                                                class="bg-[#dcfce7] text-[#16a34a] px-2.5 py-0.5 rounded-md font-bold text-[9px] sm:text-[10px] uppercase">Active</span>
                                        @else
                                            <span
                                                class="bg-gray-100 text-gray-400 px-2.5 py-0.5 rounded-md font-bold text-[9px] sm:text-[10px] uppercase">Disabled</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 sm:px-6 py-3 sm:py-4 text-right">
                                    <div class="flex items-center justify-end gap-1 sm:gap-2">

                                        {{-- 🌟 NEW: View Inventory/Details Button --}}
                                        @if (has_permission('warehouses.view'))
                                            <a href="{{ route('admin.warehouses.show', $warehouse->id) }}"
                                                class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center rounded-lg border border-blue-100 text-blue-600 hover:bg-blue-50 transition-colors"
                                                title="View Inventory">
                                                <i data-lucide="eye" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                                            </a>
                                        @endif


                                        {{-- Edit Button (Redirects to Edit Page) --}}
                                        @if (has_permission('warehouses.update'))
                                            <a href="{{ route('admin.warehouses.edit', $warehouse->id) }}"
                                                class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center rounded-lg border border-brand-100 text-brand-600 hover:bg-brand-50 transition-colors"
                                                title="Edit">
                                                <i data-lucide="edit-3" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                                            </a>
                                        @endif

                                        {{-- Delete Button --}}
                                        @if (has_permission('warehouses.delete'))
                                            <form action="{{ route('admin.warehouses.destroy', $warehouse->id) }}"
                                                method="POST" @submit.prevent="confirmDelete($event.target)"
                                                class="inline-block">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center rounded-lg border border-red-100 text-red-500 hover:bg-red-50 transition-colors"
                                                    title="Delete">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <i data-lucide="box" class="w-10 h-10 mb-2 opacity-20"></i>
                                        <p class="font-medium text-sm">No warehouses found for your stores.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 📱 MOBILE VIEW (CARDS) --}}
            <div class="md:hidden divide-y divide-gray-50 border-t border-gray-50 bg-white">
                @forelse ($warehouses as $warehouse)
                    <div class="p-4 hover:bg-gray-50/50 transition-colors flex flex-col gap-3"
                        x-show="matchesSearch('{{ strtolower(addslashes($warehouse->name)) }}', '{{ strtolower(addslashes($warehouse->city)) }}')">

                        {{-- Header: Name & Status --}}
                        <div class="flex justify-between items-start gap-2">
                            <div class="min-w-0">
                                <p class="font-bold text-gray-900 text-[14px] truncate">{{ $warehouse->name }}</p>
                                <span
                                    class="inline-block text-[9px] bg-brand-50 text-brand-600 px-1.5 py-0.5 rounded font-black uppercase mt-1">
                                    Store: {{ $warehouse->store->name ?? 'N/A' }}
                                </span>
                            </div>
                            <div class="flex flex-col items-end gap-1 shrink-0">
                                @if ($warehouse->is_active)
                                    <span
                                        class="bg-[#dcfce7] text-[#16a34a] px-2 py-0.5 rounded-md font-bold text-[9px] uppercase">Active</span>
                                @else
                                    <span
                                        class="bg-gray-100 text-gray-400 px-2 py-0.5 rounded-md font-bold text-[9px] uppercase">Disabled</span>
                                @endif

                                @if ($warehouse->is_default)
                                    <span
                                        class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded-md font-black text-[8px] uppercase tracking-tighter border border-blue-100">
                                        Primary Hub
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Context: Contact & Location --}}
                        <div class="flex flex-col gap-2 bg-gray-50/80 px-3 py-2.5 rounded-lg border border-gray-100">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <i data-lucide="user" class="w-3.5 h-3.5 text-gray-400 shrink-0"></i>
                                    <span
                                        class="font-bold text-gray-700 text-[12px] truncate">{{ $warehouse->contact_person ?? 'Not Set' }}</span>
                                </div>
                                <span
                                    class="text-[11px] text-gray-500 font-medium shrink-0">{{ $warehouse->phone ?? '-' }}</span>
                            </div>
                            <div
                                class="flex items-center gap-1.5 pt-1.5 border-t border-gray-100/50 text-[11px] text-gray-500 font-medium italic truncate">
                                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-gray-400 shrink-0"></i>
                                {{ $warehouse->city ?? 'N/A' }}
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-end gap-2 pt-1 border-t border-gray-50 mt-1">
                            @if (has_permission('warehouses.view'))
                                <a href="{{ route('admin.warehouses.show', $warehouse->id) }}"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg border border-blue-100 text-blue-600 hover:bg-blue-50 transition-colors"
                                    title="View Inventory">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                            @endif

                            @if (has_permission('warehouses.update'))
                                <a href="{{ route('admin.warehouses.edit', $warehouse->id) }}"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg border border-brand-100 text-brand-600 hover:bg-brand-50 transition-colors"
                                    title="Edit">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </a>
                            @endif

                            @if (has_permission('warehouses.delete'))
                                <form action="{{ route('admin.warehouses.destroy', $warehouse->id) }}" method="POST"
                                    @submit.prevent="confirmDelete($event.target)" class="inline-block m-0 p-0">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg border border-red-100 text-red-500 hover:bg-red-50 transition-colors"
                                        title="Delete">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-sm text-gray-400 bg-white">
                        <div class="flex flex-col items-center justify-center">
                            <i data-lucide="box" class="w-10 h-10 mb-2 opacity-20"></i>
                            <p class="font-medium text-gray-500">No warehouses found for your stores.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- 🌟 Pagination Controls --}}
            @if ($warehouses->hasPages())
                <div class="px-4 sm:px-6 py-4 border-t border-gray-100 bg-white">
                    {{ $warehouses->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function warehouseIndex() {
            return {
                search: '',

                // Real-time Alpine search filter (applies only to current paginated page)
                matchesSearch(name, city) {
                    if (this.search.trim() === '') return true;
                    const query = this.search.toLowerCase();
                    return name.includes(query) || city.includes(query);
                },

                confirmDelete(form) {
                    BizAlert.confirm(
                        'Delete Warehouse?',
                        'This location will be permanently removed. Ensure all stock has been transferred first!',
                        'Yes, Delete'
                    ).then((result) => {
                        if (result.isConfirmed) {
                            BizAlert.loading('Processing...');
                            form.submit();
                        }
                    });
                }
            }
        }
    </script>
@endpush
