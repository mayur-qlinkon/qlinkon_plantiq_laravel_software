<?php

use App\Models\Company;
use App\Models\ProductSku;
use App\Models\ProductStock;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Spin up a tenant + SKU. Returns [company, sku].
 */
function bootSkuContext(): array
{
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    Auth::login($user);

    $sku = ProductSku::factory()->create(['company_id' => $company->id]);

    return [$company, $sku];
}

function makeWarehouse(int $companyId): Warehouse
{
    // store_id is NOT NULL but has no FK constraint in the migration,
    // so we just use a stable value. Warehouse has no factory in this project.
    return Warehouse::create([
        'company_id' => $companyId,
        'store_id' => 1,
        'name' => 'Main',
        'is_active' => true,
    ]);
}

test('is_in_stock is false when no stock rows exist', function () {
    [, $sku] = bootSkuContext();

    expect($sku->is_in_stock)->toBeFalse();
});

test('is_in_stock is true when total qty across warehouses is positive', function () {
    [$company, $sku] = bootSkuContext();

    $wh = makeWarehouse($company->id);
    ProductStock::create([
        'company_id' => $company->id,
        'product_sku_id' => $sku->id,
        'warehouse_id' => $wh->id,
        'qty' => 5,
    ]);

    expect($sku->fresh()->is_in_stock)->toBeTrue();
});

test('is_in_stock is false when all qty rows sum to zero', function () {
    [$company, $sku] = bootSkuContext();

    $wh = makeWarehouse($company->id);
    ProductStock::create([
        'company_id' => $company->id,
        'product_sku_id' => $sku->id,
        'warehouse_id' => $wh->id,
        'qty' => 0,
    ]);

    expect($sku->fresh()->is_in_stock)->toBeFalse();
});

test('is_in_stock is false for inactive SKU even when stock exists', function () {
    [$company, $sku] = bootSkuContext();

    $sku->update(['is_active' => false]);
    $wh = makeWarehouse($company->id);
    ProductStock::create([
        'company_id' => $company->id,
        'product_sku_id' => $sku->id,
        'warehouse_id' => $wh->id,
        'qty' => 10,
    ]);

    expect($sku->fresh()->is_in_stock)->toBeFalse();
});

test('is_in_stock uses eager-loaded stocks without extra queries', function () {
    [$company, $sku] = bootSkuContext();

    $wh = makeWarehouse($company->id);
    ProductStock::create([
        'company_id' => $company->id,
        'product_sku_id' => $sku->id,
        'warehouse_id' => $wh->id,
        'qty' => 3,
    ]);

    $loaded = ProductSku::with('stocks')->find($sku->id);

    DB::enableQueryLog();
    $inStock = $loaded->is_in_stock;
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($inStock)->toBeTrue()
        ->and($queries)->toHaveCount(0); // accessor must not re-query when stocks are loaded
});
