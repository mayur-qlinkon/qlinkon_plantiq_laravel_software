<?php

namespace App\Models\Production;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Zone extends Model
{
    use SoftDeletes, Tenantable;

    protected $table = 'production_zones';

    protected $fillable = [
        'company_id',
        'production_site_id',
        'parent_id',
        'name',
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

    public function site(): BelongsTo
    {
        return $this->belongsTo(ProductionSite::class, 'production_site_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

     public function growingSpaces(): HasMany
    {
        return $this->hasMany(GrowingSpace::class, 'zone_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ZoneAssignment::class, 'zone_id');
    }
    // ════════════════════════════════════════════════════
    //  SCOPES
    // ════════════════════════════════════════════════════

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeRoot(Builder $q): Builder
    {
        return $q->whereNull('parent_id');
    }

    public function scopeForSite(Builder $q, int $siteId): Builder
    {
        return $q->where('production_site_id', $siteId);
    }

    // ════════════════════════════════════════════════════
    //  HELPERS
    // ════════════════════════════════════════════════════

    public function isLeaf(): bool
    {
        return $this->children()->doesntExist();
    }
}