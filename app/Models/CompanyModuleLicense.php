<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyModuleLicense extends Model
{
    use Tenantable;
    protected $fillable = [
        'company_id',
        'module_id',
        'seat_limit',
        'is_active',
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'seat_limit' => 'integer',
        'is_active'  => 'boolean',
        'starts_at'  => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * True when this license is currently usable (active + within date window).
     * Seat availability is counted live from user_module_access rows in
     * ModuleAssignmentService — never denormalised onto this table.
     */
    public function isCurrentlyValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

}