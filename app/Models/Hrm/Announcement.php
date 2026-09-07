<?php

namespace App\Models\Hrm;

use App\Models\User;
use App\Models\Store;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
// use Spatie\Activitylog\LogOptions;
// use Spatie\Activitylog\Traits\LogsActivity;

class Announcement extends Model
{
    use  SoftDeletes, Tenantable;

    // ── Status ──
    const STATUS_DRAFT = 'draft';

    const STATUS_SCHEDULED = 'scheduled';

    const STATUS_PUBLISHED = 'published';

    const STATUS_EXPIRED = 'expired';

    // ── Type ──
    const TYPE_GENERAL = 'general';

    const TYPE_POLICY = 'policy';

    const TYPE_EVENT = 'event';

    const TYPE_HOLIDAY = 'holiday';

    const TYPE_URGENT = 'urgent';

    const TYPE_CELEBRATION = 'celebration';

    // ── Priority ──
    const PRIORITY_LOW = 'low';

    const PRIORITY_NORMAL = 'normal';

    const PRIORITY_HIGH = 'high';

    const PRIORITY_CRITICAL = 'critical';

    // ── Target ──
    const TARGET_ALL = 'all';

    const TARGET_DEPARTMENT = 'department';

    const TARGET_STORE = 'store';

    const TARGET_DESIGNATION = 'designation';

    const TARGET_ROLE = 'role';

    const TARGET_USERS = 'users';

    const TARGET_LABELS = [
        self::TARGET_ALL => 'All Members',
        self::TARGET_DEPARTMENT => 'Specific Departments',
        self::TARGET_STORE => 'Specific Stores',
        self::TARGET_DESIGNATION => 'Specific Designations',
        self::TARGET_ROLE => 'Specific Roles',
        self::TARGET_USERS => 'Specific Users',
    ];

