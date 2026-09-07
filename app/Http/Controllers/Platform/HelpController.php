<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;

use App\Models\Platform\HelpArticle;
use App\Models\Platform\HelpCategory;
use App\Models\Platform\HelpArticleFeedback;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class HelpController extends Controller
{
    // =========================================================================
    // ARTICLES
    // =========================================================================

    /**
     * List all articles with optional category filter.
     */
    public function index(Request $request): View
    {
        $categories = HelpCategory::ordered()->get();

        $articles = HelpArticle::with('category')
            ->when($request->category_id, fn ($q, $id) => $q->where('help_category_id', $id))
            ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return view('platform.help.index', compact('articles', 'categories'));
    }

    /**
     * Store a new article.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'help_category_id' => ['required', 'exists:help_categories,id'],
            'title'            => ['required', 'string', 'max:200'],
            'slug'             => ['nullable', 'string', 'max:220', 'unique:help_articles,slug'],
            'content'          => ['required', 'string'],
            'video_url'        => ['nullable', 'url', 'max:500'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
            'is_published'     => ['nullable', 'boolean'],
        ]);

        // Slug: use provided, or auto-generate from title (model boot handles uniqueness)
        if (empty($validated['slug'])) {
            unset($validated['slug']); // let model boot auto-generate
        }

        $validated['is_published'] = $request->boolean('is_published');
        $validated['sort_order']   = $validated['sort_order'] ?? 0;

        HelpArticle::create($validated);

        return redirect()->route('platform.help.index')
            ->with('success', 'Article created successfully.');
    }

    /**
     * Update an existing article.
     */
    public function update(Request $request, HelpArticle $helpArticle): RedirectResponse
    {
        $validated = $request->validate([
            'help_category_id' => ['required', 'exists:help_categories,id'],
            'title'            => ['required', 'string', 'max:200'],
            'slug'             => ['nullable', 'string', 'max:220', Rule::unique('help_articles', 'slug')->ignore($helpArticle->id)],
            'content'          => ['required', 'string'],
            'video_url'        => ['nullable', 'url', 'max:500'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
            'is_published'     => ['nullable', 'boolean'],
        ]);

        // Regenerate slug if title changed and no custom slug provided
        if (empty($validated['slug'])) {
            $validated['slug'] = $this->uniqueSlug($validated['title'], $helpArticle->id);
        }

        $validated['is_published'] = $request->boolean('is_published');
        $validated['sort_order']   = $validated['sort_order'] ?? 0;

        $helpArticle->update($validated);

        return redirect()->route('platform.help.index')
            ->with('success', 'Article updated successfully.');
    }

    /**
     * Delete an article.
     */
    public function destroy(HelpArticle $helpArticle): RedirectResponse
    {
        $helpArticle->delete();

        return back()->with('success', 'Article deleted.');
    }

    /**
     * Quick toggle published/draft without opening edit form.
     */
    public function togglePublish(HelpArticle $helpArticle): RedirectResponse
    {
        $helpArticle->update([
            'is_published' => ! $helpArticle->is_published
        ]);

        $label = $helpArticle->is_published
            ? 'published'
            : 'set to draft';

        return back()->with(
            'success',
            "Article \"{$helpArticle->title}\" {$label}."
        );
    }
    // =========================================================================
    // FEEDBACK
    // =========================================================================

    /**
     * List all article feedback.
     */
    public function feedbackIndex(Request $request): View
    {
        // Get Stats
        $totalFeedback = HelpArticleFeedback::count();
        $helpfulCount = HelpArticleFeedback::where('is_helpful', true)->count();
        $notHelpfulCount = HelpArticleFeedback::where('is_helpful', false)->count();

        // Get Paginated Data
        $feedbacks = HelpArticleFeedback::with(['article', 'user.company'])
            ->latest() // Naya feedback sabse upar dikhane ke liye
            ->when($request->filled('is_helpful'), function ($q) use ($request) {
                return $q->where('is_helpful', $request->boolean('is_helpful'));
            })
            ->paginate(30)
            ->withQueryString();

        return view('platform.help.feedback', compact('feedbacks', 'totalFeedback', 'helpfulCount', 'notHelpfulCount'));
    }

    // =========================================================================
    // CATEGORIES
    // =========================================================================

    /**
     * List all categories.
     */
    public function categoriesIndex(): View
    {
        $categories = HelpCategory::withCount('articles')
            ->withCount(['articles as published_articles_count' => fn ($q) => $q->where('is_published', true)])
            ->ordered()
            ->get();

        return view('platform.help.categories', compact('categories'));
    }

    /**
     * Store a new category.
     */
    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'      => ['required', 'string', 'max:100', 'unique:help_categories,title'],
            'slug'       => ['nullable', 'string', 'max:120', 'unique:help_categories,slug'],
            'icon'       => ['nullable', 'string', 'max:60'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        $validated['is_active']  = $request->boolean('is_active', true);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        if (empty($validated['slug'])) {
            unset($validated['slug']); // model boot auto-generates
        }

        HelpCategory::create($validated);

        return redirect()->route('platform.help.categories')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Update an existing category.
     */
    public function updateCategory(Request $request, HelpCategory $helpCategory): RedirectResponse
    {
        $validated = $request->validate([
            'title'      => ['required', 'string', 'max:100', Rule::unique('help_categories', 'title')->ignore($helpCategory->id)],
            'slug'       => ['nullable', 'string', 'max:120', Rule::unique('help_categories', 'slug')->ignore($helpCategory->id)],
            'icon'       => ['nullable', 'string', 'max:60'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $validated['is_active']  = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $helpCategory->update($validated);

        return redirect()->route('platform.help.categories')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Delete a category — only if it has no articles.
     */
    public function destroyCategory(HelpCategory $helpCategory): RedirectResponse
    {
        if ($helpCategory->articles()->exists()) {
            return back()->with('error', 'Cannot delete category — move or delete its articles first.');
        }

        $helpCategory->delete();

        return back()->with('success', 'Category deleted.');
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Generate a unique slug for articles, excluding the current record during update.
     */
    private function uniqueSlug(string $title, ?int $excludeId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i    = 1;

        while (
            HelpArticle::where('slug', $slug)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}