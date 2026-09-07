<?php

namespace Database\Seeders\Production;

use App\Models\Production\BatchPlacement;
use App\Models\Production\GrowingSpace;
use App\Models\Production\GrowingSpaceType;
use App\Models\Production\PlantBatch;
use App\Models\Production\ProductionSite;
use App\Models\Production\Zone;
use App\Models\ProductSku;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo nursery data for the Production Map.
 *
 * The shape is deliberate — it exercises every case the map has to survive:
 * three levels of zone nesting, count and area based capacity units side by
 * side, one space holding several batches at once, an over-capacity space,
 * long-empty spaces, and a batch old enough to trip the stale warning. Tidy
 * uniform data would look fine and prove nothing.
 *
 * Usage:
 *   php artisan db:seed --class="Database\Seeders\ProductionDemoSeeder"
 */
class ProductionDemoSeeder extends Seeder
{
    // ── Set these two, then run ──────────────────────────────
    private const COMPANY_ID = 1;

    /** null = the company's first store. */
    private const STORE_ID = 1;

    /** true = delete this company's existing production data first. */
    private const WIPE_FIRST = true;
    // ─────────────────────────────────────────────────────────

    /**
     * Age of the deliberately overdue batch. Comfortably past the map's
     * 180-day threshold so the stale warning is guaranteed to show.
     */
    private const STALE_BATCH_AGE = 210;

    private int $companyId;
    private int $storeId;
    private ?int $userId;

    public function run(): void
    {
        $this->companyId = self::COMPANY_ID;

        $store = Store::where('company_id', $this->companyId)
            ->when(self::STORE_ID, fn ($q) => $q->whereKey(self::STORE_ID))
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->first();

        if (! $store) {
            $this->command?->error("No store found for company #{$this->companyId}. Create one first.");

            return;
        }

        $this->storeId = $store->id;
        $this->userId = User::where('company_id', $this->companyId)->orderBy('id')->value('id');

        $skus = $this->resolveSkus();

        if ($skus->isEmpty()) {
            $this->command?->error("No products found for company #{$this->companyId}. Seed products first.");

            return;
        }

        DB::transaction(function () use ($skus, $store) {
            if (self::WIPE_FIRST) {
                $this->wipe();
            }

            $types = $this->seedSpaceTypes();
            $spaces = $this->seedLayout($types);
            $this->seedBatches($spaces, $skus);

            $this->command?->info("Done — company #{$this->companyId}, store [{$store->name}]");
            $this->command?->info(count($spaces).' growing spaces created.');
        });
    }

    /**
     * Existing SKUs are reused rather than invented, because a batch points at a
     * real product and the map shows that product's name.
     */
    private function resolveSkus()
    {
        return ProductSku::where('company_id', $this->companyId)
            ->with('product:id,name')
            ->whereHas('product')
            ->orderBy('id')
            ->limit(8)
            ->get();
    }

    private function wipe(): void
    {
        BatchPlacement::where('company_id', $this->companyId)->delete();
        PlantBatch::where('company_id', $this->companyId)->forceDelete();
        GrowingSpace::where('company_id', $this->companyId)->forceDelete();
        Zone::where('company_id', $this->companyId)->forceDelete();
        ProductionSite::where('company_id', $this->companyId)->forceDelete();

        $this->command?->warn('Existing production data cleared.');
    }

    /** One type per capacity unit, so the map has to handle all of them. */
    private function seedSpaceTypes(): array
    {
        $defs = [
            ['Greenhouse Bench', GrowingSpaceType::CAPACITY_UNIT_POTS],
            ['Open Field Bed', GrowingSpaceType::CAPACITY_UNIT_AREA_SQM],
            ['Hydroponic Channel', GrowingSpaceType::CAPACITY_UNIT_LINEAR_M],
            ['Propagation Tray', GrowingSpaceType::CAPACITY_UNIT_TRAY_CELLS],
            ['Shade Net Rack', GrowingSpaceType::CAPACITY_UNIT_POTS],
        ];

        $types = [];

        foreach ($defs as $i => [$name, $unit]) {
            $types[$name] = GrowingSpaceType::updateOrCreate(
                ['company_id' => $this->companyId, 'name' => $name],
                ['capacity_unit' => $unit, 'is_active' => true, 'sort_order' => $i + 1],
            );
        }

        return $types;
    }

