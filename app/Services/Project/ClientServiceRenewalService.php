<?php

namespace App\Services\Project;

use App\Enums\Project\ChargeType;
use App\Enums\Project\ClientServiceStatus;
use App\Exceptions\Project\ProjectBillingException;
use App\Models\Project\ProjectCharge;
use App\Models\Project\ProjectClientService;
use App\Models\Project\ProjectService as ServiceCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sells, renews and expires client services.
 *
 * The central idea: a renewal is not a special kind of record, it is simply
 * the next charge on the same service. Renewing therefore never touches the
 * previous period — 2026, 2027 and 2028 sit side by side as three charges, and
 * a single payment can settle across all of them.
 *
 * The legacy renewService() overwrote invoice_price in place, which destroyed
 * every earlier period the moment a renewal happened. Nothing here mutates a
 * past charge.
 */
class ClientServiceRenewalService
{
    public function __construct(
        private readonly ProjectChargeService $charges,
    ) {}

    // ─────────────────────────────────────────────────────────
    // SELL
    // ─────────────────────────────────────────────────────────

    /**
     * Sell a service to a client.
     *
     * Everything billable is snapshotted off the catalog at this moment, so a
     * later price change in the catalog cannot rewrite what this client bought.
     *
     * Accepted keys:
     *   client_id*     int
     *   service_id     int|null   catalog entry to copy from
     *   project_id     int|null
     *   name           string     required when no service_id is given
     *   billing_cycle  string
     *   duration_days  int|null   only used when billing_cycle is custom
     *   price          float
     *   tax_rate       float
     *   started_at     date|null  defaults to today
     *   auto_renew     bool
     *   notes          string|null
     *   raise_charge   bool|null  default true — bill the first period now
     */
    /**
     * Resolve a money/rate field, honouring an explicit zero.
     *
     * The catalog value is only a starting suggestion. Once the caller has sent
     * the key at all — even as null — they have made a decision, and falling
     * back to the catalog would overrule them.
     */
    private function resolveAmount(array $data, string $key, float|string|null $catalogValue): float
    {
        if (array_key_exists($key, $data)) {
            return round((float) $data[$key], 2);
        }

        return round((float) ($catalogValue ?? 0), 2);
    }

    public function sell(array $data): ProjectClientService
    {
        return DB::transaction(function () use ($data) {
            $catalog = ! empty($data['service_id'])
                ? ServiceCatalog::find($data['service_id'])
                : null;

            $name = trim((string) ($data['name'] ?? $catalog?->name ?? ''));

            if ($name === '') {
                throw new ProjectBillingException('A service name is required.');
            }

            $cycle = $this->resolveCycle($data['billing_cycle'] ?? $catalog?->billing_cycle);

            $startedAt   = $this->toDate($data['started_at'] ?? null) ?? CarbonImmutable::today();
            $durationDays = $data['duration_days'] ?? $catalog?->duration_days;
            $periodEnd   = $cycle->periodEnd($startedAt, $durationDays ? (int) $durationDays : null);

            $clientService = ProjectClientService::create([
                'client_id'  => $data['client_id'],
                'project_id' => $data['project_id'] ?? null,
                'service_id' => $catalog?->id,
                'store_id'   => $data['store_id'] ?? optional(active_store())->id,

                // Snapshot — never re-read from the catalog after this point.
                'name'          => $name,
                'billing_cycle' => $cycle->value,
                'duration_days' => $durationDays,
                // ?? cannot tell "the caller said zero" from "the caller said
                // nothing" — both arrive as null once ConvertEmptyStringsToNull
                // has run. Clearing the GST field therefore fell back to the
                // catalog's 18% and silently re-added the tax the user removed.
                'price'         => $this->resolveAmount($data, 'price', $catalog?->price),
                'tax_rate'      => $this->resolveAmount($data, 'tax_rate', $catalog?->tax_rate),

                'status'               => ClientServiceStatus::Active->value,
                'started_at'           => $startedAt->toDateString(),
                'current_period_start' => $startedAt->toDateString(),
                'current_period_end'   => $periodEnd?->toDateString(),

                'auto_renew' => (bool) ($data['auto_renew'] ?? false),
                'notes'      => $data['notes'] ?? null,
            ]);

            if ($data['raise_charge'] ?? true) {
                $this->raiseCharge($clientService, $startedAt, $periodEnd, ChargeType::OneTime);
            }

            return $clientService->refresh();
        });
    }

