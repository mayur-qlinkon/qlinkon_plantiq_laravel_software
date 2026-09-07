<?php

namespace App\Models\Appointment;

use App\Models\Company;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppointmentService extends Model
{
    use Tenantable;

    protected $table = 'appointment_services';

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'price',
        'duration_minutes',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price'            => 'decimal:2',
        'duration_minutes' => 'integer',
        'is_active'        => 'boolean',
        'sort_order'       => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'service_id');
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // ── Accessors ──────────────────────────────────────────

    /**
     * Human-readable duration e.g. "1 hr 30 min" or "45 min"
     */
    public function getDurationLabelAttribute(): string
    {
        if (! $this->duration_minutes) {
            return '—';
        }

        $hours   = intdiv($this->duration_minutes, 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours} hr {$minutes} min";
        }

        if ($hours > 0) {
            return "{$hours} hr";
        }

        return "{$minutes} min";
    }

    /**
     * Formatted price — "Free" if null or 0
     */
    public function getPriceLabelAttribute(): string
    {
        if (is_null($this->price) || $this->price == 0) {
            return 'Free';
        }

        return '₹' . number_format($this->price, 2);
    }
}