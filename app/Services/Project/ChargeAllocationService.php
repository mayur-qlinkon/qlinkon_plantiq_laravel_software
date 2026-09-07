<?php

namespace App\Services\Project;

use App\Enums\Project\AllocationKind;
use App\Enums\Project\ChargeStatus;
use App\Exceptions\Project\ProjectBillingException;
use App\Models\Payment;
use App\Models\Project\ProjectCharge;
use App\Models\Project\ProjectChargeAllocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The heart of project billing: it decides which money settles which charge.
 *
 * Everything here runs inside a transaction with the affected charge rows
 * locked. Without the lock, two admins recording payments at the same moment
 * both read the same balance and both allocate against it, leaving a charge
 * over-settled with no error raised anywhere.
 *
 * Two invariants this class exists to protect:
 *   1. SUM(active allocations for a payment) never exceeds payment.amount
 *   2. SUM(active allocations for a charge) never exceeds charge.total_amount
 *
 * Anything left over on a payment is not an error — it is client credit.
 */
class ChargeAllocationService
{
    /**
     * Tolerance for float comparison, half a paisa.
     *
     * Money is stored as decimal(15,2) but arrives here as PHP floats, so a
     * balance that should be exactly zero can read as 0.0000000001. Comparing
     * raw floats leaves charges permanently one paisa short of Paid.
     */
    private const EPSILON = 0.005;

    // ─────────────────────────────────────────────────────────
    // READ HELPERS
    // ─────────────────────────────────────────────────────────

    /**
     * How much of this payment has not yet been applied to any charge.
     * This is the client's credit from this single payment.
     */
    public function allocatableAmount(Payment $payment): float
    {
        $allocated = (float) ProjectChargeAllocation::query()
            ->where('payment_id', $payment->id)
            ->where('is_reversed', false)
            ->sum('amount');

        return $this->money((float) $payment->amount - $allocated);
    }

    /**
     * Total advance/overpayment sitting unapplied for a client.
     *
     * Computed as received payments minus what has been allocated from them.
     * Reversed and cancelled payments are excluded — money that was undone is
     * not credit.
     */
    public function clientCreditBalance(int $clientId): float
    {
        $received = (float) $this->clientPaymentsQuery($clientId)->sum('amount');

        $allocated = (float) ProjectChargeAllocation::query()
            ->where('is_reversed', false)
            ->whereIn('payment_id', $this->clientPaymentsQuery($clientId)->select('id'))
            ->sum('amount');

        // Money already handed back reduces what the client still has on
        // account. Without this a refunded advance would stay claimable and
        // could be allocated to a future charge a second time.
        $refunded = (float) $this->clientRefundsQuery($clientId)->sum('amount');

        return max(0.0, $this->money($received - $allocated - $refunded));
    }

    /**
     * Payments that count as this client's money. Kept in one place so credit
     * balance and credit application can never drift apart.
     */
    private function clientPaymentsQuery(int $clientId)
    {
        return Payment::query()
            ->where('party_type', 'customer')
            ->where('party_id', $clientId)
            ->where('type', 'received')
            ->where('status', 'completed')
            // Scoped to this module only. Without it, a client's POS or invoice
            // payments look like unapplied project credit and get allocated to
            // project charges, settling the same rupee in two modules at once.
            ->where('payment_for', 'project');
    }

    /** Money handed back to the client from this module. */
    private function clientRefundsQuery(int $clientId)
    {
        return Payment::query()
            ->where('party_type', 'customer')
            ->where('party_id', $clientId)
            ->where('type', 'sent')
            ->where('status', 'completed')
            ->where('payment_for', 'project_refund');
    }

    // ─────────────────────────────────────────────────────────
    // ALLOCATION
    // ─────────────────────────────────────────────────────────