    // ─────────────────────────────────────────────────────────
    // RENEW
    // ─────────────────────────────────────────────────────────

    /**
     * Roll a service into its next billing period and raise the charge for it.
     *
     * Accepted options:
     *   period_start  date|null   explicit start, required when periods were skipped
     *   price         float|null  override for this period only — an explicit
     *                             0 is honoured, an absent key inherits
     *   tax_rate      float|null  same rule as price
     *   due_date      date|null
     *   notes         string|null
     */
    public function renew(ProjectClientService $clientService, array $options = []): ProjectCharge
    {
        return DB::transaction(function () use ($clientService, $options) {
            $clientService = ProjectClientService::query()
                ->whereKey($clientService->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $clientService->isRenewable()) {
                throw new ProjectBillingException(sprintf(
                    '"%s" cannot be renewed — it is a %s service with status %s.',
                    $clientService->name,
                    $clientService->billing_cycle->label(),
                    $clientService->status->label()
                ));
            }

            $periodStart = $this->resolvePeriodStart($clientService, $options);

            $periodEnd = $clientService->billing_cycle->periodEnd(
                $periodStart,
                $clientService->duration_days
            );

            $charge = $this->raiseCharge(
                clientService: $clientService,
                periodStart: $periodStart,
                periodEnd: $periodEnd,
                type: ChargeType::Renewal,
                options: $options,
            );

            // The entitlement moves forward regardless of whether the charge
            // has been paid. Work, entitlement and money advance independently.
            $clientService->update([
                'current_period_start' => $periodStart->toDateString(),
                'current_period_end'   => $periodEnd?->toDateString(),
                'status'               => ClientServiceStatus::Active->value,
            ]);

            return $charge;
        });
    }

    /**
     * Where the next period begins.
     *
     * Normally the day after the current period ends. If that start is more
     * than one full period in the past, the client skipped one or more cycles
     * and the system refuses to guess: auto-generating those charges would bill
     * someone for months they never received (Scenario E). The admin must state
     * the period explicitly.
     */
    private function resolvePeriodStart(ProjectClientService $clientService, array $options): CarbonImmutable
    {
        $explicit = $this->toDate($options['period_start'] ?? null);

        if ($explicit) {
            return $explicit;
        }

        $next = $clientService->nextPeriodStart();

        if (! $next) {
            return CarbonImmutable::today();
        }

        $gapEnd = $clientService->billing_cycle->periodEnd($next, $clientService->duration_days);

        // The whole of the next period already lies in the past — at least one
        // cycle went unbilled.
        if ($gapEnd && $gapEnd->isBefore(CarbonImmutable::today())) {
            throw new ProjectBillingException(sprintf(
                '"%s" has unbilled periods since %s. Choose the period to bill, so skipped periods are not charged by mistake.',
                $clientService->name,
                $next->format('d M Y')
            ));
        }

        return $next;
    }

    // ─────────────────────────────────────────────────────────
    // LIFECYCLE
    // ─────────────────────────────────────────────────────────

    /**
     * Stop a service permanently.
     *
     * Deliberately leaves existing charges alone — cancelling an entitlement
     * does not erase what was already owed for periods already served. Forgive
     * those separately with a write-off if that is the intent.
     */
    public function cancel(ProjectClientService $clientService, string $reason): ProjectClientService
    {
        if (trim($reason) === '') {
            throw ProjectBillingException::missingReason();
        }

        $clientService->update([
            'status'        => ClientServiceStatus::Cancelled->value,
            'cancelled_at'  => CarbonImmutable::today()->toDateString(),
            'cancel_reason' => $reason,
            'auto_renew'    => false,
        ]);

        return $clientService;
    }

    // ─────────────────────────────────────────────────────────
    // SCHEDULED PASSES
    // ─────────────────────────────────────────────────────────

