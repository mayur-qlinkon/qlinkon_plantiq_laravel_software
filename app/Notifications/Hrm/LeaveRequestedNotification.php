<?php

namespace App\Notifications\Hrm;

use App\Models\Hrm\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveRequestedNotification extends Notification
{
    use Queueable;

    protected Leave $leave;

    public function __construct(Leave $leave)
    {
        $this->leave = $leave;
    }

    public function via(object $notifiable): array
    {
        // Email is handled by EmailService in the listener, so we only need 'database' here.
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        // Safely grab the full name using your Employee model accessor
        $employeeName = $this->leave->employee->full_name ?: 'Unknown Employee';

        return [
            'type' => 'leave_request',
            'title' => 'New Leave Request',
            'message' => "{$employeeName} requested {$this->leave->total_days} day(s) off.",
            'icon' => 'calendar-clock',
            'color' => 'amber',
            'link' => '/admin/hrm/leaves/'.$this->leave->id,
        ];
    }
}
