<?php

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Company;
use App\Models\Import;
use App\Models\ImportLog;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\ProductSkuValue;
use App\Models\Unit;
use App\Models\User;
use App\Services\Import\SkuImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

/**
 * Helper — build the mapped row shape the importer expects (mirrors readCsvChunk output).
 *
 * @param  array<string,string>  $data
 */
function variantRow(array $data, int $rowNumber = 2): array
{
    return array_merge($data, ['_row_number' => $rowNumber]);
}

function makeImport(int $userId, array $overrides = []): Import
{
    return Import::create(array_merge([
        'user_id' => $userId,
        'type' => 'skus',
        'file_path' => 'imports/skus/test.csv',
        'total_rows' => 1,
        'status' => 'processing',
        'duplicate_mode' => 'skip',
        'import_mode' => 'create_or_update',
        'is_dry_run' => false,
    ], $overrides));
}

function bootTenant(): array
{
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    Auth::login($user);

    return [$company, $user];
}

test('validates required headers are present', function () {
    $importer = new SkuImporter;

    expect($importer->validateHeaders(['product_slug', 'price']))
        ->toMatchArray(['valid' => false])
        ->and($importer->validateHeaders(['product_slug', 'price', 'cost']))
        ->toMatchArray(['valid' => false]) // still missing attribute pair
        ->and($importer->validateHeaders(['product_slug', 'price', 'cost', 'attribute_1_name', 'attribute_1_value']))
        ->toMatchArray(['valid' => true]);
});

test('extracts unique key from attribute combination, not sku code', function () {
    $importer = new SkuImporter;

    $key1 = $importer->extractUniqueKey([
        'product_slug' => 'aloe-vera',
        'attribute_1_name' => 'Size',
        'attribute_1_value' => 'Small',
        'attribute_2_name' => 'Pot',
        'attribute_2_value' => 'Ceramic',
    ]);

    // Reordered pairs produce the same key (sorted canonical form)
    $key2 = $importer->extractUniqueKey([
        'product_slug' => 'aloe-vera',
        'attribute_1_name' => 'Pot',
        'attribute_1_value' => 'Ceramic',
        'attribute_2_name' => 'Size',
        'attribute_2_value' => 'Small',
    ]);

    expect($key1)->toBe($key2)->and($key1)->not->toBeNull();
});

test('creates variant with auto-resolved attributes and values', function () {
    [$company, $user] = bootTenant();

    $product = Product::factory()->variable()->create([
        'company_id' => $company->id,
        'slug' => 'aloe-vera',
    ]);

    $import = makeImport($user->id);
    $importer = new SkuImporter;

    $rows = [
        variantRow([
            'product_slug' => 'aloe-vera',
            'sku' => '',
            'price' => '299',
            'cost' => '150',
            'mrp' => '349',
            'barcode' => '',
            'stock_alert' => '5',
            'attribute_1_name' => 'Size',
            'attribute_1_value' => 'Small',
            'attribute_2_name' => 'Pot',
            'attribute_2_value' => 'Ceramic',
        ]),
    ];

    $result = $importer->processChunk($import, $rows, 2, $company->id);

    expect($result)->toMatchArray(['success' => 1, 'failed' => 0, 'created' => 1]);

    $sku = ProductSku::where('product_id', $product->id)->first();
    expect($sku)->not->toBeNull()
        ->and($sku->sku)->toBe('ALOE-VERA-SMALL-CERAMIC')
        ->and((float) $sku->price)->toBe(299.0)
        ->and($sku->skuValues)->toHaveCount(2);

    expect(Attribute::where('company_id', $company->id)->pluck('name')->sort()->values()->all())
        ->toBe(['Pot', 'Size']);

    expect(AttributeValue::where('company_id', $company->id)->pluck('value')->sort()->values()->all())
        ->toBe(['Ceramic', 'Small']);
});

