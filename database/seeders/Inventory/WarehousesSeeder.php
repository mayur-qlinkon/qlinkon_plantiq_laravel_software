<?php

namespace Database\Seeders\Inventory;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarehousesSeeder extends Seeder
{
    public function run(): void
    {
        // Fallback safety: use request attribute if set, else default to 1
        $companyId = request()->attributes->get('seeder_company_id', 1);

        $warehouses = [
            [
                'store_id' => 1,
                'name' => 'Main Godown',
                'code' => 'WH-001',
                'contact_person' => 'Ramesh Patel',
                'phone' => '9876543210',
                'email' => 'main@company.com',
                'address' => 'Industrial Area, Ahmedabad',
                'city' => 'Ahmedabad',
                'state_id' => 12,
                'zip_code' => '380001',
                'country' => 'India',
                'is_default' => true,
            ],
            [
                'store_id' => 2,
                'name' => 'West Branch',
                'code' => 'WH-002',
                'contact_person' => 'Sita Sharma',
                'phone' => '9123456780',
                'email' => 'west@company.com',
                'address' => 'Ring Road, Rajkot',
                'city' => 'Rajkot',
                'state_id' => 12,
                'zip_code' => '360001',
                'country' => 'India',
                'is_default' => false,
            ],
            [
                'store_id' => 3,
                'name' => 'Surat Depot',
                'code' => 'WH-003',
                'contact_person' => 'Arjun Mehta',
                'phone' => '9988776655',
                'email' => 'surat@company.com',
                'address' => 'Diamond Market, Surat',
                'city' => 'Surat',
                'state_id' => 12,
                'zip_code' => '395001',
                'country' => 'India',
                'is_default' => false,
            ],
        ];

        DB::beginTransaction();

        try {
            foreach ($warehouses as $warehouse) {
                DB::table('warehouses')->updateOrInsert(
                    [
                        'company_id' => $companyId,
                        'code' => $warehouse['code'],
                    ],
                    array_merge($warehouse, [
                        'company_id' => $companyId,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
