<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\NotificationEvent;
use App\Http\Controllers\Controller;
use App\Mail\DynamicMail;
use App\Models\Appointment\Appointment;
use App\Notifications\AppNotification;
use App\Services\Appointment\AppointmentService;
use App\Services\NotificationDispatcher;
use App\Services\Platform\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;
use Illuminate\Support\Facades\Log;

class AppointmentBookingController extends Controller
{
    public function __construct(
        protected AppointmentService $appointmentService,
        protected NotificationDispatcher $dispatcher,
        protected EmailService $emailService,
    ) {}

    // ══════════════════════════════════════════════════════════
    //  BOOK  — POST /{slug}/appointments/book
    //  Called by the storefront booking modal form.
    //  companyId resolved from request attribute (set by IdentifyTenant / slug).
    // ══════════════════════════════════════════════════════════

    public function book(Request $request): JsonResponse
    {
        // Slug takes HIGHEST priority — same as StorefrontController::resolveCompany().
        $slug      = $request->route('slug');
        $companyId = $slug
            ? \App\Models\Company::where('slug', $slug)->where('is_active', true)->value('id')
            : $request->attributes->get('current_company_id');

        if (! $companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid storefront. Please try again.',
            ], 400);
        }

        $validated = $request->validate([
            'service_id'       => ['required', 'integer'],
            'slot_id'          => ['required', 'integer'],
            // An upper bound was missing entirely, so bookings could be placed
            // years into the future.
            'appointment_date' => [
                'required', 'date', 'date_format:Y-m-d',
                'after_or_equal:today',
                'before_or_equal:' . now()->addDays(365)->toDateString(),
            ],
            'customer_name'    => ['required', 'string', 'max:255'],
            'customer_phone'   => ['required', 'string', 'max:20'],
            'customer_email'   => ['nullable', 'email', 'max:255'],
            'address'          => ['nullable', 'string', 'max:500'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $appointment = $this->appointmentService->book($validated, $companyId);

            // Outside book(), which wraps its work in DB::transaction(). Notifying
            // from inside would announce a booking that a later rollback undoes,
            // and an email cannot be recalled.
            $this->notifyBooked($appointment, $companyId);
            $this->confirmToCustomer($appointment);

            return response()->json([
                'success'        => true,
                'message'        => 'Your appointment has been booked successfully! We will confirm it shortly.',
                'appointment_no' => $appointment->appointment_no,
            ]);

        } catch (InvalidArgumentException $e) {
            // Slot already booked, inactive service/slot — show to user
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {
            // The service logs its own expected rejections; this branch is for
            // the unexpected ones, which would otherwise vanish silently.
            Log::error('[AppointmentBooking] Booking failed', [
                'company_id' => $companyId,
                'slot_id'    => $validated['slot_id'],
                'date'       => $validated['appointment_date'],
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════
    //  CHECK SLOT  — GET /{slug}/appointments/check-slot
    //  AJAX: Storefront JS calls this when user picks date + slot.
    //  Returns available = true/false instantly.
    // ══════════════════════════════════════════════════════════

    public function checkSlot(Request $request): JsonResponse
    {
        // Slug takes HIGHEST priority — same as StorefrontController::resolveCompany().
        $slug      = $request->route('slug');
        $companyId = $slug
            ? \App\Models\Company::where('slug', $slug)->where('is_active', true)->value('id')
            : $request->attributes->get('current_company_id');

        // Same guard as book(). Without it an unknown slug passes null into
        // isSlotAvailable(int $companyId, ...) and the request 500s.
        if (! $companyId) {
            return response()->json([
                'available' => false,
                'message'   => 'Invalid storefront. Please try again.',
            ], 400);
        }

        $request->validate([
            'slot_id' => ['required', 'integer'],
            'date'    => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);

        $available = $this->appointmentService->isSlotAvailable(
            $companyId,
            (int) $request->slot_id,
            $request->date
        );

        return response()->json([
            'available' => $available,
            'message'   => $available
                ? 'This slot is available.'
                : 'This slot is already booked for the selected date. Please choose another.',
        ]);
    }

    // ══════════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════
        // ══════════════════════════════════════════════════════════
    //  BOOKED SLOTS  — GET /{slug}/appointments/booked-slots
    //  One call returns every unavailable slot id for a date.
    //  The modal used to fire one check-slot request per slot.
    // ══════════════════════════════════════════════════════════

    public function bookedSlots(Request $request): JsonResponse
    {
        $slug      = $request->route('slug');
        $companyId = $slug
            ? \App\Models\Company::where('slug', $slug)->where('is_active', true)->value('id')
            : $request->attributes->get('current_company_id');

        if (! $companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid storefront. Please try again.',
            ], 400);
        }

        $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        return response()->json([
            'success'          => true,
            'booked_slot_ids'  => $this->appointmentService->getUnavailableSlotIds($companyId, $request->date),
        ]);
    }
    /**
     * Announce a new booking to whoever the tenant configured.
     *
     * Never throws: the customer's booking is already committed, and a mail
     * outage must not turn a successful booking into an error on their screen.
     * The dispatcher and EmailService both swallow their own failures, so this
     * guard only covers anything unexpected on the way in.
     */
    private function notifyBooked(Appointment $appointment, int $companyId): void
    {
        try {
            $serviceName = $appointment->service?->name ?? 'Appointment';
            $slotLabel   = $appointment->slot?->display_label ?? '—';
            $date        = $appointment->appointment_date?->format('d M Y') ?? '—';

            $this->dispatcher->dispatch(
                NotificationEvent::AppointmentBooked,
                $companyId,
                notification: new AppNotification(
                    title: 'New Appointment',
                    message: "{$appointment->customer_name} booked {$serviceName} on {$date} ({$slotLabel}).",
                    link: route('admin.appointments.show', $appointment->id),
                    icon: 'calendar-clock',
                    color: 'blue',
                    type: 'appointment_booked',
                    extra: ['appointment_id' => $appointment->id],
                ),
                mailable: new DynamicMail(
                    "New appointment: {$appointment->customer_name} — {$appointment->appointment_no}",
                    'emails.appointment-booked',
                    [
                        'appointmentNo'   => $appointment->appointment_no,
                        'serviceName'     => $serviceName,
                        'slotLabel'       => $slotLabel,
                        'appointmentDate' => $date,
                        'customerName'    => $appointment->customer_name,
                        'customerPhone'   => $appointment->customer_phone,
                        'customerEmail'   => $appointment->customer_email,
                        'address'         => $appointment->address,
                        'notes'           => $appointment->notes,
                        'actionUrl'       => route('admin.appointments.show', $appointment->id),
                    ],
                ),
                // The permission row alone is company-wide; module access is a
                // separate seat. Without this, a user with appointments.view but
                // no Appointments seat is notified about a page they get 403 on.
                recipientFilter: fn ($user) => user_can_access_module($user, 'appointments'),
            );
        } catch (Throwable $e) {
            Log::error('[AppointmentBooking] Notification dispatch failed', [
                'appointment_no' => $appointment->appointment_no,
                'company_id'     => $companyId,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send the customer their own copy.
     *
     * Kept separate from notifyBooked() so that a failure on one side still
     * lets the other through — the staff notification and the customer receipt
     * answer to different rules and should not share a failure path.
     */
    private function confirmToCustomer(Appointment $appointment): void
    {
        try {
            $this->emailService->sendCustomerAppointmentConfirmation($appointment);
        } catch (Throwable $e) {
            Log::error('[AppointmentBooking] Customer confirmation failed', [
                'appointment_no' => $appointment->appointment_no,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}