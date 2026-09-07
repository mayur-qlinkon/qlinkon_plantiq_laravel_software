<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),

            
            // Basic Contact
            'name' => $this->faker->company() . ' Supplies',
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->numerify('##########'),
            'address' => $this->faker->address(),
            'city' => $this->faker->city(),
            'pincode' => $this->faker->numerify('######'),
            'state_id' => null, // Kept null to avoid needing StateFactory
            
            // Indian Compliance
            'gstin' => strtoupper($this->faker->bothify('24?????####?1Z?')),
            'pan' => strtoupper($this->faker->bothify('?????####?')),
            'registration_type' => $this->faker->randomElement(['regular', 'composition', 'unregistered']),
            
            // Banking
            'bank_name' => $this->faker->randomElement(['HDFC', 'SBI', 'ICICI', 'Axis']),
            'account_number' => $this->faker->numerify('###########'),
            'ifsc_code' => strtoupper($this->faker->bothify('????0######')),
            'branch' => $this->faker->city() . ' Branch',
            
            // Financials
            'opening_balance' => $this->faker->randomFloat(2, 0, 5000),
            'balance_type' => 'payable',
            'current_balance' => $this->faker->randomFloat(2, 0, 10000),
            'credit_days' => $this->faker->randomElement([0, 15, 30, 45, 60]),
            'credit_limit' => $this->faker->randomElement([50000, 100000, 500000]),
            
            'is_active' => true,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}