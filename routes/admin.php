<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\TenantAdminAuthController;
use App\Http\Controllers\Auth\PlatformAuthController;

use App\Http\Controllers\Platform\ContactInquiryController;
use App\Http\Controllers\Platform\OnboardingController;

// HRM Controllers
use App\Http\Controllers\Admin\AnnouncementPopupController;
use App\Http\Controllers\Admin\Hrm\EmployeeDashboardController;

use App\Http\Controllers\Admin\PosController;
use App\Http\Controllers\Admin\AdminOrderController;

// Core
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HelpCenterController;
use App\Http\Controllers\Admin\AuditLogController;

/*
|==========================================================================
| PHASE 2 IN ACTION: TENANT (BUSINESS OWNER) ROUTES
|==========================================================================
| 1. 'auth'         -> Must be logged in.
| 2. 'role:owner'   -> Must be the business owner (or have owner permissions).
| 3. 'subscription' -> THE BOUNCER! Kicks them out if they haven't paid or plan expired.
|==========================================================================
*/
/*
|==========================================================================
| ADMIN AUTH (GUEST) — Clean Separation!
|==========================================================================
*/
Route::middleware('guest')->group(function () {

    // 1. SLUG-BASED TENANT (smartbiz.in/acme/admin)
    // Strictly handled by TenantAdminAuthController
    Route::get('/{slug}/admin',  [TenantAdminAuthController::class, 'showLoginForm'])->name('admin.login.slug');
    Route::post('/{slug}/admin', [TenantAdminAuthController::class, 'login'])->name('admin.login.slug.store');

    // 2. HOST-BASED (Apex vs Subdomain/Custom Domain)
    // Both hit '/admin', so we route dynamically based on whether tenant() resolved!
    $tenant = tenant(); 

    if ($tenant) {
        // If tenant exists -> It's a Custom Domain (clientshop.com/admin) or Subdomain (acme.smartbiz.in/admin)
        Route::get('/admin',  [TenantAdminAuthController::class, 'showLoginForm'])->name('admin.login.tenant');
        Route::post('/admin', [TenantAdminAuthController::class, 'login'])->name('admin.login.tenant.store');
    } else {
        // If NO tenant -> It's the Apex Domain (smartbiz.in/admin) - Super Admins & Owners global login!
        Route::get('/admin',  [PlatformAuthController::class, 'showLoginForm'])->name('admin.login');
        Route::post('/admin', [PlatformAuthController::class, 'login'])->name('admin.login.store');
    }
});

