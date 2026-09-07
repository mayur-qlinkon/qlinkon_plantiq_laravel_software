<?php

namespace App\Imports\Types;

use App\Imports\AbstractImportType;
use App\Imports\ImportContext;
use App\Imports\ImportResult;
use App\Services\Import\SupplierImporter;

class SupplierImportType extends AbstractImportType
{
    public function __construct(private SupplierImporter $importer) {}

    public function key(): string
    {
        return 'suppliers';
    }

    public function label(): string
    {
        return 'Suppliers';
    }

    public function requiredHeaders(): array
    {
        return ['name'];
    }

    public function optionalHeaders(): array
    {
        return [
            'phone', 'email', 'gstin', 'pan', 'registration_type',
            'address', 'city', 'state', 'pincode', 'credit_days', 'credit_limit', 'notes',
        ];
    }

    public function sampleRows(): array
    {
        return [
            ['Kisan Seeds Co.', '9012345678', 'orders@kisanseeds.in', '24AABCK1234N1ZP', 'AABCK1234N', 'regular', 'Shop 7, Market Road', 'Ahmedabad', 'Gujarat', '380004', '30', '50000', 'Seed supplier'],
            ['Local Pot Maker', '9876501234', '', '', '', 'unregistered', '', 'Rajkot', 'Gujarat', '360001', '0', '0', 'Cash basis'],
            ['Mumbai Fertilizers', '02266778899', 'info@mumfert.in', '27AABCM5678E1ZR', 'AABCM5678E', 'regular', 'Unit 12, Andheri East', 'Mumbai', 'Maharashtra', '400069', '15', '100000', ''],
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