<?php

namespace App\Services;

use App\Models\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PageService
{
    // ════════════════════════════════════════════════════
    //  STOREFRONT (PUBLIC) METHODS
    // ════════════════════════════════════════════════════

    /**
     * Safely fetch a published page for the public storefront.
     */
    public function getPublicPage(string $slug, int $companyId): ?Page
    {
        try {
            return Page::where('company_id', $companyId)
                ->where('slug', $slug)
                ->published() // Uses the scope from our Model
                ->firstOrFail();
        } catch (Throwable $e) {
            Log::warning('[PageService] Public page not found or unpublished', [
                'company_id' => $companyId,
                'slug' => $slug,
            ]);

            return null; // Graceful fallback instead of crashing
        }
    }

    /**
     * Get footer links categorized by type.
     */
    public function getFooterLinks(int $companyId): array
    {
        $pages = Page::where('company_id', $companyId)
            ->published()
            ->get(['title', 'slug', 'type']);

        return [
            'service' => $pages->where('type', Page::TYPE_LEGAL),
            'information' => $pages->where('type', Page::TYPE_ABOUT),
            'custom' => $pages->where('type', Page::TYPE_CUSTOM),
        ];
    }

    // ════════════════════════════════════════════════════
    //  ADMIN (PRIVATE) METHODS
    // ════════════════════════════════════════════════════

    /**
     * Get paginated list for Admin Panel with filters.
     */
    public function getList(int $companyId, array $filters = []): LengthAwarePaginator
    {
        $query = Page::where('company_id', $companyId)->with(['creator', 'updater']);

        // Search by title or slug
        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', "%{$filters['search']}%")
                    ->orWhere('slug', 'like', "%{$filters['search']}%");
            });
        }

        // Filter by Type
        if (! empty($filters['type'])) {
            $query->ofType($filters['type']);
        }

        // Filter by Status
        if (isset($filters['is_published']) && $filters['is_published'] !== '') {
            $query->where('is_published', (bool) $filters['is_published']);
        }

        return $query->orderBy('title', 'asc')->paginate($filters['per_page'] ?? 15)->withQueryString();
    }

    /**
     * Create a new page.
     */
    public function create(array $data, int $companyId): Page
    {
        try {
            $data['company_id'] = $companyId;
            $data['created_by'] = Auth::id();

            // 1. Slug Handling: Use provided slug, or auto-generate from title
            $slugBase = ! empty($data['slug']) ? $data['slug'] : $data['title'];
            $data['slug'] = $this->generateUniqueSlug($slugBase, $companyId);

            // 2. SEO Fallback: If SEO title is empty, use the main title
            if (empty($data['seo_title'])) {
                $data['seo_title'] = $data['title'];
            }

            $page = Page::create($data);

            Log::info('[PageService] Page created successfully', [
                'page_id' => $page->id,
                'company_id' => $companyId,
                'slug' => $page->slug,
            ]);

            return $page;

        } catch (Throwable $e) {
            Log::error('[PageService] Failed to create page', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing page.
     */
    public function update(Page $page, array $data): Page
    {
        try {
            $data['updated_by'] = Auth::id();

            // Handle Slug updates carefully
            if (! empty($data['slug']) && $data['slug'] !== $page->slug) {
                // User manually changed the slug
                $data['slug'] = $this->generateUniqueSlug($data['slug'], $page->company_id, $page->id);
            } elseif (! empty($data['title']) && $data['title'] !== $page->title && empty($data['slug'])) {
                // Title changed and no custom slug provided? We usually KEEP the old slug to prevent broken links (SEO best practice).
                // However, if you want it to auto-update, you would call generateUniqueSlug here.
                // We will leave the slug alone to protect SEO rankings.
                unset($data['slug']);
            }

            $page->update($data);

            Log::info('[PageService] Page updated', ['page_id' => $page->id]);

            return $page->fresh();

        } catch (Throwable $e) {
            Log::error('[PageService] Failed to update page', [
                'page_id' => $page->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Quick toggle for publish status (useful for AJAX switches in UI).
     */
    public function togglePublish(Page $page): bool
    {
        $page->is_published = ! $page->is_published;
        $page->updated_by = Auth::id();
        $page->save();

        Log::info('[PageService] Page publish toggled', [
            'page_id' => $page->id,
            'new_status' => $page->is_published,
        ]);

        return $page->is_published;
    }

    /**
     * Soft delete a page.
     */
    public function delete(Page $page): void
    {
        $page->delete();
        Log::info('[PageService] Page soft deleted', ['page_id' => $page->id, 'by' => Auth::id()]);
    }

    // ════════════════════════════════════════════════════
    //  SAAS UTILITIES
    // ════════════════════════════════════════════════════

    /**
     * Generate default boilerplate pages for a new tenant.
     * Call this inside your Company Registration / Onboarding flow.
     */
    public function seedDefaultPages(int $companyId): void
    {
        $defaults = [
            ['title' => 'Return Policy', 'slug' => 'return-policy', 'type' => Page::TYPE_LEGAL],
            ['title' => 'FAQ', 'slug' => 'faq', 'type' => Page::TYPE_ABOUT],
            ['title' => 'Privacy & Policy', 'slug' => 'privacy-policy', 'type' => Page::TYPE_LEGAL],
            ['title' => 'Terms & Conditions', 'slug' => 'terms-and-conditions', 'type' => Page::TYPE_LEGAL],
            ['title' => 'About Us', 'slug' => 'about-us', 'type' => Page::TYPE_ABOUT],
            ['title' => 'Contact Us', 'slug' => 'contact-us', 'type' => Page::TYPE_ABOUT],
        ];

        DB::beginTransaction();
        try {
            foreach ($defaults as $page) {
                Page::firstOrCreate(
                    ['company_id' => $companyId, 'slug' => $page['slug']],
                    [
                        'title' => $page['title'],
                        'type' => $page['type'],
                        'is_published' => false, // Start as draft so tenant can fill in their details
                        'content' => "<h2>{$page['title']}</h2><p>Please update this content with your company specifics.</p>",
                        'seo_title' => $page['title'],
                    ]
                );
            }
            DB::commit();
            Log::info('[PageService] Default pages seeded', ['company_id' => $companyId]);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('[PageService] Failed to seed default pages', ['company_id' => $companyId, 'error' => $e->getMessage()]);
        }
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ════════════════════════════════════════════════════

    /**
     * Ensures a slug is completely unique for the specific company.
     * Appends -1, -2 if duplicates are found.
     */
    private function generateUniqueSlug(string $string, int $companyId, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($string);
        $slug = $baseSlug;
        $counter = 1;

        while (Page::where('company_id', $companyId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