    /**
     * Apply a payment across the client's open charges, oldest due date first.
     *
     * This is the default path. A shopkeeper recording "client gave 20,000"
     * should never be asked which bill it belongs to — FIFO answers that, and
     * the manual screen exists for the rare case where it is wrong.
     *
     * Leftover money stays unallocated and becomes client credit. That is a
     * valid outcome, not a failure.
     *
     * @return Collection<int, ProjectChargeAllocation>
     */
    public function autoAllocate(Payment $payment, ?int $clientId = null): Collection
    {
        $clientId ??= (int) $payment->party_id;

        return DB::transaction(function () use ($payment, $clientId) {
            $remaining = $this->lockAndGetAllocatable($payment);

            if ($remaining <= self::EPSILON) {
                return collect();
            }

            $charges = ProjectCharge::query()
                ->where('client_id', $clientId)
                ->outstanding()
                ->fifo()
                ->lockForUpdate()
                ->get();

            $created = collect();

            foreach ($charges as $charge) {
                if ($remaining <= self::EPSILON) {
                    break;
                }

                $take = min($remaining, (float) $charge->balance_amount);

                if ($take <= self::EPSILON) {
                    continue;
                }

                $created->push($this->writeAllocation(
                    charge: $charge,
                    amount: $take,
                    kind: AllocationKind::Payment,
                    source: 'auto',
                    payment: $payment,
                ));

                $remaining = $this->money($remaining - $take);
            }

            return $created;
        });
    }

    /**
     * Apply a payment to specific charges with amounts chosen by the admin.
     *
     * @param  array<int, float>  $chargeAmounts  [charge_id => amount]
     * @return Collection<int, ProjectChargeAllocation>
     */
    public function manualAllocate(Payment $payment, array $chargeAmounts): Collection
    {
        $chargeAmounts = array_filter(
            $chargeAmounts,
            fn ($amount) => (float) $amount > self::EPSILON
        );

        if ($chargeAmounts === []) {
            return collect();
        }

        return DB::transaction(function () use ($payment, $chargeAmounts) {
            $remaining = $this->lockAndGetAllocatable($payment);

            $charges = ProjectCharge::query()
                ->whereIn('id', array_keys($chargeAmounts))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $created = collect();

            foreach ($chargeAmounts as $chargeId => $rawAmount) {
                $amount = $this->money((float) $rawAmount);
                $charge = $charges->get($chargeId);

                // A missing charge here means it was deleted or belongs to
                // another tenant — the global scope already filtered it out.
                if (! $charge) {
                    throw ProjectBillingException::chargeNotOpen('unavailable');
                }

                $this->assertCompatible($payment, $charge);

                if ($amount > $remaining + self::EPSILON) {
                    throw ProjectBillingException::exceedsPayment($amount, $remaining);
                }

                $created->push($this->writeAllocation(
                    charge: $charge,
                    amount: $amount,
                    kind: AllocationKind::Payment,
                    source: 'manual',
                    payment: $payment,
                ));

                $remaining = $this->money($remaining - $amount);
            }

            return $created;
        });
    }

