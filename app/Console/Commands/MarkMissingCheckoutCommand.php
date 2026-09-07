<?php

namespace App\Console\Commands;

use App\Services\Hrm\AttendanceService;
use Illuminate\Console\Command;

class MarkMissingCheckoutCommand extends Command
{
    protected $signature = 'attendance:mark-missing-checkout';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */    

    protected $description = 'Identify and mark attendances where employees forgot to check out';

    /**
     * Execute the console command.
     */
    public function handle(AttendanceService $attendanceService): int
    {
        $this->info('Processing missing checkouts...');

        $processedCount = $attendanceService->handleMissingCheckouts();

        $this->info("Successfully updated {$processedCount} records with missing checkout status.");

        return Command::SUCCESS;
    }
}
