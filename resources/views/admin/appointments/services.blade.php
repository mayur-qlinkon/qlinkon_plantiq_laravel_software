@extends('layouts.admin')

@section('title', 'Appointment Services')

@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Appointment Services</h1>
@endsection

@section('content')
    <div class="pb-12 space-y-6" x-data="apptServicesCrud()">

        {{-- ── Flash Toasts ── --}}
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

        {{-- ── Page Header ── --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Appointment Services</h2>
                <p class="text-sm text-gray-400 mt-0.5">Services that customers can book appointments for.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.appointments.slots.index') }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-bold text-gray-600 hover:bg-gray-50 transition-colors shadow-sm">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">Manage Slots</span>
                    <span class="sm:hidden">Slots</span>
                </a>
                <button @click="openCreate()"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-sm font-bold transition-colors shadow-md active:scale-95">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">Add Service</span>
                    <span class="sm:hidden">Add</span>
                </button>
            </div>
        </div>

        {{-- ── Table Card ── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

            {{-- Card Toolbar --}}
            <div
                class="px-5 py-4 border-b border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <span class="text-[15px] font-bold text-gray-800">
                    All Services
                    <span class="ml-2 text-xs font-bold text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">
                        {{ $services->total() }}
                    </span>
                </span>
                <div class="relative w-full sm:w-64">
                    {{-- Stable flex wrapper that permanently locks the icon in the vertical center --}}
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <i data-lucide="search" class="w-4 h-4 text-gray-400"></i>
                    </div>
                    <input type="text" x-model="search" placeholder="Search services..."
                        class="w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl text-sm text-gray-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none bg-gray-50 transition-colors">
                </div>
            </div>

            {{-- ── DESKTOP TABLE ── --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-3.5 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Service
                                Name</th>
                            <th class="px-6 py-3.5 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Duration
                            </th>
                            <th class="px-6 py-3.5 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Price</th>
                            <th
                                class="px-6 py-3.5 text-[11px] font-bold text-gray-400 uppercase tracking-wider text-center">
                                Sort</th>
                            <th
                                class="px-6 py-3.5 text-[11px] font-bold text-gray-400 uppercase tracking-wider text-center">
                                Status</th>
                            <th class="px-6 py-3.5 text-[11px] font-bold text-gray-400 uppercase tracking-wider text-right">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($services as $service)
                            <tr class="hover:bg-gray-50/60 transition-colors"
                                x-show="matchSearch('{{ strtolower(addslashes($service->name)) }}')">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-800 text-[13.5px]">{{ $service->name }}</div>
                                    @if ($service->description)
                                        <div class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">
                                            {{ Str::limit($service->description, 60) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1 text-sm text-gray-600 font-medium">
                                        <i data-lucide="timer" class="w-3.5 h-3.5 text-gray-400"></i>
                                        {{ $service->duration_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-gray-700 text-sm">{{ $service->price_label }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="text-xs font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-lg">{{ $service->sort_order }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if ($service->is_active)
                                        <span
                                            class="bg-green-50 text-green-700 px-3 py-1 rounded-lg font-bold text-[11px] uppercase tracking-wide">Active</span>
                                    @else
                                        <span
                                            class="bg-gray-100 text-gray-400 px-3 py-1 rounded-lg font-bold text-[11px] uppercase tracking-wide">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openEdit({{ $service->toJson() }})"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg border border-brand-500/30 text-brand-600 hover:bg-brand-50 transition-colors"
                                            title="Edit">
                                            <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                        </button>
                                        <button
                                            @click="deleteService({{ $service->id }}, '{{ addslashes($service->name) }}')"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg border border-red-200 text-red-500 hover:bg-red-50 transition-colors"
                                            title="Delete">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
                                            <i data-lucide="briefcase" class="w-6 h-6 text-gray-400"></i>
                                        </div>
                                        <p class="text-sm font-bold text-gray-400">No services yet</p>
                                        <button @click="openCreate()"
                                            class="text-xs text-brand-600 font-bold hover:underline">Add your first service
                                            →</button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ── MOBILE CARDS ── --}}
            <div class="md:hidden divide-y divide-gray-100">
                @forelse($services as $service)
                    <div class="p-4 hover:bg-gray-50/50 transition-colors"
                        x-show="matchSearch('{{ strtolower(addslashes($service->name)) }}')">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-bold text-gray-800 text-[14px]">{{ $service->name }}</span>
                                    @if ($service->is_active)
                                        <span
                                            class="bg-green-50 text-green-700 px-2 py-0.5 rounded-md font-bold text-[10px] uppercase">Active</span>
                                    @else
                                        <span
                                            class="bg-gray-100 text-gray-400 px-2 py-0.5 rounded-md font-bold text-[10px] uppercase">Inactive</span>
                                    @endif
                                </div>
                                @if ($service->description)
                                    <p class="text-xs text-gray-400 mt-1 line-clamp-2">{{ $service->description }}</p>
                                @endif
                                <div class="flex items-center gap-4 mt-2.5">
                                    <span class="flex items-center gap-1 text-xs text-gray-500 font-medium">
                                        <i data-lucide="timer" class="w-3 h-3"></i>
                                        {{ $service->duration_label }}
                                    </span>
                                    <span class="flex items-center gap-1 text-xs font-bold text-gray-700">
                                        <i data-lucide="indian-rupee" class="w-3 h-3 text-gray-400"></i>
                                        {{ $service->price_label }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <button @click="openEdit({{ $service->toJson() }})"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg border border-brand-500/30 text-brand-600 hover:bg-brand-50 transition-colors">
                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                </button>
                                <button @click="deleteService({{ $service->id }}, '{{ addslashes($service->name) }}')"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg border border-red-200 text-red-500 hover:bg-red-50 transition-colors">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-16 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
                                <i data-lucide="briefcase" class="w-6 h-6 text-gray-400"></i>
                            </div>
                            <p class="text-sm font-bold text-gray-400">No services yet</p>
                            <button @click="openCreate()" class="text-xs text-brand-600 font-bold hover:underline">Add
                                your first service →</button>
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- ── Pagination ── --}}
            @if ($services->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $services->withQueryString()->links() }}
                </div>
            @endif
        </div>

        {{-- ════════════════════════════════════════════
         MODAL — Create / Edit
    ════════════════════════════════════════════ --}}
        <div x-show="showModal" x-cloak
            class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 backdrop-blur-sm p-0 sm:p-4"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="relative w-full sm:max-w-lg bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl overflow-hidden"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="translate-y-8 sm:translate-y-0 sm:scale-95 opacity-0"
                x-transition:enter-end="translate-y-0 sm:scale-100 opacity-100" @click.stop>

                {{-- Modal Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-bold text-gray-800" x-text="isEdit ? 'Edit Service' : 'Add New Service'">
                    </h3>
                    <button @click="closeModal()"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 transition-colors">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                {{-- Modal Form --}}
                <form :action="formAction" method="POST" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
                    @csrf
                    <template x-if="isEdit"><input type="hidden" name="_method" value="PUT"></template>

                    {{-- Name --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                            Service Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" x-model="form.name" required
                            placeholder="e.g. Garden Maintenance"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none bg-gray-50 transition-colors">
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                            Description <span class="text-gray-400 font-normal">(optional)</span>
                        </label>
                        <textarea name="description" x-model="form.description" rows="3"
                            placeholder="Brief description shown on the booking form..."
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none bg-gray-50 transition-colors resize-none"></textarea>
                    </div>

                    {{-- Price + Duration Row --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                                Price (₹) <span class="text-gray-400 font-normal">(leave blank = Free)</span>
                            </label>
                            <input type="number" name="price" x-model="form.price" min="0" step="0.01"
                                placeholder="0.00"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none bg-gray-50 transition-colors">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                                Duration (mins)
                            </label>
                            <input type="number" name="duration_minutes" x-model="form.duration_minutes" min="1"
                                max="480" placeholder="e.g. 60"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none bg-gray-50 transition-colors">
                        </div>
                    </div>

                    {{-- Sort Order + Status Row --}}
                    <div class="grid grid-cols-2 gap-4 items-center">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                                Sort Order
                            </label>
                            <input type="number" name="sort_order" x-model="form.sort_order" min="0"
                                placeholder="0"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none bg-gray-50 transition-colors">
                        </div>
                        <div class="flex flex-col justify-end h-full pt-4">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <div class="relative">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" x-model="form.is_active"
                                        class="sr-only peer">
                                    <div
                                        class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-brand-500 transition-colors">
                                    </div>
                                    <div
                                        class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5">
                                    </div>
                                </div>
                                <span class="text-sm font-bold text-gray-600">Active</span>
                            </label>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <div class="pt-2">
                        <button type="submit"
                            class="w-full bg-brand-500 hover:bg-brand-600 text-white font-bold py-3 rounded-xl text-sm transition-colors shadow-md active:scale-95">
                            <span x-text="isEdit ? 'Update Service' : 'Save Service'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        function apptServicesCrud() {
            return {
                search: '',
                showModal: false,
                isEdit: false,
                formAction: '{{ route('admin.appointments.services.store') }}',
                form: {
                    name: '',
                    description: '',
                    price: '',
                    duration_minutes: '',
                    sort_order: 0,
                    is_active: true,
                },

                matchSearch(name) {
                    return this.search === '' || name.includes(this.search.toLowerCase());
                },

                openCreate() {
                    this.isEdit = false;
                    this.formAction = '{{ route('admin.appointments.services.store') }}';
                    this.form = {
                        name: '',
                        description: '',
                        price: '',
                        duration_minutes: '',
                        sort_order: 0,
                        is_active: true
                    };
                    this.showModal = true;
                    this.$nextTick(() => lucide?.createIcons());
                },

                openEdit(service) {
                    this.isEdit = true;
                    this.formAction = `/admin/appointment-services/${service.id}`;
                    this.form = {
                        name: service.name ?? '',
                        description: service.description ?? '',
                        price: service.price ?? '',
                        duration_minutes: service.duration_minutes ?? '',
                        sort_order: service.sort_order ?? 0,
                        is_active: Boolean(service.is_active),
                    };
                    this.showModal = true;
                    this.$nextTick(() => lucide?.createIcons());
                },

                closeModal() {
                    this.showModal = false;
                },

                async deleteService(id, name) {
                    const result = await BizAlert.confirm(
                        `Delete "${name}"?`,
                        'This cannot be undone. Active appointments will block deletion.',
                        'Yes, Delete',
                        'warning'
                    );
                    if (!result.isConfirmed) return;

                    try {
                        const res = await fetch(`/admin/appointment-services/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        const data = await res.json();

                        if (res.ok && data.success) {
                            BizAlert.toast(data.message, 'success');
                            setTimeout(() => window.location.reload(), 800);
                        } else {
                            BizAlert.toast(data.message || 'Failed to delete.', 'error');
                        }
                    } catch (e) {
                        BizAlert.toast('Network error. Please try again.', 'error');
                    }
                }
            };
        }
    </script>
@endpush
