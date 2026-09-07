<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Production\ActivityTemplate;
use App\Models\Production\BatchPlacement;
use App\Services\Production\ActivityTemplateService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateDailyProductionTasksCommand extends Command
{
    protected $signature = 'production:generate-daily-tasks
                            {--date= : Date to generate for (Y-m-d). Default: today}
                            {--dry-run : Preview without saving}
                            {--verbose-check : Print per-template due/not-due reasoning}';

    protected $description = 'Generate today\'s recurring production tasks (watering, fertilizer, etc.) for every currently-placed plant batch';

    public function handle(ActivityTemplateService $templateService): int
    {
        $targetDate = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        $dryRun = $this->option('dry-run');

        $this->info("Generating production tasks for: {$targetDate->toDateString()}" . ($dryRun ? ' [DRY RUN]' : ''));

        $totalCreated = 0;
        $totalSkipped = 0;

        // NOTE: Cron runs without Auth, Tenantable global scope NOT applied.
        // Har query manually company_id se scope karni hai.
        $companies = Company::where('is_active', true)->get();

        foreach ($companies as $company) {
            [$created, $skipped] = $this->processCompany($company->id, $targetDate, $dryRun, $templateService);
            $totalCreated += $created;
            $totalSkipped += $skipped;
        }

        $this->info("Done. Tasks created: {$totalCreated} | Not due today: {$totalSkipped}");

        return self::SUCCESS;
    }

    private function processCompany(int $companyId, Carbon $date, bool $dryRun, ActivityTemplateService $templateService): array
    {
        $created = 0;
        $skipped = 0;

        // Sirf wahi batches jo AAJ ki tareekh mein kisi zone mein actively placed hain.
        // Unplaced/planned batches ko koi employee dekh hi nahi sakta, isliye unke liye task banane ka koi matlab nahi.
        $activePlacements = BatchPlacement::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->active()
            ->with(['batch', 'growingSpace'])
            ->get();

        foreach ($activePlacements as $placement) {
            $batch = $placement->batch;

            if (! $batch || ! $batch->isActive()) {
                continue;
            }

            $zoneId = $placement->growingSpace?->zone_id;
            $placedAt = Carbon::parse($placement->placed_at)->startOfDay();

            // Batch abhi place hua hai, target date se aage — is date ke liye kuch due nahi
            if ($placedAt->gt($date->copy()->startOfDay())) {
                continue;
            }

            $ageDays = $placedAt->diffInDays($date->copy()->startOfDay());

            $templates = $templateService->resolveForBatch($batch);

            foreach ($templates as $template) {
                if ($ageDays < $template->start_after_days) {
                    $skipped++;
                    continue;
                }

                $daysSinceStart = $ageDays - $template->start_after_days;
                $intervalDays = $this->resolveIntervalDays($template->frequency_type, $template->frequency_value);

                $isDueToday = $intervalDays > 0 && $daysSinceStart % $intervalDays === 0;

                if ($this->option('verbose-check')) {
                    $this->line(sprintf(
                        '  [CHECK] Batch #%d | %s | ageDays=%d startAfter=%d daysSinceStart=%d interval=%d → %s',
                        $batch->id,
                        $template->activity_type->value,
                        $ageDays,
                        $template->start_after_days,
                        $daysSinceStart,
                        $intervalDays,
                        $isDueToday ? 'DUE' : 'not due'
                    ));
                }

                if (! $isDueToday) {
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $this->line("  [WOULD CREATE] Batch #{$batch->id} — {$template->activity_type->label()} — {$date->toDateString()}");
                    $created++;
                    continue;
                }

                $this->createTaskSafely($companyId, $batch->id, $placement->id, $zoneId, $template, $date);
                $created++;
            }
        }

        return [$created, $skipped];
    }

    // frequency_type = unit, frequency_value = multiplier.
    // Jaise: weekly + value=1  -> har 7 din
    //        weekly + value=2  -> har 14 din ("every 2 weeks")
    //        daily  + value=1  -> har din
    //        interval + value=3 -> har 3 din (raw day-count, V1 fallback naming)
    private function resolveIntervalDays(string $frequencyType, int $frequencyValue): int
    {
        $multiplier = max(1, $frequencyValue); // 0/negative galti se aa jaaye to bhi crash na ho

        return match ($frequencyType) {
            'daily'   => 1 * $multiplier,
            'weekly'  => 7 * $multiplier,
            'monthly' => 30 * $multiplier,
            default   => $multiplier, // 'interval' — value hi raw din hai
        };
    }

    private function createTaskSafely(
        int $companyId,
        int $batchId,
        int $placementId,
        ?int $zoneId,
        ActivityTemplate $template,
        Carbon $date
    ): void {
        try {
            // insertOrIgnore — DB-level unique constraint (pdt_unique_task) bachaata hai
            // agar command galti se dobara chal jaaye usi din ke liye.
            DB::table('production_daily_tasks')->insertOrIgnore([
                'company_id' => $companyId,
                'plant_batch_id' => $batchId,
                'batch_placement_id' => $placementId,
                'zone_id' => $zoneId,
                'activity_template_id' => $template->id,
                'activity_type' => $template->activity_type->value,
                'is_required' => $template->is_required,
                'due_date' => $date->toDateString(),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[GenerateDailyProductionTasks] Failed to insert', [
                'batch_id' => $batchId,
                'template_id' => $template->id,
                'date' => $date->toDateString(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}