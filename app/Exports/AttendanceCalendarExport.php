<?php

namespace App\Exports;

use App\Models\Company;
use App\Models\Hrm\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Calendar-grid attendance export.
 *
 * Layout (matches the HRM calendar report format):
 *  - Global header: CompName, Report Period
 *  - Per employee: info block (Empcode / Name / Store) → stats block
 *    (Present / Absent / Total Work+OT / Total OT) → daily grid
 *    (day numbers | day names | IN | OUT | WORK | OT | Status rows)
 *
 * For year period a monthly-summary grid is generated instead of a 365-column grid.
 */
class AttendanceCalendarExport implements WithEvents, WithTitle
{
    // ── Status display maps ────────────────────────────────────────────────────

    private const STATUS_ABBR = [
        'present' => 'P',
        'absent' => 'A',
        'late' => 'L',
        'half_day' => 'HD',
        'on_leave' => 'OL',
        'holiday' => 'HO',
        'week_off' => 'WO',
    ];

    private const STATUS_COLORS = [
        'present' => ['bg' => 'DCFCE7', 'font' => '15803D'],
        'absent' => ['bg' => 'FEE2E2', 'font' => '991B1B'],
        'late' => ['bg' => 'FEF3C7', 'font' => '92400E'],
        'half_day' => ['bg' => 'E0F2FE', 'font' => '075985'],
        'on_leave' => ['bg' => 'EDE9FE', 'font' => '5B21B6'],
        'holiday' => ['bg' => 'F0FDF4', 'font' => '166534'],
        'week_off' => ['bg' => 'F1F5F9', 'font' => '475569'],
    ];

    public function __construct(
        private readonly Company $company,
        private readonly Collection $employees,
        /** @var array<int, array<string, mixed>> $attendanceLookup [emp_id][date_str] => Attendance */
        private readonly array $attendanceLookup,
        /** @var Carbon[] $dates */
        private readonly array $dates,
        private readonly string $periodLabel,
        private readonly string $periodType,
    ) {}

    public function title(): string
    {
        return 'Attendance';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                if ($this->periodType === 'year') {
                    $this->buildYearlySheet($sheet);
                } else {
                    $this->buildCalendarSheet($sheet);
                }
            },
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  Calendar sheet  (Today / Week / Month)
    // ══════════════════════════════════════════════════════════════════════════

    private function buildCalendarSheet(Worksheet $sheet): void
    {
        $lastCol = count($this->dates) + 1; // col-1 = label, col-2…N+1 = dates
        $row = 1;

        $row = $this->writeGlobalHeader($sheet, $row, $lastCol);

        foreach ($this->employees as $employee) {
            $row = $this->writeEmployeeSection($sheet, $employee, $row, $lastCol);
        }

        $this->writeLegend($sheet, $row, $lastCol);

        // Column widths
        $sheet->getColumnDimensionByColumn(1)->setWidth(16);
        for ($c = 2; $c <= $lastCol; $c++) {
            $sheet->getColumnDimensionByColumn($c)->setWidth(7.5);
        }
    }

    private function writeGlobalHeader(Worksheet $sheet, int $row, int $lastCol): int
    {
        foreach (['CompName' => $this->company->name, 'Report Period' => $this->periodLabel] as $label => $value) {
            $sheet->setCellValueByColumnAndRow(1, $row, $label);
            $sheet->setCellValueByColumnAndRow(2, $row, $value);
            if ($lastCol > 2) {
                $sheet->mergeCellsByColumnAndRow(2, $row, $lastCol, $row);
            }
            $this->styleGlobalHeader($sheet, $row, $lastCol);
            $row++;
        }

        return $row + 1; // +1 blank row
    }

