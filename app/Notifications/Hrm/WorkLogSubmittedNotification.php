<?php

namespace App\Notifications\Hrm;

use App\Models\Hrm\WorkLog;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WorkLogSubmittedNotification extends Notification
{
    use Queueable;

    protected WorkLog $workLog;

    public function __construct(WorkLog $workLog)
    {
        $this->workLog = $workLog;
    }

    public function via(object $notifiable): array
    {
        // Email is handled by the dispatcher's mailable in the listener,
        // so only the in-app channel belongs here.
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $employeeName = $this->workLog->employee->full_name ?: 'Unknown Employee';
        $hours = number_format($this->workLog->hours_worked, 1);

        return [
            'type'    => 'work_log_submitted',
            'title'   => 'New Work Log Submitted',
            'message' => "{$employeeName} submitted {$hours}h for {$this->workLog->log_date->format('d M Y')}.",
            'icon'    => 'clipboard-check',
            'color'   => 'amber',
            'link'    => '/admin/hrm/work-logs',
        ];
    }
}