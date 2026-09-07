<?php

namespace App\Http\Controllers\Admin\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Appointment\Appointment;
use App\Models\Appointment\AppointmentService as AppointmentServiceModel;
use App\Services\Appointment\AppointmentService;
use App\Services\Platform\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class AppointmentController extends Controller
{
    public function __construct(
        protected AppointmentService $appointmentService,
        protected EmailService $emailService,
    ) {}

    // ══════════════════════════════════════════════════════════
    //  INDEX
    // ══════════════════════════════════════════════════════════

    public function index(Request $request): View
    {
        $filters  = $request->only(['status', 'service_id', 'appointment_date', 'search', 'per_page']);

        $appointments = $this->appointmentService->getAppointmentList($filters);
        $services     = AppointmentServiceModel::ordered()->get(['id', 'name']);

        return view('admin.appointments.index', compact('appointments', 'services', 'filters'));
    }

    // ══════════════════════════════════════════════════════════
    //  SHOW
    // ══════════════════════════════════════════════════════════

    public function show(Appointment $appointment): View
    {
        $appointment->load(['service', 'slot']);

        return view('admin.appointments.show', compact('appointment'));
    }

    // ══════════════════════════════════════════════════════════
    //  CONFIRM  (AJAX JSON)
    // ══════════════════════════════════════════════════════════

    public function confirm(Request $request, Appointment $appointment): JsonResponse
    {
        $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $appointment = $this->appointmentService->confirm(
                $appointment,
                $request->input('admin_notes')
            );

            $this->emailCustomer($appointment);

            return response()->json([
                'success'      => true,
                'message'      => "Appointment {$appointment->appointment_no} confirmed.",
                'status'       => $appointment->status,
                'status_label' => $appointment->status_label,
                'status_color' => $appointment->status_color,
            ]);

        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('[Appointments] Status action failed', [
                'appointment_id' => $appointment->id,
                'exception'      => $e,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm appointment. Please try again.',
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════
    //  COMPLETE  (AJAX JSON)
    // ══════════════════════════════════════════════════════════

    public function complete(Request $request, Appointment $appointment): JsonResponse
    {
        $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $appointment = $this->appointmentService->complete(
                $appointment,
                $request->input('admin_notes')
            );

            return response()->json([
                'success'      => true,
                'message'      => "Appointment {$appointment->appointment_no} marked as completed.",
                'status'       => $appointment->status,
                'status_label' => $appointment->status_label,
                'status_color' => $appointment->status_color,
            ]);

        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('[Appointments] Status action failed', [
                'appointment_id' => $appointment->id,
                'exception'      => $e,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete appointment. Please try again.',
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════
    //  CANCEL  (AJAX JSON)
    // ══════════════════════════════════════════════════════════

    public function cancel(Request $request, Appointment $appointment): JsonResponse
    {
        $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $appointment = $this->appointmentService->cancel(
                $appointment,
                $request->input('admin_notes')
            );

            $this->emailCustomer($appointment);

            return response()->json([
                'success'      => true,
                'message'      => "Appointment {$appointment->appointment_no} cancelled.",
                'status'       => $appointment->status,
                'status_label' => $appointment->status_label,
                'status_color' => $appointment->status_color,
            ]);

        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('[Appointments] Status action failed', [
                'appointment_id' => $appointment->id,
                'exception'      => $e,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel appointment. Please try again.',
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════

    /**
     * Email the customer about a status change, without letting a mail problem
     * surface as a failed transition. The status is already committed by the
     * time this runs, so reporting an error here would tell the admin their
     * click did not work when it did.
     */
    private function emailCustomer(Appointment $appointment): void
    {
        try {
            $this->emailService->sendCustomerAppointmentStatusUpdate($appointment);
        } catch (Throwable $e) {
            Log::error('[Appointments] Customer status email failed', [
                'appointment_no' => $appointment->appointment_no,
                'status'         => $appointment->status,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}