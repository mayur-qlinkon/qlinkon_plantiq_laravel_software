<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeadsSampleTemplate implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    public function title(): string
    {
        return 'CRM Leads Import';
    }

    public function headings(): array
    {
        return [
            'name',          // Required
            'phone',         // Duplicate detection key
            'email',
            'company',
            'source',        // Must match existing source name exactly
            'priority',      // low / medium / high / hot
            'value',         // Numeric, ₹
            'tags',          // Comma-separated: Hot Lead, VIP
            'address',
            'city',
            'state',
            'pin',
            'country',
            'instagram',
            'website',
            'notes',
        ];
    }

    public function array(): array
    {
        return [
            // Example row 1
            [
                'Rahul Sharma',
                '9876543210',
                'rahul@example.com',
                'Sharma Enterprises',
                'WhatsApp',
                'high',
                '50000',
                'Hot Lead, VIP',
                '123 MG Road',
                'Ahmedabad',
                'Gujarat',
                '380001',
                'India',
                'rahulsharma',
                'https://sharma.com',
                'Interested in bulk order. Follow up on Monday.',
            ],
            // Example row 2
            [
                'Priya Patel',
                '9898989898',
                'priya@email.com',
                'Patel Group',
                'Storefront',
                'medium',
                '15000',
                'Follow Up',
                '45 Ring Road',
                'Surat',
                'Gujarat',
                '395003',
                'India',
                '',
                '',
                'Online order inquiry — needs quotation.',
            ],
            // Example row 3 — minimal (only name required)
            [
                'Amit Verma',
                '9000000001',
                '',
                '',
                'Phone Call',
                'low',
                '',
                '',
                '',
                'Mumbai',
                'Maharashtra',
                '',
                'India',
                '',
                '',
                '',
            ],
        ];
    }

    // public function styles(Worksheet $sheet): array
    // {
    //     return [
    //         // Header row — dark background, white bold text
    //         1 => [
    //             'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
    //             'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1e293b']],
    //             'alignment' => ['horizontal' => 'center'],
    //         ],
    //         // Example rows — light blue tint
    //         2 => ['fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'f0f9ff']]],
    //         3 => ['fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'f0f9ff']]],
    //         4 => ['fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'f0f9ff']]],
    //     ];
    // }
}
