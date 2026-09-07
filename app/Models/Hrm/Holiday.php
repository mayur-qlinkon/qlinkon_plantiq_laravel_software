<?php

namespace App\Models\Hrm;

use App\Models\User;
use App\Traits\Tenantable;
use App\Traits\SerializesLocalDates;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Holiday extends Model
{
    use  SoftDeletes, Tenantable, SerializesLocalDates;

    const TYPE_NATIONAL = 'national';

    const TYPE_STATE = 'state';

    const TYPE_COMPANY = 'company';

    const TYPE_RESTRICTED = 'restricted';

    const TYPE_OPTIONAL = 'optional';

    const TYPE_LABELS = [
        self::TYPE_NATIONAL => 'National',
        self::TYPE_STATE => 'State',
        self::TYPE_COMPANY => 'Company',
        self::TYPE_RESTRICTED => 'Restricted',
        self::TYPE_OPTIONAL => 'Optional',
    ];

    const TYPE_COLORS = [
        self::TYPE_NATIONAL => ['bg' => '#fef2f2', 'text' => '#991b1b', 'border' => '#fecaca'],
        self::TYPE_STATE => ['bg' => '#eff6ff', 'text' => '#1e40af', 'border' => '#bfdbfe'],
        self::TYPE_COMPANY => ['bg' => '#ecfdf5', 'text' => '#065f46', 'border' => '#a7f3d0'],
        self::TYPE_RESTRICTED => ['bg' => '#fffbeb', 'text' => '#92400e', 'border' => '#fde68a'],
        self::TYPE_OPTIONAL => ['bg' => '#f5f3ff', 'text' => '#5b21b6', 'border' => '#ddd6fe'],
    ];

    protected $fillable = [
        'company_id',
        'name',
        'date',
        'end_date',
        'type',
        'description',
        'is_paid',
        'is_recurring',
        'is_active',
        'applicable_departments',
        'applicable_stores',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'end_date' => 'date',
        'is_paid' => 'boolean',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
        'applicable_departments' => 'array',
        'applicable_stores' => 'array',
    ];
  

    // ── Relationships ──

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ──

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('date', '>=', today())->orderBy('date');
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->whereYear('date', $year);
    }

    public function scopeForDateRange(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    // ── Accessors ──

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function getTypeColorAttribute(): array
    {
        return self::TYPE_COLORS[$this->type] ?? ['bg' => '#f3f4f6', 'text' => '#374151', 'border' => '#e5e7eb'];
    }

    public function getTotalDaysAttribute(): int
    {
        if ($this->end_date) {
            return $this->date->diffInDays($this->end_date) + 1;
        }

        return 1;
    }
}
