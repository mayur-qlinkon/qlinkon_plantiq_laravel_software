<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\StorefrontSection;
use App\Services\BannerService;
use App\Services\StorefrontSectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class StorefrontBuilderController extends Controller
{
    public function __construct(
        protected StorefrontSectionService $sectionService,
        protected BannerService $bannerService,
    ) {}

    // ════════════════════════════════════════════════════
    //  INDEX — Storefront Builder page
    // ════════════════════════════════════════════════════

    public function index(): View
    {
        $companyId = Auth::user()->company_id;

        // All sections ordered — same query as existing index
        $sections = $this->sectionService->getAdminList($companyId);

        // Form data: categories, type/layout labels, banner positions
        $formData = $this->sectionService->getFormData($companyId);

        // All banners grouped by position — for the banner slide-over panel
        $bannersByPosition = Banner::where('company_id', $companyId)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('position');

        // Pre-serialise sections for Alpine.js — avoids repeated Blade loops
        $sectionsJson = $sections->map(fn ($s) => $this->formatSection($s));

        return view('admin.storefront-builder.index', compact(
            'sections',
            'formData',
            'bannersByPosition',
            'sectionsJson',
        ));
    }

    // ════════════════════════════════════════════════════
    //  SECTIONS — JSON CRUD
    //  Existing StorefrontSectionController still works.
    //  These endpoints return JSON instead of redirects.
    // ════════════════════════════════════════════════════

    public function storeSection(Request $request): JsonResponse
    {
        $companyId = Auth::user()->company_id;
        $validated = $request->validate($this->sectionRules($companyId));

        try {
            $section = $this->sectionService->create($validated);
            $section->load('category:id,name');

            return response()->json([
                'success' => true,
                'message' => "Section \"{$section->display_admin_label}\" created.",
                'section' => $this->formatSection($section),
            ]);
        } catch (Throwable $e) {
            Log::error('[StorefrontBuilder] storeSection failed', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to create section.'], 500);
        }
    }

    public function updateSection(Request $request, StorefrontSection $storefrontSection): JsonResponse
    {
        $this->authorizeSection($storefrontSection);

        $companyId = Auth::user()->company_id;
        $validated = $request->validate($this->sectionRules($companyId));

        try {
            $section = $this->sectionService->update($storefrontSection, $validated);
            $section->load('category:id,name');

            return response()->json([
                'success' => true,
                'message' => "Section \"{$section->display_admin_label}\" saved.",
                'section' => $this->formatSection($section),
            ]);
        } catch (Throwable $e) {
            Log::error('[StorefrontBuilder] updateSection failed', [
                'section_id' => $storefrontSection->id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to save section.'], 500);
        }
    }

    public function destroySection(StorefrontSection $storefrontSection): JsonResponse
    {
        $this->authorizeSection($storefrontSection);

        try {
            $title = $storefrontSection->display_admin_label;
            $this->sectionService->delete($storefrontSection);

            return response()->json([
                'success' => true,
                'message' => "Section \"{$title}\" deleted.",
            ]);
        } catch (Throwable $e) {
            Log::error('[StorefrontBuilder] destroySection failed', [
                'section_id' => $storefrontSection->id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to delete section.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  BANNERS — JSON CRUD for inline banner management
    //  Uses same BannerService — no duplication of logic.
    // ════════════════════════════════════════════════════

    public function storeBanner(Request $request): JsonResponse
    {
        $request->validate([
            'position'    => ['required', 'string', Rule::in(['home_top', 'home_middle', 'home_bottom', 'category_page', 'product_page'])],
            'admin_label' => ['required', 'string', 'max:150'],
            'title'       => ['nullable', 'string', 'max:255'],
            'subtitle'    => ['nullable', 'string', 'max:255'],
            'image'       => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
            'link'        => ['nullable', 'url', 'max:500'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        try {
            $data = $request->except('image');
            $data['type'] = 'hero'; // default type for builder-created banners

            if ($request->hasFile('image')) {
                $data['image_file'] = $request->file('image');
            }

            $banner = $this->bannerService->store($data);

            return response()->json([
                'success' => true,
                'message' => "Banner \"{$banner->display_admin_label}\" created.",
                'banner'  => $this->formatBanner($banner),
            ]);
        } catch (Throwable $e) {
            Log::error('[StorefrontBuilder] storeBanner failed', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to create banner.'], 500);
        }
    }

    public function updateBanner(Request $request, Banner $banner): JsonResponse
    {
        $this->authorizeBanner($banner);

        $request->validate([
            'admin_label' => ['required', 'string', 'max:150'],
            'title'       => ['nullable', 'string', 'max:255'],
            'subtitle'    => ['nullable', 'string', 'max:255'],
            'image'       => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
            'link'        => ['nullable', 'url', 'max:500'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        try {
            $data = $request->except('image');

            if ($request->hasFile('image')) {
                $data['image_file'] = $request->file('image');
            }

            $this->bannerService->update($banner, $data);
            $banner->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Banner updated.',
                'banner'  => $this->formatBanner($banner),
            ]);
        } catch (Throwable $e) {
            Log::error('[StorefrontBuilder] updateBanner failed', [
                'banner_id' => $banner->id,
                'error'     => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to update banner.'], 500);
        }
    }

    public function destroyBanner(Banner $banner): JsonResponse
    {
        $this->authorizeBanner($banner);

        try {
            $label = $banner->display_admin_label;
            $this->bannerService->delete($banner);

            return response()->json([
                'success' => true,
                'message' => "Banner \"{$label}\" deleted.",
            ]);
        } catch (Throwable $e) {
            Log::error('[StorefrontBuilder] destroyBanner failed', [
                'banner_id' => $banner->id,
                'error'     => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to delete banner.'], 500);
        }
    }

    public function toggleBanner(Banner $banner): JsonResponse
    {
        $this->authorizeBanner($banner);

        try {
            $this->bannerService->toggleActive($banner);
            $banner->refresh();

            return response()->json([
                'success'   => true,
                'is_active' => $banner->is_active,
                'message'   => $banner->is_active ? 'Banner activated.' : 'Banner deactivated.',
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Toggle failed.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — Validation rules
    // ════════════════════════════════════════════════════

    private function sectionRules(int $companyId): array
    {
        return [
            'title'              => ['nullable', 'string', 'max:150'],
            'admin_label'        => ['nullable', 'string', 'max:150'],
            'subtitle'           => ['nullable', 'string', 'max:255'],
            'type'               => ['required', Rule::in(StorefrontSection::TYPES)],
            'category_id'        => [
                Rule::requiredIf(fn () => request()->input('type') === 'category'),
                'nullable',
                Rule::exists('categories', 'id')->where('company_id', $companyId),
            ],
            'banner_position'    => [
                Rule::requiredIf(fn () => request()->input('type') === 'banner'),
                'nullable',
                'string',
                Rule::in(['home_top', 'home_middle', 'home_bottom', 'category_page', 'product_page']),
            ],
            'layout'             => ['required', Rule::in(StorefrontSection::LAYOUTS)],
            'products_limit'     => ['required', 'integer', 'min:1', 'max:48'],
            'columns'            => ['required', 'integer', 'min:1', 'max:6'],
            'show_view_all'      => ['nullable', 'boolean'],
            'show_section_title' => ['nullable', 'boolean'],
            'show_on_mobile'     => ['nullable', 'boolean'],
            'show_on_desktop'    => ['nullable', 'boolean'],
            'is_active'          => ['nullable', 'boolean'],
            'view_all_url'       => ['nullable', 'url', 'max:500'],
            'bg_color'           => ['nullable', 'string', 'max:20'],
            'heading_color'      => ['nullable', 'string', 'max:20'],
            'custom_html'        => ['nullable', 'string'],
            'starts_at'          => ['nullable', 'date'],
            'ends_at'            => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — Data formatters (PHP → Alpine.js JSON)
    // ════════════════════════════════════════════════════

    private function formatSection(StorefrontSection $section): array
    {
        return [
            'id'                  => $section->id,
            'title'               => $section->title,
            'admin_label'         => $section->admin_label,
            'display_admin_label' => $section->display_admin_label,
            'subtitle'            => $section->subtitle,
            'type'                => $section->type,
            'type_label'          => $section->type_label,
            'category_id'         => $section->category_id,
            'category_name'       => $section->category?->name,
            'banner_position'     => $section->banner_position,
            'layout'              => $section->layout,
            'layout_label'        => $section->layout_label,
            'products_limit'      => $section->products_limit,
            'columns'             => $section->columns,
            'is_active'           => $section->is_active,
            'is_live_now'         => $section->is_live_now,
            'show_view_all'       => $section->show_view_all,
            'show_section_title'  => $section->show_section_title,
            'show_on_mobile'      => $section->show_on_mobile,
            'show_on_desktop'     => $section->show_on_desktop,
            'view_all_url'        => $section->view_all_url,
            'bg_color'            => $section->bg_color,
            'heading_color'       => $section->heading_color,
            'custom_html'         => $section->custom_html,
            'sort_order'          => $section->sort_order,
            'starts_at'           => $section->starts_at?->format('Y-m-d\TH:i'),
            'ends_at'             => $section->ends_at?->format('Y-m-d\TH:i'),
            'view_count'          => $section->view_count,
            'click_count'         => $section->click_count,
        ];
    }

    private function formatBanner(Banner $banner): array
    {
        return [
            'id'                  => $banner->id,
            'admin_label'         => $banner->admin_label,
            'display_admin_label' => $banner->display_admin_label,
            'title'               => $banner->title,
            'subtitle'            => $banner->subtitle,
            'position'            => $banner->position,
            'type'                => $banner->type,
            'image_url'           => $banner->image_url,
            'link'                => $banner->link,
            'button_text'         => $banner->button_text,
            'is_active'           => $banner->is_active,
            'is_live_now'         => $banner->is_live_now,
            'sort_order'          => $banner->sort_order,
            'click_count'         => $banner->click_count,
        ];
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — Tenant guards
    // ════════════════════════════════════════════════════

    private function authorizeSection(StorefrontSection $section): void
    {
        if ($section->company_id !== Auth::user()->company_id) {
            Log::warning('[StorefrontBuilder] Unauthorized section access', [
                'section_id' => $section->id,
                'user_id'    => Auth::id(),
            ]);
            abort(403, 'Access denied.');
        }
    }

    private function authorizeBanner(Banner $banner): void
    {
        if ($banner->company_id !== Auth::user()->company_id) {
            Log::warning('[StorefrontBuilder] Unauthorized banner access', [
                'banner_id' => $banner->id,
                'user_id'   => Auth::id(),
            ]);
            abort(403, 'Access denied.');
        }
    }
}