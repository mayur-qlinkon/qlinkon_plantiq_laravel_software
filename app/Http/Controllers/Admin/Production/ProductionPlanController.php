<?php

namespace App\Http\Controllers\Admin\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Production\StoreProductionPlanRequest;
use App\Http\Requests\Admin\Production\UpdateProductionPlanRequest;
use App\Models\Production\ProductionPlan;
use App\Services\Production\ProductionPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductionPlanController extends Controller
{
    public function __construct(protected ProductionPlanService $service) {}

    // ════════════════════════════════════════════════════
    //  INDEX — SPA Base View
    //  GET /admin/production/plans
    // ════════════════════════════════════════════════════
    public function index(Request $request)
    {
        $search = trim($request->get('q', ''));

        $plans = ProductionPlan::where('company_id', Auth::user()->company_id)
            ->when($search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%");
            })
            // SPA ke liye items aur product pehle se load kar rahe hain
            ->with(['items.product', 'createdBy', 'confirmedBy']) 
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.production.plans.index', compact('plans'));
    }

    // ════════════════════════════════════════════════════
    //  SHOW — Fetch Fresh Plan Data for Modal
    //  GET /admin/production/plans/{plan}
    // ════════════════════════════════════════════════════
    public function show(Request $request, ProductionPlan $plan)
    {
        $this->authorizePlan($plan);
        $plan->load(['items.product', 'createdBy', 'confirmedBy']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'plan' => $plan,
            ]);
        }

        // There is no standalone show page — the plans screen opens a plan in
        // its own modal, and this endpoint exists to feed it JSON. A direct
        // browser hit used to render admin.production.plans.show, a view that
        // was never created, so it 500'd. Send the user to the list instead.
        return redirect()->route('admin.production.plans.index');
    }

    // ════════════════════════════════════════════════════
    //  STORE — Create via AJAX
    //  POST /admin/production/plans
    // ════════════════════════════════════════════════════
    public function store(StoreProductionPlanRequest $request): JsonResponse
    {
        try {
            $plan = $this->service->create($request->validated());

            Log::info('[ProductionPlan] Created', ['plan_id' => $plan->id, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => "Production Plan \"{$plan->title}\" created.",
                'plan' => $plan, // Return the fresh plan to push into Alpine array
            ]);

        } catch (Throwable $e) {
            Log::error('[ProductionPlan] Store failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to create Production Plan.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  UPDATE — Edit via AJAX
    //  PUT /admin/production/plans/{plan}
    // ════════════════════════════════════════════════════
    public function update(UpdateProductionPlanRequest $request, ProductionPlan $plan): JsonResponse
    {
        $this->authorizePlan($plan);

        try {
            $updated = $this->service->update($plan, $request->validated());

            Log::info('[ProductionPlan] Updated', ['plan_id' => $plan->id, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => 'Production Plan updated.',
                'plan' => $updated, // Return updated plan to replace in Alpine array
            ]);

        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('[ProductionPlan] Update failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to update Production Plan.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  DESTROY — Delete via AJAX
    //  DELETE /admin/production/plans/{plan}
    // ════════════════════════════════════════════════════
    public function destroy(ProductionPlan $plan): JsonResponse
    {
        $this->authorizePlan($plan);

        try {
            $title = $plan->title;
            $this->service->delete($plan);

            Log::info('[ProductionPlan] Deleted', ['title' => $title, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => "Production Plan \"{$title}\" deleted.",
            ]);

        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('[ProductionPlan] Delete failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to delete Production Plan.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  CONFIRM
    //  POST /admin/production/plans/{plan}/confirm
    // ════════════════════════════════════════════════════
    public function confirm(ProductionPlan $plan): JsonResponse
    {
        $this->authorizePlan($plan);

        try {
            $confirmed = $this->service->confirm($plan);

            Log::info('[ProductionPlan] Confirmed', ['plan_id' => $plan->id, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => "Production Plan \"{$plan->title}\" confirmed.",
                'plan' => $confirmed,
            ]);

        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('[ProductionPlan] Confirm failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to confirm Production Plan.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  CLOSED
    //  POST /admin/production/plans/{plan}/closed
    // ════════════════════════════════════════════════════
    public function close(ProductionPlan $plan): JsonResponse
    {
        $this->authorizePlan($plan);

        try {
            $closed = $this->service->close($plan);

            Log::info('[ProductionPlan] Completed', ['plan_id' => $plan->id, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => "Production Plan \"{$plan->title}\" marked as closed.",
                'plan' => $closed,
            ]);

        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('[ProductionPlan] Close failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to close Production Plan.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  CANCEL
    //  POST /admin/production/plans/{plan}/cancel
    // ════════════════════════════════════════════════════
    public function cancel(ProductionPlan $plan): JsonResponse
    {
        $this->authorizePlan($plan);

        try {
            $cancelled = $this->service->cancel($plan);

            Log::info('[ProductionPlan] Cancelled', ['plan_id' => $plan->id, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => "Production Plan \"{$plan->title}\" cancelled.",
                'plan' => $cancelled,
            ]);

        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('[ProductionPlan] Cancel failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to cancel Production Plan.'], 500);
        }
    }

    private function authorizePlan(ProductionPlan $plan): void
    {
        if ($plan->company_id !== Auth::user()->company_id) {
            abort(403, 'Access denied.');
        }
    }
}