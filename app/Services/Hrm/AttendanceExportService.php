<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Attendance;
use App\Models\Hrm\Employee;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AttendanceExportService
{
    /**
     * Resolve a period key to a date range + human label.
     *
     * @return array{date_from: string, date_to: string, label: string}
     */
    public function resolvePeriod(string $period, ?string $customFrom = null, ?string $customTo = null): array
    {
        $today = Carbon::today();

        if ($period === 'custom' && $customFrom && $customTo) {
            $from = Carbon::parse($customFrom)->startOfDay();
            $to = Carbon::parse($customTo)->startOfDay();

            // Ensure from <= to
            if ($from->gt($to)) {
                [$from, $to] = [$to, $from];
            }

            return [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'label' => 'Custom Range ('.$from->format('d M Y').' – '.$to->format('d M Y').')',
            ];
        }

        return match ($period) {
            'week' => [
                'date_from' => $today->copy()->startOfWeek()->toDateString(),
                'date_to' => $today->toDateString(),
                'label' => 'This Week ('
                    .$today->copy()->startOfWeek()->format('d M')
                    .' – '
                    .$today->format('d M Y').')',
            ],
            'month' => [
                'date_from' => $today->copy()->startOfMonth()->toDateString(),
                'date_to' => $today->toDateString(),
                'label' => 'This Month ('.$today->format('F Y').')',
            ],
            'year' => [
                'date_from' => $today->copy()->startOfYear()->toDateString(),
                'date_to' => $today->toDateString(),
                'label' => 'This Year ('.$today->format('Y').')',
            ],
            default => [ // 'today'
                'date_from' => $today->toDateString(),
                'date_to' => $today->toDateString(),
                'label' => 'Today ('.$today->format('d M Y').')',
            ],
        };
    }

    /**
     * Build a Carbon date array for the given range.
     *
     * @return Carbon[]
     */
    public function buildDateRange(string $dateFrom, string $dateTo): array
    {
        $cursor = Carbon::parse($dateFrom)->startOfDay();
        $end = Carbon::parse($dateTo)->startOfDay();
        $dates = [];

        while ($cursor->lte($end)) {
            $dates[] = $cursor->copy();
            $cursor->addDay();
        }

        return $dates;
    }

    /**
     * Load employees and index attendance records for the calendar export.
     *
     * @return array{employees: Collection, lookup: array<int, array<string, mixed>>}
     */
    public function buildCalendarData(int $companyId, array $filters): array
    {
        // Load all attendance records for the period (no pagination)
        $records = $this->buildQuery($companyId, $filters)->get();

        // Index as [employee_id][date_string] => Attendance
        $lookup = [];
        foreach ($records as $record) {
            $lookup[$record->employee_id][$record->date->toDateString()] = $record;
        }

        // Load employees — include all (active + inactive) so historical reports
        // show past employees too. Hard-scoped to the active store (switcher), same as
        // the report/export query below — no arbitrary cross-store export.
        $storeId = active_store()?->id;
        $employees = Employee::with(['user', 'department', 'store'])
            ->where('company_id', $companyId)
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->when(! empty($filters['department_id']), fn ($q) => $q->where('department_id', $filters['department_id']))
            ->when(! empty($filters['employee_id']), fn ($q) => $q->where('id', $filters['employee_id']))
            ->orderBy('employee_code')
            ->get();

        return compact('employees', 'lookup');
    }

    /**
     * Build a non-paginated Eloquent query applying the same filters as getReport().
     */
    public function buildQuery(int $companyId, array $filters): Builder
    {
        $query = Attendance::with(['employee.user', 'employee.department', 'store'])
            ->where('company_id', $companyId);

        // Store awareness: same hard-scope as getReport() — exports always reflect
        // the active store (switcher), never an arbitrary store_id from the request.
        $storeId = active_store()?->id;
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if (! empty($filters['date_from']) && ! empty($filters['date_to'])) {
            $query->forDateRange($filters['date_from'], $filters['date_to']);
        }

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['department_id'])) {
            $query->whereHas('employee', fn ($b) => $b->where('department_id', $filters['department_id']));
        }

        return $query->orderBy('date', 'asc')->orderBy('check_in_time', 'asc');
    }
}
