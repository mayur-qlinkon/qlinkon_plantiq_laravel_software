<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallanItem extends Model
{
    use HasFactory;

    // ════════════════════════════════════════════════════
    //  TABLE & FILLABLE
    // ════════════════════════════════════════════════════

    protected $table = 'challan_items';

    protected $fillable = [
        // Parent
        'challan_id',

        // Product reference
        'product_id',
        'product_sku_id',

        // Snapshot
        'product_name',
        'sku_label',
        'sku_code',
        'hsn_code',
        'unit',
        'unit_id',

        // Quantities
        'qty_sent',
        'qty_returned',
        'qty_invoiced',

        // Pricing
        'unit_price',
        'tax_rate',
        'line_value',

        // Conversion reference
        'invoice_item_id',

        // Batch tracking (document-only snapshot — no stock impact)
        'batch_id',
        'batch_number',
        'expiry_date',

        // Notes
        'notes',
    ];

    /**
     * @property float $qty_sent
     * @property float $qty_returned
     * @property float $qty_invoiced
     * @property float $unit_price
     * @property float $tax_rate
     * @property float $line_value
     */
    protected $casts = [
        'qty_sent' => 'decimal:2',
        'qty_returned' => 'decimal:2',
        'qty_invoiced' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'line_value' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    // ════════════════════════════════════════════════════
    //  NO Tenantable trait here intentionally
    //  Reason: ChallanItem has no company_id column.
    //  Tenant isolation is guaranteed by the parent
    //  challan_id FK → challans.company_id.
    //  Never add company_id here — wrong design.
    // ════════════════════════════════════════════════════

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function challan(): BelongsTo
    {
        return $this->belongsTo(Challan::class);
    }

    /**
     * Original product — may be null if product was deleted.
     * Always use snapshot columns (product_name, sku_label etc.)
     * for display and printing. Use this only for live lookups.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /**
     * Original SKU — may be null if SKU was deleted.
     * Same rule: snapshot columns are source of truth for PDFs.
     */
    public function productSku(): BelongsTo // 🌟 FIX: Renamed to match the controller/service calls
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_id')->withTrashed();
    }

    /**
     * The invoice line item this challan item was converted into.
     * Null = not yet invoiced.
     */
    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class, 'invoice_item_id');
    }

    /**
     * The batch snapshot recorded at time of challan dispatch.
     * IMPORTANT: This is a document-only reference.
     * Stock deduction happens ONLY when the challan is converted to an Invoice.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }

    // ════════════════════════════════════════════════════
    //  ACCESSORS — computed qty states
    // ════════════════════════════════════════════════════

    /**
     * qty_pending = qty_sent - qty_returned - qty_invoiced
     * This is the core number the whole module revolves around.
     * Never store this — always compute fresh.
     */
    public function getQtyPendingAttribute(): float
    {
        return (float) max(
            0,
            $this->qty_sent - $this->qty_returned - $this->qty_invoiced
        );
    }

    /**
     * Is this line fully settled?
     * True when nothing is left to return or invoice.
     */
    public function getIsSettledAttribute(): bool
    {
        return $this->qty_pending <= 0;
    }

    /**
     * Has any quantity been invoiced on this line?
     */
    public function getIsPartiallyInvoicedAttribute(): bool
    {
        return $this->qty_invoiced > 0 && $this->qty_invoiced < $this->qty_sent;
    }

    /**
     * Is this line fully invoiced?
     */
    public function getIsFullyInvoicedAttribute(): bool
    {
        return $this->qty_invoiced >= $this->qty_sent;
    }

    /**
     * Has any quantity been returned on this line?
     */
    public function getIsPartiallyReturnedAttribute(): bool
    {
        return $this->qty_returned > 0 && $this->qty_returned < $this->qty_sent;
    }

    /**
     * Is this line fully returned?
     */
    public function getIsFullyReturnedAttribute(): bool
    {
        return $this->qty_returned >= $this->qty_sent;
    }

    /**
     * Tax amount on this line (indicative — real GST computed server-side)
     * Based on qty_sent, not qty_pending
     */
    public function getTaxAmountAttribute(): float
    {
        return (float) round(
            $this->line_value * $this->tax_rate / 100,
            2
        );
    }

    /**
     * Line total including tax
     */
    public function getLineTotalWithTaxAttribute(): float
    {
        return (float) round($this->line_value + $this->tax_amount, 2);
    }

    /**
     * Value of pending (uninvoiced, unreturned) qty
     * Useful for open challan value reports
     */
    public function getPendingValueAttribute(): float
    {
        return (float) round($this->qty_pending * $this->unit_price, 2);
    }

    /**
     * Display label — "Product Name (Red / XL)" or just "Product Name"
     * Safe fallback chain — never crashes even if snapshot is incomplete
     */
    public function getDisplayNameAttribute(): string
    {
        $name = $this->product_name ?? 'Unknown Product';
        $variant = $this->sku_label ?? null;

        return $variant ? "{$name} ({$variant})" : $name;
    }

    /**
     * State of this line as a simple string — useful for badges
     * Computed purely from qty fields, not from parent challan status
     */
    public function getLineStateAttribute(): string
    {
        if ($this->qty_sent <= 0) {
            return 'empty';
        }
        if ($this->is_fully_invoiced) {
            return 'invoiced';
        }
        if ($this->is_fully_returned) {
            return 'returned';
        }
        if ($this->is_partially_invoiced) {
            return 'partial_invoice';
        }
        if ($this->is_partially_returned) {
            return 'partial_return';
        }
        if ($this->qty_pending === $this->qty_sent) {
            return 'pending';
        }

        return 'mixed'; // both return and invoice have happened
    }

    // ════════════════════════════════════════════════════
    //  BUSINESS LOGIC HELPERS
    // ════════════════════════════════════════════════════

    /**
     * Record a return quantity against this line.
     * Validates bounds — can never return more than what's pending.
     *
     * @throws \InvalidArgumentException
     */
    public function recordReturn(float $qty): void
    {
        if ($qty <= 0) {
            throw new \InvalidArgumentException(
                "Return qty must be positive. Got: {$qty} on item [{$this->id}] {$this->display_name}"
            );
        }

        if ($qty > $this->qty_pending) {
            throw new \InvalidArgumentException(
                "Cannot return {$qty} units — only {$this->qty_pending} pending "
                ."on item [{$this->id}] {$this->display_name}"
            );
        }

        $this->increment('qty_returned', $qty);
    }

    /**
     * Record an invoiced quantity against this line.
     * Validates bounds — can never invoice more than what's pending.
     *
     * @throws \InvalidArgumentException
     */
    public function recordInvoiced(float $qty, ?int $invoiceItemId = null): void
    {
        if ($qty <= 0) {
            throw new \InvalidArgumentException(
                "Invoiced qty must be positive. Got: {$qty} on item [{$this->id}] {$this->display_name}"
            );
        }

        if ($qty > $this->qty_pending) {
            throw new \InvalidArgumentException(
                "Cannot invoice {$qty} units — only {$this->qty_pending} pending "
                ."on item [{$this->id}] {$this->display_name}"
            );
        }

        $this->increment('qty_invoiced', $qty);

        // Link to invoice item if provided
        if ($invoiceItemId) {
            $this->update(['invoice_item_id' => $invoiceItemId]);
        }
    }

    /**
     * Recompute line_value from unit_price × qty_sent.
     * Call after editing qty or price.
     */
    public function recomputeLineValue(): void
    {
        $this->line_value = round($this->unit_price * $this->qty_sent, 2);
        $this->save();
    }

    /**
     * Build a snapshot array from a ProductSku for creating a new item.
     * Use in your ChallanService when building items from cart/form data.
     *
     * Usage:
     *   ChallanItem::create(ChallanItem::fromSku($sku, $qty, $challanId));
     */
    public static function fromSku(
        ProductSku $sku,
        float $qty,
        int $challanId
    ): array {
        $product = $sku->product;
        $unitPrice = (float) $sku->price;

        // Build variant label from sku values if available
        $skuLabel = null;
        if ($sku->relationLoaded('skuValues')) {
            $skuLabel = $sku->skuValues
                ->map(fn ($sv) => $sv->attributeValue?->value)
                ->filter()
                ->join(' / ');
        }

        return [
            'challan_id' => $challanId,
            'product_id' => $product->id,
            'product_sku_id' => $sku->id,
            'product_name' => $product->name,
            'sku_label' => $skuLabel ?: null,
            'sku_code' => $sku->sku ?? null,
            'hsn_code' => $product->hsn_code ?? null,
            'unit' => $product->unit ?? 'pcs',
            'unit_id' => $sku->unit_id,
            'qty_sent' => $qty,
            'qty_returned' => 0,
            'qty_invoiced' => 0,
            'unit_price' => $unitPrice,
            'tax_rate' => (float) ($product->tax_rate ?? 0),
            'line_value' => round($unitPrice * $qty, 2),
        ];
    }

    /**
     * The unit of measurement snapshotted at challan time.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    // ════════════════════════════════════════════════════
    //  SCOPES
    // ════════════════════════════════════════════════════

    /**
     * Items that still have pending qty (not fully settled)
     */
    public function scopePending(Builder $query): Builder
    {
        // qty_pending = qty_sent - qty_returned - qty_invoiced > 0
        return $query->whereRaw('(qty_sent - qty_returned - qty_invoiced) > 0');
    }

    /**
     * Items that are fully settled (returned + invoiced = sent)
     */
    public function scopeSettled(Builder $query): Builder
    {
        return $query->whereRaw('(qty_sent - qty_returned - qty_invoiced) <= 0');
    }

    /**
     * Items that have been at least partially invoiced
     */
    public function scopeInvoiced(Builder $query): Builder
    {
        return $query->where('qty_invoiced', '>', 0);
    }

    /**
     * Items not yet invoiced at all
     */
    public function scopeNotInvoiced(Builder $query): Builder
    {
        return $query->where('qty_invoiced', 0);
    }

    /**
     * Items that have been at least partially returned
     */
    public function scopeReturned(Builder $query): Builder
    {
        return $query->where('qty_returned', '>', 0);
    }

    /**
     * Items for a specific product across all challans
     * Useful for product-level open challan reports
     */
    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Items for a specific SKU across all challans
     */
    public function scopeForSku(Builder $query, int $skuId): Builder
    {
        return $query->where('sku_id', $skuId);
    }

    // ════════════════════════════════════════════════════
    //  BOOT — auto-recompute line_value before save
    // ════════════════════════════════════════════════════

    protected static function booted(): void
    {
        // Auto-compute line_value whenever unit_price or qty_sent changes
        static::saving(function (ChallanItem $item) {
            if ($item->isDirty(['unit_price', 'qty_sent'])) {
                $item->line_value = round(
                    (float) $item->unit_price * (float) $item->qty_sent,
                    2
                );
            }
        });
    }
}
