<?php

namespace App\Models\Project;

use App\Models\Store;
use App\Models\Company;
use App\Models\Client;
use App\Enums\Project\BillingCycle;
use App\Enums\Project\ClientServiceStatus;
use App\Traits\StoreScoped;
use App\Traits\Tenantable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A service instance sold to one client — the ENTITLEMENT.
 *
 * Answers "is this client's hosting valid right now?", never "has it been
 * paid for?". Money lives entirely on ProjectCharge.
 *
 * price here means what the NEXT renewal will cost. Amounts already billed are
 * frozen on their charges and are never affected by editing this row — the
 * legacy renewService() overwrote the price in place, which destroyed the
 * history of every prior period.
 */
class ProjectClientService extends Model
{
    use HasFactory, LogsActivity, SoftDeletes, StoreScoped, Tenantable;

    protected $table = 'project_client_services';

    protected $fillable = [
        'company_id',
        'store_id',
        'client_id',
        'project_id',
        'service_id',
        'name',
        'billing_cycle',
        'duration_days',
        'price',
        'tax_rate',
        'status',
        'started_at',
        'current_period_start',
        'current_period_end',
        'auto_renew',
        'cancelled_at',
        'cancel_reason',
        'notes',
    ];

    protected $casts = [
        'billing_cycle'        => BillingCycle::class,
        'status'               => ClientServiceStatus::class,
        'duration_days'        => 'integer',
        'price'                => 'decimal:2',
        'tax_rate'             => 'decimal:2',
        'auto_renew'           => 'boolean',
        'started_at'           => 'date',
        'current_period_start' => 'date',
        'current_period_end'   => 'date',
        'cancelled_at'         => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Client service has been {$eventName}");
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

    /** Catalog origin. Null for ad-hoc services or if the catalog row was removed. */
    public function service(): BelongsTo
    {
        return $this->belongsTo(ProjectService::class, 'service_id');
    }

    /**
     * Every billing period ever raised for this service. This IS the renewal
     * history — there is no separate renewals table.
     */
    public function charges(): HasMany
    {
        return $this->hasMany(ProjectCharge::class, 'client_service_id');
    }

    // ─────────────────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ClientServiceStatus::Active->value);
    }

    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    /** Active services whose period ends within the next $days days. */
    public function scopeExpiringWithin(Builder $query, int $days = 30): Builder
    {
        return $query
            ->where('status', ClientServiceStatus::Active->value)
            ->whereNotNull('current_period_end')
            ->whereBetween('current_period_end', [
                CarbonImmutable::today()->toDateString(),
                CarbonImmutable::today()->addDays($days)->toDateString(),
            ]);
    }

    /** Period has already ended but the service was never renewed. */
    public function scopeOverdueRenewal(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [
                ClientServiceStatus::Active->value,
                ClientServiceStatus::Expired->value,
            ])
            ->whereNotNull('current_period_end')
            ->whereDate('current_period_end', '<', CarbonImmutable::today()->toDateString());
    }

    /**
     * Buckets the renewal board works in. Kept on the model rather than in the
     * controller so the same definition backs both the list and the counts —
     * if these two ever drift, the tab numbers stop matching the rows.
     *
     * 'week' and 'month' include today on purpose: something expiring today is
     * inside this week, and hiding it from that tab is how renewals get missed.
     */
    public function scopeRenewalBucket(Builder $query, string $bucket): Builder
    {
        $today = CarbonImmutable::today();

        $live = [ClientServiceStatus::Active->value, ClientServiceStatus::Expired->value];

        return match ($bucket) {
            'overdue' => $query->whereIn('status', $live)
                ->whereNotNull('current_period_end')
                ->whereDate('current_period_end', '<', $today->toDateString()),

            'today' => $query->whereIn('status', $live)
                ->whereDate('current_period_end', $today->toDateString()),

            'week' => $query->whereIn('status', $live)
                ->whereBetween('current_period_end', [
                    $today->toDateString(),
                    $today->addDays(7)->toDateString(),
                ]),

            'month' => $query->whereIn('status', $live)
                ->whereBetween('current_period_end', [
                    $today->toDateString(),
                    $today->addDays(30)->toDateString(),
                ]),

            'active' => $query->where('status', ClientServiceStatus::Active->value)
                ->whereDate('current_period_end', '>=', $today->toDateString()),

            'expired'   => $query->where('status', ClientServiceStatus::Expired->value),
            'cancelled' => $query->where('status', ClientServiceStatus::Cancelled->value),

            default => $query,
        };
    }

    public function scopeAutoRenewable(Builder $query): Builder
    {
        return $query
            ->where('auto_renew', true)
            ->where('status', ClientServiceStatus::Active->value)
            ->where('billing_cycle', '!=', BillingCycle::OneTime->value);
    }

    // ─────────────────────────────────────────────────────────
    // DERIVED VALUES
    // ─────────────────────────────────────────────────────────

    /**
     * Both conditions matter: a one_time service never renews regardless of
     * status, and a cancelled service never renews regardless of cycle.
     */
    public function isRenewable(): bool
    {
        return $this->billing_cycle->isRenewable() && $this->status->isRenewable();
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->current_period_end !== null
            && $this->current_period_end->isBefore(CarbonImmutable::today());
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if ($this->current_period_end === null) {
            return null;
        }

        return CarbonImmutable::today()->diffInDays($this->current_period_end, false);
    }

    /**
     * Start date of the next period — the day after the current one ends.
     *
     * Note this always continues from current_period_end, never from today. If
     * a client skips a year and returns later, the service layer decides which
     * period to bill; missed periods must never be auto-generated, or the
     * system invoices a client for something they never received.
     */
    public function nextPeriodStart(): ?CarbonImmutable
    {
        return $this->current_period_end
            ? CarbonImmutable::parse($this->current_period_end)->addDay()
            : null;
    }
}