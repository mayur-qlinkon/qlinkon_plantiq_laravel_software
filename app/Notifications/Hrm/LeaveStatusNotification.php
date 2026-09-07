<?php

namespace App\Notifications\Hrm;

use App\Models\Hrm\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveStatusNotification extends Notification
{
    use Queueable;

    public function __construct(protected Leave $leave) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $status = $this->leave->status;
        $isApproved = ($status === Leave::STATUS_APPROVED);

        return [
            'type'    => 'leave_status_update',
            'title'   => 'Leave Request ' . ucfirst($status),
            'message' => "Your leave request for {$this->leave->total_days} day(s) has been " . strtoupper($status) . ".",
            'icon'    => $isApproved ? 'check-circle' : 'x-circle',
            'color'   => $isApproved ? 'green' : 'red',
            'link' => route('admin.hrm.my-leaves.index'),
        ];
    }
}