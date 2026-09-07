<?php

namespace App\Services;

use App\Models\Expense;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExpenseService
{
    // ════════════════════════════════════════════════════
    //  STORE (Create new expense)
    // ════════════════════════════════════════════════════
    public function store(array $data, ?UploadedFile $receipt = null): Expense
    {
        $companyId = Auth::user()->company_id;

        // The lock must wrap the transaction, not sit inside it: released at the
        // end of an inner closure it lets go before COMMIT, and the next request
        // reads a sequence that cannot yet see this row.
        return Cache::lock("expense_number_{$companyId}", 20)
            ->block(10, fn () => $this->persistExpense($data, $receipt, $companyId));
    }

    /**
     * The transactional half of store(), always called under the company lock.
     */
    private function persistExpense(array $data, ?UploadedFile $receipt, int $companyId): Expense
    {
        return DB::transaction(function () use ($data, $receipt, $companyId) {
            try {

                // 1. Enforce ownership and defaults
                $data['company_id'] = $companyId;
                $data['user_id'] = $data['user_id'] ?? Auth::id();

                // 2. Generate unique sequential expense number (e.g. EXP-202403-0001)
                // lockForUpdate() alone cannot cover the first entry of a month:
                // there is no row to lock, so two concurrent requests both read
                // nothing and both produce sequence 0001.
                $data['expense_number'] = $this->generateExpenseNumber($companyId);

                // 3. Process Tax Math (CGST / SGST / IGST)
                $this->calculateTaxes($data);

                // 4. Insert Record
                $expense = Expense::create($data);

                // 5. Handle Spatie Media Upload
                if ($receipt) {
                    $expense->addMedia($receipt)->toMediaCollection('receipts');
                }

                Log::info('[ExpenseService] Expense logged successfully', [
                    'expense_id' => $expense->id,
                    'expense_number' => $expense->expense_number,
                    'user_id' => Auth::id(),
                ]);

                return $expense;

            } catch (Throwable $e) {
                Log::error('[ExpenseService] Failed to store expense', [
                    'error' => $e->getMessage(),
                    'payload' => collect($data)->except(['attachment', 'receipt'])->toArray(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e; // Bubble up to controller to trigger 500/422 response
            }
        });
    }

    // ════════════════════════════════════════════════════
    //  UPDATE
    // ════════════════════════════════════════════════════
    public function update(Expense $expense, array $data, ?UploadedFile $receipt = null): Expense
    {
        return DB::transaction(function () use ($expense, $data, $receipt) {
            try {
                // If financial data is updated, recalculate the taxes
                if (isset($data['base_amount']) || isset($data['tax_percent']) || isset($data['tax_type'])) {
                    // Merge existing state with new data to ensure accurate math
                    $calculationData = array_merge($expense->toArray(), $data);
                    $this->calculateTaxes($calculationData);

                    // Pull the newly calculated tax fields back into the update payload
                    $data['cgst_amount'] = $calculationData['cgst_amount'];
                    $data['sgst_amount'] = $calculationData['sgst_amount'];
                    $data['igst_amount'] = $calculationData['igst_amount'];
                    $data['total_amount'] = $calculationData['total_amount'];
                    $data['round_off'] = $calculationData['round_off'];
                }

                $expense->update($data);

                // Handle receipt replacement
                if ($receipt) {
                    // Spatie makes replacing files easy. Clear the old collection, add the new one.
                    $expense->clearMediaCollection('receipts');
                    $expense->addMedia($receipt)->toMediaCollection('receipts');
                }

                Log::info('[ExpenseService] Expense updated', [
                    'expense_id' => $expense->id,
                    'updated_by' => Auth::id(),
                ]);

                return $expense->fresh();

            } catch (Throwable $e) {
                Log::error('[ExpenseService] Failed to update expense', [
                    'expense_id' => $expense->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    // ════════════════════════════════════════════════════
    //  STATUS / WORKFLOW ENGINE
    // ════════════════════════════════════════════════════
    /**
     * Allowed status transitions. Membership in a flat list is not enough:
     * without this map an expense could jump straight from draft to
     * reimbursed, skipping approval entirely.
     */
    private const STATUS_TRANSITIONS = [
        'draft'            => ['pending_approval', 'rejected'],
        'pending_approval' => ['approved', 'rejected', 'draft'],
        'approved'         => ['reimbursed', 'rejected'],
        'rejected'         => ['draft'],
        'reimbursed'       => [],
    ];

    public function updateStatus(Expense $expense, string $newStatus): bool
    {
        $current = $expense->status;

        if ($current === $newStatus) {
            return true;
        }

        if (! array_key_exists($current, self::STATUS_TRANSITIONS)) {
            throw new \InvalidArgumentException("Unknown expense status: {$current}");
        }

        if (! in_array($newStatus, self::STATUS_TRANSITIONS[$current], true)) {
            $from = str_replace('_', ' ', $current);
            $to   = str_replace('_', ' ', $newStatus);

            throw new \InvalidArgumentException("An expense cannot move from {$from} to {$to}.");
        }

        // Money has already moved against this record, so the approval that
        // authorised it cannot be withdrawn. Reversing it would reopen the
        // expense for editing while its payments stayed on the ledger.
        if (in_array($current, ['approved', 'reimbursed'], true)
            && $expense->payments()->where('status', 'completed')->exists()) {
            throw new \InvalidArgumentException(
                'This expense has recorded payments, so its status can no longer change. Cancel the payments first.'
            );
        }

        $payload = ['status' => $newStatus];

        // Audit trail: Track exactly who approved it and when
        if ($newStatus === 'approved') {
            $payload['approved_by'] = Auth::id();
            $payload['approved_at'] = now();
        } elseif (in_array($current, ['approved', 'reimbursed'], true)) {
            // Leaving an approved state clears the trail, otherwise the record
            // keeps naming an approver for something no longer approved.
            $payload['approved_by'] = null;
            $payload['approved_at'] = null;
        }

        $success = $expense->update($payload);

        if ($success) {
            Log::info('[ExpenseService] Status transitioned', [
                'expense_id' => $expense->id,
                'new_status' => $newStatus,
                'changed_by' => Auth::id(),
            ]);
        }

        return $success;
    }

    // ════════════════════════════════════════════════════
    //  PAYMENT HANDLING
    // ════════════════════════════════════════════════════
   public function recordPayment(Expense $expense, array $data)
    {
        return DB::transaction(function () use ($expense, $data) {
            try {

                // 🌟 Normalize data for PaymentService
                $payload = [
                    'amount' => $data['amount'],
                    'payment_method_id' => $data['payment_method_id'] ?? null,
                    'payment_date' => $data['payment_date'] ?? now(),
                    'reference' => $data['reference'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'status' => 'completed',
                ];

                // 🌟 Use CENTRAL SERVICE (this handles EVERYTHING)
                $payment = app(\App\Services\PaymentService::class)
                    ->recordPayment($expense, $payload);

                // PaymentService::syncDocumentPaymentStatus() already set
                // payment_status inside recordPayment(). The block that used to
                // sit here recalculated it from amount_received and overwrote
                // that result — two writes disagreeing on both the column and
                // the rule. It also had no 'unpaid' branch, so a cancelled
                // payment left the expense stuck on 'partial'.
                $expense->refresh();

                Log::info('[ExpenseService] Payment recorded via PaymentService', [
                    'expense_id' => $expense->id,
                    'payment_id' => $payment->id,
                    'amount' => $payload['amount'],
                    'by_user' => Auth::id(),
                ]);

                return $payment;

            } catch (Throwable $e) {
                Log::error('[ExpenseService] Payment failed', [
                    'expense_id' => $expense->id,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }
    

    // ════════════════════════════════════════════════════
    //  PRIVATE HELPERS (The heavy lifting)
    // ════════════════════════════════════════════════════

    /**
     * Pass-by-reference tax calculator.
     * Accurately splits Indian GST into CGST/SGST or IGST.
     */
    private function calculateTaxes(array &$data): void
    {
        $base = (float) ($data['base_amount'] ?? 0);
        $percent = (float) ($data['tax_percent'] ?? 0);
        $type = $data['tax_type'] ?? 'none';

        // Reset taxes
        $data['cgst_amount'] = 0.00;
        $data['sgst_amount'] = 0.00;
        $data['igst_amount'] = 0.00;

        if ($type === 'igst' && $percent > 0) {
            $data['igst_amount'] = round(($base * $percent) / 100, 2);
        } elseif ($type === 'cgst_sgst' && $percent > 0) {
            $halfTax = round(($base * ($percent / 2)) / 100, 2);
            $data['cgst_amount'] = $halfTax;
            $data['sgst_amount'] = $halfTax;
        }

        // Calculate exact total
        $exactTotal = $base + $data['cgst_amount'] + $data['sgst_amount'] + $data['igst_amount'];

        // Round off to nearest integer (Standard Indian Accounting Practice for final invoices)
        $roundedTotal = round($exactTotal);

        $data['total_amount'] = $roundedTotal;
        $data['round_off'] = round($roundedTotal - $exactTotal, 2);
    }

    /**
     * Generate a sequential expense number tightly scoped to the company.
     * Uses pessimistic locking (lockForUpdate) to prevent duplicate IDs during concurrent requests.
     */
    private function generateExpenseNumber(int $companyId): string
    {
        $prefix = 'EXP-'.date('Ym').'-';

        // 🌟 ADD withTrashed() HERE
        $latestExpense = Expense::withTrashed()
            ->where('company_id', $companyId)
            ->where('expense_number', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        if (! $latestExpense) {
            $sequence = 1;
        } else {
            // Extract the last 4 digits and increment
            $lastSequence = (int) substr($latestExpense->expense_number, -4);
            $sequence = $lastSequence + 1;
        }

        return $prefix.str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
