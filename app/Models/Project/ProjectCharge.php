<?php

namespace App\Models\Project;

use App\Models\Store;
use App\Models\Company;
use App\Models\Client;
use App\Models\User;

use App\Enums\Project\AllocationKind;
use App\Enums\Project\ChargeStatus;
use App\Enums\Project\ChargeType;
use App\Traits\StoreScoped;
use App\Traits\Tenantable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Exceptions\Project\ProjectBillingException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A billing obligation — the heart of the module. Every rupee owed comes from
 * a charge, whether it originated from a project, a service renewal, or a
 * standalone job.
 *
 * IMMUTABLE once created. amount, tax and period are frozen by a guard below;
 * correct a mistake by cancelling and raising a new charge so history stays
 * trustworthy.
 */
class ProjectCharge extends Model
{
    use HasFactory, LogsActivity, SoftDeletes, StoreScoped, Tenantable;

    protected $table = 'project_charges';

    /**
     * Columns that must not change once money has touched this charge.
     *
     * Before any settlement the charge is freely editable — a typo of 1,00,000
     * for 10,000 should be fixable in place, not require a cancel-and-recreate
     * dance. The moment an allocation exists, editing the amount would silently
     * invalidate it, so the charge locks.
     */
    protected const FROZEN_COLUMNS = [
        'subtotal',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'period_start',
        'period_end',
        'client_id',
    ];

    /** Escape hatch for data migrations and reconcile commands only. */
    protected static bool $frozenGuardEnabled = true;

    protected $fillable = [
        'company_id',
        'store_id',
        'client_id',
        'project_id',
        'client_service_id',
        'service_id',
        'type',
        'title',
        'description',
        'charge_date',
        'due_date',
        'period_start',
        'period_end',
        'subtotal',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'total_amount',

        'status',
        'reference',
        'notes',
    ];

