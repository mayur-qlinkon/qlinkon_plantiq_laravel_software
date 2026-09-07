<?php

namespace App\Models\Appointment;

use App\Models\Company;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppointmentSlot extends Model
{
    use Tenantable;

    protected $table = 'appointment_slots';

    protected $fillable = [
        'company_id',
        'slot_name',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'slot_id');
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('start_time');
    }

    // ── Accessors ──────────────────────────────────────────

    /**
     * e.g. "09:00 - 10:00" (strips seconds from DB TIME)
     */
    public function getTimeRangeAttribute(): string
    {
        $start = substr($this->start_time, 0, 5);
        $end   = substr($this->end_time, 0, 5);

        return "{$start} - {$end}";
    }

    /**
     * Display label — uses slot_name, falls back to time range
     */
    public function getDisplayLabelAttribute(): string
    {
        return $this->slot_name ?: $this->time_range;
    }
}