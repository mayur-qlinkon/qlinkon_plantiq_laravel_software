<?php

namespace App\Models\Production;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GrowingSpace extends Model
{
    use SoftDeletes, Tenantable;

    protected $table = 'production_growing_spaces';

    protected $fillable = [
        'company_id',
        'production_site_id',
        'zone_id',
        'growing_space_type_id',
        'name',
        'capacity',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'capacity' => 'decimal:2',
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

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(GrowingSpaceType::class, 'growing_space_type_id');
    }

    // ════════════════════════════════════════════════════
    //  SCOPES
    // ════════════════════════════════════════════════════

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeForSite(Builder $q, int $siteId): Builder
    {
        return $q->where('production_site_id', $siteId);
    }

    public function scopeForZone(Builder $q, int $zoneId): Builder
    {
        return $q->where('zone_id', $zoneId);
    }

    // ════════════════════════════════════════════════════
    //  HELPERS
    // ════════════════════════════════════════════════════

    // Occupied/available capacity intentionally does NOT live here.
    // Layout Engine has zero knowledge of batches — that live snapshot
    // belongs to the Occupancy Ledger in the Production Engine (Phase 2),
    // which reads this model one-directionally.
}