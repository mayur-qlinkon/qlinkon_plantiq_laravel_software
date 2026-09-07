<?php

namespace App\Services;

use App\Enums\Auth\UserType;
use App\Enums\NotificationEvent;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\Platform\EmailService;
use Closure;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Resolves who should hear about a broadcast event, and over which channels.
 *
 * Replaces the recipient logic that was copy-pasted into each listener — and
 * had already drifted apart between them, with one validating email addresses
 * and the other not.
 *
 * Safe to call from cron: nothing here depends on an authenticated user.
 */
class NotificationDispatcher
{
    public function __construct(private EmailService $emailService) {}

    /**
     * Send an event to its configured recipients.
     *
     * @param  BaseNotification|null  $notification  In-app payload; omit for mail-only events.
     * @param  Mailable|null  $mailable  Email payload; omit for in-app-only events.
     * @param  int|null  $excludeUserId  The person who triggered the event.
     * @param  Closure|null  $recipientFilter  Per-recipient gate, fn (User): bool.
     *
     * Passed explicitly rather than read from Auth: this class is documented as
     * safe to call from cron, where there is no authenticated user, and reading
     * the session here would quietly break that guarantee.
     *
     * Without it, anyone who both holds the approving permission and filed the
     * request gets notified about their own action — which is always true for
     * a company admin, since usersWithPermission() includes them unconditionally.
     */
    public function dispatch(
        NotificationEvent $event,
        int $companyId,
        ?BaseNotification $notification = null,
        ?Mailable $mailable = null,
        ?int $excludeUserId = null,
        ?Closure $recipientFilter = null,
    ): void {
        $recipients = $this->resolveRecipients($event, $companyId);

        if ($excludeUserId !== null) {
            $recipients = $recipients->forget($excludeUserId);
        }

        // Applied after the permission lookup so a listener can reuse a policy
        // instead of re-implementing hierarchy. Keeps the notification feed and
        // the 403 from drifting apart — the permission row alone is company-wide.
        if ($recipientFilter !== null) {
            $recipients = $recipients->filter(
                fn (array $r) => (bool) $recipientFilter($r['user'])
            );
        }

        if ($recipients->isEmpty()) {
            Log::info('[Notifications] No recipients configured', [
                'event' => $event->value,
                'company_id' => $companyId,
            ]);

            return;
        }

        if ($notification !== null) {
            $inApp = $recipients
                ->filter(fn (array $r) => in_array(NotificationPreference::CHANNEL_DATABASE, $r['channels'], true))
                ->pluck('user');

            if ($inApp->isNotEmpty()) {
                Notification::send($inApp, $notification);
            }
        }

        if ($mailable !== null) {
            $byMail = $recipients
                ->filter(fn (array $r) => in_array(NotificationPreference::CHANNEL_MAIL, $r['channels'], true))
                ->pluck('user')
                // A user with no valid address would otherwise fail on every send.
                ->filter(fn (User $u) => filter_var($u->email, FILTER_VALIDATE_EMAIL) !== false);

            foreach ($byMail as $user) {
                $this->emailService->sendMailable($mailable, $user->email, $user->name);
            }
        }
    }

    /**
     * Build the final recipient list as [user_id => ['user' => User, 'channels' => [...]]].
     *
     * Explicit user rows are applied last so they override the permission row:
     * that is how a tenant switches email off for one person who is otherwise
     * part of a permission group, which the settings screen implies.
     *
     * @return Collection<int, array{user: User, channels: list<string>}>
     */
    private function resolveRecipients(NotificationEvent $event, int $companyId): Collection
    {
        $rows = NotificationPreference::where('company_id', $companyId)
            ->where('event', $event->value)
            ->get();

        // Never configured — fall back to the event's own default so existing
        // tenants keep working with nothing seeded.
        if ($rows->isEmpty()) {
            $rows = collect([
                new NotificationPreference([
                    'recipient_type' => NotificationPreference::TYPE_PERMISSION,
                    'recipient_value' => $event->defaultPermission(),
                    'channels' => $event->defaultChannels(),
                ]),
            ]);
        }

        $resolved = [];

        // 1. Permission rows first — the broad stroke.
        foreach ($rows->filter->isPermissionRow() as $row) {
            foreach ($this->usersWithPermission($companyId, $row->recipient_value) as $user) {
                $resolved[$user->id] = ['user' => $user, 'channels' => $row->channels ?? []];
            }
        }

        // 2. Explicit user rows second — these win.
        $userRows = $rows->reject->isPermissionRow();

        if ($userRows->isNotEmpty()) {
            // withoutGlobalScope('tenant') and not withoutGlobalScopes(): the
            // latter drops SoftDeletingScope too, which kept notifying staff
            // who had been removed from the company. company_id is applied
            // explicitly below because this runs from cron with no auth user.
            $users = User::withoutGlobalScope('tenant')
                ->where('company_id', $companyId)
                ->where('status', 'active')
                ->whereIn('id', $userRows->pluck('recipient_value')->map('intval'))
                ->get()
                ->keyBy('id');

            foreach ($userRows as $row) {
                $user = $users->get((int) $row->recipient_value);

                if ($user) {
                    $resolved[$user->id] = ['user' => $user, 'channels' => $row->channels ?? []];
                }
            }
        }

        // Drop anyone left with no channels at all — that is how the UI
        // expresses "do not notify this person".
        return collect($resolved)->filter(fn (array $r) => ! empty($r['channels']));
    }

    /**
     * Active users in the company who effectively hold a permission.
     *
     * Company admins are included unconditionally, mirroring has_permission(),
     * which grants them everything regardless of their role rows. Leaving them
     * out would silently stop notifying the owner.
     *
     * @return Collection<int, User>
     */
    private function usersWithPermission(int $companyId, string $permissionSlug): Collection
    {
        // Only the tenant scope is lifted — a soft-deleted user must not be
        // resolved as a recipient. See the note in resolveRecipients().
        return User::withoutGlobalScope('tenant')
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->where(function ($query) use ($permissionSlug) {
                $query->where('user_type', UserType::COMPANY_ADMIN)
                    ->orWhereHas('roles.permissions', fn ($q) => $q->where('slug', $permissionSlug));
            })
            ->get();
    }
}