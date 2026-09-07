<?php

namespace App\Listeners\Crm;

use App\Events\Crm\LeadAssigned;
use App\Notifications\Crm\CrmLeadAssignedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendLeadAssignedNotification
{

    public function handle(LeadAssigned $event): void
    {
        $assignedUser = $event->assignedTo;
        $lead         = $event->lead;

        // Don't notify if person assigned it to themselves
        if ($assignedUser->id === auth()->id()) {
            return;
        }

        try {
            $assignedUser->notify(new CrmLeadAssignedNotification($lead));

            Log::info('[CrmListener] Lead assigned notification sent', [
                'lead_id'     => $lead->id,
                'assigned_to' => $assignedUser->id,
                'reassigned'  => $event->previousUserId !== null,
            ]);
        } catch (\Throwable $e) {
            Log::error('[CrmListener] SendLeadAssignedNotification failed', [
                'lead_id' => $lead->id,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // Skip queue entirely if no valid user
    public function shouldQueue(LeadAssigned $event): bool
    {
        return $event->assignedTo !== null;
    }
}