<?php

namespace Database\Seeders\HRM;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DesignationSeeder extends Seeder
{
    public function run(): void
    {
        // Dynamically grab the company ID injected by the Visual Seeder Platform
        $companyId = request()->attributes->get('seeder_company_id', 1);

        // Level indicates hierarchy: 1 = Entry Level, 5 = C-Level/Executive
        $designations = [                    
            ['name' => 'General Manager', 'level' => 4, 'sort_order' => 3],
            ['name' => 'Department Head', 'level' => 4, 'sort_order' => 4],
            ['name' => 'Project Manager', 'level' => 3, 'sort_order' => 5],
            ['name' => 'Team Lead', 'level' => 3, 'sort_order' => 6],
            ['name' => 'Senior Executive', 'level' => 2, 'sort_order' => 7],
            ['name' => 'Executive', 'level' => 1, 'sort_order' => 8],
            ['name' => 'Trainee / Intern', 'level' => 1, 'sort_order' => 9],
        ];

        DB::beginTransaction();

        try {
            foreach ($designations as $designation) {
                DB::table('designations')->updateOrInsert(
                    [
                        'company_id' => $companyId,
                        'name' => $designation['name'],
                    ],
                    array_merge($designation, [
                        'description' => 'Standard company designation',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }

            DB::commit();
            if (isset($this->command)) {
                $this->command->info("✅ HRM Designations seeded successfully for Company ID: {$companyId}");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($this->command)) {
                $this->command->error('❌ Failed to seed designations: '.$e->getMessage());
            }
        }
    }
}
