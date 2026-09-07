<?php

namespace App\Listeners\Crm;

use App\Events\Crm\LeadLost;
use App\Models\CrmActivity;
use App\Models\CrmTask;
use Illuminate\Support\Facades\Log;

/**
 * MarkLeadLostCleanup
 *
 * NOT queued — fast DB operations only.
 *
 * What it does when a lead hits a "lost" stage:
 *   1. Cancels all pending tasks → no ghost reminders
 *   2. Clears next_followup_at → scheduler won't pick it up
 *   3. Logs the cleanup activity
 */
class MarkLeadLostCleanup
{
    public function handle(LeadLost $event): void
    {
        $lead = $event->lead;

        try {
            // ── Cancel all pending tasks ──
            $cancelledCount = CrmTask::where('crm_lead_id', $lead->id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->update([
                    'status' => 'cancelled',
                    'updated_at' => now(),
                ]);

            // ── Clear follow-up schedule ──
            // updateQuietly — skip Observer, no need to re-trigger
            $lead->updateQuietly([
                'next_followup_at' => null,
            ]);

            if ($cancelledCount > 0) {
                CrmActivity::logAuto(
                    leadId: $lead->id,
                    type: 'stage_change',
                    description: "Lead marked lost — {$cancelledCount} pending task(s) cancelled",
                    meta: ['cancelled_tasks' => $cancelledCount],
                    companyId: $lead->company_id
                );
            }

            Log::info('[CrmListener] Lead lost cleanup done', [
                'lead_id' => $lead->id,
                'tasks_cancelled' => $cancelledCount,
            ]);

        } catch (\Throwable $e) {
            Log::error('[CrmListener] MarkLeadLostCleanup failed', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
