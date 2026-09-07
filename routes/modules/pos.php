<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PosController;

// ════════════════════════════════════════════════
// POS MODULE (module:pos)
// FIX: previously gated by 'module:invoicing' — a company could pay only for
// Invoicing and still use POS, or pay only for POS and still be locked out
// because POS piggybacked on the invoicing plan check. Now it's its own
// billable module, sellable standalone.
// ════════════════════════════════════════════════
Route::middleware(['module:pos', 'permission:pos.access'])->group(function () {
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::post('/store', [PosController::class, 'store'])->name('store');
        Route::get('/scan', [PosController::class, 'scanItem'])->name('scan');
        Route::post('/quick-product', [PosController::class, 'storeQuickProduct'])->name('quick-product');
        Route::get('/receipt/{id}', [PosController::class, 'receipt'])->name('receipt');
        Route::get('/receipt/{id}/json', [PosController::class, 'receiptJson'])->name('receipt.json');
        Route::get('/history', [PosController::class, 'history'])->name('history');
    });

    // FIX: this was completely ungated before (no module/permission check at
    // all) — anyone logged in could hit it regardless of plan.
    Route::get('api/products', [PosController::class, 'fetchProducts'])->name('api.products');
});