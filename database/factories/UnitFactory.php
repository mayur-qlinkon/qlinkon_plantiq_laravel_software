<?php

namespace Database\Factories;

use App\Models\Unit;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'company_id' => Company::factory(), // ✅ The robust way
            'name' => ucfirst($name) . '-' . fake()->unique()->numberBetween(1, 999),
            'short_name' => substr($name, 0, 3) . fake()->unique()->numberBetween(1, 99),
            'is_active' => true,
        ];
    }
}