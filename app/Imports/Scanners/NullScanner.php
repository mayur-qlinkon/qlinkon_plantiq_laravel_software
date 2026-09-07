<?php

namespace App\Imports\Scanners;

use App\Imports\Contracts\ImportScanner;

/** Default for types with no pre-flight rules. */
class NullScanner implements ImportScanner
{
    public function observe(array $row): void {}

    public function reject(string $importMode, int $companyId): ?array
    {
        return null;
    }
}