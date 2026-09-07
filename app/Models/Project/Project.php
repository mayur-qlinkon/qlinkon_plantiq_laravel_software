<?php

namespace App\Models\Project;

use App\Models\Store;
use App\Models\Company;
use App\Models\Client;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Enums\Project\ChargeStatus;
use App\Enums\Project\ProjectStatus;
use App\Traits\StoreScoped;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A unit of WORK.
 *
 * Carries no money columns on purpose. A project's value is the sum of its
 * charges, and its status reflects delivery only. The legacy module let
 * recordPayment() flip a project to "done" the moment the balance hit zero,
 * which meant a client paying upfront marked the work complete before it began.
 */
class Project extends Model
{
    use HasFactory, LogsActivity, SoftDeletes, StoreScoped, Tenantable;

    protected $fillable = [
        'company_id',
        'store_id',
        'client_id',
        'title',
        'description',
        'status',
        'start_date',
        'expected_end_date',
        'completed_at',
        'owner_id',
        'quotation_id',
        'invoice_id',
        'notes',
    ];

    protected $casts = [
        'status'            => ProjectStatus::class,
        'start_date'        => 'date',
        'expected_end_date' => 'date',
        'completed_at'      => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Project has been {$eventName}");
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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function clientServices(): HasMany
    {
        return $this->hasMany(ProjectClientService::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(ProjectCharge::class);
    }

    /**
     * Optional cross-module links. Resolved here rather than via a foreign key
     * so the Projects module stays installable without Invoicing/Quotations.
     * Returns null when the referenced record no longer exists.
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    // ─────────────────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────────────────

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ProjectStatus::openValues());
    }

    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Attaches charge totals as a single aggregated query instead of loading
     * every charge row. Use this on list screens.
     *
     *   Project::withChargeTotals()->get()
     *   -> $project->charges_total, $project->charges_paid
     */
    public function scopeWithChargeTotals(Builder $query): Builder
    {
        $countable = fn (Builder $q) => $q->whereIn('status', ChargeStatus::countableValues());

        return $query
            ->withSum(['charges as charges_total' => $countable], 'total_amount')
            ->withSum(['charges as charges_paid' => $countable], 'paid_amount')
            ->withSum(['charges as charges_written_off' => $countable], 'written_off_amount');
    }

    /**
     * Delivered but not fully collected — the single most actionable list in
     * the module, and one the legacy schema could not produce at all.
     */
    public function scopeCompletedButUnpaid(Builder $query): Builder
    {
        return $query
            ->where('status', ProjectStatus::Completed->value)
            ->whereHas('charges', fn ($q) => $q->whereIn('status', ChargeStatus::outstandingValues()));
    }

    // ─────────────────────────────────────────────────────────
    // DERIVED VALUES
    // ─────────────────────────────────────────────────────────

    /**
     * Total billed on this project.
     *
     * Prefers the aggregate loaded by withChargeTotals(); falls back to a live
     * query. On a list screen without that scope this becomes an N+1, so always
     * pair list queries with withChargeTotals().
     */
    public function getTotalChargedAttribute(): float
    {
        return $this->aggregate('charges_total', 'total_amount');
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->aggregate('charges_paid', 'paid_amount');
    }

    public function getTotalWrittenOffAttribute(): float
    {
        return $this->aggregate('charges_written_off', 'written_off_amount');
    }

    /**
     * Reads a withChargeTotals() aggregate, falling back to a live query only
     * when the scope was not applied.
     *
     * array_key_exists rather than ??: withSum returns NULL for a project with
     * no charges, and ?? would treat that as "not loaded" and fire a query for
     * every such row on a list page.
     */
    private function aggregate(string $alias, string $column): float
    {
        if (array_key_exists($alias, $this->attributes)) {
            return (float) $this->attributes[$alias];
        }

        return (float) $this->charges()
            ->whereIn('status', ChargeStatus::countableValues())
            ->sum($column);
    }

    public function getOutstandingAmountAttribute(): float
    {
        return round($this->total_charged - $this->total_paid - $this->total_written_off, 2);
    }
}