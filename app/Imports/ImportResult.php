<?php

namespace App\Imports;

/**
 * Outcome of one processed chunk.
 *
 * Replaces the loose array importers returned, which the controller had to
 * read defensively (`! empty($result['created'])`) because not every importer
 * set every key. Named counters that always exist remove that guesswork.
 */
final class ImportResult
{
    public function __construct(
        public readonly int $success = 0,
        public readonly int $failed = 0,
        public readonly int $skipped = 0,
        public readonly int $created = 0,
        public readonly int $updated = 0,
        public readonly int $limitSkipped = 0,
        /** ['categories' => [...names], 'units' => [...names]] */
        public readonly array $createdRefs = [],
    ) {}

    /**
     * Adapt the legacy array shape. Lets the existing importer services stay
     * untouched while callers move to the typed result.
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            success:      (int) ($raw['success'] ?? 0),
            failed:       (int) ($raw['failed'] ?? 0),
            skipped:      (int) ($raw['skipped'] ?? 0),
            created:      (int) ($raw['created'] ?? 0),
            updated:      (int) ($raw['updated'] ?? 0),
            limitSkipped: (int) ($raw['limit_skipped'] ?? 0),
            createdRefs:  (array) ($raw['created_refs'] ?? []),
        );
    }

    public static function empty(): self
    {
        return new self;
    }
}