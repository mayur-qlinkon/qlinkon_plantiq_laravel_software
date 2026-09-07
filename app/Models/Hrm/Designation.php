<?php

namespace App\Models\Hrm;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Designation extends Model
{
    use  SoftDeletes, Tenantable;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'level',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'level' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ]; 

    // ── Relationships ──

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    // ── Scopes ──

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
