<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Import extends Model
{
    use Tenantable;

    protected $fillable = [
        'user_id',
        'type',
        'file_path',
        'temp_path',
        'total_rows',
        'processed_rows',
        'success_rows',
        'created_rows',
        'updated_rows',
        'failed_rows',
        'skipped_rows',
        'limit_skipped_rows',
        'status',
        'duplicate_mode',
        'duplicate_meta',
        'import_mode',
        'is_dry_run',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'success_rows' => 'integer',
        'created_rows' => 'integer',
        'updated_rows' => 'integer',
        'failed_rows' => 'integer',
        'skipped_rows' => 'integer',
        'limit_skipped_rows' => 'integer',
        'is_dry_run' => 'boolean',
        'duplicate_meta' => 'array',
    ];

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }

    // ── Helpers ──

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function markProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function markFailed(): void
    {
        $this->update(['status' => 'failed']);
    }
}
