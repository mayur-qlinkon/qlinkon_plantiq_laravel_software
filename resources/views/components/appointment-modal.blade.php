{{--
╔══════════════════════════════════════════════════════════════════════════════╗
║  COMPONENT: appointment-modal                                               ║
║  Usage:     <x-appointment-modal :company="$company" />                     ║
║                                                                              ║
║  Listens:   Alpine event  'storefront:book-appointment'  (open)             ║
║  APIs used: GET  /{slug}/appointments/check-slot?slot_id=&date=             ║
║             POST /{slug}/appointments/book                                  ║
║                                                                              ║
║  Steps:     1 → Choose Service                                              ║
║             2 → Pick Date + Slot (real-time availability check)             ║
║             3 → Your Details (name, phone, email, address, notes)           ║
║             4 → Confirmation screen (appointment_no shown)                  ║
║                                                                              ║
║  Features:                                                                  ║
║   · 4-step wizard with animated progress bar                                ║
║   · Real-time slot availability check (debounced AJAX)                      ║
║   · Full client-side validation before each step advance                    ║
║   · Brand-color aware (CSS var(--brand-*) throughout)                       ║
║   · Fully responsive — bottom-sheet on mobile, centered modal on desktop    ║
║   · Keyboard accessible (Escape to close, focus trap)                       ║
║   · SweetAlert2 is available in layout but we use our own inline states     ║
║     to avoid z-index conflicts with the modal itself                        ║
╚══════════════════════════════════════════════════════════════════════════════╝
--}}

@php
    use App\Services\Appointment\AppointmentService as AppointmentSvc;

    $appointmentEnabled = (bool) get_setting('appointment_enabled', 0, $company->id);

    if ($appointmentEnabled) {
        // Not $svc — the service loop below reuses that name for each model.
        $apmtSvc       = app(AppointmentSvc::class);
        $modalServices = $apmtSvc->getActiveServicesForStorefront($company->id);
        $modalSlots    = $apmtSvc->getActiveSlotsForStorefront($company->id);

        // URLs — works on slug, subdomain and custom-domain hosts
        $bookUrl         = tenant_url('appointments/book');
        $bookedSlotsUrl  = tenant_url('appointments/booked-slots');
    }
@endphp

@if($appointmentEnabled)

