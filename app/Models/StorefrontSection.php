<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// The Iron Wall

class StorefrontSection extends Model
{
    use SoftDeletes,Tenantable;

    protected $fillable = [
        'company_id',
        'title',
        'admin_label',
        'subtitle',
        'type',
        'category_id',
        'banner_position',
        'layout',
        'products_limit',
        'columns',
        'show_view_all',
        'view_all_url',
        'bg_color',
        'heading_color',
        'show_section_title',
        'custom_html',
        'is_active',
        'sort_order',
        'starts_at',
        'ends_at',
        'show_on_mobile',
        'show_on_desktop',
        'view_count',
        'click_count',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_view_all' => 'boolean',
        'show_section_title' => 'boolean',
        'show_on_mobile' => 'boolean',
        'show_on_desktop' => 'boolean',
        'products_limit' => 'integer',
        'columns' => 'integer',
        'sort_order' => 'integer',
        'view_count' => 'integer',
        'click_count' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    // ── Valid types & layouts for validation use ──
    public const TYPES = [
        'category',
        'featured',
        'new_arrivals',
        'best_sellers',
        'manual',
        'banner',
        'custom_html',
    ];

    public const LAYOUTS = [
        'grid',
        'list',
        'carousel',
        'horizontal_scroll',
    ];

    public const TYPE_LABELS = [
        'category' => 'Category Products',
        'featured' => 'Featured Products',
        'new_arrivals' => 'New Arrivals',
        'best_sellers' => 'Best Sellers',
        'manual' => 'Manual Selection',
        'banner' => 'Banner Section',
        'custom_html' => 'Custom HTML Block',
    ];

    public const LAYOUT_LABELS = [
        'grid' => 'Grid',
        'list' => 'List',
        'carousel' => 'Carousel / Slider',
        'horizontal_scroll' => 'Horizontal Scroll',
    ];

    // ════════════════════════════════════════════════════
    //  BOOT
    // ════════════════════════════════════════════════════

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (StorefrontSection $section) {
            // Auto company isolation
            if (empty($section->company_id) && Auth::check()) {
                $section->company_id = Auth::user()->company_id;
            }

            // Auto audit
            if (empty($section->created_by) && Auth::check()) {
                $section->created_by = Auth::id();
            }

            // Auto sort_order — append to end
            if (! isset($section->sort_order)) {
                $max = static::where('company_id', $section->company_id)->max('sort_order');
                $section->sort_order = ($max ?? -1) + 1;
            }
        });

