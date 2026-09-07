<?php

namespace App\Imports\Scanners;

use App\Imports\Contracts\ImportScanner;
use App\Models\Product;
use Illuminate\Support\Str;

/**
 * Enforces the plan's product cap before anything is written.
 *
 * The cap itself is resolved by ProductWithSkuImportType and injected, so
 * "what is this tenant's product limit" is answered in exactly one place.
 */
class ProductLimitScanner implements ImportScanner
{
    private array $explicitSlugs = [];
    private array $noSlugNames = [];

    /** @param int|null $limit  Null means the plan does not cap products. */
    public function __construct(private ?int $limit) {}

    public function observe(array $row): void
    {
        $slug = trim($row['slug'] ?? '');

        if ($slug !== '') {
            $this->explicitSlugs[Str::slug($slug)] = true;

            return;
        }

        $name = trim($row['name'] ?? '');
        if ($name !== '') {
            $this->noSlugNames[Str::slug($name)] = true;
        }
    }

    public function reject(string $importMode, int $companyId): ?array
    {
        // Update-only runs create nothing, so the cap cannot be breached.
        if ($this->limit === null || $importMode === 'update_only') {
            return null;
        }

        $existing = Product::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->count();

        // Explicit slugs already in the DB are updates, not creates.
        $existingExplicit = $this->explicitSlugs === [] ? 0 : Product::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereIn('slug', array_keys($this->explicitSlugs))
            ->count();

        // Rows without a slug get a random suffix, so they always create.
        $incomingNew = (count($this->explicitSlugs) - $existingExplicit) + count($this->noSlugNames);
        $available   = max(0, $this->limit - $existing);

        if ($incomingNew <= $available) {
            return null;
        }

        return [
            'error' => "Product limit exceeded. Your plan allows {$this->limit} products. "
                . "You have {$existing} existing and only {$available} slot(s) available, "
                . "but this file would add {$incomingNew} new product(s).",
            'limit_exceeded'     => true,
            'product_limit'      => $this->limit,
            'existing_count'     => $existing,
            'incoming_new_count' => $incomingNew,
            'available_slots'    => $available,
        ];
    }
}