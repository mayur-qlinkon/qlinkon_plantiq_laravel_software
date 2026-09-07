<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Banner extends Model
{
    use HasFactory, SoftDeletes, Tenantable;

    protected $fillable = [
        'company_id',
        'type',
        'position',
        'admin_label',
        'title',
        'subtitle',
        'image',
        'mobile_image',
        'alt_text',
        'link',
        'button_text',
        'target',
        'category_id',
        'product_id',
        'is_active',
        'sort_order',
        'starts_at',
        'ends_at',
        'meta',
        'click_count',
        'view_count',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'click_count' => 'integer',
        'view_count' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'meta' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes (For Easy Querying)
    |--------------------------------------------------------------------------
    */

    /**
     * Scope for active and currently scheduled banners
     */
    public function scopeIsLive($query)
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('sort_order', 'asc');
    }

    public function scopeByPosition($query, $position)
    {
        return $query->where('position', $position);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getDisplayAdminLabelAttribute(): string
    {
        return $this->admin_label
            ?: ($this->title ?: 'Untitled Banner');
    }

    public function getImageUrlAttribute(): string
    {
        return asset('storage/'.$this->image);
    }

    public function getMobileImageUrlAttribute(): ?string
    {
        return $this->mobile_image ? asset('storage/'.$this->mobile_image) : $this->image_url;
    }

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
}
