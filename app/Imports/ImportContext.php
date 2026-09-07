<?php

namespace App\Imports;

use App\Models\Import;

/**
 * Everything an importer might need to process a chunk.
 *
 * Exists because processChunk() signatures had drifted apart — categories
 * took four arguments, product+SKU took six — which forced the controller
 * into an if/else chain keyed on the type string. One object means one
 * signature, and a type that needs a new input adds a property here instead
 * of a branch there.
 */
final class ImportContext
{
    public function __construct(
        public readonly Import $import,
        public readonly int $companyId,
        public readonly ?int $storeId = null,
        /** Remaining new-record slots under the tenant's plan. */
        public readonly int $remainingSlots = PHP_INT_MAX,
    ) {}
}