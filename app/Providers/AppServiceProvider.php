<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Company;
use App\Models\User;
use App\Models\Hrm\Leave;
use App\Models\Page;
use App\Models\Hrm\Announcement;
use App\Models\Hrm\SalarySlip;
use App\Models\Hrm\WorkLog;

use App\Policies\Hrm\AnnouncementPolicy;
use App\Policies\Hrm\LeavePolicy;
use App\Policies\Hrm\SalarySlipPolicy;
use App\Policies\Hrm\WorkLogPolicy;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route as RouteFacade;
use App\Providers\Auth\TenantScopedUserProvider;



class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ── STATELESS API SESSIONS ───────────────────────────────────────
        // routes/api.php starts a session so that active_store() and the
        // other store helpers keep working unchanged for the Flutter app.
        // A native client never returns the session cookie, so with the
        // real driver (database) every single API request would write one
        // permanently orphaned row. The array driver keeps the session in
        // memory for the request and persists nothing.
        if (! $this->app->runningInConsole() && $this->app['request']->is('api/*')) {
            config(['session.driver' => 'array']);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useTailwind();
        Auth::provider('tenant_eloquent', function ($app, array $config) {
            return new TenantScopedUserProvider($app['hash'], $config['model']);
        });
        
        RouteFacade::pattern('slug', '[A-Za-z0-9\-]+');
        Gate::policy(Announcement::class, AnnouncementPolicy::class);
        Gate::policy(Leave::class, LeavePolicy::class);
        Gate::policy(SalarySlip::class, SalarySlipPolicy::class);
        Gate::policy(WorkLog::class, WorkLogPolicy::class);

        // ── API Login Rate Limiter ───────────────────────────────────────────
        // Login is the one endpoint that looks users up across every company,
        // so it is also the one an attacker would use to probe which emails
        // exist. Keyed by IP + login id so a shared office IP does not lock
        // out a whole counter because one device has a stale password.
        RateLimiter::for('api-login', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip().'|'.strtolower((string) $request->input('email')));
        });

        // ── AI Chatbot Rate Limiter ──────────────────────────────────────────
        // 20 requests per minute per user. Keyed by user ID (not IP).
        // This prevents one user from flooding Gemini API and causing cost spikes.
        RateLimiter::for('ai-chat', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?? $request->ip());
        });

        // ── OCR Scanner Rate Limiter ─────────────────────────────────────────
        // Every scan is a billable Gemini Vision call. Keyed by user ID so one
        // account cannot loop uploads and run up cost; the daily plan cap is a
        // separate, slower guard and does not stop a burst on its own.
        RateLimiter::for('ocr-scan', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?? $request->ip());
        });

        // ── Text-to-Speech Rate Limiter ──────────────────────────────────────
        // Public storefront visitors are anonymous, so this is keyed by IP.
        // A cache miss on this endpoint triggers a billable Google TTS call,
        // so the limit exists to stop a scripted loop from running up cost.
        // Configurable via TTS_RATE_LIMIT ("attempts,minutes") in .env.
        RateLimiter::for('tts', function (Request $request) {
            [$attempts, $minutes] = array_pad(
                explode(',', (string) config('tts.limits.rate_limit', '30,1')),
                2,
                1
            );

            return Limit::perMinutes((int) $minutes, (int) $attempts)->by($request->ip());
        });

        // ── Appointment Booking Rate Limiters ────────────────────────────────
        // Both endpoints are public and unauthenticated. Without a limit, a
        // script can fill a tenant's entire calendar with fake bookings and
        // shut the business down.
        RateLimiter::for('appointment-book', function (Request $request) {
            return Limit::perMinutes(10, 5)->by($request->ip());
        });

        RateLimiter::for('appointment-availability', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        View::composer('layouts.storefront', function ($view) {

            // ── Resolve company from URL slug — no auth needed ──
            // Works for public storefront where owner is not logged in
            $slug = request()->route('slug');
            $company = null;

            if ($slug) {
                $company = Company::where('slug', $slug)
                    ->first(); // soft fail — no 404 here, blade handles null
            }

            // ── Fallback: if somehow still null (edge case) ──
            if (! $company && Auth::check()) {
                $company = Auth::user()->company;
            }

            // ── Nav categories — safe even if company is null ──
            $navCategories = $company
                ? Category::where('company_id', $company->id)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->limit(10)
                    ->get()
                : collect();
            $footerPages = $company
                ? Page::where('company_id', $company->id)
                    ->published()
                    ->get()
                    ->keyBy('slug')
                : collect();            

            $view->with(compact('company', 'navCategories', 'footerPages'));
        });

        // ── 2. ADMIN VIEW LOGIC ──
        View::composer('layouts.admin', function ($view) {
            if (Auth::check()) {
                /** @var User $user */
                $user = Auth::user();

                // Eager-load the relations that the admin layout accesses repeatedly.
                //
                // roles.permissions — consumed by every has_permission() and has_module()
                //   call in the sidebar (51 + 20 blade calls). Without eager-loading, the
                //   first has_permission() fires a roles query and N permission queries.
                //   More critically, is_super_admin() calls hasRole()->exists() on each
                //   invocation; with roles pre-loaded here, is_super_admin() detects
                //   relationLoaded('roles') and skips the DB entirely.
                //
                // stores — accessed by active_store() in the header and by the store
                //   switcher dropdown.
                //
                // employee — accessed in the HRM sidebar section and by active_store()
                //   fallback path.
                $user->loadMissing(['roles.permissions', 'stores', 'employee']);

                // Fetch notifications once and derive the count from the loaded
                // collection — avoids the second COUNT(*) query that firing
                // unreadNotifications()->count() would produce.
                $unreadNotifications = $user->unreadNotificationsLimit()->get();

                $view->with([
                    'unreadNotifications' => $unreadNotifications,
                    'unreadCount' => $unreadNotifications->count(),
                ]);
            }
        });
    }
}
