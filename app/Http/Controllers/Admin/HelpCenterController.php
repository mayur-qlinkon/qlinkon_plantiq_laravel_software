<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Platform\HelpArticle;
use App\Models\Platform\HelpCategory;
use App\Models\Platform\HelpArticleFeedback;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class HelpCenterController extends Controller
{
    /**
     * Browse the help center — categories + article counts.
     * Accessible to all authenticated tenant users.
     */
    public function index(Request $request): View
    {
        $search = $request->input('search');

        // If searching, show flat article list across all categories
        if ($search) {
            $articles = HelpArticle::with('category')
                ->published()
                ->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%");
                })
                ->ordered()
                ->paginate(15)
                ->withQueryString();

            $categories = collect(); // not needed for search results view

            return view('admin.help.index', compact('articles', 'categories', 'search'));
        }

        // Default: show categories with published article counts
        $categories = HelpCategory::active()
            ->ordered()
            ->withCount(['articles as article_count' => fn ($q) => $q->where('is_published', true)])
            ->having('article_count', '>', 0) // hide empty categories from tenants
            ->get();

        $articles = collect();

        return view('admin.help.index', compact('articles', 'categories', 'search'));
    }

    /**
     * Show a single article — rendered Markdown + optional YouTube embed.
     */
    public function show(string $article_slug): View
    {
        // Find published article by slug — abort 404 if not found or draft
        $article = HelpArticle::with('category')
            ->published()
            ->where('slug', $article_slug)
            ->firstOrFail();

        // Render Markdown → safe HTML
        // Str::markdown() uses league/commonmark (bundled with Laravel 9+)
        $renderedContent = Str::markdown($article->content ?? '', [
            'html_input'         => 'strip',    // strip raw HTML from content for safety
            'allow_unsafe_links' => false,
        ]);

        // Prev / Next navigation within the same category
        $siblings = HelpArticle::published()
            ->where('help_category_id', $article->help_category_id)
            ->ordered()
            ->pluck('slug', 'id');

        $ids      = $siblings->keys()->toArray();
        $position = array_search($article->id, $ids);

        $prevSlug = ($position > 0) ? $siblings->values()[$position - 1] : null;
        $nextSlug = ($position < count($ids) - 1) ? $siblings->values()[$position + 1] : null;

        $prevArticle = $prevSlug ? HelpArticle::where('slug', $prevSlug)->first() : null;
        $nextArticle = $nextSlug ? HelpArticle::where('slug', $nextSlug)->first() : null;

        return view('admin.help.show', compact(
            'article',
            'renderedContent',
            'prevArticle',
            'nextArticle'
        ));
    }
    /**
     * Store user feedback (helpful or not) for a specific article.
     * Prevents rapid double-voting from the same IP address.
     */
    public function storeFeedback(Request $request, string $article_slug): JsonResponse
    {
        // 1. Ensure the article actually exists
        $article = HelpArticle::published()
            ->where('slug', $article_slug)
            ->firstOrFail();

        $isHelpful = $request->boolean('is_helpful');
        $ipAddress = $request->ip();
        $userId    = Auth::id();

        // 2. Prevent spam / duplicate voting within a short period (e.g., last 24 hours).
        // Match by logged-in user when available (reliable behind shared/NAT IPs);
        // fall back to IP for any guest/unauthenticated access.
        $existingFeedback = HelpArticleFeedback::where('help_article_id', $article->id)
            ->when($userId, fn ($q) => $q->where('user_id', $userId), fn ($q) => $q->where('ip_address', $ipAddress))
            ->where('created_at', '>=', now()->subDay())
            ->first();

        if ($existingFeedback) {
            // If they clicked the same button, just return success quietly
            if ($existingFeedback->is_helpful === $isHelpful) {
                return response()->json([
                    'success' => true,
                    'message' => 'Thank you! Your feedback has already been recorded.',
                ]);
            }

            // If they changed their mind (e.g., from helpful to unhelpful), update it
            $existingFeedback->update([
                'is_helpful' => $isHelpful,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Your feedback has been updated. Thank you!',
            ]);
        }

        // 3. Create fresh feedback entry
        HelpArticleFeedback::create([
            'help_article_id' => $article->id,
            'user_id'         => $userId,
            'is_helpful'      => $isHelpful,
            'ip_address'      => $ipAddress,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your valuable feedback!',
        ]);
    }
}