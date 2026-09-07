@extends ('layouts.admin')

@section('title', 'Appointments')
@section('header-title', 'Appointments')

@section('content')
    <div x-data="appointmentIndex()" class="mx-auto flex w-full flex-col gap-6">
        {{-- ── HEADER & FILTERS ── --}}
        <div class="flex flex-col gap-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-lg font-bold tracking-tight text-gray-900">All Appointments</h2>
                    <p class="mt-0.5 text-xs text-gray-500">Manage and track customer bookings.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    {{-- Quick Setup Actions --}}
                    @if (has_permission('appointment_services.view'))
                        <a href="{{ route('admin.appointments.services.index') }}"
                            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-bold text-gray-800 shadow-sm transition-all hover:border-[var(--brand-500)] hover:text-[var(--brand-600)] hover:shadow">
                            <i data-lucide="briefcase-medical" class="h-4 w-4 text-[var(--brand-600)]"></i>
                            Services
                        </a>
                    @endif

                    @if (has_permission('appointment_slots.view'))
                        <a href="{{ route('admin.appointments.slots.index') }}"
                            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-bold text-gray-800 shadow-sm transition-all hover:border-[var(--brand-500)] hover:text-[var(--brand-600)] hover:shadow">
                            <i data-lucide="clock-3" class="h-4 w-4 text-[var(--brand-600)]"></i>
                            Slots
                        </a>
                    @endif

                    {{-- Reset Filters --}}
                    @if (array_filter($filters))
                        <a href="{{ route('admin.appointments.index') }}"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-red-200 bg-red-50 px-3.5 py-2 text-xs font-bold text-red-600 transition-colors hover:bg-red-100 hover:text-red-700">
                            <i data-lucide="x-circle" class="h-4 w-4"></i> Clear Filters
                        </a>
                    @endif
                </div>
            </div>

            <form action="{{ route('admin.appointments.index') }}" method="GET"
                class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4" x-ref="filterForm"
                @change="$refs.filterForm.submit()">
                {{-- Search --}}
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <i data-lucide="search" class="h-4 w-4 text-gray-400"></i>
                    </div>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                        placeholder="Search name, phone, or ID..."
                        class="focus:border-brand-500 focus:ring-brand-500/20 w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pr-3 pl-9 text-[13px] text-gray-800 placeholder-gray-400 transition-all focus:ring-2 focus:outline-none" />
                </div>

                {{-- Status --}}
                <div class="w-full shrink-0">
                    <x-custom-select name="status" placeholder="All Statuses" :options="[
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]"
                        selected="{{ $filters['status'] ?? '' }}" />
                </div>

                {{-- Service --}}
                <div class="w-full shrink-0">
                    <x-custom-select name="service_id" placeholder="All Services" :options="collect($services)->pluck('name', 'id')->toArray()"
                        selected="{{ $filters['service_id'] ?? '' }}" />
                </div>

                {{-- Date --}}
                <div class="relative">
                    <input type="date" name="appointment_date" value="{{ $filters['appointment_date'] ?? '' }}"
                        class="focus:border-brand-500 focus:ring-brand-500/20 w-full cursor-pointer rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-[13px] text-gray-700 transition-all focus:ring-2 focus:outline-none"
                        @change="$refs.filterForm.submit()" />
                </div>

                <button type="submit" class="hidden">Submit</button>
            </form>
        </div>

        {{-- ── DATA TABLE ── --}}
        <div class="flex flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
            <div class="no-scrollbar hidden overflow-x-auto sm:block">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr
                            class="border-b border-gray-100 bg-gray-50/80 text-[11px] font-bold tracking-wider text-gray-500 uppercase">
                            <th class="px-5 py-3.5 whitespace-nowrap">ID & Date</th>
                            <th class="px-5 py-3.5 whitespace-nowrap">Customer</th>
                            <th class="px-5 py-3.5 whitespace-nowrap">Service & Slot</th>
                            <th class="px-5 py-3.5 whitespace-nowrap">Status</th>
                            <th class="px-5 py-3.5 text-right whitespace-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($appointments as $appt)
                            <tr class="group transition-colors hover:bg-gray-50/50">
                                {{-- ID & Date --}}
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-gray-100">
                                            <i data-lucide="calendar" class="h-4 w-4 text-gray-500"></i>
                                        </div>
                                        <div>
                                            <p class="text-[13px] leading-tight font-bold text-gray-900">
                                                {{ $appt->appointment_no }}</p>
                                            <p class="mt-0.5 text-[11px] font-medium text-gray-500">
                                                {{ \Carbon\Carbon::parse($appt->appointment_date)->format('d M, Y') }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Customer --}}
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p class="text-[13px] leading-tight font-semibold text-gray-800">
                                        {{ $appt->customer_name }}</p>
                                    <p class="mt-0.5 flex items-center gap-1 text-[11px] font-medium text-gray-500">
                                        <i data-lucide="phone" class="h-3 w-3 text-gray-400"></i>
                                        {{ $appt->customer_phone }}
                                    </p>
                                </td>

                                {{-- Service & Slot --}}
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p class="text-[13px] leading-tight font-semibold text-gray-800">
                                        {{ $appt->service->name ?? '—' }}</p>
                                    <p class="mt-0.5 flex items-center gap-1 text-[11px] font-medium"
                                        style="color: var(--brand-600)">
                                        <i data-lucide="clock" class="h-3 w-3 opacity-70"></i>
                                        {{ $appt->slot->time_range ?? '—' }}
                                    </p>
                                </td>

                                {{-- Status --}}
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @php
                                        $statusCfg = [
                                            'pending' => [
                                                'bg' => 'bg-amber-50',
                                                'text' => 'text-amber-600',
                                                'border' => 'border-amber-200',
                                                'icon' => 'clock',
                                            ],
                                            'confirmed' => [
                                                'bg' => 'bg-brand-50',
                                                'text' => 'text-brand-600',
                                                'border' => 'border-brand-200',
                                                'icon' => 'check-circle',
                                            ],
                                            'completed' => [
                                                'bg' => 'bg-green-50',
                                                'text' => 'text-green-600',
                                                'border' => 'border-green-200',
                                                'icon' => 'check-check',
                                            ],
                                            'cancelled' => [
                                                'bg' => 'bg-red-50',
                                                'text' => 'text-red-600',
                                                'border' => 'border-red-200',
                                                'icon' => 'x-circle',
                                            ],
                                        ];
                                        $cfg = $statusCfg[$appt->status] ?? $statusCfg['pending'];
                                    @endphp
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider border {{ $cfg['bg'] }} {{ $cfg['text'] }} {{ $cfg['border'] }}">
                                        <i data-lucide="{{ $cfg['icon'] }}" class="h-3 w-3"></i>
                                        {{ $appt->status_label }}
                                    </span>
                                </td>

                                {{-- Actions --}}
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        {{-- View --}}
                                        <a href="{{ route('admin.appointments.show', $appt->id) }}"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-transparent text-gray-400 transition-all hover:border-gray-200 hover:bg-gray-100 hover:text-gray-800"
                                            data-tip="View Details">
                                            <i data-lucide="eye" class="h-4 w-4"></i>
                                        </a>

                                        {{-- Contextual Actions based on status --}}
                                        @if ($appt->status === 'pending')
                                            <button type="button"
                                                @click="handleAction('{{ route('admin.appointments.confirm', $appt->id) }}', 'Confirm Appointment', 'Are you sure you want to confirm this booking?', 'var(--brand-500)')"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg"
                                                style="color: var(--brand-600); background: var(--color-brand-50)"
                                                data-tip="Confirm">
                                                <i data-lucide="check" class="h-4 w-4"></i>
                                            </button>
                                        @endif

                                        @if ($appt->status === 'confirmed')
                                            <button type="button"
                                                @click="handleAction('{{ route('admin.appointments.complete', $appt->id) }}', 'Mark Completed', 'Mark this appointment as successfully completed?', '#16a34a')"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-50 text-green-600 transition-all hover:bg-green-100"
                                                data-tip="Complete">
                                                <i data-lucide="check-check" class="h-4 w-4"></i>
                                            </button>
                                        @endif

                                        @if (in_array($appt->status, ['pending', 'confirmed']))
                                            <button type="button"
                                                @click="handleAction('{{ route('admin.appointments.cancel', $appt->id) }}', 'Cancel Appointment', 'This will cancel the booking. Admin notes are required.', '#dc2626', true)"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-500 transition-all hover:bg-red-100"
                                                data-tip="Cancel">
                                                <i data-lucide="x" class="h-4 w-4"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center">
                                    <div
                                        class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl border border-gray-100 bg-gray-50">
                                        <i data-lucide="calendar-x-2" class="h-5 w-5 text-gray-400"></i>
                                    </div>
                                    <p class="text-[14px] font-bold text-gray-800">No appointments found</p>
                                    <p class="mt-1 text-[12px] text-gray-500">Adjust your filters or search term.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 2. MOBILE CARD VIEW (Visible only on Mobile) --}}
            <div class="space-y-3 bg-gray-50/60 p-3 sm:hidden">
                @forelse ($appointments as $appt)
                    <div
                        class="space-y-3 rounded-xl border border-gray-200/80 bg-white p-4 shadow-sm transition-all hover:shadow-md">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-[13px] font-bold text-gray-900">#{{ $appt->appointment_no }}</p>
                                <p class="text-[11px] text-gray-500">
                                    {{ \Carbon\Carbon::parse($appt->appointment_date)->format('d M, Y') }}</p>
                            </div>
                            {{-- Status Badge (re-use your existing logic) --}}
                            <span
                                class="rounded bg-gray-100 px-2 py-1 text-[10px] font-bold">{{ strtoupper($appt->status) }}</span>
                        </div>
                        <div class="flex flex-col gap-1 text-[13px]">
                            <p class="font-semibold text-gray-800">{{ $appt->customer_name }}</p>
                            <p class="text-[12px] text-gray-500">{{ $appt->service->name ?? '—' }}</p>
                        </div>
                        <div class="flex justify-end gap-2 border-t border-gray-50 pt-2">
                            <a href="{{ route('admin.appointments.show', $appt->id) }}"
                                class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs">View</a>
                        </div>
                    </div>
                @empty
                    <div class="py-10 text-center text-sm text-gray-400">No appointments found.</div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if ($appointments->hasPages())
                <div class="border-t border-gray-100 bg-gray-50/30 px-5 py-4">
                    {{ $appointments->links() }}
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            function appointmentIndex() {
                return {
                    init() {
                        // Initialize tooltips if you have a library, or use simple native titles
                        document.querySelectorAll("[data-tip]").forEach((el) => {
                            el.setAttribute("title", el.getAttribute("data-tip"));
                        });
                    },

                    async handleAction(url, title, text, confirmColor, requireNotes = false) {
                        const swalConfig = {
                            title: `<span class="text-lg font-bold">${title}</span>`,
                            html: `<p class="text-sm text-gray-500 mb-4">${text}</p>`,
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonColor: confirmColor,
                            cancelButtonColor: "#f3f4f6",
                            confirmButtonText: "Yes, proceed",
                            cancelButtonText: '<span class="text-gray-600">Cancel</span>',
                            customClass: {
                                popup: "rounded-2xl shadow-xl border border-gray-100",
                                confirmButton: "rounded-xl px-6 py-2.5 font-bold text-[13px] tracking-wide focus:ring-2 focus:ring-offset-2",
                                cancelButton: "rounded-xl px-6 py-2.5 font-bold text-[13px] border border-gray-200 hover:bg-gray-100 focus:ring-2 focus:ring-offset-2 focus:ring-gray-200",
                            },
                            showLoaderOnConfirm: true,
                        };

                        // If cancelling, inject a textarea for admin_notes
                        if (requireNotes) {
                            swalConfig.input = "textarea";
                            swalConfig.inputPlaceholder = "Reason for cancellation (required)...";
                            swalConfig.inputAttributes = {
                                "aria-label": "Reason for cancellation",
                                class: "w-full px-3 py-2 border border-gray-200 rounded-xl text-[13px] text-gray-700 focus:outline-none focus:border-red-400 focus:ring-2 focus:ring-red-100 min-h-[80px] mt-4",
                            };
                            swalConfig.preConfirm = (notes) => {
                                if (!notes || notes.trim() === "") {
                                    Swal.showValidationMessage("Admin notes are required to cancel.");
                                    return false;
                                }
                                return notes;
                            };
                        } else {
                            // Optional notes for confirm/complete
                            swalConfig.input = "textarea";
                            swalConfig.inputPlaceholder = "Optional admin notes...";
                            swalConfig.inputAttributes = {
                                "aria-label": "Optional admin notes",
                                class: "w-full px-3 py-2 border border-gray-200 rounded-xl text-[13px] text-gray-700 focus:outline-none focus:ring-2 min-h-[60px] mt-4",
                            };
                            swalConfig.inputValidator = () => null; // bypass validation since it's optional
                        }

                        const result = await Swal.fire(swalConfig);

                        if (result.isConfirmed) {
                            const notes = result.value || "";

                            try {
                                const response = await fetch(url, {
                                    method: "PATCH",
                                    headers: {
                                        "Content-Type": "application/json",
                                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                        Accept: "application/json",
                                    },
                                    body: JSON.stringify({
                                        admin_notes: notes
                                    }),
                                });

                                const data = await response.json();

                                if (response.ok && data.success) {
                                    Swal.fire({
                                        icon: "success",
                                        title: '<span class="text-lg font-bold">Success!</span>',
                                        text: data.message,
                                        confirmButtonColor: "var(--brand-500)",
                                        customClass: {
                                            popup: "rounded-2xl",
                                            confirmButton: "rounded-xl px-6 py-2.5 font-bold text-[13px]",
                                        },
                                    }).then(() => {
                                        // If using your SPA engine, you could call navigate(location.href)
                                        // but window.location.reload is safest for table data refresh
                                        window.location.reload();
                                    });
                                } else {
                                    throw new Error(data.message || "Something went wrong.");
                                }
                            } catch (error) {
                                Swal.fire({
                                    icon: "error",
                                    title: '<span class="text-lg font-bold text-red-600">Action Failed</span>',
                                    text: error.message,
                                    confirmButtonColor: "#dc2626",
                                    customClass: {
                                        popup: "rounded-2xl",
                                        confirmButton: "rounded-xl px-6 py-2.5 font-bold text-[13px]",
                                    },
                                });
                            }
                        }
                    },
                };
            }
        </script>
    @endpush
@endsection
