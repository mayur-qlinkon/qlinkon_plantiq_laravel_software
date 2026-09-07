<?php

namespace Tests\Feature\Admin;

use App\Exceptions\ExcessReturnQuantityException;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceReturn;
use App\Models\InvoiceReturnItem;
use App\Models\ProductSku;
use App\Models\ProductStock;
use App\Models\State;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InvoiceReturnService;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end integrity tests for the Invoice Return root-cause fix.
 *
 * Covers:
 *  - return qty ceiling (create + confirm paths)
 *  - return_quantity is only written on confirmation, never on draft
 *  - idempotent confirm (duplicate confirm is a safe no-op)
 *  - invoice edit guard once confirmed returns exist
 *  - restock toggle behavior (stock movement only when restock=true)
 *  - historical backfill computes return_quantity from confirmed returns
 */
class InvoiceReturnIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceReturnService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InvoiceReturnService::class);
    }

    // ─────────────────────────────────────────────────────────────
    // The ceiling: return qty ≤ remaining_returnable_qty
    // ─────────────────────────────────────────────────────────────

    public function test_rejects_return_quantity_greater_than_invoice_line(): void
    {
        [$invoice, $item] = $this->bootScenario(soldQty: 5);

        $this->expectException(ExcessReturnQuantityException::class);

        $this->service->createReturn(
            $invoice,
            $this->returnPayload($invoice, [
                ['invoice_item' => $item, 'quantity' => 6], // over by 1
            ])
        );
    }

    public function test_allows_partial_return_and_then_remainder(): void
    {
        [$invoice, $item] = $this->bootScenario(soldQty: 10);

        // First return: 4 units, confirm it.
        $first = $this->service->createReturn(
            $invoice,
            $this->returnPayload($invoice, [['invoice_item' => $item, 'quantity' => 4]])
        );
        $this->service->confirmReturn($first);

        $this->assertSame(4.0, (float) $item->fresh()->return_quantity);
        $this->assertSame(6.0, (float) $item->fresh()->remaining_returnable_qty);

        // Second return: remaining 6 units should be accepted.
        $second = $this->service->createReturn(
            $invoice,
            $this->returnPayload($invoice, [['invoice_item' => $item, 'quantity' => 6]])
        );
        $this->service->confirmReturn($second);

        $this->assertSame(10.0, (float) $item->fresh()->return_quantity);
        $this->assertSame(0.0, (float) $item->fresh()->remaining_returnable_qty);
        $this->assertTrue($item->fresh()->is_fully_returned);
    }

    public function test_rejects_second_return_that_exceeds_remaining(): void
    {
        [$invoice, $item] = $this->bootScenario(soldQty: 10);

        $first = $this->service->createReturn(
            $invoice,
            $this->returnPayload($invoice, [['invoice_item' => $item, 'quantity' => 7]])
        );
        $this->service->confirmReturn($first);

        $this->expectException(ExcessReturnQuantityException::class);

        // Only 3 left, but asking for 4.
        $this->service->createReturn(
            $invoice,
            $this->returnPayload($invoice, [['invoice_item' => $item, 'quantity' => 4]])
        );
    }

    // ─────────────────────────────────────────────────────────────
    // Draft vs confirmed: return_quantity lifecycle & stock lifecycle
    // ─────────────────────────────────────────────────────────────

    public function test_draft_return_does_not_touch_return_quantity_or_stock(): void
    {
        [$invoice, $item, $sku, $warehouse] = $this->bootScenario(soldQty: 10);

        $this->seedStock($sku, $warehouse, qtyOnHand: 20);

        $draft = $this->service->createReturn(
            $invoice,
            $this->returnPayload($invoice, [['invoice_item' => $item, 'quantity' => 3]], restock: true)
        );

        $this->assertSame('draft', $draft->status);
        $this->assertSame(0.0, (float) $item->fresh()->return_quantity, 'return_quantity must only move on confirm');
        $this->assertFalse((bool) $draft->fresh()->stock_updated);
        $this->assertSame(20.0, $this->currentStock($sku, $warehouse), 'stock should be untouched for draft');
        $this->assertSame(0, StockMovement::count(), 'no movement should exist for a draft');
    }

    public function test_confirm_with_restock_increments_return_qty_and_adds_stock(): void
    {
        [$invoice, $item, $sku, $warehouse] = $this->bootScenario(soldQty: 10);
        $this->seedStock($sku, $warehouse, qtyOnHand: 20);

        $draft = $this->service->createReturn(
            $invoice,
            $this->returnPayload($invoice, [['invoice_item' => $item, 'quantity' => 3]], restock: true)
        );
        $this->service->confirmReturn($draft);

        $this->assertSame('confirmed', $draft->fresh()->status);
        $this->assertTrue((bool) $draft->fresh()->stock_updated);
        $this->assertSame(3.0, (float) $item->fresh()->return_quantity);
        $this->assertSame(23.0, $this->currentStock($sku, $warehouse), 'stock must grow by returned qty');
        $this->assertSame(1, StockMovement::where('movement_type', 'sale_return')->count());
    }

    public function test_confirm_without_restock_only_increments_return_qty(): void
    {
        [$invoice, $item, $sku, $warehouse] = $this->bootScenario(soldQty: 10);
        $this->seedStock($sku, $warehouse, qtyOnHand: 20);

        $draft = $this->service->createReturn(
            $invoice,
            $this->returnPayload($invoice, [['invoice_item' => $item, 'quantity' => 2]], restock: false)
        );
        $this->service->confirmReturn($draft);

        $this->assertSame('confirmed', $draft->fresh()->status);
        $this->assertFalse((bool) $draft->fresh()->stock_updated);
        $this->assertSame(2.0, (float) $item->fresh()->return_quantity);
        $this->assertSame(20.0, $this->currentStock($sku, $warehouse), 'stock must NOT change when restock=false');
        $this->assertSame(0, StockMovement::where('movement_type', 'sale_return')->count());
    }

    // ─────────────────────────────────────────────────────────────
    // Idempotent confirm — belt-and-braces against double submit
    // ─────────────────────────────────────────────────────────────

    public function test_confirm_is_idempotent_and_does_not_double_count(): void
    {
        [$invoice, $item, $sku, $warehouse] = $this->bootScenario(soldQty: 10);
        $this->seedStock($sku, $warehouse, qtyOnHand: 20);

        $draft = $this->service->createReturn(
            $invoice,
            $this->returnPayload($invoice, [['invoice_item' => $item, 'quantity' => 4]], restock: true)
        );

        $this->service->confirmReturn($draft);
        $this->service->confirmReturn($draft->fresh()); // second call — must be no-op
        $this->service->confirmReturn($draft->fresh()); // third call — must still be no-op

        $this->assertSame(4.0, (float) $item->fresh()->return_quantity);
        $this->assertSame(24.0, $this->currentStock($sku, $warehouse));
        $this->assertSame(1, StockMovement::where('movement_type', 'sale_return')->count());
    }

    // ─────────────────────────────────────────────────────────────
    // Invoice edit lock once confirmed returns exist
    // ─────────────────────────────────────────────────────────────

    public function test_invoice_cannot_be_updated_when_confirmed_returns_exist(): void
    {
        [$invoice, $item, $sku, $warehouse] = $this->bootScenario(soldQty: 10);
        $this->seedStock($sku, $warehouse, qtyOnHand: 20);

        $draft = $this->service->createReturn(
            $invoice,
            $this->returnPayload($invoice, [['invoice_item' => $item, 'quantity' => 2]], restock: true)
        );
        $this->service->confirmReturn($draft);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('confirmed returns');

        app(InvoiceService::class)->updateInvoice($invoice->fresh(), [
            'store_id' => $invoice->store_id,
            'warehouse_id' => $invoice->warehouse_id,
            'invoice_date' => now()->toDateString(),
            'supply_state' => $invoice->supply_state,
            'status' => 'confirmed',
            'items' => [[
                'product_sku_id' => $sku->id,
                'unit_id' => $item->unit_id,
                'quantity' => 1,
                'unit_price' => 100,
                'tax_percent' => 18,
                'tax_type' => 'exclusive',
            ]],
        ], $invoice->company_id);
    }

    // ─────────────────────────────────────────────────────────────
    // Historical backfill migration
    // ─────────────────────────────────────────────────────────────

    public function test_backfill_migration_restores_return_quantity_from_confirmed_returns(): void
    {
        [$invoice, $item] = $this->bootScenario(soldQty: 10);

        // Pretend two confirmed credit notes were recorded before the column was
        // being maintained — their items exist but return_quantity was never written.
        $this->persistLegacyConfirmedReturn($invoice, $item, qty: 3);
        $this->persistLegacyConfirmedReturn($invoice, $item, qty: 2);

        // Sanity: the raw column is still 0.
        $this->assertSame(0.0, (float) $item->fresh()->return_quantity);

        // Run the backfill migration in-place.
        (include database_path('migrations/2026_04_21_143308_backfill_invoice_items_return_quantity.php'))->up();

        $this->assertSame(5.0, (float) $item->fresh()->return_quantity);
        $this->assertSame(5.0, (float) $item->fresh()->remaining_returnable_qty);
    }

    // ─────────────────────────────────────────────────────────────
    // Friendly error message contract
    // ─────────────────────────────────────────────────────────────

    public function test_excess_return_exception_renders_friendly_message(): void
    {
        $e = new ExcessReturnQuantityException(
            productLabel: 'Ceramic Mug (SKU-ABC)',
            originalQty: 10,
            alreadyReturnedQty: 7,
            remainingQty: 3,
            requestedQty: 5,
        );

        $this->assertStringContainsString('5', $e->friendlyMessage());
        $this->assertStringContainsString('3', $e->friendlyMessage());
        $this->assertStringContainsString('Ceramic Mug (SKU-ABC)', $e->friendlyMessage());
        $this->assertStringNotContainsString('exception', strtolower($e->friendlyMessage()));
    }

    // ─────────────────────────────────────────────────────────────
    // Test scaffolding
    // ─────────────────────────────────────────────────────────────

    /**
     * Boot a minimal but realistic scenario:
     *   - State → Company → Auth'd User → Unit → Store → Warehouse
     *   - Product/SKU
     *   - A confirmed invoice with one line of the requested qty
     *
     * @return array{0: Invoice, 1: InvoiceItem, 2: ProductSku, 3: Warehouse}
     */
    private function bootScenario(float $soldQty): array
    {
        $state = State::firstOrCreate(
            ['code' => '24'],
            ['name' => 'Gujarat', 'type' => 'state', 'is_active' => true]
        );

        // SKU factory chain creates Company + Product + Unit for us in one call.
        $sku = ProductSku::factory()->create();
        $company = $sku->company;
        $company->update(['state_id' => $state->id]);
        $product = $sku->product;
        $unitId = $product->product_unit_id;

        $user = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        $store = Store::create([
            'company_id' => $company->id,
            'name' => 'Main Store',
            'slug' => 'main-store',
            'state_id' => $state->id,
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'state_id' => $state->id,
            'name' => 'Main Warehouse',
            'is_active' => true,
        ]);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $user->id,
            'invoice_number' => 'INV/TEST/'.uniqid(),
            'source' => 'direct',
            'invoice_date' => now()->toDateString(),
            'supply_state' => $state->name,
            'gst_treatment' => 'registered',
            'currency_code' => 'INR',
            'exchange_rate' => 1.0,
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
            'subtotal' => $soldQty * 100,
            'taxable_amount' => $soldQty * 100,
            'tax_amount' => $soldQty * 18,
            'grand_total' => $soldQty * 118,
        ]);

        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'product_sku_id' => $sku->id,
            'unit_id' => $unitId,
            'product_name' => $product->name,
            'hsn_code' => $sku->hsn_code,
            'quantity' => $soldQty,
            'unit_price' => 100,
            'tax_type' => 'exclusive',
            'tax_percent' => 18,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
            'taxable_value' => $soldQty * 100,
            'cgst_amount' => $soldQty * 9,
            'sgst_amount' => $soldQty * 9,
            'igst_amount' => 0,
            'tax_amount' => $soldQty * 18,
            'total_amount' => $soldQty * 118,
            'return_quantity' => 0,
        ]);

        return [$invoice, $item, $sku, $warehouse];
    }

    /**
     * Build a valid Store/UpdateInvoiceReturnRequest-shaped payload.
     *
     * @param  array<int, array{invoice_item: InvoiceItem, quantity: float}>  $lines
     */
    private function returnPayload(Invoice $invoice, array $lines, bool $restock = false): array
    {
        $items = [];
        foreach ($lines as $line) {
            /** @var InvoiceItem $it */
            $it = $line['invoice_item'];
            $items[] = [
                'invoice_item_id' => $it->id,
                'product_id' => $it->product_id,
                'product_sku_id' => $it->product_sku_id,
                'unit_id' => $it->unit_id,
                'product_name' => $it->product_name,
                'hsn_code' => $it->hsn_code,
                'quantity' => $line['quantity'],
                'unit_price' => (float) $it->unit_price,
                'tax_type' => 'exclusive',
                'tax_percent' => (float) $it->tax_percent,
                'discount_type' => 'fixed',
                'discount_amount' => 0,
            ];
        }

        return [
            'store_id' => $invoice->store_id,
            'warehouse_id' => $invoice->warehouse_id,
            'return_date' => now()->toDateString(),
            'return_type' => 'credit_note',
            'return_reason' => 'customer_return',
            'supply_state' => $invoice->supply_state,
            'gst_treatment' => $invoice->gst_treatment,
            'currency_code' => 'INR',
            'exchange_rate' => 1.0,
            'restock' => $restock,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
            'shipping_charge' => 0,
            'other_charges' => 0,
            'items' => $items,
        ];
    }

    /**
     * Seed a specific on-hand qty for the given sku in the given warehouse.
     */
    private function seedStock(ProductSku $sku, Warehouse $warehouse, float $qtyOnHand): void
    {
        ProductStock::create([
            'company_id' => $sku->company_id,
            'product_sku_id' => $sku->id,
            'warehouse_id' => $warehouse->id,
            'qty' => $qtyOnHand,
        ]);
    }

    private function currentStock(ProductSku $sku, Warehouse $warehouse): float
    {
        return (float) ProductStock::where('product_sku_id', $sku->id)
            ->where('warehouse_id', $warehouse->id)
            ->value('qty');
    }

    /**
     * Insert a legacy confirmed return + item row that predates the fix — the
     * parent invoice_item.return_quantity is deliberately NOT touched to
     * simulate the historical data the backfill has to heal.
     */
    private function persistLegacyConfirmedReturn(Invoice $invoice, InvoiceItem $item, float $qty): void
    {
        $return = InvoiceReturn::create([
            'company_id' => $invoice->company_id,
            'store_id' => $invoice->store_id,
            'warehouse_id' => $invoice->warehouse_id,
            'invoice_id' => $invoice->id,
            'created_by' => $invoice->created_by,
            'credit_note_number' => 'CN-LEGACY-'.uniqid(),
            'source' => 'direct',
            'return_date' => now()->toDateString(),
            'return_type' => 'credit_note',
            'return_reason' => 'customer_return',
            'restock' => false,
            'stock_updated' => false,
            'supply_state' => $invoice->supply_state,
            'gst_treatment' => $invoice->gst_treatment,
            'currency_code' => 'INR',
            'exchange_rate' => 1.0,
            'subtotal' => $qty * 100,
            'grand_total' => $qty * 118,
            'status' => 'confirmed',
            'refund_status' => 'unrefunded',
        ]);

        InvoiceReturnItem::create([
            'invoice_return_id' => $return->id,
            'invoice_item_id' => $item->id,
            'product_id' => $item->product_id,
            'product_sku_id' => $item->product_sku_id,
            'unit_id' => $item->unit_id,
            'product_name' => $item->product_name,
            'hsn_code' => $item->hsn_code,
            'quantity' => $qty,
            'unit_price' => (float) $item->unit_price,
            'is_restocked' => false,
            'tax_type' => 'exclusive',
            'tax_percent' => (float) $item->tax_percent,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
            'taxable_value' => $qty * 100,
            'cgst_amount' => $qty * 9,
            'sgst_amount' => $qty * 9,
            'igst_amount' => 0,
            'tax_amount' => $qty * 18,
            'total_amount' => $qty * 118,
        ]);
    }
}
