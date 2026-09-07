<?php

namespace App\Models\Project;

use App\Enums\Project\BillingCycle;
use App\Models\Company;
use App\Traits\Tenantable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * The company's service catalog — a price list template.
 *
 * Holds no client data and is not financial truth. Everything sold is
 * snapshotted onto ProjectClientService and ProjectCharge, so repricing a
 * catalog entry can never rewrite what was already billed.
 *
 * Company-level, not store-scoped: a price list is not a transaction.
 */
class ProjectService extends Model
{
    use HasFactory, Tenantable;

    protected $table = 'project_services';

    protected $fillable = [
        'company_id',
        'name',
        'service_type',
        'billing_cycle',
        'duration_days',
        'price',
        'tax_rate',
        'description',
        'is_active',
    ];

    protected $casts = [
        'billing_cycle' => BillingCycle::class,
        'duration_days' => 'integer',
        'price'         => 'decimal:2',
        'tax_rate'      => 'decimal:2',
        'is_active'     => 'boolean',
    ];

    // ─────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Instances sold from this catalog entry. */
    public function clientServices(): HasMany
    {
        return $this->hasMany(ProjectClientService::class, 'service_id');
    }

    // ─────────────────────────────────────────────────────────
    // SCOPES & HELPERS
    // ─────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRenewable(Builder $query): Builder
    {
        return $query->where('billing_cycle', '!=', BillingCycle::OneTime->value);
    }

    public function isRenewable(): bool
    {
        return $this->billing_cycle->isRenewable();
    }
}