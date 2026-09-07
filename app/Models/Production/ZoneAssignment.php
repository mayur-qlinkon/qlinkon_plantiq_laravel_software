<?php

namespace App\Models\Production;

use App\Models\Hrm\Employee;
use App\Models\User;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ZoneAssignment extends Model
{
    use SoftDeletes, Tenantable;

    protected $table = 'production_zone_assignments';

    protected $fillable = [
        'company_id',
        'employee_id',
        'zone_id',
        'assigned_by',
        'is_active',
        'assigned_at',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'assigned_at' => 'datetime',
    ];

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // ════════════════════════════════════════════════════
    //  SCOPES
    // ════════════════════════════════════════════════════

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeForZone(Builder $q, int $zoneId): Builder
    {
        return $q->where('zone_id', $zoneId);
    }

    public function scopeForEmployee(Builder $q, int $employeeId): Builder
    {
        return $q->where('employee_id', $employeeId);
    }
}