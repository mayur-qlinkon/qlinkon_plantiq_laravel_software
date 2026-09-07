<?php

namespace App\Models\Hrm;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class HrmTaskAttachment extends Model
{
    protected $table = 'hrm_task_attachments';

    protected $fillable = [
        'hrm_task_id', 'uploaded_by',
        'file_name', 'file_path', 'mime_type', 'file_size',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    // ── Scopes ──

    /**
     * Restrict to attachments whose parent task belongs to the current tenant.
     *
     * This table has no company_id of its own — an attachment's owner is
     * always whoever owns its task — so Tenantable cannot be used here and
     * the boundary has to be enforced through the parent.
     *
     * whereHas() on a Tenantable parent still needs an explicit company_id
     * check: the trait registers its global scope only when a user is
     * authenticated, so relying on it alone would leave this open on any
     * request where that is not true.
     */
    public function scopeOwned(Builder $query): Builder
    {
        $companyId = Auth::user()?->company_id;

        // No tenant context means no attachment is reachable. Failing closed
        // matters here because one of the callers deletes a file from disk.
        if (! $companyId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'task',
            fn (Builder $q) => $q->where('hrm_tasks.company_id', $companyId)
        );
    }

    // ── Relationships ──

    public function task(): BelongsTo
    {
        return $this->belongsTo(HrmTask::class, 'hrm_task_id');
    }

    public function uploadedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ── Accessors ──

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
    }
}
