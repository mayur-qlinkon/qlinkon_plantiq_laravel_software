<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected InvoiceWriteOffService $writeOffService;

    public function __construct(InvoiceWriteOffService $writeOffService)
    {
        $this->writeOffService = $writeOffService;
    }

    /**
     * Primary Entry Point: Record a payment against ANY document (Invoice/Purchase)
     *
     * * @param mixed $document The Invoice or Purchase model instance
     * @param  array  $data  ['amount', 'payment_method_id', 'reference', 'payment_date', 'notes']
     */
    public function recordPayment($document, array $data): Payment
    {
        return DB::transaction(function () use ($document, $data) {

            // 1. Identify Party Type and Direction
            $isInvoice = $document instanceof Invoice;
            $isOrder = $document instanceof Order;
            // Orders are always customer-received — not supplier-sent
            $partyType = ($isInvoice || $isOrder) ? 'customer' : 'supplier';

            /** * 🌟 THE CRITICAL FIX:
             * For Guest sales, customer_id is null. We fallback to 0.
             * In ERP logic, party_id 0 represents an "Anonymous/Walk-in" party.
             */
            $partyId = ($isInvoice || $isOrder)
                ? ($document->customer_id ?? 0)
                : ($document->supplier_id ?? 0);

            $type = ($isInvoice || $isOrder) ? 'received' : 'sent';

            // 🌟 POS MATH LOGIC
            $invoiceTotal = (float) $document->grand_total;
            $amountReceived = (float) ($data['amount'] ?? 0); // Raw cash handed over

            if ($amountReceived >= $invoiceTotal) {
                $changeReturned = $amountReceived - $invoiceTotal;
                $actualPaidAmount = $invoiceTotal; // Cap at invoice total
                $invoiceTotal = (float) $document->grand_total;
                Log::debug('[PaymentService] Amounts', [
                    'grand_total' => $document->grand_total,
                    'invoiceTotal' => $invoiceTotal,
                    'amountReceived' => $amountReceived,
                    'actualPaid' => $actualPaidAmount ?? 'not set yet',
                ]);
            } else {
                $changeReturned = 0;
                $actualPaidAmount = $amountReceived;
            }

            $storeId = $document->store_id
             ?? Store::where('company_id', $document->company_id)
                 ->where('is_active', true)->value('id')
             ?? Store::where('company_id', $document->company_id)
                 ->value('id');

            // A payment without a method is not a payment. Guarding here as well
            // as in validation, because this service is reached from POS, orders
            // and project charges — each with its own request class.
            if (empty($data['payment_method_id'])) {
                throw new \RuntimeException('A payment method is required to record a payment.');
            }

            // 2. Create the Payment Record
            $payment = Payment::create([
                'company_id' => $document->company_id,
                'store_id' => $storeId,
                'created_by' => Auth::id() ?? $document->created_by,
                'payment_method_id' => $data['payment_method_id'],

                'party_type' => $partyType,
                'party_id' => $partyId,

                'paymentable_type' => $document->getMorphClass(),
                'paymentable_id' => $document->id,

                'payment_number' => $this->generatePaymentNumber($document->company_id),
                'reference' => $data['reference'] ?? null,
                'payment_date' => $data['payment_date'] ?? now(),
                'type' => $type,

                // 🌟 The 3 crucial POS values
                'amount' => $actualPaidAmount,   // The applied invoice value
                'amount_received' => $amountReceived,     // The physical cash given
                'change_returned' => $changeReturned,     // The change given back

                'status' => $data['status'] ?? 'completed',
                'notes' => $data['notes'] ?? null,
            ]);

            // 3. Update the Parent Document (Invoice/Purchase) Balance/Status
            $this->syncDocumentPaymentStatus($document);


            return $payment;
        });
    }

    /**
     * Syncs the initial payment during an Invoice Update.
     * Prevents duplicate rows from being created in the payments table!
     */
    public function updateInitialPayment($document, array $data)
    {
        // 1. Look for the VERY FIRST payment attached to this invoice
        $existingPayment = $document->payments()->oldest()->first();

        if ($existingPayment) {
            // 2. If it exists and amount > 0, just UPDATE it! No duplication!
            if ($data['amount'] > 0) {

                // 🌟 POS MATH LOGIC FOR EDIT
                $invoiceTotal = (float) $document->grand_total;
                $amountReceived = (float) $data['amount'];

                if ($amountReceived >= $invoiceTotal) {
                    $changeReturned = $amountReceived - $invoiceTotal;
                    $actualPaidAmount = $invoiceTotal;
                } else {
                    $changeReturned = 0;
                    $actualPaidAmount = $amountReceived;
                }

                $existingPayment->update([
                    'amount' => $actualPaidAmount,
                    'amount_received' => $amountReceived,
                    'change_returned' => $changeReturned,
                    'payment_method_id' => $data['payment_method_id'] ?? $existingPayment->payment_method_id,
                ]);
            } else {
                // If they changed the paid amount to 0, wipe the payment record
                $existingPayment->delete();
            }
        } else {
            // 3. If NO payment exists, but they entered an amount, create it
            if ($data['amount'] > 0) {
                $this->recordPayment($document, $data);
            }
        }

        // 4. Update the Invoice status (Paid, Partial, Unpaid)
        $this->syncDocumentPaymentStatus($document);
    }

    /**
     * Syncs paid_amount and payment_status of the parent document
     */
    public function syncDocumentPaymentStatus($document): void
    {
        // Calculate total successful payments
        $totalPaid = round((float) $document->payments()->where('status', 'completed')->sum('amount'), 2);
        $grandTotal = round((float) $document->grand_total, 2);

        // ── Each document type has its own zero-payment status ──
        $zeroStatus = match (true) {
            $document instanceof Order => 'pending',
            $document instanceof PurchaseReturn => 'pending',
            default => 'unpaid', // Invoice, Purchase
        };

        $status = match (true) {
            $totalPaid >= $grandTotal && $grandTotal > 0 => 'paid',
            $totalPaid > 0 => 'partial',
            default => $zeroStatus,
        };

        $updateData = ['payment_status' => $status];

        // Purchase tracks paid_amount + balance_amount as explicit columns
        // (unlike Invoice which derives them from the payments relationship).
        if ($document instanceof Purchase) {
            $updateData['paid_amount']    = $totalPaid;
            $updateData['balance_amount'] = max(0, $grandTotal - $totalPaid);
        }

        $document->update($updateData);

        // Kasar (write-off) affects settlement_status only on Invoices — Purchases don't have this column.
        if ($document instanceof Invoice) {
            $this->writeOffService->syncInvoiceSettlementStatus($document);
        }
    }

    /**
     * Handle Indian Cheque Bounce or Payment Cancellation
     */
    public function cancelPayment(Payment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            $payment->update(['status' => 'cancelled']);

            if ($payment->paymentable) {
                $this->syncDocumentPaymentStatus($payment->paymentable);
            }

            return true;
        });
    }

    /**
     * Internal: Generate a unique PAY sequence
     */
    /**
     * Next payment number for today.
     *
     * Derived from the HIGHEST existing number, not from a row count. Counting
     * reuses any number left behind by a deleted payment: editing an invoice's
     * received amount to zero deletes its payment row, and the next edit then
     * regenerated the same number and hit the company_id + payment_number
     * unique index.
     *
     * lockForUpdate serialises concurrent callers so two payments saved in the
     * same instant cannot both read the same maximum.
     *
     * withTrashed is REQUIRED, not optional. Payment soft-deletes, but the
     * company_id + payment_number unique index ignores deleted_at — a
     * soft-deleted number stays permanently reserved in the index while
     * disappearing from ordinary queries. Without it the generator hands back
     * a number the database still considers taken.
     */
    protected function generatePaymentNumber(int $companyId): string
    {
        $prefix = 'PAY-'.date('Ymd');

        $latest = Payment::withTrashed()
            ->where('company_id', $companyId)
            ->where('payment_number', 'like', $prefix.'%')
            ->orderByDesc('payment_number')
            ->lockForUpdate()
            ->value('payment_number');

        $lastSeq = $latest ? (int) preg_replace('/.*?(\d+)$/', '$1', $latest) : 0;

        return $prefix.'-'.str_pad($lastSeq + 1, 4, '0', STR_PAD_LEFT);
    }
}
