<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmPipeline extends Model
{
    use Tenantable;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function stages(): HasMany
    {
        return $this->hasMany(CrmStage::class)->orderBy('sort_order');
    }

    public function activeStages(): HasMany
    {
        return $this->hasMany(CrmStage::class)
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(CrmLead::class);
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

    // ════════════════════════════════════════════════════
    //  HELPERS
    // ════════════════════════════════════════════════════

    public function firstStage(): ?CrmStage
    {
        return $this->activeStages()->first();
    }

    // Ensure only one default pipeline per company
    protected static function booted(): void
    {
        static::saving(function (CrmPipeline $pipeline) {
            if ($pipeline->is_default) {
                static::where('company_id', $pipeline->company_id)
                    ->where('id', '!=', $pipeline->id ?? 0)
                    ->update(['is_default' => false]);
            }
        });
    }
}
