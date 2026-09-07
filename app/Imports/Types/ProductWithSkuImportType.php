<?php

namespace App\Imports\Types;

use App\Imports\AbstractImportType;
use App\Imports\Contracts\ImportScanner;
use App\Imports\ImportContext;
use App\Imports\ImportResult;
use App\Imports\Scanners\ProductLimitScanner;
use App\Models\Company;
use App\Models\Product;
use App\Services\Import\ProductWithSkuImporter;
use Illuminate\Support\Facades\Auth;
/**
 * The only plan-limited import type, and the reason ImportContext exists:
 * its processChunk() needs a store (for the stock ledger) and a slot budget
 * that the others do not.
 */
class ProductWithSkuImportType extends AbstractImportType
{
    public function __construct(private ProductWithSkuImporter $importer) {}

    public function key(): string
    {
        return 'product_with_skus';
    }

    public function label(): string
    {
        return 'Products + SKUs';
    }

    // No dependencies: missing categories and units are created on the fly.
    // The amber notice and the "Existing …" links are derived from this list,
    // so both disappear on their own.

    public function helpText(): ?string
    {
        // Kept to the one rule a user cannot guess. Column-level detail lives
        // in the sample CSV, where it is shown in context instead of prose.
        return 'One row per SKU. If a product has several variants, repeat the '
            . 'product columns on each of its rows and keep the <code>slug</code> the same.';
    }

    public function requiredHeaders(): array
    {
        return ['name', 'slug', 'category_slug', 'price', 'cost'];
    }

    public function optionalHeaders(): array
    {
        return [
            // Product fields
            'unit', 'product_type', 'description',
            // SKU fields
            'sku', 'mrp', 'barcode', 'stock_alert', 'warehouse_name', 'stock_qty',
            // Variant attributes (up to 5 pairs)
            'attribute_1_name', 'attribute_1_value',
            'attribute_2_name', 'attribute_2_value',
            // Product guide pairs — last, so the SKU columns stay reachable
            'title1', 'value1', 'title2', 'value2', 'title3', 'value3',
        ];
    }

    /**
     * The sample CSV column order differs from requiredHeaders() + optional,
     * so it is spelled out rather than derived: readability of the downloaded
     * file matters more here than avoiding one repetition.
     */
    public function sampleHeaders(): array
    {
        return [
            'name', 'slug', 'category_slug', 'unit', 'product_type', 'description',
            'sku', 'price', 'cost', 'mrp', 'barcode', 'stock_alert',
            'warehouse_name', 'stock_qty',
            'attribute_1_name', 'attribute_1_value',
            'attribute_2_name', 'attribute_2_value',
            'title1', 'value1', 'title2', 'value2', 'title3', 'value3',
        ];
    }

    public function sampleRows(): array
    {
        return [
            [
                'Rose Plant', 'rose-plant', 'flowers', 'pcs', 'sellable', 'Beautiful flowering plant',
                'RP-RED-SM', '199', '120', '249', '', '5', 'Main Warehouse', '10',
                'Color', 'Red', 'Size', 'Small',
                'Sunlight', 'Full sun', 'Watering', 'Once a week', 'Care Level', 'Easy',
            ],
            [
                'Spider Plant', 'spider-plant', 'indoor', 'pcs', 'sellable', 'Beautiful indoor plant',
                'SP-RED-LG', '249', '150', '299', '', '3', 'Main Warehouse', '5',
                'Color', 'Red', 'Size', 'Large',
                'Sunlight', 'Full sun', 'Watering', 'Once a week', 'Care Level', 'Easy',
            ],
            [
                'Cactus Mini', 'cactus-mini', 'succulents', 'pcs', 'sellable', 'Easy care desktop succulent',
                'CAC-001', '99', '60', '129', '', '5', 'Main Warehouse', '20',
                '', '', '', '',
                'Sunlight', 'Full sun', 'Watering', 'Once a week', 'Care Level', 'Easy',
            ],
        ];
    }

    /**
     * Absorbed from BulkImportController, where the same calculation appeared
     * twice and was keyed on a type string.
     *
     * Real runs need no offset — the DB count grows as each chunk commits.
     * Dry runs write nothing, so already-simulated creates must be subtracted
     * by hand or the limit never appears to be reached.
     */
    public function isPlanLimited(): bool
    {
        return true;
    }

    /**
     * The live reference page and the full data export, on top of the
     * per-dependency exports the base class already provides.
     */
    public function extraLinks(): array
    {
        return [
            ...parent::extraLinks(),
            [
                'href'  => route('admin.bulk-import.reference'),
                'label' => 'Live Reference',
                'icon'  => 'layout',
                'color' => 'purple',
            ],
            [
                'href'  => route('admin.bulk-import.export-all'),
                'label' => 'Existing Data',
                'icon'  => 'database',
                'color' => 'blue',
            ],
        ];
    }

    public function newScanner(): ImportScanner
    {
        return new ProductLimitScanner($this->productLimit(Auth::user()->company_id));
    }

    /** The plan's product cap, or null when uncapped. Resolved once, here. */
    private function productLimit(int $companyId): ?int
    {
        return Company::find($companyId)?->subscription?->plan?->product_limit;
    }

    public function remainingSlots(int $companyId, bool $isDryRun, int $alreadySimulated): int
    {
        $limit = $this->productLimit($companyId);

        if ($limit === null) {
            return PHP_INT_MAX;   // Plan does not cap products.
        }

        $existing = Product::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->count();

        return max(0, $limit - $existing - ($isDryRun ? $alreadySimulated : 0));
    }

    public function extractUniqueKey(array $row): ?string
    {
        return $this->importer->extractUniqueKey($row);
    }

    public function processChunk(array $rows, int $startRow, ImportContext $context): ImportResult
    {
        return ImportResult::fromArray(
            $this->importer->processChunk(
                $context->import,
                $rows,
                $startRow,
                $context->companyId,
                $context->remainingSlots
            )
        );
    }
}