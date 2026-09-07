<?php

namespace App\Models;

use App\Enums\PageTemplate;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use HasFactory, SoftDeletes, Tenantable;

    // ── Application-Level Enums ──
    // Keeps the DB flexible while keeping the code strict
    public const TYPE_LEGAL = 'legal';

    public const TYPE_ABOUT = 'about';

    public const TYPE_CUSTOM = 'custom';

    public const TYPES = [
        self::TYPE_LEGAL => 'Legal & Compliance',
        self::TYPE_ABOUT => 'Company Information',
        self::TYPE_CUSTOM => 'Custom Page',
    ];

    protected $fillable = [
        'company_id',
        'title',
        'slug',
        'content',
        'template',
        'data',
        'type',
        'seo_title',
        'seo_description',
        'is_published',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'template' => PageTemplate::class,
        'data' => 'array',
    ];

    /**
     * True for pages still holding raw HTML from before templates existed.
     * Those render from content; everything else renders from data.
     */
    public function isLegacy(): bool
    {
        return $this->template === PageTemplate::Custom;
    }

    /**
     * One field's value, or null.
     *
     * Reads go through here rather than $page->data['x'] so a template that
     * gains a field does not blow up on rows saved before it existed.
     */
    public function field(string $key): ?string
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Pre-template HTML, cleaned at render time.
     *
     * These rows were written when the content column accepted anything and
     * was printed unescaped, so the stored markup has never been checked.
     * Cleaning on output closes that without waiting for every tenant to move
     * their pages across. A wider whitelist than page_content because the old
     * editor genuinely produced headings, images and tables.
     */
    public function legacyHtml(): string
    {
        return $this->content
            ? \Mews\Purifier\Facades\Purifier::clean($this->content, 'page_legacy')
            : '';
    }

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ════════════════════════════════════════════════════
    //  QUERY SCOPES (For cleaner controllers)
    // ════════════════════════════════════════════════════

    /**
     * Only fetch pages that are live on the storefront.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Filter by specific page type (e.g., fetching only legal pages for the footer).
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    // ════════════════════════════════════════════════════
    //  ACCESSORS / MUTATORS
    // ════════════════════════════════════════════════════

    /**
     * Fallback for SEO Title if it wasn't provided.
     */
    public function getMetaTitleAttribute(): string
    {
        return $this->seo_title ?: $this->title;
    }

    /**
     * Get the human-readable type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? 'Unknown Type';
    }
}
