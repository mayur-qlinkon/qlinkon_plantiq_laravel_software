<?php

use App\Http\Controllers\RazorpayWebhookController;
use App\Http\Controllers\Storefront\AppointmentBookingController;
use App\Http\Controllers\Storefront\OrderController;
use App\Http\Controllers\Storefront\StorefrontPageController;
use App\Http\Controllers\Storefront\StorefrontController;
use App\Http\Controllers\Storefront\TtsController;
use App\Http\Middleware\CheckStorefrontStatus;
use Illuminate\Support\Facades\Route;

Route::prefix('{slug}')
    ->name('storefront.')    
    ->middleware(['subscription', CheckStorefrontStatus::class])
    ->group(function () {

        Route::controller(StorefrontController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/c/{categorySlug}', 'category')->name('category');
            Route::get('/p/{productSlug}', 'show')->name('product');
            Route::get('/search', 'search')->name('search');
            Route::get('/suggest', 'suggest')->name('suggest');
            Route::post('/analytics/section/view', 'trackView')->name('analytics.section.view');
            Route::post('/analytics/section/{id}/click', 'trackClick')->name('analytics.section.click');
           Route::post('/inquiry', 'inquiry')->name('inquiry');
        });

        // ── Text-to-speech (public) ──
        // Throttled: this endpoint triggers a paid external API on cache miss,
        // so an unthrottled loop here would translate directly into billing.
        Route::post('/tts/product-guide', [TtsController::class, 'productGuide'])
            ->middleware('throttle:tts')
            ->name('tts.product-guide');

        Route::prefix('orders')
            ->name('orders.')
            ->controller(OrderController::class)
            ->group(function () {

                Route::post('/', 'store')->name('store');
                Route::post('/preview', 'previewTotals')->name('preview');
                Route::get('/{orderNumber}', 'show')->name('show');
                Route::get('/{orderNumber}/receipt', 'downloadReceipt')->name('receipt');
        });
        // ── Appointments (public booking) ──
        Route::prefix('appointments')
            ->name('appointments.')
            ->controller(AppointmentBookingController::class)
            ->group(function () {
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

        Route::get('/page/{pageSlug}', [StorefrontPageController::class, 'show'])
            ->name('page.show');

    });

Route::post('/{slug}/webhooks/razorpay', [RazorpayWebhookController::class, 'handle'])
    ->name('webhooks.razorpay');
