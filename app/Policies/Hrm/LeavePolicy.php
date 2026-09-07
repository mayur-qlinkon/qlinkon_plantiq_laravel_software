<?php

namespace App\Policies\Hrm;

use App\Models\Hrm\Leave;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeavePolicy
{
    use HandlesAuthorization;

    /**
     * Owner / super-admin bypass.
     *
     * Self-approval is checked first and denies unconditionally. Without that
     * ordering the bypass below would let an owner sign off their own leave —
     * the one thing no role is allowed to do.
     */
    public function before(User $user, string $ability, $leave = null): ?bool
    {
        if (in_array($ability, ['approve', 'reject'], true)
            && $leave instanceof Leave
            && $this->isOwnLeave($user, $leave)) {
            return false;
        }

        if ($user->isCompanyAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('leaves.view');
    }

    public function view(User $user, Leave $leave): bool
    {
        if (! $user->hasPermissionTo('leaves.view')) {
            return false;
        }

        if ($user->hasPermissionTo('leaves.approve_all')) {
            return true;
        }

        // Own request, or one filed by a direct reportee.
        return $this->isOwnLeave($user, $leave) || $this->isDirectReportee($user, $leave);
    }

    public function approve(User $user, Leave $leave): bool
    {
        return $this->canActOn($user, $leave, 'leaves.approve');
    }

    public function reject(User $user, Leave $leave): bool
    {
        return $this->canActOn($user, $leave, 'leaves.reject');
    }

    public function cancel(User $user, Leave $leave): bool
    {
        if (! $user->hasPermissionTo('leaves.cancel')) {
            return false;
        }

        if ($user->hasPermissionTo('leaves.approve_all')) {
            return true;
        }

        // Unlike approve/reject, withdrawing your own request is legitimate.
        return $this->isOwnLeave($user, $leave) || $this->isDirectReportee($user, $leave);
    }

    /**
     * Permission plus hierarchy — both must hold.
     *
     * leaves.approve_all is the HR-level marker: company-wide authority.
     * Without it a holder of the verb permission reaches only their own direct
     * reportees. An employee whose reporting_to is null therefore falls to HR
     * automatically — null never matches a manager's employee id, so no
     * special case is needed for it.
     */
    protected function canActOn(User $user, Leave $leave, string $permission): bool
    {
        if (! $user->hasPermissionTo($permission)) {
            return false;
        }

        if ($user->hasPermissionTo('leaves.approve_all')) {
            return true;
        }

        return $this->isDirectReportee($user, $leave);
    }

    /**
     * Direct line only — deliberately not recursive. A manager's manager does
     * not inherit their reportees' requests.
     */
    protected function isDirectReportee(User $user, Leave $leave): bool
    {
        $myEmployeeId = $user->employee?->id;

        return $myEmployeeId !== null
            && $leave->employee?->reporting_to === $myEmployeeId;
    }

    protected function isOwnLeave(User $user, Leave $leave): bool
    {
        $myEmployeeId = $user->employee?->id;

        return $myEmployeeId !== null && $leave->employee_id === $myEmployeeId;
    }
}