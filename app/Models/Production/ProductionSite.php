<?php

namespace App\Models\Production;

use App\Models\Company;
use App\Models\Store;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionSite extends Model
{
    use SoftDeletes, Tenantable;

    protected $fillable = [
        'company_id',
        'store_id',
        'name',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Non-structural — reporting only. Never used for scoping or capacity.
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class);
    }

    // Top-level zones only (parent_id null) — entry point into the tree.
    public function rootZones(): HasMany
    {
        return $this->hasMany(Zone::class)->whereNull('parent_id')->orderBy('sort_order');
    }

    // Growing Spaces attached directly to the Site (tenant skipped zoning).
    public function directGrowingSpaces(): HasMany
    {
        return $this->hasMany(GrowingSpace::class)->whereNull('zone_id')->orderBy('sort_order');
    }

    // All Growing Spaces under this Site, at any zone depth.
    public function growingSpaces(): HasMany
    {
        return $this->hasMany(GrowingSpace::class);
    }

    // ════════════════════════════════════════════════════
    //  SCOPES
    // ════════════════════════════════════════════════════

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('sort_order')->orderBy('name');
    }
}