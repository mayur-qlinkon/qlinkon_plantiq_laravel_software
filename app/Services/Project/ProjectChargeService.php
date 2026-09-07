<?php

namespace App\Services\Project;

use App\Enums\Project\ChargeStatus;
use App\Enums\Project\ChargeType;
use App\Exceptions\Project\ProjectBillingException;
use App\Models\Project\ProjectCharge;
use App\Models\Project\ProjectClientService;
use App\Models\Project\ProjectService as ServiceCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


/**
 * Creates and cancels charges — the only sanctioned way a billing obligation
 * enters the system.
 *
 * Everything this class writes is frozen the moment it is saved (see the
 * immutability guard on ProjectCharge). That is deliberate: a charge is a
 * record of what was agreed at a point in time, and editing one silently
 * invalidates every allocation already made against it.
 */
class ProjectChargeService
{
    public function __construct(
        private readonly ChargeAllocationService $allocations,
    ) {}

    /** Fallback when no due-date setting is configured for the tenant. */
    private const DEFAULT_DUE_DAYS = 15;

    // ─────────────────────────────────────────────────────────
    // CREATE
    // ─────────────────────────────────────────────────────────

    /**
     * Raise a new charge.
     *
     * Accepted keys:
     *   client_id*        int
     *   project_id        int|null
     *   client_service_id int|null
     *   service_id        int|null    catalog reference, for reporting only
     *   type              ChargeType|string
     *   title             string      falls back to the service/project name
     *   description       string|null
     *   charge_date       date|null   defaults to today
     *   due_date          date|null   defaults to charge_date + tenant setting
     *   period_start      date|null
     *   period_end        date|null
     *   subtotal*         float
     *   discount_amount   float
     *   tax_rate          float
     *   store_id          int|null    inherited from the parent when omitted
     *   reference         string|null
     *   notes             string|null
     *   apply_credit      bool|null   overrides the tenant default
     */
    public function create(array $data): ProjectCharge
    {
        return DB::transaction(function () use ($data) {
            $clientId = (int) ($data['client_id'] ?? 0);

            if ($clientId <= 0) {
                throw new ProjectBillingException('A charge must belong to a client.');
            }

            $chargeDate = $this->toDate($data['charge_date'] ?? null) ?? CarbonImmutable::today();
            $dueDate    = $this->resolveDueDate($chargeDate, $data['due_date'] ?? null);

            $clientService = $this->resolveClientService($data);

            $amounts = $this->calculateAmounts(
                subtotal: (float) ($data['subtotal'] ?? 0),
                discount: (float) ($data['discount_amount'] ?? 0),
                taxRate: (float) ($data['tax_rate'] ?? ($clientService?->tax_rate ?? 0)),
            );

            $periodStart = $this->toDate($data['period_start'] ?? null);
            $periodEnd   = $this->toDate($data['period_end'] ?? null);

            // Guards against a double-clicked renew button creating two
            // identical billing periods for the same service.
            if ($clientService && $periodStart) {
                $this->assertPeriodNotAlreadyBilled($clientService, $periodStart);
            }

            $charge = ProjectCharge::create([
                // Set explicitly rather than relying on the Tenantable trait:
                // that only auto-fills when a user is authenticated, so any
                // scheduled/CLI path (auto-renewals) would insert a null.
                'company_id'        => $data['company_id'] ?? $clientService?->company_id ?? Auth::user()?->company_id,
                'client_id'         => $clientId,
                'store_id'          => $this->resolveStoreId($data, $clientService),
                'project_id'        => $data['project_id'] ?? $clientService?->project_id,
                'client_service_id' => $clientService?->id,
                'service_id'        => $data['service_id'] ?? $clientService?->service_id,

                'type'  => $this->resolveType($data['type'] ?? null),
                'title' => $this->resolveTitle($data, $clientService),

                'description' => $data['description'] ?? null,

                'charge_date'  => $chargeDate->toDateString(),
                'due_date'     => $dueDate?->toDateString(),
                'period_start' => $periodStart?->toDateString(),
                'period_end'   => $periodEnd?->toDateString(),

                'subtotal'        => $amounts['subtotal'],
                'discount_amount' => $amounts['discount_amount'],
                'tax_rate'        => $amounts['tax_rate'],
                'tax_amount'      => $amounts['tax_amount'],
                'total_amount'    => $amounts['total_amount'],

                'status'    => ChargeStatus::Pending->value,
                'reference' => $data['reference'] ?? null,
                'notes'     => $data['notes'] ?? null,
            ]);

            // An advance paid months ago should settle this charge without
            // anyone having to remember it exists.
            if ($this->shouldApplyCredit($data)) {
                $this->allocations->applyCredit($clientId, $charge);
                $charge->refresh();
            }

            return $charge;
        });
    }


