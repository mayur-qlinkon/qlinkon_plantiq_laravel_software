<?php

namespace App\Exports;

use App\Services\Hrm\AttendanceExportService;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    private int $rowNumber = 0;

    public function __construct(
        private readonly int $companyId,
        private readonly array $filters,
        private readonly string $periodLabel,
    ) {}

    public function query(): Builder
    {
        return app(AttendanceExportService::class)->buildQuery($this->companyId, $this->filters);
    }

    public function title(): string
    {
        return 'Attendance Report';
    }

    public function headings(): array
    {
        return [
            '#',
            'Employee Code',
            'Employee Name',
            'Department',
            'Date',
            'Day',
            'Check In',
            'Check Out',
            'Worked Hours',
            'Overtime Hours',
            'Status',
            'Check In Method',
            'Overridden',
            'Override Reason',
        ];
    }

    public function map($att): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $att->employee?->employee_code ?? '---',
            $att->employee?->user?->name ?? 'Unknown',
            $att->employee?->department?->name ?? '---',
            $att->date->format('d M Y'),
            $att->date->format('l'),
            $att->check_in_time?->format('h:i A') ?? '---',
            $att->check_out_time?->format('h:i A') ?? '---',
            $att->worked_hours ? number_format($att->worked_hours, 2) : '---',
            $att->overtime_hours ? number_format($att->overtime_hours, 2) : '---',
            ucfirst(str_replace('_', ' ', $att->status)),
            strtoupper($att->check_in_method ?? '---'),
            $att->is_overridden ? 'Yes' : 'No',
            $att->override_reason ?? '---',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1e293b']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }
}
