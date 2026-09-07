<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use App\Http\Middleware\MaintenanceMode;
use App\Http\Controllers\Admin\SubscriptionRenewalController;

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PlatformAuthController;
use App\Http\Controllers\Auth\StorefrontAuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Customer\CustomerPortalController;

use App\Http\Controllers\Admin\PosController;

use App\Http\Controllers\InquiryController;
use App\Http\Controllers\RazorpayTestController;
use App\Http\Controllers\CashfreeWebhookController;

// Artisan helpers — auth-protected to prevent public exposure
// Route::middleware('auth')->prefix('artisan')->group(function () {
//     Route::get('/optimize', function () {
//         Artisan::call('optimize:clear');
//         return Artisan::output();
//     });

//     Route::get('/migrate', function () {
//         Artisan::call('migrate', ['--force' => true]);
//         return Artisan::output();
//     });
    
//     Route::get('/storage-link', function () {
//         Artisan::call('storage:link');
//         return nl2br(Artisan::output());
//     });    
// });

Route::get('/force-logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/admin');
});



/*
|--------------------------------------------------------------------------
| Authenticated Global Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LogoutController::class, 'destroy'])->name('logout');
});

// ══════════════════════════════════════════════════════════════
// CRITICAL: Restrict ALL main-app routes to your primary domain.
// This frees up "/" on custom domains for the storefront.
// ══════════════════════════════════════════════════════════════
$appDomain = parse_url(config('app.url'), PHP_URL_HOST);

Route::domain($appDomain)->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Welcome Page
    |--------------------------------------------------------------------------
    */
    Route::middleware(MaintenanceMode::class)->group(function () {
        Route::get('/', [InquiryController::class, 'index'])->name('welcome');
        Route::post('/inquire', [InquiryController::class, 'inquire'])->name('welcome.inquire');
    });


    /*
    |--------------------------------------------------------------------------
    | Guest Routes (Unauthenticated)
    |--------------------------------------------------------------------------
    */
    Route::middleware('guest')->group(function () {

        Route::get('/login',  [PlatformAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [PlatformAuthController::class, 'login'])->name('login.store');
        // Tenant signup is handled manually from the platform panel.
    
        // Password Recovery — OTP flow
        Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
        Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
        Route::get('/forgot-password/verify', [ForgotPasswordController::class, 'showVerify'])->name('password.verify');
        Route::post('/forgot-password/verify', [ForgotPasswordController::class, 'storeVerify'])->name('password.verify.store');
    });

    // 🌟 Public token-secured receipt routes — intentionally outside auth middleware
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/share/{id}/{token}',      [PosController::class, 'publicReceipt'])->name('receipt.public');
        Route::get('/share/{id}/{token}/pdf',  [PosController::class, 'downloadPdf'])->name('receipt.pdf');
    });

    // Subscription + renewal — auth required, but NOT subscription-gated, so an
    // owner whose plan expired can still reach this page and renew.
    Route::middleware(['auth', 'redirect.if.subscribed'])->group(function () {
        
        Route::get('/subscriptions', [SubscriptionRenewalController::class, 'index'])
            ->name('subscriptions.index');
    
        Route::post('/subscription/renew/apply-coupon', [SubscriptionRenewalController::class, 'applyCoupon'])
            ->name('subscription.renew.apply-coupon');

        Route::post('/subscription/renew/init', [SubscriptionRenewalController::class, 'initiate'])
            ->name('subscription.renew.init');
    
        Route::post('/subscription/renew/confirm', [SubscriptionRenewalController::class, 'confirm'])
            ->name('subscription.renew.confirm');
    });

/*
|--------------------------------------------------------------------------
| Customer Storefront Routes
|--------------------------------------------------------------------------
| These routes are loaded by RouteServiceProvider/bootstrap app.php
| and should be prefixed with '/{slug}/portal' or similar.
*/

    Route::prefix('{slug}')->name('storefront.')->group(function () {

        // ── GUEST ROUTES (Login & Register) ──
        Route::middleware('guest')->group(function () {
            Route::get('/login', [StorefrontAuthController::class, 'showLoginForm'])->name('login');
            Route::post('/login', [StorefrontAuthController::class, 'login'])->name('login.submit');

            Route::get('/register', [StorefrontAuthController::class, 'showRegisterForm'])->name('register');
            Route::post('/register', [StorefrontAuthController::class, 'register'])->name('register.submit');
        });

        // ── PROTECTED CUSTOMER ROUTES ──
        // We will create a 'customer' middleware to ensure Super Admins/Staff don't get stuck here
        Route::middleware(['auth', 'customer'])->prefix('portal')->name('portal.')->group(function () {

            Route::post('/logout', [StorefrontAuthController::class, 'logout'])->name('logout');
            Route::get('/dashboard', [CustomerPortalController::class, 'index'])->name('dashboard');
            Route::get('/orders', [CustomerPortalController::class, 'orders'])->name('orders');

            // Addresses Management
            Route::get('/addresses', [CustomerPortalController::class, 'addresses'])->name('addresses');
            Route::post('/addresses', [CustomerPortalController::class, 'storeAddress'])->name('addresses.store');

            // Profile Management
            Route::get('/profile', [CustomerPortalController::class, 'profile'])->name('profile');
            Route::post('/profile', [CustomerPortalController::class, 'updateProfile'])->name('profile.update');

        });
    });


});

/*
|==========================================================================
| CUSTOMER AUTH (GUEST) — Host-based tenant (subdomain / custom domain)
|==========================================================================
| NO domain restriction → tenant resolved from the HOST:
|   • Subdomain     acme.smartbiz.in/login → customer login (scoped)
|   • Custom domain clientshop.com/login   → customer login (scoped)
|   • Apex          smartbiz.in/login      → controller abort(404) (no tenant, Q1a)
|
| Slug mode (smartbiz.in/{slug}/login) stays inside the domain group above
| (name: storefront.login). Both share CustomerAuthController.
|==========================================================================
*/
Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login',  [StorefrontAuthController::class, 'showLoginForm'])->name('customer.login');
    Route::post('/login', [StorefrontAuthController::class, 'login'])->name('customer.login.store');

    // Register
    Route::get('/register',  [StorefrontAuthController::class, 'showRegisterForm'])->name('customer.register');
    Route::post('/register', [StorefrontAuthController::class, 'register'])->name('customer.register.store');
});

// Host-based customer portal (subdomain / custom domain). Mirrors the slug
// portal above; tenant resolved from host. Same middleware as the slug portal.
Route::middleware(['auth', 'customer'])->prefix('portal')->name('customer.portal.')->group(function () {
    Route::post('/logout',   [StorefrontAuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [CustomerPortalController::class, 'index'])->name('dashboard');
    Route::get('/orders',    [CustomerPortalController::class, 'orders'])->name('orders');

    Route::get('/addresses',  [CustomerPortalController::class, 'addresses'])->name('addresses');
    Route::post('/addresses', [CustomerPortalController::class, 'storeAddress'])->name('addresses.store');

    Route::get('/profile',  [CustomerPortalController::class, 'profile'])->name('profile');
    Route::post('/profile', [CustomerPortalController::class, 'updateProfile'])->name('profile.update');
});

Route::post('/webhooks/cashfree/subscription', [CashfreeWebhookController::class, 'handle'])
    ->name('cashfree.webhook');


// Temporary Razorpay Testing Routes
// Route::get('/razorpay-test', [RazorpayTestController::class, 'index']);
// Route::post('/razorpay-test/verify', [RazorpayTestController::class, 'verify']);