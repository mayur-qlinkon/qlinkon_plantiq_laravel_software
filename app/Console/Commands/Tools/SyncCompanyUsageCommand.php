<?php

namespace App\Console\Commands\Tools;

use App\Enums\UsageHealth;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Nightly usage rollup — the job behind "who do I call today?".
 *
 * Four aggregate queries for the whole platform, not four per tenant: the
 * activity log runs to hundreds of thousands of rows, and a per-company loop
 * would turn a fifty-tenant platform into two hundred scans of it.
 *
 * Three signals are merged, because no single one is trustworthy on its own:
 *
 *   activity_log  — the richest, but only covers models carrying LogsActivity.
 *                   A tenant living entirely in a module without the trait
 *                   would look dead.
 *   invoices      — real commercial work, logged or not.
 *   sessions      — catches someone who logs in daily and only reads. Reading
 *                   is still usage, and a read-only tenant is not a lapsed one.
 *
 * Writes go through DB::table rather than Eloquent. Company has no model
 * events today, but a plain UPDATE keeps this job from ever tripping one that
 * gets added later — and from writing to the very log it measures.
 *
 * Idempotent: every figure is recomputed from a rolling window, so a missed
 * night costs nothing and a double run changes nothing.
 */
class SyncCompanyUsageCommand extends Command
{
    protected $signature = 'companies:sync-usage
                            {--days=30 : Size of the rolling window}
                            {--dry-run : Report what would change without writing}';

    protected $description = 'Roll up per-company activity into the usage health columns';

    public function handle(): int
    {
        $days = max(7, (int) $this->option('days'));
        $isDryRun = (bool) $this->option('dry-run');
        $since = now()->subDays($days)->startOfDay();

        $this->info("Rolling up the last {$days} days".($isDryRun ? ' (dry run)' : ''));

        try {
            $daily = $this->dailyActivity($since);
            $lastSeen = $this->lastSeenPerCompany();
            $userCounts = $this->userCountsPerCompany();
        } catch (Throwable $e) {
            Log::error('[UsageSync] Aggregation failed', ['error' => $e->getMessage()]);
            $this->error('Could not read usage data: '.$e->getMessage());

            return self::FAILURE;
        }

        $companies = DB::table('companies')->whereNull('deleted_at')->pluck('id');
        $written = 0;

        foreach ($companies as $companyId) {
            $rows = $daily->get($companyId, collect());

            $snapshot = [
                'actions_30'      => (int) $rows->sum('actions'),
                'active_users_30' => (int) $rows->max('users') ?: 0,
                'total_users'     => (int) ($userCounts[$companyId] ?? 0),
                'bitmap_30'       => $this->buildBitmap($rows, $since, $days),
                'window_days'     => $days,
            ];

            $lastActiveAt = $lastSeen[$companyId] ?? null;

            $idleDays = $lastActiveAt
                ? (int) Carbon::parse($lastActiveAt)->startOfDay()->diffInDays(now()->startOfDay())
                : null;

            $health = UsageHealth::fromIdleDays($idleDays);

            if ($isDryRun) {
                $this->line(sprintf(
                    '  #%-4d %-14s idle=%-5s active_days=%d actions=%d',
                    $companyId,
                    $health->value,
                    $idleDays ?? '—',
                    count(array_filter($snapshot['bitmap_30'])),
                    $snapshot['actions_30'],
                ));

                continue;
            }

            DB::table('companies')->where('id', $companyId)->update([
                'last_active_at'    => $lastActiveAt,
                'usage_health'      => $health->value,
                'active_days_30'    => count(array_filter($snapshot['bitmap_30'])),
                'usage_snapshot'    => json_encode($snapshot),
                'usage_computed_at' => now(),
            ]);

            $written++;
        }

        $this->info($isDryRun ? 'Dry run complete.' : "Updated {$written} companies.");

        Log::info('[UsageSync] Rollup complete', [
            'companies' => $isDryRun ? 0 : $written,
            'days'      => $days,
        ]);

        return self::SUCCESS;
    }

