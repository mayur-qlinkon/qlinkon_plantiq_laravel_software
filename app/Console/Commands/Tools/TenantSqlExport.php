<?php

namespace App\Console\Commands\Tools;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TenantSqlExport extends Command
{
    protected $signature = 'tenant:sql-export
                            {company_id : The company ID to export}
                            {--output= : Custom output file path. Default: storage/exports/tenant_ID_DATE.sql}
                            {--chunk=500 : Rows per INSERT batch (default: 500)}
                            {--skip-fk-check : Skip SET FOREIGN_KEY_CHECKS (not recommended)}';

    protected $description = 'Export all data for a specific tenant (company_id) as a safe SQL file';

    // ─────────────────────────────────────────────────────────────────────────
    // TABLE EXPORT ORDER (dependency-aware: parents before children)
    // type: 'direct'  → table has company_id column
    // type: 'pivot'   → table linked via parent FK (no company_id)
    // parent_col      → FK column name in pivot table that links to parent
    // parent_table    → parent table name (must appear earlier in this list)
    // ─────────────────────────────────────────────────────────────────────────
    protected array $exportMap = [
        // ── LAYER 1: Lookups ──
        ['table' => 'units',                        'type' => 'direct'],
        ['table' => 'attributes',                   'type' => 'direct'],
        ['table' => 'attribute_values',             'type' => 'direct'],
        ['table' => 'categories',                   'type' => 'direct'],
        ['table' => 'warehouses',                   'type' => 'direct'],
        ['table' => 'payment_methods',              'type' => 'direct'],        
        ['table' => 'settings',                     'type' => 'direct'],
        ['table' => 'pages',                        'type' => 'direct'],
        ['table' => 'banners',                      'type' => 'direct'],

        // ── LAYER 2: People ──
        ['table' => 'users',                        'type' => 'direct'],
        ['table' => 'roles',                        'type' => 'direct'],
        // permissions → global table (no company_id), skip export
        ['table' => 'permission_role',              'type' => 'pivot', 'parent_col' => 'role_id',              'parent_table' => 'roles'],
        ['table' => 'role_user',                    'type' => 'pivot', 'parent_col' => 'user_id',              'parent_table' => 'users'],
        ['table' => 'stores',                       'type' => 'direct'],
        ['table' => 'store_user',                   'type' => 'pivot', 'parent_col' => 'store_id',             'parent_table' => 'stores'],
        ['table' => 'clients',                      'type' => 'direct'],
        ['table' => 'suppliers',                    'type' => 'direct'],

        // ── LAYER 3: Inventory ──
        ['table' => 'products',                     'type' => 'direct'],
        ['table' => 'product_skus',                 'type' => 'direct'],
        ['table' => 'product_sku_values',           'type' => 'pivot', 'parent_col' => 'product_sku_id',       'parent_table' => 'product_skus'],
        ['table' => 'product_batches',              'type' => 'direct'],
        ['table' => 'product_stocks',               'type' => 'direct'],
        ['table' => 'stock_movements',              'type' => 'direct'],
        ['table' => 'product_media',                'type' => 'direct'],
        ['table' => 'category_products',            'type' => 'pivot', 'parent_col' => 'category_id',          'parent_table' => 'categories'],

        // ── LAYER 4: Storefront ──
        ['table' => 'storefront_sections',          'type' => 'direct'],
        ['table' => 'storefront_section_products',  'type' => 'pivot', 'parent_col' => 'storefront_section_id', 'parent_table' => 'storefront_sections'],

        // ── LAYER 5: Purchases ──
        ['table' => 'purchases',                    'type' => 'direct'],
        ['table' => 'purchase_items',               'type' => 'direct'],
        ['table' => 'purchase_returns',             'type' => 'direct'],
        ['table' => 'purchase_return_items',        'type' => 'pivot', 'parent_col' => 'purchase_return_id',  'parent_table' => 'purchase_returns'],

        // ── LAYER 6: Sales ──
        ['table' => 'quotations',                   'type' => 'direct'],
        ['table' => 'quotation_items',              'type' => 'pivot', 'parent_col' => 'quotation_id',         'parent_table' => 'quotations'],
        ['table' => 'invoices',                     'type' => 'direct'],
        ['table' => 'invoice_items',                'type' => 'pivot', 'parent_col' => 'invoice_id',           'parent_table' => 'invoices'],
        ['table' => 'invoice_returns',              'type' => 'direct'],
        ['table' => 'invoice_return_items',         'type' => 'pivot', 'parent_col' => 'invoice_return_id',    'parent_table' => 'invoice_returns'],
        ['table' => 'orders',                       'type' => 'direct'],
        ['table' => 'order_items',                  'type' => 'pivot', 'parent_col' => 'order_id',             'parent_table' => 'orders'],
        ['table' => 'order_status_histories',       'type' => 'pivot', 'parent_col' => 'order_id',             'parent_table' => 'orders'],
        ['table' => 'challans',                     'type' => 'direct'],
        ['table' => 'challan_items',                'type' => 'pivot', 'parent_col' => 'challan_id',           'parent_table' => 'challans'],
        ['table' => 'challan_status_history',       'type' => 'pivot', 'parent_col' => 'challan_id',           'parent_table' => 'challans'],
        ['table' => 'challan_returns',              'type' => 'direct'],
        ['table' => 'challan_return_items',         'type' => 'pivot', 'parent_col' => 'challan_return_id',    'parent_table' => 'challan_returns'],
        ['table' => 'payments',                     'type' => 'direct'],

        // ── LAYER 7: CRM ──
        ['table' => 'crm_pipelines',                'type' => 'direct'],
        ['table' => 'crm_stages',                   'type' => 'direct'],
        ['table' => 'crm_lead_sources',             'type' => 'direct'],
        ['table' => 'crm_tags',                     'type' => 'direct'],
        ['table' => 'crm_leads',                    'type' => 'direct'],
        ['table' => 'crm_lead_assignees',           'type' => 'pivot', 'parent_col' => 'crm_lead_id',          'parent_table' => 'crm_leads'],
        ['table' => 'crm_lead_tags',                'type' => 'pivot', 'parent_col' => 'crm_lead_id',          'parent_table' => 'crm_leads'],
        ['table' => 'crm_activities',               'type' => 'direct'],
        ['table' => 'crm_tasks',                    'type' => 'direct'],

        // ── LAYER 8: Finance ──
        ['table' => 'expense_categories',           'type' => 'direct'],
        ['table' => 'expenses',                     'type' => 'direct'],

        // ── LAYER 9: HRM ──
        ['table' => 'departments',                  'type' => 'direct'],
        ['table' => 'shifts',                       'type' => 'direct'],
        ['table' => 'designations',                 'type' => 'direct'],
        ['table' => 'employees',                    'type' => 'direct'],
        ['table' => 'leave_types',                  'type' => 'direct'],
        ['table' => 'leave_balances',               'type' => 'direct'],
        ['table' => 'leaves',                       'type' => 'direct'],
        ['table' => 'salary_components',            'type' => 'direct'],
        ['table' => 'employee_salary_structures',   'type' => 'direct'],
        ['table' => 'salary_slips',                 'type' => 'direct'],
        ['table' => 'salary_slip_items',            'type' => 'pivot', 'parent_col' => 'salary_slip_id',       'parent_table' => 'salary_slips'],
        ['table' => 'attendances',                  'type' => 'direct'],
        ['table' => 'attendance_logs',              'type' => 'direct'],
        ['table' => 'attendance_rules',             'type' => 'direct'],
        ['table' => 'hrm_tasks',                    'type' => 'direct'],
        ['table' => 'hrm_task_assignments',         'type' => 'pivot', 'parent_col' => 'hrm_task_id',          'parent_table' => 'hrm_tasks'],
        ['table' => 'hrm_task_attachments',         'type' => 'pivot', 'parent_col' => 'hrm_task_id',          'parent_table' => 'hrm_tasks'],
        ['table' => 'hrm_task_comments',            'type' => 'pivot', 'parent_col' => 'hrm_task_id',          'parent_table' => 'hrm_tasks'],
        ['table' => 'hrm_task_activities',          'type' => 'pivot', 'parent_col' => 'hrm_task_id',          'parent_table' => 'hrm_tasks'],
        ['table' => 'announcements',                'type' => 'direct'],
        ['table' => 'announcement_acknowledgements','type' => 'pivot', 'parent_col' => 'announcement_id',      'parent_table' => 'announcements'],
        ['table' => 'work_logs',                    'type' => 'direct'],
        ['table' => 'holidays',                     'type' => 'direct'],

        // ── LAYER 10: Projects & Services ──
        ['table' => 'services',                     'type' => 'direct'],
        ['table' => 'projects',                     'type' => 'direct'],
        ['table' => 'project_renewals',             'type' => 'direct'],
        ['table' => 'todos',                        'type' => 'direct'],

        // ── LAYER 11: Appointments ──
        ['table' => 'appointment_services',         'type' => 'direct'],
        ['table' => 'appointment_slots',            'type' => 'direct'],
        ['table' => 'appointments',                 'type' => 'direct'],

        // ── LAYER 12: AI & Misc ──
        ['table' => 'ai_conversations',             'type' => 'direct'],
        ['table' => 'ai_messages',                  'type' => 'direct'],
        ['table' => 'ocr_scans',                    'type' => 'direct'],
        ['table' => 'imports',                      'type' => 'direct'],
        ['table' => 'import_logs',                  'type' => 'pivot', 'parent_col' => 'import_id',            'parent_table' => 'imports'],
        ['table' => 'promotions',                   'type' => 'direct'],
        ['table' => 'promotion_usages',             'type' => 'direct'],
        ['table' => 'company_subscriptions',        'type' => 'direct'],
        ['table' => 'subscription_payment_logs',    'type' => 'direct'],
    ];

