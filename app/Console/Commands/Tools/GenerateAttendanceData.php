<?php

namespace App\Console\Commands\Tools;

use App\Models\Hrm\Attendance;
use App\Models\Hrm\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateAttendanceData extends Command
{
    protected $signature = 'generate:attendance
                            {--company= : Required company ID}
                            {--days=7 : Number of days (default 7)}';
    // php artisan generate:attendance --company=1 --days=30

    protected $description = 'Generate safe, idempotent attendance test data for a company.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $companyId = $this->resolvePositiveIntegerOption('company');
        $days = $this->resolvePositiveIntegerOption('days');

        if ($companyId === null) {
            $this->error('The --company option is required and must be a valid company ID.');

            return self::FAILURE;
        }

        if ($days === null) {
            $this->error('The --days option must be a positive integer.');

            return self::FAILURE;
        }

        $employees = Employee::query()
            ->where('company_id', $companyId)
            ->orderBy('id')
            ->get(['id', 'company_id', 'store_id']);

        if ($employees->isEmpty()) {
            $this->error("No employees found for company ID {$companyId}.");

            return self::FAILURE;
        }

        $startDate = today()->subDays($days - 1);

        $this->info("Generating attendance data for company ID {$companyId} for the last {$days} day(s).");
        $this->line("Employees to process: {$employees->count()}");

        $progressBar = $this->output->createProgressBar($employees->count());
        $progressBar->start();

        foreach ($employees as $employee) {
            for ($offset = 0; $offset < $days; $offset++) {
                $date = $startDate->copy()->addDays($offset);

                $attendance = Attendance::query()
                    ->withTrashed()
                    ->updateOrCreate(
                        [
                            'company_id' => $companyId,
                            'employee_id' => $employee->id,
                            'date' => $date->toDateString(),
                        ],
                        $this->buildAttendanceAttributes($employee, $date)
                    );

                if ($attendance->trashed()) {
                    $attendance->restore();
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->line("Total employees processed: {$employees->count()}");
        $this->info('Attendance data generation completed successfully.');

        return self::SUCCESS;
    }

    /**
     * @return array{
     *     store_id: int|null,
     *     check_in_time: string,
     *     check_out_time: string,
     *     worked_hours: float,
     *     overtime_hours: float,
     *     status: string,
     *     check_in_method: string,
     *     check_out_method: string
     * }
     */
    protected function buildAttendanceAttributes(Employee $employee, Carbon $date): array
    {
        $seed = $this->buildSeed($employee->id, $date);
        $checkIn = $date->copy()->setTime(9, 0)->addMinutes($seed % 46);
        $workedMinutes = 420 + (($seed >> 8) % 181);
        $checkOut = $checkIn->copy()->addMinutes($workedMinutes);
        $workedHours = round($workedMinutes / 60, 2);
        $overtimeHours = max(round(($workedMinutes - 480) / 60, 2), 0.0);

        // Sprinkle in some GPS-review test cases so the Approve/Reject UI has data to work with:
        // ~1 in 5 days gets a pending check-in review, ~1 in 7 gets a pending check-out review.
        $reviewRoll = $seed % 20;
        $checkInPending = $reviewRoll === 0;
        $checkOutPending = ! $checkInPending && ($seed % 14) === 0;

        return [
            'store_id' => $employee->store_id,
            'check_in_time' => $checkIn->toDateTimeString(),
            'check_out_time' => $checkOut->toDateTimeString(),
            'worked_hours' => $workedHours,
            'overtime_hours' => $overtimeHours,
            'status' => $workedHours < 5 ? Attendance::STATUS_HALF_DAY : Attendance::STATUS_PRESENT,
            'check_in_method' => Attendance::METHOD_QR,
            'check_out_method' => Attendance::METHOD_QR,
            'check_in_location_verified' => ! $checkInPending,
            'check_in_review_status' => $checkInPending ? 'pending' : null,
            'check_out_location_verified' => ! $checkOutPending,
            'check_out_review_status' => $checkOutPending ? 'pending' : null,
        ];
    }

    protected function buildSeed(int $employeeId, Carbon $date): int
    {
        return (int) sprintf('%u', crc32($employeeId.'|'.$date->toDateString()));
    }

    protected function resolvePositiveIntegerOption(string $option): ?int
    {
        $value = $this->option($option);

        if (! is_string($value) || $value === '') {
            return null;
        }

        $integer = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($integer === false) {
            return null;
        }

        return (int) $integer;
    }
}
