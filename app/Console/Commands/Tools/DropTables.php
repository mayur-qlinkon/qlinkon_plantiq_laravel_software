<?php

namespace App\Console\Commands\Tools;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

class DropTables extends Command
{
    /**
     * The name and signature of the console command.
     * You can pass tables directly, OR pass nothing to trigger the interactive list.
     * php artisan drop:tables users orders
     */
    protected $signature = 'drop:tables {tables?*}';

    /**
     * The console command description.
     */
    protected $description = 'Interactively list and drop database tables, and remove their migration files/records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tables = $this->argument('tables');

        // If no tables were provided in the command line, launch interactive mode
        if (empty($tables)) {
            // Fetch all tables from the database (MySQL compatible)
            $tablesInDb = array_map('current', DB::select('SHOW TABLES'));

            // Exclude the 'migrations' table itself from being accidentally dropped
            $tablesInDb = array_filter($tablesInDb, fn($t) => $t !== 'migrations');

            if (empty($tablesInDb)) {
                $this->info('No tables found in the database.');
                return;
            }

            // Display interactive list allowing multiple selections
            $tables = $this->choice(
                'Which tables would you like to drop? (Separate multiple choices with commas, e.g., 1,3,4)',
                array_values($tablesInDb),
                null,
                null,
                true // This 'true' allows multiple selections
            );
        }

        if (empty($tables)) {
            $this->warn('No tables selected. Exiting.');
            return;
        }

        // Display selected tables back to the user
        $this->warn('You have selected the following tables to DROP:');
        foreach ($tables as $table) {
            $this->line("<fg=red>- $table</>");
        }

        // Final Confirmation for safety
        if (! $this->confirm('⚠️ WARNING: This will PERMANENTLY DROP these tables, their structure, and delete their migration files. Are you sure?')) {
            $this->info('Operation cancelled.');
            return;
        }

        try {
            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            foreach ($tables as $table) {
                // 1. Drop the table if it exists
                Schema::dropIfExists($table);
                $this->info("✅ Dropped Table: {$table}");

                // 2. Find associated migration files (matches 'create_tablename_table' convention)
                $migrationRecords = DB::table('migrations')
                    ->where('migration', 'like', "%create_{$table}_table")
                    ->get();

                foreach ($migrationRecords as $record) {
                    // 3. Delete the physical migration file
                    // $filePath = database_path('migrations/' . $record->migration . '.php');
                    // if (File::exists($filePath)) {
                    //     File::delete($filePath);
                    //     $this->line("   <fg=yellow>🗑️ Deleted File:</> {$record->migration}.php");
                    // }

                    // 4. Delete the record from the migrations database table
                    DB::table('migrations')->where('id', $record->id)->delete();
                    $this->line("   <fg=yellow>🧹 Removed DB Record:</> {$record->migration}");
                }
            }

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->info('🎉 All selected tables and their migrations were successfully removed!');
            
        } catch (\Exception $e) {
            // Ensure foreign key checks are re-enabled even if an error occurs
            DB::statement('SET FOREIGN_KEY_CHECKS=1;'); 
            $this->error('❌ Error: '.$e->getMessage());
        }
    }
}