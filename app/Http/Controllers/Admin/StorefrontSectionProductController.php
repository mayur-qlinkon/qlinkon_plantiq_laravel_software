<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StorefrontSection;
use App\Models\StorefrontSectionProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StorefrontSectionProductController extends Controller
{
    // ════════════════════════════════════════════════════
    //  INDEX PAGE — two-panel product manager
    // ════════════════════════════════════════════════════

    public function index(StorefrontSection $storefrontSection)
    {
        $this->authorizeSection($storefrontSection);

        // Abort if not manual type
        abort_if(
            $storefrontSection->type !== 'manual',
            403,
            'Product manager is only available for Manual Selection sections.'
        );

        $section = $storefrontSection->load(['manualProducts.product.media', 'manualProducts.product.skus']);

        return view('admin.storefront-sections.products', compact('section'));
    }

    // ════════════════════════════════════════════════════
    //  LOAD — get products already in this section (AJAX)
    // ════════════════════════════════════════════════════

    public function load(StorefrontSection $storefrontSection): JsonResponse
    {
        $this->authorizeSection($storefrontSection);

        $products = StorefrontSectionProduct::forSection($storefrontSection->id)
            ->ordered()
            ->with(['product' => fn ($q) => $q->with([
                'media' => fn ($m) => $m->where('is_primary', true)->limit(1),
                'skus' => fn ($s) => $s->limit(1),
            ])])
            ->get()
            ->map(fn ($pivot) => [
                'pivot_id' => $pivot->id,
                'product_id' => $pivot->product_id,
                'sort_order' => $pivot->sort_order,
                'name' => $pivot->product->name,
                'image' => $pivot->product->primary_image_url,
                'price' => $pivot->product->skus->first()?->price ?? 0,
                'sku' => $pivot->product->skus->first()?->sku ?? null,
            ]);

        return response()->json([
            'success' => true,
            'products' => $products,
            'count' => $products->count(),
        ]);
    }

    // ════════════════════════════════════════════════════
    //  SEARCH — find unassigned products (AJAX)
    // ════════════════════════════════════════════════════

    public function search(StorefrontSection $storefrontSection, Request $request): JsonResponse
    {
        $this->authorizeSection($storefrontSection);

        $query = trim($request->get('q', ''));

        // Get already-assigned product IDs to exclude
        $assignedIds = StorefrontSectionProduct::forSection($storefrontSection->id)
            ->pluck('product_id')
            ->toArray();

        $products = Product::where('is_active', true)
            ->whereNotIn('id', $assignedIds)
            ->when(strlen($query) >= 1, fn ($q) => $q->where(fn ($inner) => $inner->where('name', 'like', "%{$query}%")
                ->orWhereHas('skus', fn ($s) => $s->where('sku', 'like', "%{$query}%")
                )
            )
            )
            ->with([
                'media' => fn ($q) => $q->where('is_primary', true)->limit(1),
                'skus' => fn ($q) => $q->limit(1),
            ])
            ->limit(20)
            ->get()
            ->map(fn ($product) => [
                'product_id' => $product->id,
                'name' => $product->name,
                'image' => $product->primary_image_url,
                'price' => $product->skus->first()?->price ?? 0,
                'sku' => $product->skus->first()?->sku ?? null,
            ]);

        return response()->json([
            'success' => true,
            'products' => $products,
        ]);
    }

    // ════════════════════════════════════════════════════
    //  ADD PRODUCT (AJAX)
    // ════════════════════════════════════════════════════

    public function add(StorefrontSection $storefrontSection, Request $request): JsonResponse
    {
        $this->authorizeSection($storefrontSection);

        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $productId = (int) $request->product_id;

        // Verify product belongs to same company
        $product = Product::where('id', $productId)
            ->firstOrFail();

        $pivot = StorefrontSectionProduct::addProduct($storefrontSection->id, $productId);

        Log::info('[SectionProduct] Added', [
            'section_id' => $storefrontSection->id,
            'product_id' => $productId,
            'by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "'{$product->name}' added to section.",
            'pivot_id' => $pivot->id,
            'product_id' => $productId,
            'name' => $product->name,
            'image' => $product->primary_image_url,
            'price' => $product->skus()->first()?->price ?? 0,
        ]);
    }

    // ════════════════════════════════════════════════════
    //  REMOVE PRODUCT (AJAX)
    // ════════════════════════════════════════════════════

    public function remove(
        StorefrontSection $storefrontSection,
        int $productId
    ): JsonResponse {
        $this->authorizeSection($storefrontSection);

        $removed = StorefrontSectionProduct::removeProduct($storefrontSection->id, $productId);

        Log::info('[SectionProduct] Removed', [
            'section_id' => $storefrontSection->id,
            'product_id' => $productId,
            'by' => Auth::id(),
        ]);

        return response()->json([
            'success' => $removed,
            'message' => $removed ? 'Product removed.' : 'Product not found.',
        ]);
    }

    // ════════════════════════════════════════════════════
    //  REORDER (AJAX drag-drop)
    // ════════════════════════════════════════════════════

    public function reorder(StorefrontSection $storefrontSection, Request $request): JsonResponse
    {
        $this->authorizeSection($storefrontSection);

        $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        StorefrontSectionProduct::reorderInSection(
            $storefrontSection->id,
            $request->product_ids
        );

        Log::info('[SectionProduct] Reordered', [
            'section_id' => $storefrontSection->id,
            'order' => $request->product_ids,
            'by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order saved.',
        ]);
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE
    // ════════════════════════════════════════════════════

    private function authorizeSection(StorefrontSection $section): void
    {
        if ($section->company_id !== Auth::user()->company_id) {
            abort(403, 'Access denied.');
        }
    }
}
