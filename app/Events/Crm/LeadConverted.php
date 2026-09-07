<?php

namespace App\Events\Crm;

use App\Models\CrmLead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// ════════════════════════════════════════════════════
//  EVENT 2 — LeadConverted
//  Fired when lead reaches a "won" stage
// ════════════════════════════════════════════════════

class LeadConverted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CrmLead $lead,
    ) {}
}
