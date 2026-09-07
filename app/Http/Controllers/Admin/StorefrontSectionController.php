<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStorefrontSectionRequest;
use App\Http\Requests\Admin\UpdateStorefrontSectionRequest;
use App\Models\StorefrontSection;
use App\Services\StorefrontSectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class StorefrontSectionController extends Controller
{
    public function __construct(
        protected StorefrontSectionService $service
    ) {}

    // ════════════════════════════════════════════════════
    //  INDEX — Admin section list with drag-drop order
    // ════════════════════════════════════════════════════

    public function index(): View
    {
        $companyId = Auth::user()->company_id;

        $sections = $this->service->getAdminList($companyId);

        $stats = [
            'total' => $sections->count(),
            'live' => $sections->where('is_live_now', true)->count(),
            'inactive' => $sections->where('is_active', false)->count(),
            'scheduled' => $sections->filter(fn ($s) => $s->starts_at || $s->ends_at)->count(),
        ];

        return view('admin.storefront-sections.index', compact('sections', 'stats'));
    }

    // ════════════════════════════════════════════════════
    //  CREATE
    // ════════════════════════════════════════════════════

    public function create(): View
    {
        $companyId = Auth::user()->company_id;
        $formData = $this->service->getFormData($companyId);

        return view('admin.storefront-sections.create', compact('formData'));
    }

    // ════════════════════════════════════════════════════
    //  STORE
    // ════════════════════════════════════════════════════

    public function store(StoreStorefrontSectionRequest $request): RedirectResponse
    {
        try {
            $section = $this->service->create($request->validated());

            return redirect()
                ->route('admin.storefront-sections.index')
                ->with('success', "Section \"{$section->title}\" created successfully!");

        } catch (Throwable $e) {
            Log::error('[StorefrontSectionController] Store failed', [
                'user_id' => Auth::id(),
                'data' => $request->validated(),
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create section. Please try again.');
        }
    }

    // ════════════════════════════════════════════════════
    //  EDIT
    // ════════════════════════════════════════════════════

    public function edit(StorefrontSection $storefrontSection): View
    {
        $this->authorizeCompany($storefrontSection);

        $companyId = Auth::user()->company_id;
        $formData = $this->service->getFormData($companyId);

        return view('admin.storefront-sections.edit', [
            'section' => $storefrontSection,
            'formData' => $formData,
        ]);
    }

    // ════════════════════════════════════════════════════
    //  UPDATE
    // ════════════════════════════════════════════════════

    public function update(
        UpdateStorefrontSectionRequest $request,
        StorefrontSection $storefrontSection
    ): RedirectResponse {
        $this->authorizeCompany($storefrontSection);

        try {
            $section = $this->service->update($storefrontSection, $request->validated());

            return redirect()
                ->route('admin.storefront-sections.index')
                ->with('success', "Section \"{$section->title}\" updated successfully!");

        } catch (Throwable $e) {
            Log::error('[StorefrontSectionController] Update failed', [
                'section_id' => $storefrontSection->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update section. Please try again.');
        }
    }

    // ════════════════════════════════════════════════════
    //  DESTROY
    // ════════════════════════════════════════════════════

    public function destroy(StorefrontSection $storefrontSection): RedirectResponse
    {
        $this->authorizeCompany($storefrontSection);

        try {
            $title = $storefrontSection->title;
            $this->service->delete($storefrontSection);

            return redirect()
                ->route('admin.storefront-sections.index')
                ->with('success', "Section \"{$title}\" deleted.");

        } catch (Throwable $e) {
            Log::error('[StorefrontSectionController] Destroy failed', [
                'section_id' => $storefrontSection->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete section.');
        }
    }

    // ════════════════════════════════════════════════════
    //  TOGGLE ACTIVE — AJAX
    // ════════════════════════════════════════════════════

    public function toggleActive(StorefrontSection $storefrontSection): JsonResponse
    {
        $this->authorizeCompany($storefrontSection);

        try {
            $isActive = $this->service->toggleActive($storefrontSection);

            return response()->json([
                'success' => true,
                'is_active' => $isActive,
                'message' => $isActive
                    ? "Section \"{$storefrontSection->title}\" is now live."
                    : "Section \"{$storefrontSection->title}\" deactivated.",
            ]);

        } catch (Throwable $e) {
            Log::error('[StorefrontSectionController] Toggle failed', [
                'section_id' => $storefrontSection->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle status.',
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  REORDER — AJAX
    // ════════════════════════════════════════════════════

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        try {
            $this->service->reorder(
                companyId: Auth::user()->company_id,
                sectionIds: $request->input('ids'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Section order saved.',
            ]);

        } catch (Throwable $e) {
            Log::error('[StorefrontSectionController] Reorder failed', [
                'user_id' => Auth::id(),
                'ids' => $request->input('ids'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save order.',
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  DUPLICATE — AJAX
    // ════════════════════════════════════════════════════

    public function duplicate(StorefrontSection $storefrontSection): JsonResponse
    {
        $this->authorizeCompany($storefrontSection);

        try {
            $newSection = $this->service->duplicate($storefrontSection);

            return response()->json([
                'success' => true,
                'message' => "Section duplicated as \"{$newSection->title}\".",
                'edit_url' => route('admin.storefront-sections.edit', $newSection->id),
            ]);

        } catch (Throwable $e) {
            Log::error('[StorefrontSectionController] Duplicate failed', [
                'section_id' => $storefrontSection->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate section.',
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — Tenant Guard
    // ════════════════════════════════════════════════════

    /**
     * Abort 403 if section doesn't belong to current user's company.
     * Prevents cross-tenant access even if Tenantable scope is bypassed.
     */
    private function authorizeCompany(StorefrontSection $section): void
    {
        if ($section->company_id !== Auth::user()->company_id) {
            Log::warning('[StorefrontSectionController] Unauthorized access attempt', [
                'section_id' => $section->id,
                'section_company_id' => $section->company_id,
                'user_id' => Auth::id(),
                'user_company_id' => Auth::user()->company_id,
            ]);
            abort(403, 'Access denied.');
        }
    }
}
