<?php

namespace App\Policies\Hrm;

use App\Models\Hrm\Announcement;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AnnouncementPolicy
{
    use HandlesAuthorization;

    /**
     * Owner / super-admin bypass — unrestricted access to everything.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isCompanyAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['announcements.view', 'announcements.create']);
    }

    public function view(User $user, Announcement $announcement): bool
    {
        return $user->hasAnyPermission(['announcements.view', 'announcements.create']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('announcements.create');
    }

    public function update(User $user, Announcement $announcement): bool
    {
        if (! $user->hasPermissionTo('announcements.view')) {
            return false;
        }

        // Published announcements need extra permission to edit
        if ($announcement->status === Announcement::STATUS_PUBLISHED) {
            return $user->hasPermissionTo('announcements.update');
        }

        return true; // draft / scheduled — freely editable
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        if (! $user->hasPermissionTo('announcements.view')) {
            return false;
        }

        if ($announcement->status === Announcement::STATUS_PUBLISHED) {
            return $user->hasPermissionTo('announcements.delete');
        }

        return true;
    }

    public function restore(User $user, Announcement $announcement): bool
    {
        return $user->hasPermissionTo('announcements.create');
    }

    public function forceDelete(User $user, Announcement $announcement): bool
    {
        return $user->hasPermissionTo('announcements.delete');
    }

    public function publish(User $user, Announcement $announcement): bool
    {
        return $user->hasPermissionTo('announcements.publish')
            && in_array($announcement->status, [
                Announcement::STATUS_DRAFT,
                Announcement::STATUS_SCHEDULED,
            ]);
    }

    public function unpublish(User $user, Announcement $announcement): bool
    {
        return $user->hasPermissionTo('announcements.publish')
            && $announcement->status === Announcement::STATUS_PUBLISHED;
    }

    public function duplicate(User $user, Announcement $announcement): bool
    {
        return $user->hasPermissionTo('announcements.duplicate');
    }
}
