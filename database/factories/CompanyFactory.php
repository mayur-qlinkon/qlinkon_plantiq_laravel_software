<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(5),
            'domain' => Str::slug($name) . Str::random(3) . '.com',
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->numerify('##########'), // 10 digit Indian number
            'logo' => null,
            'gst_number' => $this->faker->numerify('24#####A#Z#'), // Mock GSTIN
            'currency' => 'INR',
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state_id' => null, // Set to null to avoid needing a StateFactory right now
            'zip_code' => $this->faker->postcode(),
            'country' => 'India',
            'is_active' => true,
        ];
    }
}