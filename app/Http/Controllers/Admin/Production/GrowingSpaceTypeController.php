<?php

namespace App\Http\Controllers\Admin\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Production\StoreGrowingSpaceTypeRequest;
use App\Http\Requests\Admin\Production\UpdateGrowingSpaceTypeRequest;
use App\Models\Production\GrowingSpaceType;
use App\Services\Production\GrowingSpaceTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GrowingSpaceTypeController extends Controller
{
    public function __construct(protected GrowingSpaceTypeService $service)
    {
    }

    // ════════════════════════════════════════════════════
    //  INDEX
    //  GET /admin/production/growing-space-types
    // ════════════════════════════════════════════════════

    public function index(): JsonResponse
    {
        $types = GrowingSpaceType::where('company_id', Auth::user()->company_id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->withCount('growingSpaces')
            ->get();

        return response()->json(['success' => true, 'types' => $types]);
    }

    // ════════════════════════════════════════════════════
    //  STORE
    //  POST /admin/production/growing-space-types
    // ════════════════════════════════════════════════════

    public function store(StoreGrowingSpaceTypeRequest $request): JsonResponse
    {
        try {
            $type = $this->service->create(array_merge(
                $request->validated(),
                ['company_id' => Auth::user()->company_id]
            ));

            Log::info('[GrowingSpaceType] Created', ['type_id' => $type->id, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => "Growing space type \"{$type->name}\" created.",
                'type' => $type,
            ]);

        } catch (Throwable $e) {
            Log::error('[GrowingSpaceType] Store failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to create growing space type.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  UPDATE
    //  PUT /admin/production/growing-space-types/{growing_space_type}
    // ════════════════════════════════════════════════════

    public function update(UpdateGrowingSpaceTypeRequest $request, GrowingSpaceType $growingSpaceType): JsonResponse
    {
        $this->authorizeType($growingSpaceType);

        try {
            $updated = $this->service->update($growingSpaceType, $request->validated());

            Log::info('[GrowingSpaceType] Updated', ['type_id' => $growingSpaceType->id, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => "Growing space type \"{$updated->name}\" updated.",
                'type' => $updated,
            ]);

        } catch (Throwable $e) {
            Log::error('[GrowingSpaceType] Update failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to update growing space type.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  DESTROY
    //  DELETE /admin/production/growing-space-types/{growing_space_type}
    // ════════════════════════════════════════════════════

    public function destroy(GrowingSpaceType $growingSpaceType): JsonResponse
    {
        $this->authorizeType($growingSpaceType);

        try {
            $name = $growingSpaceType->name;
            $this->service->delete($growingSpaceType);

            Log::info('[GrowingSpaceType] Deleted', ['name' => $name, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => "Growing space type \"{$name}\" deleted.",
            ]);

        } catch (RuntimeException $e) {
            // "Still in use" guard — user-facing, not a server error.
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);

        } catch (Throwable $e) {
            Log::error('[GrowingSpaceType] Delete failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to delete growing space type.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — Tenant isolation
    // ════════════════════════════════════════════════════

    private function authorizeType(GrowingSpaceType $type): void
    {
        if ($type->company_id !== Auth::user()->company_id) {
            abort(403, 'Access denied.');
        }
    }
}