<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallanStatusHistory extends Model
{
    use HasFactory;

    // ════════════════════════════════════════════════════
    //  TABLE & FILLABLE
    // ════════════════════════════════════════════════════

    protected $table = 'challan_status_history';

    protected $fillable = [
        'challan_id',
        'from_status',
        'to_status',
        'notes',
        'changed_by_type',
        'changed_by',
    ];

    // ════════════════════════════════════════════════════
    //  NO Tenantable — No SoftDeletes — intentional
    //
    //  Tenantable: no company_id column here.
    //  Tenant isolation guaranteed by challan_id FK.
    //
    //  SoftDeletes: history must NEVER be soft-deleted.
    //  Audit trails are immutable by design.
    //  If you need to hide a record, add a separate
    //  `is_hidden` flag — never delete history.
    // ════════════════════════════════════════════════════

    // ════════════════════════════════════════════════════
    //  CONSTANTS
    // ════════════════════════════════════════════════════

    // Who triggered the change — matches changed_by_type column
    const ACTOR_ADMIN = 'admin';

    const ACTOR_SYSTEM = 'system';   // auto-transitions (recalculateStatus)

    const ACTOR_DRIVER = 'driver';   // future: driver app

    const ACTOR_CLIENT = 'client';   // future: client portal

    const ACTOR_LABELS = [
        self::ACTOR_ADMIN => 'Admin',
        self::ACTOR_SYSTEM => 'System',
        self::ACTOR_DRIVER => 'Driver',
        self::ACTOR_CLIENT => 'Client',
    ];

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function challan(): BelongsTo
    {
        return $this->belongsTo(Challan::class);
    }

    /**
     * The user who made this change.
     * Null when changed_by_type is 'system' or 'driver' (no user record).
     * withTrashed() — user may be deleted but history must remain readable.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by')->withTrashed();
    }

    // ════════════════════════════════════════════════════
    //  ACCESSORS
    // ════════════════════════════════════════════════════

    /**
     * Human-readable label for who made the change.
     * Falls back to the user's name if a user record exists,
     * otherwise uses the actor type label.
     */
    public function getActorLabelAttribute(): string
    {
        if ($this->changedBy) {
            return $this->changedBy->name ?? self::ACTOR_LABELS[$this->changed_by_type] ?? $this->changed_by_type;
        }

        return self::ACTOR_LABELS[$this->changed_by_type] ?? ucfirst($this->changed_by_type);
    }

    /**
     * Was this an initial creation entry? (no from_status)
     */
    public function getIsInitialAttribute(): bool
    {
        return is_null($this->from_status);
    }

    /**
     * Human-readable transition string — e.g. "Draft → Dispatched"
     */
    public function getTransitionLabelAttribute(): string
    {
        $from = $this->from_status
            ? (Challan::STATUS_LABELS[$this->from_status] ?? ucfirst($this->from_status))
            : 'Created';

        $to = Challan::STATUS_LABELS[$this->to_status] ?? ucfirst($this->to_status);

        return "{$from} → {$to}";
    }

    /**
     * Badge color for the to_status — delegates to Challan constants
     */
    public function getStatusColorAttribute(): string
    {
        return Challan::STATUS_COLORS[$this->to_status] ?? 'gray';
    }

    /**
     * Formatted timestamp — "15 Jan 2025, 10:32 AM"
     */
    public function getFormattedAtAttribute(): string
    {
        return $this->created_at?->format('d M Y, h:i A') ?? '—';
    }

    // ════════════════════════════════════════════════════
    //  SCOPES
    // ════════════════════════════════════════════════════

    public function scopeByActor(Builder $query, string $actorType): Builder
    {
        return $query->where('changed_by_type', $actorType);
    }

    public function scopeSystemGenerated(Builder $query): Builder
    {
        return $query->where('changed_by_type', self::ACTOR_SYSTEM);
    }

    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('changed_by', $userId);
    }

    public function scopeForStatus(Builder $query, string $status): Builder
    {
        return $query->where('to_status', $status);
    }

    // ════════════════════════════════════════════════════
    //  BOOT — enforce immutability
    // ════════════════════════════════════════════════════

    protected static function booted(): void
    {
        // History records are write-once — never allow updates
        static::updating(function (ChallanStatusHistory $history) {
            throw new \LogicException(
                "ChallanStatusHistory [{$history->id}] is immutable and cannot be updated. "
                .'Audit trail records must never be modified.'
            );
        });

        // Hard-delete guard — history must never be deleted
        static::deleting(function (ChallanStatusHistory $history) {
            throw new \LogicException(
                "ChallanStatusHistory [{$history->id}] cannot be deleted. "
                .'Audit trail records are permanent.'
            );
        });
    }
}
