<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmStage extends Model
{
    use Tenantable;

    protected $fillable = [
        'company_id',
        'crm_pipeline_id',
        'name',
        'color',
        'is_won',
        'is_lost',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_won' => 'boolean',
        'is_lost' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(CrmPipeline::class, 'crm_pipeline_id');
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
        return $q->orderBy('sort_order');
    }

    public function scopeForPipeline(Builder $q, int $pipelineId): Builder
    {
        return $q->where('crm_pipeline_id', $pipelineId);
    }

    // ════════════════════════════════════════════════════
    //  ACCESSORS
    // ════════════════════════════════════════════════════

    public function getTypeAttribute(): string
    {
        if ($this->is_won) {
            return 'won';
        }
        if ($this->is_lost) {
            return 'lost';
        }

        return 'active';
    }

    public function getLeadsCountAttribute(): int
    {
        return $this->leads()->count();
    }
}
