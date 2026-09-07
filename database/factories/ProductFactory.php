<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Company;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = $this->faker->words(3, true);

        return [
            // Relationships - By default, creates a new one, but you can override these in your seeder
            'company_id' => Company::factory(),
            'category_id' => Category::factory(),
            'supplier_id' => Supplier::factory(),
            
            'product_unit_id' => Unit::factory(),
            'sale_unit_id' => Unit::factory(),
            'purchase_unit_id' => Unit::factory(),

            // Basic Info
            'name' => ucfirst($name),
            // Use time() or a random string to guarantee uniqueness alongside the slug
            'slug' => Str::slug($name) . '-' . Str::random(5), 
            'type' => 'single',
            'product_type' => 'sellable',
            'barcode_symbology' => 'CODE128',
            'hsn_code' => $this->faker->numerify('####.##.##'),
            
            // Details
            'quantity_limitation' => $this->faker->optional(0.7)->numberBetween(1, 100),
            'note' => $this->faker->optional()->sentence(),
            'description' => $this->faker->paragraphs(2, true),
            
            // JSON Arrays (Make sure to add protected $casts = ['specifications' => 'array', 'product_guide' => 'array'] in your Product Model)
            'specifications' => [
                'Weight' => $this->faker->randomFloat(2, 0.5, 10) . ' kg',
                'Material' => $this->faker->randomElement(['Cotton', 'Steel', 'Plastic', 'Wood']),
            ],
            'product_guide' => [
                'Care Instructions' => 'Store in a cool, dry place.',
                'Warranty' => '1 Year Manufacturer Warranty'
            ],

            // Flags
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
            'show_in_storefront' => true,
        ];
    }

    /**
     * STATE: Variable Product
     */
    public function variable(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'variable',
            ];
        });
    }

    /**
     * STATE: Catalog Only (Not sellable)
     */
    public function catalog(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'product_type' => 'catalog',
            ];
        });
    }
}