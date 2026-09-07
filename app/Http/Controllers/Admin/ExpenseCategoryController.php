<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;

use App\Services\Platform\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseCategoryController extends Controller
{
    /**
     * Inject the ImageUploadService.
     */
    public function __construct(protected ImageUploadService $imageService) {}

    /**
     * Display a listing of the resource (Single Page CRUD).
     */
    public function index()
    {        

        $categories = ExpenseCategory::with('parent')            
            ->withCount('expenses')
            ->ordered()
            ->get();

        $rootCategories = ExpenseCategory::root()            
            ->active()
            ->ordered()
            ->get();

        return view('admin.expenses.categories', compact('categories', 'rootCategories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'exists:expense_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:50'],
            'icon' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:10240'], // Adjusted max to match your service 10MB
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:direct,indirect,asset'],
            'gst_type' => ['nullable', 'in:taxable,non_taxable,exempt'],
            'account_code' => ['nullable', 'string', 'max:50'],
            'hsn_sac_code' => ['nullable', 'string', 'max:50'],
            'default_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'position' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['default_tax_rate'] = $validated['default_tax_rate'] ?? 0;                

        // Use the ImageUploadService for clean handling
        if ($request->hasFile('icon')) {
            $validated['icon'] = $this->imageService->upload(
                $request->file('icon'),
                'expense_category_icons',
                ['width' => 200, 'height' => 200] // Optional: scale the icon down to keep it lightweight
            );
        }

        ExpenseCategory::create($validated);

        return back()->with('success', 'Expense category created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        
        $validated = $request->validate([
            'parent_id' => [
                'nullable',
                'exists:expense_categories,id',
                Rule::notIn([$expenseCategory->id]),
            ],
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:50'],
            'icon' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:10240'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:direct,indirect,asset'],
            'gst_type' => ['nullable', 'in:taxable,non_taxable,exempt'],
            'account_code' => ['nullable', 'string', 'max:50'],
            'hsn_sac_code' => ['nullable', 'string', 'max:50'],
            'default_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'position' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', false);
        $validated['default_tax_rate'] = $validated['default_tax_rate'] ?? 0;

        // Use the ImageUploadService. It automatically handles deleting the old file!
        if ($request->hasFile('icon')) {
            $validated['icon'] = $this->imageService->upload(
                $request->file('icon'),
                'expense_category_icons',
                [
                    'old_file' => $expenseCategory->icon, // Pass the old file path for auto-deletion
                    'width' => 200,
                    'height' => 200,
                ]
            );
        }

        $expenseCategory->update($validated);

        return back()->with('success', 'Expense category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExpenseCategory $expenseCategory)
    {        

        if ($expenseCategory->expenses()->exists()) {
            return back()->with('error', 'Cannot delete this category because it has existing expenses associated with it.');
        }

        if ($expenseCategory->children()->exists()) {
            return back()->with('error', 'Cannot delete this category because it contains sub-categories. Reassign them first.');
        }

        // Clean up the image file upon deletion using your service
        $this->imageService->delete($expenseCategory->icon);

        $expenseCategory->delete();

        return back()->with('success', 'Expense category deleted successfully.');
    }

    /**
     * Optional: Quick toggle for is_active status directly from the table.
     */
    public function toggleStatus(ExpenseCategory $expenseCategory)
    {        
        $expenseCategory->update([
            'is_active' => ! $expenseCategory->is_active,
        ]);

        return back()->with('success', 'Category status updated.');
    }
}
