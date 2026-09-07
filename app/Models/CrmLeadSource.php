<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmLeadSource extends Model
{
    use Tenantable;

    protected $fillable = [
        'company_id',
        'name',
        'icon',
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
}
