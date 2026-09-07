<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Services\BannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class BannerController extends Controller
{
    public function __construct(protected BannerService $bannerService) {}

    // ════════════════════════════════════════════════════
    //  INDEX
    // ════════════════════════════════════════════════════
    public function index(Request $request): View
    {

        $type = $request->get('type');
        $position = $request->get('position');

        $logs = $this->bannerService->getAdminList(
            type: $type,
            position: $position,
            perPage: 20
        );

        // Available filter options for the blade
        $types = ['hero','category'];

        $positions = [
            'home_top', 'home_middle', 'home_bottom',
            'category_page',
        ];

        return view('admin.banners.index', compact('logs', 'types', 'positions', 'type', 'position'));
    }

    // ════════════════════════════════════════════════════
    //  CREATE
    // ════════════════════════════════════════════════════
    public function create(): View
    {
        $categories = Category::orderBy('name')->get(['id', 'name']);

        $products = Product::orderBy('name')->get(['id', 'name']);

        return view('admin.banners.create', compact('categories', 'products'));
    }

    // ════════════════════════════════════════════════════
    //  STORE
    // ════════════════════════════════════════════════════
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateBanner($request);

        try {
            $data = $validated;

            // Pass file objects separately from string fields
            if ($request->hasFile('image')) {
                $data['image_file'] = $request->file('image');
            }
            if ($request->hasFile('mobile_image')) {
                $data['mobile_image_file'] = $request->file('mobile_image');
            }

            // Unset raw file keys — service uses image_file / mobile_image_file
            unset($data['image'], $data['mobile_image']);

            // ── Targeting — nullable is fine if not selected ──
            $data['category_id'] = $validated['category_id'] ?? null;
            $data['product_id'] = $validated['product_id'] ?? null;

            $banner = $this->bannerService->store($data);

            return redirect()
                ->route('admin.banners.index')
                ->with('success', "Banner '{$banner->display_admin_label}' created successfully!");

        } catch (\InvalidArgumentException $e) {
            // File validation failure — show to user
            return back()->withInput()->withErrors(['image' => $e->getMessage()]);

        } catch (Throwable $e) {
            Log::error('[BannerController] Store failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Failed to create banner. Please try again.');
        }
    }

    // ════════════════════════════════════════════════════
    //  SHOW
    // ════════════════════════════════════════════════════
    public function show(Banner $banner): View
    {

        $banner->load(['creator', 'updater', 'category', 'product']);

        return view('admin.banners.show', compact('banner'));
    }

    // ════════════════════════════════════════════════════
    //  EDIT
    // ════════════════════════════════════════════════════
    public function edit(Banner $banner): View
    {

        $categories = Category::orderBy('name')->get(['id', 'name']);

        $products = Product::orderBy('name')->get(['id', 'name']);

        return view('admin.banners.edit', compact('banner', 'categories', 'products'));
    }

    // ════════════════════════════════════════════════════
    //  UPDATE
    // ════════════════════════════════════════════════════
    public function update(Request $request, Banner $banner): RedirectResponse
    {

        $validated = $this->validateBanner($request, isUpdate: true);

        try {
            $data = $validated;

            if ($request->hasFile('image')) {
                $data['image_file'] = $request->file('image');
            }
            if ($request->hasFile('mobile_image')) {
                $data['mobile_image_file'] = $request->file('mobile_image');
            }

            // Clean up file fields
            unset($data['image'], $data['mobile_image']);

            // Set remove flag cleanly — boolean() handles "1","0","true","false" all correctly
            $data['remove_mobile_image'] = $request->boolean('remove_mobile_image');

            $this->bannerService->update($banner, $data);

            return redirect()
                ->route('admin.banners.index')
                ->with('success', 'Banner updated successfully!');

        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['image' => $e->getMessage()]);

        } catch (Throwable $e) {
            Log::error('[BannerController] Update failed', [
                'banner_id' => $banner->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Failed to update banner. Please try again.');
        }
    }

    // ════════════════════════════════════════════════════
    //  DESTROY
    // ════════════════════════════════════════════════════
    public function destroy(Request $request, Banner $banner): RedirectResponse
    {

        $permanent = $request->boolean('permanent');
        $success = $this->bannerService->delete($banner, $permanent);

        if ($success) {
            $msg = $permanent
                ? 'Banner permanently deleted.'
                : 'Banner moved to trash. It can be restored.';

            return redirect()
                ->route('admin.banners.index')
                ->with('success', $msg);
        }

        return back()->with('error', 'Failed to delete banner. Please try again.');
    }

    // ════════════════════════════════════════════════════
    //  TOGGLE ACTIVE — AJAX
    // ════════════════════════════════════════════════════
    public function toggleActive(Banner $banner): JsonResponse
    {

        $success = $this->bannerService->toggleActive($banner);

        if ($success) {
            return response()->json([
                'success' => true,
                'is_active' => $banner->fresh()->is_active,
                'message' => $banner->fresh()->is_active ? 'Banner activated.' : 'Banner deactivated.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to toggle status.',
        ], 500);
    }

    // ════════════════════════════════════════════════════
    //  REORDER — AJAX (drag & drop)
    // ════════════════════════════════════════════════════
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:banners,id',
        ]);

        $ids = $request->input('ids');

        $count = Banner::whereIn('id', $ids)
            ->count();

        if ($count !== count($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized banner IDs detected.',
            ], 403);
        }

        $success = $this->bannerService->reorder($ids);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Order saved.' : 'Reorder failed.',
        ], $success ? 200 : 500);
    }

    // ════════════════════════════════════════════════════
    //  DUPLICATE — AJAX
    // ════════════════════════════════════════════════════
    public function duplicate(Banner $banner): JsonResponse
    {

        try {
            $newBanner = $this->bannerService->duplicate($banner);

            return response()->json([
                'success' => true,
                'message' => "Banner duplicated as '{$newBanner->display_admin_label}'.",
                'banner_id' => $newBanner->id,
                'edit_url' => route('admin.banners.edit', $newBanner->id),
            ]);

        } catch (Throwable $e) {
            Log::error('[BannerController] Duplicate failed', [
                'banner_id' => $banner->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate banner.',
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  RESTORE (soft-deleted banner) — AJAX
    // ════════════════════════════════════════════════════
    public function restore(int $id): JsonResponse
    {
        $banner = Banner::withTrashed()
            ->where('id', $id)
            ->firstOrFail();

        $success = $this->bannerService->restore($id);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Banner restored successfully.' : 'Restore failed.',
        ], $success ? 200 : 500);
    }

    // ════════════════════════════════════════════════════
    //  TRACK CLICK — public, called from storefront
    // ════════════════════════════════════════════════════
    public function trackClick(Banner $banner): JsonResponse
    {
        $this->bannerService->trackClick($banner);

        return response()->json(['success' => true]);
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ════════════════════════════════════════════════════

    /**
     * Shared validation rules for store and update.
     * Image is required on create, optional on update.
     */
    private function validateBanner(Request $request, bool $isUpdate = false): array
    {
        $imageRule = $isUpdate
            ? 'nullable|file|image|mimes:jpg,jpeg,png,webp,svg|max:5120'
            : 'required|file|image|mimes:jpg,jpeg,png,webp,svg|max:5120';

        return $request->validate([
            'type' => 'required|in:hero,promo,ad,category,popup',
            'position' => 'required|string|max:100',
            'admin_label' => 'required|string|max:150',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'alt_text' => 'nullable|string|max:255',
            'image' => $imageRule,
            'mobile_image' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_mobile_image' => 'nullable|boolean',
            'link' => 'nullable|url|max:500',
            'button_text' => 'nullable|string|max:100',
            'target' => 'nullable|in:_self,_blank',
            'category_id' => 'nullable|integer|exists:categories,id',
            'product_id' => 'nullable|integer|exists:products,id',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'meta' => 'nullable|array',
            'meta.bg_color' => 'nullable|string|max:20',
            'meta.text_color' => 'nullable|string|max:20',
            'meta.animation' => 'nullable|string|in:fade,slide,zoom,none',
        ]);
    }
}
