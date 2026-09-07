<?php

namespace App\Events\Crm;

use App\Models\CrmLead;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CrmLead $lead,
        public readonly User    $assignedTo,
        public readonly ?int    $previousUserId = null,  // null = new lead
    ) {}
}