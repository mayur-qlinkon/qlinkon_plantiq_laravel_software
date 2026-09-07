<?php

namespace App\Models\Hrm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementAcknowledgement extends Model
{
    protected $fillable = [
        'announcement_id', 'user_id',
        'read_at', 'acknowledged_at', 'dismissed_at',
        'ip_address', 'user_agent',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    // ── Relationships ──

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Accessors ──

    public function getIsReadAttribute(): bool
    {
        return ! is_null($this->read_at);
    }

    public function getIsAcknowledgedAttribute(): bool
    {
        return ! is_null($this->acknowledged_at);
    }

    public function getIsDismissedAttribute(): bool
    {
        return ! is_null($this->dismissed_at);
    }
}
