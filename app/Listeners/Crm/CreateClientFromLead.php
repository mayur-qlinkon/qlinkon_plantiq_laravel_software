<?php

namespace App\Listeners\Crm;

use App\Events\Crm\LeadConverted;
use App\Models\Client;
use App\Models\CrmActivity;
use Illuminate\Support\Facades\Log;

/**
 * CreateClientFromLead
 *
 * NOT queued — runs immediately.
 * Reason: if admin clicks "Convert", they expect the client
 * record to exist right away when the page refreshes.
 * This is fast (1-2 queries) so no need for background queue.
 *
 * What it does:
 *   1. Checks if client already exists by phone
 *   2. If not → creates Client from lead data
 *   3. Links lead.client_id back to the new client
 *   4. Logs the conversion activity
 */
class CreateClientFromLead
{
    public function handle(LeadConverted $event): void
    {
        $lead = $event->lead;

        // Skip if already linked to a client
        if ($lead->client_id) {
            Log::info('[CrmListener] Lead already has client_id — skipping create', [
                'lead_id' => $lead->id,
                'client_id' => $lead->client_id,
            ]);

            return;
        }

        try {
            // ── Check if client exists by phone ──
            // Prevents duplicate client records for same customer
            $existingClient = null;

            if ($lead->phone) {
                $existingClient = Client::where('company_id', $lead->company_id)
                    ->where('phone', $lead->phone)
                    ->first();
            }

            if ($existingClient) {
                // Client exists → just link them
                $lead->updateQuietly(['client_id' => $existingClient->id]);

                CrmActivity::logAuto(
                    leadId: $lead->id,
                    type: 'converted',
                    description: "Linked to existing client: {$existingClient->name}",
                    meta: ['client_id' => $existingClient->id, 'existing' => true],
                    companyId: $lead->company_id
                );

                Log::info('[CrmListener] Lead linked to existing client', [
                    'lead_id' => $lead->id,
                    'client_id' => $existingClient->id,
                ]);

                return;
            }

            // ── Create new Client from lead data ──
            $client = Client::create([
                'company_id' => $lead->company_id,
                'name' => $lead->name,
                'company_name' => $lead->company_name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'address' => $lead->address,
                'city' => $lead->city,
                'country' => $lead->country ?? 'India',
                'zip_code' => $lead->zip_code,
                'is_active' => true,
            ]);

            // Link client back to lead
            // updateQuietly — skip Observer to avoid re-triggering
            $lead->updateQuietly(['client_id' => $client->id]);

            CrmActivity::logAuto(
                leadId: $lead->id,
                type: 'converted',
                description: "New client created: {$client->name} (#{$client->id})",
                meta: ['client_id' => $client->id, 'existing' => false],
                companyId: $lead->company_id
            );

            Log::info('[CrmListener] New client created from lead', [
                'lead_id' => $lead->id,
                'client_id' => $client->id,
                'name' => $client->name,
            ]);

        } catch (\Throwable $e) {
            // Never crash — conversion already happened, client creation is secondary
            Log::error('[CrmListener] CreateClientFromLead failed', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
