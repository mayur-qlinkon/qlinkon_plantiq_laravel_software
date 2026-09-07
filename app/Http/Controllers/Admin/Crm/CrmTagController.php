<?php

// ════════════════════════════════════════════════════
//  FILE: app/Http/Controllers/Admin/Crm/CrmTagController.php
// ════════════════════════════════════════════════════

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreTagRequest;
use App\Models\CrmLeadSource;
use App\Models\CrmTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class CrmTagController extends Controller
{
    // ── INDEX ──
    public function index()
    {
        $tags = CrmTag::query()
            ->withCount('leads')
            ->ordered()
            ->get();

        $sources = CrmLeadSource::query()
            ->withCount('leads')
            ->ordered()
            ->get();

        return view('admin.crm.settings', compact('tags', 'sources'));
    }

    // ── STORE ──
    public function store(StoreTagRequest $request): JsonResponse
    {
        try {
            $tag = CrmTag::create(array_merge(
                $request->validated(),
                ['company_id' => Auth::user()->company_id]
            ));

            Log::info('[CrmTag] Created', ['tag_id' => $tag->id, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => "Tag \"{$tag->name}\" added.",
                'tag' => $tag,
            ]);

        } catch (Throwable $e) {
            Log::error('[CrmTag] Store failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to create tag.'], 500);
        }
    }

    // ── UPDATE ──
    public function update(StoreTagRequest $request, CrmTag $tag): JsonResponse
    {
        $this->authorizeTag($tag);

        try {
            $tag->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => "Tag \"{$tag->name}\" updated.",
                'tag' => $tag->fresh(),
            ]);

        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update.'], 500);
        }
    }

    // ── DESTROY ──
    public function destroy(CrmTag $tag): JsonResponse
    {
        $this->authorizeTag($tag);

        try {
            $name = $tag->name;

            // Detach from all leads first (pivot auto-cleaned)
            $tag->leads()->detach();
            $tag->delete();

            Log::info('[CrmTag] Deleted', ['name' => $name, 'by' => Auth::id()]);

            return response()->json(['success' => true, 'message' => "Tag \"{$name}\" deleted."]);

        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete.'], 500);
        }
    }

    private function authorizeTag(CrmTag $tag): void
    {
        if ($tag->company_id !== Auth::user()->company_id) {
            abort(403);
        }
    }
}
