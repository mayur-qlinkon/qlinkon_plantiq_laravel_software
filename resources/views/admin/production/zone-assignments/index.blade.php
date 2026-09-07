@extends ('layouts.admin')

@section ('title', 'Zone Assignments — PlantIQ')

@section ('header-title')
    <div class="flex w-full items-center justify-between">
        <div>
            <h1 class="text-lg font-bold tracking-tight text-gray-900">Zone Assignments</h1>
            <p class="mt-0.5 text-[12px] font-medium text-gray-500">Manage employee responsibilities and zone-level operational coverage.</p>
        </div>
    </div>
@endsection

@section ('content')
    <div x-data="zoneAssignmentsPage()" class="mx-auto w-full max-w-7xl space-y-6">
        {{-- ── Quick Overview Cards ──────────────────────────────────────── --}}
        <div
            class="custom-scrollbar flex snap-x snap-mandatory gap-4 overflow-x-auto pb-2 sm:grid sm:snap-none sm:grid-cols-3 sm:overflow-visible sm:pb-0"
        >
            <div
                class="min-w-[240px] shrink-0 snap-center rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:min-w-0 sm:shrink sm:p-5"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                    >
                        <i data-lucide="check-check" class="h-5 w-5"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-[10px] font-black tracking-wider text-gray-400 uppercase sm:text-[11px]">Total Assignments</p>
                        <p class="text-xl font-black text-gray-900 sm:text-2xl">{{ $assignments->total() }}</p>
                    </div>
                </div>
            </div>

            <div
                class="min-w-[240px] shrink-0 snap-center rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:min-w-0 sm:shrink sm:p-5"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"
                    >
                        <i data-lucide="users" class="h-5 w-5"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-[10px] font-black tracking-wider text-gray-400 uppercase sm:text-[11px]">Assigned Employees</p>
                        <p class="text-xl font-black text-gray-900 sm:text-2xl">{{ $assignments->unique('employee_id')->count() }}</p>
                    </div>
                </div>
            </div>

            <div
                class="min-w-[240px] shrink-0 snap-center rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:min-w-0 sm:shrink sm:p-5"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-600"
                    >
                        <i data-lucide="layers" class="h-5 w-5"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-[10px] font-black tracking-wider text-gray-400 uppercase sm:text-[11px]">Active Zones Covered</p>
                        <p class="text-xl font-black text-gray-900 sm:text-2xl">{{ $assignments->unique('zone_id')->count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Action Toolbar ─────────────────────────────────────────────── --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-sm font-bold text-gray-700">Active Assignments List</h2>
            <button
                type="button"
                @click="openCreateModal()"
                class="inline-flex w-full shrink-0 items-center justify-center gap-2 rounded-xl bg-[var(--brand-600)] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:bg-[var(--brand-700)] active:scale-95 sm:w-auto"
            >
                <i data-lucide="plus" class="h-4 w-4"></i>
                Assign Employee
            </button>
        </div>

        {{-- ── Main Table Card ───────────────────────────────────────────── --}}
        <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
            @if ($assignments->isEmpty())
                <div class="flex flex-col items-center justify-center px-6 py-20 text-center">
                    <div
                        class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl border border-gray-100 bg-gray-50 text-gray-300 shadow-sm"
                    >
                        <i data-lucide="user-x" class="h-8 w-8"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-900">No Assignments Found</h3>
                    <p class="mt-1 max-w-sm text-xs font-medium text-gray-400">Assign team members to production zones to establish duty ownership and task tracking.</p>
                    <button
                        type="button"
                        @click="openCreateModal()"
                        class="mt-5 inline-flex items-center gap-2 rounded-xl bg-gray-50 px-4 py-2.5 text-xs font-bold text-gray-700 transition-colors hover:bg-gray-100"
                    >
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        Assign First Employee
                    </button>
                </div>
            @else
                {{-- 🌟 DESKTOP TABLE VIEW --}}
                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full divide-y divide-gray-100 text-left align-middle">
                        <thead class="bg-gray-50/80">
                            <tr>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 text-[11px] font-black tracking-wider text-gray-400 uppercase"
                                >
                                    Employee
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 text-[11px] font-black tracking-wider text-gray-400 uppercase"
                                >
                                    Zone
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 text-[11px] font-black tracking-wider text-gray-400 uppercase"
                                >
                                    Production Site
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 text-[11px] font-black tracking-wider text-gray-400 uppercase"
                                >
                                    Assigned Date
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 text-[11px] font-black tracking-wider text-gray-400 uppercase"
                                >
                                    Notes
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 text-right text-[11px] font-black tracking-wider text-gray-400 uppercase"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 bg-white">
                            @foreach ($assignments as $a)
                                <tr id="row-{{ $a->id }}" class="group transition-colors hover:bg-gray-50/60">
                                    {{-- Employee Name --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 font-bold text-emerald-700"
                                            >
                                                {{ strtoupper(substr($a->employee->full_name ?? 'E', 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-900">{{ $a->employee?->full_name ?? 'Former employee' }}</p>
                                                <p class="text-[11px] font-medium text-gray-500">ID: {{ $a->employee?->employee_code ?? '—' }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Zone Badge --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-blue-100 bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700"
                                        >
                                            <i data-lucide="layers" class="h-3 w-3"></i>
                                            {{ $a->zone->name ?? "" }}
                                        </span>
                                    </td>

                                    {{-- Site Name --}}
                                    <td class="px-6 py-4 text-xs font-semibold whitespace-nowrap text-gray-600">
                                        {{ $a->zone->site->name ?? '—' }}
                                    </td>

                                    {{-- Assigned Date --}}
                                    <td class="px-6 py-4 text-xs font-semibold whitespace-nowrap text-gray-500">
                                        {{ $a->assigned_at?->format('d M, Y') ?? '—' }}
                                    </td>

                                    {{-- Notes preview --}}
                                    <td class="max-w-xs truncate px-6 py-4 text-xs text-gray-500">
                                        {{ $a->notes ?: '—' }}
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1">
                                            <button
                                                type="button"
                                                @click="viewAssignment({{ json_encode([
                                                    'id' => $a->id,
                                                    'employee' => $a->employee->full_name ?? '—',
                                                    'zone' => $a->zone->name ?? '—',
                                                    'site' => $a->zone->site->name ?? '—',
                                                    'assigned_at' => $a->assigned_at?->format('d M, Y') ?? '—',
                                                    'notes' => $a->notes ?: 'No additional notes provided.',
                                                ]) }})"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700"
                                                title="View Details"
                                            >
                                                <i data-lucide="eye" class="h-4 w-4"></i>
                                            </button>

                                            <button
                                                type="button"
                                                @click="unassign({{ $a->id }}, @js($a->employee?->full_name ?? 'this employee'))"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                                title="Remove Assignment"
                                            >
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- 🌟 MOBILE CARDS VIEW (< md) --}}
                <div class="block divide-y divide-gray-100 bg-gray-50/30 md:hidden">
                    @foreach ($assignments as $a)
                        <div id="mobile-row-{{ $a->id }}" class="bg-white p-4">
                            {{-- Top row: Employee & Actions --}}
                            <div class="flex items-start justify-between border-b border-gray-50 pb-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 font-bold text-emerald-700"
                                    >
                                        {{ strtoupper(substr($a->employee->full_name ?? 'E', 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-gray-900">{{ $a->employee?->full_name ?? 'Former employee' }}</p>
                                        <p class="text-[11px] font-medium text-gray-500">ID: {{ $a->employee?->employee_code ?? '—' }}</p>
                                    </div>
                                </div>
                                <div class="ml-3 flex shrink-0 items-center gap-1">
                                    <button
                                        type="button"
                                        @click="viewAssignment({{ json_encode([
                                            'id' => $a->id,
                                            'employee' => $a->employee->full_name ?? '—',
                                            'zone' => $a->zone->name ?? '—',
                                            'site' => $a->zone->site->name ?? '—',
                                            'assigned_at' => $a->assigned_at?->format('d M, Y') ?? '—',
                                            'notes' => $a->notes ?: 'No additional notes provided.',
                                        ]) }})"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition-colors hover:bg-gray-50"
                                    >
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </button>
                                    <button
                                        type="button"
                                        @click="unassign({{ $a->id }}, @js($a->employee?->full_name ?? 'this employee'))"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-red-100 bg-red-50 text-red-500 transition-colors hover:bg-red-100 hover:text-red-600"
                                    >
                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                    </button>
                                </div>
                            </div>

                            {{-- Middle Details Grid --}}
                            <div class="grid grid-cols-2 gap-3 py-3">
                                <div>
                                    <p class="text-[10px] font-black tracking-wider text-gray-400 uppercase">Zone</p>
                                    <span
                                        class="mt-1 inline-flex items-center gap-1.5 rounded-lg border border-blue-100 bg-blue-50 px-2 py-0.5 text-[11px] font-bold text-blue-700"
                                    >
                                        <i data-lucide="layers" class="h-3 w-3"></i>
                                        {{ $a->zone->name ?? "" }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black tracking-wider text-gray-400 uppercase">Site</p>
                                    <p class="mt-1 truncate text-xs font-bold text-gray-700">{{ $a->zone->site->name ?? '—' }}</p>
                                </div>
                            </div>

                            {{-- Bottom Date & Notes --}}
                            <div class="flex items-center justify-between border-t border-gray-50 pt-2">
                                <p class="text-[11px] font-medium text-gray-500">
                                    Assigned:
                                    <span
                                        class="font-bold text-gray-700"
                                        >{{ $a->assigned_at?->format('d M, Y') ?? '—' }}</span
                                    >
                                </p>
                                @if ($a->notes)
                                    <i
                                        data-lucide="message-square-text"
                                        class="h-4 w-4 text-gray-400"
                                        title="Has notes"
                                    ></i>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($assignments->hasPages())
                    <div class="border-t border-gray-100 bg-white px-6 py-4">{{ $assignments->links() }}</div>
                @endif
            @endif
        </div>

        {{-- ════════════════════════════════════════════════════════════
             POPUP MODAL: CREATE ASSIGNMENT
        ════════════════════════════════════════════════════════════ --}}
        <template x-teleport="body">
            <div
                x-show="createModalOpen"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4"
            >
                {{-- Backdrop --}}
                <div
                    x-show="createModalOpen"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm"
                    @click="closeCreateModal()"
                ></div>

                {{-- Dialog Box --}}
                <div
                    x-show="createModalOpen"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-gray-100 bg-white shadow-2xl"
                >
                    <form @submit.prevent="submitCreate()">
                        {{-- Header --}}
                        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-4 sm:px-6">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                                >
                                    <i data-lucide="user-plus" class="h-5 w-5"></i>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-gray-900">Assign Employee to Zone</h3>
                                    <p class="text-[12px] font-medium text-gray-400">Establish zone responsibility for daily operations.</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="closeCreateModal()"
                                class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                            >
                                <i data-lucide="x" class="h-5 w-5"></i>
                            </button>
                        </div>

                        {{-- Form Content --}}
                        <div class="space-y-4 p-4 sm:p-6">
                            {{-- Employee Selector --}}
                            <div>
                                <label class="mb-1.5 block text-xs font-bold tracking-wider text-gray-600 uppercase">
                                    Select Employee <span class="text-red-500">*</span>
                                </label>
                                <x-custom-select
                                    name="employee_id"
                                    placeholder="Select Employee"
                                    :options="$employees->pluck('full_name', 'id')->toArray()"
                                    required
                                />
                            </div>

                            {{-- Zone Selector --}}
                            <div>
                                <label class="mb-1.5 block text-xs font-bold tracking-wider text-gray-600 uppercase">
                                    Target Zone <span class="text-red-500">*</span>
                                </label>
                                <x-custom-select
                                    name="zone_id"
                                    placeholder="Select Zone"
                                    :options="$zones->mapWithKeys(fn($z) => [$z->id => ($z->site->name ?? 'Site') . ' → ' . $z->name])->toArray()"
                                    required
                                />
                            </div>

                            {{-- Notes --}}
                            <div>
                                <label class="mb-1.5 block text-xs font-bold tracking-wider text-gray-600 uppercase">
                                    Assignment Notes
                                    <span class="font-medium text-gray-400 normal-case">(Optional)</span>
                                </label>
                                <textarea
                                    x-model="form.notes"
                                    rows="3"
                                    placeholder="Add any specific guidelines or responsibility details..."
                                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 shadow-sm transition-all outline-none hover:border-gray-300 focus:border-[var(--brand-600)] focus:ring-1 focus:ring-[var(--brand-600)]"
                                ></textarea>
                            </div>

                            {{-- Error Banner --}}
                            <div
                                x-show="errorMessage"
                                x-cloak
                                class="rounded-xl border border-red-100 bg-red-50 p-3 text-xs font-semibold text-red-600"
                                x-text="errorMessage"
                            ></div>
                        </div>

                        {{-- Footer Actions --}}
                        <div
                            class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50/50 px-4 py-4 sm:px-6"
                        >
                            <button
                                type="button"
                                @click="closeCreateModal()"
                                class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-600 shadow-sm transition-colors hover:bg-gray-50 active:scale-95"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="submitting"
                                class="inline-flex items-center gap-2 rounded-xl bg-[var(--brand-600)] px-5 py-2.5 text-xs font-bold text-white shadow-sm transition-all hover:bg-[var(--brand-700)] active:scale-95 disabled:opacity-60"
                            >
                                <i x-show="submitting" data-lucide="loader-2" class="h-3.5 w-3.5 animate-spin"></i>
                                <span x-text="submitting ? 'Assigning...' : 'Confirm Assignment'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        {{-- ════════════════════════════════════════════════════════════
             POPUP MODAL: VIEW DETAILS
        ════════════════════════════════════════════════════════════ --}}
        <template x-teleport="body">
            <div x-show="viewModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4">
                <div
                    x-show="viewModalOpen"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm"
                    @click="viewModalOpen = false"
                ></div>

                <div
                    x-show="viewModalOpen"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl border border-gray-100 bg-white shadow-2xl"
                >
                    <div class="flex items-center justify-between border-b border-gray-100 px-4 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                <i data-lucide="info" class="h-5 w-5"></i>
                            </div>
                            <h3 class="text-base font-bold text-gray-900">Assignment Information</h3>
                        </div>
                        <button
                            type="button"
                            @click="viewModalOpen = false"
                            class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                        >
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>

                    <div class="space-y-4 p-4 text-sm sm:p-6" x-if="activeView">
                        <div class="space-y-3 rounded-2xl border border-gray-100 bg-gray-50/70 p-4">
                            <div>
                                <p class="text-[10px] font-black tracking-wider text-gray-400 uppercase">Assigned Employee</p>
                                <p class="text-base font-bold text-gray-900" x-text="activeView.employee"></p>
                            </div>
                            <div class="grid grid-cols-2 gap-3 border-t border-gray-200/60 pt-3">
                                <div>
                                    <p class="text-[10px] font-black tracking-wider text-gray-400 uppercase">Zone</p>
                                    <p class="font-bold text-blue-600" x-text="activeView.zone"></p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black tracking-wider text-gray-400 uppercase">Site</p>
                                    <p class="font-bold text-gray-700" x-text="activeView.site"></p>
                                </div>
                            </div>
                            <div class="border-t border-gray-200/60 pt-3">
                                <p class="text-[10px] font-black tracking-wider text-gray-400 uppercase">Assigned Date</p>
                                <p class="font-bold text-gray-700" x-text="activeView.assigned_at"></p>
                            </div>
                        </div>

                        <div>
                            <p class="mb-1.5 text-[10px] font-black tracking-wider text-gray-400 uppercase">Notes / Instructions</p>
                            <p
                                class="rounded-xl border border-gray-100 bg-white p-3.5 text-xs leading-relaxed text-gray-600 shadow-sm"
                                x-text="activeView.notes"
                            ></p>
                        </div>
                    </div>

                    <div class="flex justify-end border-t border-gray-100 bg-gray-50/50 px-4 py-4 sm:px-6">
                        <button
                            type="button"
                            @click="viewModalOpen = false"
                            class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-xs font-bold text-gray-600 shadow-sm transition-colors hover:bg-gray-50 active:scale-95"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endsection

