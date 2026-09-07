<?php

namespace App\Listeners\Hrm;

use App\Enums\NotificationEvent;
use App\Events\Hrm\LeaveRequested;
use App\Mail\DynamicMail;
use App\Notifications\Hrm\LeaveRequestedNotification;
use App\Models\User;
use App\Services\NotificationDispatcher;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * Notifies whoever approves leave that a request has come in.
 *
 * The recipient lookup, the email-validity filter and the per-recipient send
 * loop all moved to NotificationDispatcher — the same code existed in the
 * order listener, and the two copies had already drifted apart.
 */
class SendLeaveNotification
{
    public function __construct(private NotificationDispatcher $dispatcher) {}

    public function handle(LeaveRequested $event): void
    {
        $leave = $event->leave;

        $employeeName = $leave->employee->full_name ?: 'Unknown Employee';

        $data = [
            'employeeName' => $employeeName,
            'leaveType' => $leave->leaveType->name ?? 'Leave',
            'fromDate' => $this->formatDate($leave->from_date),
            'toDate' => $this->formatDate($leave->to_date),
            'totalDays' => $leave->total_days,
            'reason' => $leave->reason ?: 'No reason provided',
            'actionUrl' => url('/admin/hrm/leaves/'.$leave->id),
        ];

        $this->dispatcher->dispatch(
            NotificationEvent::LeaveRequested,
            $leave->company_id,
            notification: new LeaveRequestedNotification($leave),
            mailable: new DynamicMail(
                "Leave request: {$employeeName}",
                'emails.leave-requested',
                $data,
            ),
            // Same reasoning as SendWorkLogNotification — a manager who applies
            // for their own leave must not be notified of their own request.
            excludeUserId: $leave->employee?->user_id,
            // Notify only people who could actually open this request. Reusing
            // the policy instead of re-implementing the hierarchy keeps the
            // feed and the 403 from drifting apart — every leaves.approve
            // holder was previously told about every request company-wide.
            recipientFilter: fn (User $user) => Gate::forUser($user)->allows('view', $leave),
        );
    }

    /** from_date/to_date arrive as either a Carbon instance or a raw string. */
    private function formatDate(mixed $date): string
    {
        return $date instanceof Carbon
            ? $date->format('d M Y')
            : Carbon::parse($date)->format('d M Y');
    }
}