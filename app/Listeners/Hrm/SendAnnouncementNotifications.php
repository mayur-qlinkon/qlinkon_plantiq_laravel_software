<?php

namespace App\Listeners\Hrm;

use App\Events\Hrm\AnnouncementPublished;
use App\Models\User;
use App\Notifications\Hrm\AnnouncementNotification;
use Illuminate\Support\Facades\Notification;

class SendAnnouncementNotifications
{
    public function handle(AnnouncementPublished $event): void
    {
        $announcement = $event->announcement;

        // 1. Get all users in the company
        // 2. Filter them using the same scope logic your model uses for the feed
        $users = User::where('company_id', $announcement->company_id)
            ->internal()
            ->where('id', '!=', $announcement->created_by) // Don't notify the creator
            ->get()
            ->filter(function ($user) use ($announcement) {
                // Use a manual check matching the 'forAudience' scope logic
                if ($announcement->target_audience === 'all') {
                    return true;
                }

                $targetIds = $announcement->target_ids ?? [];

                return match ($announcement->target_audience) {
                    'department' => in_array($user->employee?->hrm_department_id, $targetIds),
                    'designation' => in_array($user->employee?->hrm_designation_id, $targetIds),
                    'role' => $user->roles->whereIn('id', $targetIds)->isNotEmpty(),
                    'user' => in_array($user->id, $targetIds),
                    default => false,
                };
            });

        if ($users->isNotEmpty()) {
            Notification::send($users, new AnnouncementNotification($announcement));
        }
    }
}