    /**
     * Marks active services whose period has ended as expired.
     *
     * Services set to auto-renew are skipped — generateAutoRenewals() handles
     * those, and flipping them to expired first would block the renewal.
     *
     * Intended for a daily scheduled command. Note this runs outside any
     * authenticated session, so the tenant global scope is inactive and the
     * caller must scope by company itself.
     */
    public function markExpired(?int $companyId = null): int
    {
        $query = ProjectClientService::query()
            ->withoutGlobalScope('tenant')
            ->where('status', ClientServiceStatus::Active->value)
            ->where('auto_renew', false)
            ->whereNotNull('current_period_end')
            ->whereDate('current_period_end', '<', CarbonImmutable::today()->toDateString());

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->update(['status' => ClientServiceStatus::Expired->value]);
    }

    /**
     * Raises the next charge for services that renew automatically.
     *
     * Each service is renewed in isolation: one failure — a skipped period, a
     * duplicate charge — must not abort the rest of the batch. Failures are
     * logged and reported back rather than thrown.
     *
     * @return array{renewed: Collection, failed: array<int, string>}
     */
    public function generateAutoRenewals(?int $companyId = null, int $leadDays = 0): array
    {
        $cutoff = CarbonImmutable::today()->addDays(max(0, $leadDays));

        $query = ProjectClientService::query()
            ->withoutGlobalScope('tenant')
            ->where('auto_renew', true)
            ->where('status', ClientServiceStatus::Active->value)
            ->whereNotNull('current_period_end')
            ->whereDate('current_period_end', '<=', $cutoff->toDateString());

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $renewed = collect();
        $failed  = [];

        foreach ($query->cursor() as $clientService) {
            try {
                $renewed->push($this->renew($clientService));
            } catch (Throwable $e) {
                $failed[$clientService->id] = $e->getMessage();

                Log::warning('[ProjectAutoRenew] Skipped client service', [
                    'client_service_id' => $clientService->id,
                    'company_id'        => $clientService->company_id,
                    'reason'            => $e->getMessage(),
                ]);
            }
        }

        return ['renewed' => $renewed, 'failed' => $failed];
    }

    // ─────────────────────────────────────────────────────────
    // INTERNALS
    // ─────────────────────────────────────────────────────────

    /**
     * Every charge this service produces goes through here, so the snapshot
     * fields are always taken from the client service and never from the
     * catalog.
     */
    private function raiseCharge(
        ProjectClientService $clientService,
        CarbonImmutable $periodStart,
        ?CarbonImmutable $periodEnd,
        ChargeType $type,
        array $options = [],
    ): ProjectCharge {
        return $this->charges->create([
            'company_id'        => $clientService->company_id,
            'client_id'         => $clientService->client_id,
            'client_service_id' => $clientService->id,
            'project_id'        => $clientService->project_id,
            'service_id'        => $clientService->service_id,
            'store_id'          => $clientService->store_id,

            'type'  => $type,
            'title' => $this->chargeTitle($clientService, $periodStart, $periodEnd),

            'period_start' => $periodStart->toDateString(),
            'period_end'   => $periodEnd?->toDateString(),

            'subtotal' => round((float) ($options['price'] ?? $clientService->price), 2),
            'tax_rate' => round((float) ($options['tax_rate'] ?? $clientService->tax_rate), 2),

            'charge_date' => $periodStart->isFuture()
                ? CarbonImmutable::today()->toDateString()
                : $periodStart->toDateString(),

            'due_date' => $options['due_date'] ?? null,
            'notes'    => $options['notes'] ?? null,
        ]);
    }

    /**
     * A readable, self-contained title. It must still make sense years later
     * on a statement, when the service row may be long gone.
     */
    private function chargeTitle(
        ProjectClientService $clientService,
        CarbonImmutable $periodStart,
        ?CarbonImmutable $periodEnd,
    ): string {
        if (! $periodEnd) {
            return $clientService->name;
        }

        return sprintf(
            '%s (%s to %s)',
            $clientService->name,
            $periodStart->format('d M Y'),
            $periodEnd->format('d M Y')
        );
    }

    private function resolveCycle(mixed $cycle): \App\Enums\Project\BillingCycle
    {
        if ($cycle instanceof \App\Enums\Project\BillingCycle) {
            return $cycle;
        }

        return \App\Enums\Project\BillingCycle::tryFrom((string) $cycle)
            ?? \App\Enums\Project\BillingCycle::OneTime;
    }

    
    private function toDate(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        return CarbonImmutable::parse($value)->startOfDay();
    }
}