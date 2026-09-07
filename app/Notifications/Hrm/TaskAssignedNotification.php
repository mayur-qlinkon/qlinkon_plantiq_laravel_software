<?php

namespace App\Notifications\Hrm;

use App\Models\Hrm\HrmTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public HrmTask $task,
        public bool $isPrimary
    ) {}

    public function via(object $notifiable): array
    {
        return ['database']; // Stores directly in the 'notifications' db table
    }

    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'title' => 'Task Assigned',
            'message' => $this->isPrimary
                ? "You have been assigned as the PRIMARY assignee for task: {$this->task->title}"
                : "You have been assigned to a new task: {$this->task->title}",
            'link' => route('admin.hrm.my-tasks.index', $this->task->id),
        ];
    }
}