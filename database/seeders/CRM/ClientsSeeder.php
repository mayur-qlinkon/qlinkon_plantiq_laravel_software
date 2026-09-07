<?php

namespace Database\Seeders\CRM;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientsSeeder extends Seeder
{
    public function run(): void
    {
        // Same pattern as your DesignationSeeder
        $companyId = request()->attributes->get('seeder_company_id', 1);

        $clients = [
            [
                'name' => 'Rajesh Patel',
                'client_code' => 'CLT-001',
                'company_name' => 'Patel Traders',
                'email' => 'rajesh.patel@example.com',
                'phone' => '9876543210',
                'gst_number' => '24ABCDE1234F1Z5',
                'registration_type' => 'registered',
                'address' => '123 SG Highway',
                'city' => 'Ahmedabad',
                'state_id' => 1,
                'zip_code' => '380015',
                'country' => 'India',
                'notes' => 'Regular wholesale client',
            ],
            [
                'name' => 'Priya Sharma',
                'client_code' => 'CLT-002',
                'company_name' => null,
                'email' => 'priya.sharma@example.com',
                'phone' => '9123456780',
                'gst_number' => null,
                'registration_type' => 'unregistered',
                'address' => '45 MG Road',
                'city' => 'Mumbai',
                'state_id' => 2,
                'zip_code' => '400001',
                'country' => 'India',
                'notes' => 'Retail customer',
            ],
            [
                'name' => 'Amit Verma',
                'client_code' => 'CLT-003',
                'company_name' => 'Verma Enterprises',
                'email' => 'amit.verma@example.com',
                'phone' => '9988776655',
                'gst_number' => '27ABCDE5678G1Z2',
                'registration_type' => 'registered',
                'address' => '78 Industrial Area',
                'city' => 'Pune',
                'state_id' => 3,
                'zip_code' => '411001',
                'country' => 'India',
                'notes' => 'Bulk buyer',
            ],
            [
                'name' => 'Neha Gupta',
                'client_code' => 'CLT-004',
                'company_name' => 'Gupta Creations',
                'email' => 'neha.gupta@example.com',
                'phone' => '9012345678',
                'gst_number' => null,
                'registration_type' => 'unregistered',
                'address' => '22 Fashion Street',
                'city' => 'Delhi',
                'state_id' => 4,
                'zip_code' => '110001',
                'country' => 'India',
                'notes' => 'Occasional buyer',
            ],
        ];

        DB::beginTransaction();

        try {
            foreach ($clients as $client) {
                DB::table('clients')->updateOrInsert(
                    [
                        'company_id' => $companyId,
                        'client_code' => $client['client_code'],
                    ],
                    array_merge($client, [
                        'company_id' => $companyId,
                        'store_id' => null,
                        'user_id' => null,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }

            DB::commit();

            if (isset($this->command)) {
                $this->command->info("✅ Clients seeded successfully for Company ID: {$companyId}");
            }

        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($this->command)) {
                $this->command->error('❌ Failed to seed clients: '.$e->getMessage());
            }
        }
    }
}