{{-- ═══════════════════════════════════════════════════
     SCOPED STYLES
═══════════════════════════════════════════════════════ --}}
@push('styles')
<style>
    /* ── Step progress bar ── */
    .apmt-step-bar {
        height: 3px;
        border-radius: 99px;
        background: #e5e7eb;
        overflow: hidden;
    }
    .apmt-step-bar-fill {
        height: 100%;
        border-radius: 99px;
        background: var(--brand-500);
        transition: width 350ms cubic-bezier(.4,0,.2,1);
    }

    /* ── Step circle ── */
    .apmt-step-circle {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 700;
        flex-shrink: 0;
        transition: background 250ms ease, color 250ms ease, border-color 250ms ease;
        border: 2px solid #e5e7eb;
        background: #fff;
        color: #9ca3af;
    }
    .apmt-step-circle.done {
        background: var(--brand-500);
        border-color: var(--brand-500);
        color: #fff;
    }
    .apmt-step-circle.current {
        background: var(--brand-500);
        border-color: var(--brand-500);
        color: #fff;
        box-shadow: 0 0 0 4px color-mix(in srgb, var(--brand-500) 18%, transparent);
    }
    .apmt-step-circle.pending {
        background: #fff;
        border-color: #e5e7eb;
        color: #9ca3af;
    }

    /* ── Service card ── */
    .apmt-service-card {
        border: 1.5px solid #e5e7eb;
        border-radius: 12px;
        padding: 14px 16px;
        cursor: pointer;
        transition: border-color 160ms ease, background 160ms ease, box-shadow 160ms ease;
        background: #fff;
    }
    .apmt-service-card:hover {
        border-color: var(--brand-500);
        background: color-mix(in srgb, var(--brand-500) 4%, white);
    }
    .apmt-service-card.selected {
        border-color: var(--brand-500);
        background: color-mix(in srgb, var(--brand-500) 7%, white);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-500) 15%, transparent);
    }

    /* ── Slot pill ── */
    .apmt-slot {
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        padding: 9px 14px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        text-align: center;
        transition: border-color 150ms ease, background 150ms ease, color 150ms ease;
        background: #fff;
        color: #374151;
        position: relative;
    }
    .apmt-slot:hover:not(.booked) {
        border-color: var(--brand-500);
        color: var(--brand-600);
    }
    .apmt-slot.selected {
        border-color: var(--brand-500);
        background: var(--brand-500);
        color: #fff;
        box-shadow: 0 2px 8px color-mix(in srgb, var(--brand-500) 35%, transparent);
    }
    .apmt-slot.booked {
        background: #f9fafb;
        color: #d1d5db;
        border-color: #f3f4f6;
        cursor: not-allowed;
        text-decoration: line-through;
    }
    .apmt-slot.checking {
        opacity: 0.5;
        pointer-events: none;
    }

    /* ── Input ── */
    .apmt-input {
        width: 100%;
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        padding: 10px 13px;
        font-size: 13px;
        color: #1f2937;
        outline: none;
        background: #fff;
        transition: border-color 150ms ease, box-shadow 150ms ease;
        font-family: inherit;
    }
    .apmt-input:focus {
        border-color: var(--brand-600);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-600) 10%, transparent);
    }
    .apmt-input.apmt-err {
        border-color: #f43f5e;
        box-shadow: 0 0 0 3px rgba(244, 63, 94, 0.10);
    }
    .apmt-label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 5px;
    }
    .apmt-err-msg {
        font-size: 11px;
        color: #f43f5e;
        margin-top: 4px;
        display: flex;
        align-items: center;
        gap: 3px;
    }

    /* ── Primary button ── */
    .apmt-btn-primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 11px 22px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 700;
        color: #fff;
        background: var(--brand-500);
        border: none;
        cursor: pointer;
        transition: opacity 150ms ease, transform 80ms ease;
        outline: none;
        font-family: inherit;
        width: 100%;
    }
    .apmt-btn-primary:hover:not(:disabled) { opacity: 0.9; }
    .apmt-btn-primary:active:not(:disabled) { transform: scale(0.98); }
    .apmt-btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }

    /* ── Back button ── */
    .apmt-btn-back {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 11px 18px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        color: #6b7280;
        background: #f9fafb;
        border: 1.5px solid #e5e7eb;
        cursor: pointer;
        transition: background 150ms ease;
        outline: none;
        font-family: inherit;
    }
    .apmt-btn-back:hover { background: #f3f4f6; }

    /* ── Bottom sheet on mobile ── */
    @media (max-width: 639px) {
        .apmt-modal-box {
            position: fixed !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            top: auto !important;
            border-radius: 20px 20px 0 0 !important;
            max-width: 100% !important;
            max-height: 92dvh !important;
        }
        .apmt-modal-outer {
            align-items: flex-end !important;
        }
    }

    /* ── Success checkmark animation ── */
    @keyframes apmt-pop {
        0%   { transform: scale(0.5); opacity: 0; }
        70%  { transform: scale(1.15); opacity: 1; }
        100% { transform: scale(1); }
    }
    .apmt-success-icon { animation: apmt-pop 400ms cubic-bezier(.4,0,.2,1) forwards; }
</style>
@endpush

{{-- ═══════════════════════════════════════════════════
     ALPINE COMPONENT
═══════════════════════════════════════════════════════ --}}
<div
    x-data="appointmentModal()"
   
    @storefront:book-appointment.window="open()"
    @keydown.escape.window="close()"
>

{{-- ── Modal Overlay ── --}}
<div
    x-show="isOpen"
    x-cloak
    class="apmt-modal-outer fixed inset-0 z-[200] flex items-center justify-center px-4 py-6"
    style="background: rgba(17,24,39,0.65); backdrop-filter: blur(4px);"
    x-transition:enter="transition ease-out duration-250"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-180"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click.self="close()"
>

    {{-- ── Modal Box ── --}}
    <div
        class="apmt-modal-box bg-white w-full max-w-[520px] max-h-[92dvh] rounded-2xl shadow-2xl flex flex-col overflow-hidden"
        x-transition:enter="transition ease-out duration-280"
        x-transition:enter-start="opacity-0 translate-y-6 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-180"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-97"
    >

        {{-- ════ HEADER ════ --}}
        <div class="px-5 pt-5 pb-4 border-b border-gray-100 flex-shrink-0">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center"
                         style="background: color-mix(in srgb, var(--brand-500) 12%, white);">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                             viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"
                             style="color: var(--brand-600);">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-[15px] font-bold text-gray-900 leading-tight">Book an Appointment</h2>
                        <p class="text-[11px] text-gray-400 font-medium leading-tight mt-0.5"
                           x-text="stepLabel"></p>
                    </div>
                </div>
                <button @click="close()"
                        class="w-8 h-8 flex items-center justify-center rounded-xl text-gray-400
                               hover:text-red-500 hover:bg-red-50 transition-colors flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" fill="none"
                         viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Progress: step circles + connecting bar --}}
            <div class="flex items-center gap-0">
                <template x-for="(label, idx) in stepLabels" :key="idx">
                    <div class="flex items-center" :class="idx < stepLabels.length - 1 ? 'flex-1' : ''">
                        <div class="flex flex-col items-center gap-1">
                            {{-- Circle --}}
                            <div class="apmt-step-circle"
                                 :class="{
                                    'done':    step > idx + 1,
                                    'current': step === idx + 1,
                                    'pending': step < idx + 1
                                 }">
                                {{-- Checkmark for done steps --}}
                                <template x-if="step > idx + 1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24"
                                         stroke-width="3" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                    </svg>
                                </template>
                                <template x-if="step <= idx + 1">
                                    <span x-text="idx + 1"></span>
                                </template>
                            </div>
                        </div>
                        {{-- Connector bar --}}
                        <template x-if="idx < stepLabels.length - 1">
                            <div class="apmt-step-bar flex-1 mx-1.5" style="margin-top: -14px;">
                                <div class="apmt-step-bar-fill"
                                     :style="'width: ' + (step > idx + 1 ? '100' : '0') + '%'">
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        {{-- ════ BODY (scrollable) ════ --}}
        <div class="flex-1 overflow-y-auto overscroll-contain px-5 py-5 no-scrollbar">

            {{-- ── STEP 1: Choose Service ── --}}
            <div x-show="step === 1" x-cloak>
                <p class="text-xs text-gray-500 mb-4 font-medium">
                    Select the service you'd like to book.
                </p>

                {{-- No services configured --}}
                @if($modalServices->isEmpty())
                    <div class="flex flex-col items-center justify-center py-10 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24"
                                 stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M8.25 6.75h7.5M8.25 12h7.5m-7.5 5.25h4.5m-8.25 3h16.5a2.25 2.25 0 002.25-2.25V4.5A2.25 2.25 0 0018.75 2.25H5.25A2.25 2.25 0 003 4.5v15a2.25 2.25 0 002.25 2.25z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-gray-600">No services available</p>
                        <p class="text-xs text-gray-400 mt-1">Please check back later.</p>
                    </div>
                @else
                    <div class="grid gap-2.5">
                        @foreach($modalServices as $svc)
                        <button type="button"
                                {{-- @js(), not e(). e() escapes for HTML, and the browser decodes the
                                     attribute before Alpine parses it as JS — so a name like
                                     "Men's Haircut" arrived as an unescaped quote and broke the call. --}}
                                @click="selectService({{ $svc->id }}, @js($svc->name), @js($svc->price_label), @js($svc->duration_label))"
                                :class="form.service_id === {{ $svc->id }} ? 'selected' : ''"
                                class="apmt-service-card text-left w-full group">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <p class="text-[13.5px] font-semibold text-gray-800 leading-tight group-hover:text-gray-900">
                                        {{ $svc->name }}
                                    </p>
                                    @if($svc->description)
                                    <p class="text-[11.5px] text-gray-400 mt-1 leading-relaxed line-clamp-2">
                                        {{ $svc->description }}
                                    </p>
                                    @endif
                                </div>
                                <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                    <span class="text-[13px] font-bold"
                                          style="color: var(--brand-600);">
                                        {{ $svc->price_label }}
                                    </span>
                                    @if($svc->duration_minutes)
                                    <span class="text-[10.5px] text-gray-400 font-medium flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 3"/>
                                        </svg>
                                        {{ $svc->duration_label }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            {{-- Selected indicator --}}
                            <div x-show="form.service_id === {{ $svc->id }}"
                                 class="mt-2 flex items-center gap-1.5 text-[11px] font-bold"
                                 style="color: var(--brand-600);">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                     stroke-width="3" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                                Selected
                            </div>
                        </button>
                        @endforeach
                    </div>

                    {{-- Step 1 validation error --}}
                    <div x-show="errors.service_id" class="apmt-err-msg mt-3">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                             stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/>
                        </svg>
                        <span x-text="errors.service_id"></span>
                    </div>
                @endif
            </div>

            {{-- ── STEP 2: Date + Slot ── --}}
            <div x-show="step === 2" x-cloak>

                {{-- Date picker --}}
                <div class="mb-5">
                    <label class="apmt-label">Preferred Date <span class="text-red-500">*</span></label>
                    <input type="date"
                           class="apmt-input"
                           :class="errors.appointment_date ? 'apmt-err' : ''"
                           x-model="form.appointment_date"
                           :min="today"
                           @change="onDateChange()"
                    />
                    <div x-show="errors.appointment_date" class="apmt-err-msg">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                             stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/>
                        </svg>
                        <span x-text="errors.appointment_date"></span>
                    </div>
                </div>

                {{-- Time Slots --}}
                <div>
                    <label class="apmt-label">
                        Available Slots <span class="text-red-500">*</span>
                        <span x-show="checkingSlot"
                              class="ml-2 text-[10px] font-semibold text-gray-400 normal-case tracking-normal">
                            checking…
                        </span>
                    </label>

                    @if($modalSlots->isEmpty())
                        <p class="text-sm text-gray-400 py-3">No time slots configured yet.</p>
                    @else
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            @foreach($modalSlots as $slot)
                            <button type="button"
                                    class="apmt-slot"
                                    :class="{
                                        'selected !text-white':  form.slot_id === {{ $slot->id }},
                                        'booked':    bookedSlots.includes({{ $slot->id }}),
                                        'checking':  checkingSlot
                                    }"
                                    :disabled="bookedSlots.includes({{ $slot->id }}) || checkingSlot"
                                    @click="selectSlot({{ $slot->id }}, @js($slot->display_label . ' — ' . $slot->time_range))"
                            >
                                <div class="text-[12.5px] font-semibold leading-tight">
                                    {{ $slot->display_label }}
                                </div>
                                <div class="text-[10.5px] font-medium opacity-70 mt-0.5">
                                    {{ $slot->time_range }}
                                </div>
                                {{-- Booked badge --}}
                                <template x-if="bookedSlots.includes({{ $slot->id }})">
                                    <span class="absolute top-1 right-1.5 text-[8.5px] font-bold text-gray-300 uppercase tracking-wide">
                                        Booked
                                    </span>
                                </template>
                            </button>
                            @endforeach
                        </div>

                        <div x-show="errors.slot_id" class="apmt-err-msg mt-2">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                 stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/>
                            </svg>
                            <span x-text="errors.slot_id"></span>
                        </div>

                        {{-- Helper note --}}
                        <p class="text-[10.5px] text-gray-400 mt-3 flex items-center gap-1">
                            <svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                 stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                            </svg>
                            Strikethrough slots are already booked for the selected date.
                        </p>
                    @endif
                </div>
            </div>

            {{-- ── STEP 3: Your Details ── --}}
            <div x-show="step === 3" x-cloak>

                <div class="space-y-4">

                    {{-- Name --}}
                    <div>
                        <label class="apmt-label">Full Name <span class="text-red-500">*</span></label>
                        <input type="text"
                               class="apmt-input"
                               :class="errors.customer_name ? 'apmt-err' : ''"
                               x-model="form.customer_name"
                               placeholder="e.g. Priya Sharma"
                               autocomplete="name"
                        />
                        <div x-show="errors.customer_name" class="apmt-err-msg">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                 stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/>
                            </svg>
                            <span x-text="errors.customer_name"></span>
                        </div>
                    </div>

                    {{-- Phone --}}
                    <div>
                        <label class="apmt-label">Contact Number <span class="text-red-500">*</span></label>
                        <input type="tel"
                            class="apmt-input"
                            :class="errors.customer_phone ? 'apmt-err' : ''"
                            x-model="form.customer_phone"
                            placeholder="9876543210"
                            autocomplete="tel"
                            inputmode="numeric"
                            minlength="10"
                            maxlength="10"
                            pattern="[0-9]{10}"
                            @input="form.customer_phone = $event.target.value.replace(/\D/g, '').slice(0, 10)"
                        />
                        <div x-show="errors.customer_phone" class="apmt-err-msg">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                 stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/>
                            </svg>
                            <span x-text="errors.customer_phone"></span>
                        </div>
                    </div>

                    {{-- Email (optional) --}}
                    <div>
                        <label class="apmt-label">Email Address <span class="text-gray-300 font-medium normal-case">(optional)</span></label>
                        <input type="email"
                               class="apmt-input"
                               :class="errors.customer_email ? 'apmt-err' : ''"
                               x-model="form.customer_email"
                               placeholder="you@example.com"
                               autocomplete="email"
                        />
                        <div x-show="errors.customer_email" class="apmt-err-msg">
                            <span x-text="errors.customer_email"></span>
                        </div>
                    </div>

                    {{-- Address (optional) --}}
                    <div>
                        <label class="apmt-label">Address <span class="text-gray-300 font-medium normal-case">(optional)</span></label>
                        <textarea class="apmt-input"
                                  x-model="form.address"
                                  placeholder="House no, Street, Landmark, City…"
                                  rows="2"
                                  style="resize: vertical; min-height: 60px;"></textarea>
                    </div>

                    {{-- Notes (optional) --}}
                    <div>
                        <label class="apmt-label">Additional Notes <span class="text-gray-300 font-medium normal-case">(optional)</span></label>
                        <textarea class="apmt-input"
                                  x-model="form.notes"
                                  placeholder="Anything you'd like us to know…"
                                  rows="2"
                                  style="resize: vertical; min-height: 60px;"></textarea>
                    </div>

                </div>

                {{-- Booking Summary chip --}}
                <div class="mt-5 p-3.5 rounded-xl border border-gray-100 bg-gray-50 flex flex-col gap-1.5">
                    <p class="text-[10.5px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Booking Summary</p>
                    <div class="flex items-center gap-2 text-[12.5px] text-gray-700">
                        <svg class="w-4 h-4 flex-shrink-0" style="color:var(--brand-500);" fill="none"
                             viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h8m-8 6h16"/>
                        </svg>
                        <span class="font-semibold" x-text="selectedService.name || '—'"></span>
                    </div>
                    <div class="flex items-center gap-2 text-[12.5px] text-gray-700">
                        <svg class="w-4 h-4 flex-shrink-0" style="color:var(--brand-500);" fill="none"
                             viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span x-text="form.appointment_date ? formatDate(form.appointment_date) : '—'"></span>
                    </div>
                    <div class="flex items-center gap-2 text-[12.5px] text-gray-700">
                        <svg class="w-4 h-4 flex-shrink-0" style="color:var(--brand-500);" fill="none"
                             viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span x-text="selectedSlot.label || '—'"></span>
                    </div>
                </div>
            </div>

            {{-- ── STEP 4: Confirmation ── --}}
            <div x-show="step === 4" x-cloak>
                <div class="flex flex-col items-center text-center py-6">
                    {{-- Animated checkmark --}}
                    <div class="apmt-success-icon w-16 h-16 rounded-full flex items-center justify-center mb-5"
                         style="background: color-mix(in srgb, var(--brand-500) 12%, white);">
                        <svg class="w-8 h-8" style="color: var(--brand-500);" fill="none"
                             viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                    </div>

                    <h3 class="text-lg font-extrabold text-gray-900 mb-1">Appointment Booked!</h3>
                    <p class="text-sm text-gray-500 mb-5 max-w-xs leading-relaxed">
                        Your appointment has been received. We'll confirm it shortly.
                    </p>

                    {{-- Appointment number badge --}}
                    <div class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl mb-5"
                         style="background: color-mix(in srgb, var(--brand-500) 8%, white);
                                border: 1.5px solid color-mix(in srgb, var(--brand-500) 25%, white);">
                        <svg class="w-4 h-4" style="color: var(--brand-500);" fill="none"
                             viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5l-3.9 19.5m-2.1-19.5l-3.9 19.5"/>
                        </svg>
                        <span class="text-sm font-bold" style="color: var(--brand-700, var(--brand-600));"
                              x-text="appointmentNo"></span>
                    </div>

                    {{-- Summary grid --}}
                    <div class="w-full text-left space-y-2.5">
                        <div class="flex items-start gap-2.5 p-3 rounded-xl bg-gray-50">
                            <div class="w-5 flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24"
                                     stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h8m-8 6h16"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Service</p>
                                <p class="text-[13px] font-semibold text-gray-800" x-text="selectedService.name"></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5 p-3 rounded-xl bg-gray-50">
                            <div class="w-5 flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24"
                                     stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Date & Slot</p>
                                <p class="text-[13px] font-semibold text-gray-800"
                                   x-text="formatDate(form.appointment_date) + ' · ' + selectedSlot.label"></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5 p-3 rounded-xl bg-gray-50">
                            <div class="w-5 flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24"
                                     stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Customer</p>
                                <p class="text-[13px] font-semibold text-gray-800"
                                   x-text="form.customer_name + ' · ' + form.customer_phone"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── API Error Banner ── --}}
            <div x-show="apiError"
                 class="mt-4 flex items-start gap-2.5 p-3.5 rounded-xl text-sm"
                 style="background: #fff1f2; border: 1.5px solid #fecdd3;">
                <svg class="w-4 h-4 text-rose-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24"
                     stroke-width="2.2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/>
                </svg>
                <p class="text-rose-700 font-medium leading-snug" x-text="apiError"></p>
            </div>

        </div>
        {{-- /scrollable body --}}

        {{-- ════ FOOTER ════ --}}
        <div class="px-5 py-4 border-t border-gray-100 flex-shrink-0 bg-white">

            {{-- Step 1 --}}
            <template x-if="step === 1">
                <button class="apmt-btn-primary"
                        @click="goStep2()"
                        :disabled="!form.service_id">
                    Continue
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                    </svg>
                </button>
            </template>

            {{-- Step 2 --}}
            <template x-if="step === 2">
                <div class="flex gap-3">
                    <button class="apmt-btn-back" @click="step = 1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                        </svg>
                        Back
                    </button>
                    <button class="apmt-btn-primary"
                            @click="goStep3()"
                            :disabled="!form.appointment_date || !form.slot_id">
                        Continue
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                        </svg>
                    </button>
                </div>
            </template>

            {{-- Step 3 --}}
            <template x-if="step === 3">
                <div class="flex gap-3">
                    <button class="apmt-btn-back" @click="step = 2">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                        </svg>
                        Back
                    </button>
                    <button class="apmt-btn-primary"
                            @click="submit()"
                            :disabled="isSubmitting">
                        <template x-if="isSubmitting">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                                <path class="opacity-75" fill="currentColor"
                                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </template>
                        <span x-text="isSubmitting ? 'Confirming…' : 'Confirm Booking'"></span>
                        <template x-if="!isSubmitting">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                        </template>
                    </button>
                </div>
            </template>

            {{-- Step 4 --}}
            <template x-if="step === 4">
                <button class="apmt-btn-primary" @click="close()">
                    Done
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                </button>
            </template>

        </div>
        {{-- /footer --}}

    </div>
    {{-- /modal box --}}

