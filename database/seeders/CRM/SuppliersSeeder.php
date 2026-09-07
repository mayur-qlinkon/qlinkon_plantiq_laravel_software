<?php

namespace Database\Seeders\CRM;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SuppliersSeeder extends Seeder
{
    public function run(): void
    {
        // Dynamic company injection (same pattern)
        $companyId = request()->attributes->get('seeder_company_id', 1);

        $suppliers = [
            [
                'name' => 'Shree Ganesh Traders',
                'email' => 'ganesh.traders@example.com',
                'phone' => '9898989898',
                'address' => '12 Market Yard',
                'city' => 'Ahmedabad',
                'pincode' => '380022',
                'state_id' => 1,
                'gstin' => '24ABCDE1234F1Z5',
                'pan' => 'ABCDE1234F',
                'registration_type' => 'regular',
                'bank_name' => 'State Bank of India',
                'account_number' => '123456789012',
                'ifsc_code' => 'SBIN0001234',
                'branch' => 'Ahmedabad Main',
                'opening_balance' => 50000,
                'balance_type' => 'payable',
                'current_balance' => 50000,
                'credit_days' => 30,
                'credit_limit' => 200000,
                'notes' => 'Primary raw material supplier',
            ],
            [
                'name' => 'Om Industrial Supplies',
                'email' => 'om.industrial@example.com',
                'phone' => '9876501234',
                'address' => '88 GIDC Industrial Area',
                'city' => 'Surat',
                'pincode' => '395003',
                'state_id' => 1,
                'gstin' => '24ABCDE5678G1Z2',
                'pan' => 'ABCDE5678G',
                'registration_type' => 'composition',
                'bank_name' => 'HDFC Bank',
                'account_number' => '987654321000',
                'ifsc_code' => 'HDFC0000456',
                'branch' => 'Surat',
                'opening_balance' => 20000,
                'balance_type' => 'payable',
                'current_balance' => 20000,
                'credit_days' => 15,
                'credit_limit' => 100000,
                'notes' => 'Fast moving goods supplier',
            ],
            [
                'name' => 'Global Export Hub',
                'email' => 'exports@example.com',
                'phone' => '9123456789',
                'address' => 'Export Zone Phase 2',
                'city' => 'Mumbai',
                'pincode' => '400001',
                'state_id' => 2,
                'gstin' => null,
                'pan' => 'AAACG1234H',
                'registration_type' => 'overseas',
                'bank_name' => 'ICICI Bank',
                'account_number' => '456789123456',
                'ifsc_code' => 'ICIC0000789',
                'branch' => 'Mumbai',
                'opening_balance' => 0,
                'balance_type' => 'advance',
                'current_balance' => 0,
                'credit_days' => 0,
                'credit_limit' => 500000,
                'notes' => 'International supplier',
            ],
            [
                'name' => 'Krishna Packaging',
                'email' => 'krishna.pack@example.com',
                'phone' => '9011223344',
                'address' => 'Plot 55 Packaging Zone',
                'city' => 'Delhi',
                'pincode' => '110020',
                'state_id' => 4,
                'gstin' => '07ABCDE9999K1Z7',
                'pan' => 'ABCDE9999K',
                'registration_type' => 'regular',
                'bank_name' => 'Axis Bank',
                'account_number' => '112233445566',
                'ifsc_code' => 'UTIB0000123',
                'branch' => 'Delhi',
                'opening_balance' => 10000,
                'balance_type' => 'payable',
                'current_balance' => 10000,
                'credit_days' => 45,
                'credit_limit' => 150000,
                'notes' => 'Packaging materials vendor',
            ],
        ];

        DB::beginTransaction();

        try {
            foreach ($suppliers as $supplier) {
                DB::table('suppliers')->updateOrInsert(
                    [
                        'company_id' => $companyId,
                        'name' => $supplier['name'],
                    ],
                    array_merge($supplier, [
                        'company_id' => $companyId,
                        'store_id' => null,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }

            DB::commit();

            if (isset($this->command)) {
                $this->command->info("✅ Suppliers seeded successfully for Company ID: {$companyId}");
            }

        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($this->command)) {
                $this->command->error('❌ Failed to seed suppliers: '.$e->getMessage());
            }
        }
    }
}
