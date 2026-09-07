<?php

namespace App\Console\Commands\Tools;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FetchTableData extends Command
{
    /**
     * The name and signature of the console command.
     * {table} : The name of the table you want to fetch.
     * {--L|limit=5} : The number of rows to fetch (default is 5).
     * {--O|orderBy=id} : The column to order by (default is id).
     * {--D|direction=desc} : The order direction (asc or desc).
     */
    protected $signature = 'db:fetch {table} {--L|limit=5} {--O|orderBy=id} {--D|direction=desc}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch data from any table and output it in a simple key-value text format';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tableName = $this->argument('table');
        $limit = (int) $this->option('limit');
        $orderBy = $this->option('orderBy');
        $direction = $this->option('direction');

        // 1. Validate if the table exists
        if (!Schema::hasTable($tableName)) {
            $this->error("Error: The table '{$tableName}' does not exist in the database.");
            return Command::FAILURE;
        }

        // 2. Fetch the data dynamically
        try {
            // We use try-catch in case the 'orderBy' column doesn't exist on this specific table
            $data = DB::table($tableName)
                ->orderBy($orderBy, $direction)
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            // Fallback: If ordering fails (e.g., no 'id' column), just fetch without ordering
            $data = DB::table($tableName)
                ->limit($limit)
                ->get();
            $this->warn("Note: Could not order by '{$orderBy}'. Displaying default order.");
        }

        // 3. Check if table is empty
        if ($data->isEmpty()) {
            $this->info("The table '{$tableName}' is currently empty.");
            return Command::SUCCESS;
        }

        // 4. Output in Key-Value format
        $this->info("Fetching {$data->count()} row(s) from '{$tableName}':\n");

        foreach ($data as $index => $row) {
            $this->line("<bg=blue;fg=white> --- Row " . ($index + 1) . " --- </>");
            
            foreach ((array) $row as $key => $value) {
                // Handle nulls and long strings nicely
                $displayValue = is_null($value) ? 'NULL' : $value;
                
                // Truncate extremely long text (like JSON or long descriptions) for terminal readability
                if (is_string($displayValue) && strlen($displayValue) > 200) {
                    $displayValue = substr($displayValue, 0, 200) . '... [TRUNCATED]';
                }

                // <comment> makes the key yellow, default line is white
                $this->line("<comment>{$key}</comment>: {$displayValue}");
            }
            $this->newLine();
        }

        return Command::SUCCESS;
    }
}