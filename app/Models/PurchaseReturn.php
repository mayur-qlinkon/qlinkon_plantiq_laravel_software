<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturn extends Model
{
    use HasFactory, SoftDeletes,Tenantable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id'];

    protected $fillable = [
        // Ownership
        'company_id',
        'store_id',
        'supplier_id',
        'warehouse_id',
        'purchase_id',
        'created_by',

        // Reference Numbers
        'return_number',
        'supplier_credit_note_number',

        // Dates
        'return_date',

        // Status & Tax Type
        'status',
        'payment_status',
        'tax_type',

        // Amounts (15,2)
        'subtotal',
        'discount_type',        
        'discount_value',
        'discount_amount',
        'taxable_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'tax_amount',
        'total_amount',

        // Extra
        'reason',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'return_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];
  

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

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }
}