    // ── Labels ──
    const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SCHEDULED => 'Scheduled',
        self::STATUS_PUBLISHED => 'Published',
        self::STATUS_EXPIRED => 'Expired',
    ];

    const TYPE_LABELS = [
        self::TYPE_GENERAL => 'General',
        self::TYPE_POLICY => 'Policy',
        self::TYPE_EVENT => 'Event',
        self::TYPE_HOLIDAY => 'Holiday',
        self::TYPE_URGENT => 'Urgent',
        self::TYPE_CELEBRATION => 'Celebration',
    ];

    const PRIORITY_LABELS = [
        self::PRIORITY_LOW => 'Low',
        self::PRIORITY_NORMAL => 'Normal',
        self::PRIORITY_HIGH => 'High',
        self::PRIORITY_CRITICAL => 'Critical',
    ];

    // ── Colors ──
    const STATUS_BADGE = [
        self::STATUS_DRAFT => 'secondary',
        self::STATUS_SCHEDULED => 'info',
        self::STATUS_PUBLISHED => 'success',
        self::STATUS_EXPIRED => 'danger',
    ];

    const TYPE_COLORS = [
        self::TYPE_GENERAL => ['bg' => '#f3f4f6', 'text' => '#374151'],
        self::TYPE_POLICY => ['bg' => '#eff6ff', 'text' => '#1e40af'],
        self::TYPE_EVENT => ['bg' => '#f0fdf4', 'text' => '#166534'],
        self::TYPE_HOLIDAY => ['bg' => '#fdf4ff', 'text' => '#86198f'],
        self::TYPE_URGENT => ['bg' => '#fef2f2', 'text' => '#991b1b'],
        self::TYPE_CELEBRATION => ['bg' => '#fffbeb', 'text' => '#92400e'],
    ];

    const PRIORITY_BADGE = [
        self::PRIORITY_LOW => 'secondary',
        self::PRIORITY_NORMAL => 'primary',
        self::PRIORITY_HIGH => 'warning',
        self::PRIORITY_CRITICAL => 'danger',
    ];

    // ── Fillable / Casts ──

    protected $fillable = [
        'created_by', 'published_by', 'last_updated_by',
        'title', 'content', 'type', 'priority', 'status',
        'target_audience', 'target_ids',
        'publish_at', 'expire_at', 'published_at',
        'requires_acknowledgement', 'is_pinned',
        'attachment', 'attachment_name',
    ];

    protected $casts = [
        'target_ids' => 'array',
        'publish_at' => 'datetime',
        'expire_at' => 'datetime',
        'published_at' => 'datetime',
        'requires_acknowledgement' => 'boolean',
        'is_pinned' => 'boolean',
    ];

    // ── Activity Log ──

    // public function getActivitylogOptions(): LogOptions
    // {
    //     return LogOptions::defaults()
    //         ->logOnly(['title', 'type', 'priority', 'status', 'target_audience', 'is_pinned'])
    //         ->logOnlyDirty()
    //         ->dontSubmitEmptyLogs()
    //         ->setDescriptionForEvent(fn (string $event) => "Announcement '{$this->title}' was {$event}");
    // }

    // ── Relationships ──

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function publishedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function lastUpdatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    public function acknowledgements(): HasMany
    {
        return $this->hasMany(AnnouncementAcknowledgement::class);
    }

    public function confirmedAcknowledgements(): HasMany
    {
        return $this->hasMany(AnnouncementAcknowledgement::class)
            ->whereNotNull('acknowledged_at');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementAcknowledgement::class)
            ->whereNotNull('read_at');
    }

    // ── Scopes ──

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    /**
     * Published + publish_at reached + not expired.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where(fn ($q) => $q->whereNull('publish_at')->orWhere('publish_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expire_at')->orWhere('expire_at', '>', now()));
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    public function scopeRequiresAcknowledgement(Builder $query): Builder
    {
        return $query->where('requires_acknowledgement', true);
    }

    /**
     * Filter announcements visible to a specific user based on target_audience.
     *
     * Store targeting uses the user's ALL assigned stores (store_user pivot),
     * NOT just the employee's single store or the active session store.
     * This ensures multi-store users receive announcements for any of their stores.
     *
     * Department/designation resolved from employee profile.
     * Roles resolved from role_user pivot. Direct user targeting by user ID.
     */
    public function scopeForAudience(Builder $query, User $user): Builder
    {
        $employee = $user->employee;
        $roleIds = $user->roles()->pluck('roles.id')->toArray();

        // Collect ALL store IDs the user is assigned to (pivot + employee fallback)
        $storeIds = $user->stores()->pluck('stores.id')
            ->map(fn ($id) => (string) $id)
            ->toArray();

        // The company admin is often attached to no store at all, which would
        // hide every store-targeted announcement from the person who sent it.
        if ($user->isCompanyAdmin()) {
            $storeIds = Store::where('company_id', $user->company_id)
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->toArray();
        }        
        return $query->where(function ($q) use ($user, $employee, $roleIds, $storeIds) {
            $q->where('target_audience', self::TARGET_ALL);

            // Direct user targeting
            $q->orWhere(function ($sub) use ($user) {
                $sub->where('target_audience', self::TARGET_USERS)
                    ->whereJsonContains('target_ids', (string) $user->id);
            });

            // Role targeting
            if (! empty($roleIds)) {
                foreach ($roleIds as $roleId) {
                    $q->orWhere(function ($sub) use ($roleId) {
                        $sub->where('target_audience', self::TARGET_ROLE)
                            ->whereJsonContains('target_ids', (string) $roleId);
                    });
                }
            }

            // Store targeting — matches ANY of the user's assigned stores
            if (! empty($storeIds)) {
                foreach ($storeIds as $storeId) {
                    $q->orWhere(function ($sub) use ($storeId) {
                        $sub->where('target_audience', self::TARGET_STORE)
                            ->whereJsonContains('target_ids', $storeId);
                    });
                }
            }

            // Employee profile targeting (department + designation)
            if ($employee) {
                if ($employee->department_id) {
                    $q->orWhere(function ($sub) use ($employee) {
                        $sub->where('target_audience', self::TARGET_DEPARTMENT)
                            ->whereJsonContains('target_ids', (string) $employee->department_id);
                    });
                }
                if ($employee->designation_id) {
                    $q->orWhere(function ($sub) use ($employee) {
                        $sub->where('target_audience', self::TARGET_DESIGNATION)
                            ->whereJsonContains('target_ids', (string) $employee->designation_id);
                    });
                }
            }
        });
    }

    // ── Accessors ──

    public function getIsDraftAttribute(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function getIsScheduledAttribute(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->expire_at && $this->expire_at->isPast());
    }

    /**
     * Draft and Scheduled can be freely edited.
     * Published requires special permission.
     */
    public function getIsEditableAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED]);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? ucfirst($this->type);
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITY_LABELS[$this->priority] ?? ucfirst($this->priority);
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::STATUS_BADGE[$this->status] ?? 'secondary';
    }

    public function getPriorityBadgeAttribute(): string
    {
        return self::PRIORITY_BADGE[$this->priority] ?? 'secondary';
    }

    public function getTypeColorAttribute(): array
    {
        return self::TYPE_COLORS[$this->type] ?? [];
    }

    /**
     * Announcement attachments live on the private disk, so there is no public
     * URL to hand out — this points at the authorised download action instead.
     * The route checks the 'view' policy before streaming the file.
     */
    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment
            ? route('admin.hrm.announcements.download', $this)
            : null;
    }
}
