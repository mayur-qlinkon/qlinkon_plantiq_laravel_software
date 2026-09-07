@extends('layouts.admin')

@section('title', 'Appointment #' . $appointment->appointment_no)
@section('header-title', 'Appointment Details')

@section('content')
    {{-- 
    Main Wrapper: Sky-blue subtle gradient background to enhance the glassmorphism 
    effect of the cards placed over it.
--}}
    <div x-data="appointmentShow()"
        class="relative min-h-full -m-5 p-5 sm:p-6 lg:p-8 bg-gradient-to-br from-sky-50 via-white to-sky-50/50">

        {{-- ── Top Navigation & Header ── --}}
        <div class="max-w-6xl mx-auto mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.appointments.index') }}"
                    class="w-10 h-10 rounded-xl bg-white/70 backdrop-blur-md border border-white/80 shadow-sm flex items-center justify-center text-gray-500 hover:text-sky-600 hover:bg-white transition-all">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900 tracking-tight flex items-center gap-2">
                        #{{ $appointment->appointment_no }}
                    </h1>
                    <p class="text-xs font-medium text-gray-500 mt-0.5">
                        Booked on {{ $appointment->created_at->format('d M, Y h:i A') }}
                    </p>
                </div>
            </div>

            {{-- Dynamic Status Badge --}}
            @php
                $statusCfg = [
                    'pending' => [
                        'bg' => 'bg-amber-100/50',
                        'text' => 'text-amber-700',
                        'border' => 'border-amber-200/50',
                        'icon' => 'clock',
                    ],
                    'confirmed' => [
                        'bg' => 'bg-sky-100/50',
                        'text' => 'text-sky-700',
                        'border' => 'border-sky-200/50',
                        'icon' => 'check-circle',
                    ],
                    'completed' => [
                        'bg' => 'bg-green-100/50',
                        'text' => 'text-green-700',
                        'border' => 'border-green-200/50',
                        'icon' => 'check-check',
                    ],
                    'cancelled' => [
                        'bg' => 'bg-red-100/50',
                        'text' => 'text-red-700',
                        'border' => 'border-red-200/50',
                        'icon' => 'x-circle',
                    ],
                ];
                $cfg = $statusCfg[$appointment->status] ?? $statusCfg['pending'];
            @endphp
            <div
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border backdrop-blur-md {{ $cfg['bg'] }} {{ $cfg['text'] }} {{ $cfg['border'] }} shadow-sm sm:self-start">
                <i data-lucide="{{ $cfg['icon'] }}" class="w-4 h-4"></i>
                <span class="text-sm font-bold uppercase tracking-wider">{{ $appointment->status_label }}</span>
            </div>
        </div>

        {{-- ── Main Content Grid ── --}}
        <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- LEFT COLUMN: Details --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Service & Schedule Card (Glass Theme) --}}
                <div
                    class="bg-white/60 backdrop-blur-xl border border-white/80 rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100/50 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-sky-100 flex items-center justify-center text-sky-600">
                            <i data-lucide="calendar-days" class="w-4 h-4"></i>
                        </div>
                        <h2 class="text-sm font-bold text-gray-800 uppercase tracking-widest">Schedule & Service</h2>
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Service
                                Requested</p>
                            <p class="text-sm font-bold text-gray-900">{{ $appointment->service->name ?? '—' }}</p>
                            @if ($appointment->service?->duration_minutes)
                                <p class="text-xs font-medium text-gray-500 mt-1 flex items-center gap-1.5">
                                    <i data-lucide="timer" class="w-3.5 h-3.5"></i>
                                    {{ $appointment->service->duration_minutes }} Minutes
                                </p>
                            @endif
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Appointment
                                Date & Time</p>
                            <p
                                class="text-sm font-bold text-sky-700 bg-sky-50 inline-block px-3 py-1 rounded-lg border border-sky-100">
                                {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('l, d M Y') }}
                            </p>
                            <p class="text-sm font-semibold text-gray-700 mt-2 flex items-center gap-1.5">
                                <i data-lucide="clock-3" class="w-4 h-4 text-sky-500"></i>
                                {{ $appointment->slot->start_time ?? '—' }} to {{ $appointment->slot->end_time ?? '—' }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Customer Information Card --}}
                <div
                    class="bg-white/60 backdrop-blur-xl border border-white/80 rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100/50 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-sky-100 flex items-center justify-center text-sky-600">
                            <i data-lucide="user" class="w-4 h-4"></i>
                        </div>
                        <h2 class="text-sm font-bold text-gray-800 uppercase tracking-widest">Customer Details</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Full Name
                                </p>
                                <p class="text-sm font-semibold text-gray-900">{{ $appointment->customer_name }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Contact
                                    Phone</p>
                                <a href="tel:{{ $appointment->customer_phone }}"
                                    class="text-sm font-semibold text-sky-600 hover:underline flex items-center gap-1.5">
                                    <i data-lucide="phone" class="w-3.5 h-3.5"></i> {{ $appointment->customer_phone }}
                                </a>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Email
                                    Address</p>
                                <p class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                                    <i data-lucide="mail" class="w-3.5 h-3.5 text-gray-400"></i>
                                    {{ $appointment->customer_email ?? 'Not provided' }}
                                </p>
                            </div>
                            @if ($appointment->address)
                                <div class="sm:col-span-2">
                                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Address
                                    </p>
                                    <p
                                        class="text-sm font-medium text-gray-700 leading-relaxed bg-gray-50/50 p-3 rounded-xl border border-gray-100">
                                        {{ $appointment->address }}</p>
                                </div>
                            @endif
                            @if ($appointment->notes)
                                <div class="sm:col-span-2">
                                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">
                                        Customer Notes</p>
                                    <p
                                        class="text-sm font-medium text-amber-800 leading-relaxed bg-amber-50/50 p-3 rounded-xl border border-amber-100/50">
                                        {{ $appointment->notes }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>

            {{-- RIGHT COLUMN: Actions & Management --}}
            <div class="space-y-6">

                {{-- Action Controls Card --}}
                <div
                    class="bg-white/60 backdrop-blur-xl border border-white/80 rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100/50">
                        <h2 class="text-sm font-bold text-gray-800 uppercase tracking-widest">Management</h2>
                    </div>
                    <div class="p-6 flex flex-col gap-3">

                        @if ($appointment->status === 'pending')
                            <button @click="processAction('confirm')"
                                class="w-full flex items-center justify-center gap-2 px-5 py-3 bg-sky-500 hover:bg-sky-600 text-white rounded-xl text-sm font-bold transition-all shadow-md shadow-sky-500/20 active:scale-[0.98]">
                                <i data-lucide="check-circle" class="w-4 h-4"></i> Confirm Appointment
                            </button>
                        @endif

                        @if ($appointment->status === 'confirmed')
                            <button @click="processAction('complete')"
                                class="w-full flex items-center justify-center gap-2 px-5 py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-sm font-bold transition-all shadow-md shadow-emerald-500/20 active:scale-[0.98]">
                                <i data-lucide="check-check" class="w-4 h-4"></i> Mark as Completed
                            </button>
                        @endif

                        @if (in_array($appointment->status, ['pending', 'confirmed']))
                            <button @click="processAction('cancel')"
                                class="w-full flex items-center justify-center gap-2 px-5 py-3 bg-white border-2 border-red-100 text-red-600 hover:bg-red-50 hover:border-red-200 rounded-xl text-sm font-bold transition-all active:scale-[0.98]">
                                <i data-lucide="x-circle" class="w-4 h-4"></i> Cancel Booking
                            </button>
                        @endif

                        @if (in_array($appointment->status, ['completed', 'cancelled']))
                            <div class="flex items-center gap-3 p-4 bg-gray-50/80 rounded-xl border border-gray-100">
                                <i data-lucide="lock" class="w-5 h-5 text-gray-400"></i>
                                <p class="text-xs font-semibold text-gray-500">This appointment is locked and cannot be
                                    modified further.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Admin Notes Display --}}
                @if ($appointment->admin_notes)
                    <div
                        class="bg-white/60 backdrop-blur-xl border border-white/80 rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-100/50 flex items-center gap-3">
                            <i data-lucide="clipboard-list" class="w-4 h-4 text-gray-400"></i>
                            <h2 class="text-sm font-bold text-gray-800 uppercase tracking-widest">Admin Notes</h2>
                        </div>
                        <div class="p-6">
                            <p class="text-sm font-medium text-gray-700 whitespace-pre-line">
                                {{ $appointment->admin_notes }}</p>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>

    <script>
        // A plain global factory, not Alpine.data() inside alpine:init. Stack
        // content is parsed after Alpine has already booted, so the listener would
        // register too late and x-data="appointmentShow()" would never resolve.
        window.appointmentShow = function() {
            return {
                appointmentId: {{ $appointment->id }},

                async processAction(actionType) {
                    let config = {
                        confirm: {
                            title: 'Confirm Appointment',
                            text: 'Are you sure you want to confirm this booking?',
                            color: '#0ea5e9', // sky-500
                            url: `{{ route('admin.appointments.confirm', $appointment->id) }}`,
                            requireNotes: false
                        },
                        complete: {
                            title: 'Complete Appointment',
                            text: 'Has this service been successfully completed?',
                            color: '#10b981', // emerald-500
                            url: `{{ route('admin.appointments.complete', $appointment->id) }}`,
                            requireNotes: false
                        },
                        cancel: {
                            title: 'Cancel Appointment',
                            text: 'This will cancel the booking. Admin notes are required.',
                            color: '#ef4444', // red-500
                            url: `{{ route('admin.appointments.cancel', $appointment->id) }}`,
                            requireNotes: true
                        }
                    };

                    const act = config[actionType];

                    const swalOptions = {
                        title: `<span class="text-lg font-extrabold tracking-tight">${act.title}</span>`,
                        html: `<p class="text-sm text-gray-500 mb-2">${act.text}</p>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: act.color,
                        cancelButtonColor: '#f3f4f6',
                        confirmButtonText: 'Yes, proceed',
                        cancelButtonText: '<span class="text-gray-600 font-bold">Close</span>',
                        customClass: {
                            popup: 'rounded-[24px] shadow-2xl border border-gray-100 bg-white/90 backdrop-blur-xl',
                            confirmButton: 'rounded-xl px-6 py-2.5 font-bold text-[13px] tracking-wide',
                            cancelButton: 'rounded-xl px-6 py-2.5 font-bold text-[13px] border border-gray-200 hover:bg-gray-100',
                        },
                        showLoaderOnConfirm: true,
                        input: 'textarea',
                        inputPlaceholder: act.requireNotes ? 'Reason required...' : 'Optional admin notes...',
                        inputAttributes: {
                            'class': 'w-full px-4 py-3 border border-gray-200 rounded-xl text-[13px] text-gray-700 bg-gray-50 focus:outline-none focus:bg-white focus:ring-2 focus:border-transparent mt-4 transition-all min-h-[80px]'
                        }
                    };

                    if (act.requireNotes) {
                        swalOptions.preConfirm = (notes) => {
                            if (!notes || notes.trim() === '') {
                                Swal.showValidationMessage('Admin notes are required for this action.');
                                return false;
                            }
                            return notes;
                        };
                    } else {
                        swalOptions.inputValidator = () => null;
                    }

                    const result = await Swal.fire(swalOptions);

                    if (result.isConfirmed) {
                        try {
                            const response = await fetch(act.url, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                        .content,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    admin_notes: result.value || ''
                                })
                            });

                            const data = await response.json();

                            if (response.ok && data.success) {
                                await Swal.fire({
                                    icon: 'success',
                                    title: '<span class="text-lg font-extrabold">Success</span>',
                                    text: data.message,
                                    confirmButtonColor: act.color,
                                    customClass: {
                                        popup: 'rounded-[24px] bg-white/90 backdrop-blur-xl',
                                        confirmButton: 'rounded-xl px-6 py-2.5 font-bold text-[13px]'
                                    }
                                });
                                window.location.reload();
                            } else {
                                throw new Error(data.message || 'Server rejected the request.');
                            }
                        } catch (error) {
                            Swal.fire({
                                icon: 'error',
                                title: '<span class="text-lg font-extrabold text-red-600">Failed</span>',
                                text: error.message,
                                confirmButtonColor: '#ef4444',
                                customClass: {
                                    popup: 'rounded-[24px]',
                                    confirmButton: 'rounded-xl px-6 py-2.5 font-bold text-[13px]'
                                }
                            });
                        }
                    }
                }
            };
        };
    </script>
@endsection
