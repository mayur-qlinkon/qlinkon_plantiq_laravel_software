<?php

namespace App\Policies\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\WorkLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkLogPolicy
{
    use HandlesAuthorization;

    /**
     * Owner / super-admin bypass.
     *
     * Self-approval is checked first and denies unconditionally. Without that
     * ordering the bypass below would let an owner sign off their own work
     * log — the one thing no role is allowed to do. Mirrors LeavePolicy.
     */
    public function before(User $user, string $ability, $workLog = null): ?bool
    {
        if ($ability === 'approve'
            && $workLog instanceof WorkLog
            && $this->isOwnLog($user, $workLog)) {
            return false;
        }

        if ($user->isCompanyAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('work_logs.view');
    }

    public function view(User $user, WorkLog $workLog): bool
    {
        if (! $user->hasPermissionTo('work_logs.view')) {
            return false;
        }

        if ($user->hasPermissionTo('work_logs.approve_all')) {
            return true;
        }

        // Own log, or one filed by a direct reportee.
        return $this->isOwnLog($user, $workLog) || $this->isDirectReportee($user, $workLog);
    }

    /**
     * Filing a log on someone's behalf follows the same hierarchy as approving.
     *
     * The target arrives as an id because there is no model instance yet.
     * Laravel strips the leading class name from the argument array, so
     * authorize('create', [WorkLog::class, $id]) lands here as $employeeId.
     */
    public function create(User $user, ?int $employeeId = null): bool
    {
        if (! $user->hasPermissionTo('work_logs.view')) {
            return false;
        }

        if ($user->hasPermissionTo('work_logs.approve_all')) {
            return true;
        }

        $myEmployeeId = $user->employee?->id;

        if ($myEmployeeId === null || $employeeId === null) {
            return false;
        }

        return $employeeId === $myEmployeeId
            || Employee::where('id', $employeeId)
                ->where('reporting_to', $myEmployeeId)
                ->exists();
    }

    public function update(User $user, WorkLog $workLog): bool
    {
        return $this->view($user, $workLog);
    }

    public function delete(User $user, WorkLog $workLog): bool
    {
        return $this->view($user, $workLog);
    }

    /**
     * Permission plus hierarchy — both must hold.
     *
     * work_logs.approve_all is the HR-level marker: company-wide authority.
     * Without it a holder of the verb permission reaches only their own direct
     * reportees. An employee whose reporting_to is null therefore falls to HR
     * automatically — null never matches a manager's employee id.
     */
    public function approve(User $user, WorkLog $workLog): bool
    {
        if (! $user->hasPermissionTo('work_logs.approve')) {
            return false;
        }

        if ($user->hasPermissionTo('work_logs.approve_all')) {
            return true;
        }

        return $this->isDirectReportee($user, $workLog);
    }

    /**
     * Direct line only — deliberately not recursive. A manager's manager does
     * not inherit their reportees' logs.
     */
    protected function isDirectReportee(User $user, WorkLog $workLog): bool
    {
        $myEmployeeId = $user->employee?->id;

        return $myEmployeeId !== null
            && $workLog->employee?->reporting_to === $myEmployeeId;
    }

    protected function isOwnLog(User $user, WorkLog $workLog): bool
    {
        $myEmployeeId = $user->employee?->id;

        return $myEmployeeId !== null && $workLog->employee_id === $myEmployeeId;
    }
}