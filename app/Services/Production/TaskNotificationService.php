<?php

namespace App\Services\Production;

use App\Enums\NotificationEvent;
use App\Models\Production\DailyTask;
use App\Notifications\AppNotification;
use App\Services\NotificationDispatcher;

/**
 * Production task notifications.
 *
 * Recipients used to be hardcoded to every company admin, which a tenant
 * could neither redirect nor switch off — a nursery finishing dozens of tasks
 * a day had no way to stop the owner's bell filling up. Both events are now
 * configurable from Settings > Notifications.
 *
 * Called from a controller and from cron, so nothing here may depend on an
 * authenticated user; the dispatcher resolves recipients by company_id alone.
 */
class TaskNotificationService
{
    public function __construct(private NotificationDispatcher $dispatcher) {}

    public function taskCompleted(DailyTask $task): void
    {
        $this->send(
            task: $task,
            event: NotificationEvent::ProductionTaskCompleted,
            title: 'Task Completed',
            verb: 'completed',
            icon: 'check-circle',
            color: 'green',
            type: 'task_completed',
        );
    }

    public function taskMissed(DailyTask $task): void
    {
        $this->send(
            task: $task,
            event: NotificationEvent::ProductionTaskMissed,
            title: 'Task Missed',
            verb: 'missed',
            icon: 'alert-triangle',
            color: 'red',
            type: 'task_missed',
        );
    }

    private function send(
        DailyTask $task,
        NotificationEvent $event,
        string $title,
        string $verb,
        string $icon,
        string $color,
        string $type,
    ): void {
        $this->dispatcher->dispatch(
            $event,
            $task->company_id,
            notification: new AppNotification(
                title: $title,
                message: $this->buildMessage($task, $verb),
                link: route('admin.production.plant-batches.show', $task->plant_batch_id),
                icon: $icon,
                color: $color,
                type: $type,
                extra: [
                    'task_id' => $task->id,
                    'zone_id' => $task->zone_id,
                    'plant_batch_id' => $task->plant_batch_id,
                ],
            ),
        );
    }

    private function buildMessage(DailyTask $task, string $verb): string
    {
        $batchLabel = $task->plantBatch->batch_code ?? "Batch #{$task->plant_batch_id}";
        $zoneName = $task->zone->name ?? 'Unassigned Zone';
        $activityLabel = $task->activity_type->label();

        return "{$activityLabel} for {$batchLabel} in {$zoneName} was {$verb}.";
    }
}