test('auto-converts single product to variable and soft-deletes default SKU when multiple SKUs exist', function () {
    [$company, $user] = bootTenant();

    $product = Product::factory()->create([
        'company_id' => $company->id,
        'slug' => 'simple-plant',
        'type' => 'single',
    ]);

    // Default SKU with no attribute values (classic "single product" setup).
    $defaultSku = ProductSku::factory()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
    ]);

    $import = makeImport($user->id);
    $importer = new SkuImporter;

    $result = $importer->processChunk($import, [
        variantRow([
            'product_slug' => 'simple-plant',
            'price' => '100',
            'cost' => '50',
            'attribute_1_name' => 'Size',
            'attribute_1_value' => 'Small',
        ]),
    ], 2, $company->id);

    expect($result)->toMatchArray(['success' => 1, 'failed' => 0, 'created' => 1]);

    $product->refresh();
    expect($product->type)->toBe('variable');

    // The default (no-attrs) SKU should be soft-deleted.
    expect(ProductSku::find($defaultSku->id))->toBeNull();
    expect(ProductSku::withTrashed()->find($defaultSku->id)->trashed())->toBeTrue();

    // The new variant SKU should be alive and well.
    expect(ProductSku::where('product_id', $product->id)->count())->toBe(1);
});

test('does not convert product type when only one SKU exists after import', function () {
    [$company, $user] = bootTenant();

    $product = Product::factory()->create([
        'company_id' => $company->id,
        'slug' => 'lonely-plant',
        'type' => 'single',
    ]);

    $import = makeImport($user->id);
    $importer = new SkuImporter;

    $result = $importer->processChunk($import, [
        variantRow([
            'product_slug' => 'lonely-plant',
            'price' => '100',
            'cost' => '50',
            'attribute_1_name' => 'Size',
            'attribute_1_value' => 'Small',
        ]),
    ], 2, $company->id);

    expect($result)->toMatchArray(['success' => 1, 'created' => 1]);
    expect($product->fresh()->type)->toBe('single');
});

test('rejects duplicate attribute combination for the same product', function () {
    [$company, $user] = bootTenant();

    Product::factory()->variable()->create([
        'company_id' => $company->id,
        'slug' => 'aloe-vera',
    ]);

    $import = makeImport($user->id, ['import_mode' => 'create_only']);
    $importer = new SkuImporter;

    $row = variantRow([
        'product_slug' => 'aloe-vera',
        'price' => '299',
        'cost' => '150',
        'attribute_1_name' => 'Size',
        'attribute_1_value' => 'Small',
    ]);

    // First insert succeeds
    $r1 = $importer->processChunk($import, [$row], 2, $company->id);
    expect($r1)->toMatchArray(['success' => 1, 'created' => 1]);

    // Second insert with same combination (case-insensitive) should be skipped under create_only
    $r2 = $importer->processChunk($import, [variantRow([
        'product_slug' => 'aloe-vera',
        'price' => '399',
        'cost' => '200',
        'attribute_1_name' => 'size',   // different case
        'attribute_1_value' => 'SMALL', // different case
    ], 3)], 3, $company->id);

    expect($r2)->toMatchArray(['skipped' => 1, 'created' => 0]);
    expect(ProductSku::count())->toBe(1);
});

test('updates existing variant under create_or_update mode', function () {
    [$company, $user] = bootTenant();

    Product::factory()->variable()->create([
        'company_id' => $company->id,
        'slug' => 'aloe-vera',
    ]);

    $import = makeImport($user->id, ['import_mode' => 'create_or_update']);
    $importer = new SkuImporter;

    $importer->processChunk($import, [variantRow([
        'product_slug' => 'aloe-vera',
        'price' => '299',
        'cost' => '150',
        'attribute_1_name' => 'Size',
        'attribute_1_value' => 'Small',
    ])], 2, $company->id);

    // Second run with same combo → update price
    $r = $importer->processChunk($import, [variantRow([
        'product_slug' => 'aloe-vera',
        'price' => '499',
        'cost' => '250',
        'attribute_1_name' => 'Size',
        'attribute_1_value' => 'Small',
    ], 3)], 3, $company->id);

    expect($r)->toMatchArray(['success' => 1, 'updated' => 1]);
    expect((float) ProductSku::first()->price)->toBe(499.0);
    expect(ProductSku::count())->toBe(1);
});

