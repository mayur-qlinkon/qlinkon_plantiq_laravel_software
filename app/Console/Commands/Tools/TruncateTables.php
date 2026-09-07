<?php

namespace App\Console\Commands\Tools;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TruncateTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * You can pass tables like:
     * php artisan truncate:tables users orders order_items
     */
    protected $signature = 'truncate:tables {tables?*}';

    /**
     * The console command description.
     */
    protected $description = 'Truncate given tables or default product tables';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tables = $this->argument('tables');

        // Default tables if none provided
        if (empty($tables)) {
            $tables = [
                // 'product_sku_values',
                // 'product_skus',
                // 'products',

                'projects',
                'project_services',
                'project_client_services',
                'project_charges',
                'project_charge_allocations'
            ];
        }

        // Confirmation for safety
        if (! $this->confirm('⚠️ This will permanently delete data. Continue?')) {
            $this->warn('Operation cancelled.');

            return;
        }

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            foreach ($tables as $table) {
                DB::table($table)->truncate();
                $this->info("✅ Truncated: {$table}");
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->info('🎉 All done successfully!');
        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;'); // ensure re-enable
            $this->error('❌ Error: '.$e->getMessage());
        }
    }
}

// php artisan truncate:tables
