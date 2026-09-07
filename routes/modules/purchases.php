<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\PurchaseReturnController;
use App\Http\Controllers\Admin\SupplierController;

Route::middleware('module:purchases')->group(function () {

    // Each verb carries its own permission. Every resource here was gated on
    // .view alone, so anyone who could merely read purchases could also create,
    // edit and delete them, delete suppliers, and raise purchase returns — which
    // move stock. Resource registrations are split with only() because
    // Route::resource()->middleware() ignores array keys and would otherwise
    // apply every permission to every verb.

    // ── PURCHASES ──────────────────────────────────────────────────────────────
    // show is registered last on purpose. Routes match in registration order,
    // so a single-segment purchases/{purchase} declared before purchases/create
    // swallows /create and binds it as a model id.
    Route::resource('purchases', PurchaseController::class)
        ->only(['index'])->middleware('permission:purchases.view');
    Route::resource('purchases', PurchaseController::class)
        ->only(['create', 'store'])->middleware('permission:purchases.create');
    Route::resource('purchases', PurchaseController::class)
        ->only(['edit', 'update'])->middleware('permission:purchases.update');
    Route::resource('purchases', PurchaseController::class)
        ->only(['destroy'])->middleware('permission:purchases.delete');
    Route::resource('purchases', PurchaseController::class)
        ->only(['show'])->middleware('permission:purchases.view');

    // ── SUPPLIERS ──────────────────────────────────────────────────────────────
    // download-pdf is declared before the resource so it is not shadowed.
    Route::get('suppliers/download-pdf', [SupplierController::class, 'downloadPdf'])
        ->middleware('permission:suppliers.export')
        ->name('suppliers.download-pdf');

    Route::resource('suppliers', SupplierController::class)
        ->only(['index'])->middleware('permission:suppliers.view');
    Route::resource('suppliers', SupplierController::class)
        ->only(['store'])->middleware('permission:suppliers.create');
    Route::resource('suppliers', SupplierController::class)
        ->only(['update'])->middleware('permission:suppliers.update');
    Route::resource('suppliers', SupplierController::class)
        ->only(['destroy'])->middleware('permission:suppliers.delete');

    // ── Purchase Actions ───────────────────────────────────────────────────────
    Route::get('api/purchases/{id}/for-return', [PurchaseController::class, 'getForReturn'])
        ->middleware('permission:purchase_returns.create')
        ->name('api.purchases.for-return');

    Route::get('api/purchases/search', [PurchaseController::class, 'searchForReturn'])
        ->middleware('permission:purchase_returns.view')
        ->name('api.purchases.search');

    Route::get('api/purchases/search-skus', [PurchaseController::class, 'searchSkus'])
        ->middleware('permission:purchases.view')
        ->name('api.purchases.search-skus');

    Route::post('/purchases/{purchase}/pay', [PurchaseController::class, 'addPayment'])
        ->middleware('permission:purchases.add_payment')
        ->name('purchases.pay');

    Route::get('/purchases/{purchase}/pdf', [PurchaseController::class, 'downloadPdf'])
        ->middleware('permission:purchases.download_pdf')
        ->name('purchases.pdf');

    // ── PURCHASE RETURNS ───────────────────────────────────────────────────────
    Route::get('/purchase-returns/{purchase_return}/pdf', [PurchaseReturnController::class, 'downloadPdf'])
        ->middleware('permission:purchase_returns.download_pdf')
        ->name('purchase-returns.pdf');

    Route::patch('/purchase-returns/{purchase_return}/payment', [PurchaseReturnController::class, 'updatePayment'])
        ->middleware('permission:purchase_returns.add_payment')
        ->name('purchase-returns.payment');

    Route::resource('purchase-returns', PurchaseReturnController::class)
        ->only(['index'])->middleware('permission:purchase_returns.view');
    Route::resource('purchase-returns', PurchaseReturnController::class)
        ->only(['create', 'store'])->middleware('permission:purchase_returns.create');
    Route::resource('purchase-returns', PurchaseReturnController::class)
        ->only(['edit', 'update'])->middleware('permission:purchase_returns.update');
    Route::resource('purchase-returns', PurchaseReturnController::class)
        ->only(['destroy'])->middleware('permission:purchase_returns.delete');
    Route::resource('purchase-returns', PurchaseReturnController::class)
        ->only(['show'])->middleware('permission:purchase_returns.view');
});