test('enforces 100-variant cap per product', function () {
    [$company, $user] = bootTenant();

    $product = Product::factory()->variable()->create([
        'company_id' => $company->id,
        'slug' => 'big-product',
    ]);

    // Shared unit so ProductSku::factory doesn't burn Unit factory unique suffixes 100x.
    $unit = Unit::factory()->create(['company_id' => $company->id]);

    $attr = Attribute::create([
        'company_id' => $company->id,
        'name' => 'Slot',
        'type' => 'text',
        'is_active' => true,
    ]);

    for ($i = 1; $i <= 100; $i++) {
        $value = AttributeValue::create([
            'company_id' => $company->id,
            'attribute_id' => $attr->id,
            'value' => "V{$i}",
            'position' => 0,
            'is_active' => true,
        ]);
        $sku = ProductSku::factory()->create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'sku' => "PREFILL-{$i}",
        ]);
        ProductSkuValue::create([
            'product_sku_id' => $sku->id,
            'attribute_id' => $attr->id,
            'attribute_value_id' => $value->id,
        ]);
    }

    $import = makeImport($user->id);
    $importer = new SkuImporter;

    $r = $importer->processChunk($import, [variantRow([
        'product_slug' => 'big-product',
        'price' => '10',
        'cost' => '5',
        'attribute_1_name' => 'Slot',
        'attribute_1_value' => 'V101',
    ])], 2, $company->id);

    expect($r)->toMatchArray(['failed' => 1, 'created' => 0]);
    expect(ImportLog::where('import_id', $import->id)->first()->error_message)
        ->toContain('Maximum of 100 variants');
});

test('dry-run does not persist any rows', function () {
    [$company, $user] = bootTenant();

    Product::factory()->variable()->create([
        'company_id' => $company->id,
        'slug' => 'aloe-vera',
    ]);

    $import = makeImport($user->id, ['is_dry_run' => true]);
    $importer = new SkuImporter;

    $r = $importer->processChunk($import, [variantRow([
        'product_slug' => 'aloe-vera',
        'price' => '299',
        'cost' => '150',
        'attribute_1_name' => 'Size',
        'attribute_1_value' => 'Small',
    ])], 2, $company->id);

    expect($r)->toMatchArray(['success' => 1, 'created' => 1]);
    expect(ProductSku::count())->toBe(0); // rolled back
    expect(Attribute::count())->toBe(0);  // attribute creation was inside the transaction too
});

test('rejects row with mismatched attribute pair columns', function () {
    [$company, $user] = bootTenant();

    Product::factory()->variable()->create([
        'company_id' => $company->id,
        'slug' => 'aloe-vera',
    ]);

    $import = makeImport($user->id);
    $importer = new SkuImporter;

    $r = $importer->processChunk($import, [variantRow([
        'product_slug' => 'aloe-vera',
        'price' => '299',
        'cost' => '150',
        'attribute_1_name' => 'Size',
        'attribute_1_value' => '', // mismatched: name filled, value empty
    ])], 2, $company->id);

    expect($r)->toMatchArray(['failed' => 1, 'created' => 0]);
});

test('respects company scoping when resolving product slug', function () {
    $otherCompany = Company::factory()->create();
    Product::factory()->variable()->create([
        'company_id' => $otherCompany->id,
        'slug' => 'aloe-vera',
    ]);

    [$company, $user] = bootTenant();

    $import = makeImport($user->id);
    $importer = new SkuImporter;

    $r = $importer->processChunk($import, [variantRow([
        'product_slug' => 'aloe-vera',
        'price' => '299',
        'cost' => '150',
        'attribute_1_name' => 'Size',
        'attribute_1_value' => 'Small',
    ])], 2, $company->id);

    expect($r)->toMatchArray(['failed' => 1, 'created' => 0]);
    expect(ImportLog::where('import_id', $import->id)->first()->error_message)
        ->toContain('not found');
});