    /**
     * Actions and distinct actors per company per day, inside the window.
     *
     * activity_log carries a causer, not a company, so users supplies the
     * tenant. Rows whose causer is gone — a deleted staff member — drop out,
     * which is correct: their work is still in the invoice tables, and those
     * feed last_active_at separately.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection>
     */
    private function dailyActivity(Carbon $since)
    {
        return DB::table('activity_log')
            ->join('users', 'users.id', '=', 'activity_log.causer_id')
            ->where('activity_log.causer_type', User::class)
            ->where('activity_log.created_at', '>=', $since)
            ->groupBy('users.company_id', 'day')
            ->selectRaw('users.company_id as company_id')
            ->selectRaw('DATE(activity_log.created_at) as day')
            ->selectRaw('COUNT(*) as actions')
            ->selectRaw('COUNT(DISTINCT activity_log.causer_id) as users')
            ->get()
            ->groupBy('company_id');
    }

    /**
     * The most recent sign of life per company, across all three signals.
     *
     * Not limited to the window: a tenant quiet for six months still has a
     * real last-active date, and "six months" is exactly what the platform
     * needs to see.
     *
     * @return array<int, string>
     */
    private function lastSeenPerCompany(): array
    {
        $latest = [];

        $merge = function (array $rows) use (&$latest): void {
            foreach ($rows as $companyId => $timestamp) {
                if (! $timestamp) {
                    continue;
                }

                if (! isset($latest[$companyId]) || $timestamp > $latest[$companyId]) {
                    $latest[$companyId] = $timestamp;
                }
            }
        };

        // Aggregates are aliased and plucked by that alias. Passing the raw
        // expression to pluck() instead makes Laravel read it as a column
        // name and take everything after the last dot — "created_at)", bracket
        // included — which fails at the point the row is read, not the query.
        $merge(
            DB::table('activity_log')
                ->join('users', 'users.id', '=', 'activity_log.causer_id')
                ->where('activity_log.causer_type', User::class)
                ->groupBy('users.company_id')
                ->selectRaw('users.company_id as company_id, MAX(activity_log.created_at) as last_at')
                ->pluck('last_at', 'company_id')
                ->all()
        );

        $merge(
            DB::table('invoices')
                ->groupBy('company_id')
                ->selectRaw('company_id, MAX(created_at) as last_at')
                ->pluck('last_at', 'company_id')
                ->all()
        );

        // last_activity is a unix timestamp, so it is converted before being
        // compared with the datetime strings above.
        $sessions = DB::table('sessions')
            ->join('users', 'users.id', '=', 'sessions.user_id')
            ->groupBy('users.company_id')
            ->selectRaw('users.company_id as company_id, MAX(sessions.last_activity) as last_unix')
            ->pluck('last_unix', 'company_id')
            ->all();

        // Same reason as Company::lastActiveAt() — without the timezone this
        // writes a UTC clock reading into a column every other query reads as
        // app-local, so a session-derived last_active_at lands hours in the
        // past and can drop a live tenant a health band.
        $merge(array_map(
            fn ($unix) => $unix
                ? Carbon::createFromTimestamp((int) $unix, config('app.timezone'))->toDateTimeString()
                : null,
            $sessions
        ));

        return $latest;
    }

    /**
     * Staff headcount per company. Customers are excluded — a storefront
     * shopper is not a seat and must not inflate adoption figures.
     *
     * @return array<int, int>
     */
    private function userCountsPerCompany(): array
    {
        return DB::table('users')
            ->whereNull('deleted_at')
            ->whereIn('user_type', ['company_admin', 'internal'])
            ->groupBy('company_id')
            ->selectRaw('company_id, COUNT(*) as total')
            ->pluck('total', 'company_id')
            ->all();
    }

    /**
     * One boolean per day in the window, oldest first, for the sparkline.
     *
     * Built by walking the calendar rather than the result rows, so quiet days
     * appear as gaps instead of silently collapsing the strip.
     *
     * @return list<bool>
     */
    private function buildBitmap($rows, Carbon $since, int $days): array
    {
        $daysWithActivity = $rows->pluck('day')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip();

        $bitmap = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $since->copy()->addDays($i)->toDateString();
            $bitmap[] = $daysWithActivity->has($date);
        }

        return $bitmap;
    }
}