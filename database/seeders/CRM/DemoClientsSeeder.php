<?php

namespace Database\Seeders\CRM;

use App\Models\Client;
use App\Models\Company;
use Illuminate\Database\Seeder;

/**
 * Bulk-generates fake suppliers for testing pagination, search & filters.
 *
 * Usage:
 *   php artisan db:seed --class=Database\\Seeders\\CRM\\DemoSuppliersSeeder
 *
 * Optional env overrides:
 *   DEMO_SUPPLIER_COUNT      Number of fakes to create (default: 60)
 *   DEMO_SUPPLIER_COMPANY_ID Company to attach them to (default: first company)
 */
class DemoClientsSeeder extends Seeder
{
    public function run(): void
    {
        $count = 60;

        $companyId = 3;
        if (! $companyId) {
            $companyId = optional(Company::query()->first())->id;
        }

        if (! $companyId) {
            $this->command?->warn('No company found. Skipping DemoSuppliersSeeder.');

            return;
        }

        Client::factory()
            ->count($count)
            ->create(['company_id' => $companyId]);

        $this->command?->info("Seeded {$count} demo suppliers for company #{$companyId}.");
    }
}
