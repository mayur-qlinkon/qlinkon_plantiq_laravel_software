<?php

namespace App\Models\Production;

use App\Models\User;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionPlan extends Model
{
    use SoftDeletes, Tenantable;

    // ── Status constants ──

    const STATUS_DRAFT = 'draft';

    const STATUS_CONFIRMED = 'confirmed';

    const STATUS_CLOSED = 'closed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_CLOSED => 'Closed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    const STATUS_COLORS = [
        self::STATUS_DRAFT => ['bg' => '#f3f4f6', 'text' => '#374151', 'dot' => '#9ca3af'],
        self::STATUS_CONFIRMED => ['bg' => '#ecfdf5', 'text' => '#065f46', 'dot' => '#10b981'],
        self::STATUS_CLOSED => ['bg' => '#eff6ff', 'text' => '#1e40af', 'dot' => '#3b82f6'],
        self::STATUS_CANCELLED => ['bg' => '#fef2f2', 'text' => '#991b1b', 'dot' => '#ef4444'],
    ];

    // Valid transitions — Planning is intentionally simple:
    // Draft can be confirmed or cancelled.
    // Confirmed can be marked Closed (owner does it manually once fulfilled)
    // or Cancelled (intent was recorded, cannot un-confirm).
    // Closed and Cancelled are terminal.
    const STATUS_TRANSITIONS = [
        self::STATUS_DRAFT => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
        self::STATUS_CONFIRMED => [self::STATUS_CLOSED, self::STATUS_CANCELLED],
        self::STATUS_CLOSED => [],
        self::STATUS_CANCELLED => [],
    ];

    // ── Purpose constants — soft label only, no workflow effect ──

    const PURPOSE_SEASONAL = 'seasonal';

    const PURPOSE_CUSTOMER_ORDER = 'customer_order';

    const PURPOSE_STOCK_REPLENISHMENT = 'stock_replenishment';

    const PURPOSE_TRIAL = 'trial';

    const PURPOSE_FORECAST = 'forecast';

    const PURPOSE_OTHER = 'other';

    const PURPOSE_LABELS = [
        self::PURPOSE_SEASONAL => 'Seasonal',
        self::PURPOSE_CUSTOMER_ORDER => 'Customer Order',
        self::PURPOSE_STOCK_REPLENISHMENT => 'Stock Replenishment',
        self::PURPOSE_TRIAL => 'Trial',
        self::PURPOSE_FORECAST => 'Forecast',
        self::PURPOSE_OTHER => 'Other',
    ];

    // ── Fillable ──

    protected $fillable = [
        'company_id',
        'created_by',
        'title',
        'purpose',
        'notes',
        'status',
        'confirmed_at',
        'confirmed_by',
        'closed_at',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionPlanItem::class)->orderBy('sort_order');
    }

    // ════════════════════════════════════════════════════
    //  SCOPES
    // ════════════════════════════════════════════════════

    public function scopeDraft(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_DRAFT);
    }

    public function scopeConfirmed(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeClosed(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_CLOSED);
    }

    public function scopeByStatus(Builder $q, string $status): Builder
    {
        return $q->where('status', $status);
    }

    // ════════════════════════════════════════════════════
    //  HELPERS
    // ════════════════════════════════════════════════════

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::STATUS_TRANSITIONS[$this->status] ?? []);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    // Items can be added/edited/removed only while Draft.
    public function isEditable(): bool
    {
        return $this->isDraft();
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): array
    {
        return self::STATUS_COLORS[$this->status] ?? ['bg' => '#f3f4f6', 'text' => '#374151', 'dot' => '#9ca3af'];
    }

    public function getPurposeLabelAttribute(): string
    {
        return self::PURPOSE_LABELS[$this->purpose] ?? '—';
    }
}