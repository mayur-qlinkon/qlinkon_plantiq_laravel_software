<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Announcement;
use App\Models\Hrm\AnnouncementAcknowledgement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AnnouncementService
{
    // ─────────────────────────────────────────────
    //  CRUD
    // ─────────────────────────────────────────────

    public function create(array $data): Announcement
    {
        $data['created_by'] = Auth::id();
        $data['last_updated_by'] = Auth::id();
        $data['status'] = $this->resolveInitialStatus($data);

        return Announcement::create($data);
    }

    public function update(Announcement $announcement, array $data): Announcement
    {
        $data['last_updated_by'] = Auth::id();

        // Recalculate status only for non-published announcements
        if ($announcement->is_editable) {
            $data['status'] = $this->resolveInitialStatus($data);
        }

        $announcement->update($data);

        return $announcement->fresh();
    }

    public function delete(Announcement $announcement): void
    {
        $this->deleteAttachment($announcement);
        $announcement->delete();
    }

    public function forceDelete(Announcement $announcement): void
    {
        $this->deleteAttachment($announcement);
        $announcement->forceDelete();
    }

    public function restore(int $id): Announcement
    {
        $announcement = Announcement::withTrashed()->findOrFail($id);
        $announcement->restore();

        return $announcement;
    }

    public function duplicate(Announcement $announcement): Announcement
    {
        $copy = $announcement->replicate();
        $copy->title = 'Copy of '.$announcement->title;
        $copy->status = Announcement::STATUS_DRAFT;
        $copy->publish_at = null;
        $copy->expire_at = null;
        $copy->published_at = null;
        $copy->published_by = null;
        $copy->is_pinned = false;
        $copy->attachment = null;
        $copy->attachment_name = null;
        $copy->created_by = Auth::id();
        $copy->last_updated_by = Auth::id();
        $copy->save();

        return $copy;
    }

    // ─────────────────────────────────────────────
    //  Status Management
    // ─────────────────────────────────────────────

    public function publish(Announcement $announcement): Announcement
    {
        abort_if(
            $announcement->status === Announcement::STATUS_PUBLISHED,
            422,
            'Announcement is already published.'
        );

        $announcement->update([
            'status' => Announcement::STATUS_PUBLISHED,
            'publish_at' => $announcement->publish_at ?? now(),
            'published_at' => now(),
            'published_by' => Auth::id(),
        ]);

        $this->clearAllPendingCaches($announcement);

        return $announcement->fresh();
    }

    public function unpublish(Announcement $announcement): Announcement
    {
        abort_if(
            $announcement->status !== Announcement::STATUS_PUBLISHED,
            422,
            'Only published announcements can be unpublished.'
        );

        $announcement->update([
            'status' => Announcement::STATUS_DRAFT,
            'published_at' => null,
            'published_by' => null,
        ]);

        return $announcement->fresh();
    }

    public function schedule(Announcement $announcement, Carbon $publishAt): Announcement
    {
        abort_if($publishAt->isPast(), 422, 'Scheduled time must be in the future.');

        abort_if(
            $announcement->status === Announcement::STATUS_PUBLISHED,
            422,
            'Unpublish the announcement before rescheduling.'
        );

        $announcement->update([
            'status' => Announcement::STATUS_SCHEDULED,
            'publish_at' => $publishAt,
        ]);

        return $announcement->fresh();
    }

    // ─────────────────────────────────────────────
    //  Employee: Read + Acknowledge
    // ─────────────────────────────────────────────

    public function markRead(
        Announcement $announcement,
        ?string $ip = null,
        ?string $userAgent = null
    ): AnnouncementAcknowledgement {
        $ack = AnnouncementAcknowledgement::firstOrNew([
            'announcement_id' => $announcement->id,
            'user_id' => Auth::id(),
        ]);

        if (! $ack->read_at) {
            $ack->read_at = now();
            $ack->ip_address = $ip;
            $ack->user_agent = $userAgent;
            $ack->save();
        }

        return $ack;
    }

    public function acknowledge(
        Announcement $announcement,
        ?string $ip = null,
        ?string $userAgent = null
    ): AnnouncementAcknowledgement {
        $ack = AnnouncementAcknowledgement::firstOrNew([
            'announcement_id' => $announcement->id,
            'user_id' => Auth::id(),
        ]);

        if (! $ack->acknowledged_at) {
            $ack->read_at = $ack->read_at ?? now();
            $ack->acknowledged_at = now();
            $ack->ip_address = $ip;
            $ack->user_agent = $userAgent;
            $ack->save();
        }

        self::clearPendingCache(Auth::id());

        return $ack;
    }

    /**
     * Dismiss a non-mandatory announcement (skip without acknowledging).
     */
    public function dismiss(
        Announcement $announcement,
        ?string $ip = null,
        ?string $userAgent = null
    ): AnnouncementAcknowledgement {
        $ack = AnnouncementAcknowledgement::firstOrNew([
            'announcement_id' => $announcement->id,
            'user_id' => Auth::id(),
        ]);

        if (! $ack->dismissed_at) {
            $ack->read_at = $ack->read_at ?? now();
            $ack->dismissed_at = now();
            $ack->ip_address = $ip;
            $ack->user_agent = $userAgent;
            $ack->save();
        }

        self::clearPendingCache(Auth::id());

        return $ack;
    }

    public function hasAcknowledged(Announcement $announcement, ?int $userId = null): bool
    {
        return AnnouncementAcknowledgement::where('announcement_id', $announcement->id)
            ->where('user_id', $userId ?? Auth::id())
            ->whereNotNull('acknowledged_at')
            ->exists();
    }

    public function hasRead(Announcement $announcement, ?int $userId = null): bool
    {
        return AnnouncementAcknowledgement::where('announcement_id', $announcement->id)
            ->where('user_id', $userId ?? Auth::id())
            ->whereNotNull('read_at')
            ->exists();
    }

    // ─────────────────────────────────────────────
    //  Pending (for middleware / employee popup)
    // ─────────────────────────────────────────────

    /**
     * Mandatory unacknowledged announcements (blocks navigation).
     */
    public function getPendingMandatory(User $user): Collection
    {
        return $this->getPendingQuery($user)
            ->where('requires_acknowledgement', true)
            ->get();
    }

    /**
     * All pending announcements (mandatory + non-mandatory, not dismissed/acknowledged).
     */
    public function getPendingForUser(User $user): Collection
    {
        return $this->getPendingQuery($user)->get();
    }

    /**
     * Quick boolean check using cache (for middleware — avoids DB per request).
     */
    public function hasPendingMandatory(User $user): bool
    {
        return Cache::remember(
            self::pendingCacheKey($user->id),
            now()->addMinutes(5),
            fn () => $this->getPendingMandatory($user)->isNotEmpty()
        );
    }

    public function hasPendingAnnouncements(User $user): bool
    {
        return $this->getPendingForUser($user)->isNotEmpty();
    }

    /**
     * Clear the cached pending-mandatory flag for a user.
     */
    public static function clearPendingCache(?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();
        Cache::forget(self::pendingCacheKey($userId));
    }

    private static function pendingCacheKey(int $userId): string
    {
        return "announcements:pending_mandatory:{$userId}";
    }

    /**
     * Base query: published, for audience, not acknowledged, not dismissed.
     * Excludes the creator and publisher — they should never see their own popup.
     */
    private function getPendingQuery(User $user): Builder
    {
        $handledIds = AnnouncementAcknowledgement::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNotNull('acknowledged_at')
                    ->orWhereNotNull('dismissed_at');
            })
            ->pluck('announcement_id');

        return Announcement::published()
            ->forAudience($user)
            ->whereNotIn('id', $handledIds)
            ->where('created_by', '!=', $user->id)
            ->where(function ($q) use ($user) {
                $q->whereNull('published_by')
                    ->orWhere('published_by', '!=', $user->id);
            })
            ->orderBy('requires_acknowledgement', 'desc')
            ->orderBy('is_pinned', 'desc')
            ->orderByRaw("FIELD(priority, 'critical', 'high', 'normal', 'low')")
            ->orderBy('publish_at', 'desc');
    }

    // ─────────────────────────────────────────────
    //  Queries
    // ─────────────────────────────────────────────

    /**
     * Admin list with filters.
     */
    public function getList(array $filters = []): LengthAwarePaginator
    {
        $query = Announcement::with(['createdByUser', 'publishedByUser'])
            ->withCount([
                'acknowledgements',
                'acknowledgements as read_count' => fn ($q) => $q->whereNotNull('read_at'),
                'acknowledgements as acknowledged_count' => fn ($q) => $q->whereNotNull('acknowledged_at'),
            ]);

        if (! empty($filters['search'])) {
            $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$filters['search']}%")
                ->orWhere('content', 'like', "%{$filters['search']}%")
            );
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['is_pinned'])) {
            $query->where('is_pinned', (bool) $filters['is_pinned']);
        }

        $allowed = ['title', 'created_at', 'publish_at', 'priority', 'status'];
        $sortBy = in_array($filters['sort_by'] ?? null, $allowed) ? $filters['sort_by'] : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)
            ->paginate($filters['per_page'] ?? 25)
            ->withQueryString();
    }

    /**
     * Employee feed — published + target-filtered.
     */
    public function getForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Announcement::published()
            ->forAudience($user)
            ->with('createdByUser')
            ->withCount([
                'acknowledgements as acknowledged_count' => fn ($q) => $q->whereNotNull('acknowledged_at'),
            ]);

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->orderBy('is_pinned', 'desc')
            ->orderBy('publish_at', 'desc')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();
    }

    // ─────────────────────────────────────────────
    //  Scheduler (called from Console/Kernel)
    // ─────────────────────────────────────────────

    /**
     * Auto-publish scheduled announcements whose publish_at has arrived.
     */
    public function syncScheduledToPublished(): int
    {
        return Announcement::where('status', Announcement::STATUS_SCHEDULED)
            ->whereNotNull('publish_at')
            ->where('publish_at', '<=', now())
            ->update([
                'status' => Announcement::STATUS_PUBLISHED,
                'published_at' => now(),
            ]);
    }

    /**
     * Auto-expire published announcements past their expire_at.
     */
    public function syncExpiredStatus(): int
    {
        return Announcement::where('status', Announcement::STATUS_PUBLISHED)
            ->whereNotNull('expire_at')
            ->where('expire_at', '<=', now())
            ->update(['status' => Announcement::STATUS_EXPIRED]);
    }

    // ─────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────

    private function resolveInitialStatus(array $data): string
    {
        if (empty($data['publish_at'])) {
            return Announcement::STATUS_DRAFT;
        }

        return Carbon::parse($data['publish_at'])->isFuture()
            ? Announcement::STATUS_SCHEDULED
            : Announcement::STATUS_DRAFT;
    }

    private function deleteAttachment(Announcement $announcement): void
    {
        if ($announcement->attachment) {
            Storage::disk('local')->delete($announcement->attachment);
        }
    }

    /**
     * When an announcement is published/unpublished, clear cache for all users
     * who might be affected. Uses targeted IDs if available, otherwise clears
     * for all company users.
     */
    private function clearAllPendingCaches(Announcement $announcement): void
    {
        $userIds = User::where('company_id', $announcement->company_id)
            ->pluck('id');

        foreach ($userIds as $userId) {
            self::clearPendingCache($userId);
        }
    }
}
