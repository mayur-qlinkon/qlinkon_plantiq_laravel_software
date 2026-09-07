<?php

namespace App\Listeners\Hrm;

use App\Enums\NotificationEvent;
use App\Events\Hrm\WorkLogSubmitted;
use App\Mail\DynamicMail;
use App\Models\User;
use App\Notifications\Hrm\WorkLogSubmittedNotification;
use App\Services\NotificationDispatcher;
use Illuminate\Support\Facades\Gate;

/**
 * Notifies whoever approves work logs that one has come in.
 *
 * Mirrors SendLeaveNotification — recipient lookup, email-validity filtering
 * and the per-recipient send loop all live in NotificationDispatcher.
 */
class SendWorkLogNotification
{
    public function __construct(private NotificationDispatcher $dispatcher) {}

    public function handle(WorkLogSubmitted $event): void
    {
        $workLog = $event->workLog;

        $employeeName = $workLog->employee->full_name ?: 'Unknown Employee';

        $data = [
            'employeeName' => $employeeName,
            'logDate'      => $workLog->log_date->format('d M Y'),
            'hoursWorked'  => number_format($workLog->hours_worked, 1),
            'description'  => $workLog->description,
            'actionUrl'    => url('/admin/hrm/work-logs'),
        ];

        $this->dispatcher->dispatch(
            NotificationEvent::WorkLogSubmitted,
            $workLog->company_id,
            notification: new WorkLogSubmittedNotification($workLog),
            mailable: new DynamicMail(
                "Work log submitted: {$employeeName}",
                'emails.work-log-submitted',
                $data,
            ),
            // An approver who files their own log should not be told about it.
            excludeUserId: $workLog->employee?->user_id,
            // Notify only people who could actually open this log. Reusing the
            // policy instead of re-implementing the hierarchy keeps the feed and
            // the 403 aligned — every work_logs.approve holder was previously
            // told about every submission company-wide.
            recipientFilter: fn (User $user) => Gate::forUser($user)->allows('view', $workLog),
        );
    }
}