<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryProduct;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class MerchandisingController extends Controller
{
    // ════════════════════════════════════════════════════
    //  INDEX — Category selector + optional product list
    // ════════════════════════════════════════════════════

    public function index(Request $request): View
    {

        // All categories for the left panel — flat list with product count
        // ── Step 1: Get categories ──
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        // ── Step 2: Get all pivot counts in ONE query ──
        $pivotCounts = CategoryProduct::selectRaw('
            category_id,
            COUNT(*) as total_products,
            SUM(is_active = 1) as active_products,
            SUM(is_featured = 1) as featured_products
        ')
            ->whereIn('category_id', $categories->pluck('id'))
            ->groupBy('category_id')
            ->get()
            ->keyBy('category_id');

        // ── Step 3: Attach counts to each category ──
        $categories->each(function ($cat) use ($pivotCounts) {
            $counts = $pivotCounts->get($cat->id);
            $cat->total_products = $counts?->total_products ?? 0;
            $cat->active_products = $counts?->active_products ?? 0;
            $cat->featured_products = $counts?->featured_products ?? 0;
        });

        // If a category is pre-selected via query param
        $selectedCategory = null;
        $assignedProducts = collect();
        $categoryId = $request->integer('category');

        if ($categoryId) {
            $selectedCategory = Category::findOrFail($categoryId);

            $assignedProducts = $this->getAssignedProducts($categoryId);
        }

        return view('admin.storefront-sections.merchandising', compact(
            'categories',
            'selectedCategory',
            'assignedProducts'
        ));
    }

    // ════════════════════════════════════════════════════
    //  LOAD CATEGORY PRODUCTS — AJAX
    //  Called when user clicks a category in left panel
    // ════════════════════════════════════════════════════

    public function loadCategory(Request $request, int $categoryId): JsonResponse
    {
        $companyId = Auth::user()->company_id;

        try {
            $category = Category::where('company_id', $companyId)->findOrFail($categoryId);

            $pivots = CategoryProduct::where('category_id', $categoryId)
                ->with([
                    'product' => fn ($q) => $q->with([
                        'media' => fn ($q) => $q->where('is_primary', true)->limit(1),
                    ]),
                ])
                ->ordered() // featured desc, sort_order asc
                ->get();

            // Map to a clean JSON structure Alpine can consume directly
            $products = $pivots->map(fn ($pivot) => [
                'product_id' => $pivot->product_id,
                'is_active' => $pivot->is_active,
                'is_featured' => $pivot->is_featured,
                'sort_order' => $pivot->sort_order,
                'product' => $pivot->product ? [
                    'id' => $pivot->product->id,
                    'name' => $pivot->product->name,
                    'hsn_code' => $pivot->product->hsn_code,
                    'show_in_storefront' => $pivot->product->show_in_storefront,
                    'primary_image_url' => $pivot->product->primary_image_url,
                ] : null,
            ]);

            Log::info('[Merchandising] Category loaded', [
                'category_id' => $categoryId,
                'count' => $products->count(),
            ]);

            return response()->json([
                'success' => true,
                'products' => $products,
                'count' => $products->count(),
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                ],
            ]);

        } catch (Throwable $e) {
            Log::error('[Merchandising] Load category failed', [
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load category products.',
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  SEARCH UNASSIGNED PRODUCTS — AJAX
    //  Finds products NOT yet in this category
    // ════════════════════════════════════════════════════

    public function searchProducts(Request $request, int $categoryId): JsonResponse
    {
        $companyId = Auth::user()->company_id;
        $query = trim($request->get('q', ''));
        // ── TEMP DEBUG ──
        $total = Product::where('company_id', $companyId)->count();
        $active = Product::where('company_id', $companyId)->where('is_active', true)->count();
        Log::info('[Debug] Products visible', [
            'company_id' => $companyId,
            'total' => $total,
            'active' => $active,
            'search_q' => $query,
        ]);
        // ── END DEBUG ──
        try {
            // Get IDs already assigned to this category
            $assignedIds = CategoryProduct::where('category_id', $categoryId)
                ->pluck('product_id')
                ->toArray();

            $products = Product::where('company_id', $companyId)
                ->where('is_active', true)
                ->whereNotIn('id', $assignedIds) // exclude already assigned
                ->when($query, fn ($q) => $q->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('hsn_code', 'like', "%{$query}%");
                }))
                ->with(['media' => fn ($q) => $q->where('is_primary', true)->limit(1)])
                ->select(['id', 'name', 'hsn_code', 'is_active', 'show_in_storefront'])
                ->limit(20)
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'hsn_code' => $p->hsn_code,
                    'image_url' => $p->primary_image_url,
                    'in_storefront' => $p->show_in_storefront,
                ]);

            return response()->json([
                'success' => true,
                'products' => $products,
                'count' => $products->count(),
                'query' => $query,
            ]);

        } catch (Throwable $e) {
            Log::error('[Merchandising] Product search failed', [
                'category_id' => $categoryId,
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Search failed. Please try again.',
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  AVAILABLE PRODUCTS — AJAX (browse + paginated)
    //  Products not yet assigned to this category
    // ════════════════════════════════════════════════════

    public function availableProducts(Request $request, int $categoryId): JsonResponse
    {
        $companyId = Auth::user()->company_id;
        $query = trim((string) $request->get('q', ''));
        $perPage = 20;

        try {
            Category::where('company_id', $companyId)->findOrFail($categoryId);

            $assignedIds = CategoryProduct::where('category_id', $categoryId)
                ->pluck('product_id')
                ->all();

            $paginator = Product::where('company_id', $companyId)
                ->where('category_id', $categoryId)
                ->where('is_active', true)
                ->whereNotIn('id', $assignedIds)
                ->when($query !== '', fn ($q) => $q->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('hsn_code', 'like', "%{$query}%");
                }))
                ->with(['media' => fn ($q) => $q->where('is_primary', true)->limit(1)])
                ->select(['id', 'name', 'hsn_code', 'is_active', 'show_in_storefront'])
                ->orderBy('name')
                ->paginate($perPage);

            $products = $paginator->getCollection()->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'hsn_code' => $p->hsn_code,
                'image_url' => $p->primary_image_url,
                'in_storefront' => $p->show_in_storefront,
            ]);

            return response()->json([
                'success' => true,
                'products' => $products,
                'count' => $products->count(),
                'page' => $paginator->currentPage(),
                'has_more' => $paginator->hasMorePages(),
                'total' => $paginator->total(),
                'query' => $query,
            ]);

        } catch (Throwable $e) {
            Log::error('[Merchandising] Available products fetch failed', [
                'category_id' => $categoryId,
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load available products.',
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  ADD PRODUCT — AJAX
    // ════════════════════════════════════════════════════

    public function addProduct(Request $request, int $categoryId): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $companyId = Auth::user()->company_id;

        try {
            // Verify category belongs to this company
            $category = Category::where('company_id', $companyId)->findOrFail($categoryId);

            // Verify product belongs to this company
            $product = Product::where('company_id', $companyId)
                ->findOrFail($request->integer('product_id'));

            // Attach — safe, won't duplicate
            $pivot = CategoryProduct::attachProduct(
                categoryId: $categoryId,
                productId: $product->id,
            );

            Log::info('[Merchandising] Product added to category', [
                'category_id' => $categoryId,
                'product_id' => $product->id,
                'added_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "\"{$product->name}\" added to {$category->name}.",
                'pivot_id' => $pivot->id,
                'sort_order' => $pivot->sort_order,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image_url' => $product->primary_image_url,
                    'in_storefront' => $product->show_in_storefront,
                    'is_active' => $pivot->is_active,
                    'is_featured' => $pivot->is_featured,
                    'sort_order' => $pivot->sort_order,
                ],
            ]);

        } catch (Throwable $e) {
            Log::error('[Merchandising] Add product failed', [
                'category_id' => $categoryId,
                'product_id' => $request->input('product_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add product. Please try again.',
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  ADD MULTIPLE PRODUCTS — AJAX (bulk)
    // ════════════════════════════════════════════════════

    public function addMultipleProducts(Request $request, int $categoryId): JsonResponse
    {
        $request->validate([
            'product_ids' => 'required|array|min:1|max:200',
            'product_ids.*' => 'integer|distinct',
        ]);

        $companyId = Auth::user()->company_id;

        try {
            $category = Category::where('company_id', $companyId)->findOrFail($categoryId);

            // Company-scoped filter — only IDs that actually belong to this company
            $safeIds = Product::where('company_id', $companyId)
                ->whereIn('id', $request->input('product_ids'))
                ->pluck('id')
                ->all();

            if (empty($safeIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid products to add.',
                ], 422);
            }

            // Skip already-assigned (unique constraint would handle it, but this lets us return accurate counts)
            $alreadyAssigned = CategoryProduct::where('category_id', $categoryId)
                ->whereIn('product_id', $safeIds)
                ->pluck('product_id')
                ->all();

            $toAdd = array_values(array_diff($safeIds, $alreadyAssigned));
            $added = [];
            $failed = 0;

            foreach ($toAdd as $productId) {
                try {
                    CategoryProduct::attachProduct(
                        categoryId: $categoryId,
                        productId: $productId,
                    );
                    $added[] = $productId;
                } catch (Throwable $inner) {
                    $failed++;
                    Log::warning('[Merchandising] Bulk add — single item failed', [
                        'category_id' => $categoryId,
                        'product_id' => $productId,
                        'error' => $inner->getMessage(),
                    ]);
                }
            }

            // Fetch freshly added pivots with the data the frontend needs
            $products = CategoryProduct::where('category_id', $categoryId)
                ->whereIn('product_id', $added)
                ->with([
                    'product' => fn ($q) => $q->with([
                        'media' => fn ($q) => $q->where('is_primary', true)->limit(1),
                    ]),
                ])
                ->get()
                ->map(fn ($pivot) => [
                    'product_id' => $pivot->product_id,
                    'is_active' => $pivot->is_active,
                    'is_featured' => $pivot->is_featured,
                    'sort_order' => $pivot->sort_order,
                    'product' => $pivot->product ? [
                        'id' => $pivot->product->id,
                        'name' => $pivot->product->name,
                        'hsn_code' => $pivot->product->hsn_code,
                        'show_in_storefront' => $pivot->product->show_in_storefront,
                        'primary_image_url' => $pivot->product->primary_image_url,
                    ] : null,
                ]);

            Log::info('[Merchandising] Bulk add completed', [
                'category_id' => $categoryId,
                'requested' => count($request->input('product_ids')),
                'added' => count($added),
                'skipped' => count($alreadyAssigned),
                'failed' => $failed,
                'by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'added_count' => count($added),
                'skipped_count' => count($alreadyAssigned),
                'failed_count' => $failed,
                'products' => $products,
                'message' => $this->buildBulkMessage(count($added), count($alreadyAssigned), $failed, $category->name),
            ]);

        } catch (Throwable $e) {
            Log::error('[Merchandising] Bulk add failed', [
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add selected products.',
            ], 500);
        }
    }

    private function buildBulkMessage(int $added, int $skipped, int $failed, string $categoryName): string
    {
        if ($added === 0) {
            return 'No new products added.';
        }

        $msg = "{$added} product".($added === 1 ? '' : 's')." added to {$categoryName}.";
        if ($skipped > 0) {
            $msg .= " ({$skipped} already assigned.)";
        }
        if ($failed > 0) {
            $msg .= " ({$failed} failed.)";
        }

        return $msg;
    }

    // ════════════════════════════════════════════════════
    //  REMOVE PRODUCT — AJAX
    // ════════════════════════════════════════════════════

    public function removeProduct(Request $request, int $categoryId, int $productId): JsonResponse
    {
        $companyId = Auth::user()->company_id;

        try {
            // Verify ownership
            Category::where('company_id', $companyId)->findOrFail($categoryId);
            Product::where('company_id', $companyId)->findOrFail($productId);

            $removed = CategoryProduct::detachProduct($categoryId, $productId);

            if (! $removed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product was not in this category.',
                ], 404);
            }

            Log::info('[Merchandising] Product removed from category', [
                'category_id' => $categoryId,
                'product_id' => $productId,
                'removed_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product removed from category.',
            ]);

        } catch (Throwable $e) {
            Log::error('[Merchandising] Remove product failed', [
                'category_id' => $categoryId,
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove product.',
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  REORDER — AJAX
    // ════════════════════════════════════════════════════

    public function reorder(Request $request, int $categoryId): JsonResponse
    {
        $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $companyId = Auth::user()->company_id;

        try {
            Category::where('company_id', $companyId)->findOrFail($categoryId);

            CategoryProduct::reorderInCategory($categoryId, $request->input('product_ids'));

            Log::info('[Merchandising] Products reordered', [
                'category_id' => $categoryId,
                'count' => count($request->input('product_ids')),
                'by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order saved.',
            ]);

        } catch (Throwable $e) {
            Log::error('[Merchandising] Reorder failed', [
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save order.',
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  TOGGLE FEATURED — AJAX
    // ════════════════════════════════════════════════════

    public function toggleFeatured(Request $request, int $categoryId, int $productId): JsonResponse
    {
        $companyId = Auth::user()->company_id;

        try {
            Category::where('company_id', $companyId)->findOrFail($categoryId);

            $pivot = CategoryProduct::where('category_id', $categoryId)
                ->where('product_id', $productId)
                ->firstOrFail();

            $pivot->update(['is_featured' => ! $pivot->is_featured]);

            Log::info('[Merchandising] Featured toggled', [
                'category_id' => $categoryId,
                'product_id' => $productId,
                'is_featured' => $pivot->is_featured,
                'by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'is_featured' => $pivot->is_featured,
                'message' => $pivot->is_featured
                    ? 'Product marked as featured.'
                    : 'Product unfeatured.',
            ]);

        } catch (Throwable $e) {
            Log::error('[Merchandising] Toggle featured failed', [
                'category_id' => $categoryId,
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update featured status.',
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  TOGGLE ACTIVE (per-category visibility) — AJAX
    // ════════════════════════════════════════════════════

    public function toggleActive(Request $request, int $categoryId, int $productId): JsonResponse
    {
        $companyId = Auth::user()->company_id;

        try {
            Category::where('company_id', $companyId)->findOrFail($categoryId);

            $pivot = CategoryProduct::where('category_id', $categoryId)
                ->where('product_id', $productId)
                ->firstOrFail();

            $pivot->update(['is_active' => ! $pivot->is_active]);

            Log::info('[Merchandising] Visibility toggled', [
                'category_id' => $categoryId,
                'product_id' => $productId,
                'is_active' => $pivot->is_active,
                'by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'is_active' => $pivot->is_active,
                'message' => $pivot->is_active
                    ? 'Product visible in this category.'
                    : 'Product hidden from this category.',
            ]);

        } catch (Throwable $e) {
            Log::error('[Merchandising] Toggle active failed', [
                'category_id' => $categoryId,
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update visibility.',
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ════════════════════════════════════════════════════

    /**
     * Get all products assigned to a category, ordered correctly.
     * Featured first, then by sort_order.
     */
    private function getAssignedProducts(int $categoryId): Collection
    {
        return CategoryProduct::where('category_id', $categoryId)
            ->with([
                'product' => fn ($q) => $q->with([
                    'media' => fn ($q) => $q->where('is_primary', true)->limit(1),
                ]),
            ])
            ->ordered()
            ->get()
            ->map(fn ($pivot) => [
                'product_id' => $pivot->product_id,
                'is_active' => $pivot->is_active,
                'is_featured' => $pivot->is_featured,
                'sort_order' => $pivot->sort_order,
                'product' => $pivot->product ? [
                    'id' => $pivot->product->id,
                    'name' => $pivot->product->name,
                    'hsn_code' => $pivot->product->hsn_code,
                    'show_in_storefront' => $pivot->product->show_in_storefront,
                    'primary_image_url' => $pivot->product->primary_image_url,
                ] : null,
            ]);
    }
}
