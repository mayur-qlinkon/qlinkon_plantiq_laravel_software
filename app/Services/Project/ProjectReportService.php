<?php

namespace App\Services\Project;

use App\Enums\Project\AllocationKind;
use App\Enums\Project\BillingCycle;
use App\Enums\Project\ChargeStatus;
use App\Enums\Project\ClientServiceStatus;
use App\Enums\Project\ProjectStatus;
use App\Models\Payment;
use App\Models\Project\Project;
use App\Models\Project\ProjectCharge;
use App\Models\Project\ProjectChargeAllocation;
use App\Models\Project\ProjectClientService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-only reporting for the projects module.
 *
 * Every figure here is an aggregate computed in SQL rather than by loading
 * rows and summing in PHP. On shared hosting that difference decides whether
 * a dashboard opens in 200ms or times out once a tenant has a few thousand
 * charges.
 *
 * Nothing in this class writes. Any drift between the cached paid_amount on a
 * charge and its allocations is a bug to be fixed by a reconcile command, not
 * papered over here.
 */
class ProjectReportService
{
    public function __construct(
        private readonly ChargeAllocationService $allocations,
    ) {}

    /**
     * SQL expression for what is still owed on a charge.
     * Written once so no report can accidentally forget write-offs.
     */
    private const BALANCE_SQL = '(total_amount - paid_amount - written_off_amount)';

    // ─────────────────────────────────────────────────────────
    // DASHBOARD
    // ─────────────────────────────────────────────────────────

    /**
     * Everything the module's landing page needs, in a handful of queries.
     *
     * @param  array<int>|null  $storeIds  null means every store the tenant has
     */
    public function dashboard(?array $storeIds = null): array
    {
        $monthStart = CarbonImmutable::today()->startOfMonth();
        $monthEnd   = CarbonImmutable::today()->endOfMonth();

        return [
            'outstanding'        => $this->totalOutstanding($storeIds),
            'overdue'            => $this->totalOverdue($storeIds),
            'aging'              => $this->agingBuckets(storeIds: $storeIds),
            'billed_this_month'  => $this->billedBetween($monthStart, $monthEnd, $storeIds),

            // Billed and collected are shown side by side on purpose. They are
            // different businesses questions and a single "revenue" number
            // hides whichever one is going wrong.
            'collected_this_month' => $this->collectedBetween($monthStart, $monthEnd, $storeIds),

            'client_credit'         => $this->totalClientCredit(),
            'active_projects'       => $this->countProjects(ProjectStatus::Active, $storeIds),
            'on_hold_projects'      => $this->countProjects(ProjectStatus::OnHold, $storeIds),
            'renewals_due_30_days'  => $this->upcomingRenewals(30, $storeIds)->count(),
            'overdue_renewals'      => $this->overdueRenewals($storeIds)->count(),
            'completed_unpaid'      => $this->completedButUnpaidProjects($storeIds)->count(),
        ];
    }

    // ─────────────────────────────────────────────────────────
    // MONEY
    // ─────────────────────────────────────────────────────────

    public function totalOutstanding(?array $storeIds = null, ?int $clientId = null): float
    {
        return (float) $this->outstandingQuery($storeIds, $clientId)
            ->sum(DB::raw(self::BALANCE_SQL));
    }

    public function totalOverdue(?array $storeIds = null, ?int $clientId = null): float
    {
        return (float) $this->outstandingQuery($storeIds, $clientId)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', CarbonImmutable::today()->toDateString())
            ->sum(DB::raw(self::BALANCE_SQL));
    }

