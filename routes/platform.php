<?php
use App\Http\Controllers\Platform\ContactInquiryController;
use App\Http\Controllers\Platform\ModuleController;
use App\Http\Controllers\Platform\PermissionController;
use App\Http\Controllers\Platform\PlatformDashboardController;
use App\Http\Controllers\Platform\PlatformSeederController;
use App\Http\Controllers\Platform\SystemSettingController;
use App\Http\Controllers\Platform\HelpController;
use App\Http\Controllers\Platform\AddonController;
use App\Http\Controllers\Platform\PlanCalculatorController;
use App\Http\Controllers\Platform\CoreFeatureController;
use App\Http\Controllers\Platform\PromotionController;
use App\Http\Controllers\Platform\PromotionUsageController;
use App\Http\Controllers\Platform\PaymentLogController;
use App\Http\Controllers\Platform\PlantProfileController;
use App\Http\Controllers\Platform\PlatformOnboardingController;
use App\Http\Controllers\Platform\ProfileKitController;
use App\Http\Controllers\Platform\PlantLibraryController;
use App\Http\Controllers\Platform\PlantLibraryImportController;

use Illuminate\Support\Facades\Route;

Route::middleware(['auth','super_admin'])
    ->prefix('platform')
    ->name('platform.')
    ->group(function () {

        Route::get('/dashboard', [PlatformDashboardController::class, 'index'])->name('dashboard');
        Route::get('/', [PlatformDashboardController::class, 'index']); // Fallback so /platform goes to dashboard

        Route::resource('modules', ModuleController::class)->except(['create', 'show', 'edit']);
        Route::resource('plant-profiles', PlantProfileController::class)->except(['show']);
        Route::resource('profile-kits', ProfileKitController::class)->except(['show']);        
        Route::resource('promotions', PromotionController::class);

        // Subscription payments (plan builder + renewals)
        Route::get('payments', [PaymentLogController::class, 'index'])->name('payments.index');
        Route::get('payments/{payment}', [PaymentLogController::class, 'show'])->name('payments.show');

        // Coupon redemption records
        Route::get('promotion-usages', [PromotionUsageController::class, 'index'])->name('promotion-usages.index');
        Route::resource('addons', AddonController::class)->except(['show']);

        // Read-only pricing calculator — no writes, no payment.
        Route::get('plan-calculator', [PlanCalculatorController::class, 'index'])->name('plan-calculator.index');
        Route::post('plan-calculator/quote', [PlanCalculatorController::class, 'quote'])->name('plan-calculator.quote');
        Route::resource('core-features', CoreFeatureController::class)->except(['show']);        

         // ── Unified Company + Plan + Subscription Management ──
        // "tenants" in the URL and route names: the Client model on the tenant
        // side is a tenant's own customer, so the word meant two things.
        Route::prefix('tenants')->name('tenants.')->group(function () {
            Route::get('/',                        [PlatformOnboardingController::class, 'index'])->name('index');
            Route::get('/create',                  [PlatformOnboardingController::class, 'create'])->name('create');
            Route::post('/',                       [PlatformOnboardingController::class, 'store'])->name('store');
            Route::get('/slug-check',              [PlatformOnboardingController::class, 'slugCheck'])->name('slug-check');
            Route::get('/{tenant}',                [PlatformOnboardingController::class, 'show'])->name('show');
            Route::get('/{tenant}/edit',           [PlatformOnboardingController::class, 'edit'])->name('edit');
            Route::put('/{tenant}',                [PlatformOnboardingController::class, 'update'])->name('update');
            Route::delete('/{tenant}',             [PlatformOnboardingController::class, 'destroy'])->name('destroy');
            Route::get('/{tenant}/slug-check',     [PlatformOnboardingController::class, 'slugCheck'])->name('slug-check.edit');
        });


        Route::controller(SystemSettingController::class)
            ->prefix('system')
            ->name('system.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::put('/', 'update')->name('update');
                Route::post('/reset', 'reset')->name('reset');
                Route::post('/clear-cache', 'clearCache')->name('clear-cache');
            });

        // ── Contact Inquiries ──
        Route::controller(ContactInquiryController::class)
            ->prefix('inquiries')
            ->name('inquiries.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/{contactInquiry}', 'show')->name('show');
                Route::delete('/{contactInquiry}', 'destroy')->name('destroy');
            });

        // ── SYSTEM PERMISSIONS ──

        // Sync Defaults (Must be defined before routes with {parameters})
        Route::post('permissions/sync', [PermissionController::class, 'syncDefault'])->name('permissions.sync');

        // Standard CRUD (Single-page modal setup)
        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::post('permissions', [PermissionController::class, 'store'])->name('permissions.store');
        Route::put('permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
        Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

        // ── Help Center
        Route::prefix('help')
            ->name('help.')
            ->controller(HelpController::class)
            ->group(function () {

                // Articles
                Route::get('/',                     'index')->name('index');
                Route::post('/',                    'store')->name('store');
                Route::put('/{helpArticle}',        'update')->name('update');
                Route::delete('/{helpArticle}',     'destroy')->name('destroy');
                Route::post('/{helpArticle}/toggle','togglePublish')->name('toggle');

                // Categories
                Route::get('/categories',                    'categoriesIndex')->name('categories');
                Route::post('/categories',                   'storeCategory')->name('categories.store');
                Route::put('/categories/{helpCategory}',     'updateCategory')->name('categories.update');
                Route::delete('/categories/{helpCategory}',  'destroyCategory')->name('categories.destroy');

                Route::get('/feedback',                      'feedbackIndex')->name('feedback');
        });
                


        // ── Visual Seeder Platform ──
        Route::controller(PlatformSeederController::class)
            ->prefix('seeders')
            ->name('seeders.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/execute', 'execute')->name('execute');
            });
        
        // ── Plant Education Master Library ──
        // Declared above the resource so 'import' is never swallowed by the
        // {plantLibrary} wildcard if the resource routes are reordered later.
        Route::get('plant-library/import/sample', [PlantLibraryController::class, 'downloadSample'])->name('plant-library.import.sample');
        Route::post('plant-library/import', [PlantLibraryController::class, 'import'])->name('plant-library.import');
        Route::resource('plant-library', PlantLibraryController::class);
        Route::post('plant-library/{plantLibrary}/media', [PlantLibraryController::class, 'storeMedia'])->name('plant-library.media.store');
        Route::delete('plant-library/{plantLibrary}/media/{media}', [PlantLibraryController::class, 'destroyMedia'])->name('plant-library.media.destroy');
        Route::post('plant-library/{plantLibrary}/media/{media}/primary', [PlantLibraryController::class, 'setPrimaryMedia'])->name('plant-library.media.primary');

        // ── Plant Library → Tenant Import ──
        Route::get('plant-library-import', [PlantLibraryImportController::class, 'index'])->name('plant-library-import.index');
        Route::get('plant-library-import/{company}', [PlantLibraryImportController::class, 'select'])->name('plant-library-import.select');
        Route::post('plant-library-import/{company}', [PlantLibraryImportController::class, 'store'])->name('plant-library-import.store');


    });