    /**
     * paid_amount and written_off_amount are deliberately NOT fillable. They
     * are caches owned by recalculateSettlement() and must never be mass
     * assigned from a request.
     */
    protected $casts = [
        'type'               => ChargeType::class,
        'status'             => ChargeStatus::class,
        'charge_date'        => 'date',
        'due_date'           => 'date',
        'period_start'       => 'date',
        'period_end'         => 'date',
        'subtotal'           => 'decimal:2',
        'discount_amount'    => 'decimal:2',
        'tax_rate'           => 'decimal:2',
        'tax_amount'         => 'decimal:2',
        'total_amount'       => 'decimal:2',
        'paid_amount'        => 'decimal:2',
        'written_off_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $charge) {
            if (! static::$frozenGuardEnabled) {
                return;
            }

            $changed = array_intersect(array_keys($charge->getDirty()), self::FROZEN_COLUMNS);

            // Nothing financial is changing, or nothing has settled this charge
            // yet — either way the edit is safe.
            if ($changed === [] || ! $charge->isLocked()) {
                return;
            }

            throw new ProjectBillingException(
                'This charge already has money against it, so its amount cannot be changed. '
                .'Cancel it and raise a new one instead.'
            );
        });
    }

    /**
     * True once any money — payment, TDS or write-off — has been applied, or
     * once the charge has been cancelled.
     */
    public function isLocked(): bool
    {
        return (float) $this->getOriginal('paid_amount') > 0
            || (float) $this->getOriginal('written_off_amount') > 0
            || $this->getOriginal('status') === ChargeStatus::Cancelled->value;
    }

    /** Convenience for views: should the edit button be shown at all? */
    public function isEditable(): bool
    {
        return ! $this->isLocked();
    }

    /** Runs $callback with the immutability guard lifted. Use sparingly. */
    public static function withoutFrozenGuard(callable $callback): mixed
    {
        static::$frozenGuardEnabled = false;

        try {
            return $callback();
        } finally {
            static::$frozenGuardEnabled = true;
        }
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Charge has been {$eventName}");
    }

    // ─────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function clientService(): BelongsTo
    {
        return $this->belongsTo(ProjectClientService::class, 'client_service_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ProjectService::class, 'service_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ProjectChargeAllocation::class, 'charge_id');
    }

    /** Allocations that actually count — reversed rows are history, not money. */
    public function activeAllocations(): HasMany
    {
        return $this->allocations()->where('is_reversed', false);
    }

    // ─────────────────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────────────────

    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('status', ChargeStatus::outstandingValues());
    }

    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->outstanding()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', CarbonImmutable::today()->toDateString());
    }

    /**
     * FIFO order for automatic allocation: oldest due first, and charges with
     * no due date last so a dateless charge never jumps the queue.
     */
    public function scopeFifo(Builder $query): Builder
    {
        return $query
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->orderBy('id');
    }

    /** Charges falling in an ageing bucket, by days past due. */
    public function scopeAgedBetween(Builder $query, int $fromDays, ?int $toDays = null): Builder
    {
        $today = CarbonImmutable::today();

        $query->outstanding()->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $today->subDays($fromDays)->toDateString());

        if ($toDays !== null) {
            $query->whereDate('due_date', '>', $today->subDays($toDays)->toDateString());
        }

        return $query;
    }

    // ─────────────────────────────────────────────────────────
    // SETTLEMENT
    // ─────────────────────────────────────────────────────────

    /**
     * Amount tax is calculated on. Derived, never stored — a third stored
     * money column is a third thing that can drift out of step.
     */
    public function getTaxableAmountAttribute(): float
    {
        return round((float) $this->subtotal - (float) $this->discount_amount, 2);
    }

    /**
     * Remaining balance. Written-off amounts settle a charge just as payments
     * do, so both are subtracted.
     */
    public function getBalanceAmountAttribute(): float
    {
        return round(
            (float) $this->total_amount - (float) $this->paid_amount - (float) $this->written_off_amount,
            2
        );
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date !== null
            && ! $this->status->isSettled()
            && $this->due_date->isBefore(CarbonImmutable::today());
    }

    public function getDaysPastDueAttribute(): ?int
    {
        if (! $this->is_overdue) {
            return null;
        }

        return (int) CarbonImmutable::parse($this->due_date)->diffInDays(CarbonImmutable::today());
    }

    /**
     * Rebuilds paid_amount, written_off_amount and status from the allocation
     * rows, which are the only source of truth.
     *
     * Call this inside the same transaction as any allocation change. Never set
     * status by hand anywhere else — business rule 3.
     *
     * Cancelled charges are left alone: cancellation is a deliberate human
     * decision and must not be undone by an incoming payment.
     */
    public function recalculateSettlement(bool $persist = true): static
    {
        // Two plain aggregates rather than a grouped pluck. pluck() on a column
        // that carries an enum cast is fragile — the keys can come back as enum
        // instances depending on the Eloquent version, and a silently failed
        // lookup here would leave written_off_amount permanently at zero.
        $writtenOff = (float) $this->activeAllocations()
            ->where('kind', AllocationKind::WriteOff->value)
            ->sum('amount');

        $paid = (float) $this->activeAllocations()
            ->where('kind', '!=', AllocationKind::WriteOff->value)
            ->sum('amount');

        $this->paid_amount        = round($paid, 2);
        $this->written_off_amount = round($writtenOff, 2);

        if ($this->status !== ChargeStatus::Cancelled) {
            $this->status = $this->resolveStatus();
        }

        if ($persist) {
            $this->save();
        }

        return $this;
    }

    /**
     * Status purely as a function of money settled.
     *
     * The 0.01 tolerance absorbs rounding on split allocations — without it a
     * charge settled by three payments can sit one paisa short of Paid forever
     * and keep appearing in collection lists.
     */
    protected function resolveStatus(): ChargeStatus
    {
        $total    = (float) $this->total_amount;
        $paid     = (float) $this->paid_amount;
        $writeOff = (float) $this->written_off_amount;
        $settled  = $paid + $writeOff;

        if ($settled + 0.01 < $total) {
            return $settled > 0 ? ChargeStatus::PartiallyPaid : ChargeStatus::Pending;
        }

        // Fully settled. Written off only when nothing was actually collected,
        // so a part-paid part-forgiven charge still reads as Paid rather than
        // hiding the money that did come in.
        return $paid > 0 ? ChargeStatus::Paid : ChargeStatus::WrittenOff;
    }
}