    /**
     * Receivables split by how long they have been overdue.
     *
     * This is the single most useful collection tool in the module and the one
     * thing the legacy schema could not produce at all, because it had no due
     * date anywhere.
     *
     * "current" covers anything not yet due, including charges with no due
     * date, so no money silently disappears from the totals.
     */
    public function agingBuckets(?int $clientId = null, ?array $storeIds = null): array
    {
        $today = CarbonImmutable::today()->toDateString();

        $row = $this->outstandingQuery($storeIds, $clientId)
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN due_date IS NULL OR due_date >= ? THEN '.self::BALANCE_SQL.' ELSE 0 END), 0) AS current,
                 COALESCE(SUM(CASE WHEN due_date < ? AND due_date >= DATE_SUB(?, INTERVAL 30 DAY) THEN '.self::BALANCE_SQL.' ELSE 0 END), 0) AS days_0_30,
                 COALESCE(SUM(CASE WHEN due_date < DATE_SUB(?, INTERVAL 30 DAY) AND due_date >= DATE_SUB(?, INTERVAL 60 DAY) THEN '.self::BALANCE_SQL.' ELSE 0 END), 0) AS days_31_60,
                 COALESCE(SUM(CASE WHEN due_date < DATE_SUB(?, INTERVAL 60 DAY) AND due_date >= DATE_SUB(?, INTERVAL 90 DAY) THEN '.self::BALANCE_SQL.' ELSE 0 END), 0) AS days_61_90,
                 COALESCE(SUM(CASE WHEN due_date < DATE_SUB(?, INTERVAL 90 DAY) THEN '.self::BALANCE_SQL.' ELSE 0 END), 0) AS days_90_plus',
                [$today, $today, $today, $today, $today, $today, $today, $today]
            )
            ->first();

        return [
            'current'    => (float) ($row->current ?? 0),
            'days_0_30'  => (float) ($row->days_0_30 ?? 0),
            'days_31_60' => (float) ($row->days_31_60 ?? 0),
            'days_61_90' => (float) ($row->days_61_90 ?? 0),
            'days_90_plus' => (float) ($row->days_90_plus ?? 0),
        ];
    }

    /** Value of charges raised in a period — what was billed, not collected. */
    public function billedBetween(CarbonImmutable $from, CarbonImmutable $to, ?array $storeIds = null): float
    {
        return (float) $this->chargeQuery($storeIds)
            ->where('status', '!=', ChargeStatus::Cancelled->value)
            ->whereBetween('charge_date', [$from->toDateString(), $to->toDateString()])
            ->sum('total_amount');
    }

    /**
     * Money actually applied to charges in a period.
     *
     * Counts allocations rather than payments, so an advance received in
     * December is reported in the month its charge was settled — and write-offs
     * are excluded, because forgiving a debt is not collecting it.
     */
    /**
     * Money actually collected in a period.
     *
     * Dated by the PAYMENT, not by when it happened to be allocated. An advance
     * received in December and applied to a May renewal was collected in
     * December — dating it by allocation would put it in May and the figure
     * would never reconcile against the bank.
     *
     * Write-offs are excluded: forgiving a debt is not collecting it.
     */
    public function collectedBetween(CarbonImmutable $from, CarbonImmutable $to, ?array $storeIds = null): float
    {
        $query = ProjectChargeAllocation::query()
            ->where('is_reversed', false)
            ->where('kind', '!=', AllocationKind::WriteOff->value)
            ->whereHas('payment', fn ($q) => $q
                ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()]));

        if ($storeIds !== null) {
            $query->whereIn(
                'charge_id',
                ProjectCharge::query()->whereIn('store_id', $storeIds)->select('id')
            );
        }

        return (float) $query->sum('amount');
    }

    /**
     * Advances and overpayments across all clients that have not been applied
     * to any charge.
     *
     * Computed in two aggregates rather than per client, so this stays a
     * constant-cost query as the client list grows.
     */
    public function totalClientCredit(): float
    {
        $received = (float) Payment::query()
            ->where('party_type', 'customer')
            ->where('type', 'received')
            ->where('status', 'completed')
            ->where('payment_for', 'project')
            ->sum('amount');

        $applied = (float) ProjectChargeAllocation::query()
            ->where('is_reversed', false)
            ->whereNotNull('payment_id')
            ->sum('amount');

        $refunded = (float) Payment::query()
            ->where('party_type', 'customer')
            ->where('type', 'sent')
            ->where('status', 'completed')
            ->where('payment_for', 'project_refund')
            ->sum('amount');

        return max(0.0, round($received - $applied - $refunded, 2));
    }

    /** Clients owing the most, largest first. */
    public function topOutstandingClients(int $limit = 10, ?array $storeIds = null): Collection
    {
        return $this->outstandingQuery($storeIds)
            ->selectRaw('client_id, SUM('.self::BALANCE_SQL.') AS outstanding, COUNT(*) AS charge_count')
            ->groupBy('client_id')
            ->havingRaw('outstanding > 0')
            ->orderByDesc('outstanding')
            ->limit($limit)
            ->with('client:id,name')
            ->get();
    }

    // ─────────────────────────────────────────────────────────
    // CLIENT VIEW
    // ─────────────────────────────────────────────────────────

    /** Complete financial picture for one client. */
    public function clientOutstanding(int $clientId, ?array $storeIds = null): array
    {
        return [
            'outstanding' => $this->totalOutstanding($storeIds, $clientId),
            'overdue'     => $this->totalOverdue($storeIds, $clientId),
            'credit'      => $this->allocations->clientCreditBalance($clientId),
            'aging'       => $this->agingBuckets($clientId, $storeIds),
            'open_charges' => $this->outstandingQuery($storeIds, $clientId)->count(),
        ];
    }

    // ─────────────────────────────────────────────────────────
    // RECURRING BUSINESS
    // ─────────────────────────────────────────────────────────

    /**
     * Annualised value of every active recurring service — the clearest single
     * measure of the business's health.
     *
     * One-time services are excluded because they do not recur. Custom cycles
     * are normalised through their duration in days.
     */
    public function annualRecurringValue(?array $storeIds = null): float
    {
        $query = ProjectClientService::query()
            ->where('status', ClientServiceStatus::Active->value)
            ->where('billing_cycle', '!=', BillingCycle::OneTime->value);

        if ($storeIds !== null) {
            $query->whereIn('store_id', $storeIds);
        }

        $total = 0.0;

        // Only the two columns needed, streamed — an active service list can be
        // long and none of it needs to be held in memory at once.
        foreach ($query->select('price', 'billing_cycle', 'duration_days')->cursor() as $service) {
            $months = $service->billing_cycle->months();

            if ($months) {
                $total += (float) $service->price * (12 / $months);
                continue;
            }

            if ($service->duration_days > 0) {
                $total += (float) $service->price * (365 / $service->duration_days);
            }
        }

        return round($total, 2);
    }

    /** Services lost in a period — cancelled or left to expire. */
    public function churn(CarbonImmutable $from, CarbonImmutable $to, ?array $storeIds = null): array
    {
        $base = fn () => ProjectClientService::query()
            ->when($storeIds !== null, fn (Builder $q) => $q->whereIn('store_id', $storeIds));

        $cancelled = $base()
            ->where('status', ClientServiceStatus::Cancelled->value)
            ->whereBetween('cancelled_at', [$from->toDateString(), $to->toDateString()])
            ->count();

        $expired = $base()
            ->where('status', ClientServiceStatus::Expired->value)
            ->whereBetween('current_period_end', [$from->toDateString(), $to->toDateString()])
            ->count();

        return [
            'cancelled' => $cancelled,
            'expired'   => $expired,
            'total'     => $cancelled + $expired,
        ];
    }

    public function upcomingRenewals(int $days = 30, ?array $storeIds = null): Collection
    {
        return ProjectClientService::query()
            ->expiringWithin($days)
            ->when($storeIds !== null, fn (Builder $q) => $q->whereIn('store_id', $storeIds))
            ->with('client:id,name')
            ->orderBy('current_period_end')
            ->get();
    }

    public function overdueRenewals(?array $storeIds = null): Collection
    {
        return ProjectClientService::query()
            ->overdueRenewal()
            ->when($storeIds !== null, fn (Builder $q) => $q->whereIn('store_id', $storeIds))
            ->with('client:id,name')
            ->orderBy('current_period_end')
            ->get();
    }

    // ─────────────────────────────────────────────────────────
    // WORK VS MONEY
    // ─────────────────────────────────────────────────────────

    /**
     * Delivered projects that still owe money — the most actionable follow-up
     * list in the module, and impossible to produce in the legacy schema where
     * a paid project was automatically marked done.
     */
    public function completedButUnpaidProjects(?array $storeIds = null): Collection
    {
        return Project::query()
            ->completedButUnpaid()
            ->when($storeIds !== null, fn (Builder $q) => $q->whereIn('store_id', $storeIds))
            ->withChargeTotals()
            ->with('client:id,name')
            ->orderByDesc('completed_at')
            ->get();
    }

    public function countProjects(ProjectStatus $status, ?array $storeIds = null): int
    {
        return Project::query()
            ->where('status', $status->value)
            ->when($storeIds !== null, fn (Builder $q) => $q->whereIn('store_id', $storeIds))
            ->count();
    }

    // ─────────────────────────────────────────────────────────
    // INTERNALS
    // ─────────────────────────────────────────────────────────

    private function chargeQuery(?array $storeIds = null, ?int $clientId = null): Builder
    {
        return ProjectCharge::query()
            ->when($storeIds !== null, fn (Builder $q) => $q->whereIn('store_id', $storeIds))
            ->when($clientId !== null, fn (Builder $q) => $q->where('client_id', $clientId));
    }

    /** Charges that still contribute to receivables. */
    private function outstandingQuery(?array $storeIds = null, ?int $clientId = null): Builder
    {
        return $this->chargeQuery($storeIds, $clientId)
            ->whereIn('status', ChargeStatus::outstandingValues());
    }
}