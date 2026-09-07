<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per "this lead was handed to this person" event.
 *
 * Never updated except to stamp unassigned_at. The report reads holding periods
 * from here, so editing history in place would silently rewrite past numbers.
 */
class CrmLeadAssignment extends Model
{
    use Tenantable;

    protected $fillable = [
        'company_id',
        'crm_lead_id',
        'user_id',
        'assigned_by',
        'is_primary',
        'assigned_at',
        'unassigned_at',
    ];

    protected $casts = [
        'is_primary'    => 'boolean',
        'assigned_at'   => 'datetime',
        'unassigned_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'crm_lead_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /** Still held — the row has not been closed by a reassignment. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('unassigned_at');
    }

    /**
     * Assignments that were live at any point inside the window.
     *
     * Deliberately overlap-based, not start-based: a lead handed over last month
     * and worked on all of this month belongs in this month's report.
     */
    public function scopeOverlapping(Builder $query, string $from, string $to): Builder
    {
        return $query
            ->where('assigned_at', '<=', $to)
            ->where(fn (Builder $q) => $q
                ->whereNull('unassigned_at')
                ->orWhere('unassigned_at', '>=', $from));
    }
}