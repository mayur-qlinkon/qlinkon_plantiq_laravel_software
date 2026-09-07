<?php

namespace App\Http\Controllers\Admin\Production;

use App\Enums\Production\ActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Production\StoreActivityTemplateRequest;
use App\Models\Product;
use App\Models\Production\ActivityTemplate;
use App\Models\Production\PlantBatch;
use App\Services\Production\ActivityTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ActivityTemplateController extends Controller
{
    public function __construct(protected ActivityTemplateService $service)
    {
    }

    // ════════════════════════════════════════════════════
    //  INDEX — list page (Blade view)
    //  GET /admin/production/activity-templates
    // ════════════════════════════════════════════════════

    public function index()
    {
        $companyId = Auth::user()->company_id;

        $templates = ActivityTemplate::with(['product', 'creator'])
            ->where('company_id', $companyId)
            ->active()
            ->orderByRaw('product_id IS NULL DESC') // Global Defaults on top
            ->orderBy('product_id')
            ->ordered() // Uses sort_order asc
            ->get();

        // Only products used in actual Plant Batches as species
        $speciesIds = PlantBatch::where('company_id', $companyId)
            ->whereNotNull('product_id')
            ->distinct()
            ->pluck('product_id');

        $products = Product::where('company_id', $companyId)
            ->whereIn('id', $speciesIds)
            ->orderBy('name')
            ->get();

        $activityTypes = ActivityType::options();

        return view('admin.production.activity-templates.index', compact('templates', 'products', 'activityTypes'));
    }

    // ════════════════════════════════════════════════════
    //  STORE
    //  POST /admin/production/activity-templates
    // ════════════════════════════════════════════════════

    public function store(StoreActivityTemplateRequest $request): JsonResponse
    {
        try {
            $template = $this->service->create(array_merge(
                $request->validated(),
                [
                    'company_id' => Auth::user()->company_id,
                    'created_by' => Auth::id(),
                ]
            ));

            $template->load('product');

            Log::info('[ActivityTemplate] Created', ['template_id' => $template->id, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => 'Activity template successfully created.',
                'template' => $template,
            ]);

        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);

        } catch (Throwable $e) {
            Log::error('[ActivityTemplate] Store failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to create template.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  UPDATE
    //  PUT /admin/production/activity-templates/{activityTemplate}
    // ════════════════════════════════════════════════════

    public function update(StoreActivityTemplateRequest $request, ActivityTemplate $activityTemplate): JsonResponse
    {
        $this->authorizeTemplate($activityTemplate);

        try {
            $updatedTemplate = $this->service->update($activityTemplate, $request->validated());
            $updatedTemplate->load('product');

            Log::info('[ActivityTemplate] Updated', ['template_id' => $activityTemplate->id, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => 'Activity template updated successfully.',
                'template' => $updatedTemplate,
            ]);

        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);

        } catch (Throwable $e) {
            Log::error('[ActivityTemplate] Update failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to update template.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  DESTROY
    //  DELETE /admin/production/activity-templates/{activityTemplate}
    // ════════════════════════════════════════════════════

    public function destroy(ActivityTemplate $activityTemplate): JsonResponse
    {
        $this->authorizeTemplate($activityTemplate);

        try {
            $this->service->delete($activityTemplate);

            Log::info('[ActivityTemplate] Deleted', ['template_id' => $activityTemplate->id, 'by' => Auth::id()]);

            return response()->json(['success' => true, 'message' => 'Template successfully deleted.']);

        } catch (Throwable $e) {
            Log::error('[ActivityTemplate] Destroy failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to delete template.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — Tenant isolation
    // ════════════════════════════════════════════════════

    private function authorizeTemplate(ActivityTemplate $template): void
    {
        if ($template->company_id !== Auth::user()->company_id) {
            abort(403, 'Access denied.');
        }
    }
}