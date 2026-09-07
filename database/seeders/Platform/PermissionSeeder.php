<?php

namespace Database\Seeders\Platform;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 🌟 THE ENTERPRISE PERMISSION MATRIX
        // Structure: 'module_group_lowercase' => ['feature_prefix' => ['actions']]
        $matrix = [
            'core' => [                
                'users' => ['view', 'create', 'update', 'delete'],
                'roles' => ['view', 'create', 'update', 'delete'],
                'notifications' => ['view'],
                'stores' => ['view', 'create', 'update', 'delete', 'switch'],
                'payment_methods' => ['view', 'create', 'update', 'delete'],
                'settings' => ['view', 'update', 'clear_cache', 'update_notifications', 'reset', 'audit'],                                
                'clients' => ['view', 'create', 'update', 'delete', 'export'],
            ],            

            'invoicing' => [
                // Sales metrics live on their own screen at /admin/sales/dashboard.
                // Kept separate from core.dashboard, which used to gate the old
                // combined landing page and no longer guards anything.
                'sales_dashboard' => ['view'],
                'quotations' => ['view', 'create', 'update', 'delete', 'convert', 'mark_sent', 'download_pdf'],
                'invoices' => ['view', 'create', 'update', 'delete', 'add_payment', 'download_pdf'],
                'invoice_returns' => ['view', 'create', 'update', 'delete', 'confirm'],
                'challans' => ['view', 'create', 'update', 'delete', 'change_status', 'download_pdf'],
                'challan_returns' => ['view', 'create', 'update', 'download_pdf'],
                'invoice_ledger'     => ['view', 'export'],
                'sales_reports'      => ['view', 'export'],
            ],

            'pos' => [
                'pos' => ['access', 'create_sale', 'create_quick_product', 'create_quick_client', 'apply_discount'],                
            ],       

            'expenses' => [
                'expenses' => ['view', 'create', 'update', 'delete', 'approve', 'reimburse'],  
                'expense_categories' => ['view', 'create', 'update', 'delete'],           
            ],            

            'purchases' => [
                'purchases' => ['view', 'create', 'update', 'delete', 'add_payment', 'download_pdf'],
                'purchase_returns' => ['view', 'create', 'update', 'delete', 'add_payment', 'download_pdf'],
                'suppliers' => ['view', 'create', 'update', 'delete', 'export'],
            ],            

            'inventory' => [
                'products' => ['view', 'create', 'update', 'delete', 'duplicate'],
                'categories' => ['view', 'create', 'update', 'delete'],
                'attributes' => ['view', 'create', 'update', 'delete'],
                'units' => ['view', 'create', 'update', 'delete'],                
                'warehouses' => ['view', 'create', 'update', 'delete'],
                'inventory_reports' => ['view', 'export'],
            ],

            'crm' => [
                'crm_dashboard' => ['view'],
                'crm_reports' => ['view'],                

                // view      → sees the leads assigned to them
                // view_all  → sees every lead in the company. This is what makes
                //             someone a CRM manager; company admins get it for
                //             free through has_permission().
                'crm_leads' => ['view', 'view_all', 'create', 'update', 'delete', 'convert', 'change_stage', 'import', 'export'],
                'crm_tasks' => ['view', 'create', 'complete'],
                'crm_pipelines' => ['view', 'create', 'update', 'delete'],
                'crm_stages' => ['view', 'create', 'update', 'delete', 'reorder'],
                'crm_sources' => ['view', 'create', 'update', 'delete'],
                'crm_tags' => ['view', 'create', 'update', 'delete'],                
            ],

            'storefront' => [
                'storefront_builder' => ['view', 'create', 'update', 'delete', 'duplicate'],
                'pages' => ['view', 'create', 'update', 'delete', 'toggle_publish'],
                'banners' => ['view', 'create', 'update', 'delete', 'toggle_status', 'duplicate', 'reorder'],
            ],

            'hrm' => [
                'hrm_dashboard' => ['view'],
                'employee_dashboard' => ['view'],
                'employees' => ['view', 'create', 'update', 'delete'],
                'departments' => ['view', 'create', 'update', 'delete'],
                'designations' => ['view', 'create', 'update', 'delete'],
                'shifts' => ['view', 'create', 'update', 'delete'],
                'holidays' => ['view', 'create', 'update', 'delete'],
                'attendance' => ['view', 'scan', 'report', 'override'],
                'attendance_rules' => ['view', 'create', 'update', 'delete'],
                'office_locations' => ['view', 'update', 'generate_qr'],
                'leave_types' => ['view', 'create', 'update', 'delete'],
                'leave_balances' => ['view', 'update', 'initialize', 'carry_forward'],
                'leaves' => ['view', 'create', 'approve', 'approve_all', 'reject', 'cancel'],
                'salary_components' => ['view', 'create', 'update', 'delete'],
                'salary_slips' => ['view', 'generate', 'edit', 'approve', 'mark_paid', 'download_pdf', 'delete'],
                'hrm_tasks' => ['view', 'create', 'update', 'delete', 'change_status', 'add_comment', 'add_attachment', 'download_attachment', 'delete_attachment'],
                'announcements' => ['view', 'create', 'update', 'delete', 'publish', 'duplicate'],
                'work_logs' => ['view', 'approve', 'approve_all'],
            ],

            'inquiry' => [
                'inquiries' => ['view', 'create', 'update', 'delete', 'convert_to_quotation'],
                'orders' => ['view', 'create', 'update', 'delete', 'change_status', 'cancel', 'add_note', 'record_payment', 'download_receipt'],
            ],  

            'bulk_import' => [
                'bulk_import' => ['view', 'products', 'images_import'],
            ],  

            'ai_assistant' => [
                'ai_assistant' => ['access'],
            ],            

            'ocr_scanner' => [                
                'ocr_scanner' => ['access','view', 'history', 'delete'],                
            ],
            'label_printing' => [                
                'labels' => ['view', 'print'],
            ],

            'projects' => [
                'projects' => ['view', 'create', 'update', 'delete'],
                'project_services' => ['view', 'create', 'update', 'delete'],
                'project_client_services' => ['view', 'create', 'update', 'delete', 'renew', 'cancel'],

                // write_off and reverse are separate from update on purpose:
                // forgiving or undoing money is a different level of trust from
                // raising a charge, and most staff should not have it.
                'project_charges' => ['view', 'create', 'update', 'cancel', 'write_off'],
                'project_payments' => ['view', 'create', 'allocate', 'reverse'],
                'project_reports' => ['view'],
            ],

            'appointments' => [                
                'appointments' => ['view', 'update'],
                'appointment_services' => ['view', 'create', 'update', 'delete'],
                'appointment_slots' => ['view', 'create', 'update', 'delete'],
            ],  
            
            'production' => [
                'production_layout'           => ['view'],
                'production_sites'            => ['view', 'create', 'update', 'delete'],
                'production_zones'            => ['view', 'create', 'update', 'delete'],                
                'production_growing_spaces'   => ['view', 'create', 'update', 'delete'],
                'production_plans'            => ['view', 'create', 'edit', 'delete'],
                'production_plant_batches'    => ['view', 'create', 'update', 'delete', 'change_status', 'place', 'release'],
                'production_zone_assignments' => ['view', 'create', 'delete'],
                'production_activity_templates' => ['view', 'create', 'update', 'delete'],
                'production_harvest_lots'     => ['view', 'create', 'update', 'receive', 'cancel'],
            ],
        ];

        DB::beginTransaction();

        try {
            foreach ($matrix as $moduleGroup => $features) {
                // Ensure module_group is always lowercase as requested
                $moduleGroupSlug = strtolower($moduleGroup);

                foreach ($features as $feature => $actions) {
                    
                    // Format feature name beautifully for the UI (e.g., "crm_leads" -> "CRM Leads")
                    $featureName = Str::of($feature)->replace('_', ' ')->title()->replace('Crm', 'CRM')->replace('Hrm', 'HRM')->replace('Pos', 'POS');

                    foreach ($actions as $action) {
                        // Format action beautifully (e.g., "download_pdf" -> "Download PDF")
                        $actionName = Str::of($action)->replace('_', ' ')->title()->replace('Pdf', 'PDF');

                        $name = "{$actionName} {$featureName}"; // e.g., "Download PDF Quotations"
                        
                        // 🛑 SLUG REMAINS EXACTLY THE SAME: "feature.action"
                        $slug = "{$feature}.{$action}"; 

                        DB::table('permissions')->updateOrInsert(
                            ['slug' => $slug],
                            [
                                'name' => $name,
                                'module_group' => $moduleGroupSlug,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    }
                }
            }

            DB::commit();
            
            if (isset($this->command)) {
                $this->command->info('✅ Enterprise Permissions seeded successfully!');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($this->command)) {
                $this->command->error('❌ Failed to seed permissions: '.$e->getMessage());
            }
        }
    }
}