<?php

// ════════════════════════════════════════════════════════════════
//  FILE 1: app/Listeners/Crm/LogStageChangeNotification.php
// ════════════════════════════════════════════════════════════════

namespace App\Listeners\Crm;

use App\Events\Crm\LeadStageChanged;
use App\Models\CrmStage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Sends a database notification to the assigned user
 * when a lead moves to a new stage.
 *
 * ShouldQueue → runs in background, user never waits.
 *
 * WHY database notification instead of WhatsApp here?
 * Database notifications show up in your admin panel bell icon.
 * WhatsApp is a separate concern — add that listener later
 * without touching this class at all.
 */
class LogStageChangeNotification implements ShouldQueue
{
    // If the queue job fails, retry up to 3 times
    public int $tries = 3;

    // Wait 30 seconds before retrying after failure
    public int $backoff = 30;

    public function handle(LeadStageChanged $event): void
    {
        $lead = $event->lead;
        $toStageId = $event->toStageId;

        try {
            $newStage = CrmStage::find($toStageId);

            // Get assigned user IDs from pivot
            $assigneeIds = $lead->assignees()->pluck('users.id')->toArray();

            if (empty($assigneeIds)) {
                Log::info('[CrmListener] No assignees for stage change notification', [
                    'lead_id' => $lead->id,
                ]);

                return;
            }

            foreach ($assigneeIds as $userId) {
                DB::table('notifications')->insert([
                    'id' => Str::uuid(),
                    'type' => 'crm_stage_change',
                    'notifiable_type' => 'App\Models\User',
                    'notifiable_id' => $userId,
                    'data' => json_encode([
                        'title' => "Lead Stage Changed",
                        'lead_id' => $lead->id,
                        'lead_name' => $lead->name,
                        'stage_name' => $newStage?->name,
                        'message' => "Lead \"{$lead->name}\" moved to stage \"{$newStage?->name}\"",
                        'link' =>url("/admin/crm/leads/{$lead->id}"),
                        
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Log::info('[CrmListener] Stage change notification sent', [
                'lead_id' => $lead->id,
                'to_stage' => $newStage?->name,
                'notified' => count($assigneeIds),
            ]);

        } catch (\Throwable $e) {
            Log::error('[CrmListener] LogStageChangeNotification failed', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
            throw $e; // re-throw so ShouldQueue can retry
        }
    }

    /**
     * Decide whether to queue this job at all.
     * Skip notification if lead has no assignees.
     */
    public function shouldQueue(LeadStageChanged $event): bool
    {
        return $event->lead->assignees()->exists();
    }
}
