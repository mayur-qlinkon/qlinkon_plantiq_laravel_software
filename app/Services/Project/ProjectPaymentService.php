<?php

namespace App\Services\Project;

use App\Exceptions\Project\ProjectBillingException;
use App\Models\Payment;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Records money received from a client.
 *
 * Deliberately does NOT use PaymentService::recordPayment(), which is
 * document-centric: it requires a document with a grand_total, caps the amount
 * at that total, and then writes back the document's payment status. All three
 * are wrong here.
 *
 * The capping in particular breaks the core rule of this module — a payment is
 * never refused and never trimmed. If a client hands over more than they owe,
 * that is an advance, and the surplus becomes credit. Silently reducing the
 * recorded amount would mean the books no longer match the bank.
 *
 * A payment here belongs to the CLIENT (party_type = customer), not to any
 * document, which is what allows one cheque to settle three billing periods.
 */
class ProjectPaymentService
{
    /**
     * Attempts to survive a payment-number collision under concurrency.
     * Three is comfortably enough: each retry re-reads the current maximum.
     */
    private const NUMBER_MAX_ATTEMPTS = 3;

    public function __construct(
        private readonly ChargeAllocationService $allocations,
    ) {}

    // ─────────────────────────────────────────────────────────
    // RECORD
    // ─────────────────────────────────────────────────────────

    /**
     * Record a payment received from a client, then apply it.
     *
     * Accepted keys:
     *   client_id*         int
     *   amount*            float
     *   payment_method_id* int
     *   payment_date       date|null   defaults to today
     *   reference          string|null cheque no / UTR / UPI ref
     *   notes              string|null
     *   store_id           int|null    defaults to the active store
     *   allocate           bool|null   false to hold the whole amount as credit
     *   allocations        array|null  [charge_id => amount] for manual mode
     *
     * @return Payment  with ->project_allocations set to what was applied
     */
    public function record(array $data): Payment
    {
        $clientId = (int) ($data['client_id'] ?? 0);
        $amount   = round((float) ($data['amount'] ?? 0), 2);

        if ($clientId <= 0) {
            throw new ProjectBillingException('A payment must be recorded against a client.');
        }

        if ($amount <= 0) {
            throw new ProjectBillingException('Payment amount must be greater than zero.');
        }

        if (empty($data['payment_method_id'])) {
            throw new ProjectBillingException('Please select a payment method.');
        }

        $idempotencyKey = $data['idempotency_key'] ?? null;

        try {
            $payment = DB::transaction(function () use ($data, $clientId, $amount) {
                return $this->createPaymentRow($data, $clientId, $amount);
            });
        } catch (QueryException $e) {
            // The second half of a double-submit. The first request already
            // recorded AND allocated this payment, so hand that one back
            // untouched — re-running applyIncoming() here would allocate the
            // same money a second time, which is the exact bug this prevents.
            $existing = $idempotencyKey !== null && $this->isDuplicateKey($e)
                ? Payment::where('idempotency_key', $idempotencyKey)->first()
                : null;

            if (! $existing) {
                throw $e;
            }

            Log::info('[ProjectPayment] Duplicate submit ignored', [
                'payment_id' => $existing->id,
                'client_id'  => $clientId,
            ]);

            $existing->setAttribute('project_allocations', $existing->projectAllocations()->get());
            $existing->setAttribute('allocation_error', null);

            return $existing;
        }

        // Allocation runs in its own transaction so a failure here cannot
        // discard the payment itself. The money arriving is a fact; how it is
        // applied is a decision that can be corrected afterwards.
        //
        // The exception must be swallowed for the same reason: letting it
        // bubble made the caller report "payment failed" for a payment that was
        // already committed, and the user would record it a second time. The
        // money instead stays as client credit and can be allocated by hand.
        $applied = collect();
        $allocationError = null;

        try {
            $applied = $this->applyIncoming($payment, $data, $clientId);
        } catch (Throwable $e) {
            $allocationError = $e->getMessage();

            Log::error('[ProjectPayment] Allocation failed after the payment was recorded', [
                'payment_id' => $payment->id,
                'client_id'  => $clientId,
                'error'      => $e->getMessage(),
            ]);
        }

        // refresh() before the custom attributes, not after — it reloads from
        // the database and would otherwise drop both of them.
        $payment->refresh();

        $payment->setAttribute('project_allocations', $applied);
        $payment->setAttribute('allocation_error', $allocationError);

        return $payment;
    }