    /**
     * Two sites with intentionally different nesting depth — one goes three
     * levels deep, which is exactly what a fixed-depth renderer would break on.
     */
    private function seedLayout(array $types): array
    {
        $spaces = [];

        // ── Site 1: North Nursery ──
        $north = $this->site('North Nursery', 'Main propagation and hardening area', 1);

        $blockA = $this->zone($north, null, 'Block A', 1);
        $row1 = $this->zone($north, $blockA, 'Row 1', 1);
        $row2 = $this->zone($north, $blockA, 'Row 2', 2);
        $shade = $this->zone($north, $row1, 'Shade Section A1', 1); // third level

        $blockB = $this->zone($north, null, 'Block B', 2);
        $row3 = $this->zone($north, $blockB, 'Row 3', 1);

        $spaces[] = $this->space($north, $row1, $types['Open Field Bed'], 'Bed A1', 50, 1);
        $spaces[] = $this->space($north, $row1, $types['Open Field Bed'], 'Bed A2', 50, 2);
        $spaces[] = $this->space($north, $row1, $types['Open Field Bed'], 'Bed A3', 40, 3);
        $spaces[] = $this->space($north, $row1, $types['Greenhouse Bench'], 'Bench A1-1', 120, 4);

        $spaces[] = $this->space($north, $shade, $types['Shade Net Rack'], 'SH-A1-01', 60, 1);
        $spaces[] = $this->space($north, $shade, $types['Shade Net Rack'], 'SH-A1-02', 60, 2);
        $spaces[] = $this->space($north, $shade, $types['Propagation Tray'], 'Tray-A1-01', 200, 3);

        $spaces[] = $this->space($north, $row2, $types['Open Field Bed'], 'Bed A4', 50, 1);
        $spaces[] = $this->space($north, $row2, $types['Open Field Bed'], 'Bed A5', 50, 2);
        $spaces[] = $this->space($north, $row2, $types['Greenhouse Bench'], 'Bench A2-1', 100, 3);
        $spaces[] = $this->space($north, $row2, $types['Greenhouse Bench'], 'Bench A2-2', 100, 4);
        $spaces[] = $this->space($north, $row2, $types['Hydroponic Channel'], 'HYDRO-A2-1', 30, 5);

        $spaces[] = $this->space($north, $row3, $types['Greenhouse Bench'], 'GH1-R3-C5', 60, 1);
        $spaces[] = $this->space($north, $row3, $types['Greenhouse Bench'], 'GH1-R3-C6', 60, 2);
        $spaces[] = $this->space($north, $row3, $types['Greenhouse Bench'], 'GH1-R3-C7', 60, 3);

        // Attached straight to the site, with no zone — the map must still show it.
        $spaces[] = $this->space($north, null, $types['Propagation Tray'], 'QUARANTINE-01', 150, 99);

        // ── Site 2: Polyhouse Facility ──
        $poly = $this->site('Polyhouse Facility', 'Climate controlled production', 2);

        $ph1 = $this->zone($poly, null, 'Polyhouse 1', 1);
        $ph2 = $this->zone($poly, null, 'Polyhouse 2', 2);

        foreach (range(1, 5) as $i) {
            $spaces[] = $this->space($poly, $ph1, $types['Greenhouse Bench'], "PH1-B{$i}", 80, $i);
        }

        foreach (range(1, 4) as $i) {
            $spaces[] = $this->space($poly, $ph2, $types['Hydroponic Channel'], "PH2-CH{$i}", 25, $i);
        }

        $spaces[] = $this->space($poly, $ph2, $types['Propagation Tray'], 'PH2-TRAY-01', 288, 5);

        return $spaces;
    }

