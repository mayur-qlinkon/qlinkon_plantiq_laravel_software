<?php

namespace Database\Seeders\Inventory;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $providedCompanyId = request()->attributes->get('seeder_company_id', 1);

        // Fallback safe: Ensure company exists before seeding
        $company = Company::find($providedCompanyId) ?? Company::factory()->create(['id' => $providedCompanyId]);
        $companyId = $company->id;

        // ALL Plant Categories Required by ProductsSeeder
        $categories = [
            ['name' => 'Indoor Plants'],
            ['name' => 'Outdoor Plants'],
            ['name' => 'Medicinal Plants'],
            ['name' => 'Flowering Plants'],
            ['name' => 'Succulents'],
        ];

        DB::beginTransaction();

        try {
            foreach ($categories as $cat) {
                DB::table('categories')->updateOrInsert(
                    [
                        'company_id' => $companyId,
                        'slug' => Str::slug($cat['name']) . '-' . $companyId,
                    ],
                    [
                        'company_id' => $companyId,
                        'name' => $cat['name'],
                        'slug' => Str::slug($cat['name']) . '-' . $companyId,                        
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            DB::commit();

            if (isset($this->command)) {
                $this->command->info('✅ Plant Categories seeded successfully for Company ID: ' . $companyId);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($this->command)) {
                $this->command->error('❌ CategoriesSeeder Failed: ' . $e->getMessage());
            }
            throw $e;
        }
    }
}