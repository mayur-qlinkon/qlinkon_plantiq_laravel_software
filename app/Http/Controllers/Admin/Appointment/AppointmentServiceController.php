<?php

namespace App\Http\Controllers\Admin\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Appointment\AppointmentService;
use App\Services\Appointment\AppointmentService as AppointmentSvc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class AppointmentServiceController extends Controller
{
    public function __construct(
        protected AppointmentSvc $appointmentService
    ) {}

    // ══════════════════════════════════════════════════════════
    //  INDEX
    // ══════════════════════════════════════════════════════════

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'is_active', 'per_page']);

        $services = $this->appointmentService->getServiceList($filters);

        return view('admin.appointments.services', compact('services', 'filters'));
    }

    // ══════════════════════════════════════════════════════════
    //  STORE
    // ══════════════════════════════════════════════════════════

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'price'            => ['nullable', 'numeric', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:480'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
            'is_active'        => ['boolean'],
        ]);

        // Checkbox safe fallback
        $validated['is_active'] = $request->boolean('is_active', true);

        try {
            $this->appointmentService->createService($validated);

            return redirect()
                ->route('admin.appointments.services.index')
                ->with('success', 'Service created successfully.');

        } catch (Throwable $e) {
            return back()->withInput()->with('error', 'Failed to create service. Please try again.');
        }
    }

    // ══════════════════════════════════════════════════════════
    //  UPDATE
    // ══════════════════════════════════════════════════════════

    public function update(Request $request, AppointmentService $appointmentService): RedirectResponse
    {
        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'price'            => ['nullable', 'numeric', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:480'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
            'is_active'        => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        try {
            $this->appointmentService->updateService($appointmentService, $validated);

            return redirect()
                ->route('admin.appointments.services.index')
                ->with('success', 'Service updated successfully.');

        } catch (Throwable $e) {
            return back()->withInput()->with('error', 'Failed to update service. Please try again.');
        }
    }

    // ══════════════════════════════════════════════════════════
    //  DESTROY  (AJAX JSON)
    // ══════════════════════════════════════════════════════════

    public function destroy(AppointmentService $appointmentService): JsonResponse
    {
        try {
            $this->appointmentService->deleteService($appointmentService);

            return response()->json([
                'success' => true,
                'message' => "Service \"{$appointmentService->name}\" deleted successfully.",
            ]);

        } catch (InvalidArgumentException $e) {
            // Active appointments blocking delete — user-friendly message
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete service. Please try again.',
            ], 500);
        }
    }
}