    private function writeEmployeeSection(Worksheet $sheet, Employee $employee, int $row, int $lastCol): int
    {
        $empAttendance = $this->attendanceLookup[$employee->id] ?? [];
        $stats = $this->computeStats($empAttendance);

        // ── Info block ──────────────────────────────────────────────────────
        $infoRows = [
            'Empcode' => $employee->employee_code ?? (string) $employee->id,
            'Name' => $employee->user?->name ?? 'Unknown',
            'Store' => $employee->store?->name ?? '—',
        ];
        foreach ($infoRows as $label => $value) {
            $sheet->setCellValueByColumnAndRow(1, $row, $label);
            $sheet->setCellValueByColumnAndRow(2, $row, $value);
            if ($lastCol > 2) {
                $sheet->mergeCellsByColumnAndRow(2, $row, $lastCol, $row);
            }
            $this->styleEmpInfo($sheet, $row, $lastCol);
            $row++;
        }

        $row++; // blank

        // ── Stats block ─────────────────────────────────────────────────────
        $statsRows = [
            'Present' => $stats['present'],
            'Absent' => $stats['absent'],
            'Total Work Hours' => $this->formatHours($stats['total_work']),
            'Total OT Hours' => $this->formatHours($stats['total_ot']),
        ];
        foreach ($statsRows as $label => $value) {
            $sheet->setCellValueByColumnAndRow(1, $row, $label);
            $sheet->setCellValueByColumnAndRow(2, $row, $value);
            $this->styleStats($sheet, $row);
            $row++;
        }

        $row++; // blank

        // ── Calendar grid header ─────────────────────────────────────────────
        // Day-number row
        $this->styleLabelCell($sheet, 1, $row);
        foreach ($this->dates as $i => $date) {
            $col = $i + 2;
            $sheet->setCellValueByColumnAndRow($col, $row, $date->day);
            $this->styleCalHeader($sheet, $col, $row, $date->isWeekend());
        }
        $row++;

        // Day-name row
        $this->styleLabelCell($sheet, 1, $row);
        foreach ($this->dates as $i => $date) {
            $col = $i + 2;
            $sheet->setCellValueByColumnAndRow($col, $row, strtoupper($date->format('D')));
            $this->styleCalHeader($sheet, $col, $row, $date->isWeekend());
        }
        $row++;

        // ── Grid data rows (IN / OUT / WORK / OT / Status) ──────────────────
        $gridRows = [
            'IN' => fn ($att) => $att?->check_in_time?->format('H:i') ?? '',
            'OUT' => fn ($att) => $att?->check_out_time?->format('H:i') ?? '',
            'WORK' => fn ($att) => $att && $att->worked_hours > 0 ? $this->formatHours((float) $att->worked_hours) : '',
            'OT' => fn ($att) => $att && $att->overtime_hours > 0 ? $this->formatHours((float) $att->overtime_hours) : '',
            'Status' => fn ($att) => $att ? (self::STATUS_ABBR[$att->status] ?? strtoupper($att->status)) : '',
        ];

        foreach ($gridRows as $label => $getter) {
            $sheet->setCellValueByColumnAndRow(1, $row, $label);
            $this->styleLabelCell($sheet, 1, $row);

            foreach ($this->dates as $i => $date) {
                $col = $i + 2;
                $att = $empAttendance[$date->toDateString()] ?? null;
                $sheet->setCellValueByColumnAndRow($col, $row, $getter($att));

                if ($label === 'Status' && $att) {
                    $this->styleStatusCell($sheet, $col, $row, $att->status);
                } else {
                    $this->styleDataCell($sheet, $col, $row, $date->isWeekend());
                }
            }
            $row++;
        }

        return $row + 2; // 2-row blank separator before next employee
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  Yearly sheet  (monthly aggregate summary)
    // ══════════════════════════════════════════════════════════════════════════

    private function buildYearlySheet(Worksheet $sheet): void
    {
        $year = count($this->dates) > 0 ? $this->dates[0]->year : now()->year;
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $lastCol = 15; // label + 12 months + 1 total + 1 padding
        $row = 1;

        // Global header
        foreach (['CompName' => $this->company->name, 'Report Year' => (string) $year] as $lbl => $val) {
            $sheet->setCellValueByColumnAndRow(1, $row, $lbl);
            $sheet->setCellValueByColumnAndRow(2, $row, $val);
            $sheet->mergeCellsByColumnAndRow(2, $row, $lastCol, $row);
            $this->styleGlobalHeader($sheet, $row, $lastCol);
            $row++;
        }
        $row++;

        // Month-names header
        $sheet->setCellValueByColumnAndRow(1, $row, 'Employee / Metric');
        $this->styleLabelCell($sheet, 1, $row);
        foreach ($months as $i => $m) {
            $sheet->setCellValueByColumnAndRow($i + 2, $row, $m);
            $this->styleCalHeader($sheet, $i + 2, $row, false);
        }
        $sheet->setCellValueByColumnAndRow(14, $row, 'Total');
        $this->styleCalHeader($sheet, 14, $row, false);
        $row++;

        // Per-employee rows
        $metrics = ['Present' => 'present', 'Absent' => 'absent', 'Late' => 'late'];

        foreach ($this->employees as $employee) {
            $empAttendance = $this->attendanceLookup[$employee->id] ?? [];

            // Group records by month
            $monthly = array_fill(1, 12, ['present' => 0, 'absent' => 0, 'late' => 0]);
            foreach ($empAttendance as $dateStr => $att) {
                $m = (int) Carbon::parse($dateStr)->month;
                if (isset($monthly[$m][$att->status])) {
                    $monthly[$m][$att->status]++;
                }
            }

            // Employee name header row
            $sheet->setCellValueByColumnAndRow(1, $row, $employee->user?->name ?? 'Unknown');
            $sheet->mergeCellsByColumnAndRow(1, $row, $lastCol, $row);
            $this->styleEmpInfo($sheet, $row, $lastCol);
            $row++;

            foreach ($metrics as $metricLabel => $metricKey) {
                $sheet->setCellValueByColumnAndRow(1, $row, $metricLabel);
                $this->styleStats($sheet, $row);
                $total = 0;
                for ($m = 1; $m <= 12; $m++) {
                    $val = $monthly[$m][$metricKey];
                    $sheet->setCellValueByColumnAndRow($m + 1, $row, $val);
                    $this->styleDataCell($sheet, $m + 1, $row, false);
                    $total += $val;
                }
                $sheet->setCellValueByColumnAndRow(14, $row, $total);
                $this->styleDataCell($sheet, 14, $row, false);
                $row++;
            }

            $row++; // blank separator
        }

        // Column widths
        $sheet->getColumnDimensionByColumn(1)->setWidth(22);
        for ($c = 2; $c <= $lastCol; $c++) {
            $sheet->getColumnDimensionByColumn($c)->setWidth(7);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  Data helpers
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * @param  array<string, mixed>  $attendance  [date_str => Attendance]
     * @return array{present: int, absent: int, total_work: float, total_ot: float}
     */
    private function computeStats(array $attendance): array
    {
        $present = $absent = 0;
        $totalWork = $totalOt = 0.0;

        foreach ($attendance as $att) {
            if ($att->status === 'present') {
                $present++;
            }
            if ($att->status === 'absent') {
                $absent++;
            }
            $totalWork += (float) ($att->worked_hours ?? 0);
            $totalOt += (float) ($att->overtime_hours ?? 0);
        }

        return compact('present', 'absent') + ['total_work' => $totalWork, 'total_ot' => $totalOt];
    }

    /** Convert decimal hours (e.g. 8.5) to "H:MM" string (e.g. "8:30"). */
    private function formatHours(float $hours): string
    {
        $h = (int) $hours;
        $m = (int) round(($hours - $h) * 60);

        return sprintf('%d:%02d', $h, $m);
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  Legend  (appended at the bottom of the calendar sheet)
    // ══════════════════════════════════════════════════════════════════════════

    private function writeLegend(Worksheet $sheet, int $row, int $lastCol): void
    {
        // ── Section header ────────────────────────────────────────────────────
        $sheet->setCellValueByColumnAndRow(1, $row, 'LEGEND — Abbreviations Used in This Report');
        if ($lastCol > 1) {
            $sheet->mergeCellsByColumnAndRow(1, $row, $lastCol, $row);
        }
        $sheet->getStyle($this->rowRange($row, 1, $lastCol))->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(18);
        $row++;

        // ── Row-label abbreviations ───────────────────────────────────────────
        $rowLabels = [
            'IN' => 'Check-In Time',
            'OUT' => 'Check-Out Time',
            'WORK' => 'Worked Hours (H:MM)',
            'OT' => 'Overtime Hours (H:MM)',
        ];

        foreach ($rowLabels as $abbr => $full) {
            $sheet->setCellValueByColumnAndRow(1, $row, $abbr);
            $sheet->setCellValueByColumnAndRow(2, $row, $full);
            if ($lastCol > 2) {
                $sheet->mergeCellsByColumnAndRow(2, $row, $lastCol, $row);
            }
            $this->styleLegendRow($sheet, $row, $lastCol, null);
            $row++;
        }

        $row++; // blank separator

        // ── Status-code abbreviations ─────────────────────────────────────────
        $statusLegend = [
            'P' => ['full' => 'Present', 'status' => 'present'],
            'L' => ['full' => 'Late (arrived after shift start time)', 'status' => 'late'],
            'A' => ['full' => 'Absent', 'status' => 'absent'],
            'HD' => ['full' => 'Half Day', 'status' => 'half_day'],
            'OL' => ['full' => 'On Leave (approved)', 'status' => 'on_leave'],
            'HO' => ['full' => 'Holiday (public holiday)', 'status' => 'holiday'],
            'WO' => ['full' => 'Week Off (scheduled off day)', 'status' => 'week_off'],
        ];

        foreach ($statusLegend as $abbr => $info) {
            $sheet->setCellValueByColumnAndRow(1, $row, $abbr);
            $sheet->setCellValueByColumnAndRow(2, $row, $info['full']);
            if ($lastCol > 2) {
                $sheet->mergeCellsByColumnAndRow(2, $row, $lastCol, $row);
            }
            $this->styleLegendRow($sheet, $row, $lastCol, $info['status']);
            $row++;
        }
    }

    private function styleLegendRow(Worksheet $sheet, int $row, int $lastCol, ?string $status): void
    {
        if ($status !== null) {
            $colors = self::STATUS_COLORS[$status] ?? ['bg' => 'F1F5F9', 'font' => '374151'];
            // Abbreviation cell — colored like the status cell in the grid
            $sheet->getStyle($this->coord(1, $row))->applyFromArray([
                'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => $colors['font']]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colors['bg']]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            ]);
        } else {
            // Row-label entries (IN/OUT/WORK/OT) — neutral style
            $sheet->getStyle($this->coord(1, $row))->applyFromArray([
                'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '374151']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            ]);
        }

        // Full-name cell
        $sheet->getStyle($this->coord(2, $row))->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '374151']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FAFAFA']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        ]);
        if ($lastCol > 2) {
            $sheet->getStyle($this->rowRange($row, 2, $lastCol))->applyFromArray([
                'font' => ['size' => 9, 'color' => ['rgb' => '374151']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FAFAFA']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            ]);
        }

        $sheet->getRowDimension($row)->setRowHeight(16);
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  Styling helpers
    // ══════════════════════════════════════════════════════════════════════════

    /** Return "A1"-style coordinate string from 1-indexed column + row. */
    private function coord(int $col, int $row): string
    {
        return Coordinate::stringFromColumnIndex($col).$row;
    }

    /** "A5:Z5" row-range string. */
    private function rowRange(int $row, int $firstCol, int $lastCol): string
    {
        return $this->coord($firstCol, $row).':'.$this->coord($lastCol, $row);
    }

    private function styleGlobalHeader(Worksheet $sheet, int $row, int $lastCol): void
    {
        $sheet->getStyle($this->rowRange($row, 1, $lastCol))->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(18);
    }

    private function styleEmpInfo(Worksheet $sheet, int $row, int $lastCol): void
    {
        $sheet->getStyle($this->rowRange($row, 1, $lastCol))->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        // Label cell slightly dimmed
        $sheet->getStyle($this->coord(1, $row))->getFont()->getColor()->setRGB('94A3B8');
        $sheet->getRowDimension($row)->setRowHeight(16);
    }

    private function styleStats(Worksheet $sheet, int $row): void
    {
        $sheet->getStyle($this->coord(1, $row))->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '475569']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
        ]);
        $sheet->getStyle($this->coord(2, $row))->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1E293B']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
        ]);
    }

    private function styleCalHeader(Worksheet $sheet, int $col, int $row, bool $isWeekend): void
    {
        $bg = $isWeekend ? 'E2E8F0' : 'F1F5F9';
        $sheet->getStyle($this->coord($col, $row))->applyFromArray([
            'font' => ['bold' => true, 'size' => 8, 'color' => ['rgb' => $isWeekend ? '94A3B8' : '475569']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ]);
    }

    private function styleLabelCell(Worksheet $sheet, int $col, int $row): void
    {
        $sheet->getStyle($this->coord($col, $row))->applyFromArray([
            'font' => ['bold' => true, 'size' => 8, 'color' => ['rgb' => '374151']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        ]);
    }

    private function styleDataCell(Worksheet $sheet, int $col, int $row, bool $isWeekend): void
    {
        $sheet->getStyle($this->coord($col, $row))->applyFromArray([
            'font' => ['size' => 8, 'color' => ['rgb' => '374151']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $isWeekend ? 'F8FAFC' : 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        ]);
    }

    private function styleStatusCell(Worksheet $sheet, int $col, int $row, string $status): void
    {
        $colors = self::STATUS_COLORS[$status] ?? ['bg' => 'F1F5F9', 'font' => '374151'];
        $sheet->getStyle($this->coord($col, $row))->applyFromArray([
            'font' => ['bold' => true, 'size' => 8, 'color' => ['rgb' => $colors['font']]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colors['bg']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        ]);
    }
}
