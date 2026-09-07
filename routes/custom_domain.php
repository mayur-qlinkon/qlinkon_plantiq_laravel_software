<?php
use App\Http\Controllers\Storefront\OrderController;
use App\Http\Controllers\Storefront\StorefrontController;
use App\Http\Controllers\Storefront\StorefrontPageController;
use App\Http\Controllers\Storefront\AppointmentBookingController;
use App\Http\Controllers\Storefront\TtsController;
use App\Http\Middleware\CheckStorefrontStatus;
use App\Http\Middleware\ResolveCustomDomain;
use Illuminate\Support\Facades\Route;

// All routes here are protected by ResolveCustomDomain, which aborts(404)
// if the host is not a registered custom domain. This means these routes
// are effectively invisible to your main domain.

Route::middleware([ResolveCustomDomain::class, CheckStorefrontStatus::class])
    ->group(function () {

        // ── Public storefront ──
        Route::controller(StorefrontController::class)->group(function () {
            Route::get('/', 'index')->name('custom_domain.storefront.index');
            Route::get('/c/{categorySlug}', 'category');
            Route::get('/p/{productSlug}', 'show');
            Route::get('/search', 'search');
            Route::get('/suggest', 'suggest');
            Route::post('/analytics/section/view', 'trackView');
            Route::post('/analytics/section/{id}/click', 'trackClick');
            Route::post('/inquiry', 'inquiry');
        });

        // ── Text-to-speech (public) ──
        Route::post('/tts/product-guide', [TtsController::class, 'productGuide'])
            ->middleware('throttle:tts');

        // Custom pages
        Route::get('/page/{pageSlug}', [StorefrontPageController::class, 'show']);

        // Orders
        Route::prefix('orders')->controller(OrderController::class)->group(function () {
            Route::post('/', 'store');
            Route::post('/preview', 'previewTotals');
            Route::get('/{number}', 'show');
            Route::get('/{orderNumber}/receipt', 'downloadReceipt');
        });

        // Appointments (public booking)
        // Named to match the slug routes so route() resolves on either host.
        Route::prefix('appointments')->name('appointments.')->controller(AppointmentBookingController::class)->group(function () {
            Route::post('/book', 'book')
                ->middleware('throttle:appointment-book')
                ->name('book');

            Route::get('/check-slot', 'checkSlot')
                ->middleware('throttle:appointment-availability')
                ->name('check-slot');

            Route::get('/booked-slots', 'bookedSlots')
                ->middleware('throttle:appointment-availability')
                ->name('booked-slots');
        });

        });

// NOTE: Admin routes (/admin/*) already work on custom domains automatically!
// They are in admin.php with prefix 'admin' and NO domain restriction.
// clientdomain.com/admin → works ✅