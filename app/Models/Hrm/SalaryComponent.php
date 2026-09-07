<?php

namespace App\Models\Hrm;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryComponent extends Model
{
    use SoftDeletes, Tenantable;

    const TYPE_EARNING = 'earning';

    const TYPE_DEDUCTION = 'deduction';

    const CALC_FIXED = 'fixed';

    const CALC_PERCENTAGE = 'percentage';

    // ── Roles ──
    // The engine resolves components by role, never by name or code.

    const ROLE_BASIC = 'basic';

    const ROLE_DA = 'da';

    const ROLE_HRA = 'hra';

    const ROLE_SPECIAL_ALLOWANCE = 'special_allowance';

    const ROLE_OTHER = 'other';

    /**
     * Roles the engine gives special meaning to, so a company may define at
     * most one active component for each. ROLE_OTHER is deliberately absent —
     * a company may have any number of ordinary allowances and deductions.
     */
    const SINGLETON_ROLES = [
        self::ROLE_BASIC,
        self::ROLE_DA,
        self::ROLE_HRA,
        self::ROLE_SPECIAL_ALLOWANCE,
    ];

    const ROLES = [
        self::ROLE_BASIC,
        self::ROLE_DA,
        self::ROLE_HRA,
        self::ROLE_SPECIAL_ALLOWANCE,
        self::ROLE_OTHER,
    ];

    const ROLE_LABELS = [
        self::ROLE_BASIC => 'Basic Salary',
        self::ROLE_DA => 'Dearness Allowance',
        self::ROLE_HRA => 'House Rent Allowance',
        self::ROLE_SPECIAL_ALLOWANCE => 'Special Allowance (balancing)',
        self::ROLE_OTHER => 'Other',
    ];

    protected $fillable = [
        'company_id', 'name', 'code', 'type', 'role', 'description',
        'calculation_type', 'percentage_of_component_id', 'default_amount',
        'is_taxable', 'is_statutory', 'appears_on_payslip',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',
        'is_taxable' => 'boolean',
        'is_statutory' => 'boolean',
        'appears_on_payslip' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ── Boot ──

    protected static function booted(): void
    {
        $guard = function (SalaryComponent $component) {
            // The column is a plain string so Phase 3 can add statutory roles
            // without an enum migration — which means the allowed set has to
            // be enforced here instead of by the schema.
            if (! in_array($component->role, self::ROLES, true)) {
                throw new \InvalidArgumentException(
                    'Invalid salary component role: '.$component->role
                );
            }

            if (! in_array($component->role, self::SINGLETON_ROLES, true)) {
                return;
            }

            // Only active components compete for a role. An inactive or
            // soft-deleted component keeps its role so history stays readable,
            // but it no longer blocks a replacement from being created.
            if (! $component->is_active) {
                return;
            }

            // The tenant scope resolves to the *current* company, but a
            // component may be created for another one (tenant bootstrap
            // seeding), so the company condition is stated explicitly.
            // A unique index backs this check — the hook exists to turn a
            // raw duplicate-key error into a message a user can act on.
            $exists = static::withoutGlobalScope('tenant')
                ->where('company_id', $component->company_id)
                ->where('role', $component->role)
                ->where('is_active', true)
                ->when($component->exists, fn ($q) => $q->whereKeyNot($component->getKey()))
                ->exists();

            if ($exists) {
                throw new \InvalidArgumentException(
                    'An active component with the role "'.(self::ROLE_LABELS[$component->role] ?? $component->role).'" already exists. Deactivate it before creating another.'
                );
            }
        };

        static::creating($guard);
        static::updating($guard);
    }

    // ── Relationships ──

    public function employeeStructures(): HasMany
    {
        return $this->hasMany(EmployeeSalaryStructure::class);
    }

    /**
     * The component this one is a percentage of, when calculation_type is
     * percentage. Null means the engine resolves the base by role instead.
     */
    public function percentageOfComponent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'percentage_of_component_id');
    }

    // ── Scopes ──

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeEarnings(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_EARNING);
    }

    public function scopeDeductions(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_DEDUCTION);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    // ── Accessors ──

    public function getRoleLabelAttribute(): string
    {
        return self::ROLE_LABELS[$this->role] ?? 'Other';
    }
}
