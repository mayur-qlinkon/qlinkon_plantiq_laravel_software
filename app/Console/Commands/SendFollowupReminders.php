<?php

namespace App\Console\Commands;

use App\Models\CrmLead;
use App\Notifications\Crm\CrmFollowupReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendFollowupReminders extends Command
{
    protected $signature   = 'crm:send-followup-reminders';
    protected $description = 'Send notifications for CRM leads whose follow-up time has arrived';

    public function handle(): int
    {
        $count = 0;

        CrmLead::query()
            ->whereNotNull('next_followup_at')
            ->where('next_followup_at', '<=', now())   // due ya past
            ->whereNull('followup_reminded_at')         // abhi tak send nahi hua
            ->where('is_converted', false)              // converted leads skip
            ->with('assignees')
            ->chunkById(50, function ($leads) use (&$count) {
                foreach ($leads as $lead) {

                    // Koi assignee nahi → skip
                    if ($lead->assignees->isEmpty()) {
                        // Mark as reminded to avoid re-checking every minute
                        $lead->updateQuietly(['followup_reminded_at' => now()]);
                        continue;
                    }

                    try {
                        foreach ($lead->assignees as $assignee) {
                            $assignee->notify(new CrmFollowupReminderNotification($lead));
                        }

                        $lead->updateQuietly(['followup_reminded_at' => now()]);
                        $count++;

                    } catch (Throwable $e) {
                        Log::error('[CrmFollowup] Notification failed', [
                            'lead_id' => $lead->id,
                            'error'   => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("Follow-up reminders sent for {$count} lead(s).");

        return self::SUCCESS;
    }
}