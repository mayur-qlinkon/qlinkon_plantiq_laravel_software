<?php

namespace App\Http\Controllers\Admin\Project;

use App\Console\Commands\Projects\SyncServiceRenewalsCommand;
use App\Enums\Project\BillingCycle;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Project\ProjectClientService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Renewal board — a single screen answering "what needs renewing, and when?".
 *
 * Reads project_client_services only. Renewals are not a separate table: a
 * service's current_period_end IS its renewal date, and its charges are its
 * renewal history. Adding a renewals table would give the same fact two homes.
 *
 * Renewing itself is not handled here. That stays on
 * ProjectClientServiceController@renew so the skipped-period guard, the charge
 * it raises and its permission all live in one place.
 */
class ProjectRenewalController extends Controller
{
    /** Anything outside this list falls back to 'week'. */
    private const BUCKETS = ['overdue', 'today', 'week', 'month', 'active', 'expired', 'cancelled', 'all'];

    public function index(Request $request)
    {
        $bucket = in_array($request->input('bucket'), self::BUCKETS, true)
            ? $request->input('bucket')
            : 'week';

        $counts = $this->bucketCounts($request);

        $renewals = $this->filtered($request)
            ->with('client:id,name,phone', 'project:id,title', 'service:id,name')
            ->renewalBucket($bucket)
            // Soonest first. On the overdue tab that puts the longest-lapsed
            // service at the top, which is the one most likely to be lost.
            ->orderByRaw('current_period_end IS NULL, current_period_end ASC')
            ->paginate(50)
            ->withQueryString();

            $clients = Client::select('id', 'name', 'phone')->orderBy('name')->get();
            $cycles  = BillingCycle::options();
            $today   = CarbonImmutable::today();

        [$lastSyncedAt, $syncIsStale] = $this->syncHealth();

        return view(
            'admin.projects.renewals.index',
            compact('renewals', 'counts', 'bucket', 'clients', 'cycles', 'today', 'lastSyncedAt', 'syncIsStale')
        );
    }

    /**
     * When the nightly sync last completed, and whether that is worrying.
     *
     * Every number on this board is only as fresh as that run. Without this the
     * page has no way to admit it is stale: a cron that died silently leaves
     * yesterday's counts rendering perfectly, and nobody finds out until a
     * client calls about a renewal that was never followed up.
     *
     * @return array{0: ?CarbonImmutable, 1: bool}
     */
    private function syncHealth(): array
    {
        $raw = get_setting(SyncServiceRenewalsCommand::LAST_SYNC_KEY);

        if (! $raw) {
            // Never run at all — on a fresh install, or the schedule was never
            // wired up. Treated as stale so it cannot be mistaken for healthy.
            return [null, true];
        }

        $lastSyncedAt = CarbonImmutable::parse($raw);

        // The command runs daily. 26 hours allows for a late or slow run
        // without crying wolf, while still catching a genuinely missed night.
        return [$lastSyncedAt, $lastSyncedAt->lessThan(CarbonImmutable::now()->subHours(26))];
    }
    

    /**
     * Filters that apply to BOTH the list and the tab counts.
     *
     * The bucket is deliberately excluded — a count that already had the bucket
     * applied would show the selected tab's total on every tab.
     */
    private function filtered(Request $request): Builder
    {
        $query = ProjectClientService::query()->forActiveStore();

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('billing_cycle')) {
            $query->where('billing_cycle', $request->billing_cycle);
        }

        if ($request->filled('search')) {
            $term = trim($request->search);

            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhereHas('client', fn (Builder $c) => $c
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%"));
            });
        }

        return $query;
    }

    /**
     * One grouped query for every tab instead of eight COUNT round trips.
     *
     * toBase() is used so the aggregate is not hydrated into models, but it
     * still applies the tenant global scope first — dropping to DB::table()
     * here would leak other companies' counts.
     *
     * @return array<string,int>
     */
    private function bucketCounts(Request $request): array
    {
        $today = CarbonImmutable::today();

        $t     = $today->toDateString();
        $week  = $today->addDays(7)->toDateString();
        $month = $today->addDays(30)->toDateString();

        $row = $this->filtered($request)->toBase()->selectRaw("
            SUM(CASE WHEN status IN ('active','expired') AND current_period_end IS NOT NULL AND current_period_end < ? THEN 1 ELSE 0 END) AS overdue_count,
            SUM(CASE WHEN status IN ('active','expired') AND current_period_end = ? THEN 1 ELSE 0 END) AS today_count,
            SUM(CASE WHEN status IN ('active','expired') AND current_period_end BETWEEN ? AND ? THEN 1 ELSE 0 END) AS week_count,
            SUM(CASE WHEN status IN ('active','expired') AND current_period_end BETWEEN ? AND ? THEN 1 ELSE 0 END) AS month_count,
            SUM(CASE WHEN status = 'active' AND current_period_end >= ? THEN 1 ELSE 0 END) AS active_count,
            SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) AS expired_count,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count,
            COUNT(*) AS all_count
        ", [$t, $t, $t, $week, $t, $month, $t])->first();

        return [
            'overdue'   => (int) ($row->overdue_count ?? 0),
            'today'     => (int) ($row->today_count ?? 0),
            'week'      => (int) ($row->week_count ?? 0),
            'month'     => (int) ($row->month_count ?? 0),
            'active'    => (int) ($row->active_count ?? 0),
            'expired'   => (int) ($row->expired_count ?? 0),
            'cancelled' => (int) ($row->cancelled_count ?? 0),
            'all'       => (int) ($row->all_count ?? 0),
        ];
    }
}