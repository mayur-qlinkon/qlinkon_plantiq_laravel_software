<?php

namespace Database\Factories;

use App\Models\ProductSku;
use App\Models\Product;
use App\Models\Company;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductSkuFactory extends Factory
{
    protected $model = ProductSku::class;

    public function definition(): array
    {
        $cost = $this->faker->randomFloat(2, 10, 500);

        return [
            'company_id' => Company::factory(),
            'product_id' => Product::factory(),
            'unit_id' => Unit::factory(),
            
            // Uniqueness handled by appending a random string
            'sku' => strtoupper($this->faker->lexify('SKU-????')) . '-' . Str::random(4),
            'barcode' => $this->faker->ean13() . $this->faker->randomNumber(3), // Ensures absolute uniqueness
            
            'cost' => $cost,
            'price' => $cost * $this->faker->randomFloat(2, 1.2, 1.8), // 20% to 80% markup
            'mrp' => $cost * 2, // MRP is double the cost
            
            'hsn_code' => $this->faker->numerify('####.##.##'),
            'gst_rate' => $this->faker->randomElement([0, 5, 12, 18, 28]), // Standard GST slabs
            'order_tax' => 0,
            'tax_type' => 'exclusive',
            'stock_alert' => $this->faker->numberBetween(5, 20),
            
            'is_active' => true,
        ];
    }
}