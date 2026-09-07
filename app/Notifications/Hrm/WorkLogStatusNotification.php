<?php

namespace App\Notifications\Hrm;

use App\Models\Hrm\WorkLog;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Targeted, not broadcast — the recipient is the employee who filed the log,
 * which follows from the data itself. See NotificationEvent for why events
 * like this one are deliberately absent from the settings screen.
 */
class WorkLogStatusNotification extends Notification
{
    use Queueable;

    public function __construct(protected WorkLog $workLog) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $status = $this->workLog->status;
        $isApproved = ($status === WorkLog::STATUS_APPROVED);

        $message = "Your work log for {$this->workLog->log_date->format('d M Y')} has been "
            . strtoupper($status) . '.';

        // The rejection reason is the actionable part — surfacing it here saves
        // the employee opening the page to find out why.
        if (! $isApproved && $this->workLog->admin_remarks) {
            $message .= " Reason: {$this->workLog->admin_remarks}";
        }

        return [
            'type'    => 'work_log_status_update',
            'title'   => 'Work Log ' . ucfirst($status),
            'message' => $message,
            'icon'    => $isApproved ? 'check-circle' : 'x-circle',
            'color'   => $isApproved ? 'green' : 'red',
            'link'    => route('admin.hrm.my-work-logs.index'),
        ];
    }
}