<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ChallanReturn extends Model
{
    use HasFactory, Tenantable;

    // ════════════════════════════════════════════════════
    //  TABLE & FILLABLE
    // ════════════════════════════════════════════════════

    protected $table = 'challan_returns';

    protected $fillable = [
        'challan_id',
        'company_id',
        'return_number',
        'return_date',
        'received_by',
        'vehicle_number',
        'notes',
        'condition',
        'created_by',
    ];

    /**
     * @property Carbon $return_date
     */
    protected $casts = [
        'return_date' => 'datetime',
    ];

    // ════════════════════════════════════════════════════
    //  NOTE ON Tenantable
    //  This table HAS company_id — Tenantable is correct here.
    //  Unlike ChallanItem / ChallanStatusHistory which rely on
    //  the parent FK chain, ChallanReturn is a top-level
    //  document that can be queried independently (e.g. returns
    //  dashboard), so it needs its own company_id guard.
    // ════════════════════════════════════════════════════

    // ════════════════════════════════════════════════════
    //  NO SoftDeletes — intentional
    //  A return entry is a financial/inventory record.
    //  Once goods are received back, that event happened.
    //  Deleting it would corrupt qty_returned on challan items.
    //  To "undo" a return: reverse it via a new entry, never delete.
    // ════════════════════════════════════════════════════

    // ════════════════════════════════════════════════════
    //  CONSTANTS
    // ════════════════════════════════════════════════════

    const CONDITION_GOOD = 'good';

    const CONDITION_DAMAGED = 'damaged';

    const CONDITION_PARTIAL = 'partial';

    const CONDITION_LABELS = [
        self::CONDITION_GOOD => 'Good Condition',
        self::CONDITION_DAMAGED => 'Damaged',
        self::CONDITION_PARTIAL => 'Partial Return',
    ];

    const CONDITION_COLORS = [
        self::CONDITION_GOOD => 'green',
        self::CONDITION_DAMAGED => 'red',
        self::CONDITION_PARTIAL => 'amber',
    ];

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function challan(): BelongsTo
    {
        return $this->belongsTo(Challan::class)->withTrashed();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ChallanReturnItem::class, 'challan_return_id');
    }

    /**
     * withTrashed — staff may be deleted but return record must remain readable
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    // ════════════════════════════════════════════════════
    //  ACCESSORS
    // ════════════════════════════════════════════════════

    /**
     * Human-readable condition label
     */
    public function getConditionLabelAttribute(): string
    {
        return self::CONDITION_LABELS[$this->condition] ?? ucfirst($this->condition);
    }

    /**
     * Badge color for condition
     */
    public function getConditionColorAttribute(): string
    {
        return self::CONDITION_COLORS[$this->condition] ?? 'gray';
    }

    /**
     * Total qty returned across all items in this return entry
     */
    public function getTotalQtyReturnedAttribute(): float
    {
        return (float) $this->items->sum('qty_returned');
    }

    /**
     * Total qty damaged across all items in this return entry
     */
    public function getTotalQtyDamagedAttribute(): float
    {
        return (float) $this->items->sum('qty_damaged');
    }

    /**
     * Total clean qty = returned - damaged
     */
    public function getTotalQtyCleanAttribute(): float
    {
        return (float) max(0, $this->total_qty_returned - $this->total_qty_damaged);
    }

    /**
     * Does this return have any damaged items?
     */
    public function getHasDamageAttribute(): bool
    {
        return $this->items->contains(fn ($item) => $item->qty_damaged > 0);
    }

    /**
     * Formatted return date
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->return_date?->format('d M Y') ?? '—';
    }

    // ════════════════════════════════════════════════════
    //  BUSINESS LOGIC
    // ════════════════════════════════════════════════════

    /**
     * Process this return:
     * 1. Validates each return item qty against challan item pending qty
     * 2. Increments qty_returned on each ChallanItem
     * 3. Triggers challan status recalculation
     *
     * Always call inside DB::transaction() from your service.
     *
     * @throws \InvalidArgumentException
     */
    public function process(): void
    {
        $this->loadMissing('items.challanItem', 'challan');

        foreach ($this->items as $returnItem) {
            $returnItem->challanItem->recordReturn($returnItem->qty_returned);
        }

        // After all items processed, recalculate parent challan status
        $this->challan->recalculateStatus();
    }

    /**
     * Generate next return number scoped to company.
     * Format: CR-YYYY-NNNN (e.g. CR-2025-0003)
     * Call inside DB::transaction() with lockForUpdate.
     */
    public static function generateNumber(int $companyId, ?string $prefix = null): string
    {
        $prefix = $prefix ?? 'CR';
        $year = now()->format('Y');

        $latestReturn = static::where('company_id', $companyId)
            ->where('return_number', 'like', "{$prefix}-{$year}-%")
            ->lockForUpdate()
            ->orderBy('id', 'desc')
            ->value('return_number');

        if ($latestReturn) {
            $lastSequence = (int) last(explode('-', $latestReturn));
            $sequence = $lastSequence + 1;
        } else {
            $sequence = 1;
        }

        return "{$prefix}-{$year}-".str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    // ════════════════════════════════════════════════════
    //  SCOPES
    // ════════════════════════════════════════════════════

    public function scopeForChallan(Builder $query, int $challanId): Builder
    {
        return $query->where('challan_id', $challanId);
    }

    public function scopeWithDamage(Builder $query): Builder
    {
        return $query->whereHas('items', fn ($q) => $q->where('qty_damaged', '>', 0));
    }

    public function scopeOfCondition(Builder $query, string $condition): Builder
    {
        return $query->where('condition', $condition);
    }

    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('return_date', $date);
    }

    public function scopeBetweenDates(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('return_date', [$from, $to]);
    }

    // ════════════════════════════════════════════════════
    //  BOOT — immutability + auto-assign
    // ════════════════════════════════════════════════════

    protected static function booted(): void
    {
        static::creating(function (ChallanReturn $return) {
            // Auto-assign created_by if not set
            if (empty($return->created_by)) {
                $return->created_by = Auth::id();
            }
        });

        // Return records are financial events — updating is dangerous
        // If you need to fix a mistake, void and re-enter
        static::updating(function (ChallanReturn $return) {
            // Allow only notes and condition to be updated post-creation
            $allowedDirty = ['notes', 'condition', 'received_by'];
            $dirty = array_keys($return->getDirty());
            $illegal = array_diff($dirty, $allowedDirty);

            if (! empty($illegal)) {
                throw new \LogicException(
                    "ChallanReturn [{$return->id}] cannot update fields: ["
                    .implode(', ', $illegal).']. '
                    .'Only [notes, condition, received_by] are editable after creation.'
                );
            }
        });

        static::deleting(function (ChallanReturn $return) {
            throw new \LogicException(
                "ChallanReturn [{$return->id}] cannot be deleted. "
                .'Return records are permanent inventory events. '
                .'Reverse via a new corrective entry.'
            );
        });
    }
}
