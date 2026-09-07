<?php

namespace App\Console\Commands;

use App\Enums\Production\TaskStatus;
use App\Models\Company;
use App\Models\Production\DailyTask;
use App\Services\Production\TaskNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class NotifyMissedProductionTasksCommand extends Command
{
    protected $signature = 'production:notify-missed-tasks
                            {--date= : Date to check as "missed" (Y-m-d). Default: yesterday}';

    protected $description = 'Notify company admins about production tasks still pending past their due date';

    public function handle(TaskNotificationService $taskNotificationService): int
    {
        $targetDate = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday();

        $this->info("Checking missed tasks for: {$targetDate->toDateString()}");

        $totalNotified = 0;

        // NOTE: Cron runs without Auth, Tenantable global scope NOT applied.
        $companies = Company::where('is_active', true)->get();

        foreach ($companies as $company) {
            $missedTasks = DailyTask::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->whereDate('due_date', $targetDate->toDateString())
                ->where('status', TaskStatus::Pending->value)
                ->with(['plantBatch', 'zone'])
                ->get();

            foreach ($missedTasks as $task) {
                $taskNotificationService->taskMissed($task);
                $totalNotified++;
            }
        }

        $this->info("Done. Missed tasks notified: {$totalNotified}");

        return self::SUCCESS;
    }
}