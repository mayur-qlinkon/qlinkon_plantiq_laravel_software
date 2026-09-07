<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StorePipelineRequest;
use App\Models\CrmPipeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class CrmPipelineController extends Controller
{
    // ════════════════════════════════════════════════════
    //  INDEX — list all pipelines
    //  GET /admin/crm/pipelines
    // ════════════════════════════════════════════════════

    public function index()
    {
        $companyId = Auth::user()->company_id;

        $pipelines = CrmPipeline::where('company_id', $companyId)
            ->withCount('leads')
            ->with(['stages' => fn ($q) => $q->ordered()])
            ->ordered()
            ->get();

        return view('admin.crm.pipelines', compact('pipelines'));
    }

    // ════════════════════════════════════════════════════
    //  STORE
    //  POST /admin/crm/pipelines
    // ════════════════════════════════════════════════════

    public function store(StorePipelineRequest $request): JsonResponse
    {
        $companyId = Auth::user()->company_id;

        try {
            $pipeline = CrmPipeline::create(array_merge(
                $request->validated(),
                ['company_id' => $companyId]
            ));

            Log::info('[CrmPipeline] Created', [
                'pipeline_id' => $pipeline->id,
                'name' => $pipeline->name,
                'by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Pipeline \"{$pipeline->name}\" created.",
                'pipeline' => $pipeline->loadCount('leads'),
                'redirect_stages' => route('admin.crm.stages.index', $pipeline->id),
            ]);

        } catch (Throwable $e) {
            Log::error('[CrmPipeline] Store failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to create pipeline.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  UPDATE
    //  PUT /admin/crm/pipelines/{pipeline}
    // ════════════════════════════════════════════════════

    public function update(StorePipelineRequest $request, CrmPipeline $pipeline): JsonResponse
    {
        $this->authorizePipeline($pipeline);

        try {
            $pipeline->update($request->validated());

            Log::info('[CrmPipeline] Updated', [
                'pipeline_id' => $pipeline->id,
                'by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Pipeline \"{$pipeline->name}\" updated.",
                'pipeline' => $pipeline->fresh()->loadCount('leads'),
            ]);

        } catch (Throwable $e) {
            Log::error('[CrmPipeline] Update failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to update pipeline.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  DESTROY
    //  DELETE /admin/crm/pipelines/{pipeline}
    // ════════════════════════════════════════════════════

    public function destroy(CrmPipeline $pipeline): JsonResponse
    {
        $this->authorizePipeline($pipeline);

        // Prevent deleting if leads exist
        if ($pipeline->leads()->exists()) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete — this pipeline has {$pipeline->leads()->count()} lead(s). Move them first.",
            ], 422);
        }

        // Prevent deleting last pipeline
        $total = CrmPipeline::where('company_id', Auth::user()->company_id)->count();
        if ($total <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the last pipeline.',
            ], 422);
        }

        try {
            $name = $pipeline->name;
            $pipeline->delete();

            Log::info('[CrmPipeline] Deleted', ['name' => $name, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => "Pipeline \"{$name}\" deleted.",
            ]);

        } catch (Throwable $e) {
            Log::error('[CrmPipeline] Delete failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to delete.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  SET DEFAULT — AJAX
    //  POST /admin/crm/pipelines/{pipeline}/default
    // ════════════════════════════════════════════════════

    public function setDefault(CrmPipeline $pipeline): JsonResponse
    {
        $this->authorizePipeline($pipeline);

        try {
            // CrmPipeline::booted() handles un-setting others automatically
            $pipeline->update(['is_default' => true]);

            Log::info('[CrmPipeline] Set as default', ['pipeline_id' => $pipeline->id]);

            return response()->json([
                'success' => true,
                'message' => "\"{$pipeline->name}\" is now the default pipeline.",
            ]);

        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — Tenant isolation
    // ════════════════════════════════════════════════════

    private function authorizePipeline(CrmPipeline $pipeline): void
    {
        if ($pipeline->company_id !== Auth::user()->company_id) {
            abort(403, 'Access denied.');
        }
    }
}
