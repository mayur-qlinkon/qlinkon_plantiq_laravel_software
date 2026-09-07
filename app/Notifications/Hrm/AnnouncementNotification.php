<?php

namespace App\Notifications\Hrm;

use App\Models\Hrm\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AnnouncementNotification extends Notification
{
    use Queueable;

    public function __construct(protected Announcement $announcement) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'New Announcement',
            'message' => $this->announcement->title,
            'link' => route('admin.employee.dashboard'), // Redirect to their feed
            'icon' => 'megaphone',
            'color' => 'purple',
            'type' => 'announcement',
            'announcement_id' => $this->announcement->id,
            'priority' => $this->announcement->priority,
        ];
    }
}
