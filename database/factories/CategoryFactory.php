<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'company_id' => Company::factory(), // ✅ The robust way
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 9999),
            'image' => fake()->optional()->imageUrl(),
            'is_active' => true,
        ];
    }
}