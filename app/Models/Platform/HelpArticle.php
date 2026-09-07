<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class HelpArticle extends Model
{
    // Platform-level model — intentionally NO Tenantable trait, NO company_id
    // Visible to ALL tenants across the platform

    protected $fillable = [
        'help_category_id',
        'title',
        'slug',
        'content',      // Markdown text — rendered on view side
        'video_url',    // Full YouTube URL — optional
        'sort_order',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order'   => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function category(): BelongsTo
    {
        return $this->belongsTo(HelpCategory::class, 'help_category_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    // -------------------------------------------------------------------------
    // Accessors / Helpers
    // -------------------------------------------------------------------------

    /**
     * Extract YouTube video ID from any standard YouTube URL format.
     * Supports: youtube.com/watch?v=ID, youtu.be/ID, youtube.com/embed/ID
     * Returns null if no valid video URL is set.
     */
    public function getYoutubeIdAttribute(): ?string
    {
        if (empty($this->video_url)) {
            return null;
        }

        preg_match(
            '/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([A-Za-z0-9_\-]{11})/',
            $this->video_url,
            $matches
        );

        return $matches[1] ?? null;
    }

    /**
     * Returns the YouTube embed URL ready for <iframe src="">.
     */
    public function getYoutubeEmbedUrlAttribute(): ?string
    {
        $id = $this->youtube_id;
        return $id ? "https://www.youtube.com/embed/{$id}?rel=0" : null;
    }

    /**
     * Returns true if this article has a valid embeddable video.
     */
    public function hasVideo(): bool
    {
        return $this->youtube_id !== null;
    }

    /**
     * Reading time estimate (words / 200 wpm, minimum 1 min).
     */
    public function getReadingTimeAttribute(): int
    {
        $words = str_word_count(strip_tags($this->content ?? ''));
        return max(1, (int) ceil($words / 200));
    }

    /**
     * Auto-generate slug from title if not set.
     * Also ensures uniqueness by appending a suffix if needed.
     */
    public static function boot(): void
    {
        parent::boot();

        static::creating(function (self $article) {
            if (empty($article->slug)) {
                $base = Str::slug($article->title);
                $slug = $base;
                $i    = 1;

                while (static::where('slug', $slug)->exists()) {
                    $slug = "{$base}-{$i}";
                    $i++;
                }

                $article->slug = $slug;
            }
        });
    }
}