    /**
     * Settle part or all of a charge from the client's existing unapplied
     * payments, oldest payment first.
     *
     * Called automatically when a new charge is raised, so an advance paid in
     * December is picked up by May's renewal without anyone remembering it.
     *
     * @return Collection<int, ProjectChargeAllocation>
     */
    /**
     * Apply the client's unallocated credit, honouring charge-level FIFO.
     *
     * The target charge is only reached once every OLDER outstanding charge is
     * settled. Applying credit straight to a freshly raised charge left older
     * periods showing as unpaid while their money sat on a future period —
     * breaking ageing reports and sending reminders for bills already paid.
     */
    public function applyCredit(int $clientId, ProjectCharge $charge, ?float $max = null): Collection
    {
        return DB::transaction(function () use ($clientId, $charge, $max) {
            $charge = ProjectCharge::query()
                ->whereKey($charge->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($charge->status->isSettled()) {
                return collect();
            }

            // Settle anything older than the target first. Same FIFO ordering
            // autoAllocate() uses, so both credit paths agree on priority.
            $older = ProjectCharge::query()
                ->where('client_id', $clientId)
                ->whereKeyNot($charge->id)
                ->outstanding()
                ->fifo()
                ->lockForUpdate()
                ->get()
                ->filter(fn (ProjectCharge $other) => $this->precedes($other, $charge));

            $created = collect();

            foreach ($older as $olderCharge) {
                $created = $created->merge(
                    $this->fillChargeFromCredit($clientId, $olderCharge)
                );
            }

            $charge->refresh();

            if ($charge->status->isSettled()) {
                return $created;
            }

            $remaining = (float) $charge->balance_amount;

            if ($max !== null) {
                $remaining = min($remaining, $this->money($max));
            }

            if ($remaining <= self::EPSILON) {
                return $created;
            }

            // Oldest money first, so credit is consumed in the order it arrived.
            $payments = $this->clientPaymentsQuery($clientId)
                ->orderBy('payment_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($payments as $payment) {
                if ($remaining <= self::EPSILON) {
                    break;
                }

                $available = $this->allocatableAmount($payment);

                if ($available <= self::EPSILON) {
                    continue;
                }

                $take = min($remaining, $available);

                $created->push($this->writeAllocation(
                    charge: $charge,
                    amount: $take,
                    kind: AllocationKind::Payment,
                    // source distinguishes this from money applied on the day;
                    // a separate allocation kind would have carried no extra
                    // meaning, since both settle the charge identically.
                    source: 'credit',
                    payment: $payment,
                ));

                $remaining = $this->money($remaining - $take);
                $charge->refresh();
            }

            return $created;
        });
    }

    /**
     * Forgive part of a charge — the Kasar pattern from the invoices module.
     *
     * Settles the charge without any money arriving, so payment_id stays null
     * and the amount accumulates into written_off_amount rather than
     * paid_amount. Collected money and forgiven money must never be summed
     * together in reporting.
     */
    /**
     * Does $a come before $b in FIFO order?
     *
     * Mirrors scopeFifo() exactly: due date first, nulls last, id as the
     * tie-breaker. Kept in sync with that scope — if they drift, credit and
     * payments would start disagreeing about which charge is older.
     */
    private function precedes(ProjectCharge $a, ProjectCharge $b): bool
    {
        $aDue = $a->due_date;
        $bDue = $b->due_date;

        if ($aDue === null && $bDue === null) {
            return $a->id < $b->id;
        }

        // A charge with no due date sorts last.
        if ($aDue === null) {
            return false;
        }

        if ($bDue === null) {
            return true;
        }

        if (! $aDue->isSameDay($bDue)) {
            return $aDue->lessThan($bDue);
        }

        return $a->id < $b->id;
    }

    /**
     * Consume available credit into one charge, oldest payment first.
     *
     * @return Collection<int, ProjectChargeAllocation>
     */
    private function fillChargeFromCredit(int $clientId, ProjectCharge $charge): Collection
    {
        $remaining = (float) $charge->balance_amount;

        if ($remaining <= self::EPSILON) {
            return collect();
        }

        $payments = $this->clientPaymentsQuery($clientId)
            ->orderBy('payment_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $created = collect();

        foreach ($payments as $payment) {
            if ($remaining <= self::EPSILON) {
                break;
            }

            $available = $this->allocatableAmount($payment);

            if ($available <= self::EPSILON) {
                continue;
            }

            $take = min($remaining, $available);

            $created->push($this->writeAllocation(
                charge: $charge,
                amount: $take,
                kind: AllocationKind::Payment,
                source: 'credit',
                payment: $payment,
            ));

            $remaining = $this->money($remaining - $take);
            $charge->refresh();
        }

        return $created;
    }

    public function writeOff(ProjectCharge $charge, float $amount, string $reason): ProjectChargeAllocation
    {
        if (trim($reason) === '') {
            throw ProjectBillingException::missingReason();
        }

        return DB::transaction(function () use ($charge, $amount, $reason) {
            $charge = ProjectCharge::query()
                ->whereKey($charge->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($charge->status === ChargeStatus::Cancelled) {
                throw ProjectBillingException::chargeNotOpen($charge->status->label());
            }

            return $this->writeAllocation(
                charge: $charge,
                amount: $this->money($amount),
                kind: AllocationKind::WriteOff,
                source: 'manual',
                payment: null,
                reason: $reason,
            );
        });
    }

    // ─────────────────────────────────────────────────────────
    // REVERSAL
    // ─────────────────────────────────────────────────────────

    /**
     * Undo a single allocation. The row survives as history — financial
     * records are never hard deleted, only flagged.
     */
    public function reverseAllocation(ProjectChargeAllocation $allocation, string $reason): ProjectChargeAllocation
    {
        if (trim($reason) === '') {
            throw ProjectBillingException::missingReason();
        }

        return DB::transaction(function () use ($allocation, $reason) {
            $allocation = ProjectChargeAllocation::query()
                ->whereKey($allocation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($allocation->is_reversed) {
                throw ProjectBillingException::alreadyReversed();
            }

            $charge = ProjectCharge::query()
                ->whereKey($allocation->charge_id)
                ->lockForUpdate()
                ->firstOrFail();

            $allocation->forceFill([
                'is_reversed' => true,
                'reversed_at' => now(),
                'reversed_by' => Auth::id(),
                'notes'       => trim(($allocation->notes ? $allocation->notes."\n" : '')."Reversed: {$reason}"),
            ])->save();

            $charge->recalculateSettlement();

            return $allocation;
        });
    }

    /**
     * Reverse every live allocation belonging to a payment.
     *
     * Used when a cheque bounces or a payment was entered by mistake. Each
     * affected charge recalculates independently, so a payment spread across
     * three billing periods correctly reopens all three.
     *
     * @return Collection<int, ProjectChargeAllocation>
     */
    public function reversePaymentAllocations(Payment $payment, string $reason): Collection
    {
        return DB::transaction(function () use ($payment, $reason) {
            $allocations = ProjectChargeAllocation::query()
                ->where('payment_id', $payment->id)
                ->where('is_reversed', false)
                ->lockForUpdate()
                ->get();

            return $allocations->map(fn (ProjectChargeAllocation $a) => $this->reverseAllocation($a, $reason));
        });
    }

    // ─────────────────────────────────────────────────────────
    // INTERNALS
    // ─────────────────────────────────────────────────────────

    /**
     * Locks the payment row, then reports how much of it is still unapplied.
     *
     * The lock is what stops two concurrent allocations from both seeing the
     * full amount as available.
     */
    private function lockAndGetAllocatable(Payment $payment): float
    {
        $locked = Payment::query()
            ->whereKey($payment->id)
            ->lockForUpdate()
            ->firstOrFail();

        return $this->allocatableAmount($locked);
    }

    /**
     * Single writer for allocation rows. Every path goes through here so the
     * balance check and the charge recalculation can never be skipped.
     */
    private function writeAllocation(
        ProjectCharge $charge,
        float $amount,
        AllocationKind $kind,
        string $source,
        ?Payment $payment = null,
        ?string $reason = null,
    ): ProjectChargeAllocation {
        $amount = $this->money($amount);

        if ($amount <= self::EPSILON) {
            throw ProjectBillingException::invalidAmount();
        }

        if ($charge->status === ChargeStatus::Cancelled) {
            throw ProjectBillingException::chargeNotOpen($charge->status->label());
        }

        $balance = (float) $charge->balance_amount;

        if ($amount > $balance + self::EPSILON) {
            throw ProjectBillingException::exceedsCharge($amount, $balance);
        }

        if ($payment) {
            $this->assertCompatible($payment, $charge);
        }

        $allocation = ProjectChargeAllocation::create([
            'company_id'   => $charge->company_id,
            'charge_id'    => $charge->id,
            'payment_id'   => $payment?->id,
            'kind'         => $kind->value,
            'amount'       => $amount,
            'source'       => $source,
            'reason'       => $reason,
            'allocated_by' => Auth::id(),
            'allocated_at' => now(),
        ]);

        // Same transaction, immediately after the write — the cached totals on
        // the charge must never be allowed to lag behind its allocations.
        $charge->recalculateSettlement();

        return $allocation;
    }

    /**
     * Cross-tenant and cross-client safety net.
     *
     * The Tenantable global scope already prevents reading another company's
     * rows, but this asserts it explicitly at the point money moves. A billing
     * bug that silently applies one client's payment to another client's charge
     * is far more damaging than an exception.
     */
    private function assertCompatible(Payment $payment, ProjectCharge $charge): void
    {
        if ((int) $payment->company_id !== (int) $charge->company_id) {
            throw ProjectBillingException::companyMismatch();
        }

        if ($payment->party_type === 'customer' && (int) $payment->party_id !== (int) $charge->client_id) {
            throw ProjectBillingException::clientMismatch();
        }
    }

    /** Normalises to two decimal places so comparisons stay predictable. */
    private function money(float $value): float
    {
        return round($value, 2);
    }
}