    // Collected parent IDs per table for pivot resolution
    protected array $collectedIds = [];

    public function handle(): int
    {
        $companyId = (int) $this->argument('company_id');
        $chunkSize = (int) $this->option('chunk');

        if ($companyId <= 0) {
            $this->error('Invalid company_id provided.');
            return self::FAILURE;
        }

        // Verify company exists
        $company = DB::table('companies')->where('id', $companyId)->first();
        if (! $company) {
            $this->error("Company with ID {$companyId} not found.");
            return self::FAILURE;
        }

        // Resolve output path
        $outputPath = $this->option('output')
            ?? storage_path('exports/tenant_' . $companyId . '_' . now()->format('Ymd_His') . '.sql');

        // Ensure directory exists
        $dir = dirname($outputPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("  Tenant SQL Export");
        $this->info("  Company  : [{$companyId}] " . ($company->name ?? ''));
        $this->info("  Output   : {$outputPath}");
        $this->info("  Chunk    : {$chunkSize} rows/INSERT");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $handle = fopen($outputPath, 'w');
        if (! $handle) {
            $this->error("Cannot write to: {$outputPath}");
            return self::FAILURE;
        }

        // ── SQL File Header ──
        $this->writeHeader($handle, $companyId, $company);

        $totalRows   = 0;
        $totalTables = 0;

        foreach ($this->exportMap as $entry) {
            $table = $entry['table'];

            // Skip if table doesn't exist in DB (future-proof)
            if (! Schema::hasTable($table)) {
                $this->warn("  [SKIP] Table `{$table}` does not exist.");
                continue;
            }

            $rows = $this->fetchRows($entry, $companyId);
            $count = count($rows);

            if ($count === 0) {
                $this->line("  <fg=gray>[EMPTY]</> {$table}");
                // Still write a comment in the SQL for clarity
                fwrite($handle, "\n-- [{$table}] 0 rows\n");
                continue;
            }

            $this->line("  <fg=green>[OK]</> {$table} → {$count} rows");

            // Write INSERT statements
            $this->writeInserts($handle, $table, $rows, $chunkSize);

            // Store IDs collected from this table for pivot resolution
            if (isset($rows[0]) && isset($rows[0]->id)) {
                $this->collectedIds[$table] = array_column($rows, 'id');
            }

            $totalRows   += $count;
            $totalTables++;
        }

        // ── SQL File Footer ──
        $this->writeFooter($handle, $totalTables, $totalRows);
        fclose($handle);

        $fileSize = $this->humanFileSize(filesize($outputPath));
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("  ✅ Export complete!");
        $this->info("  Tables : {$totalTables}");
        $this->info("  Rows   : {$totalRows}");
        $this->info("  Size   : {$fileSize}");
        $this->info("  File   : {$outputPath}");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FETCH ROWS
    // ─────────────────────────────────────────────────────────────────────────
    protected function fetchRows(array $entry, int $companyId): array
    {
        $table = $entry['table'];

        if ($entry['type'] === 'direct') {
            return DB::table($table)
                ->where('company_id', $companyId)
                ->get()
                ->toArray();
        }

        // Pivot table: get parent IDs first, then fetch matching rows
        $parentTable = $entry['parent_table'];
        $parentCol   = $entry['parent_col'];

        $parentIds = $this->collectedIds[$parentTable] ?? [];

        if (empty($parentIds)) {
            // Try to fetch parent IDs fresh from DB (safety fallback)
            if (Schema::hasColumn($parentTable, 'company_id')) {
                $parentIds = DB::table($parentTable)
                    ->where('company_id', $companyId)
                    ->pluck('id')
                    ->toArray();
            }
        }

        if (empty($parentIds)) {
            return [];
        }

        return DB::table($table)
            ->whereIn($parentCol, $parentIds)
            ->get()
            ->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // WRITE SQL HEADER
    // ─────────────────────────────────────────────────────────────────────────
    protected function writeHeader($handle, int $companyId, object $company): void
    {
        $now     = now()->toDateTimeString();
        $name    = addslashes($company->name ?? 'Unknown');
        $dbName  = DB::getDatabaseName();

        fwrite($handle, <<<SQL
        -- ============================================================
        --  QLinKon Tenant SQL Export
        --  Company ID   : {$companyId}
        --  Company Name : {$name}
        --  Database     : {$dbName}
        --  Exported At  : {$now}
        --  Generated by : php artisan tenant:sql-export {$companyId}
        -- ============================================================
        --
        --  HOW TO IMPORT:
        --  1. Open phpMyAdmin → select target database
        --  2. Go to Import tab → choose this file
        --  3. Format: SQL → Click Go
        --
        --  OR via CLI:
        --  mysql -u root -p your_database < this_file.sql
        --
        -- ============================================================

        SET NAMES utf8mb4;
        SET CHARACTER SET utf8mb4;
        SET time_zone = '+00:00';
        SET FOREIGN_KEY_CHECKS = 0;
        SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

        SQL);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // WRITE INSERT STATEMENTS
    // ─────────────────────────────────────────────────────────────────────────
    protected function writeInserts($handle, string $table, array $rows, int $chunkSize): void
    {
        fwrite($handle, "\n-- [{$table}] " . count($rows) . " rows\n");

        $columns = array_keys((array) $rows[0]);
        $colList = '`' . implode('`, `', $columns) . '`';

        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            $valueGroups = [];

            foreach ($chunk as $row) {
                $values = array_map(
                    fn($val) => $this->escapeValue($val),
                    (array) $row
                );
                $valueGroups[] = '(' . implode(', ', $values) . ')';
            }

            fwrite($handle,
                "INSERT INTO `{$table}` ({$colList}) VALUES\n" .
                implode(",\n", $valueGroups) .
                ";\n"
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // WRITE SQL FOOTER
    // ─────────────────────────────────────────────────────────────────────────
    protected function writeFooter($handle, int $tables, int $rows): void
    {
        $now = now()->toDateTimeString();
        fwrite($handle, <<<SQL

        -- ============================================================
        --  Export finished at : {$now}
        --  Total tables       : {$tables}
        --  Total rows         : {$rows}
        -- ============================================================

        SET FOREIGN_KEY_CHECKS = 1;
        SQL);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ESCAPE VALUE FOR SQL
    // ─────────────────────────────────────────────────────────────────────────
    protected function escapeValue(mixed $val): string
    {
        if ($val === null) {
            return 'NULL';
        }

        if (is_bool($val)) {
            return $val ? '1' : '0';
        }

        if (is_int($val) || is_float($val)) {
            return (string) $val;
        }

        // String: escape special characters
        $val = str_replace(
            ['\\',   "\0",  "\n",  "\r",  "'",   '"',   "\x1a"],
            ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'],
            (string) $val
        );

        return "'{$val}'";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPER: Human readable file size
    // ─────────────────────────────────────────────────────────────────────────
    protected function humanFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < 3) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}