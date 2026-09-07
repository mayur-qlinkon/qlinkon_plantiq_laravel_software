<?php

namespace App\Models\Project;

use App\Models\Payment;
use App\Models\Company;
use App\Models\User;
use App\Enums\Project\AllocationKind;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Links money to charges. This is what makes "one payment across many charges"
 * and "many payments against one charge" simultaneously true — the capability
 * the legacy module lacked, and the reason a partly paid renewal could not be
 * represented at all.
 *
 * Never hard deleted. Reversal is a flag so the audit trail survives; there is
 * no SoftDeletes here because is_reversed already carries that meaning and two
 * mechanisms for "this row no longer counts" would eventually disagree.
 */
class ProjectChargeAllocation extends Model
{
    use HasFactory, LogsActivity, Tenantable;

    protected $table = 'project_charge_allocations';

    protected $fillable = [
        'company_id',
        'charge_id',
        'payment_id',
        'kind',
        'amount',
        'source',
        'reason',
        'allocated_by',
        'allocated_at',
        'notes',
    ];

    protected $casts = [
        'kind'         => AllocationKind::class,
        'amount'       => 'decimal:2',
        'allocated_at' => 'datetime',
        'is_reversed'  => 'boolean',
        'reversed_at'  => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Charge allocation has been {$eventName}");
    }

    // ─────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(ProjectCharge::class, 'charge_id');
    }

    /** Null for TDS and write-offs, which settle a charge without money arriving. */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    // ─────────────────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_reversed', false);
    }

    public function scopeOfKind(Builder $query, AllocationKind $kind): Builder
    {
        return $query->where('kind', $kind->value);
    }

    public function scopeForPayment(Builder $query, int $paymentId): Builder
    {
        return $query->where('payment_id', $paymentId);
    }
}