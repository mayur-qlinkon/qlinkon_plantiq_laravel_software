<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductSku;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BestSellerCounterTest extends TestCase
{
    use RefreshDatabase;

    public function test_applies_and_reverses_counters_on_sku_and_product(): void
    {
        $product = Product::factory()->create();
        $sku = ProductSku::factory()->create([
            'product_id' => $product->id,
            'company_id' => $product->company_id,
        ]);

        $items = [
            ['product_id' => $product->id, 'product_sku_id' => $sku->id, 'quantity' => 3],
            ['product_id' => $product->id, 'product_sku_id' => $sku->id, 'quantity' => 2],
        ];

        InvoiceService::applySaleCounters($items, 1);

        $this->assertSame(5, (int) $sku->fresh()->total_sold);
        $this->assertSame(5, (int) $product->fresh()->total_sold);

        InvoiceService::applySaleCounters([
            ['product_id' => $product->id, 'product_sku_id' => $sku->id, 'quantity' => 2],
        ], -1);

        $this->assertSame(3, (int) $sku->fresh()->total_sold);
        $this->assertSame(3, (int) $product->fresh()->total_sold);
    }

    public function test_counter_never_goes_negative(): void
    {
        $product = Product::factory()->create();
        $sku = ProductSku::factory()->create([
            'product_id' => $product->id,
            'company_id' => $product->company_id,
        ]);

        InvoiceService::applySaleCounters([
            ['product_id' => $product->id, 'product_sku_id' => $sku->id, 'quantity' => 99],
        ], -1);

        $this->assertSame(0, (int) $sku->fresh()->total_sold);
        $this->assertSame(0, (int) $product->fresh()->total_sold);
    }

    public function test_invalid_sign_is_ignored(): void
    {
        $product = Product::factory()->create();
        $sku = ProductSku::factory()->create([
            'product_id' => $product->id,
            'company_id' => $product->company_id,
        ]);

        InvoiceService::applySaleCounters([
            ['product_id' => $product->id, 'product_sku_id' => $sku->id, 'quantity' => 5],
        ], 0);

        $this->assertSame(0, (int) $sku->fresh()->total_sold);
    }
}