    /**
     * Edit a charge that has not been settled yet.
     *
     * The model guard rejects this the moment any money is against the charge,
     * so this exists for the ordinary case of a typo caught straight away —
     * far better than forcing a cancel-and-recreate for a wrong amount.
     */
    public function update(ProjectCharge $charge, array $data): ProjectCharge
    {
        return DB::transaction(function () use ($charge, $data) {
            $charge = ProjectCharge::query()
                ->whereKey($charge->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($charge->isLocked()) {
                throw new ProjectBillingException(
                    'This charge already has money against it and can no longer be edited. '
                    .'Cancel it and raise a new one instead.'
                );
            }

            $amounts = $this->calculateAmounts(
                subtotal: (float) ($data['subtotal'] ?? $charge->subtotal),
                discount: (float) ($data['discount_amount'] ?? $charge->discount_amount),
                taxRate: (float) ($data['tax_rate'] ?? $charge->tax_rate),
            );

            $charge->update([
                'title'       => $data['title'] ?? $charge->title,
                'description' => $data['description'] ?? $charge->description,
                'charge_date' => $data['charge_date'] ?? $charge->charge_date,
                'due_date'    => $data['due_date'] ?? $charge->due_date,
                'reference'   => $data['reference'] ?? $charge->reference,
                'notes'       => $data['notes'] ?? $charge->notes,
                ...$amounts,
            ]);

            return $charge->refresh();
        });
    }

    // ─────────────────────────────────────────────────────────
    // CANCEL
    // ─────────────────────────────────────────────────────────

    /**
     * Cancel a charge that should never have existed, or that the client is no
     * longer obliged to pay.
     *
     * Any money already applied is released back to the client as credit
     * rather than vanishing — the payment itself is untouched and stays
     * available for other charges or a refund. This is the Scenario F path:
     * a cancelled project where 30,000 had already been received.
     *
     * Use writeOff() instead when the obligation was real but is being
     * forgiven — the two must stay distinguishable in reporting.
     */
    public function cancel(ProjectCharge $charge, string $reason): ProjectCharge
    {
        if (trim($reason) === '') {
            throw ProjectBillingException::missingReason();
        }

        return DB::transaction(function () use ($charge, $reason) {
            $charge = ProjectCharge::query()
                ->whereKey($charge->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($charge->status === ChargeStatus::Cancelled) {
                return $charge;
            }

            // Release allocated money first. recalculateSettlement() runs
            // inside each reversal and will reset this charge to Pending.
            foreach ($charge->activeAllocations()->get() as $allocation) {
                $this->allocations->reverseAllocation($allocation, "Charge cancelled: {$reason}");
            }

            $charge->refresh();

            $charge->forceFill([
                'status' => ChargeStatus::Cancelled->value,
                'notes'  => trim(($charge->notes ? $charge->notes."\n" : '')."Cancelled: {$reason}"),
            ])->save();

            return $charge;
        });
    }

    // ─────────────────────────────────────────────────────────
    // MATH
    // ─────────────────────────────────────────────────────────

    /**
     * The single place charge money is computed.
     *
     *   taxable = subtotal - discount
     *   tax     = taxable * rate / 100
     *   total   = taxable + tax
     *
     * Discount is applied before tax, which is how Indian service billing
     * works — tax is charged on the amount actually payable, not the list
     * price.
     */
    public function calculateAmounts(float $subtotal, float $discount = 0, float $taxRate = 0): array
    {
        $subtotal = round(max(0, $subtotal), 2);
        $discount = round(max(0, min($discount, $subtotal)), 2);
        $taxRate  = round(max(0, $taxRate), 2);

        $taxable   = round($subtotal - $discount, 2);
        $taxAmount = round($taxable * $taxRate / 100, 2);

        // taxable_amount is intentionally absent: it is derived on the model as
        // subtotal - discount, so there is no stored third value to drift.
        return [
            'subtotal'        => $subtotal,
            'discount_amount' => $discount,
            'tax_rate'        => $taxRate,
            'tax_amount'      => $taxAmount,
            'total_amount'    => round($taxable + $taxAmount, 2),
        ];
    }

    // ─────────────────────────────────────────────────────────
    // INTERNALS
    // ─────────────────────────────────────────────────────────

    /**
     * A charge without a due date cannot appear in an ageing bucket, which is
     * why the legacy module had no collection follow-up at all. So one is
     * always derived unless explicitly supplied.
     */
    private function resolveDueDate(CarbonImmutable $chargeDate, mixed $explicit): ?CarbonImmutable
    {
        if ($explicit !== null && $explicit !== '') {
            return $this->toDate($explicit);
        }

        // Platform-level policy, not a per-tenant one — and critically, readable
        // from CLI. get_setting() resolves its company from Auth or the tenant
        // host, neither of which exists in a scheduled command, so every charge
        // raised by the nightly sync silently fell back to the default here.
        $days = (int) get_system_setting('project_charge_due_days', self::DEFAULT_DUE_DAYS);

        return $chargeDate->addDays(max(0, $days));
    }

    private function resolveClientService(array $data): ?ProjectClientService
    {
        $id = $data['client_service_id'] ?? null;

        return $id ? ProjectClientService::find($id) : null;
    }

    /**
     * Title is a snapshot, so it must be readable even after every parent row
     * is deleted or renamed.
     */
    private function resolveTitle(array $data, ?ProjectClientService $clientService): string
    {
        $title = trim((string) ($data['title'] ?? ''));

        if ($title !== '') {
            return $title;
        }

        if ($clientService) {
            return $clientService->name;
        }

        if (! empty($data['service_id'])) {
            $catalog = ServiceCatalog::find($data['service_id']);

            if ($catalog) {
                return $catalog->name;
            }
        }

        return 'Charge';
    }

    /**
     * Charges must share the store of whatever produced them, so a payment
     * recorded at that store can settle them without a store mismatch.
     * Falls back to the StoreScoped trait's active-store default.
     */
    private function resolveStoreId(array $data, ?ProjectClientService $clientService): ?int
    {
        return $data['store_id']
            ?? $clientService?->store_id
            ?? optional(active_store())->id;
    }

    private function resolveType(mixed $type): string
    {
        if ($type instanceof ChargeType) {
            return $type->value;
        }

        return ChargeType::tryFrom((string) $type)?->value ?? ChargeType::OneTime->value;
    }

    /**
     * Two charges for the same service and the same period start is always a
     * mistake — usually a resubmitted form. Caught here rather than leaving a
     * duplicate obligation on the client's ledger.
     */
    private function assertPeriodNotAlreadyBilled(ProjectClientService $clientService, CarbonImmutable $periodStart): void
    {
        $exists = ProjectCharge::query()
            ->where('client_service_id', $clientService->id)
            ->whereDate('period_start', $periodStart->toDateString())
            ->where('status', '!=', ChargeStatus::Cancelled->value)
            ->exists();

        if ($exists) {
            throw new ProjectBillingException(sprintf(
                'A charge for "%s" starting %s already exists. Cancel it first if you need to raise a new one.',
                $clientService->name,
                $periodStart->format('d M Y')
            ));
        }
    }

    /** Tenant default, overridable per call. */
    private function shouldApplyCredit(array $data): bool
    {
        if (array_key_exists('apply_credit', $data) && $data['apply_credit'] !== null) {
            return (bool) $data['apply_credit'];
        }

        // Same CLI blindness as the due-days lookup, but with money attached:
        // read through get_setting() this returned true on every auto-renewal
        // regardless of configuration, quietly allocating client credit that
        // the tenant may have chosen to hold back.
        return (bool) get_system_setting('project_auto_apply_credit', true);
    }

    private function toDate(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        return CarbonImmutable::parse($value)->startOfDay();
    }
}