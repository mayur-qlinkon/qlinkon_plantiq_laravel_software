<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceWriteOff;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceWriteOffService
{
    /**
     * STEP 1: CREATE DRAFT WRITE-OFF (Kasar)
     * Validates amount against current outstanding balance and creates the record.
     * Draft does NOT touch settlement_status — only confirm does.
     */
    public function createWriteOff(Invoice $invoice, array $data): InvoiceWriteOff
    {
        return DB::transaction(function () use ($invoice, $data) {
            $companyId = $invoice->company_id;

            $outstanding = $this->getOutstandingBalance($invoice);
            $amount = round((float) $data['amount'], 2);

            if ($amount <= 0) {
                throw new Exception('Write-off amount must be greater than zero.');
            }

            if ($amount > $outstanding + 0.00005) {
                throw new Exception("Write-off amount cannot exceed the outstanding balance of ₹{$outstanding}.");
            }

            return InvoiceWriteOff::create([
                'company_id' => $companyId,
                'store_id' => $data['store_id'] ?? $invoice->store_id,
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'created_by' => Auth::id(),

                'write_off_number' => $this->generateWriteOffNumber($companyId),
                'write_off_date' => $data['write_off_date'] ?? now(),
                'amount' => $amount,
                'status' => 'draft',
                'reason' => $data['reason'] ?? 'other',
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * STEP 2: CONFIRM WRITE-OFF
     * Re-validates against the live outstanding balance under a row lock (closes the race
     * where two drafts were created against the same balance), locks the document, and
     * refreshes the invoice's settlement_status.
     */
    public function confirmWriteOff(InvoiceWriteOff $writeOff): InvoiceWriteOff
    {
        if ($writeOff->status === 'confirmed') {
            return $writeOff; // idempotent — prevents double settlement_status sync
        }

        if ($writeOff->status === 'cancelled') {
            throw new Exception('A cancelled write-off cannot be confirmed.');
        }

        return DB::transaction(function () use ($writeOff) {
            $invoice = Invoice::where('id', $writeOff->invoice_id)->lockForUpdate()->firstOrFail();

            $outstanding = $this->getOutstandingBalance($invoice, excludeWriteOffId: $writeOff->id);

            if ((float) $writeOff->amount > $outstanding + 0.00005) {
                throw new Exception("This write-off exceeds the invoice's current outstanding balance of ₹{$outstanding}.");
            }

            $writeOff->update([
                'status' => 'confirmed',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            $this->syncInvoiceSettlementStatus($invoice);

            return $writeOff;
        });
    }

    /**
     * STEP 3: CANCEL WRITE-OFF
     * Reverses a confirmed write-off by flipping its status — never deletes or writes a
     * negative reversal row, keeping the ledger of "how much Kasar was ever given" intact.
     * Re-opens the invoice's settlement_status if it was closed because of this write-off.
     */
    public function cancelWriteOff(InvoiceWriteOff $writeOff, ?string $reason = null): InvoiceWriteOff
    {
        if ($writeOff->status === 'cancelled') {
            return $writeOff; // idempotent
        }

        return DB::transaction(function () use ($writeOff, $reason) {
            $wasConfirmed = $writeOff->status === 'confirmed';

            $writeOff->update([
                'status' => 'cancelled',
                'cancelled_by' => Auth::id(),
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            if ($wasConfirmed) {
                $invoice = Invoice::where('id', $writeOff->invoice_id)->lockForUpdate()->firstOrFail();
                $this->syncInvoiceSettlementStatus($invoice);
            }

            return $writeOff;
        });
    }

    /**
     * Central outstanding-balance formula — the single source of truth.
     * Outstanding = Grand Total - Completed Payments - Confirmed Returns - Confirmed Write-offs
     *
     * @param  int|null  $excludeWriteOffId  Exclude a specific write-off from the sum — used
     *                                       when re-validating that same write-off during confirm.
     */
    public function getOutstandingBalance(Invoice $invoice, ?int $excludeWriteOffId = null): float
    {
        $grandTotal = round((float) $invoice->grand_total, 2);

        $totalPaid = round((float) $invoice->payments()->where('status', 'completed')->sum('amount'), 2);

        $totalReturned = round((float) $invoice->returns()->where('status', 'confirmed')->sum('grand_total'), 2);

        $writeOffQuery = $invoice->writeOffs()->where('status', 'confirmed');
        if ($excludeWriteOffId) {
            $writeOffQuery->where('id', '!=', $excludeWriteOffId);
        }
        $totalWrittenOff = round((float) $writeOffQuery->sum('amount'), 2);

        return max(0, round($grandTotal - $totalPaid - $totalReturned - $totalWrittenOff, 2));
    }

    /**
     * Refreshes invoice.settlement_status based on the outstanding balance.
     * payment_status (pure cash collection) is intentionally left untouched here —
     * settlement_status is the only field that reflects Kasar-driven closure.
     */
    public function syncInvoiceSettlementStatus(Invoice $invoice): void
    {
        $outstanding = $this->getOutstandingBalance($invoice);

        $invoice->update([
            'settlement_status' => $outstanding <= 0.00005 ? 'closed' : 'open',
        ]);
    }

    /**
     * Generates a sequential Write-off number (e.g., WO-2608-0001)
     */
    protected function generateWriteOffNumber(int $companyId): string
    {
        $prefix = 'WO-'.date('ym');

        $latest = InvoiceWriteOff::withTrashed()
            ->where('company_id', $companyId)
            ->where('write_off_number', 'like', "{$prefix}-%")
            ->orderBy('write_off_number', 'desc')
            ->first();

        $nextSequence = $latest ? ((int) substr($latest->write_off_number, -4)) + 1 : 1;

        return $prefix.'-'.str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    }
}