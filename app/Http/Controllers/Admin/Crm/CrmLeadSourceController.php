<?php

// ════════════════════════════════════════════════════
//  FILE: app/Http/Controllers/Admin/Crm/CrmLeadSourceController.php
// ════════════════════════════════════════════════════

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreLeadSourceRequest;
use App\Models\CrmLeadSource;
use App\Models\CrmTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class CrmLeadSourceController extends Controller
{
    // ── INDEX ──
    public function index()
    {
        $sources = CrmLeadSource::where('company_id', Auth::user()->company_id)
            ->withCount('leads')
            ->ordered()
            ->get();
        $tags = CrmTag::where('company_id', Auth::user()->company_id)
            ->withCount('leads')->ordered()->get();

        return view('admin.crm.settings', compact('sources', 'tags'));
    }

    // ── STORE ──
    public function store(StoreLeadSourceRequest $request): JsonResponse
    {
        $companyId = Auth::user()->company_id;

        try {
            $maxOrder = CrmLeadSource::where('company_id', $companyId)->max('sort_order') ?? 0;

            $source = CrmLeadSource::create(array_merge(
                $request->validated(),
                [
                    'company_id' => $companyId,
                    'sort_order' => $request->input('sort_order', $maxOrder + 1),
                ]
            ));

            Log::info('[CrmLeadSource] Created', ['source_id' => $source->id, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => "Source \"{$source->name}\" added.",
                'source' => $source,
            ]);

        } catch (Throwable $e) {
            Log::error('[CrmLeadSource] Store failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to create source.'], 500);
        }
    }

    // ── UPDATE ──
    public function update(StoreLeadSourceRequest $request, CrmLeadSource $source): JsonResponse
    {
        $this->authorizeSource($source);

        try {
            $source->update($request->validated());

            Log::info('[CrmLeadSource] Updated', ['source_id' => $source->id, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => "Source \"{$source->name}\" updated.",
                'source' => $source->fresh(),
            ]);

        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update.'], 500);
        }
    }

    // ── DESTROY ──
    public function destroy(CrmLeadSource $source): JsonResponse
    {
        $this->authorizeSource($source);

        if ($source->leads()->exists()) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete — {$source->leads()->count()} lead(s) use this source.",
            ], 422);
        }

        try {
            $name = $source->name;
            $source->delete();

            Log::info('[CrmLeadSource] Deleted', ['name' => $name, 'by' => Auth::id()]);

            return response()->json(['success' => true, 'message' => "Source \"{$name}\" deleted."]);

        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete.'], 500);
        }
    }

    private function authorizeSource(CrmLeadSource $source): void
    {
        if ($source->company_id !== Auth::user()->company_id) {
            abort(403);
        }
    }
}
