<?php

namespace App\Http\Controllers\Admin\Project;

use App\Enums\Project\BillingCycle;
use App\Http\Controllers\Controller;
use App\Models\Project\ProjectService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * The service catalog — a price list, not financial truth.
 *
 * Editing anything here only changes what future sales default to. Services
 * already sold carry their own snapshot, so a price rise can never rewrite a
 * client's existing entitlement or any charge already raised.
 */
class ProjectServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = ProjectService::where('company_id', Auth::user()->company_id)
            ->orderBy('name');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.trim($request->search).'%');
        }

        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        if ($request->filled('billing_cycle')) {
            $query->where('billing_cycle', $request->billing_cycle);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $services = $query->paginate(50)->withQueryString();
        $cycles   = BillingCycle::options();

        if ($request->ajax()) {
            return view('admin.projects.services.index', compact('services', 'cycles'))->render();
        }

        return view('admin.projects.services.index', compact('services', 'cycles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        // New catalog entries only: default to 18% when tax_rate was never sent.
        // Applied here, not inside normalise(), so update() never re-forces a
        // default onto a rate the user explicitly cleared to zero.
        if (! array_key_exists('tax_rate', $validated) || $validated['tax_rate'] === null) {
            $validated['tax_rate'] = 18;
        }

        $service = ProjectService::create($this->normalise($validated));

        return response()->json([
            'success' => true,
            'message' => 'Service added to the catalog.',
            'service' => $service,
        ]);
    }

    public function update(Request $request, ProjectService $projectService)
    {
        abort_if($projectService->company_id !== Auth::user()->company_id, 403);

        $validated = $request->validate($this->rules($projectService->id));

        // An absent/cleared tax_rate on edit means "no GST", not "use 18%
        // default". Overwriting it here was what silently re-added GST every
        // time a GST-free service was saved.
        if (! array_key_exists('tax_rate', $validated) || $validated['tax_rate'] === null) {
            $validated['tax_rate'] = 0;
        }

        $projectService->update($this->normalise($validated));

        return response()->json([
            'success' => true,
            'message' => 'Service updated. Existing client services keep their original pricing.',
            'service' => $projectService->fresh(),
        ]);
    }

    /**
     * Catalog entries that have been sold are deactivated rather than deleted.
     *
     * Deleting would null the service_id on every client service and charge
     * that referenced it, quietly severing the link back to what was sold.
     */
    public function destroy(ProjectService $projectService)
    {
        abort_if($projectService->company_id !== Auth::user()->company_id, 403);

        try {
            if ($projectService->clientServices()->exists()) {
                $projectService->update(['is_active' => false]);

                return response()->json([
                    'success' => true,
                    'message' => 'This service has been sold to clients, so it was deactivated instead of deleted.',
                ]);
            }

            $projectService->delete();

            return response()->json(['success' => true, 'message' => 'Service removed from the catalog.']);
        } catch (Exception $e) {
            Log::error('Project service delete failed', [
                'service_id' => $projectService->id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => config('app.debug')
                    ? 'Could not remove the service: '.$e->getMessage()
                    : 'Could not remove the service.',
            ], 422);
        }
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('project_services', 'name')
                    ->where('company_id', Auth::user()->company_id)
                    ->ignore($ignoreId),
            ],
            'service_type'  => 'required|string|max:50',
            'billing_cycle' => 'required|string|in:'.implode(',', array_column(BillingCycle::cases(), 'value')),
            // Only meaningful for a custom cycle, and required there — without
            // it a custom period has no end date and can never be renewed.
            'duration_days' => 'nullable|integer|min:1|required_if:billing_cycle,custom',
            'price'         => 'required|numeric|min:0',
            'tax_rate'      => 'nullable|numeric|min:0|max:100',
            'description'   => 'nullable|string',
            'is_active'     => 'nullable|boolean',
        ];
    }

    private function normalise(array $data): array
    {
        $data['company_id'] = Auth::user()->company_id;
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        // duration_days belongs to custom cycles only; leaving a stale value on
        // a yearly service would silently override the cycle's own period math.
        if (($data['billing_cycle'] ?? null) !== BillingCycle::Custom->value) {
            $data['duration_days'] = null;
        }

        return $data;
    }
}