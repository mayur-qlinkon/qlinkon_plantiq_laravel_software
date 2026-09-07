<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallanReturnItem extends Model
{
    use HasFactory;

    // ════════════════════════════════════════════════════
    //  TABLE & FILLABLE
    // ════════════════════════════════════════════════════

    protected $table = 'challan_return_items';

    protected $fillable = [
        'challan_return_id',
        'challan_item_id',
        'qty_returned',
        'qty_damaged',
        'damage_note',
    ];

    /**
     * @property float $qty_returned
     * @property float $qty_damaged
     */
    protected $casts = [
        'qty_returned' => 'decimal:2',
        'qty_damaged' => 'decimal:2',
    ];

    // ════════════════════════════════════════════════════
    //  NO Tenantable — No SoftDeletes — intentional
    //  Tenant isolation: challan_return_id → challan_returns.company_id
    //  Immutability: same reason as ChallanReturn —
    //  these are physical inventory events, not editable records.
    // ════════════════════════════════════════════════════

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function challanReturn(): BelongsTo
    {
        return $this->belongsTo(ChallanReturn::class, 'challan_return_id');
    }

    /**
     * The original challan line item this return is against.
     * Never nullOnDelete in migration — if challan_item disappears,
     * something has gone very wrong upstream. Keep the reference.
     */
    public function challanItem(): BelongsTo
    {
        return $this->belongsTo(ChallanItem::class, 'challan_item_id');
    }

    // ════════════════════════════════════════════════════
    //  ACCESSORS
    // ════════════════════════════════════════════════════

    /**
     * Clean qty = returned minus damaged
     * Only undamaged goods are usable back in inventory
     */
    public function getQtyCleanAttribute(): float
    {
        return (float) max(0, $this->qty_returned - $this->qty_damaged);
    }

    /**
     * Is there any damage on this line?
     */
    public function getHasDamageAttribute(): bool
    {
        return $this->qty_damaged > 0;
    }

    /**
     * Damage percentage on this line (for reporting)
     */
    public function getDamagePercentAttribute(): float
    {
        if ($this->qty_returned <= 0) {
            return 0.0;
        }

        return (float) round(
            ($this->qty_damaged / $this->qty_returned) * 100,
            1
        );
    }

    /**
     * Safe display name — delegates to challan item snapshot.
     * Falls back gracefully if relation not loaded.
     * ⚠️ Eager-load challanItem to avoid N+1
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->challanItem?->display_name ?? "Item #{$this->challan_item_id}";
    }

    // ════════════════════════════════════════════════════
    //  SCOPES
    // ════════════════════════════════════════════════════

    /**
     * Lines that have damage
     */
    public function scopeWithDamage(Builder $query): Builder
    {
        return $query->where('qty_damaged', '>', 0);
    }

    /**
     * Lines for a specific challan item — useful for history view
     */
    public function scopeForChallanItem(Builder $query, int $challanItemId): Builder
    {
        return $query->where('challan_item_id', $challanItemId);
    }

    // ════════════════════════════════════════════════════
    //  BOOT — validation + immutability
    // ════════════════════════════════════════════════════

    protected static function booted(): void
    {
        static::creating(function (ChallanReturnItem $item) {

            // qty_damaged can never exceed qty_returned
            if ($item->qty_damaged > $item->qty_returned) {
                throw new \InvalidArgumentException(
                    "qty_damaged ({$item->qty_damaged}) cannot exceed "
                    ."qty_returned ({$item->qty_returned}) "
                    ."on challan_item_id [{$item->challan_item_id}]."
                );
            }

            // qty_returned must be positive
            if ($item->qty_returned <= 0) {
                throw new \InvalidArgumentException(
                    'qty_returned must be greater than 0 '
                    ."on challan_item_id [{$item->challan_item_id}]."
                );
            }
        });

        // Return item lines are immutable once written
        static::updating(function (ChallanReturnItem $item) {
            // Allow only damage_note to be updated (e.g. typo fix)
            $allowedDirty = ['damage_note'];
            $dirty = array_keys($item->getDirty());
            $illegal = array_diff($dirty, $allowedDirty);

            if (! empty($illegal)) {
                throw new \LogicException(
                    "ChallanReturnItem [{$item->id}] cannot update fields: ["
                    .implode(', ', $illegal).']. '
                    .'Only [damage_note] is editable after creation.'
                );
            }
        });

        static::deleting(function (ChallanReturnItem $item) {
            throw new \LogicException(
                "ChallanReturnItem [{$item->id}] cannot be deleted. "
                .'Return line items are permanent records.'
            );
        });
    }
}
