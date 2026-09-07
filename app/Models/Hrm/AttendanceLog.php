<?php

namespace App\Models\Hrm;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    use Tenantable;

    const ACTION_CHECK_IN = 'check_in';

    const ACTION_CHECK_OUT = 'check_out';

    protected $fillable = [
        'company_id',
        'employee_id',
        'attendance_id',
        'action',
        'method',
        'punched_at',
        'latitude',
        'longitude',
        'accuracy_meters',
        'distance_meters',
        'device_info',
        'ip_address',
        'user_agent',
        'remarks',
        'is_valid',
        'rejection_reason',
    ];

    protected $casts = [
        'punched_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'accuracy_meters' => 'decimal:2',
        'distance_meters' => 'decimal:2',
        'is_valid' => 'boolean',
    ];

    // ── Relationships ──

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    // ── Scopes ──

    public function scopeValid(Builder $query): Builder
    {
        return $query->where('is_valid', true);
    }

    public function scopeForDate(Builder $query, $date): Builder
    {
        return $query->whereDate('punched_at', $date);
    }
}