    /**
     * Batches are laid out so each interesting state actually appears:
     * over-capacity, multi-batch, stale, partially full, and plenty of empties.
     */
    private function seedBatches(array $spaces, $skus): void
    {
        $byName = collect($spaces)->keyBy('name');
        $n = 1;

        // ── The awkward cases the map exists to surface ──

        // Over capacity: 110 plants in a 100-pot bench.
        $this->batch($n++, $skus[0], 110, 45, $byName['Bench A2-1']);

        // Two batches sharing one space — both must be visible, not "+1 more".
        $this->batch($n++, $skus[1 % $skus->count()], 35, 60, $byName['GH1-R3-C5']);
        $this->batch($n++, $skus[2 % $skus->count()], 20, 20, $byName['GH1-R3-C5']);

        // Sitting far too long — trips the 180-day warning.
        $this->batch($n++, $skus[3 % $skus->count()], 70, self::STALE_BATCH_AGE, $byName['Bench A1-1']);

        // Area-based space: capacity is in sqm, quantity in plants, so no ratio.
        $this->batch($n++, $skus[4 % $skus->count()], 240, 30, $byName['Bed A1']);

        // Linear-metre channel.
        $this->batch($n++, $skus[5 % $skus->count()], 180, 25, $byName['HYDRO-A2-1']);

        // Exactly full.
        $this->batch($n++, $skus[0], 60, 15, $byName['SH-A1-01']);

        // Comfortably partial.
        $this->batch($n++, $skus[1 % $skus->count()], 150, 12, $byName['Tray-A1-01']);
        $this->batch($n++, $skus[2 % $skus->count()], 55, 40, $byName['PH1-B1']);
        $this->batch($n++, $skus[3 % $skus->count()], 30, 8,  $byName['PH1-B2']);
        $this->batch($n++, $skus[4 % $skus->count()], 78, 90, $byName['PH1-B3']);
        $this->batch($n++, $skus[5 % $skus->count()], 12, 5,  $byName['PH2-CH1']);
        $this->batch($n++, $skus[0], 200, 18, $byName['PH2-TRAY-01']);
        $this->batch($n++, $skus[1 % $skus->count()], 90, 35, $byName['QUARANTINE-01']);

        // ── Closed batches, so some spaces read as "empty for N days" ──
        $this->closedBatch($n++, $skus[2 % $skus->count()], 40, 120, 22, $byName->get('Bed A4'));
        $this->closedBatch($n++, $skus[3 % $skus->count()], 55, 200, 60, $byName->get('Bed A5'));
    }

    // ── builders ─────────────────────────────────────────────

    private function site(string $name, string $desc, int $order): ProductionSite
    {
        return ProductionSite::create([
            'company_id' => $this->companyId,
            'store_id' => $this->storeId,
            'name' => $name,
            'description' => $desc,
            'is_active' => true,
            'sort_order' => $order,
        ]);
    }

    private function zone(ProductionSite $site, ?Zone $parent, string $name, int $order): Zone
    {
        return Zone::create([
            'company_id' => $this->companyId,
            'production_site_id' => $site->id,
            'parent_id' => $parent?->id,
            'name' => $name,
            'is_active' => true,
            'sort_order' => $order,
        ]);
    }

    private function space(ProductionSite $site, ?Zone $zone, GrowingSpaceType $type, string $name, float $capacity, int $order): GrowingSpace
    {
        return GrowingSpace::create([
            'company_id' => $this->companyId,
            'production_site_id' => $site->id,
            'zone_id' => $zone?->id,
            'growing_space_type_id' => $type->id,
            'name' => $name,
            'capacity' => $capacity,
            'is_active' => true,
            'sort_order' => $order,
        ]);
    }

    /** An active batch, currently placed. */
    private function batch(int $n, ProductSku $sku, int $qty, int $ageDays, GrowingSpace $space): void
    {
        $started = now()->subDays($ageDays);

        $batch = PlantBatch::create([
            'company_id' => $this->companyId,
            'batch_code' => 'PB-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'product_id' => $sku->product_id,
            'product_sku_id' => $sku->id,
            'source_type' => 'opening_stock',
            'initial_quantity' => (int) round($qty * 1.1),
            'current_quantity' => $qty,
            'status' => 'active',
            'batch_start_datetime' => $started,
            'notes' => 'Demo batch',
            'created_by' => $this->userId,
        ]);

        BatchPlacement::create([
            'company_id' => $this->companyId,
            'plant_batch_id' => $batch->id,
            'growing_space_id' => $space->id,
            'placed_at' => $started,
            'ended_at' => null,
            'placed_by' => $this->userId,
        ]);
    }

    /**
     * A finished batch whose placement has ended. This is what gives an empty
     * space its "empty for N days" reading — that value is derived, not stored.
     */
    private function closedBatch(int $n, ProductSku $sku, int $qty, int $ageDays, int $emptyDays, GrowingSpace $space): void
    {
        $started = now()->subDays($ageDays);
        $ended = now()->subDays($emptyDays);

        $batch = PlantBatch::create([
            'company_id' => $this->companyId,
            'batch_code' => 'PB-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'product_id' => $sku->product_id,
            'product_sku_id' => $sku->id,
            'source_type' => 'opening_stock',
            'initial_quantity' => $qty,
            'current_quantity' => 0,
            'status' => 'closed',
            'batch_start_datetime' => $started,
            'batch_end_datetime' => $ended,
            'notes' => 'Demo batch — harvested',
            'created_by' => $this->userId,
        ]);

        BatchPlacement::create([
            'company_id' => $this->companyId,
            'plant_batch_id' => $batch->id,
            'growing_space_id' => $space->id,
            'placed_at' => $started,
            'ended_at' => $ended,
            'placed_by' => $this->userId,
        ]);
    }
}