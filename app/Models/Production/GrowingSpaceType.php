<?php

namespace App\Models\Production;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrowingSpaceType extends Model
{
    use Tenantable;

    protected $table = 'production_growing_space_types';

    // ── Constants ──

    const CAPACITY_UNIT_POTS = 'pots';

    const CAPACITY_UNIT_AREA_SQM = 'area_sqm';

    const CAPACITY_UNIT_LINEAR_M = 'linear_m';

    const CAPACITY_UNIT_TRAY_CELLS = 'tray_cells';

    const CAPACITY_UNIT_CUSTOM = 'custom';

    const CAPACITY_UNIT_LABELS = [
        self::CAPACITY_UNIT_POTS => 'Pots (count)',
        self::CAPACITY_UNIT_AREA_SQM => 'Area (sq. m)',
        self::CAPACITY_UNIT_LINEAR_M => 'Length (linear m)',
        self::CAPACITY_UNIT_TRAY_CELLS => 'Tray Cells (count)',
        self::CAPACITY_UNIT_CUSTOM => 'Custom',
    ];

    // Units where capacity and plant-quantity are both counts, so they're
    // directly comparable (X of Y pots full). Area/length units measure
    // physical space, not plant count — no valid quantity-vs-capacity
    // comparison exists for them.
    const COUNT_BASED_UNITS = [
        self::CAPACITY_UNIT_POTS,
        self::CAPACITY_UNIT_TRAY_CELLS,
    ];

    protected $fillable = [
        'company_id',
        'name',
        'capacity_unit',
        'custom_unit_label',
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

    // ════════════════════════════════════════════════════
    //  ACCESSORS
    // ════════════════════════════════════════════════════

    public function getCapacityUnitLabelAttribute(): string
    {
        if ($this->capacity_unit === self::CAPACITY_UNIT_CUSTOM && $this->custom_unit_label) {
            return $this->custom_unit_label;
        }

        return self::CAPACITY_UNIT_LABELS[$this->capacity_unit] ?? $this->capacity_unit;
    }
}