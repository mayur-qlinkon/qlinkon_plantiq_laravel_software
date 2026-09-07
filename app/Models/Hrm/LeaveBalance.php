<?php

namespace App\Models\Hrm;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use Tenantable;

    protected $fillable = [
        'company_id', 'employee_id', 'leave_type_id', 'year',
        'allocated', 'used', 'carried_forward', 'adjustment',
    ];

    protected $casts = [
        'year' => 'integer',
        'allocated' => 'decimal:1',
        'used' => 'decimal:1',
        'carried_forward' => 'decimal:1',
        'adjustment' => 'decimal:1',
    ];

    // ── Relationships ──

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    // ── Accessors ──

    public function getAvailableAttribute(): float
    {
        return round($this->allocated + $this->carried_forward + $this->adjustment - $this->used, 1);
    }
}
