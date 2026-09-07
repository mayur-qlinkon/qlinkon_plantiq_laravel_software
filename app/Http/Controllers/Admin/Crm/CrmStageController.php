<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreStageRequest;
use App\Models\CrmPipeline;
use App\Models\CrmStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class CrmStageController extends Controller
{
    // ════════════════════════════════════════════════════
    //  INDEX — stages list for a pipeline
    //  GET /admin/crm/pipelines/{pipeline}/stages
    // ════════════════════════════════════════════════════

    public function index(CrmPipeline $pipeline)
    {
        $this->authorizePipeline($pipeline);

        $stages = $pipeline->stages()
            ->withCount('leads')
            ->ordered()
            ->get();

        return view('admin.crm.stages', compact('pipeline', 'stages'));
    }

    // ════════════════════════════════════════════════════
    //  STORE — AJAX (inline form on stages page)
    //  POST /admin/crm/pipelines/{pipeline}/stages
    // ════════════════════════════════════════════════════

    public function store(StoreStageRequest $request, CrmPipeline $pipeline): JsonResponse
    {
        $this->authorizePipeline($pipeline);

        // ── Business rule: only one Won and one Lost per pipeline ──
        if ($request->boolean('is_won')) {
            $existing = CrmStage::where('crm_pipeline_id', $pipeline->id)
                ->where('is_won', true)->exists();
            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'This pipeline already has a Won stage.',
                ], 422);
            }
        }

        if ($request->boolean('is_lost')) {
            $existing = CrmStage::where('crm_pipeline_id', $pipeline->id)
                ->where('is_lost', true)->exists();
            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'This pipeline already has a Lost stage.',
                ], 422);
            }
        }

        try {
            // Auto sort_order — add to end if not specified
            $maxOrder = CrmStage::where('crm_pipeline_id', $pipeline->id)->max('sort_order') ?? 0;

            $stage = CrmStage::create(array_merge(
                $request->validated(),
                [
                    'company_id' => Auth::user()->company_id,
                    'crm_pipeline_id' => $pipeline->id,
                    'sort_order' => $request->input('sort_order', $maxOrder + 1),
                ]
            ));

            Log::info('[CrmStage] Created', [
                'stage_id' => $stage->id,
                'name' => $stage->name,
                'pipeline_id' => $pipeline->id,
                'by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Stage \"{$stage->name}\" added.",
                'stage' => $stage->load('pipeline:id,name'),
            ]);

        } catch (Throwable $e) {
            Log::error('[CrmStage] Store failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to create stage.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  UPDATE — AJAX (inline edit)
    //  PUT /admin/crm/pipelines/{pipeline}/stages/{stage}
    // ════════════════════════════════════════════════════

    public function update(StoreStageRequest $request, CrmPipeline $pipeline, CrmStage $stage): JsonResponse
    {
        $this->authorizePipeline($pipeline);
        $this->authorizeStage($stage, $pipeline);

        // ── Won/Lost uniqueness check (excluding self) ──
        if ($request->boolean('is_won') && ! $stage->is_won) {
            $existing = CrmStage::where('crm_pipeline_id', $pipeline->id)
                ->where('is_won', true)->where('id', '!=', $stage->id)->exists();
            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Another Won stage already exists in this pipeline.',
                ], 422);
            }
        }

        if ($request->boolean('is_lost') && ! $stage->is_lost) {
            $existing = CrmStage::where('crm_pipeline_id', $pipeline->id)
                ->where('is_lost', true)->where('id', '!=', $stage->id)->exists();
            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Another Lost stage already exists in this pipeline.',
                ], 422);
            }
        }

        try {
            $stage->update($request->validated());

            Log::info('[CrmStage] Updated', ['stage_id' => $stage->id, 'by' => Auth::id()]);

            // Load leads count specifically to prevent Javascript undefined crashes on UI updates
            $stage->loadCount('leads');

            return response()->json([
                'success' => true,
                'message' => "Stage \"{$stage->name}\" updated.",
                'stage' => $stage,
            ]);

        } catch (Throwable $e) {
            Log::error('[CrmStage] Update failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to update.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  DESTROY — AJAX
    //  DELETE /admin/crm/pipelines/{pipeline}/stages/{stage}
    // ════════════════════════════════════════════════════

    public function destroy(CrmPipeline $pipeline, CrmStage $stage): JsonResponse
    {
        $this->authorizePipeline($pipeline);
        $this->authorizeStage($stage, $pipeline);

        // Prevent deleting if leads are in this stage
        if ($stage->leads()->exists()) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete — {$stage->leads()->count()} lead(s) are in this stage. Move them first.",
            ], 422);
        }

        // Prevent deleting last stage in pipeline
        $stageCount = CrmStage::where('crm_pipeline_id', $pipeline->id)->count();
        if ($stageCount <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the last stage in a pipeline.',
            ], 422);
        }

        try {
            $name = $stage->name;
            $stage->delete();

            Log::info('[CrmStage] Deleted', ['name' => $name, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => "Stage \"{$name}\" deleted.",
            ]);

        } catch (Throwable $e) {
            Log::error('[CrmStage] Delete failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to delete.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  REORDER — SortableJS drag drop
    //  POST /admin/crm/pipelines/{pipeline}/stages/reorder
    // ════════════════════════════════════════════════════

    public function reorder(Request $request, CrmPipeline $pipeline): JsonResponse
    {
        $this->authorizePipeline($pipeline);

        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        try {
            foreach ($request->order as $sortOrder => $stageId) {
                CrmStage::where('id', $stageId)
                    ->where('crm_pipeline_id', $pipeline->id) // tenant safety
                    ->where('company_id', Auth::user()->company_id)
                    ->update(['sort_order' => $sortOrder + 1]);
            }

            Log::info('[CrmStage] Reordered', [
                'pipeline_id' => $pipeline->id,
                'by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stages reordered.',
            ]);

        } catch (Throwable $e) {
            Log::error('[CrmStage] Reorder failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to reorder.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — Tenant guards
    // ════════════════════════════════════════════════════

    private function authorizePipeline(CrmPipeline $pipeline): void
    {
        if ($pipeline->company_id !== Auth::user()->company_id) {
            abort(403, 'Access denied.');
        }
    }

    private function authorizeStage(CrmStage $stage, CrmPipeline $pipeline): void
    {
        if ($stage->crm_pipeline_id !== $pipeline->id || $stage->company_id !== Auth::user()->company_id) {
            abort(403, 'Stage does not belong to this pipeline.');
        }
    }
}
