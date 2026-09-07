<?php

namespace App\Models\Hrm;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveType extends Model
{
    use SoftDeletes, Tenantable;

    protected $fillable = [
        'company_id', 'name', 'code', 'description',
        'default_days_per_year', 'is_paid', 'is_carry_forward', 'max_carry_forward_days',
        'is_encashable', 'requires_document', 'min_days_before_apply', 'max_consecutive_days',
        'applicable_gender', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'default_days_per_year' => 'decimal:1',
        'max_carry_forward_days' => 'decimal:1',
        'max_consecutive_days' => 'decimal:1',
        'min_days_before_apply' => 'integer',
        'is_paid' => 'boolean',
        'is_carry_forward' => 'boolean',
        'is_encashable' => 'boolean',
        'requires_document' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ── Relationships ──

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
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

    public function scopeForGender(Builder $query, ?string $gender): Builder
    {
        return $query->where(function ($q) use ($gender) {
            $q->where('applicable_gender', 'all');
            if ($gender) {
                $q->orWhere('applicable_gender', $gender);
            }
        });
    }
}
