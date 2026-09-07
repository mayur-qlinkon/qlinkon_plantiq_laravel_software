<?php

namespace App\Services\Import;

/**
 * Sentinel exception thrown inside an import row's transaction when the
 * import is running in dry-run mode. Catching this outside the transaction
 * lets the DB rollback happen normally while still treating the row as
 * "would-have-succeeded" for counter purposes.
 */
class DryRunRollback extends \RuntimeException
{
    /** @var string|null One of: 'created' | 'updated' */
    public ?string $action = null;

    public static function for(string $action): self
    {
        $e = new self('Dry run rollback');
        $e->action = $action;

        return $e;
    }
}
