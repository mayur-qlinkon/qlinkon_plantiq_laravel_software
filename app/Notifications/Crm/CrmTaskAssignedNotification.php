<?php

namespace App\Notifications\Crm;

use App\Models\CrmTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CrmTaskAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(public CrmTask $task) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        // Safely fetch lead name
        $leadName = $this->task->lead->name ?? 'a lead';

        return [
            'type'    => 'crm_task_assigned',
            'title'   => 'CRM Task Assigned',
            'message' => "You have been assigned a task for {$leadName}: {$this->task->title}",
            'icon'    => 'check-square',
            'color'   => 'blue',
            'link'    => route('admin.crm.leads.show', $this->task->crm_lead_id),
        ];
    }
}