<?php

namespace Database\Seeders\HRM;

use App\Models\Hrm\Attendance;
use App\Models\Hrm\Employee;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Generates realistic attendance records for the current company.
 *
 * Coverage:
 *  - Jan 1 of the current year → today  (tests Today / Week / Month / Year exports)
 *  - All 7 status types used: present, late, absent, half_day, on_leave, holiday, week_off
 *  - Realistic check-in/check-out times, worked hours, overtime
 *  - Indian public holidays included
 *  - Safe to re-run (updateOrInsert on company_id + employee_id + date)
 */
class AttendanceSeeder extends Seeder
{
    // ── Indian public holidays (MM-DD format, year-independent) ──────────────

    private const HOLIDAYS = [
        '01-26', // Republic Day
        '03-29', // Holi (approx)
        '04-14', // Ambedkar Jayanti / Tamil New Year
        '08-15', // Independence Day
        '10-02', // Gandhi Jayanti
        '11-01', // Diwali (approx)
        '12-25', // Christmas
    ];

    // ── Status distribution weights per employee slot ─────────────────────────
    // Keyed by employee index (0-based). Different employees have different patterns.

    private const PATTERNS = [
        // [present, late, absent, half_day, on_leave]  — weights add to 100
        0 => [77, 12, 5, 3, 3],   // EMP-0001: reliable, occasional late
        1 => [70, 18, 6, 3, 3],   // EMP-0002: a bit more late
        2 => [82,  8, 4, 3, 3],   // EMP-AKASH: very regular
    ];

