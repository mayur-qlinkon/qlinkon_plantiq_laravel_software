<?php

namespace App\Services\Production;

use App\Enums\Production\BatchStatus;
use App\Enums\Production\TaskStatus;
use App\Models\Production\BatchActivity;
use App\Models\Production\BatchHarvest;
use App\Models\Production\BatchLoss;
use App\Models\Production\DailyTask;
use App\Models\Production\GrowingSpace;
use App\Models\Production\PlantBatch;
use App\Models\Production\ZoneAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProductionDashboardService
{
    // ════════════════════════════════════════════════════
    //  OWNER SNAPSHOT — single query pass per metric.
    //  All models use Tenantable, so no manual company_id
    //  filtering is needed here — the global scope handles it.
    // ════════════════════════════════════════════════════

    public function getStats(): array
    {
        $today = Carbon::today();

        return [
            'today_activities'      => $this->todayActivitiesCount($today),
            'live_plants'           => $this->livePlantsCount(),
            'live_batches'          => $this->liveBatchesCount(),
            'today_dead_plants'     => $this->todayDeadPlantsCount($today),
            'today_harvested'       => $this->todayHarvestedQuantity($today),
            'total_workers'         => $this->totalAssignedWorkers(),
            'month_harvested'       => $this->monthHarvestedQuantity(),
            'pending_tasks'         => $this->pendingTasksCount(),
        ];
    }

    protected function pendingTasksCount(): int
    {
        // Only today's still-open tasks. Pending tasks whose due_date has
        // already passed are "missed", not "pending" — excluded here.
        return DailyTask::where('status', TaskStatus::Pending->value)
            ->whereDate('due_date', Carbon::today())
            ->count();
    }

    // ════════════════════════════════════════════════════
    //  ACTIVE BATCHES — paginated table for the dashboard.
    // ════════════════════════════════════════════════════

    public function activeBatches(int $perPage = 5)
    {
        return PlantBatch::where('status', BatchStatus::Active)
            ->with([
                'product',
                'currentPlacement.growingSpace.zone.site',
                'currentPlacement.growingSpace.site',
            ])
            ->latest('id')
            ->paginate($perPage, ['*'], 'batches_page');
    }

    // ════════════════════════════════════════════════════
    //  AVAILABLE GROWING SPACES — for the "Move Batch" quick
    //  action dropdown. Active spaces only, company-scoped
    //  via Tenantable.
    // ════════════════════════════════════════════════════

    public function availableGrowingSpaces(): Collection
    {
        return GrowingSpace::where('is_active', true)
            ->with('zone')
            ->orderBy('name')
            ->get();
    }

    // ════════════════════════════════════════════════════
    //  RECENT ACTIVITY FEED — merges three event sources:
    //  worker-logged activities, admin zone assignments, and
    //  worker task completions — into one timeline.
    // ════════════════════════════════════════════════════

    public function recentActivity(int $limit = 15): Collection
    {
        $activities = BatchActivity::with(['batch', 'performedBy'])
            ->latest('performed_on')
            ->limit($limit)
            ->get()
            ->map(fn (BatchActivity $a) => [
                'type'      => 'activity_logged',
                'icon'      => $a->activityTypeEnum()->icon(),
                'title'     => $a->activityTypeEnum()->label().' logged',
                'subtitle'  => $a->batch?->batch_code,
                'actor'     => $a->performedBy?->name,
                'timestamp' => $a->performed_on,
            ]);

        $assignments = ZoneAssignment::with(['employee.user', 'zone', 'assignedBy'])
            ->latest('assigned_at')
            ->limit($limit)
            ->get()
            ->map(fn (ZoneAssignment $z) => [
                'type'      => 'zone_assigned',
                'icon'      => 'user-check',
                'title'     => ($z->employee?->full_name ?: 'Worker').' assigned to '.($z->zone?->name ?? 'a zone'),
                'subtitle'  => 'By '.($z->assignedBy?->name ?? 'Admin'),
                'actor'     => $z->assignedBy?->name,
                'timestamp' => $z->assigned_at,
            ]);

        $completions = DailyTask::with(['template', 'completedBy.user', 'plantBatch'])
            ->where('status', TaskStatus::Done->value)
            ->latest('completed_at')
            ->limit($limit)
            ->get()
            ->map(fn (DailyTask $t) => [
                'type'      => 'task_completed',
                'icon'      => $t->activity_type?->icon() ?? 'check',
                'title'     => ($t->activity_type?->label() ?? 'Task').' completed',
                'subtitle'  => $t->plantBatch?->batch_code,
                'actor'     => $t->completedBy?->full_name ?: 'Unknown',
                'timestamp' => $t->completed_at,
            ]);

        return $activities
            ->concat($assignments)
            ->concat($completions)
            ->filter(fn ($item) => $item['timestamp'] !== null)
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values();
    }

    protected function todayActivitiesCount(Carbon $today): int
    {
        return BatchActivity::whereDate('performed_on', $today)->count();
    }

    protected function livePlantsCount(): int
    {
        return (int) PlantBatch::where('status', BatchStatus::Active)->sum('current_quantity');
    }

    protected function liveBatchesCount(): int
    {
        return PlantBatch::where('status', BatchStatus::Active)->count();
    }

    protected function todayDeadPlantsCount(Carbon $today): int
    {
        return (int) BatchLoss::whereDate('loss_date', $today)->sum('quantity_lost');
    }

    protected function todayHarvestedQuantity(Carbon $today): int
    {
        return (int) BatchHarvest::whereDate('harvested_on', $today)->sum('quantity_harvested');
    }

    protected function totalAssignedWorkers(): int
    {
        return ZoneAssignment::where('is_active', true)->distinct('employee_id')->count('employee_id');
    }

    protected function monthHarvestedQuantity(): int
    {
        return (int) BatchHarvest::whereMonth('harvested_on', now()->month)
            ->whereYear('harvested_on', now()->year)
            ->sum('quantity_harvested');
    }
}