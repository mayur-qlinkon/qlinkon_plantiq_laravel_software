<?php

namespace Database\Seeders\Inventory;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Throwable;

class AttributesSeeder extends Seeder
{
    public function run(): void
    {
        $providedCompanyId = $this->resolveCompanyId();

        $company = $this->resolveOrCreateCompany($providedCompanyId);
        $companyId = $company->id;

        // Clean, essential attributes that actually generate SKUs/Variants
        $attributes = [
            [
                'name' => 'Color',
                'type' => 'color',
                'values' => [
                    ['value' => 'Red',    'color_code' => '#EF4444'],
                    ['value' => 'Green',  'color_code' => '#22C55E'],
                    ['value' => 'Blue',   'color_code' => '#3B82F6'],
                    ['value' => 'Yellow', 'color_code' => '#EAB308'],
                    ['value' => 'Black',  'color_code' => '#000000'],
                    ['value' => 'White',  'color_code' => '#FFFFFF'],
                    ['value' => 'Brown',  'color_code' => '#78350F'],
                ],
            ],
            [
                'name' => 'Size', // Standard sizes for general items, apparel, or tools
                'type' => 'button',
                'values' => [
                    ['value' => 'Small'],
                    ['value' => 'Medium'],
                    ['value' => 'Large'],
                    ['value' => 'Extra Large'],
                ],
            ],
            [
                'name' => 'Pot Size', // Crucial for Nurseries (Pricing usually changes based on this)
                'type' => 'button',
                'values' => [
                    ['value' => '4 inch'],
                    ['value' => '6 inch'],
                    ['value' => '8 inch'],
                    ['value' => '10 inch'],
                    ['value' => '12 inch'],
                    ['value' => 'Grow Bag'],
                ],
            ],
            [
                'name' => 'Material', // Consolidating "Pot Material" to just "Material" for broader business use
                'type' => 'button',
                'values' => [
                    ['value' => 'Plastic'],
                    ['value' => 'Ceramic'],
                    ['value' => 'Terracotta'],
                    ['value' => 'Metal'],
                    ['value' => 'Wood'],
                    ['value' => 'Fiberglass'],
                ],
            ],           
        ];

        DB::beginTransaction();

        try {
            foreach ($attributes as $attr) {
                $attributeId = $this->syncAttribute($companyId, $attr);

                foreach ($attr['values'] as $position => $value) {
                    DB::table('attribute_values')->updateOrInsert(
                        [
                            'company_id' => $companyId,
                            'attribute_id' => $attributeId,
                            'value' => $value['value'],
                        ],
                        [
                            'company_id' => $companyId,
                            'attribute_id' => $attributeId,
                            'value' => $value['value'],
                            'color_code' => $value['color_code'] ?? null,
                            'position' => $position,
                            'is_active' => true,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            }

            DB::commit();

            if (isset($this->command)) {
                $this->command->info('✅ Core business & nursery attributes seeded successfully for Company ID: ' . $companyId);
            }
        } catch (Throwable $e) {
            DB::rollBack();

            if (isset($this->command)) {
                $this->command->error('❌ AttributesSeeder failed: ' . $e->getMessage());
            }

            throw $e;
        }
    }

    private function resolveCompanyId(): int
    {
        $companyId = 1;

        if (app()->bound('request')) {
            $fromRequest = request()->attributes->get('seeder_company_id', 1);

            if (! empty($fromRequest) && is_numeric($fromRequest)) {
                $companyId = (int) $fromRequest;
            }
        }

        return $companyId;
    }

    private function resolveOrCreateCompany(int $companyId): Company
    {
        $company = Company::query()->find($companyId);

        if ($company) {
            return $company;
        }

        try {
            return Company::factory()->create(['id' => $companyId]);
        } catch (Throwable $e) {
            $company = new Company();
            $company->forceFill([
                'id' => $companyId,
                'name' => 'Seeder Company ' . $companyId,
            ]);
            $company->save();

            return $company;
        }
    }

    private function syncAttribute(int $companyId, array $attr): int
    {
        DB::table('attributes')->updateOrInsert(
            [
                'company_id' => $companyId,
                'name' => $attr['name'],
            ],
            [
                'company_id' => $companyId,
                'name' => $attr['name'],
                'type' => $attr['type'],
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return (int) DB::table('attributes')
            ->where('company_id', $companyId)
            ->where('name', $attr['name'])
            ->value('id');
    }
}