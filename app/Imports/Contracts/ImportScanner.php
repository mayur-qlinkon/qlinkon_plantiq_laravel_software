<?php

namespace App\Imports\Contracts;

/**
 * Observes each CSV row during the upload pre-flight pass.
 *
 * Exists for one reason: the product-limit check must know how many NEW
 * products a file would create, which can only be counted while walking the
 * rows. Without this hook that logic sits in the controller behind a type
 * check, which is exactly what the registry refactor is removing.
 *
 * The scan is a single pass — implementations accumulate, they do not re-read
 * the file.
 */
interface ImportScanner
{
    /** Called once per non-empty data row, headers already mapped. */
    public function observe(array $row): void;

    /**
     * Reject the upload before a single record is written.
     *
     * @return array|null  JSON error payload, or null to allow the import.
     */
    public function reject(string $importMode, int $companyId): ?array;
}