<?php

namespace App\Console\Commands\Projects;

use App\Models\Company;
use App\Models\Setting;
use App\Services\Project\ClientServiceRenewalService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Nightly renewal sync — the job that keeps the renewals board honest.
 *
 * Two passes per company, and the order is not interchangeable:
 *
 *   1. Auto-renewals first. markExpired() deliberately skips rows with
 *      auto_renew = true, so anything that fails to renew here stays Active
 *      with a period end in the past and is never picked up again. That is a
 *      silent blind spot, which is why failures are counted and surfaced
 *      rather than only logged.
 *
 *   2. Mark expired second, for everything that does not auto-renew.
 *
 * Both passes are idempotent: markExpired() is a plain date query, and
 * generateAutoRenewals() is protected by assertPeriodNotAlreadyBilled(). A
 * missed night therefore costs nothing — the next run catches up.
 */
class SyncServiceRenewalsCommand extends Command
{
    protected $signature = 'projects:sync-renewals
                            {--company= : Limit the run to one company ID}
                            {--lead-days=0 : Renew this many days before the period actually ends}
                            {--dry-run : Report what would change without writing anything}';

    protected $description = 'Roll auto-renewing client services into their next period and expire the rest';

    /** Setting key holding the last successful run, read by the renewals board. */
    public const LAST_SYNC_KEY = 'project_renewals_last_synced_at';

    public function handle(ClientServiceRenewalService $renewals): int
    {
        $leadDays = max(0, (int) $this->option('lead-days'));
        $isDryRun = (bool) $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN — no changes will be saved.');
        }

        $companies = $this->targetCompanies();

        if ($companies->isEmpty()) {
            $this->warn('No active companies found. Nothing to do.');

            return self::SUCCESS;
        }

        $totalRenewed = 0;
        $totalExpired = 0;
        $totalFailed  = 0;

        foreach ($companies as $company) {
            $this->line("→ {$company->name} (#{$company->id})");

            try {
                [$renewedCount, $expiredCount, $failures] = $this->syncCompany(
                    $renewals,
                    $company->id,
                    $leadDays,
                    $isDryRun
                );

                $totalRenewed += $renewedCount;
                $totalExpired += $expiredCount;
                $totalFailed  += count($failures);

                $this->reportCompany($renewedCount, $expiredCount, $failures);
            } catch (Throwable $e) {
                // One tenant's bad data must never stop the rest of the batch.
                $totalFailed++;

                $this->error("   Aborted: {$e->getMessage()}");

                Log::error('[ProjectRenewalSync] Company sync failed', [
                    'company_id' => $company->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Done. Renewed: {$totalRenewed} | Expired: {$totalExpired} | Failed: {$totalFailed}");

        return self::SUCCESS;
    }

    /**
     * Run both passes for one tenant.
     *
     * @return array{0: int, 1: int, 2: array<int, string>}
     */
    private function syncCompany(
        ClientServiceRenewalService $renewals,
        int $companyId,
        int $leadDays,
        bool $isDryRun,
    ): array {
        if ($isDryRun) {
            return [0, 0, []];
        }

        // Pass 1 — auto-renewals. Returns its own failures instead of throwing,
        // so a single skipped-period guard cannot abort the tenant.
        $result = $renewals->generateAutoRenewals($companyId, $leadDays);

        $renewedCount = $result['renewed']->count();
        $failures     = $result['failed'];

        // Pass 2 — expire what nobody is renewing.
        $expiredCount = $renewals->markExpired($companyId);

        $this->recordLastSync($companyId);

        return [$renewedCount, $expiredCount, $failures];
    }

    private function reportCompany(int $renewed, int $expired, array $failures): void
    {
        $this->line("   Renewed: {$renewed} | Expired: {$expired}");

        if (empty($failures)) {
            return;
        }

        // These are the rows that will otherwise sit Active with a past date
        // forever. Printed in full so a cron email is actionable on its own.
        $this->warn('   '.count($failures).' auto-renewal(s) need manual attention:');

        foreach ($failures as $clientServiceId => $reason) {
            $this->warn("     #{$clientServiceId}: {$reason}");
        }
    }

    /**
     * Stamp the successful run so the board can show "Last synced ...".
     *
     * Without this a failed cron is invisible: the board keeps rendering
     * yesterday's truth and looks perfectly healthy.
     *
     * Written at company level (store_id = 0 sentinel) because the sync is not
     * store-specific.
     */
    private function recordLastSync(int $companyId): void
    {
        Setting::updateOrCreate(
            [
                'company_id' => $companyId,
                'store_id'   => Setting::COMPANY_LEVEL,
                'key'        => self::LAST_SYNC_KEY,
            ],
            [
                'value' => CarbonImmutable::now()->toDateTimeString(),
                'group' => 'projects',
                'type'  => 'string',
            ]
        );

        // The settings map is cached for 24 hours; without this the board would
        // keep reading the previous night's timestamp.
        forget_settings_cache($companyId);
    }

    /**
     * Cron runs without Auth, so the Tenantable global scope is inactive and
     * every company must be selected explicitly.
     */
    private function targetCompanies()
    {
        $query = Company::query()->where('is_active', true);

        if ($companyId = $this->option('company')) {
            $query->whereKey((int) $companyId);
        }

        return $query->get(['id', 'name']);
    }
}