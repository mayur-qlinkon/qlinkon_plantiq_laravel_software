@extends('layouts.admin')

@section('title', 'Appointment Slots')

@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Appointment Slots</h1>
@endsection

@section('content')
<div class="pb-12 space-y-6" x-data="apptSlotsCrud()">

    {{-- ── Flash Toasts ── --}}
    @if(session('success'))
        <script>document.addEventListener('DOMContentLoaded', () => BizAlert.toast("{{ session('success') }}", 'success'));</script>
    @endif
    @if(session('error'))
        <script>document.addEventListener('DOMContentLoaded', () => BizAlert.toast("{{ session('error') }}", 'error'));</script>
    @endif

    {{-- ── Page Header ── --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Appointment Slots</h2>
            <p class="text-sm text-gray-400 mt-0.5">Time slots customers can select when booking.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.appointments.services.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-bold text-gray-600 hover:bg-gray-50 transition-colors shadow-sm">
                <i data-lucide="briefcase" class="w-4 h-4"></i>
                <span class="hidden sm:inline">Manage Services</span>
                <span class="sm:hidden">Services</span>
            </a>
            <button @click="openCreate()"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-sm font-bold transition-colors shadow-md active:scale-95">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span class="hidden sm:inline">Add Slot</span>
                <span class="sm:hidden">Add</span>
            </button>
        </div>
    </div>

    {{-- ── Slot Cards Grid (Mobile-first) ── --}}
    {{-- Desktop: table. Mobile: cards. Same data, different layout --}}

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

        {{-- Toolbar --}}
        <div class="px-5 py-4 border-b border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <span class="text-[15px] font-bold text-gray-800">
                All Slots
                <span class="ml-2 text-xs font-bold text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">
                    {{ $slots->total() }}
                </span>
            </span>
            {{-- Filter active/inactive --}}
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.appointments.slots.index') }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors
                        {{ !request('is_active') ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                    All
                </a>
                <a href="{{ route('admin.appointments.slots.index', ['is_active' => 1]) }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors
                        {{ request('is_active') == '1' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                    Active
                </a>
                <a href="{{ route('admin.appointments.slots.index', ['is_active' => 0]) }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors
                        {{ request('is_active') === '0' ? 'bg-gray-500 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                    Inactive
                </a>
            </div>
        </div>

        {{-- ── DESKTOP TABLE ── --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-3.5 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Slot Name</th>
                        <th class="px-6 py-3.5 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Start Time</th>
                        <th class="px-6 py-3.5 text-[11px] font-bold text-gray-400 uppercase tracking-wider">End Time</th>
                        <th class="px-6 py-3.5 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Duration</th>
                        <th class="px-6 py-3.5 text-[11px] font-bold text-gray-400 uppercase tracking-wider text-center">Status</th>
                        <th class="px-6 py-3.5 text-[11px] font-bold text-gray-400 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($slots as $slot)
                    @php
                        $start = \Carbon\Carbon::parse($slot->start_time);
                        $end   = \Carbon\Carbon::parse($slot->end_time);
                        $diff  = $start->diffInMinutes($end);
                    @endphp
                    <tr class="hover:bg-gray-50/60 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-brand-50 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="clock" class="w-4 h-4 text-brand-600"></i>
                                </div>
                                <span class="font-bold text-gray-800 text-[13.5px]">{{ $slot->slot_name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-mono font-bold text-gray-700 text-sm bg-gray-100 px-2.5 py-1 rounded-lg">
                                {{ substr($slot->start_time, 0, 5) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-mono font-bold text-gray-700 text-sm bg-gray-100 px-2.5 py-1 rounded-lg">
                                {{ substr($slot->end_time, 0, 5) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-xs text-gray-500 font-medium">{{ $diff }} min</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($slot->is_active)
                                <span class="bg-green-50 text-green-700 px-3 py-1 rounded-lg font-bold text-[11px] uppercase tracking-wide">Active</span>
                            @else
                                <span class="bg-gray-100 text-gray-400 px-3 py-1 rounded-lg font-bold text-[11px] uppercase tracking-wide">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="openEdit({{ $slot->toJson() }})"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg border border-brand-500/30 text-brand-600 hover:bg-brand-50 transition-colors"
                                    title="Edit">
                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                </button>
                                <button @click="deleteSlot({{ $slot->id }}, '{{ addslashes($slot->slot_name) }}')"
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
                                    <i data-lucide="clock" class="w-6 h-6 text-gray-400"></i>
                                </div>
                                <p class="text-sm font-bold text-gray-400">No slots yet</p>
                                <button @click="openCreate()" class="text-xs text-brand-600 font-bold hover:underline">Add your first slot →</button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ── MOBILE CARDS ── --}}
        <div class="md:hidden divide-y divide-gray-100">
            @forelse($slots as $slot)
            @php
                $start = \Carbon\Carbon::parse($slot->start_time);
                $end   = \Carbon\Carbon::parse($slot->end_time);
                $diff  = $start->diffInMinutes($end);
            @endphp
            <div class="p-4 hover:bg-gray-50/50 transition-colors">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="clock" class="w-4.5 h-4.5 text-brand-600"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-gray-800 text-sm">{{ $slot->slot_name }}</span>
                                @if($slot->is_active)
                                    <span class="bg-green-50 text-green-700 px-2 py-0.5 rounded-md font-bold text-[10px] uppercase">Active</span>
                                @else
                                    <span class="bg-gray-100 text-gray-400 px-2 py-0.5 rounded-md font-bold text-[10px] uppercase">Inactive</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="font-mono text-xs font-bold text-gray-600 bg-gray-100 px-2 py-0.5 rounded">{{ substr($slot->start_time, 0, 5) }}</span>
                                <i data-lucide="arrow-right" class="w-3 h-3 text-gray-400"></i>
                                <span class="font-mono text-xs font-bold text-gray-600 bg-gray-100 px-2 py-0.5 rounded">{{ substr($slot->end_time, 0, 5) }}</span>
                                <span class="text-[11px] text-gray-400 font-medium">({{ $diff }} min)</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button @click="openEdit({{ $slot->toJson() }})"
                            class="w-8 h-8 flex items-center justify-center rounded-lg border border-brand-500/30 text-brand-600 hover:bg-brand-50 transition-colors">
                            <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                        </button>
                        <button @click="deleteSlot({{ $slot->id }}, '{{ addslashes($slot->slot_name) }}')"
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
                        <i data-lucide="clock" class="w-6 h-6 text-gray-400"></i>
                    </div>
                    <p class="text-sm font-bold text-gray-400">No slots yet</p>
                    <button @click="openCreate()" class="text-xs text-brand-600 font-bold hover:underline">Add your first slot →</button>
                </div>
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($slots->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
            {{ $slots->withQueryString()->links() }}
        </div>
        @endif
    </div>

    {{-- ════════════════════════════════════════════
         MODAL — Create / Edit Slot
    ════════════════════════════════════════════ --}}
    <div x-show="showModal" x-cloak
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 backdrop-blur-sm p-0 sm:p-4"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">

        <div class="relative w-full sm:max-w-md bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl overflow-hidden"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="translate-y-8 sm:translate-y-0 sm:scale-95 opacity-0"
            x-transition:enter-end="translate-y-0 sm:scale-100 opacity-100"
            @click.stop>

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-base font-bold text-gray-800"
                    x-text="isEdit ? 'Edit Slot' : 'Add New Slot'"></h3>
                <button @click="closeModal()"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            {{-- Form --}}
            <form :action="formAction" method="POST" class="p-6 space-y-4">
                @csrf
                <template x-if="isEdit"><input type="hidden" name="_method" value="PUT"></template>

                {{-- Slot Name --}}
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                        Slot Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="slot_name" x-model="form.slot_name" required
                        placeholder="e.g. Morning Slot / 09:00 - 10:00"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none bg-gray-50 transition-colors">
                    <p class="text-[10px] text-gray-400 mt-1 font-medium">Shown to customers in the booking form dropdown.</p>
                </div>

                {{-- Start + End Time --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                            Start Time <span class="text-red-500">*</span>
                        </label>
                        <input type="time" name="start_time" x-model="form.start_time" required
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none bg-gray-50 transition-colors">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                            End Time <span class="text-red-500">*</span>
                        </label>
                        <input type="time" name="end_time" x-model="form.end_time" required
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none bg-gray-50 transition-colors">
                    </div>
                </div>

                {{-- Live Duration Preview --}}
                <div class="bg-brand-50 rounded-xl px-4 py-2.5 flex items-center gap-2"
                    x-show="form.start_time && form.end_time">
                    <i data-lucide="info" class="w-4 h-4 text-brand-500 flex-shrink-0"></i>
                    <span class="text-xs font-bold text-brand-700">
                        Duration: <span x-text="calcDuration()"></span>
                    </span>
                </div>

                {{-- Status Toggle --}}
                <div class="flex items-center gap-3 pt-1">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1"
                                x-model="form.is_active" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-brand-500 transition-colors"></div>
                            <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                        </div>
                        <span class="text-sm font-bold text-gray-600">Active</span>
                    </label>
                </div>

                {{-- Submit --}}
                <div class="pt-2">
                    <button type="submit"
                        class="w-full bg-brand-500 hover:bg-brand-600 text-white font-bold py-3 rounded-xl text-sm transition-colors shadow-md active:scale-95">
                        <span x-text="isEdit ? 'Update Slot' : 'Save Slot'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function apptSlotsCrud() {
    return {
        showModal: false,
        isEdit: false,
        formAction: '{{ route('admin.appointments.slots.store') }}',
        form: {
            slot_name: '',
            start_time: '',
            end_time: '',
            is_active: true,
        },

        openCreate() {
            this.isEdit = false;
            this.formAction = '{{ route('admin.appointments.slots.store') }}';
            this.form = { slot_name: '', start_time: '', end_time: '', is_active: true };
            this.showModal = true;
            this.$nextTick(() => lucide?.createIcons());
        },

        openEdit(slot) {
            this.isEdit = true;
            this.formAction = `/admin/appointment-slots/${slot.id}`;
            this.form = {
                slot_name: slot.slot_name ?? '',
                // DB stores HH:MM:SS — browser time input needs HH:MM
                start_time: slot.start_time ? slot.start_time.substring(0, 5) : '',
                end_time:   slot.end_time   ? slot.end_time.substring(0, 5)   : '',
                is_active:  Boolean(slot.is_active),
            };
            this.showModal = true;
            this.$nextTick(() => lucide?.createIcons());
        },

        closeModal() {
            this.showModal = false;
        },

        // Live duration preview in modal
        calcDuration() {
            if (!this.form.start_time || !this.form.end_time) return '—';
            const [sh, sm] = this.form.start_time.split(':').map(Number);
            const [eh, em] = this.form.end_time.split(':').map(Number);
            const diff = (eh * 60 + em) - (sh * 60 + sm);
            if (diff <= 0) return 'Invalid range';
            const hrs  = Math.floor(diff / 60);
            const mins = diff % 60;
            if (hrs > 0 && mins > 0) return `${hrs} hr ${mins} min`;
            if (hrs > 0) return `${hrs} hr`;
            return `${mins} min`;
        },

        async deleteSlot(id, name) {
            const result = await BizAlert.confirm(
                `Delete "${name}"?`,
                'Active appointments using this slot will block deletion.',
                'Yes, Delete',
                'warning'
            );
            if (!result.isConfirmed) return;

            try {
                const res = await fetch(`/admin/appointment-slots/${id}`, {
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