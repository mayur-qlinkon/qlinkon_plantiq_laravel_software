<?php

namespace App\Models\Hrm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmTaskAssignment extends Model
{
    protected $table = 'hrm_task_assignments';

    protected $fillable = [
        'hrm_task_id', 'employee_id', 'assigned_by',
        'assigned_at', 'is_primary',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'is_primary' => 'boolean',
    ];

    // ── Relationships ──

    public function task(): BelongsTo
    {
        return $this->belongsTo(HrmTask::class, 'hrm_task_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
