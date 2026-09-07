<?php

namespace App\Imports;

use App\Imports\Contracts\ImportType;
use App\Imports\Types\CategoryImportType;
use App\Imports\Types\ClientImportType;
use App\Imports\Types\ProductWithSkuImportType;
use App\Imports\Types\SupplierImportType;
use App\Imports\Types\UnitImportType;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * The single place a new import type is registered.
 *
 * Order matters: it is the recommended import order shown to the user, and
 * dependencies must precede their dependents.
 */
class ImportTypeRegistry
{
    /** @var list<class-string<ImportType>> */
    private const TYPES = [
        CategoryImportType::class,
        UnitImportType::class,
        ProductWithSkuImportType::class,
        ClientImportType::class,
        SupplierImportType::class,
    ];

    /** @var array<string, ImportType>|null */
    private ?array $resolved = null;

    /** @return Collection<string, ImportType> */
    public function all(): Collection
    {
        if ($this->resolved === null) {
            $this->resolved = [];

            foreach (self::TYPES as $class) {
                $type = app($class);
                $this->resolved[$type->key()] = $type;
            }
        }

        return collect($this->resolved);
    }

    public function get(string $key): ImportType
    {
        return $this->all()->get($key)
            ?? throw new InvalidArgumentException("Unknown import type: {$key}");
    }

    public function has(string $key): bool
    {
        return $this->all()->has($key);
    }

    /**
     * Types the current user may actually run. Used by the page, the modal
     * and the controller alike, so a permission can never be enforced in one
     * of those and forgotten in another.
     *
     * @return Collection<string, ImportType>
     */
    public function availableToUser(): Collection
    {
        return $this->all()->filter(
            fn (ImportType $type) => $type->permission() === null
                || has_permission($type->permission())
        );
    }
}