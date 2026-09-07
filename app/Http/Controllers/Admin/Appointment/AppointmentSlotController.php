<?php

namespace App\Http\Controllers\Admin\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Appointment\AppointmentSlot;
use App\Services\Appointment\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;
use Throwable;

class AppointmentSlotController extends Controller
{
    public function __construct(
        protected AppointmentService $appointmentService
    ) {}

    // ══════════════════════════════════════════════════════════
    //  INDEX
    // ══════════════════════════════════════════════════════════

    public function index(Request $request): View
    {
        $filters = $request->only(['is_active', 'per_page']);

        $slots = $this->appointmentService->getSlotList($filters);

        return view('admin.appointments.slots', compact('slots', 'filters'));
    }

    // ══════════════════════════════════════════════════════════
    //  STORE
    // ══════════════════════════════════════════════════════════

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slot_name'  => ['required', 'string', 'max:100'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
            'is_active'  => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        try {
            $this->appointmentService->createSlot($validated);

            return redirect()
                ->route('admin.appointments.slots.index')
                ->with('success', 'Slot created successfully.');

        } catch (InvalidArgumentException $e) {
            // Overlap rejections are a user-facing rule, not a failure.
            return back()->withInput()->with('error', $e->getMessage());

        } catch (Throwable $e) {
            Log::error('[Appointments] Slot create failed', ['exception' => $e]);

            return back()->withInput()->with('error', 'Failed to create slot. Please try again.');
        }
    }

    // ══════════════════════════════════════════════════════════
    //  UPDATE
    // ══════════════════════════════════════════════════════════

    public function update(Request $request, AppointmentSlot $appointmentSlot): RedirectResponse
    {
        $validated = $request->validate([
            'slot_name'  => ['required', 'string', 'max:100'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
            'is_active'  => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        try {
            $this->appointmentService->updateSlot($appointmentSlot, $validated);

            return redirect()
                ->route('admin.appointments.slots.index')
                ->with('success', 'Slot updated successfully.');

        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());

        } catch (Throwable $e) {
            Log::error('[Appointments] Slot update failed', [
                'slot_id'   => $appointmentSlot->id,
                'exception' => $e,
            ]);

            return back()->withInput()->with('error', 'Failed to update slot. Please try again.');
        }
    }

    // ══════════════════════════════════════════════════════════
    //  DESTROY  (AJAX JSON)
    // ══════════════════════════════════════════════════════════

    public function destroy(AppointmentSlot $appointmentSlot): JsonResponse
    {
        try {
            $this->appointmentService->deleteSlot($appointmentSlot);

            return response()->json([
                'success' => true,
                'message' => "Slot \"{$appointmentSlot->slot_name}\" deleted successfully.",
            ]);

        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete slot. Please try again.',
            ], 500);
        }
    }
}