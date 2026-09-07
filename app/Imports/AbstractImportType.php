<?php

namespace App\Imports;

use App\Imports\Contracts\ImportScanner;
use App\Imports\Contracts\ImportType;
use App\Imports\Scanners\NullScanner;

/**
 * Shared behaviour for every import type.
 *
 * validateHeaders() lived identically in all five importer services; it now
 * lives once. The rest are derivations — a subclass that hand-writes its
 * column hint or dependency notice is doing something wrong.
 */
abstract class AbstractImportType implements ImportType
{
    public function title(): string
    {
        return 'Import ' . $this->label();
    }

    public function optionalHeaders(): array
    {
        return [];
    }

    public function permission(): ?string
    {
        return null;
    }

    public function dependsOn(): array
    {
        return [];
    }

    public function helpText(): ?string
    {
        return null;
    }

    public function remainingSlots(int $companyId, bool $isDryRun, int $alreadySimulated): int
    {
        return PHP_INT_MAX;
    }

    public function newScanner(): ImportScanner
    {
        return new NullScanner;
    }

    public function isPlanLimited(): bool
    {
        return false;
    }

    /**
     * Defaults to an "existing data" export for each dependency, so a user
     * looking at a category_slug column can grab the valid slugs without
     * leaving the panel. Derived from dependsOn() — subclasses add to it
     * rather than restating it.
     */
    public function extraLinks(): array
    {
        $links = [];

        foreach ($this->dependsOn() as $dependency) {
            $links[] = [
                'href'  => route('admin.bulk-import.export', $dependency),
                'label' => 'Existing ' . app(ImportTypeRegistry::class)->get($dependency)->label(),
                'icon'  => 'database',
                'color' => 'blue',
            ];
        }

        return $links;
    }

    /** @return list<string> */
    public function allHeaders(): array
    {
        return [...$this->requiredHeaders(), ...$this->optionalHeaders()];
    }

    /** The "Columns: name, slug" hint. Derived — never hand-written. */
    public function columnHint(): string
    {
        return implode(', ', $this->allHeaders());
    }

    /**
     * @return array{valid: bool, message: string}
     */
    public function validateHeaders(array $headers): array
    {
        $headers = array_map(fn ($h) => strtolower(trim($h)), $headers);

        foreach ($this->requiredHeaders() as $required) {
            if (! in_array($required, $headers, true)) {
                return ['valid' => false, 'message' => "Missing required column: {$required}"];
            }
        }

        return ['valid' => true, 'message' => 'Headers valid'];
    }

    /**
     * "Import Categories and Units first." — built from dependsOn() so the
     * notice can never drift out of sync with the actual dependency list.
     */
    public function dependencyNotice(): ?string
    {
        if ($this->dependsOn() === []) {
            return null;
        }

        $registry = app(ImportTypeRegistry::class);

        $labels = array_map(
            fn (string $key) => $registry->get($key)->label(),
            $this->dependsOn()
        );

        $last = array_pop($labels);
        $list = $labels === [] ? $last : implode(', ', $labels) . ' and ' . $last;

        return "{$list} must be imported first.";
    }
}