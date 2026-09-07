<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Platform\SystemSetting;

use App\Services\Platform\ImageUploadService;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    /**
     * Keys that are allowed to be saved via the UI form.
     * Any key not in this list is silently ignored — prevents mass-assignment.
     *
     * @var string[]
     */
    private const ALLOWED_TEXT_KEYS = [
        // General
        'app_name', 'support_email', 'support_phone', 'timezone',
        // Branding (New)
        'primary_color', 'hover_color', 'theme_config',
        // Security limits (New)
        'password_reset_expiry_minutes', 'otp_length',
        // Mail / SMTP
        'mail_driver', 'mail_host', 'mail_port', 'mail_username',
        'mail_encryption', 'mail_from_email', 'mail_from_name',    
        // SEO
        'seo_title', 'seo_description', 'seo_keywords',
    ];

    /** @var string[] Keys stored as password — never returned to the UI */
    private const PASSWORD_KEYS = ['mail_password'];

    /** @var string[] Keys treated as booleans (checkbox/toggle) */
    private const BOOLEAN_KEYS = [
        'maintenance_mode',
        'allow_public_registration',
        'force_email_verification',
        'enable_2fa',
    ];

    /** @var string[] Keys treated as file uploads */
    private const FILE_KEYS = ['seo_meta_image','app_logo', 'app_favicon', 'default_plant_profile_image'];

    public function __construct(private readonly ImageUploadService $imageService) {}

    /**
     * Display the system settings page.
     */
    public function index(): View
    {
        $flatSettings = SystemSetting::allCached();
        $systemInfo = $this->systemInfo(); // Fetch real-time system stats

        return view('platform.settings.index', compact('flatSettings', 'systemInfo'));
    }

    /**
     * Bulk-update system settings.
     */
    public function update(Request $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            // 1. Text / numeric keys — only whitelisted keys are accepted.
            foreach (self::ALLOWED_TEXT_KEYS as $key) {
                if ($request->has($key)) {
                    SystemSetting::setSetting($key, (string) $request->input($key), $this->determineGroup($key));
                }
            }

            // 2. Password keys — only save if the field is non-empty (user typed a new value).
            foreach (self::PASSWORD_KEYS as $key) {
                $value = $request->input($key);
                if (! empty($value)) {
                    SystemSetting::setSetting($key, (string) $value, $this->determineGroup($key));
                }
            }

            // 3. Boolean toggles — absent from POST = false.
            foreach (self::BOOLEAN_KEYS as $key) {
                SystemSetting::setSetting($key, $request->boolean($key), $this->determineGroup($key), 'boolean');
            }

            // 4. File uploads.
            foreach (self::FILE_KEYS as $fileKey) {
                if ($request->hasFile($fileKey)) {
                    $oldPath = SystemSetting::getSetting($fileKey);
                    if ($oldPath && method_exists($this->imageService, 'delete')) {
                        $this->imageService->delete($oldPath);
                    }

                    $path = $this->imageService->upload($request->file($fileKey), 'settings', [
                        'format' => 'webp',
                        'quality' => 90,
                        'crop' => false,
                    ]);

                    SystemSetting::setSetting($fileKey, $path, 'branding', 'string');
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Settings saved successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[SystemSettingController] Update failed: '.$e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Failed to save settings. Check system logs.'])
                ->withInput();
        }
    }

    /**
     * Reset ALL settings back to their config defaults. (SWAL-confirmed on frontend.)
     */
    public function reset(Request $request): RedirectResponse
    {
        try {
            // Assuming your SystemSetting model has a resetAll() static method or similar logic
            if (method_exists(SystemSetting::class, 'resetAll')) {
                SystemSetting::resetAll();
            } else {
                // Fallback: Truncate the table and clear cache
                SystemSetting::truncate();
                if (method_exists(SystemSetting::class, 'clearCache')) {
                    SystemSetting::clearCache();
                }
            }

            return redirect()->back()->with('success', 'All settings have been reset to their defaults.');
        } catch (\Exception $e) {
            Log::error('[SystemSettingController] Reset failed: '.$e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Failed to reset settings.']);
        }
    }

    /**
     * Clear application caches via artisan (Performance & Cache tab).
     */
    public function clearCache(Request $request): RedirectResponse
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');

            // Also flush the specific settings cache if the method exists
            if (method_exists(SystemSetting::class, 'clearCache')) {
                SystemSetting::clearCache();
            }

            return redirect()->back()->with('success', 'Application cache cleared successfully.');
        } catch (\Throwable $e) {
            Log::error('[SystemSettingController] Cache clear failed: '.$e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Failed to clear cache: ' . $e->getMessage()]);
        }
    }

    // ── Helpers ──

    /**
     * Auto-categorise a key to its database group.
     */
    private function determineGroup(string $key): string
    {
        if (str_starts_with($key, 'mail_') || str_starts_with($key, 'smtp_')) {
            return 'mail';
        }
        if (in_array($key, ['app_logo', 'app_favicon', 'default_plant_profile_image', 'primary_color', 'hover_color', 'theme_config'])) {
            return 'branding';
        }
        if (in_array($key, ['allow_public_registration', 'force_email_verification', 'enable_2fa', 'password_reset_expiry_minutes', 'otp_length'])) {
            return 'security';
        }
        if (str_starts_with($key, 'seo_')) {
            return 'seo';
        }

        return 'general';
    }

    /**
     * Read-only system information for the System tab.
     */
    private function systemInfo(): array
    {
        return [
            'Application'   => config('app.name'),
            'Laravel'       => app()->version(),
            'PHP'           => PHP_VERSION,
            'Environment'   => app()->environment(),
            'Debug Mode'    => config('app.debug') ? 'On' : 'Off',
            'Timezone'      => config('app.timezone'),
            'Database'      => config('database.default'),
            'Cache Driver'  => config('cache.default'),
            'Queue Driver'  => config('queue.default'),
            'Server Time'   => now()->format('d M Y, h:i A'),
        ];
    }
}