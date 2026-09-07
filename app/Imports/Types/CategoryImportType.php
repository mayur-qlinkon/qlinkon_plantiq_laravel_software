<?php

namespace App\Imports\Types;

use App\Imports\AbstractImportType;
use App\Imports\ImportContext;
use App\Imports\ImportResult;
use App\Services\Import\CategoryImporter;

/**
 * Wraps the existing CategoryImporter rather than absorbing its row logic.
 *
 * Deliberate: this phase changes structure only, so the code that actually
 * writes records stays byte-identical and the refactor cannot alter import
 * behaviour. Merging the importer into this class is the final phase, once
 * every caller reads through the registry.
 */
class CategoryImportType extends AbstractImportType
{
    public function __construct(private CategoryImporter $importer) {}

    public function key(): string
    {
        return 'categories';
    }

    public function label(): string
    {
        return 'Categories';
    }

    public function requiredHeaders(): array
    {
        return ['name'];
    }

    public function optionalHeaders(): array
    {
        return ['slug'];
    }

    public function sampleRows(): array
    {
        return [
            ['Indoor Plants', 'indoor-plants'],
            ['Succulents', 'succulents'],
            ['Outdoor Plants', 'outdoor-plants'],
            ['Flowering', 'flowers'],
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