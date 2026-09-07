<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\SerializesLocalDates;
use App\Traits\Tenantable;

class InvoiceReturn extends Model
{
    use Tenantable, HasFactory, SerializesLocalDates, SoftDeletes;

    protected $fillable = [        
        'store_id',
        'warehouse_id',
        'invoice_id',
        'customer_id',
        'customer_name',
        'created_by',
        'approved_by',
        'approved_at',
        'salesperson_id',
        'pos_terminal_id',
        'credit_note_number',
        'source',
        'return_date',
        'max_returnable_qty',
        'return_type',
        'refunded_amount',
        'return_reason',
        'restock',
        'stock_updated',
        'supply_state',
        'billing_address',
        'shipping_address',
        'gst_treatment',
        'currency_code',
        'exchange_rate',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'taxable_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'tax_amount',
        'shipping_charge',
        'other_charges',
        'round_off',
        'grand_total',
        'status',
        'refund_status',
        'irn',
        'notes',
        'terms_conditions',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'return_date' => 'date',
        'approved_at' => 'datetime',
        'billing_address' => 'array', // JSON to Array
        'shipping_address' => 'array', // JSON to Array
        'restock' => 'boolean',
        'stock_updated' => 'boolean',
        // Mathematical precision casting
        'exchange_rate' => 'float',
        'max_returnable_qty' => 'float',
        'refunded_amount' => 'float',
        'subtotal' => 'float',
        'discount_amount' => 'float',
        'discount_value' => 'float',        
        'taxable_amount' => 'float',
        'cgst_amount' => 'float',
        'sgst_amount' => 'float',
        'igst_amount' => 'float',
        'tax_amount' => 'float',
        'shipping_charge' => 'float',
        'other_charges' => 'float',
        'round_off' => 'float',
        'grand_total' => 'float',
    ];


    // ─────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceReturnItem::class, 'invoice_return_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    // 🌟 Links directly back to the original invoice
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'customer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }
}
