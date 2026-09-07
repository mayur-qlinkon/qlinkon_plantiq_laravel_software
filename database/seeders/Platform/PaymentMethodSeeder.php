<?php

namespace Database\Seeders\Platform;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyId = request()->attributes->get('seeder_company_id', 1);

        $methods = [
            [
                'slug' => 'cash',
                'label' => 'Cash',
                'gateway' => null,
                'is_online' => false,
                'sort_order' => 1,
            ],
            [
                'slug' => 'upi',
                'label' => 'UPI / QR Code',
                'gateway' => null,
                'is_online' => false,
                'sort_order' => 2,
            ],            
            [
                'slug' => 'bank_transfer',
                'label' => 'Bank Transfer (NEFT/IMPS)',
                'gateway' => null,
                'is_online' => false,
                'sort_order' => 4,
            ],
            [
                'slug' => 'cheque',
                'label' => 'Cheque',
                'gateway' => null,
                'is_online' => false,
                'sort_order' => 5,
            ],
            [
                'slug' => 'razorpay',
                'label' => 'Pay Online (Razorpay)',
                'gateway' => 'razorpay',
                'is_online' => true,
                'sort_order' => 6,
            ],
        ];

        foreach ($methods as $method) {
            // Using updateOrInsert makes this seeder bulletproof (idempotent)
            DB::table('payment_methods')->updateOrInsert(
                [
                    'company_id' => $companyId,
                    'slug' => $method['slug'],
                ],
                array_merge($method, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
        if (isset($this->command)) {
            $this->command->info('Payment methods seeded successfully for Company ID: '.$companyId);
        }
    }
}