        static::updating(function (StorefrontSection $section) {
            if (Auth::check()) {
                $section->updated_by = Auth::id();
            }
        });
    }

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function manualProducts(): HasMany
    {
        return $this->hasMany(StorefrontSectionProduct::class)
            ->orderBy('sort_order');
    }

    // ════════════════════════════════════════════════════
    //  SCOPES
    // ════════════════════════════════════════════════════

    /**
     * Only active sections.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Sections currently live — respects scheduling.
     */
    public function scopeIsLive(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            });
    }

    /**
     * Ordered by sort_order for homepage rendering.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc');
    }

    /**
     * Only sections of a specific type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Only sections for a specific company.
     */
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Sections visible on mobile.
     */
    public function scopeMobileVisible(Builder $query): Builder
    {
        return $query->where('show_on_mobile', true);
    }

    /**
     * Sections visible on desktop.
     */
    public function scopeDesktopVisible(Builder $query): Builder
    {
        return $query->where('show_on_desktop', true);
    }

    // ════════════════════════════════════════════════════
    //  ACCESSORS
    // ════════════════════════════════════════════════════

    /**
     * Is this section live right now (respects scheduling)?
     */
    public function getIsLiveNowAttribute(): bool
    {
        if (! $this->is_active) {
            return false;
        }
        $now = now();
        if ($this->starts_at && $this->starts_at->gt($now)) {
            return false;
        }
        if ($this->ends_at && $this->ends_at->lt($now)) {
            return false;
        }

        return true;
    }

    /**
     * Human-readable type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? ucfirst($this->type);
    }

    /**
     * Human-readable layout label.
     */
    public function getLayoutLabelAttribute(): string
    {
        return self::LAYOUT_LABELS[$this->layout] ?? ucfirst($this->layout);
    }

    /**
     * CTR percentage.
     */
    public function getCtrAttribute(): float
    {
        if ($this->view_count === 0) {
            return 0.0;
        }

        return round(($this->click_count / $this->view_count) * 100, 1);
    }

    /**
     * Resolved "View All" URL.
     * Intentionally restricted to category sections only.
     * Featured / new_arrivals / best_sellers / manual / banner / custom_html
     * have no destination page and should hide the button.
     */
    public function getResolvedViewAllUrlAttribute(): ?string
    {
        if ($this->type !== 'category' || ! $this->category) {
            return null;
        }

        if ($this->view_all_url) {
            return $this->view_all_url;
        }

        return route('storefront.category', [
            'slug' => $this->company->slug ?? '',
            'categorySlug' => $this->category->slug,
        ]);
    }

    /**
     * Admin-facing label used in listings.
     * Falls back to storefront title, then a friendly type-based label.
     */
    public function getDisplayAdminLabelAttribute(): string
    {
        if (! empty($this->admin_label)) {
            return $this->admin_label;
        }

        if (! empty($this->title)) {
            return $this->title;
        }

        return 'Untitled '.(self::TYPE_LABELS[$this->type] ?? ucfirst((string) $this->type)).' Section';
    }

    // ════════════════════════════════════════════════════
    //  PRODUCT RESOLUTION — the core engine
    // ════════════════════════════════════════════════════

    /**
     * Resolve products for this section based on its type.
     * Returns a collection ready to render on storefront.
     *
     * Usage in StorefrontController:
     *   $section->resolveProducts()
     */
    public function resolveProducts(?int $limit = null): Collection
    {
        $limit = $limit ?? $this->products_limit;

        return match ($this->type) {

            'category' => $this->resolveCategory($limit),

            'featured' => Product::withoutGlobalScope('tenant')
                ->where('products.company_id', $this->company_id)
                ->where('products.show_in_storefront', true)
                ->where('products.is_active', true)
                ->whereNull('products.deleted_at')
                ->whereHas('categoryPivots', fn ($q) => $q->where('is_featured', true))
                ->with(['media', 'skus'])
                ->limit($limit)
                ->get(),

            'new_arrivals' => Product::withoutGlobalScope('tenant')
                ->where('products.company_id', $this->company_id)
                ->where('products.show_in_storefront', true)
                ->where('products.is_active', true)
                ->whereNull('products.deleted_at')
                ->with(['media', 'skus'])
                ->latest('products.created_at')
                ->limit($limit)
                ->get(),

            'best_sellers' => Product::withoutGlobalScope('tenant')
                ->where('products.company_id', $this->company_id)
                ->where('products.show_in_storefront', true)
                ->where('products.is_active', true)
                ->whereNull('products.deleted_at')
                ->with(['media', 'skus'])
                ->orderBy('products.total_sold', 'desc')
                ->orderBy('products.created_at', 'desc')
                ->limit($limit)
                ->get(),
            'manual' => $this->resolveManual($limit),

            'banner' => $this->resolveBanners(),

            default => new Collection,
        };
    }

    /**
     * Resolve category products respecting pivot sort_order and is_featured.
     */
    private function resolveCategory(int $limit): Collection
    {
        if (! $this->category_id) {
            return new Collection;
        }

        return Product::withoutGlobalScope('tenant') // bypass Tenantable — we scope manually below
            ->where('products.company_id', $this->company_id)
            ->where('products.show_in_storefront', true)
            ->where('products.is_active', true)
            ->whereNull('products.deleted_at')
            ->whereHas('categoryPivots', function (Builder $q) {
                $q->where('category_id', $this->category_id)
                    ->where('category_products.is_active', true);
            })
            ->with(['media', 'skus',
                'categoryPivots' => fn ($q) => $q->where('category_id', $this->category_id),
            ])
            ->join('category_products as cp',
                fn ($join) => $join
                    ->on('products.id', '=', 'cp.product_id')
                    ->where('cp.category_id', $this->category_id)
            )
            ->orderBy('cp.is_featured', 'desc')
            ->orderBy('cp.sort_order', 'asc')
            ->select('products.*')
            ->limit($limit)
            ->get();
    }

    private function resolveBanners(): Collection
    {
        if (! $this->banner_position) {
            return new Collection;
        }

        $now = now();
        $limit = $this->products_limit ?? 10; // reuse products_limit as banner limit

        return Banner::where('company_id', $this->company_id)
            ->where('position', $this->banner_position)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();
    }

    private function resolveManual(int $limit): Collection
    {
        return Product::withoutGlobalScope('tenant')
            ->whereHas('sectionPivots', fn ($q) => $q->where('storefront_section_id', $this->id)
            )
            ->where('products.company_id', $this->company_id)
            ->where('products.is_active', true)
            ->where('products.show_in_storefront', true)
            ->whereNull('products.deleted_at')
            ->with(['media', 'skus'])
            ->join('storefront_section_products as ssp',
                fn ($join) => $join->on('products.id', '=', 'ssp.product_id')
                    ->where('ssp.storefront_section_id', $this->id)
            )
            ->orderBy('ssp.sort_order')
            ->select('products.*')
            ->limit($limit)
            ->get();
    }

    // ════════════════════════════════════════════════════
    //  STATIC HELPERS
    // ════════════════════════════════════════════════════

    /**
     * Reorder sections for a company — single CASE WHEN query.
     */
    public static function reorderForCompany(int $companyId, array $sectionIds): bool
    {
        if (empty($sectionIds)) {
            return true;
        }

        $cases = '';
        $ids = implode(',', array_map('intval', $sectionIds));

        foreach ($sectionIds as $sortOrder => $id) {
            $cases .= "WHEN {$id} THEN {$sortOrder} ";
        }

        DB::statement("
            UPDATE storefront_sections
            SET sort_order = CASE id {$cases} END,
                updated_at = NOW()
            WHERE company_id = {$companyId}
              AND id IN ({$ids})
              AND deleted_at IS NULL
        ");

        return true;
    }

    /**
     * Get all live sections for a company — used in StorefrontController.
     * Eager loads category and its image for rendering.
     */
    public static function getLiveForCompany(int $companyId): Collection
    {
        return static::forCompany($companyId)
            ->isLive()
            ->ordered()
            ->with(['category', 'company'])
            ->get();
    }

    /**
     * Track a view — atomic increment, never throws.
     */
    public function trackView(): void
    {
        try {
            static::where('id', $this->id)->increment('view_count');
        } catch (\Throwable $e) {
            Log::warning('[StorefrontSection] View track failed', [
                'section_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Track a click — atomic increment, never throws.
     */
    public function trackClick(): void
    {
        try {
            static::where('id', $this->id)->increment('click_count');
        } catch (\Throwable $e) {
            Log::warning('[StorefrontSection] Click track failed', [
                'section_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
