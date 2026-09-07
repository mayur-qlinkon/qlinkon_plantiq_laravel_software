<?php

namespace App\Events\Crm;

use App\Models\CrmLead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// ════════════════════════════════════════════════════
//  EVENT 3 — LeadLost
//  Fired when lead reaches a "lost" stage
// ════════════════════════════════════════════════════

class LeadLost
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CrmLead $lead,
    ) {}
}
