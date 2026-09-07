@extends('layouts.admin')

@section('title', 'Suppliers Management')
@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Suppliers</h1>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        body.modal-open {
            overflow: hidden;
        }
    </style>
@endpush

@section('content')
    <div class="pb-10" x-data="supplierCrud(@js($suppliers->items()))">

        {{-- Alerts --}}
        @if (session('success'))
            <div
                class="bg-green-50 text-green-700 px-5 py-4 rounded-xl text-sm font-bold shadow-sm border border-green-100 mb-6 flex items-center gap-2">
                <i data-lucide="check-circle" class="w-5 h-5"></i> {{ session('success') }}
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', () => BizAlert.toast("{{ session('success') }}", 'success'));
            </script>
        @endif
        @if (session('error'))
            <div
                class="bg-red-50 text-red-700 px-5 py-4 rounded-xl text-sm font-bold shadow-sm border border-red-100 mb-6 flex items-center gap-2">
                <i data-lucide="alert-octagon" class="w-5 h-5"></i> {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div
                class="bg-[#fee2e2] text-[#ef4444] px-5 py-4 rounded-xl text-sm font-bold shadow-sm border border-red-100 mb-6">
                <div class="flex items-center gap-2 mb-2"><i data-lucide="alert-triangle" class="w-5 h-5"></i> Please fix
                    the following errors:</div>
                <ul class="list-disc list-inside pl-7 text-xs font-medium space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- ── TOOLBAR (Search, Filters & Actions) ── --}}
        <div class="bg-white rounded-t-xl shadow-sm border border-gray-100 p-4 border-b-0 mb-0">
            <form id="supplier-filter-form" method="GET" action="{{ route('admin.suppliers.index') }}"
                @submit.prevent="submitForm" @change="submitForm"
                class="flex flex-col xl:flex-row items-start xl:items-center justify-between gap-4">

                {{-- Left Side: Search + Filters --}}
                <div class="flex flex-wrap items-center gap-3 w-full xl:flex-1 xl:max-w-4xl">

                    {{-- Search Group (Input + Clear) --}}
                    <div class="flex flex-row items-center gap-2 flex-1 min-w-[250px] max-w-md w-full">
                        <div class="relative flex-1">
                            {{-- Stable inner flex container keeping search icon centered perfectly --}}
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i data-lucide="search" class="w-4 h-4 text-brand-500"></i>
                            </div>
                            <input type="text" name="search" value="{{ request('search') }}"
                                @input.debounce.400ms="submitForm" placeholder="Search Name, Phone, Email, City or GSTIN..."
                                class="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2.5 text-sm text-gray-700 focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a] outline-none transition-all placeholder-gray-400">
                        </div>

                        <button type="button" @click="clearFilters" x-show="hasActiveFilters" x-cloak
                            class="bg-red-50 hover:bg-red-100 text-red-500 px-3 py-2.5 rounded-lg text-sm font-bold transition-colors shrink-0 flex items-center justify-center gap-1.5"
                            title="Clear Filters">
                            <i data-lucide="x" class="w-4 h-4"></i> Clear
                        </button>
                    </div>

                    {{-- Status Custom Select --}}
                    <div class="w-full sm:w-[150px] shrink-0">
                        <x-custom-select name="status" placeholder="All Status" :options="['active' => 'Active', 'inactive' => 'Inactive']"
                            selected="{{ request('status') }}" />
                    </div>

                    {{-- GST Registration Type Custom Select --}}
                    <div class="w-full sm:w-[170px] shrink-0">
                        <x-custom-select name="registration_type" placeholder="All GST Types" :options="[
                            'regular' => 'Regular',
                            'composition' => 'Composition',
                            'unregistered' => 'Unregistered',
                            'overseas' => 'Overseas',
                        ]"
                            selected="{{ request('registration_type') }}" />
                    </div>

                </div>

                {{-- Right Side: Actions --}}
                <div
                    class="flex flex-row flex-wrap items-center gap-2 w-full xl:w-auto justify-start xl:justify-end mt-2 xl:mt-0">

                    @if (has_permission('suppliers.export'))
                        <button type="button" @click="exportCSV()"
                            class="bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 px-3 md:px-4 py-2.5 rounded-lg text-sm font-bold transition-colors shadow-sm flex items-center gap-1.5 whitespace-nowrap">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4 text-[#108c2a]"></i> CSV
                        </button>
                        <button type="button" @click="exportPDF()"
                            class="bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 px-3 md:px-4 py-2.5 rounded-lg text-sm font-bold transition-colors shadow-sm flex items-center gap-1.5 whitespace-nowrap">
                            <i data-lucide="file-text" class="w-4 h-4 text-red-500"></i> PDF
                        </button>
                    @endif

                    @if (has_permission('suppliers.create'))
                        <x-import-modal type="suppliers"
                            triggerClass="bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-3 md:px-4 py-2.5 rounded-lg text-sm font-bold transition-colors shadow-sm flex items-center gap-1.5 whitespace-nowrap" />
                    @endif

                    @if (has_permission('suppliers.create'))
                        <button type="button" @click="openCreateModal()"
                            class="bg-[#108c2a] hover:bg-green-700 text-white px-4 md:px-5 py-2.5 rounded-lg text-sm font-bold transition-colors shadow-sm flex items-center gap-1.5 whitespace-nowrap">
                            <i data-lucide="plus" class="w-4 h-4"></i> Add Supplier
                        </button>
                    @endif
                </div>
            </form>
        </div>

        {{-- SUPPLIERS TABLE --}}
        <div id="suppliers-list-container"
            class="bg-white rounded-b-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col"
            @click="handlePaginationClick($event)">

            {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead
                        class="text-[11px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100 bg-[#f8fafc]">
                        <tr>
                            <th class="px-6 py-4">SUPPLIER DETAILS</th>
                            <th class="px-6 py-4">CONTACT</th>
                            <th class="px-6 py-4 hidden md:table-cell">CITY</th>
                            <th class="px-6 py-4 text-center hidden md:table-cell">STATUS</th>
                            <th class="px-6 py-4 text-right">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($suppliers as $supplier)
                            <tr class="hover:bg-gray-50/50 transition-colors group">

                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="w-10 h-10 rounded-full bg-green-50 border border-green-100 text-[#108c2a] flex items-center justify-center text-sm font-bold shrink-0">
                                            {{ strtoupper(substr($supplier->name, 0, 1)) }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span
                                                class="font-bold text-[#475569] text-[13.5px]">{{ $supplier->name }}</span>
                                            <span
                                                class="text-[11px] text-gray-400 truncate max-w-[200px]">{{ $supplier->email ?? 'No email added' }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-[#475569]">
                                    <div class="flex items-center gap-2 text-sm text-gray-600 font-medium">
                                        <i data-lucide="phone" class="w-3.5 h-3.5 text-gray-400"></i>
                                        {{ $supplier->phone ?? 'N/A' }}
                                    </div>
                                </td>

                                <td class="px-6 py-4 hidden md:table-cell">
                                    @if ($supplier->city)
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-blue-50 text-blue-600 border border-blue-100">
                                            {{ $supplier->city }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400 italic font-medium">-</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-center hidden md:table-cell">
                                    @if ($supplier->is_active)
                                        <span
                                            class="bg-[#dcfce7] text-[#16a34a] px-3 py-1 rounded-md font-bold text-[10px] uppercase tracking-wider">Active</span>
                                    @else
                                        <span
                                            class="bg-gray-200 text-gray-500 px-3 py-1 rounded-md font-bold text-[10px] uppercase tracking-wider">Inactive</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2 transition-opacity">
                                        <button @click="openViewModal({{ $supplier->toJson() }})"
                                            class="w-8 h-8 rounded-lg bg-gray-50 text-gray-500 hover:bg-gray-100 hover:text-gray-700 flex items-center justify-center transition-colors"
                                            title="View Details">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </button>

                                        @if (has_permission('suppliers.update'))
                                            <button @click="openEditModal({{ $supplier->toJson() }})"
                                                class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-700 flex items-center justify-center transition-colors"
                                                title="Edit">
                                                <i data-lucide="pencil" class="w-4 h-4"></i>
                                            </button>
                                        @endif


                                        @if (has_permission('suppliers.delete'))
                                            <form action="{{ route('admin.suppliers.destroy', $supplier->id) }}"
                                                method="POST" @submit.prevent="confirmDelete($event.target)"
                                                class="inline-block">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 hover:text-red-600 flex items-center justify-center transition-colors"
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
                                <td colspan="5" class="px-6 py-12 text-center text-gray-400 font-medium">
                                    <div class="flex flex-col items-center justify-center">
                                        <i data-lucide="users" class="w-12 h-12 mb-3 text-gray-300"></i>
                                        <p class="text-sm font-medium">No suppliers found matching your criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 📱 MOBILE VIEW (CARDS) --}}
            <div class="md:hidden divide-y divide-gray-50 border-t border-gray-50 bg-white">
                @forelse ($suppliers as $supplier)
                    <div class="p-4 hover:bg-gray-50/50 transition-colors flex flex-col gap-3">

                        {{-- Header: Avatar, Name & Status --}}
                        <div class="flex justify-between items-start gap-2">
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="w-10 h-10 rounded-full bg-green-50 border border-green-100 text-[#108c2a] flex items-center justify-center text-sm font-bold shrink-0">
                                    {{ strtoupper(substr($supplier->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-[14px] text-gray-900 truncate">{{ $supplier->name }}</p>
                                    <p class="text-[11px] text-gray-500 mt-0.5 truncate">
                                        {{ $supplier->email ?? 'No email added' }}</p>
                                </div>
                            </div>
                            <div class="shrink-0">
                                @if ($supplier->is_active)
                                    <span
                                        class="bg-[#dcfce7] text-[#16a34a] px-2 py-0.5 rounded text-[9px] font-extrabold uppercase tracking-wider">Active</span>
                                @else
                                    <span
                                        class="bg-gray-200 text-gray-500 px-2 py-0.5 rounded text-[9px] font-extrabold uppercase tracking-wider">Inactive</span>
                                @endif
                            </div>
                        </div>

                        {{-- Details: Phone & City --}}
                        <div
                            class="flex items-center justify-between bg-gray-50/80 px-3 py-2.5 rounded-lg border border-gray-100">
                            <div class="flex items-center gap-1.5 text-sm text-gray-600 font-medium">
                                <i data-lucide="phone" class="w-3.5 h-3.5 text-gray-400"></i>
                                {{ $supplier->phone ?? 'N/A' }}
                            </div>
                            @if ($supplier->city)
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-600 border border-blue-100">
                                    {{ $supplier->city }}
                                </span>
                            @else
                                <span class="text-[10px] text-gray-400 italic font-medium">-</span>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-end gap-2 pt-1 border-t border-gray-50 mt-1">
                            <button type="button" @click="openViewModal({{ $supplier->toJson() }})"
                                class="w-8 h-8 rounded-lg bg-gray-50 text-gray-500 hover:bg-gray-100 hover:text-gray-700 flex items-center justify-center transition-colors"
                                title="View Details">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>

                            @if (has_permission('suppliers.update'))
                                <button type="button" @click="openEditModal({{ $supplier->toJson() }})"
                                    class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-700 flex items-center justify-center transition-colors"
                                    title="Edit">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </button>
                            @endif

                            @if (has_permission('suppliers.delete'))
                                <form action="{{ route('admin.suppliers.destroy', $supplier->id) }}" method="POST"
                                    @submit.prevent="confirmDelete($event.target)" class="inline-block">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 hover:text-red-600 flex items-center justify-center transition-colors"
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
                            <i data-lucide="users" class="w-12 h-12 mb-3 text-gray-300 opacity-50"></i>
                            <p class="font-medium text-gray-500">No suppliers found matching your criteria.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="px-6 py-4 border-t border-gray-100 bg-white">
                {{ $suppliers->links() }}
            </div>
        </div>

        {{-- CREATE / EDIT MODAL --}}
        <div x-show="isModalOpen" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex justify-center items-start md:items-center p-4 sm:p-6 bg-black/60 backdrop-blur-sm transition-opacity"
            x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="relative w-full max-w-4xl my-auto">
                <div class="relative bg-white rounded-2xl shadow-2xl border border-gray-100 flex flex-col h-full">

                    <div
                        class="flex items-center justify-between p-6 border-b border-gray-100 bg-white sticky top-0 z-20 shrink-0 rounded-t-2xl">
                        <div>
                            <h3 class="text-xl font-bold text-[#212538]"
                                x-text="modalMode === 'create' ? 'Onboard New Supplier' : 'Update Supplier Details'"></h3>
                            <p class="text-xs text-gray-400 font-medium mt-1">Maintain accurate vendor records for
                                inventory and billing.</p>
                        </div>
                        <button @click="closeModal()" type="button"
                            class="text-gray-400 hover:bg-gray-100 hover:text-gray-700 rounded-full p-2 transition-colors">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <form :action="formAction" method="POST" class="flex flex-col flex-1"
                        @submit="BizAlert.loading('Saving...')">
                        @csrf
                        <template x-if="modalMode === 'edit'">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        {{-- Changed viewport wrapper behavior to overflow-visible to handle floating elements securely --}}
                        <div class="p-6 overflow-visible space-y-8">

                            <div>
                                <h4 class="text-xs font-bold text-gray-800 mb-4 uppercase tracking-widest pb-2">1.
                                    Basic Information</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div class="md:col-span-2">
                                        <label
                                            class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Company
                                            / Vendor Name <span class="text-red-500">*</span></label>
                                        <input type="text" name="name" x-model="formData.name"
                                            placeholder="e.g. Acme Corp"
                                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-700 focus:ring-2 focus:ring-[#108c2a]/20 focus:border-[#108c2a] outline-none transition-all">
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Phone
                                            Number</label>
                                        <input type="tel" name="phone" x-model="formData.phone"
                                            placeholder="+91 00000 00000" maxlength="10" minlength="10"
                                            pattern="[0-9]{10}" inputmode="numeric"
                                            oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-700 focus:ring-2 focus:ring-[#108c2a]/20 focus:border-[#108c2a] outline-none transition-all">
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Email
                                            Address</label>
                                        <input type="email" name="email" x-model="formData.email"
                                            placeholder="vendor@email.com"
                                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-700 focus:ring-2 focus:ring-[#108c2a]/20 focus:border-[#108c2a] outline-none transition-all">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4
                                    class="text-xs font-bold text-[#108c2a] mb-4 uppercase tracking-widest border-b border-[#108c2a]/20 pb-2">
                                    2. Compliance & Location</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5 items-end">
                                    <div class="w-full shrink-0">
                                        <label
                                            class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Registration
                                            Type</label>
                                        <x-custom-select name="registration_type" id="modal-supplier-registration-type"
                                            placeholder="Select Type" :options="[
                                                'regular' => 'Regular',
                                                'composition' => 'Composition',
                                                'unregistered' => 'Unregistered',
                                                'sez' => 'SEZ',
                                                'overseas' => 'Overseas',
                                            ]" selected="" />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">GSTIN</label>
                                        <input type="text" name="gstin" x-model="formData.gstin"
                                            placeholder="15 Digit GSTIN" maxlength="15"
                                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm uppercase focus:ring-2 focus:ring-[#108c2a]/20 focus:border-[#108c2a] outline-none">
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">PAN
                                            Number</label>
                                        <input type="text" name="pan" x-model="formData.pan"
                                            placeholder="10 Digit PAN" maxlength="10"
                                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm uppercase focus:ring-2 focus:ring-[#108c2a]/20 focus:border-[#108c2a] outline-none">
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5 items-end">
                                    <div>
                                        <label
                                            class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">City</label>
                                        <input type="text" name="city" x-model="formData.city"
                                            placeholder="e.g. Mumbai"
                                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#108c2a]/20 focus:border-[#108c2a] outline-none">
                                    </div>
                                    <div class="w-full shrink-0">
                                        <label
                                            class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">State
                                        </label>
                                        <x-custom-select name="state_id" id="modal-supplier-state-id"
                                            placeholder="Select State" :options="collect($states ?? [])
                                                ->pluck('name', 'id')
                                                ->toArray()" selected="" />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">PIN
                                            Code</label>
                                        <input type="text" name="pincode" x-model="formData.pincode"
                                            placeholder="e.g. 400001"
                                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#108c2a]/20 focus:border-[#108c2a] outline-none">
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Physical
                                        Address</label>
                                    <textarea name="address" x-model="formData.address" rows="2" placeholder="Full office or warehouse address..."
                                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-700 focus:ring-2 focus:ring-[#108c2a]/20 focus:border-[#108c2a] outline-none transition-all resize-none"></textarea>
                                </div>
                            </div>

                            <div>
                                <h4
                                    class="text-xs font-bold text-orange-600 mb-4 uppercase tracking-widest border-b border-orange-100 pb-2">
                                    3. Banking & Credit Setup</h4>

                                <div
                                    class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-5 bg-orange-50/40 p-5 rounded-xl border border-orange-100 items-end">
                                    <div>
                                        <label
                                            class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">Opening
                                            Bal. (₹)</label>
                                        <input type="number" step="0.01" name="opening_balance"
                                            x-model="formData.opening_balance"
                                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none bg-white">
                                    </div>
                                    <div class="w-full shrink-0">
                                        <label
                                            class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">Balance
                                            Type</label>
                                        <x-custom-select name="balance_type" id="modal-supplier-balance-type"
                                            placeholder="Select Balance Type" :options="[
                                                'payable' => 'Payable (We owe)',
                                                'advance' => 'Advance (They owe)',
                                            ]" selected="" />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">Credit
                                            Days</label>
                                        <input type="number" name="credit_days" x-model="formData.credit_days"
                                            placeholder="e.g. 30"
                                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none bg-white">
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">Credit
                                            Limit (₹)</label>
                                        <input type="number" step="0.01" name="credit_limit"
                                            x-model="formData.credit_limit" placeholder="0 = No Limit"
                                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none bg-white">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
                                    <div class="md:col-span-2">
                                        <label
                                            class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Bank
                                            Name</label>
                                        <input type="text" name="bank_name" x-model="formData.bank_name"
                                            placeholder="e.g. HDFC Bank"
                                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#108c2a]/20 focus:border-[#108c2a] outline-none">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label
                                            class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Account
                                            Number</label>
                                        <input type="text" name="account_number" x-model="formData.account_number"
                                            placeholder="Account Number"
                                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#108c2a]/20 focus:border-[#108c2a] outline-none">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label
                                            class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">IFSC
                                            Code</label>
                                        <input type="text" name="ifsc_code" x-model="formData.ifsc_code"
                                            placeholder="e.g. HDFC0001234"
                                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm uppercase focus:ring-2 focus:ring-[#108c2a]/20 focus:border-[#108c2a] outline-none">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label
                                            class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Branch
                                            Name</label>
                                        <input type="text" name="branch" x-model="formData.branch"
                                            placeholder="e.g. Navrangpura"
                                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#108c2a]/20 focus:border-[#108c2a] outline-none">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-xs font-bold text-gray-800 mb-4 uppercase tracking-widest pb-2">4.
                                    Additional Notes</h4>
                                <textarea x-model="formData.notes" rows="2" name="notes"
                                    placeholder="Any internal notes or terms regarding this supplier..."
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-700 focus:ring-2 focus:ring-[#108c2a]/20 focus:border-[#108c2a] outline-none transition-all resize-none mb-4"></textarea>

                                <label
                                    class="relative inline-flex items-center cursor-pointer bg-gray-50 p-3 rounded-xl border border-gray-100 pr-5 w-fit">
                                    <input type="checkbox" name="is_active" value="1" x-model="formData.is_active"
                                        class="sr-only peer">
                                    <div
                                        class="relative w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#108c2a]">
                                    </div>
                                    <span class="ms-3 text-sm font-bold text-gray-700">Active Supplier Account</span>
                                </label>
                            </div>

                        </div>

                        <div
                            class="p-5 border-t border-gray-100 bg-gray-50 flex justify-end gap-3 sticky bottom-0 z-20 shrink-0 rounded-b-2xl">
                            <button type="button" @click="closeModal()"
                                class="px-6 py-2.5 rounded-xl border border-gray-200 text-gray-600 text-sm font-bold hover:bg-white hover:shadow-sm transition-all">Cancel</button>
                            <button type="submit"
                                class="bg-brand-500 hover:bg-brand-600 text-white px-8 py-2.5 rounded-xl text-sm font-bold shadow-md transition-all active:scale-95 flex items-center gap-2">
                                <i data-lucide="check-circle" class="w-4 h-4"></i>
                                <span x-text="modalMode === 'create' ? 'Save Supplier' : 'Update Details'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- 👁️ VIEW SUPPLIER MODAL (read-only) --}}
        <div x-show="isViewModalOpen" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex justify-center items-start md:items-center p-4 sm:p-6 bg-black/60 backdrop-blur-sm transition-opacity"
            x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="relative w-full max-w-2xl my-auto">
                <div class="relative bg-white rounded-2xl shadow-2xl border border-gray-100 flex flex-col max-h-[90vh]">

                    {{-- Header --}}
                    <div
                        class="flex items-center justify-between p-6 border-b border-gray-100 bg-white sticky top-0 z-20 shrink-0 rounded-t-2xl">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-green-50 border border-green-100 text-[#108c2a] flex items-center justify-center text-base font-bold shrink-0"
                                x-text="(viewingSupplier.name || '?').charAt(0).toUpperCase()"></div>
                            <div>
                                <h3 class="text-lg font-bold text-[#212538]" x-text="viewingSupplier.name"></h3>
                                <span
                                    class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-extrabold uppercase tracking-wider"
                                    :class="viewingSupplier.is_active ? 'bg-[#dcfce7] text-[#16a34a]' :
                                        'bg-gray-200 text-gray-500'"
                                    x-text="viewingSupplier.is_active ? 'Active' : 'Inactive'"></span>
                            </div>
                        </div>
                        <button @click="closeViewModal()" type="button"
                            class="text-gray-400 hover:bg-gray-100 hover:text-gray-700 rounded-full p-2 transition-colors">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    {{-- Body --}}
                    <div class="p-6 overflow-y-auto space-y-7">

                        {{-- Contact --}}
                        <div>
                            <h4 class="text-xs font-bold text-gray-800 mb-3 uppercase tracking-widest border-b pb-2">
                                Contact</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Phone</p>
                                    <p class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                                        <i data-lucide="phone" class="w-3.5 h-3.5 text-gray-400"></i>
                                        <span x-text="viewingSupplier.phone || 'Not provided'"></span>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Email</p>
                                    <p class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                                        <i data-lucide="mail" class="w-3.5 h-3.5 text-gray-400"></i>
                                        <span x-text="viewingSupplier.email || 'Not provided'"></span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Compliance & Location --}}
                        <div>
                            <h4
                                class="text-xs font-bold text-[#108c2a] mb-3 uppercase tracking-widest border-b border-[#108c2a]/20 pb-2">
                                Compliance & Location</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">
                                        Registration Type</p>
                                    <p class="text-sm font-semibold text-gray-700 capitalize"
                                        x-text="(viewingSupplier.registration_type || 'regular').replace('_', ' ')"></p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">GSTIN</p>
                                    <p class="text-sm font-semibold text-gray-700 font-mono"
                                        x-text="viewingSupplier.gstin || '—'"></p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">PAN</p>
                                    <p class="text-sm font-semibold text-gray-700 font-mono"
                                        x-text="viewingSupplier.pan || '—'"></p>
                                </div>
                            </div>
                            <div class="bg-gray-50/80 px-4 py-3 rounded-lg border border-gray-100">
                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Address</p>
                                <p class="text-sm font-medium text-gray-700">
                                    <span x-text="viewingSupplier.address || ''"></span>
                                    <template x-if="viewingSupplier.address"><br></template>
                                    <span
                                        x-text="[viewingSupplier.city, viewingSupplier.state?.name, viewingSupplier.pincode].filter(Boolean).join(', ') || 'No address on file'"></span>
                                </p>
                            </div>
                        </div>

                        {{-- Banking --}}
                        <div>
                            <h4
                                class="text-xs font-bold text-orange-600 mb-3 uppercase tracking-widest border-b border-orange-100 pb-2">
                                Banking Details</h4>
                            <template x-if="viewingSupplier.bank_name || viewingSupplier.account_number">
                                <div
                                    class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-orange-50/40 p-4 rounded-xl border border-orange-100">
                                    <div>
                                        <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Bank
                                            Name</p>
                                        <p class="text-sm font-semibold text-gray-700"
                                            x-text="viewingSupplier.bank_name || '—'"></p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">
                                            Account Number</p>
                                        <p class="text-sm font-semibold text-gray-700 font-mono"
                                            x-text="viewingSupplier.account_number || '—'"></p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">IFSC
                                            Code</p>
                                        <p class="text-sm font-semibold text-gray-700 font-mono"
                                            x-text="viewingSupplier.ifsc_code || '—'"></p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Branch
                                        </p>
                                        <p class="text-sm font-semibold text-gray-700"
                                            x-text="viewingSupplier.branch || '—'"></p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="!(viewingSupplier.bank_name || viewingSupplier.account_number)">
                                <p class="text-sm text-gray-400 italic">No banking details on file.</p>
                            </template>
                        </div>

                        {{-- Balance & Credit --}}
                        <div>
                            <h4 class="text-xs font-bold text-gray-800 mb-3 uppercase tracking-widest border-b pb-2">
                                Balance & Credit</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="p-4 rounded-xl border"
                                    :class="viewingSupplier.balance_type === 'advance' ? 'bg-green-50 border-green-100' :
                                        'bg-red-50 border-red-100'">
                                    <p class="text-[11px] font-bold uppercase tracking-wider mb-1"
                                        :class="viewingSupplier.balance_type === 'advance' ? 'text-green-600' : 'text-red-500'"
                                        x-text="viewingSupplier.balance_type === 'advance' ? 'Advance (They owe)' : 'Payable (We owe)'">
                                    </p>
                                    <p class="text-lg font-black"
                                        :class="viewingSupplier.balance_type === 'advance' ? 'text-green-700' : 'text-red-600'">
                                        ₹<span x-text="formatCurrency(viewingSupplier.current_balance || 0)"></span>
                                    </p>
                                </div>
                                <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/80">
                                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Credit
                                        Days</p>
                                    <p class="text-lg font-black text-gray-700"
                                        x-text="(viewingSupplier.credit_days || 0) + ' days'"></p>
                                </div>
                                <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/80">
                                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Credit
                                        Limit</p>
                                    <p class="text-lg font-black text-gray-700">
                                        <span x-show="(viewingSupplier.credit_limit || 0) > 0">₹<span
                                                x-text="formatCurrency(viewingSupplier.credit_limit)"></span></span>
                                        <span x-show="!(viewingSupplier.credit_limit > 0)">No Limit</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Notes --}}
                        <template x-if="viewingSupplier.notes">
                            <div>
                                <h4 class="text-xs font-bold text-gray-800 mb-3 uppercase tracking-widest border-b pb-2">
                                    Notes</h4>
                                <p class="text-sm text-gray-600 whitespace-pre-line" x-text="viewingSupplier.notes"></p>
                            </div>
                        </template>
                    </div>

                    {{-- Footer --}}
                    <div class="p-5 border-t border-gray-100 bg-gray-50 flex justify-end gap-3 shrink-0 rounded-b-2xl">
                        <button type="button" @click="closeViewModal()"
                            class="px-6 py-2.5 rounded-xl border border-gray-200 text-gray-600 text-sm font-bold hover:bg-white hover:shadow-sm transition-all">Close</button>
                        @if (true)
                            <button type="button" @click="closeViewModal(); openEditModal(viewingSupplier)"
                                class="bg-brand-500 hover:bg-brand-600 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-md transition-all active:scale-95 flex items-center gap-2">
                                <i data-lucide="pencil" class="w-4 h-4"></i> Edit Supplier
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        function supplierCrud(allSuppliersData) {
            return {
                allSuppliers: allSuppliersData,
                isModalOpen: false,
                modalMode: 'create',
                formAction: '{{ route('admin.suppliers.store') }}',

                // 👁️ View modal state
                isViewModalOpen: false,
                viewingSupplier: {},

                openViewModal(sup) {
                    document.body.classList.add('modal-open');
                    this.viewingSupplier = sup;
                    this.isViewModalOpen = true;
                },

                closeViewModal() {
                    this.isViewModalOpen = false;
                    document.body.classList.remove('modal-open');
                },

                formatCurrency(val) {
                    return parseFloat(val || 0).toLocaleString('en-IN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                },

                // SPA-Safe Search & Filter Logic
                hasActiveFilters: false,

                init() {
                    this.checkActiveFilters();
                    window.submitSupplierForm = () => this.submitForm();
                },

                handlePaginationClick(e) {
                    const pageLink = e.target.closest('a[href*="?page="]');
                    if (pageLink) {
                        e.preventDefault();
                        this.fetchResults(pageLink.href);
                    }
                },

                checkActiveFilters() {
                    const form = document.getElementById('supplier-filter-form');
                    if (!form) return;
                    const formData = new FormData(form);
                    this.hasActiveFilters = [...formData.entries()].some(([, v]) => v && String(v).trim() !== '');
                },

                submitForm() {
                    const form = document.getElementById('supplier-filter-form');
                    if (!form) return;
                    const url = new URL(form.action);
                    new FormData(form).forEach((v, k) => {
                        if (v) url.searchParams.set(k, v);
                    });

                    this.fetchResults(url.toString());
                },

                clearFilters() {
                    const form = document.getElementById('supplier-filter-form');
                    if (form) {
                        form.querySelectorAll('input[type="text"], input[type="search"], select').forEach(el => {
                            el.value = '';
                            // Dispatch tracking event loops to sync the custom elements safely back to empty states
                            el.dispatchEvent(new Event('change', {
                                bubbles: true
                            }));
                        });
                    }
                    this.fetchResults(form.action);
                },

                fetchResults(url) {
                    const targetContainer = document.getElementById('suppliers-list-container');
                    if (!targetContainer) return;

                    targetContainer.style.opacity = '0.5';
                    targetContainer.style.pointerEvents = 'none';

                    fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(res => res.text())
                        .then(html => {
                            const doc = new DOMParser().parseFromString(html, 'text/html');
                            const newContainer = doc.getElementById('suppliers-list-container');

                            if (newContainer) {
                                targetContainer.innerHTML = newContainer.innerHTML;

                                // Update internal data for PDF/CSV exports based on the new page
                                const newAlpineData = newContainer.closest('[x-data]')?.getAttribute('x-data');
                                if (newAlpineData) {
                                    const match = newAlpineData.match(/supplierCrud\((.*?)\)/);
                                    if (match && match[1]) {
                                        try {
                                            this.allSuppliers = JSON.parse(match[1]);
                                        } catch (e) {}
                                    }
                                }
                            }

                            targetContainer.style.opacity = '1';
                            targetContainer.style.pointerEvents = 'auto';
                            window.history.pushState({}, '', url);

                            this.checkActiveFilters();
                            if (typeof lucide !== 'undefined') lucide.createIcons();
                        })
                        .catch(() => {
                            targetContainer.style.opacity = '1';
                            targetContainer.style.pointerEvents = 'auto';
                        });
                },

                formData: {
                    name: '',
                    email: '',
                    phone: '',
                    address: '',
                    city: '',
                    pincode: '',
                    state_id: '',
                    gstin: '',
                    pan: '',
                    registration_type: 'regular',
                    bank_name: '',
                    account_number: '',
                    ifsc_code: '',
                    branch: '',
                    opening_balance: 0,
                    balance_type: 'payable',
                    credit_days: 0,
                    credit_limit: 0,
                    is_active: true,
                    notes: '',
                },

                matchesSearch(name, phone, email = '', city) {
                    if (this.search === '') return true;
                    const query = this.search.toLowerCase();
                    return name.includes(query) || phone.includes(query) || email.includes(query) || city.includes(query);
                },

                openCreateModal() {
                    document.body.classList.add('modal-open');
                    this.modalMode = 'create';
                    this.formAction = '{{ route('admin.suppliers.store') }}';

                    // Programmatically flush values inside the premium selectors to handle fresh state swaps cleanly
                    this.$nextTick(() => {
                        ['modal-supplier-registration-type', 'modal-supplier-state-id',
                            'modal-supplier-balance-type'
                        ].forEach(id => {
                            const selectEl = document.getElementById(id);
                            if (selectEl) {
                                selectEl.value = id === 'modal-supplier-registration-type' ? 'regular' : (
                                    id === 'modal-supplier-balance-type' ? 'payable' : '');
                                selectEl.dispatchEvent(new Event('change', {
                                    bubbles: true
                                }));
                            }
                        });
                    });

                    this.formData = {
                        name: '',
                        email: '',
                        phone: '',
                        address: '',
                        city: '',
                        pincode: '',
                        state_id: '',
                        gstin: '',
                        pan: '',
                        registration_type: 'regular',
                        bank_name: '',
                        account_number: '',
                        ifsc_code: '',
                        branch: '',
                        opening_balance: 0,
                        balance_type: 'payable',
                        credit_days: 0,
                        credit_limit: 0,
                        is_active: true,
                        notes: '',
                    };
                    this.isModalOpen = true;
                },

                openEditModal(sup) {
                    document.body.classList.add('modal-open');
                    this.modalMode = 'edit';
                    this.formAction = `/admin/suppliers/${sup.id}`;

                    // Hydrate structural parameters into the open select fields context immediately using nextTick
                    this.$nextTick(() => {
                        const regTypeSelect = document.getElementById('modal-supplier-registration-type');
                        if (regTypeSelect) {
                            regTypeSelect.value = sup.registration_type || 'regular';
                            regTypeSelect.dispatchEvent(new Event('change', {
                                bubbles: true
                            }));
                        }
                        const stateSelect = document.getElementById('modal-supplier-state-id');
                        if (stateSelect) {
                            stateSelect.value = sup.state_id || '';
                            stateSelect.dispatchEvent(new Event('change', {
                                bubbles: true
                            }));
                        }
                        const balTypeSelect = document.getElementById('modal-supplier-balance-type');
                        if (balTypeSelect) {
                            balTypeSelect.value = sup.balance_type || 'payable';
                            balTypeSelect.dispatchEvent(new Event('change', {
                                bubbles: true
                            }));
                        }
                    });

                    this.formData = {
                        name: sup.name,
                        email: sup.email || '',
                        phone: sup.phone || '',
                        address: sup.address || '',
                        city: sup.city || '',
                        pincode: sup.pincode || '',
                        state_id: sup.state_id || '',
                        gstin: sup.gstin || '',
                        pan: sup.pan || '',
                        registration_type: sup.registration_type || 'regular',
                        bank_name: sup.bank_name || '',
                        account_number: sup.account_number || '',
                        ifsc_code: sup.ifsc_code || '',
                        branch: sup.branch || '',
                        opening_balance: sup.opening_balance || 0,
                        balance_type: sup.balance_type || 'payable',
                        credit_days: sup.credit_days || 0,
                        credit_limit: sup.credit_limit || 0,
                        is_active: sup.is_active,
                        notes: sup.notes || '',
                    };
                    this.isModalOpen = true;
                },

                closeModal() {
                    this.isModalOpen = false;
                    document.body.classList.remove('modal-open');
                },

                confirmDelete(form) {
                    BizAlert.confirm('Delete Supplier?',
                            'Old transaction records will be preserved, but this supplier will be hidden.', 'Yes, Delete')
                        .then((result) => {
                            if (result.isConfirmed) {
                                BizAlert.loading('Processing...');
                                form.submit();
                            }
                        });
                },

                // --- EXPORT LOGIC ---
                exportCSV() {
                    const headers = ["Name", "Email", "Phone", "GSTIN", "PAN", "City", "Pincode", "Address", "Bank Name",
                        "Acc No.", "IFSC", "Current Balance"
                    ];
                    const rows = this.allSuppliers.map(sup => [
                        `"${sup.name || ''}"`,
                        `"${sup.email || ''}"`,
                        `"${sup.phone || ''}"`,
                        `"${sup.gstin || ''}"`,
                        `"${sup.pan || ''}"`,
                        `"${sup.city || ''}"`,
                        `"${sup.pincode || ''}"`,
                        `"${sup.address || ''}"`,
                        `"${sup.bank_name || ''}"`,
                        `"${sup.account_number || ''}"`,
                        `"${sup.ifsc_code || ''}"`,
                        `"${sup.current_balance || 0}"`
                    ]);

                    let csvContent = headers.join(",") + "\n" + rows.map(e => e.join(",")).join("\n");
                    const blob = new Blob([csvContent], {
                        type: 'text/csv;charset=utf-8;'
                    });
                    const link = document.createElement("a");
                    link.href = URL.createObjectURL(blob);
                    link.setAttribute("download", "Suppliers_Full_Report.csv");
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                    BizAlert.toast('Supplier CSV Exported!', 'success');
                },

                // --- EXPORT SPECIFIC DATA TO PDF (SERVER BACKEND) ---
                exportPDF() {
                    BizAlert.loading('Compiling Supplier Directory PDF...');

                    const form = document.getElementById('supplier-filter-form');
                    const url = new URL('{{ route('admin.suppliers.download-pdf') }}', window.location.origin);

                    // Harvest live search/dropdown values to pass to downstream DomPDF query builder
                    if (form) {
                        new FormData(form).forEach((v, k) => {
                            if (v && String(v).trim() !== '') {
                                url.searchParams.set(k, v);
                            }
                        });
                    }

                    // Direct browser window buffer stream target route conversion execution
                    window.location.href = url.toString();

                    setTimeout(() => {
                        if (typeof swal !== 'undefined') swal.close();
                    }, 2000);
                }
            }
        }
    </script>
@endpush
