<?php

namespace App\Models\Appointment;

use App\Models\Company;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Appointment extends Model
{
    use Tenantable;

    protected $table = 'appointments';

    // ── Status Constants ───────────────────────────────────

    const STATUS_PENDING   = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const STATUS_LABELS = [
        self::STATUS_PENDING   => 'Pending',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    const STATUS_COLORS = [
        self::STATUS_PENDING   => 'warning',
        self::STATUS_CONFIRMED => 'info',
        self::STATUS_COMPLETED => 'success',
        self::STATUS_CANCELLED => 'danger',
    ];

    protected $fillable = [
        'company_id',
        'appointment_no',
        'service_id',
        'slot_id',
        'appointment_date',
        'customer_name',
        'customer_phone',
        'customer_email',
        'address',
        'notes',
        'status',
        'admin_notes',
    ];

    protected $casts = [
        'appointment_date' => 'date',
    ];

    // ── Boot ───────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Appointment $appointment) {
            if (empty($appointment->appointment_no)) {
                $appointment->appointment_no = static::generateAppointmentNo(
                    $appointment->company_id ?? Auth::user()?->company_id
                );
            }
        });

        // Feeds the appointments_active_booking_unique index, which is what
        // actually stops two customers holding the same slot on the same date.
        // NULL for cancelled rows, and a unique index ignores NULLs, so
        // cancelling releases the slot for rebooking.
        //
        // saving fires on both insert and update, so a status change to
        // cancelled clears the key in the same write. Every status change in
        // the app goes through a model instance; a query-builder mass update
        // would bypass this hook and must not be introduced.
        static::saving(function (Appointment $appointment) {
            $appointment->active_booking_key = $appointment->buildActiveBookingKey();
        });
    }

    // ── Relationships ──────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(AppointmentService::class, 'service_id');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(AppointmentSlot::class, 'slot_id');
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_CONFIRMED])
                     ->where('appointment_date', '>=', now()->toDateString());
    }

    // ── Accessors ──────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->appointment_date?->format('d M Y') ?? '—';
    }

    // ── Static Helpers ─────────────────────────────────────
        /**
     * The slot-occupancy key for this row, or null when cancelled.
     */
    public function buildActiveBookingKey(): ?string
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return null;
        }

        $date = $this->appointment_date instanceof \DateTimeInterface
            ? $this->appointment_date->format('Y-m-d')
            : (string) $this->appointment_date;

        return $this->company_id . '-' . $date . '-' . $this->slot_id;
    }
    /**
     * Generate unique appointment number: APT-YYYYMM-XXXXX
     * e.g. APT-202606-00001
     */
    public static function generateAppointmentNo(int $companyId): string
    {
        $prefix = 'APT-' . date('Ym') . '-';

        $last = static::withoutGlobalScope('tenant')
            ->where('company_id', $companyId)
            ->where('appointment_no', 'like', $prefix . '%')
            ->max('appointment_no');

        $nextNum = $last ? ((int) substr($last, -5)) + 1 : 1;

        return $prefix . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Check if a slot is already booked for a given date.
     * Cancelled appointments do NOT block the slot.
     */
    public static function isSlotBooked(int $companyId, int $slotId, string $date): bool
    {
        return static::withoutGlobalScope('tenant')
            ->where('company_id', $companyId)
            ->where('slot_id', $slotId)
            ->where('appointment_date', $date)
            // Anything that is not cancelled holds the slot. Listing only
            // pending and confirmed freed a slot the moment it was marked
            // completed, letting the same slot on the same date be booked
            // twice. This also matches the active_booking_key index exactly.
            ->where('status', '<>', self::STATUS_CANCELLED)
            ->exists();
    }

    /**
     * All valid statuses for validation rules.
     */
    public static function getStatuses(): array
    {
        return array_keys(self::STATUS_LABELS);
    }
}