    public function run(): void
    {
        $companyId = request()->attributes->get('seeder_company_id', 1);

        $employees = Employee::where('company_id', $companyId)
            ->orderBy('id')
            ->get();

        if ($employees->isEmpty()) {
            $this->command?->warn('No employees found for company ID '.$companyId.'. Skipping.');

            return;
        }

        $start = Carbon::create(now()->year, 1, 1)->startOfDay();
        $end = now()->startOfDay();
        $total = 0;

        DB::beginTransaction();

        try {
            foreach ($employees as $empIndex => $employee) {
                $pattern = self::PATTERNS[$empIndex] ?? self::PATTERNS[0];
                $cursor = $start->copy();

                while ($cursor->lte($end)) {
                    $record = $this->buildRecord($companyId, $employee, $cursor->copy(), $empIndex, $pattern);

                    DB::table('attendances')->updateOrInsert(
                        [
                            'company_id' => $companyId,
                            'employee_id' => $employee->id,
                            'date' => $record['date'],
                        ],
                        array_merge($record, [
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])
                    );

                    $total++;
                    $cursor->addDay();
                }
            }

            DB::commit();

            $this->command?->info("✅ Attendance seeded: {$total} records for ".$employees->count()." employees (Company ID: {$companyId})");

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->command?->error('❌ Attendance seeding failed: '.$e->getMessage());
            throw $e;
        }
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  Record builder
    // ═════════════════════════════════════════════════════════════════════════

    private function buildRecord(int $companyId, Employee $employee, Carbon $date, int $empIndex, array $pattern): array
    {
        $base = [
            'company_id' => $companyId,
            'employee_id' => $employee->id,
            'store_id' => $employee->store_id,
            'date' => $date->toDateString(),
            'check_in_time' => null,
            'check_in_method' => null,
            'check_out_time' => null,
            'check_out_method' => null,
            'worked_hours' => null,
            'overtime_hours' => 0.00,
            'is_overridden' => false,
            'overridden_by' => null,
            'override_reason' => null,
            'notes' => null,
        ];

        // ── Sunday → week_off ────────────────────────────────────────────────
        if ($date->isSunday()) {
            return array_merge($base, ['status' => 'week_off']);
        }

        // ── Public holiday ───────────────────────────────────────────────────
        if (in_array($date->format('m-d'), self::HOLIDAYS, true)) {
            return array_merge($base, ['status' => 'holiday', 'notes' => 'Public Holiday']);
        }

        // ── Determine status deterministically ───────────────────────────────
        // Seed based on date + employee so reruns produce the same data.
        $seed = ($date->dayOfYear * 7 + $empIndex * 31 + $date->year) % 100;
        $status = $this->resolveStatus($seed, $pattern);

        return match ($status) {
            'present' => $this->presentRecord($base, $date, $empIndex, false),
            'late' => $this->presentRecord($base, $date, $empIndex, true),
            'half_day' => $this->halfDayRecord($base, $date),
            'absent' => array_merge($base, ['status' => 'absent']),
            'on_leave' => array_merge($base, ['status' => 'on_leave', 'notes' => 'Approved Leave']),
            default => array_merge($base, ['status' => 'present']),
        };
    }

    /** Map a 0-99 seed value to a status string using the employee's weight pattern. */
    private function resolveStatus(int $seed, array $pattern): string
    {
        [$present, $late, $absent, $halfDay, $onLeave] = $pattern;

        if ($seed < $present) {
            return 'present';
        }
        if ($seed < $present + $late) {
            return 'late';
        }
        if ($seed < $present + $late + $absent) {
            return 'absent';
        }
        if ($seed < $present + $late + $absent + $halfDay) {
            return 'half_day';
        }

        return 'on_leave';
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  Time builders
    // ═════════════════════════════════════════════════════════════════════════

    private function presentRecord(array $base, Carbon $date, int $empIndex, bool $isLate): array
    {
        $methods = ['qr', 'qr', 'qr', 'manual']; // 75% QR

        if ($isLate) {
            // Late: check-in between 09:31 and 11:00
            $lateMinutes = 31 + ($empIndex * 17 + $date->day * 3) % 89;
            $checkIn = $date->copy()->setTime(9, 0)->addMinutes($lateMinutes);
        } else {
            // On time: check-in between 08:45 and 09:15
            $earlyMinutes = ($empIndex * 11 + $date->day * 7) % 31;
            $checkIn = $date->copy()->setTime(8, 45)->addMinutes($earlyMinutes);
        }

        // Check-out: 17:30 – 19:30 (some days overtime)
        $checkOutMinutes = 450 + ($empIndex * 13 + $date->dayOfYear * 5) % 120; // 7.5h–9.5h after base 10:00
        $checkOut = $date->copy()->setTime(17, 30)->addMinutes(
            ($empIndex * 19 + $date->day * 11) % 90
        );

        // Occasionally add overtime (roughly 1 in 5 present days)
        $hasOvertime = (($empIndex * 7 + $date->dayOfYear) % 5) === 0;
        if ($hasOvertime) {
            $checkOut->addMinutes(60 + ($date->day % 60));
        }

        $workedMinutes = (int) $checkIn->diffInMinutes($checkOut);
        $workedHours = round($workedMinutes / 60, 2);
        $standardMinutes = 8 * 60;
        $overtimeHours = $workedMinutes > $standardMinutes
            ? round(($workedMinutes - $standardMinutes) / 60, 2)
            : 0.00;

        $method = $methods[$date->dayOfWeek % count($methods)];

        return array_merge($base, [
            'status' => $isLate ? 'late' : 'present',
            'check_in_time' => $checkIn->toDateTimeString(),
            'check_in_method' => $method,
            'check_out_time' => $checkOut->toDateTimeString(),
            'check_out_method' => $method,
            'worked_hours' => $workedHours,
            'overtime_hours' => $overtimeHours,
        ]);
    }

    private function halfDayRecord(array $base, Carbon $date): array
    {
        $checkIn = $date->copy()->setTime(9, 0)->addMinutes($date->day % 20);
        $checkOut = $date->copy()->setTime(13, 0)->addMinutes($date->day % 30);

        $workedMinutes = (int) $checkIn->diffInMinutes($checkOut);
        $workedHours = round($workedMinutes / 60, 2);

        return array_merge($base, [
            'status' => 'half_day',
            'check_in_time' => $checkIn->toDateTimeString(),
            'check_in_method' => 'manual',
            'check_out_time' => $checkOut->toDateTimeString(),
            'check_out_method' => 'manual',
            'worked_hours' => $workedHours,
            'overtime_hours' => 0.00,
        ]);
    }
}
