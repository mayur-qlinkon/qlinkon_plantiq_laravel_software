<?php

namespace App\Imports\Types;

use App\Imports\AbstractImportType;
use App\Imports\ImportContext;
use App\Imports\ImportResult;
use App\Services\Import\UnitImporter;

class UnitImportType extends AbstractImportType
{
    public function __construct(private UnitImporter $importer) {}

    public function key(): string
    {
        return 'units';
    }

    public function label(): string
    {
        return 'Units';
    }

    public function requiredHeaders(): array
    {
        return ['name', 'short_name'];
    }

    public function sampleRows(): array
    {
        return [
            ['Kilogram', 'kg'],
            ['Piece', 'pcs'],
            ['Litre', 'ltr'],
            ['Meter', 'm'],
            ['Box', 'box'],
        ];
    }

    public function extractUniqueKey(array $row): ?string
    {
        return $this->importer->extractUniqueKey($row);
    }

    public function processChunk(array $rows, int $startRow, ImportContext $context): ImportResult
    {
        return ImportResult::fromArray(
            $this->importer->processChunk($context->import, $rows, $startRow, $context->companyId)
        );
    }
}