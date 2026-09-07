<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Inventory\LowStockNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Daily low-stock digest, one pass per company.
 *
 * STORE SCOPING, DELIBERATELY ABSENT
 * The admin report and the dashboard widget both narrow their low-stock lists
 * with active_store_ids(), which reads the session. There is no session in
 * cron, so this command works company-wide across every warehouse instead.
 *
 * For a single-store tenant the two agree exactly. For a multi-store tenant
 * the alert can name a SKU that the reader's currently-selected store does not
 * show as low. That is a known, accepted difference — going per-store would
 * mean deciding which store each recipient belongs to, which the notification
 * preferences do not model.
 */
class NotifyLowStockCommand extends Command
{
    protected $signature = 'inventory:notify-low-stock
                            {--company= : Limit the run to one company ID}
                            {--dry-run : Report what would be sent without notifying or writing flags}';

    protected $description = 'Notify inventory staff about products that have dropped to their stock alert level';

    public function handle(LowStockNotificationService $lowStockService): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no notifications sent, no flags written.');
        }

        $companies = $this->targetCompanies();

        if ($companies->isEmpty()) {
            $this->warn('No active companies found. Nothing to do.');

            return self::SUCCESS;
        }

        $totalNotified  = 0;
        $totalRecovered = 0;

        foreach ($companies as $company) {
            try {
                $result = $lowStockService->runForCompany($company, $dryRun);
            } catch (Throwable $e) {
                // One tenant's bad data must not stop the rest of the run.
                Log::error('[LowStock] Company scan failed', [
                    'company_id' => $company->id,
                    'error'      => $e->getMessage(),
                ]);

                $this->error("  {$company->name}: failed — {$e->getMessage()}");

                continue;
            }

            $totalNotified  += $result['notified'];
            $totalRecovered += $result['recovered'];

            // Only report companies where something actually happened, so the
            // cron output stays readable once there are a few hundred tenants.
            if ($result['notified'] > 0 || $result['recovered'] > 0) {
                $this->line(sprintf(
                    '  %s — low: %d, newly notified: %d, recovered: %d',
                    $company->name,
                    $result['low'],
                    $result['notified'],
                    $result['recovered'],
                ));
            }
        }

        $this->info("Done. Companies scanned: {$companies->count()}, notified: {$totalNotified}, recovered: {$totalRecovered}");

        return self::SUCCESS;
    }

    private function targetCompanies()
    {
        $query = Company::query()->where('is_active', true);

        if ($companyId = $this->option('company')) {
            $query->whereKey((int) $companyId);
        }

        return $query->get(['id', 'name']);
    }
}