    /**
     * Decides how a freshly recorded payment is applied.
     *
     * Manual amounts win when supplied; otherwise FIFO settles the oldest due
     * charges first. Passing allocate => false holds the entire amount as
     * client credit, which is how a pure advance is recorded.
     *
     * @return Collection<int, \App\Models\Project\ProjectChargeAllocation>
     */
    private function applyIncoming(Payment $payment, array $data, int $clientId): Collection
    {
        if (array_key_exists('allocate', $data) && $data['allocate'] === false) {
            return collect();
        }

        if (! empty($data['allocations']) && is_array($data['allocations'])) {
            return $this->allocations->manualAllocate($payment, $data['allocations']);
        }

        return $this->allocations->autoAllocate($payment, $clientId);
    }

    private function createPaymentRow(array $data, int $clientId, float $amount): Payment
    {
        return $this->withUniqueNumber(
            (int) Auth::user()?->company_id,
            fn (string $number) => Payment::create([
                'store_id'          => $data['store_id'] ?? optional(active_store())->id,
                'created_by'        => Auth::id(),
                'payment_method_id' => $data['payment_method_id'],

                // The client owns this payment. No document reference — that is
                // exactly what lets it span several charges.
                'party_type'       => 'customer',
                'party_id'         => $clientId,
                'paymentable_type' => null,
                'paymentable_id'   => null,

                'payment_number' => $number,
                'reference'      => $data['reference'] ?? null,
                'payment_date'   => $data['payment_date'] ?? now()->toDateString(),
                'type'           => 'received',

                // amount_received mirrors amount: there is no cash-drawer
                // change concept here the way there is at a POS counter.
                'amount'          => $amount,
                'amount_received' => $amount,
                'change_returned' => 0,

                'status'      => 'completed',
                'payment_for' => 'project',
                'notes'       => $data['notes'] ?? null,
            ])
        );
    }

    // ─────────────────────────────────────────────────────────
    // REVERSE
    // ─────────────────────────────────────────────────────────

