<?php

namespace Database\Seeders\CRM;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CrmStageSeeder extends Seeder
{
    public function run(): void
    {
        // Dynamically grab the company ID injected by the Visual Seeder Platform or fallback to 1
        $companyId = request()->attributes->get('seeder_company_id', 1);

        // Example default pipeline ID; adjust according to your existing CRM pipelines
        $defaultPipelineId = request()->attributes->get('seeder_pipeline_id', 1);

        // Define CRM stages
        $stages = [
            [
                'name' => 'New',
                'color' => '#6b7280',
                'is_won' => false,
                'is_lost' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Contacted',
                'color' => '#3b82f6',
                'is_won' => false,
                'is_lost' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'Qualified',
                'color' => '#f59e0b',
                'is_won' => false,
                'is_lost' => false,
                'sort_order' => 3,
            ],
            [
                'name' => 'Proposal',
                'color' => '#10b981',
                'is_won' => false,
                'is_lost' => false,
                'sort_order' => 4,
            ],
            [
                'name' => 'Won',
                'color' => '#14b8a6',
                'is_won' => true,
                'is_lost' => false,
                'sort_order' => 5,
            ],
            [
                'name' => 'Lost',
                'color' => '#ef4444',
                'is_won' => false,
                'is_lost' => true,
                'sort_order' => 6,
            ],
        ];

        DB::beginTransaction();

        try {
            foreach ($stages as $stage) {
                DB::table('crm_stages')->updateOrInsert(
                    [
                        'company_id' => $companyId,
                        'crm_pipeline_id' => $defaultPipelineId,
                        'name' => $stage['name'],
                    ],
                    array_merge($stage, [
                        'company_id' => $companyId,
                        'crm_pipeline_id' => $defaultPipelineId,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }

            DB::commit();

            if (isset($this->command)) {
                $this->command->info("✅ CRM Stages seeded successfully for Company ID: {$companyId}, Pipeline ID: {$defaultPipelineId}");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($this->command)) {
                $this->command->error('❌ Failed to seed CRM stages: '.$e->getMessage());
            }
        }
    }
}
