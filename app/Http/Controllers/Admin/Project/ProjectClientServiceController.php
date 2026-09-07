<?php

namespace App\Http\Controllers\Admin\Project;

use App\Enums\Project\BillingCycle;
use App\Exceptions\Project\ProjectBillingException;
use App\Http\Controllers\Controller;
use App\Models\Project\ProjectClientService;
use App\Rules\TenantExists;
use App\Services\Project\ClientServiceRenewalService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Services sold to clients — hosting, domains, maintenance.
 *
 * This screen answers "is it still valid?", never "has it been paid for?".
 * Renewing moves the entitlement forward and raises a new charge; the previous
 * period's charge is never touched, which is what lets three unpaid years sit
 * side by side and be settled by one payment.
 */
class ProjectClientServiceController extends Controller
{
    public function __construct(
        private readonly ClientServiceRenewalService $renewals,
    ) {}    

    /** Sell a service. Raises the first period's charge unless told not to. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id'     => ['required', TenantExists::make('clients', softDeletes: true)],
            'project_id'    => ['nullable', TenantExists::make('projects', softDeletes: true)],
            'service_id'    => ['nullable', TenantExists::make('project_services')],
            'name'          => 'required_without:service_id|nullable|string|max:255',
            'billing_cycle' => 'required|string|in:'.implode(',', array_column(BillingCycle::cases(), 'value')),
            'duration_days' => 'nullable|integer|min:1|required_if:billing_cycle,custom',
            'price'         => 'required|numeric|min:0',
            'tax_rate'      => 'nullable|numeric|min:0|max:100',
            'started_at'    => 'nullable|date',
            'auto_renew'    => 'nullable|boolean',
            'notes'         => 'nullable|string',
            'raise_charge'  => 'nullable|boolean',
        ]);

        try {
            $clientService = $this->renewals->sell($validated);

            return response()->json([
                'success'        => true,
                'message'        => 'Service added for this client.',
                'client_service' => $clientService->load('client:id,name'),
            ]);
        } catch (ProjectBillingException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Exception $e) {
            Log::error('Client service create failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Could not add the service.'], 422);
        }
    }

    /**
     * Edit what the NEXT renewal will look like.
     *
     * Changing the price here never affects a period already billed — that
     * amount is frozen on its own charge.
     */
    public function update(Request $request, ProjectClientService $clientService)
    {
        abort_if($clientService->company_id !== Auth::user()->company_id, 403);

        $validated = $request->validate([
            'name'          => 'sometimes|required|string|max:255',
            'price'         => 'sometimes|required|numeric|min:0',
            'tax_rate'      => 'nullable|numeric|min:0|max:100',
            'billing_cycle' => 'sometimes|required|string|in:'.implode(',', array_column(BillingCycle::cases(), 'value')),
            'duration_days' => 'nullable|integer|min:1|required_if:billing_cycle,custom',
            'auto_renew'    => 'nullable|boolean',
            'notes'         => 'nullable|string',
        ]);

        if (($validated['billing_cycle'] ?? null) !== BillingCycle::Custom->value) {
            $validated['duration_days'] = null;
        }

        $clientService->update($validated);

        return response()->json([
            'success'        => true,
            'message'        => 'Service updated. Periods already billed are unchanged.',
            'client_service' => $clientService->fresh(),
        ]);
    }

    /**
     * Roll into the next period and raise its charge.
     *
     * period_start is normally derived. It becomes required when the client
     * skipped one or more cycles, because auto-generating those would bill them
     * for months they never received.
     */
    public function renew(Request $request, ProjectClientService $clientService)
    {
        abort_if($clientService->company_id !== Auth::user()->company_id, 403);

        $validated = $request->validate([
            'period_start' => 'nullable|date',
            'price'        => 'nullable|numeric|min:0',
            'tax_rate'     => 'nullable|numeric|min:0|max:100',
            'due_date'     => 'nullable|date',
            'notes'        => 'nullable|string',
        ]);

        try {
            $charge = $this->renewals->renew($clientService, $validated);

            return response()->json([
                'success'        => true,
                'message'        => 'Renewed. A new charge has been raised for the period.',
                'charge'         => $charge,
                'client_service' => $clientService->fresh(),
            ]);
        } catch (ProjectBillingException $e) {
            // Usually the skipped-period guard. The message names the date the
            // user has to choose, so it is actionable rather than a dead end.
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Stop a service permanently.
     *
     * Existing charges are left alone: cancelling an entitlement does not erase
     * what was owed for periods already served. Forgive those separately with a
     * write-off if that is the intent.
     */
    public function cancel(Request $request, ProjectClientService $clientService)
    {
        abort_if($clientService->company_id !== Auth::user()->company_id, 403);

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
            $this->renewals->cancel($clientService, $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Service cancelled. Charges already raised are unaffected.',
            ]);
        } catch (ProjectBillingException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}