    /**
     * Undo a payment — a bounced cheque, or an entry made in error.
     *
     * Never deletes. Every allocation it made is reversed, so each affected
     * charge independently returns to Pending or Partially Paid, and the
     * payment row survives as history with status = reversed.
     */
    public function reverse(Payment $payment, string $reason): Payment
    {
        if (trim($reason) === '') {
            throw ProjectBillingException::missingReason();
        }

        return DB::transaction(function () use ($payment, $reason) {
            $payment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status === 'reversed') {
                throw new ProjectBillingException('This payment has already been reversed.');
            }

            $this->allocations->reversePaymentAllocations($payment, $reason);

            $payment->forceFill([
                'status' => 'reversed',
                'notes'  => trim(($payment->notes ? $payment->notes."\n" : '')."Reversed: {$reason}"),
            ])->save();

            return $payment;
        });
    }

    // ─────────────────────────────────────────────────────────
    // REFUND
    // ─────────────────────────────────────────────────────────

    /**
     * Hand money back to a client from their unapplied credit.
     *
     * Recorded as an ordinary outgoing payment rather than a new kind of
     * record — the money genuinely left the business, and the payments table
     * already models that direction. payment_for keeps it identifiable so it
     * can be subtracted from the client's credit balance.
     *
     * Only unallocated credit can be refunded. Money already applied to a
     * charge must be released first by cancelling that charge, which is a
     * deliberate decision rather than a side effect of a refund.
     */
    public function refund(int $clientId, array $data): Payment
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);

        if ($amount <= 0) {
            throw new ProjectBillingException('Refund amount must be greater than zero.');
        }

        if (empty($data['payment_method_id'])) {
            throw new ProjectBillingException('Please select a payment method.');
        }

        if (trim((string) ($data['reason'] ?? '')) === '') {
            throw ProjectBillingException::missingReason();
        }

        return DB::transaction(function () use ($clientId, $amount, $data) {
            $credit = $this->allocations->clientCreditBalance($clientId);

            if ($amount > $credit) {
                throw new ProjectBillingException(sprintf(
                    'Cannot refund %s. This client only has %s of unapplied credit. '
                    .'Cancel a charge first to release money that is already applied.',
                    number_format($amount, 2),
                    number_format($credit, 2)
                ));
            }

            return $this->withUniqueNumber(
                (int) Auth::user()?->company_id,
                fn (string $number) => Payment::create([
                    'store_id'          => $data['store_id'] ?? optional(active_store())->id,
                    'created_by'        => Auth::id(),
                    'payment_method_id' => $data['payment_method_id'],

                    'party_type'       => 'customer',
                    'party_id'         => $clientId,
                    'paymentable_type' => null,
                    'paymentable_id'   => null,

                    'payment_number' => $number,
                    'reference'      => $data['reference'] ?? null,
                    'payment_date'   => $data['payment_date'] ?? now()->toDateString(),

                    // Outgoing. This is what keeps it out of every "received"
                    // total across the application.
                    'type' => 'sent',

                    'amount'          => $amount,
                    'amount_received' => 0,
                    'change_returned' => 0,

                    'status'      => 'completed',
                    'payment_for' => 'project_refund',
                    'notes'       => trim("Refund: {$data['reason']}"),
                ])
            );
        });
    }

    // ─────────────────────────────────────────────────────────
    // PAYMENT NUMBER
    // ─────────────────────────────────────────────────────────

    /**
     * Runs $callback with a generated payment number, retrying on collision.
     *
     * payments has unique(company_id, payment_number), and the number is
     * derived from existing rows, so two simultaneous inserts can produce the
     * same value. Rather than locking a sequence table, this simply detects the
     * duplicate-key error and regenerates — cheap, and correct under the low
     * concurrency this module actually sees.
     */
    private function withUniqueNumber(int $companyId, callable $callback): Payment
    {
        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                return $callback($this->generatePaymentNumber($companyId, $attempt - 1));
            } catch (QueryException $e) {
                // Only a payment_number collision is worth retrying. Since the
                // idempotency index landed on this same table, a bare 23000
                // check would mistake a duplicate submit for a number clash and
                // burn every attempt regenerating numbers that can never win.
                if (! $this->isNumberCollision($e) || $attempt >= self::NUMBER_MAX_ATTEMPTS) {
                    throw $e;
                }

                // Fall through and try again with a freshly read sequence.
            }
        }
    }

    /**
     * PAY-YYYYMMDD-NNNN, sequential per company per day.
     *
     * Uses MAX(suffix) rather than COUNT(*): counting breaks permanently the
     * first time a payment is deleted, because the next number then collides
     * with one already issued.
     *
     * $skew nudges the sequence forward on retry so a colliding pair does not
     * immediately collide again.
     */
    private function generatePaymentNumber(int $companyId, int $skew = 0): string
    {
        $prefix = 'PAY-'.now()->format('Ymd');

        $lastNumber = Payment::withTrashed()
            ->withoutGlobalScope('tenant')
            ->where('company_id', $companyId)
            ->where('payment_number', 'like', $prefix.'-%')
            ->orderByDesc('payment_number')
            ->value('payment_number');

        $lastSequence = $lastNumber
            ? (int) substr((string) $lastNumber, strrpos((string) $lastNumber, '-') + 1)
            : 0;

        $next = $lastSequence + 1 + $skew;

        return $prefix.'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /** MySQL integrity constraint violation. */
    private function isDuplicateKey(Throwable $e): bool
    {
        return $e instanceof QueryException
            && ((string) ($e->errorInfo[0] ?? '')) === '23000';
    }

    /**
     * True only when the integrity violation came from the payment_number
     * unique index, as opposed to the idempotency index on the same table.
     */
    private function isNumberCollision(Throwable $e): bool
    {
        return $this->isDuplicateKey($e)
            && str_contains((string) $e->getMessage(), 'payments_company_id_payment_number_unique');
    }
}