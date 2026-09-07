<?php

namespace App\Http\Controllers\Admin\Project;

use App\Exceptions\Project\ProjectBillingException;
use App\Http\Controllers\Controller;
use App\Rules\TenantExists;
use App\Models\Payment;
use App\Models\Project\ProjectCharge;
use App\Models\Project\ProjectChargeAllocation;
use App\Services\Project\ChargeAllocationService;
use App\Services\Project\ProjectPaymentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Taking money from a client.
 *
 * The default path asks for one thing only: how much. Which bills it settles
 * is FIFO's job, not the user's — a shopkeeper recording "client gave 20,000"
 * should never be interrogated about allocation. Manual allocation exists, but
 * behind a deliberate step.
 */
class ProjectPaymentController extends Controller
{
    public function __construct(
        private readonly ProjectPaymentService $payments,
        private readonly ChargeAllocationService $allocations,
    ) {}

    /**
     * What the payment screen needs before the user types anything: how much
     * this client owes, what credit they already have, and the open charges in
     * the order FIFO would settle them.
     */
    public function context(Request $request)
    {
        $validated = $request->validate([
            'client_id' => ['required', TenantExists::make('clients', softDeletes: true)],
        ]);

        $clientId = (int) $validated['client_id'];

        $charges = ProjectCharge::query()
            ->where('client_id', $clientId)
            ->outstanding()
            ->fifo()
            ->get(['id', 'title', 'charge_date', 'due_date', 'total_amount', 'paid_amount', 'written_off_amount', 'status']);

        return response()->json([
            'success'     => true,
            'outstanding' => round((float) $charges->sum(fn ($c) => $c->balance_amount), 2),
            'credit'      => $this->allocations->clientCreditBalance($clientId),
            'charges'     => $charges->map(fn ($c) => [
                'id'       => $c->id,
                'title'    => $c->title,
                'due_date' => optional($c->due_date)->toDateString(),
                'balance'  => $c->balance_amount,
                'overdue'  => $c->is_overdue,
            ]),
        ]);
    }

    /**
     * Record money received.
     *
     * allocations[] is optional and only sent by the advanced screen. Without
     * it the amount is applied FIFO, and anything left over stays as credit.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id'         => ['required', TenantExists::make('clients', softDeletes: true)],
            'amount'            => 'required|numeric|min:0.01',
            'payment_method_id' => ['required', TenantExists::make('payment_methods')],
            'payment_date'      => 'nullable|date',
            'reference'         => 'nullable|string|max:255',
            'notes'             => 'nullable|string',
            'idempotency_key'   => 'nullable|uuid',

            // Advanced mode only.
            'allocations'   => 'nullable|array',
            'allocations.*' => 'numeric|min:0',

            // Record the money but apply none of it — a pure advance.
            'allocate' => 'nullable|boolean',
        ]);

        try {
            $payment = $this->payments->record($validated);
            $applied = $payment->getAttribute('project_allocations') ?? collect();

            $appliedTotal = round((float) $applied->sum('amount'), 2);
            $leftover     = round((float) $payment->amount - $appliedTotal, 2);

            $allocationError = $payment->getAttribute('allocation_error');

            return response()->json([
                'success' => true,
                'message' => match (true) {
                    $allocationError !== null => 'Payment recorded, but it could not be applied to any charge. '
                        .'The full amount is sitting as client credit — allocate it manually from a charge.',
                    $leftover > 0 => 'Payment recorded. '.number_format($leftover, 2).' is left on the client\'s account as credit.',
                    default => 'Payment recorded and applied.',
                },
                'payment' => $payment,
                // Shown back to the user so FIFO is transparent rather than
                // magic: "9,000 to 2026 hosting, 11,000 to 2027 hosting".
                'applied' => $applied->map(fn (ProjectChargeAllocation $a) => [
                    'charge_id' => $a->charge_id,
                    'title'     => $a->charge?->title,
                    'amount'    => (float) $a->amount,
                ])->values(),
                'credit' => $this->allocations->clientCreditBalance((int) $validated['client_id']),
            ]);
        } catch (ProjectBillingException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Exception $e) {
            // Enough context to find the row and reproduce it. The bare message
            // alone made a failed payment impossible to trace.
            Log::error('Project payment failed', [
                'client_id' => $validated['client_id'] ?? null,
                'amount'    => $validated['amount'] ?? null,
                'error'     => $e->getMessage(),
                'file'      => $e->getFile().':'.$e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => config('app.debug')
                    ? 'Could not record the payment: '.$e->getMessage()
                    : 'Could not record the payment.',
            ], 422);
        }
    }

    /**
     * Apply a client's existing credit to specific charges.
     *
     * This is the advanced screen acting on money already in hand rather than
     * on a new payment.
     */
    public function allocate(Request $request, Payment $payment)
    {
        abort_if($payment->company_id !== Auth::user()->company_id, 403);

        $validated = $request->validate([
            'allocations'   => 'required|array|min:1',
            'allocations.*' => 'numeric|min:0',
        ]);

        try {
            $created = $this->allocations->manualAllocate($payment, $validated['allocations']);

            return response()->json([
                'success' => true,
                'message' => $created->count().' allocation(s) recorded.',
                'applied' => $created->map(fn ($a) => [
                    'charge_id' => $a->charge_id,
                    'amount'    => (float) $a->amount,
                ])->values(),
            ]);
        } catch (ProjectBillingException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Undo one allocation without touching the payment.
     *
     * Used when FIFO applied money to the wrong bill and the user wants to move
     * it, rather than reverse the whole payment.
     */
    public function reverseAllocation(Request $request, ProjectChargeAllocation $allocation)
    {
        abort_if($allocation->company_id !== Auth::user()->company_id, 403);

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
            $this->allocations->reverseAllocation($allocation, $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Allocation reversed. The amount is back on the client\'s credit.',
            ]);
        } catch (ProjectBillingException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /** Bounced cheque, or an entry made in error. Never a delete. */
    public function reverse(Request $request, Payment $payment)
    {
        abort_if($payment->company_id !== Auth::user()->company_id, 403);

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
            $this->payments->reverse($payment, $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Payment reversed. Every charge it settled has been reopened.',
            ]);
        } catch (ProjectBillingException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /** Hand unapplied credit back to the client. */
    public function refund(Request $request)
    {
        $validated = $request->validate([
            'client_id'         => ['required', TenantExists::make('clients', softDeletes: true)],
            'amount'            => 'required|numeric|min:0.01',
            'payment_method_id' => ['required', TenantExists::make('payment_methods')],
            'payment_date'      => 'nullable|date',
            'reference'         => 'nullable|string|max:255',
            'reason'            => 'required|string|max:255',
        ]);

        try {
            $refund = $this->payments->refund((int) $validated['client_id'], $validated);

            return response()->json([
                'success' => true,
                'message' => 'Refund recorded.',
                'payment' => $refund,
                'credit'  => $this->allocations->clientCreditBalance((int) $validated['client_id']),
            ]);
        } catch (ProjectBillingException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}