<?php

namespace Database\Seeders\CRM;

use App\Models\Company;
use App\Models\CrmLeadSource;
use App\Models\CrmPipeline;
use App\Models\CrmStage;
use App\Models\CrmTag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * CrmDefaultSeeder
 *
 * Seeds default CRM setup for a company.
 * Called in two places:
 *   1. DatabaseSeeder (for fresh installs)
 *   2. CompanyRegisteredListener (when a new tenant signs up)
 *
 * Usage:
 *   php artisan db:seed --class=CrmDefaultSeeder
 *
 * For a specific company:
 *   $seeder = new CrmDefaultSeeder();
 *   $seeder->seedForCompany($company->id);
 */
class CrmDefaultSeeder extends Seeder
{
    public function run(): void
    {
        // Seed for all companies that have no CRM setup yet
        Company::all()->each(function (Company $company) {
            if (! CrmPipeline::where('company_id', $company->id)->exists()) {
                $this->seedForCompany($company->id);
            }
        });
    }

    /**
     * Seed default CRM data for a single company.
     * Safe to call multiple times — checks before creating.
     */
    public function seedForCompany(int $companyId): void
    {
        try {
            // ── 1. Default Sales Pipeline ──
            $pipeline = CrmPipeline::firstOrCreate(
                ['company_id' => $companyId, 'name' => 'Sales Pipeline'],
                [
                    'description' => 'Default sales funnel for tracking leads to closure.',
                    'is_default' => true,
                    'is_active' => true,
                    'sort_order' => 1,
                ]
            );

            // ── 2. Default Stages ──
            $stages = [
                ['name' => 'New Lead',    'color' => '#6b7280', 'is_won' => false, 'is_lost' => false, 'sort_order' => 1],
                ['name' => 'Contacted',   'color' => '#3b82f6', 'is_won' => false, 'is_lost' => false, 'sort_order' => 2],
                ['name' => 'Interested',  'color' => '#8b5cf6', 'is_won' => false, 'is_lost' => false, 'sort_order' => 3],
                ['name' => 'Proposal',    'color' => '#f59e0b', 'is_won' => false, 'is_lost' => false, 'sort_order' => 4],
                ['name' => 'Negotiation', 'color' => '#f97316', 'is_won' => false, 'is_lost' => false, 'sort_order' => 5],
                ['name' => 'Won',         'color' => '#10b981', 'is_won' => true,  'is_lost' => false, 'sort_order' => 6],
                ['name' => 'Lost',        'color' => '#ef4444', 'is_won' => false, 'is_lost' => true,  'sort_order' => 7],
            ];

            foreach ($stages as $stage) {
                CrmStage::firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'crm_pipeline_id' => $pipeline->id,
                        'name' => $stage['name'],
                    ],
                    array_merge($stage, [
                        'company_id' => $companyId,
                        'crm_pipeline_id' => $pipeline->id,
                        'is_active' => true,
                    ])
                );
            }

            // ── 3. Default Lead Sources ──
            $sources = [
                ['name' => 'Storefront',  'icon' => 'shopping-bag',     'sort_order' => 1],
                ['name' => 'WhatsApp',    'icon' => 'message-circle',   'sort_order' => 2],
                ['name' => 'Phone Call',  'icon' => 'phone',            'sort_order' => 3],
                ['name' => 'Instagram',   'icon' => 'instagram',        'sort_order' => 4],
                ['name' => 'Referral',    'icon' => 'users',            'sort_order' => 5],
                ['name' => 'Walk-in',     'icon' => 'footprints',       'sort_order' => 6],
                ['name' => 'Google',      'icon' => 'search',           'sort_order' => 7],
                ['name' => 'Facebook',    'icon' => 'facebook',         'sort_order' => 8],
                ['name' => 'Other',       'icon' => 'more-horizontal',  'sort_order' => 9],
            ];

            foreach ($sources as $source) {
                CrmLeadSource::firstOrCreate(
                    ['company_id' => $companyId, 'name' => $source['name']],
                    array_merge($source, ['company_id' => $companyId, 'is_active' => true])
                );
            }

            // ── 4. Default Tags ──
            $tags = [
                ['name' => 'Hot Lead',    'color' => '#ef4444'],
                ['name' => 'Follow Up',   'color' => '#f59e0b'],
                ['name' => 'VIP',         'color' => '#8b5cf6'],
                ['name' => 'Bulk Order',  'color' => '#3b82f6'],
                ['name' => 'Repeat',      'color' => '#10b981'],
            ];

            foreach ($tags as $tag) {
                CrmTag::firstOrCreate(
                    ['company_id' => $companyId, 'name' => $tag['name']],
                    array_merge($tag, ['company_id' => $companyId])
                );
            }

            Log::info('[CrmDefaultSeeder] Seeded for company', [
                'company_id' => $companyId,
                'pipeline_id' => $pipeline->id,
            ]);

        } catch (\Throwable $e) {
            Log::error('[CrmDefaultSeeder] Failed for company', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
