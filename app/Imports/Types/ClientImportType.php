<?php

namespace App\Imports\Types;

use App\Imports\AbstractImportType;
use App\Imports\ImportContext;
use App\Imports\ImportResult;
use App\Services\Import\ClientImporter;

class ClientImportType extends AbstractImportType
{
    public function __construct(private ClientImporter $importer) {}

    public function key(): string
    {
        return 'clients';
    }

    public function label(): string
    {
        return 'Clients';
    }

    public function requiredHeaders(): array
    {
        return ['name'];
    }

    public function optionalHeaders(): array
    {
        return [
            'company_name', 'phone', 'email', 'gst_number', 'registration_type',
            'address', 'city', 'state', 'zip_code', 'country', 'notes',
        ];
    }

    public function sampleRows(): array
    {
        return [
            ['Rahul Sharma', 'Sharma Nursery', '9876543210', 'rahul@example.com', '', 'unregistered', '12 Park Lane', 'Ahmedabad', 'Gujarat', '380001', 'India', 'Regular customer'],
            ['Priya Patel', '', '9123456780', 'priya@example.com', '', 'unregistered', '', 'Surat', 'Gujarat', '', 'India', ''],
            ['Green Thumb Pvt Ltd', 'Green Thumb Pvt Ltd', '02212345678', 'sales@greenthumb.in', '27AABCU9603R1ZM', 'regular', 'Plot 45, MIDC', 'Mumbai', 'Maharashtra', '400093', 'India', ''],
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