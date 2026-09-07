<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Todo extends Model
{
    use HasFactory, SoftDeletes,Tenantable;

    protected $fillable = [
        'company_id',
        'user_id',
        'title',
        'description',
        'priority',
        'status',
        'due_date',
        'completed_at',
        'sort_order',
        'is_important',
    ];

    protected $casts = [
        'due_date'     => 'date',
        'completed_at' => 'datetime',
        'is_important' => 'boolean',
        'sort_order'   => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────────
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────
    // Always scope to the current user — todos are strictly personal
    public function scopeMine($query)
    {
        return $query->where('user_id', Auth::id());                     
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeImportant($query)
    {
        return $query->where('is_important', true);
    }

    // ── Helpers ──────────────────────────────────────────────────────
    public function isOverdue(): bool
    {
        return $this->status === 'pending'
            && $this->due_date !== null
            && $this->due_date->isPast();
    }
}