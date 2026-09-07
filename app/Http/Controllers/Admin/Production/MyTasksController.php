<?php

namespace App\Http\Controllers\Admin\Production;

use App\Http\Controllers\Controller;
use App\Models\Production\BatchPlacement;
use App\Models\Production\DailyTask;
use App\Models\Production\GrowingSpace;
use App\Models\Production\PlantBatch;
use App\Models\Production\ZoneAssignment;
use App\Models\Production\Zone;

use App\Enums\Production\TaskStatus;
use App\Enums\Production\ActivityType;

use App\Services\Production\BatchLossService;
use App\Services\Production\BatchHarvestService;
use App\Services\Production\BatchPlacementService;
use App\Services\Production\TaskNotificationService;

use App\Http\Requests\Admin\Production\StoreBatchLossRequest;
use App\Http\Requests\Admin\Production\StoreBatchHarvestRequest;
use App\Http\Requests\Admin\Production\MoveBatchRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class MyTasksController extends Controller
{
    // ════════════════════════════════════════════════════
    //  INDEX — Today's Tasks checklist
    //  GET /admin/production/my-tasks
    // ════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $employee = Auth::user()->employee;

        $zoneIds = ZoneAssignment::where('employee_id', $employee->id)
            ->active()
            ->pluck('zone_id');

        // 1. Today's Checklist grouped by Zone
        $tasks = DailyTask::whereIn('zone_id', $zoneIds)
            ->forDate(today()->toDateString())
            ->with(['plantBatch.product', 'zone', 'template'])
            ->orderBy('zone_id')
            ->orderBy('plant_batch_id')
            ->get()
            ->groupBy(fn ($task) => $task->zone->name ?? 'Unassigned Zone');

        $pendingCount = $tasks->flatten()->where('status', TaskStatus::Pending)->count();
        $doneCount = $tasks->flatten()->where('status', TaskStatus::Done)->count();

        // 2. Full History (due_date < today) with Pagination
        $historyTasks = DailyTask::whereIn('zone_id', $zoneIds)
            ->where('due_date', '<', today()->toDateString())
            ->with(['plantBatch.product', 'zone', 'template', 'completedBy'])
            ->orderBy('due_date', 'desc')
            ->orderBy('zone_id')
            ->paginate(15, ['*'], 'history_page')
            ->withQueryString();

        // 3. Enum metadata for activity colors, labels & icons
        $activityMeta = [];
        foreach (ActivityType::cases() as $case) {
            $activityMeta[$case->value] = [
                'label' => $case->label(),
                'icon'  => $case->icon(),
                'color' => $case->color(),
            ];
        }

        // 4. Worker's active assigned zones for UI Zone Switcher Pills
        $assignedZones = Zone::whereIn('id', $zoneIds)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('admin.production.my-tasks.index', compact(
            'tasks', 
            'pendingCount', 
            'doneCount', 
            'historyTasks', 
            'activityMeta',
            'assignedZones'
        ));
    }

    // ════════════════════════════════════════════════════
    //  NOT CHECKED IN — blocked page (middleware redirects here)
    //  GET /admin/production/my-tasks/not-checked-in
    // ════════════════════════════════════════════════════

    public function notCheckedIn()
    {
        return view('admin.production.my-tasks.not-checked-in');
    }

    // ════════════════════════════════════════════════════
    //  TOGGLE — checkbox tick/untick, single endpoint.
    //  Pending → Done (creates BatchActivity) or Done → Pending (reverses it).
    //  POST /admin/production/my-tasks/{dailyTask}/toggle
    // ════════════════════════════════════════════════════

    public function toggle(Request $request, DailyTask $dailyTask, TaskNotificationService $taskNotificationService): JsonResponse
    {
        $employee = Auth::user()->employee;

        // Defense-in-depth: worker can only toggle tasks in their own assigned zone
        $ownsZone = ZoneAssignment::where('employee_id', $employee->id)
            ->where('zone_id', $dailyTask->zone_id)
            ->active()
            ->exists();

        if (! $ownsZone) {
            abort(403, 'This task does not belong to your zone.');
        }

        if ($dailyTask->status === TaskStatus::Skipped) {
            return response()->json(['success' => false, 'message' => 'This task has already been processed.'], 422);
        }

        $isCompleting = $dailyTask->status === TaskStatus::Pending;
        $notes = $request->input('notes');

        try {
            DB::transaction(function () use ($dailyTask, $employee, $notes, $isCompleting) {
                if ($isCompleting) {
                    // Register the actual activity — this is the piece that was previously missing entirely
                    $activity = DB::table('production_batch_activities')->insertGetId([
                        'company_id' => $dailyTask->company_id,
                        'plant_batch_id' => $dailyTask->plant_batch_id,
                        'activity_type' => $dailyTask->activity_type->value,
                        'performed_on' => now(),
                        'performed_by' => Auth::id(), // User ID — performed_by has an FK to users, not employees
                        'notes' => $notes,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $dailyTask->update([
                        'status' => TaskStatus::Done,
                        'completed_by' => $employee->id,
                        'completed_at' => now(),
                        'batch_activity_id' => $activity,
                        'notes' => $notes,
                    ]);
                } else {
                    // Remove the activity record created when the task was completed,
                    // so unchecking fully reverses it instead of leaving an orphaned row.
                    if ($dailyTask->batch_activity_id) {
                        DB::table('production_batch_activities')->where('id', $dailyTask->batch_activity_id)->delete();
                    }

                    $dailyTask->update([
                        'status' => TaskStatus::Pending,
                        'completed_by' => null,
                        'completed_at' => null,
                        'batch_activity_id' => null,
                        'notes' => null,
                    ]);
                }
            });
             
            if ($isCompleting) {
                $taskNotificationService->taskCompleted($dailyTask->fresh(['plantBatch', 'zone']));
            }

            Log::info('[MyTasks] Task toggled', [
                'task_id' => $dailyTask->id,
                'employee_id' => $employee->id,
                'new_status' => $isCompleting ? 'done' : 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => $isCompleting ? 'Task completed.' : 'Task marked as pending.',
                'status' => $isCompleting ? 'done' : 'pending',
            ]);

        } catch (Throwable $e) {
            Log::error('[MyTasks] Toggle failed', ['task_id' => $dailyTask->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Something went wrong, please try again.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  RECORD LOSS — worker action for reporting how many dead plants were found.
    //  Batch-scoped, independent of any task — can be reported at any time.
    //  POST /admin/production/my-tasks/losses
    // ════════════════════════════════════════════════════

    public function recordLoss(StoreBatchLossRequest $request, BatchLossService $batchLossService): JsonResponse
    {
        $employee = Auth::user()->employee;
        $plantBatch = PlantBatch::findOrFail($request->plant_batch_id);

        // Defense-in-depth: the growing space where this batch is currently placed
        // must belong to one of the worker's assigned zones.
        $zoneIds = ZoneAssignment::where('employee_id', $employee->id)
            ->active()
            ->pluck('zone_id');

        $inWorkerZone = BatchPlacement::active()
            ->where('plant_batch_id', $plantBatch->id)
            ->whereHas('growingSpace', fn ($q) => $q->whereIn('zone_id', $zoneIds))
            ->exists();

        if (! $inWorkerZone) {
             abort(403, 'This batch does not belong to your assigned zone.');
        }

        try {
            $batchLossService->record(
                batch: $plantBatch,
                quantityLost: (int) $request->quantity_lost,
                reason: $request->reason,
                notes: $request->notes,
                companyId: $plantBatch->company_id,
                userId: Auth::id(),
            );

            Log::info('[MyTasks] Loss recorded', [
                'batch_id' => $plantBatch->id,
                'employee_id' => $employee->id,
                'quantity' => $request->quantity_lost,
            ]);

            // Reporting a loss already proves the dead-plant check was performed —
            // auto-complete today's pending Dead Check task for this batch so the
            // worker doesn't have to separately tick it. Idempotent: a worker can
            // report loss any number of times today, but this only fires once —
            // once the task is Done, the lookup below finds nothing and no-ops.
            $completedTaskId = $this->autoCompleteDeadCheckTask($plantBatch, $employee);

            return response()->json([
                'success' => true,
                'message' => 'Loss recorded successfully.',
                'completed_task_id' => $completedTaskId,
            ]);

        } catch (RuntimeException $e) {
            // e.g. quantity_lost exceeds current_quantity
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);

        } catch (Throwable $e) {
            Log::error('[MyTasks] Loss record failed', ['batch_id' => $plantBatch->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Something went wrong, please try again.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  RECORD HARVEST — worker action for reporting harvested quantity.
    //  Batch-scoped, independent of any task — same pattern as recordLoss(),
    //  but does NOT auto-complete any task (no Harvest ActivityType/DailyTask link).
    //  POST /admin/production/my-tasks/harvests
    // ════════════════════════════════════════════════════

    public function recordHarvest(StoreBatchHarvestRequest $request, BatchHarvestService $batchHarvestService): JsonResponse
    {
        $employee = Auth::user()->employee;
        $plantBatch = PlantBatch::findOrFail($request->plant_batch_id);

        // Defense-in-depth: the growing space where this batch is currently placed
        // must belong to one of the worker's assigned zones.
        $zoneIds = ZoneAssignment::where('employee_id', $employee->id)
            ->active()
            ->pluck('zone_id');

        $inWorkerZone = BatchPlacement::active()
            ->where('plant_batch_id', $plantBatch->id)
            ->whereHas('growingSpace', fn ($q) => $q->whereIn('zone_id', $zoneIds))
            ->exists();

        if (! $inWorkerZone) {
            abort(403, 'This batch does not belong to your assigned zone.');
        }

        try {
            $batchHarvestService->record(
                batch: $plantBatch,
                quantityHarvested: (int) $request->quantity_harvested,
                warehouseId: $request->warehouse_id,
                notes: $request->notes,
                companyId: $plantBatch->company_id,
                userId: Auth::id(),
            );

            Log::info('[MyTasks] Harvest recorded', [
                'batch_id' => $plantBatch->id,
                'employee_id' => $employee->id,
                'quantity' => $request->quantity_harvested,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Harvest recorded successfully.',
            ]);

        } catch (RuntimeException $e) {
            // e.g. quantity_harvested exceeds current_quantity
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);

        } catch (Throwable $e) {
            Log::error('[MyTasks] Harvest record failed', ['batch_id' => $plantBatch->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Something went wrong, please try again.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  AUTO-COMPLETE DEAD CHECK — internal helper, called from recordLoss()
    //  Mirrors complete()'s write path so both routes produce the same
    //  BatchActivity + DailyTask shape.
    // ════════════════════════════════════════════════════

    private function autoCompleteDeadCheckTask(PlantBatch $plantBatch, $employee): ?int
    {
        $task = DailyTask::where('plant_batch_id', $plantBatch->id)
            ->where('activity_type', ActivityType::DeadCheck->value)
            ->forDate(today()->toDateString())
            ->where('status', TaskStatus::Pending->value)
            ->first();

        if (! $task) {
            return null;
        }

        DB::transaction(function () use ($task, $employee) {
            $activityId = DB::table('production_batch_activities')->insertGetId([
                'company_id' => $task->company_id,
                'plant_batch_id' => $task->plant_batch_id,
                'activity_type' => $task->activity_type->value,
                'performed_on' => now(),
                'performed_by' => Auth::id(),
                'notes' => 'Auto-completed via Report Loss',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $task->update([
                'status' => TaskStatus::Done,
                'completed_by' => $employee->id,
                'completed_at' => now(),
                'batch_activity_id' => $activityId,
                'notes' => 'Auto-completed via Report Loss',
            ]);
        });

        return $task->id;
    }

    // ════════════════════════════════════════════════════
    //  BATCHES FOR DROPDOWN — "Report Loss" modal data source
    //  GET /admin/production/my-tasks/batches
    // ════════════════════════════════════════════════════

    public function batchesForDropdown(): JsonResponse
    {
        $employee = Auth::user()->employee;

        $zoneIds = ZoneAssignment::where('employee_id', $employee->id)
            ->active()
            ->pluck('zone_id');

        $batches = BatchPlacement::active()
            ->whereHas('growingSpace', fn ($q) => $q->whereIn('zone_id', $zoneIds))
            ->with(['batch.product', 'growingSpace.zone'])
            ->get()
            ->map(fn ($placement) => [
                'id' => $placement->batch->id,
                'batch_code' => $placement->batch->batch_code,
                'product_name' => $placement->batch->product->name ?? 'Unknown species',
                'current_quantity' => $placement->batch->current_quantity,
                // Opening Stock batches skip the harvest lifecycle entirely —
                // used by the frontend to exclude them from the Report Harvest picker.
                'source_type' => $placement->batch->source_type,
                // Needed by the Move Batch modal to show "Currently in: X" and
                // exclude the current space from the destination list.
                'growing_space_id' => $placement->growing_space_id,
                'growing_space_name' => $placement->growingSpace->name ?? 'Unknown space',
                'zone_name' => $placement->growingSpace->zone->name ?? null,
            ])
            ->values();

        return response()->json(['success' => true, 'batches' => $batches]);
    }

    // ════════════════════════════════════════════════════
    //  SPACES FOR DROPDOWN — destinations for the Move Batch modal.
    //  Scoped to the worker's assigned zones only — same boundary as
    //  batchesForDropdown(), so a worker can only move plants between
    //  spaces they're responsible for.
    //  GET /admin/production/my-tasks/spaces
    // ════════════════════════════════════════════════════

    public function spacesForDropdown(): JsonResponse
    {
        $employee = Auth::user()->employee;

        $zoneIds = ZoneAssignment::where('employee_id', $employee->id)
            ->active()
            ->pluck('zone_id');

        $spaces = GrowingSpace::where('is_active', true)
            ->whereIn('zone_id', $zoneIds)
            ->with('zone')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($space) => [
                'id' => $space->id,
                'name' => $space->name,
                'zone_name' => $space->zone->name ?? null,
            ])
            ->values();

        return response()->json(['success' => true, 'spaces' => $spaces]);
    }

    // ════════════════════════════════════════════════════
    //  MOVE BATCH — AJAX
    //  Reuses BatchPlacementService::place(), which already treats
    //  initial placement and movement as the same ledger operation
    //  (closes the current active row, inserts a new one).
    //  POST /admin/production/my-tasks/move
    // ════════════════════════════════════════════════════

    public function moveBatch(MoveBatchRequest $request, BatchPlacementService $placementService): JsonResponse
    {
        $employee = Auth::user()->employee;
        $plantBatch = PlantBatch::findOrFail($request->plant_batch_id);
        $destination = GrowingSpace::findOrFail($request->growing_space_id);

        $zoneIds = ZoneAssignment::where('employee_id', $employee->id)
            ->active()
            ->pluck('zone_id');

        // Defense-in-depth: batch's current space AND the destination space
        // must both belong to the worker's assigned zones.
        $batchInWorkerZone = BatchPlacement::active()
            ->where('plant_batch_id', $plantBatch->id)
            ->whereHas('growingSpace', fn ($q) => $q->whereIn('zone_id', $zoneIds))
            ->exists();

        if (! $batchInWorkerZone) {
            abort(403, 'This batch does not belong to your assigned zone.');
        }

        if (! $zoneIds->contains($destination->zone_id)) {
            abort(403, 'The destination space is outside your assigned zones.');
        }

        try {
            $placementService->place(
                batch: $plantBatch,
                space: $destination,
                userId: Auth::id(),
                notes: $request->notes,
            );

            Log::info('[MyTasks] Batch moved', [
                'batch_id' => $plantBatch->id,
                'employee_id' => $employee->id,
                'growing_space_id' => $destination->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Batch {$plantBatch->batch_code} moved to {$destination->name}.",
            ]);

        } catch (InvalidArgumentException|RuntimeException $e) {
            // e.g. batch already in this space, batch/space inactive
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);

        } catch (Throwable $e) {
            Log::error('[MyTasks] Move batch failed', [
                'batch_id' => $plantBatch->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Something went wrong, please try again.'], 500);
        }
    }
}