<?php

namespace App\Policies\Hrm;

use App\Models\Hrm\SalarySlip;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SalarySlipPolicy
{
    use HandlesAuthorization;

    /**
     * Actions nobody may perform on their own payslip, owners included.
     * Viewing and downloading your own slip is fine — signing it off is not.
     */
    protected const SELF_BLOCKED = ['update', 'approve', 'markPaid', 'delete'];

    /**
     * Checked before the owner bypass on purpose: otherwise an owner could
     * approve and pay their own payslip.
     */
    public function before(User $user, string $ability, $slip = null): ?bool
    {
        if (in_array($ability, self::SELF_BLOCKED, true)
            && $slip instanceof SalarySlip
            && $this->isOwnSlip($user, $slip)) {
            return false;
        }

        if ($user->isCompanyAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('salary_slips.view');
    }

    /**
     * Payroll is HR/finance territory, so there is deliberately no reporting
     * hierarchy here — unlike leave, a manager does not see a reportee's pay.
     * An employee reads their own slip through the my-salary-slips page, which
     * is scoped separately.
     */
    public function view(User $user, SalarySlip $slip): bool
    {
        return $user->hasPermissionTo('salary_slips.view') || $this->isOwnSlip($user, $slip);
    }

    public function downloadPdf(User $user, SalarySlip $slip): bool
    {
        return $user->hasPermissionTo('salary_slips.download_pdf') || $this->isOwnSlip($user, $slip);
    }

    public function generate(User $user): bool
    {
        return $user->hasPermissionTo('salary_slips.generate');
    }

    public function update(User $user, SalarySlip $slip): bool
    {
        return $user->hasPermissionTo('salary_slips.edit');
    }

    public function approve(User $user, SalarySlip $slip): bool
    {
        return $user->hasPermissionTo('salary_slips.approve');
    }

    public function markPaid(User $user, SalarySlip $slip): bool
    {
        return $user->hasPermissionTo('salary_slips.mark_paid');
    }

    public function delete(User $user, SalarySlip $slip): bool
    {
        return $user->hasPermissionTo('salary_slips.delete');
    }

    protected function isOwnSlip(User $user, SalarySlip $slip): bool
    {
        $myEmployeeId = $user->employee?->id;

        return $myEmployeeId !== null && $slip->employee_id === $myEmployeeId;
    }
}