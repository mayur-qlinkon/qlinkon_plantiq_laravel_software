<?php

namespace App\Models\Production;

use App\Enums\Production\BatchSourceType;
use App\Enums\Production\BatchStatus;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\PurchaseItem;
use App\Models\User;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlantBatch extends Model
{
    use SoftDeletes, Tenantable;

    protected $table = 'production_plant_batches';

    protected $fillable = [
        'company_id',
        'batch_code',
        'idempotency_key',
        'product_id',
        'product_sku_id',
        'source_type',
        'source_reference_id',
        'initial_quantity',
        'current_quantity',
        'status',
        'batch_start_datetime',
        'batch_end_datetime',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'batch_start_datetime' => 'datetime',
        'batch_end_datetime'   => 'datetime',
        'initial_quantity'    => 'integer',
        'current_quantity'    => 'integer',
        'source_reference_id' => 'integer',
    ];

    // ════════════════════════════════════════════════════
    //  BOOT — auto-generate batch_code inside transaction
    // ════════════════════════════════════════════════════

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (PlantBatch $batch) {
            if (empty($batch->batch_code)) {
                $batch->batch_code = static::generateCode($batch->company_id);
            }
        });
    }

    // ════════════════════════════════════════════════════
    //  CODE GENERATION
    // ════════════════════════════════════════════════════

    public static function generateCode(int $companyId): string
    {
        $prefix = 'PLB';
        $year   = now()->format('Y');

        // withTrashed() ensures soft-deleted records don't reset the sequence.
        // lockForUpdate() prevents duplicate codes under concurrent requests.
        $latest = static::withTrashed()
            ->where('company_id', $companyId)
            ->where('batch_code', 'like', "{$prefix}-{$year}-%")
            ->lockForUpdate()
            ->orderBy('id', 'desc')
            ->value('batch_code');

        $sequence = $latest
            ? ((int) last(explode('-', $latest))) + 1
            : 1;

        return "{$prefix}-{$year}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function placements(): HasMany
    {
        return $this->hasMany(BatchPlacement::class, 'plant_batch_id');
    }

    public function currentPlacement(): HasMany
    {
        return $this->hasMany(BatchPlacement::class, 'plant_batch_id')->whereNull('ended_at');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(BatchActivity::class, 'plant_batch_id');
    }

    public function losses(): HasMany
    {
        return $this->hasMany(BatchLoss::class, 'plant_batch_id');
    }

    public function harvests(): HasMany
    {
        return $this->hasMany(BatchHarvest::class, 'plant_batch_id');
    }

    public function dailyTasks(): HasMany
    {
        return $this->hasMany(DailyTask::class, 'plant_batch_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(BatchAdjustment::class, 'plant_batch_id');
    }

    // ════════════════════════════════════════════════════
    //  STATUS HELPERS
    // ════════════════════════════════════════════════════

    public function statusEnum(): BatchStatus
    {
        return BatchStatus::from($this->status);
    }

    public function canTransitionTo(BatchStatus $new): bool
    {
        return $this->statusEnum()->canTransitionTo($new);
    }

    public function isActive(): bool
    {
        return $this->status === BatchStatus::Active->value;
    }

    public function isClosed(): bool
    {
        return $this->status === BatchStatus::Closed->value;
    }

    public function isCancelled(): bool
    {
        return $this->status === BatchStatus::Cancelled->value;
    }

    // ════════════════════════════════════════════════════
    //  QUANTITY HELPERS
    // ════════════════════════════════════════════════════

    // True when no loss or harvest has reduced the quantity.
    // Used by service to gate edits on product_id and initial_quantity.
    public function isQuantityIntact(): bool
    {
        return $this->current_quantity === $this->initial_quantity;
    }

    // Real lifecycle age — from batch_start_datetime, NOT created_at.
    // created_at only reflects when the record was entered into the system;
    // batch_start_datetime reflects when the plants actually entered the nursery
    // (which can be backdated, e.g. pre-existing stock).
    // Ends at batch_end_datetime once Closed/Cancelled, otherwise runs to now.
    public function getAgeAttribute(): ?string
    {
        if (!$this->batch_start_datetime) {
            return null;
        }

        return $this->batch_start_datetime->diffForHumans($this->batch_end_datetime ?? now(), true);
    }
    

    // ════════════════════════════════════════════════════
    //  SOURCE RELATIONSHIPS & BRIDGES
    // ════════════════════════════════════════════════════

    public function productionPlanItem(): BelongsTo
    {
        return $this->belongsTo(ProductionPlanItem::class, 'source_reference_id');
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class, 'source_reference_id');
    }

    /**
     * Helper to grab the parent Document (Plan or Purchase) directly for the UI
     */
    public function getSourceDocumentAttribute()
    {
        if ($this->source_type === 'production_plan') {
            // Uses the 'plan()' relation from ProductionPlanItem
            return $this->productionPlanItem?->plan; 
        }

        if ($this->source_type === 'purchase') {
            // Uses the 'purchase()' relation from PurchaseItem
            return $this->purchaseItem?->purchase; 
        }

        return null;
    }

    // ════════════════════════════════════════════════════
    //  SOURCE HELPERS
    // ════════════════════════════════════════════════════  
    public function sourceTypeEnum(): BatchSourceType
    {
        return BatchSourceType::from($this->source_type);
    }

    public function hasPrefillableSource(): bool
    {
        return $this->sourceTypeEnum()->hasReference()
            && !is_null($this->source_reference_id);
    }

    // ════════════════════════════════════════════════════
    //  ACCESSORS — consistent with existing model pattern
    // ════════════════════════════════════════════════════

    public function getStatusLabelAttribute(): string
    {
        return BatchStatus::from($this->status)->label();
    }

    public function getStatusColorAttribute(): array
    {
        return BatchStatus::from($this->status)->color();
    }

    public function getSourceTypeLabelAttribute(): string
    {
        return BatchSourceType::from($this->source_type)->label();
    }

    // ════════════════════════════════════════════════════
    //  SCOPES
    // ════════════════════════════════════════════════════

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', BatchStatus::Active->value);
    }

    public function scopeClosed(Builder $q): Builder
    {
        return $q->where('status', BatchStatus::Closed->value);
    }

    public function scopeByStatus(Builder $q, string $status): Builder
    {
        return $q->where('status', $status);
    }

    public function scopeBySource(Builder $q, string $sourceType): Builder
    {
        return $q->where('source_type', $sourceType);
    }

    public function scopeByProduct(Builder $q, int $productId): Builder
    {
        return $q->where('product_id', $productId);
    }
}