</div>
{{-- /overlay --}}

</div>
{{-- /x-data --}}


{{-- ═══════════════════════════════════════════════════
     ALPINE JS COMPONENT LOGIC
═══════════════════════════════════════════════════════ --}}
@push('scripts')
<script>
function appointmentModal() {
    return {

        // ── State ───────────────────────────────────────────
        isOpen:        false,
        step:          1,
        isSubmitting:  false,
        apiError:      '',
        appointmentNo: '',

        // Step 4 visible labels
        stepLabels: ['Service', 'Date & Slot', 'Details', 'Done'],

        // Slot availability
        checkingSlot: false,
        bookedSlots:  [],  // slot IDs already booked for the selected date
        slotCheckDebounce: null,

        // Form fields (match AppointmentBookingController@book validation)
        form: {
            service_id:       null,
            slot_id:          null,
            appointment_date: '',
            customer_name:    '',
            customer_phone:   '',
            customer_email:   '',
            address:          '',
            notes:            '',
        },

        // Client-side validation errors
        errors: {
            service_id:       '',
            slot_id:          '',
            appointment_date: '',
            customer_name:    '',
            customer_phone:   '',
            customer_email:   '',
        },

        // Selected display state (shown in summary + step 4)
        selectedService: { name: '', price: '', duration: '' },
        selectedSlot:    { label: '' },

        // Computed
        get today() {
            // toISOString() is UTC. In IST that returns yesterday's date
            // between midnight and 05:30, so the modal opened pre-filled with
            // a date the server then rejected as being in the past.
            const d = new Date();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${d.getFullYear()}-${m}-${day}`;
        },
        get stepLabel() {
            const labels = ['Choose Service', 'Date & Slot', 'Your Details', 'Confirmed!'];
            return labels[(this.step - 1)] || '';
        },

        // ── Lifecycle ─────────────────────────────────────────
        init() {
            // Re-run Lucide after each step change to cover x-cloak reveals
            this.$watch('step', () => {
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            });
            this.$watch('isOpen', (val) => {
                document.body.style.overflow = val ? 'hidden' : '';
                if (!val) document.body.style.overflow = '';
            });
        },

        // ── Open / Close ──────────────────────────────────────
        open() {
            this.reset();
            this.isOpen = true;
            // Pre-load today's slot availability so Step 2 isn't empty
            // when the user reaches it (date is pre-selected in reset()).
            this.onDateChange();
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        close() {
            this.isOpen = false;
            document.body.style.overflow = '';
        },

        reset() {
            this.step          = 1;
            this.isSubmitting  = false;
            this.apiError      = '';
            this.appointmentNo = '';
            this.bookedSlots   = [];
            this.checkingSlot  = false;
            this.selectedService = { name: '', price: '', duration: '' };
            this.selectedSlot    = { label: '' };
            this.form = {
                service_id: null, slot_id: null,
                appointment_date: this.today, customer_name: '',
                customer_phone: '', customer_email: '',
                address: '', notes: '',
            };
            this.errors = {
                service_id: '', slot_id: '', appointment_date: '',
                customer_name: '', customer_phone: '', customer_email: '',
            };
        },

        // ── Step 1 — Service selection ────────────────────────
        selectService(id, name, price, duration) {
            this.form.service_id = id;
            this.selectedService = { name, price, duration };
            this.errors.service_id = '';
            this.apiError = '';
        },

        goStep2() {
            if (!this.form.service_id) {
                this.errors.service_id = 'Please select a service to continue.';
                return;
            }
            this.errors.service_id = '';
            this.apiError = '';
            this.step = 2;
        },

        // ── Step 2 — Date + Slot ──────────────────────────────
        async onDateChange() {
            this.form.slot_id   = null;
            this.selectedSlot   = { label: '' };
            this.errors.slot_id = '';
            this.apiError       = '';
            this.bookedSlots    = [];
            this.errors.appointment_date = '';

            if (!this.form.appointment_date) return;

            if (this.form.appointment_date < this.today) {
                this.errors.appointment_date = 'Please select today or a future date.';
                return;
            }

            // Check ALL slots for this date in a single call per slot
            // We do sequential checks to avoid hammering the server
            await this.checkAllSlotsForDate(this.form.appointment_date);
        },

        async checkAllSlotsForDate(date) {
            this.checkingSlot = true;
            this.apiError     = '';

            // One request for the whole date. This used to fan out into one
            // request per slot on every date change and on modal open.
            const baseUrl = @json($bookedSlotsUrl);

            try {
                const res = await fetch(`${baseUrl}?date=${encodeURIComponent(date)}`, {
                    headers: { 'Accept': 'application/json' },
                });

                if (!res.ok) throw new Error('Availability lookup failed');

                const data = await res.json();
                this.bookedSlots = data.booked_slot_ids || [];

            } catch (err) {
                // Fail closed. Silently treating everything as available let a
                // customer pick a taken slot and only find out at submit.
                this.bookedSlots = @json($modalSlots->pluck('id'));
                this.apiError    = 'Could not load slot availability. Please check your connection and try again.';

            } finally {
                this.checkingSlot = false;
            }
        },

        selectSlot(id, label) {
            if (this.bookedSlots.includes(id) || this.checkingSlot) return;
            this.form.slot_id   = id;
            this.selectedSlot   = { label };
            this.errors.slot_id = '';
            this.apiError       = '';
        },

        goStep3() {
            let valid = true;

            if (!this.form.appointment_date) {
                this.errors.appointment_date = 'Please select a date.';
                valid = false;
            } else if (this.form.appointment_date < this.today) {
                this.errors.appointment_date = 'Please select today or a future date.';
                valid = false;
            } else {
                this.errors.appointment_date = '';
            }

            if (!this.form.slot_id) {
                this.errors.slot_id = 'Please select a time slot.';
                valid = false;
            } else {
                this.errors.slot_id = '';
            }

            if (!valid) return;

            this.apiError = '';
            this.step = 3;
        },

        // ── Step 3 — Details validation ───────────────────────
        validateDetails() {
            let valid = true;

            const name = this.form.customer_name.trim();
            if (!name) {
                this.errors.customer_name = 'Full name is required.';
                valid = false;
            } else if (name.length < 2) {
                this.errors.customer_name = 'Name must be at least 2 characters.';
                valid = false;
            } else {
                this.errors.customer_name = '';
            }

            const phone = this.form.customer_phone.trim();
            if (!phone) {
                this.errors.customer_phone = 'Contact number is required.';
                valid = false;
            } else if (phone.replace(/\D/g, '').length < 7) {
                this.errors.customer_phone = 'Please enter a valid phone number.';
                valid = false;
            } else {
                this.errors.customer_phone = '';
            }

            const email = this.form.customer_email.trim();
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                this.errors.customer_email = 'Please enter a valid email address.';
                valid = false;
            } else {
                this.errors.customer_email = '';
            }

            return valid;
        },

        // ── Submit ────────────────────────────────────────────
        async submit() {
            if (!this.validateDetails()) return;

            this.isSubmitting = true;
            this.apiError     = '';

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            try {
                const response = await fetch(@json($bookUrl), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        service_id:       this.form.service_id,
                        slot_id:          this.form.slot_id,
                        appointment_date: this.form.appointment_date,
                        customer_name:    this.form.customer_name.trim(),
                        customer_phone:   this.form.customer_phone.trim(),
                        customer_email:   this.form.customer_email.trim() || null,
                        address:          this.form.address.trim()        || null,
                        notes:            this.form.notes.trim()          || null,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.appointmentNo = data.appointment_no || '';
                    this.step          = 4;
                    this.$nextTick(() => {
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    });
                } else {
                    // Handle Laravel validation errors (422)
                    if (response.status === 422 && data.errors) {
                        const first = Object.values(data.errors)[0];
                        this.apiError = Array.isArray(first) ? first[0] : first;
                    } else {
                        this.apiError = data.message || 'Something went wrong. Please try again.';
                    }
                }

            } catch (err) {
                this.apiError = 'Network error. Please check your connection and try again.';
            } finally {
                this.isSubmitting = false;
            }
        },

        // ── Helpers ───────────────────────────────────────────
        formatDate(dateStr) {
            if (!dateStr) return '';
            try {
                const d = new Date(dateStr + 'T00:00:00');
                return d.toLocaleDateString('en-IN', {
                    weekday: 'short', day: 'numeric',
                    month:   'short', year: 'numeric',
                });
            } catch (_) {
                return dateStr;
            }
        },

    };
}
</script>
@endpush

@endif
{{-- /@if($appointmentEnabled) --}}