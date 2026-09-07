<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\AttributeValueController;
use App\Http\Controllers\Admin\StockAdjustmentController;
use App\Http\Controllers\Admin\InventoryReportController;
use App\Http\Controllers\Admin\WarehouseController;


Route::middleware('module:inventory')->group(function () {
    // ── PRODUCTS ──────────────────────────────────────────────────────────────
    Route::resource('/products', ProductController::class)->middleware(['permission:products.view']);
    Route::resource('/categories', CategoryController::class)->except(['create', 'show', 'edit'])->middleware(['permission:categories.view']);
    Route::resource('/units', UnitController::class)->except(['create', 'show', 'edit'])->middleware(['permission:units.view']);
    Route::resource('/attributes', AttributeController::class)->except(['create', 'show', 'edit'])->middleware(['permission:attributes.view']);
    Route::resource('/warehouses', WarehouseController::class)->middleware(['permission:warehouses.view']);

    Route::post('products/bulk-delete', [ProductController::class, 'bulkDestroy'])->name('products.bulk-delete');
    Route::patch('/products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');
    Route::post('/products/{product}/duplicate', [ProductController::class, 'duplicate'])
        ->name('products.duplicate');
    
    Route::post('/attributes/{attribute}/values', [AttributeValueController::class, 'store'])->name('attribute-values.store');
    Route::put('/attribute-values/{attributeValue}', [AttributeValueController::class, 'update'])->name('attribute-values.update');
    Route::delete('/attribute-values/{attributeValue}', [AttributeValueController::class, 'destroy'])->name('attribute-values.destroy');    
    
    // Stock Adjustments — history listing + product/SKU search for the quick-adjust modal
    Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])
        ->middleware(['permission:warehouses.view'])
        ->name('stock-adjustments.index');

    Route::post('/stock-adjustments/{product}/skus/{sku}/adjust-stock', [StockAdjustmentController::class, 'store'])                
        ->name('stock-adjustments.adjust-stock');  

    Route::get('/stock-adjustments/search-skus', [StockAdjustmentController::class, 'searchSkus'])
        ->middleware(['permission:warehouses.view'])
        ->name('stock-adjustments.search-skus');

    Route::get('/inventory/reports', [InventoryReportController::class, 'index'])            
        ->name('inventory.reports.index');
    Route::get('/reports/inventory/ledger', [InventoryReportController::class, 'ajaxLedger'])->name('inventory.reports.ledger'); 
}); 