Route::middleware(['auth', 'subscription', 'store.exists', 'block.client', 'announcements'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {                                                                                           
            // --- ONBOARDING ROUTES ---
            Route::get('/welcome', [OnboardingController::class, 'index'])->name('onboarding.index');
            Route::post('/welcome', [OnboardingController::class, 'store'])->name('onboarding.store');
            Route::post('/switch-store', [StoreController::class, 'switch'])->name('store.switch');

            // ── CORE ──────────────────────────────────────────────────────────────
            // The launcher is the universal home. The old metrics dashboard keeps all of
            // its behaviour but now lives under its own sales-specific URL.
            Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');
            Route::get('/sales/dashboard', [DashboardController::class, 'index'])
                ->middleware('permission:sales_dashboard.view')
                ->name('sales.dashboard');
            Route::get('/my-dashboard', [EmployeeDashboardController::class, 'index'])->name('employee.dashboard');
            Route::resource('/stores', StoreController::class)->middleware('permission:stores.view');   
            Route::resource('/users', UserController::class)->middleware('permission:users.view'); 
            // Declared before the resource so the static segment is not read as
            // a {client} route parameter.
            Route::post('/clients/bulk-destroy', [ClientController::class, 'bulkDestroy'])
                ->name('clients.bulk-destroy')
                ->middleware('permission:clients.delete');

            Route::resource('/clients', ClientController::class)->except(['create', 'edit', 'show'])->middleware('permission:clients.view');        
            Route::resource('/roles', RoleController::class)->middleware('permission:roles.view');
            Route::resource('/payment-methods', PaymentMethodController::class)->except(['create', 'edit', 'show'])->middleware('permission:payment_methods.view');
            Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');            
            Route::get('/clients/pdf', [ClientController::class,'downloadPdf'])->name('clients.download-pdf');   
            Route::get('/clients/csv', [ClientController::class,'downloadCsv'])->name('clients.download-csv');   

            // ── SETTINGS ──────────────────────────────────────────────────────────────
            Route::middleware('permission:settings.view')->group(function () {
                Route::prefix('settings')->name('settings.')->controller(SettingController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('/', 'update')->name('update');
                    Route::post('/notifications', 'updateNotifications')->name('notifications.update');
                    Route::post('/clear-cache', 'clearCache')->name('clear-cache');
                    Route::post('/reset', 'resetAll')->name('reset');
                    Route::get('/audit', 'auditTrail')->name('audit');
                });
            });            

            // ── NOTIFICATIONS ──────────────────────────────────────────────────────────────
            // NOTE: fetch-recent is intentionally NOT registered here. It runs on a
            // lightweight middleware stack declared below this group.
            Route::prefix('notifications')->name('notifications.')->controller(NotificationController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/{id}/read', 'markAsRead')->name('read');
                Route::post('/mark-all-read', 'markAllRead')->name('mark-all-read');
            }); 
            // ── Help Center (Platform Content — Read Only for Tenants) ──
            // NOTE: uses {article_slug}, NOT {slug} — tenant() / IdentifyTenant
            // resolve the tenant from request()->route('slug'), so a route
            // parameter literally named {slug} here would shadow the tenant
            // slug and break auth on slug-based tenant URLs (/{slug}/admin/...).
            Route::prefix('help-center')
                ->name('help.')
                ->controller(HelpCenterController::class)
                ->group(function () {
                    Route::get('/',          'index')->name('index');
                    Route::get('/{article_slug}',    'show')->name('show');

                    Route::post('/{article_slug}/feedback', 'storeFeedback')->name('feedback');
            });

            // Contact Inquiry (from Help Center)
            Route::post('/contact-inquiry', [ContactInquiryController::class, 'store'])
                ->name('contact.store');

            // ── Announcement Popup (employee-facing AJAX) ──
            Route::prefix('announcements-popup')->name('announcements-popup.')->controller(AnnouncementPopupController::class)->group(function () {
                Route::get('/pending', 'pending')->name('pending');
                Route::post('/{announcement}/read', 'markRead')->name('read');
                Route::post('/{announcement}/acknowledge', 'acknowledge')->name('acknowledge');
                Route::post('/{announcement}/dismiss', 'dismiss')->name('dismiss');
            });                                                                                                                                                           
            // ── ORDERS ──────────────────────────────────────────────────────────────
            Route::middleware(['module:inquiry','permission:orders.view'])->group(function () {
                Route::prefix('orders')
                    ->name('orders.')
                    ->controller(AdminOrderController::class)
                    ->group(function () {
                        Route::get('/', 'index')->name('index');
                        Route::get('/create', 'create')->name('create');
                        // Must be declared before /{order} so the literal
                        // segment is not captured as an order id.
                        Route::get('/search-skus', 'searchSkus')->name('search-skus');
                        Route::get('/{order}/edit', 'edit')->name('edit');
                        Route::post('/', 'store')->name('store');
                        Route::get('/{order}', 'show')->name('show');
                        Route::put('/{order}', 'update')->name('update');
                        Route::patch('/{order}/logistics', 'updateLogistics')->name('logistics');
                        Route::post('/{order}/status', 'updateStatus')->name('status');
                        Route::post('/{order}/cancel', 'cancel')->name('cancel');
                        Route::post('/{order}/note', 'addNote')->name('note');                    
                        Route::get('/{order}/receipt', 'downloadReceipt')->name('receipt');                        
                    });
            });
            // ════════════════════════════════════════════════
            // Inventory MODULE
            // ════════════════════════════════════════════════
            require __DIR__.'/modules/inventory.php';

            // ════════════════════════════════════════════════
            // Purchases MODULE
            // ════════════════════════════════════════════════
            require __DIR__.'/modules/purchases.php';
            
            // ════════════════════════════════════════════════
            // Invoicing MODULE
            // ════════════════════════════════════════════════
            require __DIR__.'/modules/invoicing.php';

            // ════════════════════════════════════════════════
            // POS MODULE
            // ════════════════════════════════════════════════
            require __DIR__.'/modules/pos.php';            

            // ════════════════════════════════════════════════
            // CRM MODULE
            // ════════════════════════════════════════════════
            require __DIR__.'/modules/crm.php';

            // ════════════════════════════════════════════════
            // PROJECT MODULE
            // ════════════════════════════════════════════════
            require __DIR__.'/modules/projects.php';

            // ════════════════════════════════════════════════
            // EXPENSE MODULE
            // ════════════════════════════════════════════════
            require __DIR__.'/modules/expenses.php';

            // ════════════════════════════════════════════════
            // PRODUCTION MODULE
            // ════════════════════════════════════════════════
            require __DIR__.'/modules/production.php';

            // ════════════════════════════════════════════════
            // HRM MODULE
            // ════════════════════════════════════════════════
            require __DIR__.'/modules/hrm.php';        
            
            // ════════════════════════════════════════════════
            // Storefront MODULE
            // ════════════════════════════════════════════════
            require __DIR__.'/modules/storefront.php';    

            // ════════════════════════════════════════════════
            // APPOINTMENTS MODULE
            // ════════════════════════════════════════════════
            require __DIR__.'/modules/appointments.php';
            
            // ════════════════════════════════════════════════
            // TOOLS MODULES
            // ════════════════════════════════════════════════
            require __DIR__.'/modules/tools.php';                                    
});                                    


/*
|==========================================================================
| LIGHTWEIGHT POLLING ENDPOINT
|==========================================================================
| Hit every 30 seconds by every open admin tab, so it deliberately skips the
| navigation gates that the rest of /admin/* uses.
|
| Dropped middleware and why it is safe:
|
| subscription  — gates paid functionality. Reading your own unread count is
|                 not billable functionality, and an expired tenant is still
|                 bounced the moment they navigate anywhere. Saves one
|                 database-cache query per poll.
| store.exists  — an onboarding redirect. It returns a redirect response,
|                 which is meaningless (and harmful) for a JSON endpoint.
| announcements — blocks navigation until mandatory announcements are
|                 acknowledged, and returns 409 to AJAX callers. That 409 was
|                 silently killing polling for any user with a pending
|                 announcement. It gates navigation, not notification reads.
|
| Retained protection:
|   auth          — request must be authenticated.
|   block.client  — customer accounts can never reach admin notifications.
|   Global web stack still runs: IdentifyTenant, StartSession,
|   RebuildTenantSessionContext, EnsureAuthUserBelongsToTenant.
|
| Tenant isolation is unchanged: EnsureAuthUserBelongsToTenant still logs out
| any session whose company_id does not match the resolved tenant, and the
| query itself is scoped to notifiable_id = the authenticated user's own id.
| No tenant-wide notification query exists on this path.
*/
Route::middleware(['auth', 'block.client'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/notifications/fetch-recent', [NotificationController::class, 'fetchRecent'])
            ->name('notifications.fetch-recent');
    });
                            
