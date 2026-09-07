<?php

namespace App\Notifications\Crm;

use App\Models\CrmLead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CrmLeadAssignedNotification extends Notification
{

    public function __construct(public CrmLead $lead) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'crm_lead_assigned',
            'title'   => 'CRM Lead Assigned',
            'message' => "You have been assigned a lead: {$this->lead->name}",
            'icon'    => 'user-check',
            'color'   => 'green',
            'link'    => route('admin.crm.leads.show', $this->lead->id),
        ];
    }
}