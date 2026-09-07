<?php

namespace App\Imports\Contracts;

use App\Imports\ImportContext;
use App\Imports\ImportResult;

/**
 * A single importable resource — its schema, its behaviour and how it is
 * presented, in one place.
 *
 * Before this, adding an import type meant editing six files: the importer
 * service, two controller wrapper methods, two routes, the sample-CSV array,
 * and the Blade panel metadata. A class implementing this interface plus one
 * line in ImportTypeRegistry is now the whole job.
 *
 * Anything derivable is NOT declared here — the column hint comes from the
 * headers, the dependency notice from dependsOn(). And anything that belongs
 * to a page rather than to the resource (tab grouping, step numbers, accent
 * colours) is deliberately absent: that is layout, not identity.
 */
interface ImportType
{
    // ── Identity ──

    /** URL segment and Import::$type value, e.g. 'categories'. */
    public function key(): string;

    /** Human label, e.g. 'Categories'. */
    public function label(): string;

    /** Panel heading, e.g. 'Import Categories'. */
    public function title(): string;

    // ── Schema ──

    /** @return list<string> Columns the CSV must contain. */
    public function requiredHeaders(): array;

    /** @return list<string> Columns the CSV may contain. */
    public function optionalHeaders(): array;

    /** @return list<list<string>> Example data rows for the sample CSV. */
    public function sampleRows(): array;

    /** @return list<string> Required + optional headers, in that order. */
    public function allHeaders(): array;

    /** The "Columns: name, slug" hint shown in the panel. */
    public function columnHint(): string;

    /**
     * Check the uploaded CSV's header row against this type's schema.
     *
     * @return array{valid: bool, message: string}
     */
    public function validateHeaders(array $headers): array;

    // ── Behaviour ──

    /**
     * Key used to detect duplicate rows within one file.
     * Null for rows with no usable key — normal validation catches those.
     */
    public function extractUniqueKey(array $row): ?string;

    public function processChunk(array $rows, int $startRow, ImportContext $context): ImportResult;

    // ── Policy ──

    /** Permission slug required to use this import, or null when open. */
    public function permission(): ?string;

    /** @return list<string> Keys of types that must be imported first. */
    public function dependsOn(): array;

    /**
     * How many new records the tenant's plan still allows.
     * PHP_INT_MAX when the resource is not plan-limited.
     */
    public function remainingSlots(int $companyId, bool $isDryRun, int $alreadySimulated): int;

    /** Extra guidance shown in the panel. HTML allowed. */
    public function helpText(): ?string;

    /** Pre-flight observer for the upload scan. */
    public function newScanner(): ImportScanner;

    /**
     * Whether the panel should render plan-limit warnings.
     * Distinct from remainingSlots(), which answers "how many" at run time —
     * this answers "is this resource capped at all" at render time.
     */
    public function isPlanLimited(): bool;

    /**
     * Helper links shown beside the Sample CSV button.
     *
     * @return list<array{href: string, label: string, icon: string, color: string}>
     */
    public function extraLinks(): array;
}