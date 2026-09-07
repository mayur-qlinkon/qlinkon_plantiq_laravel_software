<?php

namespace App\Exports;

use App\Models\Client;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class CustomerLedgerExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
{
    public function __construct(
        protected Client $client,
        protected Collection $entries,
        protected array $summary
    ) {}

    public function title(): string
    {
        return 'Ledger';
    }

    public function headings(): array
    {
        return [
            ['Customer Ledger — '.$this->client->name],
            ['Generated: '.now()->format('d M Y, h:i A')],
            [''], // blank spacer
            ['Date', 'Type', 'Reference', 'Invoice Amount (Dr)', 'Payment Amount (Cr)', 'Balance', 'Status'],
        ];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->entries as $entry) {
            $balance   = $entry['running_balance'];
            $isInvoice = $entry['type'] === 'invoice';

            $status = match(true) {
                ! $isInvoice => 'Received',
                $entry['payment_status'] === 'paid'    => 'Paid',
                $entry['payment_status'] === 'partial' => 'Partial',
                $entry['due_date'] && \Carbon\Carbon::parse($entry['due_date'])->isPast() => 'Overdue',
                default => 'Due',
            };

            $rows[] = [
                \Carbon\Carbon::parse($entry['date'])->format('d M Y'),
                $isInvoice ? 'Invoice' : 'Payment',
                $entry['reference'],
                $isInvoice ? number_format($entry['invoice_amount'], 2) : '',
                ! $isInvoice ? number_format($entry['payment_amount'], 2) : '',
                number_format(abs($balance), 2).($balance > 0 ? ' Dr' : ($balance < 0 ? ' Cr' : '')),
                $status,
            ];
        }

        // Closing balance row
        $rows[] = [''];
        $rows[] = [
            'CLOSING BALANCE',
            '',
            '',
            number_format($this->summary['total_invoiced'], 2),
            number_format($this->summary['total_paid'], 2),
            number_format($this->summary['outstanding'], 2).($this->summary['outstanding'] > 0 ? ' Dr' : ' Cr'),
            $this->summary['outstanding'] <= 0 ? 'Settled' : 'Pending',
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '108c2a']],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastRow = $event->sheet->getHighestRow();

                // Style the closing balance row
                $event->sheet->getStyle("A{$lastRow}:G{$lastRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1a1a1a']],
                ]);
            },
        ];
    }
}