@push ('scripts')
    <script>
        function zoneAssignmentsPage() {
            return {
                createModalOpen: false,
                viewModalOpen: false,
                submitting: false,
                errorMessage: null,
                activeView: null,
                form: { employee_id: "", zone_id: "", notes: "" },

                openCreateModal() {
                    this.form = { employee_id: "", zone_id: "", notes: "" };
                    this.errorMessage = null;
                    this.createModalOpen = true;
                    this.refreshIcons();
                },

                closeCreateModal() {
                    if (this.submitting) return;
                    this.createModalOpen = false;
                },

                viewAssignment(data) {
                    this.activeView = data;
                    this.viewModalOpen = true;
                    this.refreshIcons();
                },

                refreshIcons() {
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                },

                async submitCreate() {
                    this.submitting = true;
                    this.errorMessage = null;

                    // Fetch current values from custom selects
                    this.form.employee_id = document.querySelector('select[name="employee_id"]')?.value || "";
                    this.form.zone_id = document.querySelector('select[name="zone_id"]')?.value || "";

                    try {
                        const res = await fetch("{{ route('admin.production.zone-assignments.store') }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                Accept: "application/json",
                            },
                            body: JSON.stringify(this.form),
                        });

                        const json = await res.json();

                        if (res.ok && json.success) {
                            if (window.BizAlert?.toast) {
                                window.BizAlert.toast(json.message || "Employee assigned successfully.", "success");
                            }
                            this.closeCreateModal();
                            setTimeout(() => window.location.reload(), 600);
                        } else {
                            this.errorMessage = json.message || "Failed to assign employee. Please check input.";
                        }
                    } catch (e) {
                        this.errorMessage = "A network error occurred. Please try again.";
                    } finally {
                        this.submitting = false;
                    }
                },

                async unassign(id, employeeName) {
                    let isConfirmed = false;

                    if (window.Swal) {
                        const result = await Swal.fire({
                            title: "Remove Assignment?",
                            text: `Are you sure you want to remove zone assignment for "${employeeName}"?`,
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonText: "Yes, Remove",
                            cancelButtonText: "Cancel",
                            confirmButtonColor: "#dc2626",
                            customClass: {
                                popup: "rounded-2xl border border-gray-100 shadow-2xl bg-white",
                                title: "text-base font-bold text-gray-900",
                                htmlContainer: "text-xs font-medium text-gray-500",
                                confirmButton:
                                    "rounded-xl bg-red-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-red-700 transition-colors mr-2",
                                cancelButton:
                                    "rounded-xl border border-gray-200 bg-white px-4 py-2 text-xs font-bold text-gray-600 shadow-sm hover:bg-gray-50 transition-colors",
                            },
                            buttonsStyling: false,
                        });
                        isConfirmed = result.isConfirmed;
                    } else if (window.BizAlert?.confirm) {
                        const result = await window.BizAlert.confirm(
                            "Remove Assignment",
                            `Are you sure you want to remove the zone assignment for "${employeeName}"?`,
                            "Yes, Remove",
                            "warning",
                        );
                        isConfirmed = result.isConfirmed;
                    } else {
                        isConfirmed = confirm(`Remove zone assignment for ${employeeName}?`);
                    }

                    if (!isConfirmed) return;

                    try {
                        const res = await fetch(`/admin/production/zone-assignments/${id}`, {
                            method: "DELETE",
                            headers: {
                                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                Accept: "application/json",
                            },
                        });

                        const json = await res.json();

                        if (res.ok && json.success) {
                            if (window.BizAlert?.toast) {
                                window.BizAlert.toast(json.message || "Assignment removed successfully.", "success");
                            }
                            document.getElementById(`row-${id}`)?.remove();
                            document.getElementById(`mobile-row-${id}`)?.remove();
                        } else {
                            if (window.BizAlert?.toast) {
                                window.BizAlert.toast(json.message || "Could not remove assignment.", "error");
                            }
                        }
                    } catch (e) {
                        if (window.BizAlert?.toast) {
                            window.BizAlert.toast("Network error occurred.", "error");
                        }
                    }
                },
            };
        }
    </script>
@endpush
