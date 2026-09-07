<?php

use Illuminate\Support\Facades\Route;
// Storefront Website
use App\Http\Controllers\Admin\MerchandisingController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\StorefrontSectionController;
use App\Http\Controllers\Admin\StorefrontSectionProductController;
use App\Http\Controllers\Admin\StorefrontBuilderController;
use App\Http\Controllers\Admin\BannerController;

Route::middleware('module:storefront')->group(function () {
    // ── Storefront Pages (CMS) ──
    Route::middleware('permission:pages.view')->group(function () {
        Route::prefix('pages')->name('pages.')->controller(PageController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{page}/edit', 'edit')->name('edit');
            Route::put('/{page}', 'update')->name('update');
            Route::delete('/{page}', 'destroy')->name('destroy');

            // AJAX Quick Toggles
            Route::post('/{page}/toggle', 'togglePublish')->name('toggle');
        });
    });

    // ── Banners ──
    Route::prefix('banners')->middleware(['permission:banners.view'])->name('banners.')->controller(BannerController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{banner}', 'show')->name('show');
        Route::get('/{banner}/edit', 'edit')->name('edit');
        Route::put('/{banner}', 'update')->name('update');
        Route::delete('/{banner}', 'destroy')->name('destroy');
        Route::post('/{banner}/toggle', 'toggleActive')->name('toggle');
        Route::post('/{banner}/duplicate', 'duplicate')->name('duplicate');
        Route::post('/reorder', 'reorder')->name('reorder');
        Route::post('/{id}/restore', 'restore')->name('restore');
        Route::post('/{banner}/click', 'trackClick')->name('track-click');
    });

    // ── Merchandising ──
    Route::prefix('merchandising')
    ->name('merchandising.')
    ->controller(MerchandisingController::class)
    ->group(function () {

        // ── Main page ──
        Route::get('/', 'index')->name('index');

        // ── AJAX: load products for a category ──
        Route::get('/{categoryId}/products', 'loadCategory')->name('load-category');

        // ── AJAX: search unassigned products ──
        Route::get('/{categoryId}/search', 'searchProducts')->name('search');

        // ── AJAX: browse available (unassigned) products, paginated ──
        Route::get('/{categoryId}/available', 'availableProducts')->name('available');

        // ── AJAX: add a product to category ──
        Route::post('/{categoryId}/add', 'addProduct')->name('add-product');

        // ── AJAX: bulk-add multiple products to category ──
        Route::post('/{categoryId}/add-multiple', 'addMultipleProducts')->name('add-multiple');

        // ── AJAX: remove a product from category ──
        Route::delete('/{categoryId}/products/{productId}', 'removeProduct')->name('remove-product');

        // ── AJAX: save drag-drop order ──
        Route::post('/{categoryId}/reorder', 'reorder')->name('reorder');

        // ── AJAX: toggle featured star ──
        Route::post('/{categoryId}/products/{productId}/toggle-featured', 'toggleFeatured')->name('toggle-featured');

        // ── AJAX: toggle per-category visibility ──
        Route::post('/{categoryId}/products/{productId}/toggle-active', 'toggleActive')->name('toggle-active');

    });


    // ── Storefront Sections ──
    Route::middleware(['permission:storefront_sections.view'])->group(function () {
        Route::prefix('storefront-sections')
            ->name('storefront-sections.')
            ->controller(StorefrontSectionController::class)
            ->group(function () {

                // ── Standard CRUD ──
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{storefrontSection}/edit', 'edit')->name('edit');
                Route::put('/{storefrontSection}', 'update')->name('update');
                Route::delete('/{storefrontSection}', 'destroy')->name('destroy');

                // ── AJAX ──
                Route::post('/reorder', 'reorder')->name('reorder');
                Route::post('/{storefrontSection}/toggle', 'toggleActive')->name('toggle');
                Route::post('/{storefrontSection}/duplicate', 'duplicate')->name('duplicate');
            });
    });

    // ── Storefront Builder (unified drag-and-drop editor) ──
    Route::middleware(['permission:storefront_sections.view'])
        ->prefix('storefront-builder')
        ->name('storefront-builder.')
        ->controller(StorefrontBuilderController::class)
        ->group(function () {

            // ── Main builder page ──
            Route::get('/', 'index')->name('index');

            // ── Section AJAX — JSON responses (existing section routes unchanged) ──
            Route::post('/sections', 'storeSection')->name('sections.store');
            Route::patch('/sections/{storefrontSection}', 'updateSection')->name('sections.update');
            Route::delete('/sections/{storefrontSection}', 'destroySection')->name('sections.destroy');

            // ── Banner AJAX — inline banner management inside builder ──
            Route::post('/banners', 'storeBanner')->name('banners.store');
            Route::patch('/banners/{banner}', 'updateBanner')->name('banners.update');
            Route::delete('/banners/{banner}', 'destroyBanner')->name('banners.destroy');
            Route::post('/banners/{banner}/toggle', 'toggleBanner')->name('banners.toggle');
    });

    // ── Storefront Sections Products ──
    Route::prefix('storefront-sections/{storefrontSection}/products')
    ->name('storefront-sections.products.')
    ->controller(StorefrontSectionProductController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/load', 'load')->name('load');
        Route::get('/search', 'search')->name('search');
        Route::post('/', 'add')->name('add');
        Route::delete('/{productId}', 'remove')->name('remove');
        Route::post('/reorder', 'reorder')->name('reorder');
    });
});