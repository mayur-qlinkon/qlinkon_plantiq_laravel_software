<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class HelpCategory extends Model
{
    // Platform-level model — intentionally NO Tenantable trait, NO company_id
    // Visible to ALL tenants across the platform

    protected $fillable = [
        'title',
        'slug',
        'icon',        // Lucide icon name e.g. "file-text", "shopping-cart"
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function articles(): HasMany
    {
        return $this->hasMany(HelpArticle::class);
    }

    public function publishedArticles(): HasMany
    {
        return $this->hasMany(HelpArticle::class)->where('is_published', true)->orderBy('sort_order');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    // -------------------------------------------------------------------------
    // Accessors / Helpers
    // -------------------------------------------------------------------------

    /**
     * Count only published articles for tenant-facing display.
     */
    public function getPublishedCountAttribute(): int
    {
        return $this->articles()->where('is_published', true)->count();
    }

    /**
     * Auto-generate slug from title if not set.
     */
    public static function boot(): void
    {
        parent::boot();

        static::creating(function (self $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->title);
            }
        });
    }
}