<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes,Tenantable;

    protected $fillable = [
        'company_id',
        'user_id',
        'name',
        'client_code',
        'company_name',
        'email',
        'phone',
        'gst_number',
        'registration_type',
        'address',
        'city',
        'state_id',
        'zip_code',
        'country',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
  

    /**
     * Get the company that owns this client.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the store that this client belongs to.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the state for GST compliance.
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Future feature: Get the login user account associated with this client profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all invoices generated for this client.
     */
    public function invoices(): HasMany
    {
            return $this->hasMany(Invoice::class, 'customer_id');
    }
}
