<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;

use App\Services\Platform\ImageUploadService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    protected ImageUploadService $imageService;

    // Inject the ImageUploadService through the constructor
    public function __construct(ImageUploadService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function index()
    {
        $categories = Category::orderBy('created_at', 'desc')->get();

        return view('admin.products.categories', compact('categories'));
    }

    public function store(Request $request)
    {
        // 1. Generate slug and set active status before validation
        $request->merge([
            'slug' => $request->filled('slug') ? Str::slug($request->slug) : Str::slug($request->name),
            'is_active' => $request->has('is_active'),
        ]);

        $companyId = Auth::user()->company_id ?? null;

        // 2. Validate with SaaS Scoping
        // whereNull('deleted_at'): Rule::unique() queries the table directly and
        // does not apply the SoftDeletes scope, so a deleted category kept its
        // name reserved forever against a row the user cannot see.
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('categories')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],
            'slug' => [
                'required', 'string', 'max:255',
                Rule::unique('categories')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:10240', // Max 10MB to match service
            'is_active' => 'boolean',            
        ]);

        try {
            // 3. Handle Image Upload using the custom Service
            if ($request->hasFile('image_file')) {
                $data['image'] = $this->imageService->upload(
                    $request->file('image_file'),
                    'categories',
                    [
                        'format' => 'webp', // Convert everything to webp for performance
                        'width' => 600,     // Optional: Standardize thumbnail size
                        'height' => 600,
                        'quality' => 85,
                    ]
                );
            }

            // 4. Save to Database
            Category::create($data);

            Log::info('[CategoryController] Category created successfully', ['name' => $data['name'], 'company_id' => $companyId]);

            return redirect()->back()->with('success', 'Category created successfully!');

        } catch (\Throwable $e) {
            // Log the detailed error for debugging
            Log::error('[CategoryController] Store failed: '.$e->getMessage(), [
                'company_id' => $companyId,
                'trace' => $e->getTraceAsString(),
            ]);

            // Return a user-friendly error to the UI
            return redirect()->back()
                ->withErrors(['error' => 'Failed to create category. Please check the logs or try again.'])
                ->withInput();
        }
    }

    public function update(Request $request, Category $category)
    {
        $request->merge([
            'slug' => $request->filled('slug') ? Str::slug($request->slug) : Str::slug($request->name),
            'is_active' => $request->has('is_active'),
        ]);

        $companyId = Auth::user()->company_id ?? null;

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('categories')->where('company_id', $companyId)->whereNull('deleted_at')->ignore($category->id),
            ],
            'slug' => [
                'required', 'string', 'max:255',
                Rule::unique('categories')->where('company_id', $companyId)->whereNull('deleted_at')->ignore($category->id),
            ],
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:10240',
            'is_active' => 'boolean',
        ]);

        try {
            // Handle Image Upload & automatic old file deletion via Service
            if ($request->hasFile('image_file')) {
                $data['image'] = $this->imageService->upload(
                    $request->file('image_file'),
                    'categories',
                    [
                        'old_file' => $category->image, // The service will safely delete this!
                        'format' => 'webp',
                        'width' => 600,
                        'height' => 600,
                        'quality' => 85,
                    ]
                );
            }

            $category->update($data);

            Log::info('[CategoryController] Category updated successfully', ['category_id' => $category->id]);

            return redirect()->back()->with('success', 'Category updated successfully!');

        } catch (\Throwable $e) {
            Log::error('[CategoryController] Update failed: '.$e->getMessage(), [
                'category_id' => $category->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Failed to update category. Please check the logs or try again.'])
                ->withInput();
        }
    }

    public function destroy(Category $category)
    {
        try {
            // We use SoftDeletes on Category, so we DO NOT delete the physical image file yet.
            // If you eventually implement a "Force Delete" method, you would call:
            // $this->imageService->delete($category->image);

            $category->delete();

            Log::info('[CategoryController] Category soft-deleted', ['category_id' => $category->id]);

            return redirect()->back()->with('success', 'Category moved to trash!');

        } catch (\Throwable $e) {
            Log::error('[CategoryController] Destroy failed: '.$e->getMessage(), [
                'category_id' => $category->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->withErrors(['error' => 'Failed to delete category.']);
        }
    }
}
