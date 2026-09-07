<?php

namespace App\Models;

use App\Traits\SerializesLocalDates;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Challan extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SerializesLocalDates, SoftDeletes, Tenantable;

    // ════════════════════════════════════════════════════
    //  TABLE & FILLABLE
    // ════════════════════════════════════════════════════

    protected $table = 'challans';

    protected $fillable = [
        // Tenancy
        'company_id',
        'store_id',

        // Identity
        'challan_number',
        'challan_date',
        'challan_type',
        'direction',

        // GST / State
        'from_state_id',
        'to_state_id',
        'is_inter_state',

        // Party (simple FKs)
        'client_id',
        'supplier_id',
        'branch_store_id',
        'warehouse_id', // Add this if missing
        'to_warehouse_id', // 🌟 ADD THIS LINE HERE

        // Party snapshot
        'party_name',
        'party_address',
        'party_gst',
        'party_phone',
        'party_state',

        // Transport
        'transport_mode',
        'transport_name',
        'vehicle_number',
        'lr_number',
        'eway_bill_number',
        'eway_bill_expiry',

        // Return tracking
        'is_returnable',
        'return_due_date',
        'return_received_date',

        // Source document
        'source_type',
        'source_id',

        // Status
        'status',

        // Financials
        'total_qty',
        'total_value',

        // Notes
        'purpose_note',
        'internal_notes',

        // Delivery confirmation
        'received_by',
        'delivered_at',

        // Audit
        'created_by',
        'dispatched_by',
    ];

    /**
     * @property Carbon $challan_date
     * @property Carbon|null $eway_bill_expiry
     * @property Carbon|null $return_due_date
     * @property Carbon|null $return_received_date
     * @property Carbon|null $delivered_at
     * @property bool $is_inter_state
     * @property bool $is_returnable
     */
    protected $casts = [
        'challan_date' => 'datetime',
        'eway_bill_expiry' => 'datetime',
        'return_due_date' => 'datetime',
        'return_received_date' => 'datetime',
        'delivered_at' => 'datetime',
        'is_inter_state' => 'boolean',
        'is_returnable' => 'boolean',
        'total_qty' => 'decimal:2',
        'total_value' => 'decimal:2',
    ];

    // ════════════════════════════════════════════════════
    //  CONSTANTS — single source of truth for all enums
    // ════════════════════════════════════════════════════

    const TYPE_DELIVERY = 'delivery';

    const TYPE_JOB_WORK_OUT = 'job_work_out';

    const TYPE_JOB_WORK_IN = 'job_work_in';

    const TYPE_BRANCH_TRANSFER = 'branch_transfer';

    const TYPE_SALE_ON_APPROVAL = 'sale_on_approval';

    const TYPE_CONSIGNMENT = 'consignment';

    const TYPE_REPAIR_OUT = 'repair_out';

    const TYPE_EXHIBITION = 'exhibition';

    const TYPE_RETURNABLE = 'returnable';

    const TYPE_NON_RETURNABLE = 'non_returnable';

    const DIRECTION_OUTWARD = 'outward';

    const DIRECTION_INWARD = 'inward';

    const STATUS_DRAFT = 'draft';

    const STATUS_DISPATCHED = 'dispatched';

    const STATUS_IN_TRANSIT = 'in_transit';

    const STATUS_DELIVERED = 'delivered';

    const STATUS_PARTIALLY_RETURNED = 'partially_returned';

    const STATUS_FULLY_RETURNED = 'fully_returned';

    const STATUS_CONVERTED = 'converted_to_invoice';

    const STATUS_PARTIALLY_CONVERTED = 'partially_converted';

    const STATUS_CLOSED = 'closed';

    const STATUS_CANCELLED = 'cancelled';

    // Types that are always returnable by nature
    const RETURNABLE_TYPES = [
        self::TYPE_JOB_WORK_OUT,
        self::TYPE_SALE_ON_APPROVAL,
        self::TYPE_CONSIGNMENT,
        self::TYPE_REPAIR_OUT,
        self::TYPE_EXHIBITION,
        self::TYPE_RETURNABLE,
    ];

    // Valid status transitions — prevents illegal state changes
    const STATUS_TRANSITIONS = [
        self::STATUS_DRAFT => [self::STATUS_DISPATCHED, self::STATUS_CANCELLED],
        self::STATUS_DISPATCHED => [self::STATUS_IN_TRANSIT, self::STATUS_DELIVERED, self::STATUS_CANCELLED],
        self::STATUS_IN_TRANSIT => [self::STATUS_DELIVERED, self::STATUS_CANCELLED],
        self::STATUS_DELIVERED => [
            self::STATUS_PARTIALLY_RETURNED,
            self::STATUS_FULLY_RETURNED,
            self::STATUS_CONVERTED,
            self::STATUS_PARTIALLY_CONVERTED,
            self::STATUS_CLOSED,
        ],
        self::STATUS_PARTIALLY_RETURNED => [
            self::STATUS_FULLY_RETURNED,
            self::STATUS_PARTIALLY_CONVERTED,
            self::STATUS_CONVERTED,
            self::STATUS_CLOSED,
        ],
        self::STATUS_PARTIALLY_CONVERTED => [
            self::STATUS_CONVERTED,
            self::STATUS_PARTIALLY_RETURNED,
            self::STATUS_CLOSED,
        ],
        self::STATUS_FULLY_RETURNED => [self::STATUS_CLOSED],
        self::STATUS_CONVERTED => [self::STATUS_CLOSED],
        self::STATUS_CLOSED => [],  // terminal
        self::STATUS_CANCELLED => [],  // terminal
    ];

    // Human-readable labels — use in blade/views
    const TYPE_LABELS = [
        self::TYPE_DELIVERY => 'Delivery',
        self::TYPE_JOB_WORK_OUT => 'Job Work (Out)',
        self::TYPE_JOB_WORK_IN => 'Job Work (In)',
        self::TYPE_BRANCH_TRANSFER => 'Branch Transfer',
        self::TYPE_SALE_ON_APPROVAL => 'Sale on Approval',
        self::TYPE_CONSIGNMENT => 'Consignment',
        self::TYPE_REPAIR_OUT => 'Repair / Service',
        self::TYPE_EXHIBITION => 'Exhibition',
        self::TYPE_RETURNABLE => 'Returnable',
        self::TYPE_NON_RETURNABLE => 'Non-Returnable',
    ];

    const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_DISPATCHED => 'Dispatched',
        self::STATUS_IN_TRANSIT => 'In Transit',
        self::STATUS_DELIVERED => 'Delivered',
        self::STATUS_PARTIALLY_RETURNED => 'Partially Returned',
        self::STATUS_FULLY_RETURNED => 'Fully Returned',
        self::STATUS_CONVERTED => 'Converted to Invoice',
        self::STATUS_PARTIALLY_CONVERTED => 'Partially Converted',
        self::STATUS_CLOSED => 'Closed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    // Badge colors for UI (Tailwind-friendly)
    const STATUS_COLORS = [
        self::STATUS_DRAFT => 'gray',
        self::STATUS_DISPATCHED => 'blue',
        self::STATUS_IN_TRANSIT => 'indigo',
        self::STATUS_DELIVERED => 'cyan',
        self::STATUS_PARTIALLY_RETURNED => 'amber',
        self::STATUS_FULLY_RETURNED => 'teal',
        self::STATUS_CONVERTED => 'green',
        self::STATUS_PARTIALLY_CONVERTED => 'lime',
        self::STATUS_CLOSED => 'slate',
        self::STATUS_CANCELLED => 'red',
    ];

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function fromState(): BelongsTo
    {
        return $this->belongsTo(State::class, 'from_state_id');
    }

    public function toState(): BelongsTo
    {
        return $this->belongsTo(State::class, 'to_state_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branchStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'branch_store_id');
    }

    /**
     * Returns whichever party is set — use in snapshotParty(), PDF, display
     */
    public function getPartyModelAttribute(): ?Model
    {
        return $this->client ?? $this->supplier ?? $this->branchStore ?? null;
    }

    /**
     * Source document — Quotation, Order, Purchase, etc.
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Invoices generated from this challan. Cancelled invoices are excluded
     * because their quantities have been released back to the challan.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'challan_id')
            ->where('status', '!=', 'cancelled')
            ->latest('id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ChallanItem::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ChallanReturn::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ChallanStatusHistory::class)->orderBy('created_at', 'asc');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dispatchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    // ════════════════════════════════════════════════════
    //  SPATIE MEDIA LIBRARY — collections
    // ════════════════════════════════════════════════════

    public function registerMediaCollections(): void
    {
        // Scanned / photographed signed delivery copy
        $this->addMediaCollection('signed_copy')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf']);

        // E-Way Bill PDF or screenshot
        $this->addMediaCollection('eway_bill')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf']);

        // Photos of goods condition (dispatch or receipt)
        $this->addMediaCollection('photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        // Any other document — LR copy, gate pass, etc.
        $this->addMediaCollection('documents');
    }

    // ════════════════════════════════════════════════════
    //  ACCESSORS
    // ════════════════════════════════════════════════════

    /**
     * Human-readable challan type label
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->challan_type] ?? ucfirst($this->challan_type);
    }

    /**
     * Human-readable status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    /**
     * UI badge color string
     */
    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    /**
     * Total qty pending = sent - returned - invoiced (across all items)
     */
    /**
     * Total qty pending = sent - returned - invoiced (across all items)
     * ⚠️  Always eager-load items to avoid N+1: Challan::with('items')->get()
     */
    public function getPendingQtyAttribute(): float
    {
        return (float) $this->items->sum(fn ($item) => $item->qty_sent - $item->qty_returned - $item->qty_invoiced
        );
    }

    /**
     * Is this challan fully settled (nothing pending)?
     */
    public function getIsFullySettledAttribute(): bool
    {
        return $this->pending_qty <= 0;
    }

    /**
     * Is e-way bill expired?
     */
    public function getIsEwayExpiredAttribute(): bool
    {
        if (! $this->eway_bill_expiry) {
            return false;
        }

        return $this->eway_bill_expiry->isPast();
    }

    /**
     * Days until return due date (negative = overdue)
     */
    public function getDaysUntilReturnDueAttribute(): ?int
    {
        if (! $this->return_due_date) {
            return null;
        }

        /** @var Carbon $due */
        $due = $this->return_due_date;

        return (int) now()->diffInDays($due, false);
    }

    /**
     * Is return overdue?
     */
    public function getIsReturnOverdueAttribute(): bool
    {
        if (! $this->is_returnable || ! $this->return_due_date) {
            return false;
        }
        if (in_array($this->status, [self::STATUS_FULLY_RETURNED, self::STATUS_CLOSED, self::STATUS_CANCELLED])) {
            return false;
        }

        return $this->return_due_date->isPast();
    }

    /**
     * GST type label for display
     */
    public function getGstTypeAttribute(): string
    {
        return $this->is_inter_state ? 'IGST' : 'CGST + SGST';
    }

    /**
     * Can this challan be edited?
     */
    public function getIsEditableAttribute(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Can this challan be cancelled?
     */
    public function getIsCancellableAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_DISPATCHED,
            self::STATUS_IN_TRANSIT,
        ]);
    }

    /**
     * Is any item on this challan already invoiced?
     * Replaces the old invoice_id check.
     */
    public function getIsConvertedAttribute(): bool
    {
        return $this->items->contains(
            fn ($item) => $item->qty_invoiced > 0
        );
    }

    /**
     * Can this challan be converted to invoice?
     */
    public function getIsConvertibleAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_DELIVERED,
            self::STATUS_PARTIALLY_RETURNED,
            self::STATUS_PARTIALLY_CONVERTED,
        ]) && $this->pending_qty > 0;
    }

    // ════════════════════════════════════════════════════
    //  STATUS TRANSITION — safe, audited
    // ════════════════════════════════════════════════════

    /**
     * Transition to a new status with validation + history log
     *
     * @throws \InvalidArgumentException
     */
    public function transitionTo(
        string $newStatus,
        ?string $notes = null,
        string $changedByType = 'admin',
        ?int $changedBy = null
    ): void {
        $allowed = self::STATUS_TRANSITIONS[$this->status] ?? [];

        if (! in_array($newStatus, $allowed)) {
            throw new \InvalidArgumentException(
                "Cannot transition challan #{$this->challan_number} "
                ."from [{$this->status}] to [{$newStatus}]. "
                .'Allowed: ['.implode(', ', $allowed).']'
            );
        }

        $fromStatus = $this->status;
        $this->status = $newStatus;

        // Auto-set delivered_at timestamp
        if ($newStatus === self::STATUS_DELIVERED && ! $this->delivered_at) {
            $this->delivered_at = Carbon::now();
        }
        $this->save();

        ChallanStatusHistory::create([
            'challan_id' => $this->id,
            'from_status' => $fromStatus,
            'to_status' => $newStatus,
            'notes' => $notes,
            'changed_by_type' => $changedByType,
            'changed_by' => $changedBy ?? Auth::id(),
        ]);
    }

    // ════════════════════════════════════════════════════
    //  BUSINESS LOGIC HELPERS
    // ════════════════════════════════════════════════════

    /**
     * Compute and store is_inter_state based on from/to state IDs.
     * Call this before saving when states are set.
     */
    public function computeInterState(): void
    {
        if ($this->from_state_id && $this->to_state_id) {
            $this->is_inter_state = $this->from_state_id !== $this->to_state_id;
        }
    }

    /**
     * Sync total_qty and total_value from items.
     * Call after adding/editing/removing items.
     */
    public function syncTotals(): void
    {
        $this->total_qty = $this->items()->sum('qty_sent');
        $this->total_value = $this->items()->sum('line_value');
        $this->save();
    }

    /**
     * Recalculate and auto-update status based on item states.
     * Call after any return or invoice conversion.
     */
    public function recalculateStatus(): void
    {
        // Only applies to challans that have been delivered
        if (! in_array($this->status, [
            self::STATUS_DELIVERED,
            self::STATUS_PARTIALLY_RETURNED,
            self::STATUS_PARTIALLY_CONVERTED,
            self::STATUS_FULLY_RETURNED,
            self::STATUS_CONVERTED,
        ])) {
            return;
        }

        $items = $this->items;

        $totalSent = $items->sum('qty_sent');
        $totalReturned = $items->sum('qty_returned');
        $totalInvoiced = $items->sum('qty_invoiced');
        $totalPending = $totalSent - $totalReturned - $totalInvoiced;

        if ($totalSent <= 0) {
            return;
        }

        // Determine new status
        if ($totalPending <= 0 && $totalInvoiced >= $totalSent) {
            $newStatus = self::STATUS_CONVERTED;
        } elseif ($totalPending <= 0 && $totalReturned > 0) {
            $newStatus = self::STATUS_FULLY_RETURNED;
        } elseif ($totalInvoiced > 0 && $totalPending > 0) {
            $newStatus = self::STATUS_PARTIALLY_CONVERTED;
        } elseif ($totalReturned > 0 && $totalPending > 0) {
            $newStatus = self::STATUS_PARTIALLY_RETURNED;
        } else {
            return; // No change needed
        }

        if ($newStatus !== $this->status) {
            $this->transitionTo($newStatus, 'Auto-updated based on item quantities', 'system');
        }
    }

    /**
     * Snapshot party details from the related model.
     * Call before saving so old challans always print correctly.
     */
    public function snapshotParty(): void
    {
        $p = $this->client ?? $this->supplier ?? $this->branchStore ?? null;

        if (! $p) {
            return;
        }

        $this->party_name = $p->name ?? null;
        $this->party_phone = $p->phone ?? null;
        $this->party_gst = $p->gst_number ?? null;
        $this->party_address = $p->address ?? null;
        $this->party_state = $p->state?->name ?? null;
    }

    /**
     * Auto-set return_due_date based on GST rules and challan type.
     * Inputs (raw material etc.) = 1 year
     * Capital goods = 3 years
     * Default = 1 year for any returnable type
     */
    public function setReturnDueDate(string $goodsCategory = 'inputs'): void
    {
        if (! $this->is_returnable) {
            return;
        }
        if (! $this->challan_date) {
            return;
        }

        /** @var Carbon $date */
        $date = $this->challan_date;

        $this->return_due_date = match ($goodsCategory) {
            'capital_goods' => $date->copy()->addYears(3),
            default => $date->copy()->addYear(),
        };
    }

    // ════════════════════════════════════════════════════
    //  SCOPES — common queries
    // ════════════════════════════════════════════════════

    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeOutward(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_OUTWARD);
    }

    public function scopeInward(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_INWARD);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            self::STATUS_CLOSED,
            self::STATUS_CANCELLED,
            self::STATUS_FULLY_RETURNED,
            self::STATUS_CONVERTED,
        ]);
    }

    public function scopeOverdueReturn(Builder $query): Builder
    {
        return $query
            ->where('is_returnable', true)
            ->whereNotNull('return_due_date')
            ->where('return_due_date', '<', now())
            ->whereNotIn('status', [
                self::STATUS_FULLY_RETURNED,
                self::STATUS_CLOSED,
                self::STATUS_CANCELLED,
            ]);
    }

    public function scopeOfType(Builder $query, string|array $type): Builder
    {
        return is_array($type)
            ? $query->whereIn('challan_type', $type)
            : $query->where('challan_type', $type);
    }

    public function scopeOfStatus(Builder $query, string|array $status): Builder
    {
        return is_array($status)
            ? $query->whereIn('status', $status)
            : $query->where('status', $status);
    }

    public function scopeWithPendingReturn(Builder $query): Builder
    {
        return $query
            ->where('is_returnable', true)
            ->whereIn('status', [
                self::STATUS_DELIVERED,
                self::STATUS_PARTIALLY_RETURNED,
            ]);
    }

    public function scopeConvertible(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_DELIVERED,
            self::STATUS_PARTIALLY_RETURNED,
            self::STATUS_PARTIALLY_CONVERTED,
        ]);
    }

    /**
     * Aging scope — challans open for more than N days
     */
    public function scopeOlderThan(Builder $query, int $days): Builder
    {
        return $query->open()->where('challan_date', '<=', now()->subDays($days));
    }

    public function scopeInterState(Builder $query): Builder
    {
        return $query->where('is_inter_state', true);
    }

    public function scopeIntraState(Builder $query): Builder
    {
        return $query->where('is_inter_state', false);
    }

    // ════════════════════════════════════════════════════
    //  NUMBER GENERATION
    // ════════════════════════════════════════════════════

    /**
     * Generate next challan number for a company safely.
     * Format: DC-YYYY-NNNN  (e.g. DC-2026-0042)
     */
    public static function generateNumber(int $companyId, ?string $prefix = null): string
    {
        $prefix = $prefix ?? 'DC';
        $year = now()->format('Y');

        // 1. Fetch the absolute latest challan number for this prefix/year
        // 🛡️ withTrashed() ensures we don't trip over soft-deleted records!
        $latestChallan = static::withTrashed()
            ->where('company_id', $companyId)
            ->where('challan_number', 'like', "{$prefix}-{$year}-%")
            ->lockForUpdate() // Keeps it thread-safe inside the transaction
            ->orderBy('id', 'desc')
            ->value('challan_number');

        if ($latestChallan) {
            // 2. Extract the last part (e.g., '0042' from 'DC-2026-0042') and cast to int
            $lastSequence = (int) last(explode('-', $latestChallan));
            $sequence = $lastSequence + 1;
        } else {
            // 3. Start fresh if no records exist for this year
            $sequence = 1;
        }

        return "{$prefix}-{$year}-".str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
    // ════════════════════════════════════════════════════
    //  BOOT — auto-set returnable flag from type
    // ════════════════════════════════════════════════════

    protected static function booted(): void
    {
        static::creating(function (Challan $challan) {
            // Auto-set is_returnable based on type
            if (in_array($challan->challan_type, self::RETURNABLE_TYPES)) {
                $challan->is_returnable = true;
            }

            // Auto-compute inter_state
            $challan->computeInterState();
        });

        static::updating(function (Challan $challan) {
            // Recompute inter_state if states changed
            if ($challan->isDirty(['from_state_id', 'to_state_id'])) {
                $challan->computeInterState();
            }
        });

        static::deleting(function (Challan $challan) {
            // Prevent deletion if returns exist
            if ($challan->returns()->exists()) {
                throw new \LogicException(
                    "Cannot delete Challan #{$challan->challan_number} because goods have already been returned against it. Please cancel it instead."
                );
            }
        });
    }
}
