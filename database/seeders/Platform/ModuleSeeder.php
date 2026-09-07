<?php

namespace Database\Seeders\Platform;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        // 🌟 THE HIGH-LEVEL BILLABLE MODULES
        // We do not include "Settings" or "Users" here, as those are Core ERP features everyone gets.
        $modules = [
            [
                'name' => 'Inventory & Catalog',
                'slug' => 'inventory', // Includes: Products, Categories, Attributes, Units, Warehouses
                'is_active' => 1,
            ],            
            [
                'name' => 'Invoicing & Billing',
                'slug' => 'invoicing', // Includes: Quotations, Invoices, Invoice Returns, Challans, Sales Reports, Invoice Ledger
                'depends_on' => ['inventory'],
                'is_active' => 1,
            ],
            [
                'name' => 'Point of Sale (POS)',
                'slug' => 'pos', // Includes: POS Counter, POS History, Receipts
                'depends_on' => ['inventory'],
                'is_active' => 1,
            ],
            [
                'name' => 'Expense Management',
                'slug' => 'expenses', // Includes: Expenses, Expense Categories
                'is_active' => 1,
            ],
            [
                'name' => 'Purchasing & Supply Chain',
                'slug' => 'purchases', // Includes: Purchase Orders, Purchase Returns, Suppliers
                'depends_on' => ['inventory'],
                'is_active' => 1,
            ],
            [
                'name' => 'CRM & Lead Management',
                'slug' => 'crm', // Includes: Leads, Pipelines, Tasks, Activities
                'is_active' => 1,
            ],
            [
                'name' => 'HR & Payroll Management',
                'slug' => 'hrm', // Includes: Employees, Attendance, Payroll
                'is_active' => 1,
            ],            
            [
                'name' => 'Storefront Website',
                'slug' => 'storefront', // Includes: Storefront Sections, Banners, Merchandising
                'depends_on' => ['inventory'],
                'is_active' => 1,
            ],           
            [
                'name' => 'Inquiries & Pre-Sales',
                'slug' => 'inquiry', // Includes: Product Inquiries, Web Forms, Custom Requests
                'depends_on' => ['inventory'],
                'is_active' => 1,
            ],    
            [
                'name' => 'Plant Education',
                'slug' => 'plant_education',
                'depends_on' => ['inventory'],
                'is_active' => 1,
            ],                       
            [
                'name' => 'Projects & Renewals',
                'slug' => 'projects', // Includes: Work Progress Tracking, Payments, Subscription Renewals
                'is_active' => 1,
            ],                  
            [
                'name' => 'Appointments & Bookings',
                'slug' => 'appointments', // Includes: Services, Slots, Booking management
                'depends_on' => ['storefront'],
                'is_active' => 1,
            ],
            [
                'name' => 'Production Management',
                'slug' => 'production', // Includes: Layout, Batches, Harvest Lots, Worker Tasks
                'depends_on' => ['hrm','inventory'],
                'is_active' => 1,
            ],
            [
                'name' => 'Label Printing',
                'slug' => 'label_printing', // Includes: Barcode/Price Label design & printing
                'depends_on' => ['inventory'],
                'is_active' => 1,
            ],
            [
                'name' => 'AI Business Assistant',
                'slug' => 'ai_assistant', // Includes: AI Chatbot (chat, conversations, usage)
                'is_active' => 1,
            ],
            [
                'name' => 'OCR Document Scanner',
                'slug' => 'ocr_scanner', // Includes: Scan, extract, save documents
                'is_active' => 1,
            ],
            [
                'name' => 'Bulk Import & Export',
                'slug' => 'bulk_import', // Includes: CSV/Excel import, sample downloads, exports
                'is_active' => 1,
            ],

        ];

        // 🔻 RETIRED — dissolved into the split-out modules above.
        // 'finance' → content moved into 'invoicing' (reports/ledger) + 'expenses'.
        // 'tools'   → split into 'ai_assistant', 'ocr_scanner', 'bulk_import'.
        // Left inactive (not deleted) so no FK on modules.id ever dangles;
        // if you truncate+reseed on dev data, these simply won't exist at all.
        DB::table('modules')->whereIn('slug', ['finance', 'tools'])->update(['is_active' => 0]);

        DB::beginTransaction();

        try {
            foreach ($modules as $module) {
                // updateOrInsert prevents duplicate rows if you run the seeder multiple times
                DB::table('modules')->updateOrInsert(
                    ['slug' => $module['slug']],
                    [
                        'name' => $module['name'],
                        'depends_on' => isset($module['depends_on'])
                            ? json_encode($module['depends_on'])
                            : null,
                        'is_active' => $module['is_active'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            DB::commit();
            if (isset($this->command)) {
                $this->command->info('✅ High-level SaaS Modules seeded successfully!');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($this->command)) {
                $this->command->error('❌ Failed to seed modules: '.$e->getMessage());
            }
            throw $e;
        }
    }
}