<?php

namespace App\Http\Controllers\Admin\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Production\BulkGenerateGrowingSpaceRequest;
use App\Services\Production\GrowingSpaceBulkGeneratorService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GrowingSpaceBulkGeneratorController extends Controller
{
    public function __construct(protected GrowingSpaceBulkGeneratorService $service)
    {
    }

    // ════════════════════════════════════════════════════
    //  PREVIEW — no writes, feeds the Inspector's live grid
    //  POST /admin/production/growing-spaces/bulk/preview
    // ════════════════════════════════════════════════════

    public function preview(BulkGenerateGrowingSpaceRequest $request): JsonResponse
    {
        try {
            $grid = $this->service->preview($request->validated());

            return response()->json([
                'success' => true,
                'grid' => $grid,
                'total' => count($grid),
                'conflict_count' => count(array_filter($grid, fn (array $row) => $row['conflict'])),
            ]);

        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);

        } catch (Throwable $e) {
            Log::error('[GrowingSpaceBulkGenerator] Preview failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to build preview.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  GENERATE — commits the grid
    //  POST /admin/production/growing-spaces/bulk/generate
    // ════════════════════════════════════════════════════

    public function generate(BulkGenerateGrowingSpaceRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), ['company_id' => Auth::user()->company_id]);
        $skipConflicts = $request->boolean('skip_conflicts', false);

        try {
            $result = $this->service->generate($data, $skipConflicts);

            Log::info('[GrowingSpaceBulkGenerator] Generated', [
                'created_count' => $result['created']->count(),
                'skipped_count' => count($result['skipped']),
                'by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$result['created']->count()} growing space(s) created.".
                    (count($result['skipped']) ? ' '.count($result['skipped']).' name(s) skipped as duplicates.' : ''),
                'created' => $result['created'],
                'skipped' => $result['skipped'],
            ]);

        } catch (RuntimeException $e) {
            // Name conflicts surfaced without skip_conflicts=true — 422 so
            // the Inspector can show the exact conflicting names inline.
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);

        } catch (QueryException $e) {
            // DB-level unique constraint (production_site_id, zone_id, name)
            // tripped by a concurrent request that raced past preview()'s
            // application-level check. Treat as the same "duplicates" case
            // the RuntimeException branch handles, not a server error.
            if ((int) $e->getCode() === 23000) {
                Log::warning('[GrowingSpaceBulkGenerator] Unique constraint race', ['error' => $e->getMessage()]);

                return response()->json([
                    'success' => false,
                    'message' => 'Some of these names were just created by another request. Please refresh the preview and try again.',
                ], 422);
            }

            Log::error('[GrowingSpaceBulkGenerator] Generate failed (DB)', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to generate growing spaces.'], 500);

        } catch (Throwable $e) {
            Log::error('[GrowingSpaceBulkGenerator] Generate failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to generate growing spaces.'], 500);
        }
    }
}