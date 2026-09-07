<?php

namespace App\Console\Commands\Projects;

use App\Enums\Project\ClientServiceStatus;
use App\Models\Company;
use App\Models\Project\ProjectClientService;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Warns company admins about client services approaching their renewal date.
 *
 * One notification per service per billing period — never a daily drip. The
 * notification row IS the idempotency record: before sending, the same
 * client_service_id AND period_end are looked for in the admin's existing
 * notifications. That pairing matters — keying on the service alone would
 * silence every future period, and keying on the date alone would let a
 * second service on the same date be swallowed.
 *
 * No extra table is needed because the guarantee lives in the data already
 * being written.
 */
class NotifyExpiringServicesCommand extends Command
{
    protected $signature = 'projects:notify-expiring
                            {--days=7 : Warn this many days before the period ends}
                            {--company= : Limit the run to one company ID}';

    protected $description = 'Notify company admins about client services expiring soon';

    /** Marks these rows in the notifications table for the duplicate check. */
    private const NOTIFICATION_TYPE = 'service_expiry';

    public function handle(): int
    {
        $leadDays = max(0, (int) $this->option('days'));
        $today    = CarbonImmutable::today();
        $cutoff   = $today->addDays($leadDays);

        $companies = $this->targetCompanies();

        if ($companies->isEmpty()) {
            $this->warn('No active companies found. Nothing to do.');

            return self::SUCCESS;
        }

        $totalSent    = 0;
        $totalSkipped = 0;

        foreach ($companies as $company) {
            $admins = User::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->companyAdmins()
                ->get();

            if ($admins->isEmpty()) {
                continue;
            }

            $services = $this->expiringServices($company->id, $today, $cutoff);

            if ($services->isEmpty()) {
                continue;
            }

            $this->line("→ {$company->name} (#{$company->id}) — {$services->count()} expiring");

            foreach ($services as $service) {
                foreach ($admins as $admin) {
                    if ($this->alreadyNotified($admin, $service)) {
                        $totalSkipped++;

                        continue;
                    }

                    $this->send($admin, $service, $today)
                        ? $totalSent++
                        : $totalSkipped++;
                }
            }
        }

        $this->newLine();
        $this->info("Done. Sent: {$totalSent} | Skipped (already notified): {$totalSkipped}");

        return self::SUCCESS;
    }

    /**
     * Services whose period ends inside the warning window.
     *
     * Auto-renewing services are excluded on purpose: the nightly sync rolls
     * them forward by itself, so a warning would be noise about something
     * nobody has to act on.
     *
     * Cancelled and already-expired rows are excluded too — a warning about a
     * date that has already passed is a report, not an alert.
     */
    private function expiringServices(int $companyId, CarbonImmutable $today, CarbonImmutable $cutoff)
    {
        return ProjectClientService::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', ClientServiceStatus::Active->value)
            ->where('auto_renew', false)
            ->whereNotNull('current_period_end')
            ->whereBetween('current_period_end', [
                $today->toDateString(),
                $cutoff->toDateString(),
            ])
            ->with('client:id,name')
            ->orderBy('current_period_end')
            ->get();
    }

    /**
     * Has this admin already been told about THIS period of THIS service?
     *
     * Deliberately unbounded by date. Restricting to "today" would resend every
     * night for the whole window — seven identical alerts for one renewal.
     */
    private function alreadyNotified(User $admin, ProjectClientService $service): bool
    {
        return $admin->notifications()
            ->where('data->type', self::NOTIFICATION_TYPE)
            ->whereJsonContains('data->client_service_id', $service->id)
            ->whereJsonContains('data->period_end', $service->current_period_end?->toDateString())
            ->exists();
    }

    private function send(User $admin, ProjectClientService $service, CarbonImmutable $today): bool
    {
        $periodEnd = $service->current_period_end
            ? CarbonImmutable::parse($service->current_period_end)
            : null;

        $daysLeft   = $periodEnd ? (int) $today->diffInDays($periodEnd, false) : 0;
        $clientName = $service->client?->name ?? 'Unknown client';

        $when = match (true) {
            $daysLeft <= 0 => 'expires today',
            $daysLeft === 1 => 'expires tomorrow',
            default         => "expires in {$daysLeft} days",
        };

        try {
            $admin->sendSmartNotification(
                title: 'Service Renewal Due',
                message: "{$clientName} — \"{$service->name}\" {$when}.",
                link: route('admin.project_renewals.index', ['bucket' => 'week']),
                icon: 'calendar-clock',
                color: $daysLeft <= 1 ? 'red' : 'orange',
                type: self::NOTIFICATION_TYPE,
                extra: [
                    // These two together form the idempotency key. Changing
                    // either name breaks alreadyNotified() silently, so keep
                    // them in sync with that query.
                    'client_service_id' => $service->id,
                    'period_end'        => $periodEnd?->toDateString(),
                    'client_id'         => $service->client_id,
                    'project_id'        => $service->project_id,
                ],
            );

            return true;
        } catch (Throwable $e) {
            Log::warning('[ProjectExpiryNotify] Notification failed', [
                'client_service_id' => $service->id,
                'user_id'           => $admin->id,
                'error'             => $e->getMessage(),
            ]);

            return false;
        }
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