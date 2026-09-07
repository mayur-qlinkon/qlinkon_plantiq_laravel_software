<?php

namespace App\Models;

use App\Traits\Tenantable;
use App\Models\Project\ProjectChargeAllocation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes,Tenantable;

    /**
     * The attributes that are mass assignable.
     * 🌟 This fixes the "Add [company_id] to fillable property" error!
     */
    protected $fillable = [
        'company_id',
        'store_id',
        'created_by',
        'payment_method_id',
        // Polymorphic Party (Customer / Supplier)
        'party_type',
        'party_id',
        // Polymorphic Document Reference (Invoice / Purchase Order)
        'paymentable_type',
        'paymentable_id',
        'payment_number',
        'idempotency_key',
        'reference',
        'payment_date',
        'type', // 'sent' or 'received'
        'amount',
        'amount_received',
        'change_returned',
        'payment_for',
        'currency_code',
        'exchange_rate',
        'status', // 'pending', 'completed', 'cancelled', 'bounced'
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'amount_received' => 'decimal:2',
        'change_returned' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
    ];
    // -------------------------------------------------------------------------
    // STANDARD RELATIONSHIPS
    // -------------------------------------------------------------------------

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * What this payment settled in the Projects module.
     *
     * paymentable answers "where did this come from"; these answer "what did it
     * pay off". Different questions, which is why one payment can span several
     * charges.
     */
    public function projectAllocations(): HasMany
    {
        return $this->hasMany(ProjectChargeAllocation::class, 'payment_id');
    }

    // -------------------------------------------------------------------------
    // POLYMORPHIC RELATIONSHIPS
    // -------------------------------------------------------------------------

    /**
     * Get the party that made/received the payment (Customer or Supplier).
     */
    public function party(): MorphTo
    {
        // Explicitly telling Laravel to use party_type and party_id
        return $this->morphTo(__FUNCTION__, 'party_type', 'party_id');
    }

    /**
     * Get the parent document (Invoice or Purchase) this payment belongs to.
     */
    public function paymentable(): MorphTo
    {
        // Because the method is called 'paymentable',
        // Laravel automatically looks for 'paymentable_type' and 'paymentable_id'
        return $this->morphTo();
    }
}
