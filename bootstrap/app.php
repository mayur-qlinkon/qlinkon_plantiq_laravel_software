<?php

use App\Http\Middleware\CheckModuleAccess;
use App\Http\Middleware\CheckPendingAnnouncements;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureStoreExists;
use App\Http\Middleware\EnsureValidStoreSession;

use App\Http\Middleware\EnsureUserIsSuperAdmin;
use App\Http\Middleware\IsCustomer;
use App\Http\Middleware\EnsureEmployeeProfile;


use App\Http\Middleware\RedirectIfSubscribed;
use App\Http\Middleware\ResolveCustomDomain;
use App\Http\Middleware\ResolveStorePublic;
use App\Http\Middleware\BlockClientAccess;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\RebuildTenantSessionContext;
use App\Http\Middleware\EnsureAuthUserBelongsToTenant;
use App\Http\Middleware\Production\EnsureCheckedInToday;
use App\Http\Middleware\ResolveApiStoreContext;
use App\Http\Middleware\BindTenantFromUser;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/pwa.php'));
            Route::middleware('web')
                ->group(base_path('routes/custom_domain.php'));
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));

            Route::middleware('web')
                ->group(base_path('routes/platform.php'));

            Route::middleware('web')
                ->group(base_path('routes/storefront.php'));
        }
    ) 
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        // IdentifyTenant must run first: the guest/user redirect closures and the
        // 419 handler all call tenant_url(), which needs tenant() to be bound.
        // It never touches the session, so running it before StartSession is safe.
        $middleware->web(prepend: [
            IdentifyTenant::class,
        ]);


        // Same tenant resolution for the API group — subdomain-based (e.g.
        // acme.qlinkonbizness.com/api/v1/...) so the Flutter app resolves
        // its company exactly like the web app does, no extra client-side
        // tenant plumbing needed.
        $middleware->api(prepend: [
            IdentifyTenant::class,
        ]);


        // These two read Auth::user(), so they MUST run after StartSession.
        // Prepending them placed both ahead of StartSession, where Auth::user()
        // is always null — meaning the cross-tenant guard and the session-context
        // rebuild were silently no-ops on every single request.
        $middleware->web(append: [
            RebuildTenantSessionContext::class,
            EnsureAuthUserBelongsToTenant::class,
        ]);

        // Unauthenticated redirects — tenant-mode aware.
        // Customer portal → customer login; everything backend → admin login.
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('portal/*', '*/portal/*')) {
                return tenant_url('login');   // customer area
            }
            return tenant_url('admin');       // admin / platform / other backend
        });

        // Already-authenticated user hitting a guest-only route (login
        // form) — send to their own dashboard instead of Laravel's
        // default, non-tenant-aware HOME. This is what fixes the PWA
        // reopen case: start_url loads the tenant login route, and if
        // the session is still valid this fires before the controller
        // ever runs.
        $middleware->redirectUsersTo(function () {
            return homeUrl();
        });

        $middleware->validateCsrfTokens(except: [
            '*/webhooks/razorpay',
            'webhooks/cashfree/subscription',

            // Logout must never fail with a 419. A stale token here would bounce
            // the user to the login page while still authenticated, and the
            // guest-redirect would send them straight back — an unbreakable loop.
            'portal/logout',
            '*/portal/logout',
            'logout',
            '*/logout',
        ]);
        $middleware->alias([
            'permission' => CheckPermission::class,
            'module' => CheckModuleAccess::class,
            'subscription' => CheckSubscription::class,
            'announcements' => CheckPendingAnnouncements::class,
            
            'tenant.bind' => BindTenantFromUser::class,
            'store.context' => ResolveApiStoreContext::class,
            'store.exists' => EnsureStoreExists::class,
            'store.session' => EnsureValidStoreSession::class,
            'custom.domain' => ResolveCustomDomain::class,
            'identify.tenant' => IdentifyTenant::class,
            'store.public' => ResolveStorePublic::class,  
            'redirect.if.subscribed' => RedirectIfSubscribed::class,        
            
            'block.client' => BlockClientAccess::class, 
            'checked_in' => EnsureCheckedInToday::class,

            'customer' => IsCustomer::class,
            'super_admin' => EnsureUserIsSuperAdmin::class,
            'employee.profile' => EnsureEmployeeProfile::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Return JSON 429 for AI chatbot rate limit — prevents JS frontend crash
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, Request $request) {
            if ($request->is('*/ai-chatbot/chat')) {
                return response()->json([
                    'success' => false,
                    'reply'   => 'You are sending messages too fast. Please wait a moment and try again.',
                ], 429);
            }
        });

        // Handle 419 (CSRF token mismatch) with a silent redirect instead of an
        // error page. NOTE: Laravel's prepareException() converts
        // TokenMismatchException into a generic HttpException(419) BEFORE render
        // callbacks are matched, so type-hinting TokenMismatchException here
        // never fires. We must catch HttpException and filter on the status code.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null; // Let 403/404/500 fall through to default rendering.
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your session expired. Please refresh and try again.',
                ], 419);
            }

            $target = $request->is('portal/*', '*/portal/*')
                ? tenant_url('login')
                : tenant_url('admin');

            return redirect()->to($target)
                ->with('error', 'Your session expired. Please log in again.');
        });
    })->create();
