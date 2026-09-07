<?php

namespace App\Services\Platform;
use App\Models\User;

/**
 * DashboardResolver — decides a tenant's default ("home") dashboard route
 * based on the modules their active plan includes.
 *
 * Single source of truth for the "which dashboard does this company land on?"
 * question. Used by the main DashboardController as a guard: if the resolved
 * route isn't the main dashboard, it redirects there.
 *
 * Priority (highest first):
 *   1. Any SALES/finance module      → admin.dashboard      (the main one)
 *   2. HRM                           → admin.hrm.dashboard
 *   3. CRM                           → admin.crm.dashboard
 *   4. Fallback (storefront-only,
 *      inventory-only, etc.)         → admin.dashboard      (neutral default)
 *
 * Note: `inventory` and `reports` are intentionally NOT treated as sales
 * signals — they are shared by storefront / plant_education / other modules,
 * so on their own they don't imply the sales dashboard.
 *
 * Adding a new dashboard later = add one branch here. Nothing else changes.
 */
class DashboardResolver
{
    /** The main (sales/finance/inventory) dashboard route name. */
    public const MAIN = 'admin.dashboard';

    /** Where someone whose only job is being an employee should land. */
    public const EMPLOYEE = 'admin.employee.dashboard';

    /**
     * Decide where a user lands — after login AND on every app/PWA reopen
     * (see homeUrl() in helpers.php, wired to redirectUsersTo()).
     *
     * Anyone holding an HR profile lands on their own dashboard, company
     * admin included. Attendance is the single most frequent daily action
     * for every profile holder, so it must cost zero clicks; module work is
     * one sidebar tap away and is not done dozens of times a day.
     *
     * This governs landing only. /admin/dashboard always renders the
     * launcher and /admin/my-dashboard always renders the employee view,
     * so both sidebar links stay honest.
     */
    public function landingRouteName(User $user): string
    {
        return $user->employee !== null ? self::EMPLOYEE : self::MAIN;
    }

    /**
     * Modules that mean "this tenant does billing/sales" → main dashboard.
     * Any one of these being present is enough.
     */
    private const SALES_MODULES = [
        'invoicing',   // master sales umbrella (POS, invoices, quotations, expenses, challans, reports)
        'pos',
        'purchases',
        'challan',
        'expenses',
        'accounting',
    ];

    /**
     * Resolve the route name this company should land on.
     */
    public function resolveRouteName(): string
    {
        // Super admins (no company) always use the main dashboard.
        if (function_exists('is_super_admin') && is_super_admin()) {
            return self::MAIN;
        }

        // 1. Any sales/finance module → main dashboard.
        if (has_module(self::SALES_MODULES)) {
            return self::MAIN;
        }

        // 2. HRM takes priority over CRM (per business rule).
        if (has_module('hrm')) {
            return 'admin.hrm.dashboard';
        }

        // 3. CRM.
        if (has_module('crm')) {
            return 'admin.crm.dashboard';
        }

        // 4. Fallback — nothing module-specific matched; stay on the main one.
        return self::MAIN;
    }

    /**
     * Is the main dashboard the correct landing for this tenant?
     * When true, the main DashboardController renders normally (no redirect).
     */
    public function shouldUseMain(): bool
    {
        return $this->resolveRouteName() === self::MAIN;
    }

    /**
     * 'ControllerClass@method' to FORWARD to internally (not redirect) when
     * the resolved dashboard isn't the main one. Forwarding keeps the browser
     * URL at /admin/dashboard while rendering the correct dashboard's content.
     *
     * IMPORTANT: forwarding bypasses that route's own middleware (module:*,
     * permission:*) since we never actually hit that route. Each branch here
     * MUST re-check the same module/permission guards manually before
     * forwarding, or a user could see a dashboard they shouldn't have access to.
     */
    public function resolveForward(): ?string
    {
        $target = $this->resolveRouteName();

        if ($target === 'admin.hrm.dashboard') {
            if (! has_module('hrm') || ! has_permission('hrm_dashboard.view')) {
                return null; // not authorized — fall through to main dashboard
            }

            return \App\Http\Controllers\Admin\Hrm\HrmDashboardController::class . '@index';
        }

        if ($target === 'admin.crm.dashboard') {
            if (! has_module('crm') || ! has_permission('crm_dashboard.view')) {
                return null;
            }

            return \App\Http\Controllers\Admin\Crm\CrmDashboardController::class . '@index';
        }

        return null; // MAIN — render the main dashboard as normal
    }
}