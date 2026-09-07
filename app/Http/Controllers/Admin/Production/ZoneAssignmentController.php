<?php

namespace App\Http\Controllers\Admin\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Production\StoreZoneAssignmentRequest;
use App\Models\Hrm\Employee;
use App\Models\Production\Zone;
use App\Models\Production\ZoneAssignment;
use App\Services\Production\ZoneAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ZoneAssignmentController extends Controller
{
    public function __construct(protected ZoneAssignmentService $service)
    {
    }

    // ════════════════════════════════════════════════════
    //  INDEX — list page (Blade view)
    //  GET /admin/production/zone-assignments
    // ════════════════════════════════════════════════════

    public function index()
    {
        $companyId = Auth::user()->company_id;

        // Assignments whose employee has been archived are meaningless — the
        // person no longer works here. Excluding them here also stops the
        // soft-delete scope from handing the view a null relation.
        $assignments = ZoneAssignment::with(['employee', 'zone.site'])
            ->whereHas('employee')
            ->whereHas('zone')
            ->active()
            ->latest('assigned_at')
            ->paginate(20);

        $employees = Employee::where('company_id', $companyId)->active()
                    ->with('user:id,name')
                    ->get()
                    ->sortBy('full_name')
                    ->values();
        $zones = Zone::where('company_id', $companyId)->active()->with('site')->orderBy('name')->get();

        return view('admin.production.zone-assignments.index', compact('assignments', 'employees', 'zones'));
    }

    // ════════════════════════════════════════════════════
    //  STORE
    //  POST /admin/production/zone-assignments
    // ════════════════════════════════════════════════════

    public function store(StoreZoneAssignmentRequest $request): JsonResponse
    {
        try {
            $assignment = $this->service->assign(array_merge(
                $request->validated(),
                [
                    'company_id' => Auth::user()->company_id,
                    'assigned_by' => Auth::id(),
                ]
            ));

            $assignment->load(['employee', 'zone.site']);

            Log::info('[ZoneAssignment] Created', ['assignment_id' => $assignment->id, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => "{$assignment->employee->full_name} ko {$assignment->zone->name} assign kar diya gaya.",
                'assignment' => $assignment,
            ]);

        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);

        } catch (Throwable $e) {
            Log::error('[ZoneAssignment] Store failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Assignment create nahi ho paya.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  DESTROY — unassign
    //  DELETE /admin/production/zone-assignments/{zoneAssignment}
    // ════════════════════════════════════════════════════

    public function destroy(ZoneAssignment $zoneAssignment): JsonResponse
    {
        $this->authorizeAssignment($zoneAssignment);

        try {
            $this->service->unassign($zoneAssignment);

            Log::info('[ZoneAssignment] Removed', ['assignment_id' => $zoneAssignment->id, 'by' => Auth::id()]);

            return response()->json(['success' => true, 'message' => 'Assignment hata diya gaya.']);

        } catch (Throwable $e) {
            Log::error('[ZoneAssignment] Destroy failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to remove the zone assignment.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — Tenant isolation
    // ════════════════════════════════════════════════════

    private function authorizeAssignment(ZoneAssignment $assignment): void
    {
        if ($assignment->company_id !== Auth::user()->company_id) {
            abort(403, 'Access denied.');
        }
    }
}