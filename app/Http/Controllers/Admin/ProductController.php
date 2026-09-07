<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of the products.
     */
    public function index(Request $request)
    {
        // Get all filters from the URL query string (e.g., ?search=shirt&category_id=2)
        $filters = $request->only(['search', 'category_id', 'status']);

        // Fetch products using the Service
        $products = $this->productService->getProductsList($filters);

        // Fetch categories for the filter dropdown
        $categories = Category::where('is_active', true)->get();

        return view('admin.products.index', compact('products', 'categories', 'filters'));
    }

    /**
     * Toggle Product Status (AJAX Request)
     */
    public function toggleStatus(Product $product)
    {
        $this->productService->toggleStatus($product);

        return response()->json([
            'success' => true,
            'message' => 'Product status updated successfully.',
            'is_active' => $product->is_active,
        ]);
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product)
    {
        $this->productService->deleteProduct($product);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    /**
     * Remove multiple products from storage.
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:products,id',
        ]);

        try {
            $products = Product::whereIn('id', $request->ids)->get();

            foreach ($products as $product) {
                // Utilizing your existing service logic
                $this->productService->deleteProduct($product);
            }

            // Flash success message for when the page reloads
            session()->flash('success', count($request->ids).' products deleted successfully.');

            return response()->json([
                'success' => true,
                'message' => 'Products deleted successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting products.',
            ], 500);
        }
    }

    public function create()
    {
        if (! check_plan_limit('products')) {
            return back()->with('error', 'You have reached your plan\'s Product limit. Please upgrade your subscription to add more products.');
        }
        // Fetch all necessary dropdown data for the UI
        $categories = Category::where('is_active', true)->get();
        $units = Unit::where('is_active', true)->get();
        $suppliers = Supplier::where('is_active', true)->get();
        $warehouses = Warehouse::where('is_active', true)->get();
        $attributes = Attribute::with('values')->where('is_active', true)->get();
        $stores = auth_stores()->get(); // Fetch authorized stores

        return view('admin.products.create', compact(
            'categories', 'units', 'suppliers', 'warehouses', 'attributes', 'stores'
        ));
    }

    public function store(StoreProductRequest $request)
    {
        if (! check_plan_limit('products')) {
            return back()->with('error', 'You have reached your plan\'s Product limit. Please upgrade your subscription to add more products.');
        }
        $validated = $request->validated();

        $this->productService->createProduct(
            $validated,
            Auth::user()->company_id,
            null
        );

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully!');
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product)
    {
        // Eager load all related data so the Edit view can pre-fill the form
        $product->load(['skus.skuValues', 'media']);

        $categories = Category::where('is_active', true)->get();
        $units = Unit::where('is_active', true)->get();
        $suppliers = Supplier::where('is_active', true)->get();
        $attributes = Attribute::with('values')->where('is_active', true)->get();
        $warehouses = Warehouse::where('is_active', true)->get();
        $stores = auth_stores()->get(); // Fetch authorized stores

        return view('admin.products.edit', compact(
            'product', 'categories', 'units', 'suppliers', 'attributes','warehouses', 'stores'
        ));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $validated = $request->validated();

        $this->productService->updateProduct(
            $product,
            $validated,
            Auth::user()->company_id,
            null
        );

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully!');
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product)
    {
        // Eager load EVERYTHING we need for the view
        $product->load([
            'category',
            'supplier',
            'media',
            'skus.skuValues.attribute',
            'skus.skuValues.attributeValue',
            'skus.stocks' => function ($q) {
                $warehouseIds = Warehouse::where('store_id', optional(active_store())->id)->pluck('id');
                $q->whereIn('warehouse_id', $warehouseIds)->with('warehouse');
            },
        ]);

        // Load units separately (since they are referenced directly on the product)
        $product->load(['productUnit', 'saleUnit', 'purchaseUnit']);

        $warehouses = Warehouse::where('is_active', true)
            ->where('store_id', optional(active_store())->id)
            ->orderBy('name')->get();

        return view('admin.products.show', compact('product','warehouses'));
    }

    public function duplicate(Product $product)
    {
        if (! check_plan_limit('products')) {
            return back()->with('error', 'You have reached your plan\'s Product limit. Please upgrade your subscription to add more products.');
        }
        $newProduct = $this->productService->duplicateProduct($product);

        return redirect()
            ->route('admin.products.edit', $newProduct->id)
            ->with('success', "'{$product->name}' duplicated. Review and activate when ready.");
    }
}
