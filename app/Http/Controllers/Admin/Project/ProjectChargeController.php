<?php

namespace App\Http\Controllers\Admin\Project;

use App\Enums\Project\ChargeType;
use App\Exceptions\Project\ProjectBillingException;
use App\Http\Controllers\Controller;
use App\Models\Project\ProjectCharge;
use App\Rules\TenantExists;
use App\Services\Project\ChargeAllocationService;
use App\Services\Project\ProjectChargeService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Charges are the primary path in this module.
 *
 * Most work a tenant bills for is a one-off job, not a tracked project — a
 * project only earns its own record when the amount is large enough to be
 * worth following over time. So raising a charge must be reachable on its own,
 * without creating a project first.
 */
class ProjectChargeController extends Controller
{
    public function __construct(
        private readonly ProjectChargeService $charges,
        private readonly ChargeAllocationService $allocations,
    ) {}   

    /**
     * Raise a charge. project_id and client_service_id are both optional — a
     * standalone job needs neither.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id'         => ['required', TenantExists::make('clients', softDeletes: true)],
            'project_id'        => ['nullable', TenantExists::make('projects', softDeletes: true)],
            'client_service_id' => ['nullable', TenantExists::make('project_client_services', softDeletes: true)],
            'service_id'        => ['nullable', TenantExists::make('project_services')],
            'type'              => 'nullable|string|in:'.implode(',', array_column(ChargeType::cases(), 'value')),
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'charge_date'       => 'nullable|date',
            'due_date'          => 'nullable|date|after_or_equal:charge_date',
            'subtotal'          => 'required|numeric|min:0.01',
            'discount_amount'   => 'nullable|numeric|min:0',
            'tax_rate'          => 'nullable|numeric|min:0|max:100',
            'reference'         => 'nullable|string|max:255',
            'notes'             => 'nullable|string',
        ]);

        try {
            $charge = $this->charges->create($validated);

            return response()->json([
                'success' => true,
                'message' => $charge->paid_amount > 0
                    ? 'Charge raised. Existing client credit has been applied to it.'
                    : 'Charge raised successfully.',
                'charge'  => $charge->load('client:id,name'),
            ]);
        } catch (ProjectBillingException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Exception $e) {
            Log::error('Charge create failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Could not raise the charge.'], 422);
        }
    }

    /**
     * Edit a charge that has not been settled yet.
     *
     * The service rejects this once any money is against it, so the UI should
     * hide the edit button when $charge->isEditable() is false rather than
     * letting the user discover the rule by hitting an error.
     */
    public function update(Request $request, ProjectCharge $charge)
    {
        abort_if($charge->company_id !== Auth::user()->company_id, 403);

        $validated = $request->validate([
            'title'           => 'sometimes|required|string|max:255',
            'description'     => 'nullable|string',
            'charge_date'     => 'nullable|date',
            'due_date'        => 'nullable|date',
            'subtotal'        => 'sometimes|required|numeric|min:0.01',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_rate'        => 'nullable|numeric|min:0|max:100',
            'reference'       => 'nullable|string|max:255',
            'notes'           => 'nullable|string',
        ]);

        try {
            $charge = $this->charges->update($charge, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Charge updated.',
                'charge'  => $charge,
            ]);
        } catch (ProjectBillingException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Cancel a charge that should never have existed.
     *
     * Any money applied is released back to the client as credit. Use writeOff
     * instead when the debt was real but is being forgiven — reporting must be
     * able to tell "we billed this by mistake" from "we let this go".
     */
    public function cancel(Request $request, ProjectCharge $charge)
    {
        abort_if($charge->company_id !== Auth::user()->company_id, 403);

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
            $this->charges->cancel($charge, $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Charge cancelled. Any money applied has been returned to the client\'s credit.',
            ]);
        } catch (ProjectBillingException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /** Kasar — forgive part or all of a real debt. */
    public function writeOff(Request $request, ProjectCharge $charge)
    {
        abort_if($charge->company_id !== Auth::user()->company_id, 403);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ]);

        try {
            $this->allocations->writeOff($charge, (float) $validated['amount'], $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Write-off recorded.',
                'charge'  => $charge->refresh(),
            ]);
        } catch (ProjectBillingException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}