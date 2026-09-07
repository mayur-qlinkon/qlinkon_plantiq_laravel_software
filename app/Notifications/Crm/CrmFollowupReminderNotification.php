<?php

namespace App\Notifications\Crm;

use App\Models\CrmLead;
use Illuminate\Notifications\Notification;

class CrmFollowupReminderNotification extends Notification
{
    // NOTE: No Queueable — runs synchronously (shared hosting friendly)

    public function __construct(public CrmLead $lead) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $label   = $this->lead->followup_label
                    ? " · {$this->lead->followup_label}"
                    : '';
        $dateStr = $this->lead->next_followup_at
                    ? $this->lead->next_followup_at->format('d M Y, h:i A')
                    : '';

        return [
            'type'    => 'crm_followup_reminder',
            'title'   => 'Follow-up Reminder',
            'message' => "Follow up with \"{$this->lead->name}\"{$label} — {$dateStr}",
            'icon'    => 'calendar',
            'color'   => 'blue',
            'link'    => route('admin.crm.leads.show', $this->lead->id),
        ];
    }
}