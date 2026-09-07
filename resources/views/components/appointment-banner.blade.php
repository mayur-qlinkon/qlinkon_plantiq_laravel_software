{{--
 ┌─────────────────────────────────────────────────────────────────────────┐
 │  COMPONENT: components/appointment-banner                               │
 │  Usage: <x-appointment-banner :company="$company" />                    │
 │                                                                         │
 │  Renders only when get_setting('appointment_enabled') = 1               │
 │  All text is admin-configurable via Settings → Appointments section     │
 │  Brand color aware — uses CSS var(--brand-*) tokens                     │
 │  Dispatches Alpine event 'storefront:book-appointment' on click         │
 │   → Listeners (modal, page redirect) can hook in without changing here │
 └─────────────────────────────────────────────────────────────────────────┘
--}}

@props([
    /**
     * Required: The current tenant Company model.
     * Used for explicit company-scoped get_setting() calls.
     */
    'company',

    /**
     * Optional overrides — if passed, these take priority over DB settings.
     * Useful if you embed this component in a different context with custom copy.
     */
    'title'      => null,
    'subtitle'   => null,
    'buttonText' => null,
])

@php
    // ── Gate: only render if appointments are enabled for this company ──────
    $appointmentEnabled = (bool) get_setting('appointment_enabled', 0, $company->id);

    // ── Content: prop → DB setting → fallback string ─────────────────────────
    // Keys match exactly what SettingController saves from the appointments tab
    $heading    = $title      ?? get_setting('appointment_heading',    'Book a Service Appointment', $company->id);
    $subheading = $subtitle   ?? get_setting('appointment_subheading', 'Schedule a visit with our experts at your convenience.', $company->id);
    $btnLabel   = $buttonText ?? get_setting('appointment_button_text', 'Book Service', $company->id);
@endphp

@if($appointmentEnabled)

{{--
    ╔══════════════════════════════════════════════════════════════════════╗
    ║  APPOINTMENT BANNER SECTION                                          ║
    ║  Sits below hero banner slider, above product sections.              ║
    ╚══════════════════════════════════════════════════════════════════════╝
--}}
<section
    id="appointment-section"
    class="w-full rounded-2xl overflow-hidden mb-6 border border-gray-100 transition-all duration-300"
    style="
        background-color: #ebf2fa;
    "
    x-data
    aria-label="Book an appointment"
>
    <div class="py-8 sm:py-10 lg:py-12 px-6 sm:px-12 flex flex-col items-center justify-center text-center max-w-4xl mx-auto">

        {{-- ── Premium Sprout/Plant SVG Icon Badge ─────────────────────────── --}}
        <div
            class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 shadow-sm border border-white transition-transform duration-300 hover:scale-105"
            style="background-color: color-mix(in srgb, var(--brand-500) 12%, white);"
        >
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: var(--brand-600);">
                <path d="M12 22V10M12 10C12 10 12.5 6.5 16 5.5C19.5 4.5 19.5 8 19.5 8C19.5 9.5 18 12.5 14 13.5C12.5 13.8.5 14 12 10ZM12 12C12 12 11.5 8.5 8 7.5C4.5 6.5 4.5 10 4.5 10C4.5 11.5 6 14.5 10 15.5C11.5 15.8 12 12 12 12Z" 
                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        {{-- ── Heading ─────────────────────────────────────────────────────── --}}
        <h2
            class="text-xl sm:text-2xl lg:text-3xl font-extrabold leading-tight tracking-tight mb-2 max-w-2xl"
            style="color: var(--brand-700, var(--brand-600));"
        >
            {!! nl2br(e($heading)) !!}
        </h2>

        {{-- ── Subheading ──────────────────────────────────────────────────── --}}
        @if($subheading)
            <p class="text-xs sm:text-sm text-gray-700 max-w-xl mb-6 leading-relaxed">
                {{ $subheading }}
            </p>
        @endif

        {{-- ── CTA Button (High-Contrast Highlighted Style) ────────────────── --}}
        {{--
            Dispatches a custom Alpine event: 'storefront:book-appointment'
            To connect a modal, add the listener anywhere in the page:
               x-on:storefront:book-appointment.window="modalOpen = true"
            To link to a page instead, swap the button for an <a> tag.
        --}}
        <button
            type="button"
            @click="$dispatch('storefront:book-appointment')"
            class="
                inline-flex items-center gap-2.5
                px-10 py-3
                rounded-full
                text-white font-extrabold text-sm sm:text-base
                shadow-md hover:shadow-xl
                transform hover:-translate-y-0.5 active:translate-y-0
                transition-all duration-300
                focus:outline-none focus:ring-4 focus:ring-offset-2
                uppercase tracking-wider
            "
            style="
                background: linear-gradient(135deg, var(--brand-600) 0%, var(--brand-500) 100%);
            "
        >
            <i data-lucide="calendar-plus" class="w-4 h-4 sm:w-5 sm:h-5 shrink-0"></i>
            <span>{{ $btnLabel }}</span>
        </button>

    </div>
</section>

@endif