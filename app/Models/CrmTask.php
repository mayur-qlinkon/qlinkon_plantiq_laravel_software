<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmTask extends Model
{
    use SoftDeletes, Tenantable;

    protected $fillable = [
        'company_id',        
        'crm_lead_id',
        'assigned_to',
        'created_by',
        'title',
        'description',
        'type',
        'status',
        'priority',
        'due_at',
        'completed_at',
        'completion_note',
        'reminder_sent',
        'remind_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
        'remind_at' => 'datetime',
        'reminder_sent' => 'boolean',
    ];

    public const TYPES = [
        'follow_up' => 'Follow Up',
        'call' => 'Call',
        'meeting' => 'Meeting',
        'whatsapp' => 'WhatsApp',
        'email' => 'Email',
        'demo' => 'Demo',
        'other' => 'Other',
    ];

    public const STATUSES = [
        'pending' => ['label' => 'Pending',     'color' => '#eab308'],
        'in_progress' => ['label' => 'In Progress', 'color' => '#3b82f6'],
        'completed' => ['label' => 'Completed',   'color' => '#22c55e'],
        'cancelled' => ['label' => 'Cancelled',   'color' => '#6b7280'],
        'overdue' => ['label' => 'Overdue',     'color' => '#ef4444'],
    ];

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'crm_lead_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ════════════════════════════════════════════════════
    //  SCOPES
    // ════════════════════════════════════════════════════

    public function scopePending(Builder $q): Builder
    {
        return $q->whereIn('status', ['pending', 'in_progress']);
    }

    public function scopeCompleted(Builder $q): Builder
    {
        return $q->where('status', 'completed');
    }

    public function scopeOverdue(Builder $q): Builder
    {
        return $q->where('due_at', '<', now())
            ->whereIn('status', ['pending', 'in_progress']);
    }

    public function scopeDueToday(Builder $q): Builder
    {
        return $q->whereDate('due_at', today())
            ->whereIn('status', ['pending', 'in_progress']);
    }

    public function scopeForUser(Builder $q, int $userId): Builder
    {
        return $q->where('assigned_to', $userId);
    }

    // ── For scheduler: tasks needing WhatsApp reminder ──
    public function scopeNeedsReminder(Builder $q): Builder
    {
        return $q->where('reminder_sent', false)
            ->whereNotNull('remind_at')
            ->where('remind_at', '<=', now())
            ->whereIn('status', ['pending', 'in_progress']);
    }

    // ════════════════════════════════════════════════════
    //  ACCESSORS
    // ════════════════════════════════════════════════════

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_at
            && $this->due_at->isPast()
            && in_array($this->status, ['pending', 'in_progress']);
    }

    public function getStatusInfoAttribute(): array
    {
        return self::STATUSES[$this->status] ?? ['label' => ucfirst($this->status), 'color' => '#6b7280'];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status]['label'] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUSES[$this->status]['color'] ?? '#6b7280';
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    // ════════════════════════════════════════════════════
    //  HELPERS
    // ════════════════════════════════════════════════════

    public function complete(string $note = ''): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completion_note' => $note,
        ]);
    }

    public function markOverdue(): void
    {
        $this->update(['status' => 'overdue']);
    }

    // ════════════════════════════════════════════════════
    //  STATIC STATS
    // ════════════════════════════════════════════════════

    public static function getStatsForUser(int $userId): array
    {
        $base = static::where('assigned_to', $userId);

        return [
            'due_today' => (clone $base)->dueToday()->count(),
            'overdue' => (clone $base)->overdue()->count(),
            'pending' => (clone $base)->pending()->count(),
        ];
    }
}
