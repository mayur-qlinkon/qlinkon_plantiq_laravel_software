<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InvoiceWriteOff extends Model
{
    use HasFactory, LogsActivity, SoftDeletes, Tenantable;

    protected $fillable = [
        'company_id',
        'store_id',
        'invoice_id',
        'customer_id',
        'created_by',
        'approved_by',
        'approved_at',
        'write_off_number',
        'write_off_date',
        'amount',
        'status',
        'reason',
        'notes',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'write_off_date' => 'date',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    /**
     * Spatie Activitylog Configuration
     * Tracks every modification to this Kasar document for audit trail.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Invoice Write-Off (Kasar) has been {$eventName}");
    }

    // ─────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

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

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}