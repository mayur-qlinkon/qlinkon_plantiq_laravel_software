<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Purchase extends Model
{
    use HasFactory, LogsActivity, SoftDeletes,Tenantable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // Ownership
        'company_id',
        'store_id',
        'supplier_id',
        'warehouse_id',
        'created_by',
        'updated_by',

        // Reference Numbers
        'purchase_number',
        'supplier_invoice_number',
        'supplier_invoice_date',

        // Dates
        'purchase_date',
        'due_date',

        // Status
        'status',
        'payment_status',

        // Indian GST & Compliance
        'tax_type',
        'supplier_gst_number',
        'company_gst_number',

        // Amounts
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'taxable_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'tax_amount',
        'shipping_cost',
        'other_charges',
        'round_off',
        'total_amount',

        // Payment Tracking
        'paid_amount',
        'balance_amount',

        // Extra
        'notes',
        'terms_and_conditions',
        'cancellation_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'purchase_date' => 'date',
        'due_date' => 'date',

        // Decimal casting ensures accuracy for financial calculations
        'subtotal' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'round_off' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // Logs every fillable attribute
            ->logOnlyDirty() // ONLY logs attributes that actually changed
            ->dontSubmitEmptyLogs() // Prevents logging if nothing was actually modified
            ->setDescriptionForEvent(fn (string $eventName) => "Purchase has been {$eventName}");
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Polymorphic payments — same table used by Invoice, Order, Expense.
     * PaymentService::recordPayment() uses this automatically.
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    /**
     * grand_total accessor — PaymentService::syncDocumentPaymentStatus()
     * reads $document->grand_total on every document type.
     * Purchase stores it as total_amount, so we bridge the gap here.
     */
    public function getGrandTotalAttribute(): float
    {
        return (float) $this->total_amount;
    }
}
