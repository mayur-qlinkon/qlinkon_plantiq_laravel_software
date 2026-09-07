<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Generates a user-friendly Excel guide for the Product Image ZIP importer.
 *
 * Sheet layout (7 columns A–G):
 *   Row  1  — Title banner
 *   Row  2  — Spacer
 *   Row  3  — "HOW TO NAME YOUR FILES" section header
 *   Rows 4–9  — Step-by-step instructions
 *   Row 10  — Spacer
 *   Row 11  — Column headers (dark green bar)
 *   Row 12+ — One product per row with pre-filled example filenames
 */
class ProductImageGuideExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    private const HEADER_ROW = 11;

    private const DATA_START = 12;

    private const TOTAL_COLUMNS = 7;

    public function __construct(private readonly Collection $products) {}

    public function title(): string
    {
        return 'Image Upload Guide';
    }

    public function array(): array
    {
        $blank = array_fill(0, self::TOTAL_COLUMNS, '');

        $rows = [
            // ── Row 1: Title ─────────────────────────────────────────────
            ['PRODUCT IMAGE UPLOAD GUIDE — Naming Reference Sheet', '', '', '', '', '', ''],

            // ── Row 2: Spacer ─────────────────────────────────────────────
            $blank,

            // ── Row 3: Section header ─────────────────────────────────────
            ['HOW TO NAME YOUR IMAGE FILES', '', '', '', '', '', ''],

            // ── Rows 4-9: Step-by-step guide ─────────────────────────────
            ['Step 1', 'Find your product in the table below.', '', '', '', '', ''],
            ['Step 2', 'Copy the exact text from the "Product Slug" column.', '', '', '', '', ''],
            ['Step 3', 'Rename your images as:  {slug}-1.jpg  /  {slug}-2.jpg  /  {slug}-3.jpg', '', '', '', '', ''],
            ['Step 4', 'The file named  {slug}-1  automatically becomes the PRIMARY (main/cover) image.', '', '', '', '', ''],
            ['Step 5', 'Place ALL renamed images inside a single ZIP folder and upload it on the Bulk Import page.', '', '', '', '', ''],
            ['Tip', 'Supported formats: jpg, jpeg, png, webp   |   Maximum ZIP size: 20 MB', '', '', '', '', ''],

            // ── Row 10: Spacer ────────────────────────────────────────────
            $blank,

            // ── Row 11: Column headers ────────────────────────────────────
            ['#', 'Product Name', 'Category', 'Product Slug  ← use this exactly', 'Image 1 Filename (Primary)', 'Image 2 Filename', 'Image 3 Filename'],
        ];

        // ── Rows 12+: One row per product ─────────────────────────────────
        foreach ($this->products as $i => $product) {
            $slug = $product->slug;

            $rows[] = [
                $i + 1,
                $product->name,
                $product->category->name ?? '—',
                $slug,
                "{$slug}-1.jpg",
                "{$slug}-2.jpg",
                "{$slug}-3.jpg",
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastDataRow = self::DATA_START + $this->products->count() - 1;
        $lastDataRow = max($lastDataRow, self::DATA_START); // guard: 0 products

        // ── Merge cells across all columns for wide text ─────────────────
        $range = 'A1:G'.self::TOTAL_COLUMNS; // not used — use explicit refs
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A3:G3');
        foreach (range(4, 9) as $row) {
            $sheet->mergeCells("B{$row}:G{$row}");
        }

        // ── Row heights ───────────────────────────────────────────────────
        $sheet->getRowDimension(1)->setRowHeight(32);
        $sheet->getRowDimension(11)->setRowHeight(22);

        // ── Alternating background for product rows ───────────────────────
        for ($row = self::DATA_START; $row <= $lastDataRow; $row++) {
            $isAlternate = ($row - self::DATA_START) % 2 === 1;
            if ($isAlternate) {
                $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => 'f8fafc'],
                    ],
                ]);
            }
        }

        // ── Slug column (D): highlight in light blue so it stands out ─────
        if ($lastDataRow >= self::DATA_START) {
            $sheet->getStyle('D'.self::DATA_START.':D'.$lastDataRow)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '1d4ed8']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'eff6ff']],
            ]);
        }

        // ── Example filename columns (E–G): muted green ───────────────────
        if ($lastDataRow >= self::DATA_START) {
            $sheet->getStyle('E'.self::DATA_START.':G'.$lastDataRow)->applyFromArray([
                'font' => ['color' => ['rgb' => '166534']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'f0fdf4']],
            ]);
        }

        // ── Border around the full product table ──────────────────────────
        if ($lastDataRow >= self::HEADER_ROW) {
            $sheet->getStyle('A'.self::HEADER_ROW.':G'.$lastDataRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => 'thin',
                        'color' => ['rgb' => 'e2e8f0'],
                    ],
                ],
            ]);
        }

        return [
            // Row 1: Title banner
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '166534']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'f0fdf4']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            ],
            // Row 3: Section header (amber)
            3 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '78350f']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'fef3c7']],
            ],
            // Rows 4–9: Instruction rows (pale amber)
            '4:9' => [
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'fffbeb']],
                'font' => ['size' => 10, 'color' => ['rgb' => '374151']],
                'alignment' => ['wrapText' => true, 'vertical' => 'center'],
            ],
            // Step label cells bold + amber
            'A4' => ['font' => ['bold' => true, 'color' => ['rgb' => 'd97706']]],
            'A5' => ['font' => ['bold' => true, 'color' => ['rgb' => 'd97706']]],
            'A6' => ['font' => ['bold' => true, 'color' => ['rgb' => 'd97706']]],
            'A7' => ['font' => ['bold' => true, 'color' => ['rgb' => 'd97706']]],
            'A8' => ['font' => ['bold' => true, 'color' => ['rgb' => 'd97706']]],
            'A9' => ['font' => ['bold' => true, 'color' => ['rgb' => '059669']]],
            // Row 11: Column header bar (brand green)
            11 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '108c2a']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            ],
        ];
    }
}
