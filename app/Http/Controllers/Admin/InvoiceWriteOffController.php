<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInvoiceWriteOffRequest;
use App\Models\Invoice;
use App\Models\InvoiceWriteOff;
use App\Services\InvoiceWriteOffService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceWriteOffController extends Controller
{
    protected InvoiceWriteOffService $writeOffService;

    public function __construct(InvoiceWriteOffService $writeOffService)
    {
        $this->writeOffService = $writeOffService;
    }

    /**
     * "Mark as Kasar" — creates and confirms a write-off in one step,
     * matching the Quick Payment modal's single-step UX.
     */
    public function store(StoreInvoiceWriteOffRequest $request, Invoice $invoice)
    {
        abort_if($invoice->company_id !== Auth::user()->company_id, 403);

        if ($invoice->status === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Cannot write off a cancelled invoice.'], 422);
        }

        try {
            $writeOff = DB::transaction(function () use ($request, $invoice) {
                $draft = $this->writeOffService->createWriteOff($invoice, [
                    'amount' => $request->amount,
                    'write_off_date' => $request->write_off_date ?? now(),
                    'reason' => $request->reason,
                    'notes' => $request->notes,
                ]);

                return $this->writeOffService->confirmWriteOff($draft);
            });

            return response()->json([
                'success' => true,
                'message' => "Kasar of ₹{$writeOff->amount} recorded successfully.",
            ]);
        } catch (Exception $e) {
            Log::error('Invoice Write-Off Failed', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Cancel (reverse) a confirmed write-off — re-opens the invoice balance.
     */
    public function cancel(Request $request, InvoiceWriteOff $invoiceWriteOff)
    {
        abort_if($invoiceWriteOff->company_id !== Auth::user()->company_id, 403);

        try {
            $this->writeOffService->cancelWriteOff(
                $invoiceWriteOff,
                $request->input('cancellation_reason')
            );

            return response()->json(['success' => true, 'message' => 'Write-off cancelled. Balance has been re-opened.']);
        } catch (Exception $e) {
            Log::error('Invoice Write-Off Cancel Failed', ['write_off_id' => $invoiceWriteOff->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}