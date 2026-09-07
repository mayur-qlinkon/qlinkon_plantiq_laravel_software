<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\LabelController;

// ════════════════════════════════════════════════════════════════════════
// FLUTTER APP API — v1
//
// Tenant resolution: identical to the web app — subdomain-based, handled
// globally by IdentifyTenant (prepended to the 'api' middleware group in
// bootstrap/app.php). The Flutter app hits {company-subdomain}.<central
// domain>/api/v1/..., exactly like the web admin panel, so tenant() is
// already bound by the time any route here runs. No separate tenant
// plumbing needed on this side.
//
// Auth: Sanctum bearer tokens (stateless — no cookies, no CSRF).
//
// Store context: active_store() / active_store_ids() / auth_stores() all
// read session('store_id'). The 'store.context' middleware (see
// ResolveApiStoreContext) seeds that from the X-Store-Id header for the
// current request only, so every existing helper — and PosService /
// LabelService, unchanged — keeps working exactly as it does on the web.
// ════════════════════════════════════════════════════════════════════════

Route::prefix('v1')->group(function () {

    // ── PUBLIC (no token yet) ──────────────────────────────────────────
    // Login itself needs no session at all — it does its own tenant-scoped
    // User::where(...) lookup (same pattern as TenantAdminAuthController)
    // and issues a Sanctum token directly. No Auth::login(), no session.
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:api-login');

    // ── AUTHENTICATED ─────────────────────────────────────────────────
    // StartSession (NOT the full 'web' group — that includes
    // VerifyCsrfToken, which would 419-block every bearer-token POST from
    // the Flutter app). We only need the session store for active_store()
    // to read/write session('store_id') within this one request; nothing
    // here is cookie- or CSRF-based.
    Route::middleware([
        'auth:sanctum',
        \Illuminate\Session\Middleware\StartSession::class,
        'tenant.bind',
    ])->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);

        // No store.context here on purpose — this is how the app learns
        // which stores exist in the first place, before it can send
        // X-Store-Id on anything else.
        Route::get('/me', [AuthController::class, 'me']);

        // Only POS/Labels need a resolved store — nested so /me and
        // /logout above stay reachable without one.
        Route::middleware('store.context')->group(function () {
            Route::middleware(['module:pos', 'permission:pos.access'])
                ->prefix('pos')->name('api.pos.')->controller(PosController::class)
                ->group(function () {
                    Route::get('/bootstrap', 'bootstrap')->name('bootstrap');
                    Route::get('/products', 'products')->name('products');
                    Route::get('/scan', 'scan')->name('scan');
                    Route::get('/clients', 'clients')->name('clients');
                    Route::post('/checkout', 'checkout')->name('checkout');
                    Route::get('/history', 'history')->name('history');
                    Route::get('/receipt/{id}', 'receipt')->name('receipt');
                });

            Route::middleware(['module:label_printing'])
                ->prefix('labels')->name('api.labels.')->controller(LabelController::class)
                ->group(function () {
                    Route::get('/bootstrap', 'bootstrap')->name('bootstrap');
                    Route::get('/products', 'products')->name('products');
                    Route::post('/selected', 'selected')->name('selected');
                    Route::get('/render', 'render')->name('render');
                });
        });
    });
});



// POST /api/v1/login          POST /api/v1/logout        GET /api/v1/me
// GET  /api/v1/pos/bootstrap  GET  /api/v1/pos/products  GET /api/v1/pos/scan
// GET  /api/v1/pos/clients    POST /api/v1/pos/checkout
// GET  /api/v1/pos/history    GET  /api/v1/pos/receipt/{id}
// GET  /api/v1/labels/bootstrap  GET /api/v1/labels/products
// POST /api/v1/labels/selected   GET /api/v1/labels/render