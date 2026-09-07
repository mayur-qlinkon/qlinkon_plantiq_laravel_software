<?php

namespace Database\Seeders\Inventory;

use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductsSeeder extends Seeder
{
    public function run(): void
    {
        $providedCompanyId = request()->attributes->get('seeder_company_id');

        /*
        |--------------------------------------------------------------------------
        | 🟢 EASY DATA ENTRY SECTION
        |--------------------------------------------------------------------------
        */
        $productsData = [
            ['name' => 'Snake Plant (Sansevieria)', 'category' => 'Indoor Plants', 'sku' => 'PLANT-SNAKE', 'barcode' => 'NR-000001', 'cost' => 80.00, 'price' => 199.00],
            ['name' => 'Areca Palm (Medium)', 'category' => 'Indoor Plants', 'sku' => 'PLANT-ARECA', 'barcode' => 'NR-000002', 'cost' => 120.00, 'price' => 299.00],
            ['name' => 'Money Plant (Pothos)', 'category' => 'Indoor Plants', 'sku' => 'PLANT-MONEY', 'barcode' => 'NR-000003', 'cost' => 50.00, 'price' => 149.00],
            ['name' => 'Tulsi (Holy Basil)', 'category' => 'Outdoor Plants', 'sku' => 'PLANT-TULSI', 'barcode' => 'NR-000004', 'cost' => 40.00, 'price' => 99.00],
            ['name' => 'Aloe Vera Plant', 'category' => 'Medicinal Plants', 'sku' => 'PLANT-ALOE', 'barcode' => 'NR-000005', 'cost' => 60.00, 'price' => 150.00],
            ['name' => 'Peace Lily (Spathiphyllum)', 'category' => 'Indoor Plants', 'sku' => 'PLANT-PEACE', 'barcode' => 'NR-000006', 'cost' => 110.00, 'price' => 249.00],
            ['name' => 'Bougainvillea (Pink/Red)', 'category' => 'Outdoor Plants', 'sku' => 'PLANT-BOUG', 'barcode' => 'NR-000007', 'cost' => 150.00, 'price' => 350.00],
            ['name' => 'Spider Plant (Chlorophytum)', 'category' => 'Indoor Plants', 'sku' => 'PLANT-SPIDER', 'barcode' => 'NR-000008', 'cost' => 65.00, 'price' => 179.00],
            ['name' => 'Hibiscus (Gudhal)', 'category' => 'Flowering Plants', 'sku' => 'PLANT-HIBIS', 'barcode' => 'NR-000009', 'cost' => 85.00, 'price' => 199.00],
            ['name' => 'ZZ Plant (Zamioculcas)', 'category' => 'Indoor Plants', 'sku' => 'PLANT-ZZ', 'barcode' => 'NR-000010', 'cost' => 180.00, 'price' => 399.00],
            ['name' => 'Neem Tree Sapling', 'category' => 'Medicinal Plants', 'sku' => 'PLANT-NEEM', 'barcode' => 'NR-000011', 'cost' => 45.00, 'price' => 120.00],
            ['name' => 'Rose (Desi Gulab)', 'category' => 'Flowering Plants', 'sku' => 'PLANT-ROSE', 'barcode' => 'NR-000012', 'cost' => 90.00, 'price' => 220.00],
            ['name' => 'Rubber Plant (Ficus elastica)', 'category' => 'Indoor Plants', 'sku' => 'PLANT-RUBBER', 'barcode' => 'NR-000013', 'cost' => 140.00, 'price' => 329.00],
            ['name' => 'Curry Leaf Plant (Kadi Patta)', 'category' => 'Outdoor Plants', 'sku' => 'PLANT-CURRY', 'barcode' => 'NR-000014', 'cost' => 55.00, 'price' => 135.00],
            ['name' => 'Jade Plant (Crassula)', 'category' => 'Succulents', 'sku' => 'PLANT-JADE', 'barcode' => 'NR-000015', 'cost' => 70.00, 'price' => 189.00],
        ];

        DB::beginTransaction();

        try {
            $company = $this->resolveCompany($providedCompanyId);
            $companyId = $company->id;

            // Resolve base dependencies safely
            $unit = $this->resolveUnit($companyId);
            $supplier = $this->resolveSupplier($companyId);

            // Loop through our easy data entry array
            foreach ($productsData as $item) {

                // 1. STRICT CATEGORY RESOLUTION: Must exist beforehand
                $category = $this->resolveCategoryByName($companyId, $item['category']);

                // 2. Create the Product
                $product = Product::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'slug' => Str::slug($item['name']) . '-' . $companyId,
                    ],
                    [
                        'company_id' => $companyId,
                        'category_id' => $category->id,
                        'supplier_id' => $supplier->id,
                        'name' => $item['name'],
                        'type' => 'single',
                        'product_unit_id' => $unit->id,
                        'sale_unit_id' => $unit->id,
                        'purchase_unit_id' => $unit->id,
                    ]
                );

                // 3. Create the Product SKU
                ProductSku::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'product_id' => $product->id,
                        'sku' => $item['sku'] . '-' . $companyId,
                    ],
                    [
                        'company_id' => $companyId,
                        'unit_id' => $unit->id,
                        'barcode' => $item['barcode'] . '-' . $companyId,
                        'cost' => $item['cost'],
                        'price' => $item['price'],
                    ]
                );
            }

            DB::commit();

            if (isset($this->command)) {
                $this->command->info('✅ ' . count($productsData) . " Nursery products seeded successfully for Company ID: {$companyId}");
            }
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('ProductsSeeder Failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            if (isset($this->command)) {
                $this->command->error('❌ Failed to seed products: ' . $e->getMessage());
            }

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    private function resolveCompany(?int $providedCompanyId): Company
    {
        if ($providedCompanyId) {
            return Company::find($providedCompanyId)
                ?? Company::factory()->create(['id' => $providedCompanyId]);
        }

        return Company::first() ?? Company::factory()->create();
    }

    private function resolveUnit(int $companyId): Unit
    {
        return Unit::firstOrCreate(
            ['company_id' => $companyId, 'short_name' => 'pcs'],
            ['name' => 'Pieces']
        );
    }

    private function resolveSupplier(int $companyId): Supplier
    {
        return Supplier::firstOrCreate(
            ['company_id' => $companyId, 'name' => 'GreenGrow Nursery Supplies'],
            ['email' => 'contact@greengrow.com', 'phone' => '9876543210']
        );
    }

    // 🔥 STRICT Category Resolver: Now throws an error instead of creating!
    private function resolveCategoryByName(int $companyId, string $name): Category
    {
        $category = Category::where('company_id', $companyId)
            ->where('name', $name)
            ->first();

        if (!$category) {
            throw new \Exception("Category '{$name}' not found for Company ID {$companyId}. You MUST run CategoriesSeeder before running ProductsSeeder.");
        }

        return $category;
    }
}