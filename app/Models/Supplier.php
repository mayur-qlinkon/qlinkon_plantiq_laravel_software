<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes, Tenantable;

    protected $fillable = [
        'company_id',        
        'name',
        'email',
        'phone',
        'address',
        'city',
        'pincode',
        'state_id',
        'gstin',
        'pan',
        'registration_type',
        'bank_name',
        'account_number',
        'ifsc_code',
        'branch',
        'opening_balance',
        'balance_type',
        'current_balance',
        'credit_days',
        'credit_limit',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'credit_days' => 'integer',
    ];

    /**
     * Get the store that this supplier is assigned to (if store-specific)
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the company that owns this supplier profile
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the state for GST compliance
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
