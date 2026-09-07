<?php

namespace App\Events\Crm;

use App\Models\CrmLead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ══════════════════════════════════════════════════════
 * WHAT IS AN EVENT?
 * ══════════════════════════════════════════════════════
 *
 * An Event is just a data container — a plain PHP class
 * that holds information about something that happened.
 *
 * event(new LeadStageChanged($lead, $oldId, $newId));
 *
 * Laravel's Event system then finds all LISTENERS
 * registered for this event and calls them one by one.
 *
 * WHY USE EVENTS instead of calling logic directly?
 *
 * Without events (tightly coupled):
 *   Observer calls NotifyUser, SendWhatsApp, UpdateDashboard
 *   Observer now knows about all those classes
 *   Hard to add new behavior later
 *
 * With events (loosely coupled):
 *   Observer fires: event(new LeadStageChanged(...))
 *   Observer is DONE — it doesn't know who listens
 *   New listener tomorrow? Just register it. Zero changes to Observer.
 *   Remove a listener? Zero changes to Observer.
 *
 * This is the Open/Closed Principle:
 *   Open for extension, closed for modification.
 */

// ════════════════════════════════════════════════════
//  EVENT 1 — LeadStageChanged
// ════════════════════════════════════════════════════

class LeadStageChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CrmLead $lead,
        public readonly int $fromStageId,
        public readonly int $toStageId,
    ) {}
}
