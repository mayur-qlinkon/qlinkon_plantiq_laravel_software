<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Setting;
use App\Models\State;
use App\Models\User;
use App\Models\Store;

use App\Enums\NotificationEvent;
use App\Models\NotificationPreference;

use App\Services\Platform\ImageUploadService;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class SettingController extends Controller
{
    // ── Fields stored in the companies table (not settings key-value) ──
    private const COMPANY_FIELDS = [
        'company_name',
        'company_slug',
        'company_email',
        'company_phone',
        'gst_number',
        'currency',
        'address',    
        'city',
        'zip_code',
        'state_id',
        'country',
    ];

    // ── Keys that now belong to a store, not the company ──
    //
    // Billing, bank and invoice content are configured per store from the
    // Store edit screen. Listing them here keeps this controller from writing
    // a company-level copy that nothing would ever read again.
    private const STORE_OWNED_FIELDS = [
        'bank_name',
        'bank_holder',
        'bank_ac',
        'ifsc',
        'bank_branch',
        'upi_id',
        'invoice_prefix',
        'invoice_start_number',
        'quotation_prefix',
        'default_tax_type',
        'payment_terms',
        'round_off',
        'invoice_footer_note',
        'default_terms',
        'signature',
    ];

    // ── File upload fields with their config ──
    private const FILE_FIELDS = [
        'logo' => ['path' => 'settings/logos',      'width' => 600,  'format' => 'webp', 'quality' => 90],
        'icon' => ['path' => 'settings/logos',      'width' => 800,  'format' => 'webp', 'quality' => 90],
        'favicon' => ['path' => 'settings/favicons',   'width' => 64,   'format' => 'webp', 'quality' => 90],
    ];

    // ── Boolean/checkbox fields (unchecked = not in request = false) ──
    private const BOOLEAN_FIELDS = [
        'enable_batch_tracking',
        'enable_product_pricing',
    ];

    // ── Fields to skip entirely (never save to DB) ──
    private const SKIP_FIELDS = [
        '_token',
        '_method',
    ];

    public function __construct(protected ImageUploadService $imageService) {}

    // ════════════════════════════════════════════════════
    //  INDEX
    // ════════════════════════════════════════════════════
    public function index(): View
    {
        try {
            $companyId = Auth::user()->company_id;
            $company = Company::find($companyId);
            $states = State::orderBy('name')->get();

            $users = User::internal()->where('company_id', $companyId)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'email']);

            $notificationConfig = $this->buildNotificationConfig($companyId);

            $stores = Store::where('company_id', $companyId)
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get(['id', 'name']);

            $tabs = array_values(array_filter([
                ['id' => 'company',       'label' => 'Company',       'icon' => 'building-2'],
                ['id' => 'branding',      'label' => 'Branding',      'icon' => 'palette'],
                // Billing, Storefront and SEO now live on the store, since a
                // multi-store tenant needs different values per branch. They
                // are edited from Stores → Edit.
                has_module('appointments')  ? ['id' => 'appointments',  'label' => 'Appointments',  'icon' => 'calendar-check'] : null,
                ['id' => 'notifications', 'label' => 'Notifications', 'icon' => 'bell'],
                ['id' => 'system',        'label' => 'System',        'icon' => 'cpu'],
            ]));

            return view('admin.settings', compact('company', 'states', 'users', 'notificationConfig', 'stores', 'tabs'));

        } catch (\Throwable $e) {
            Log::error('[Settings] Failed to load settings page', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

             return view('admin.settings', [
                'company' => null,
                'states' => collect(),
                'users' => collect(),
                'notificationConfig' => [],
                'tabs' => [
                    ['id' => 'company',       'label' => 'Company',       'icon' => 'building-2'],
                    ['id' => 'branding',      'label' => 'Branding',      'icon' => 'palette'],
                    ['id' => 'notifications', 'label' => 'Notifications', 'icon' => 'bell'],
                    ['id' => 'system',        'label' => 'System',        'icon' => 'cpu'],
                ],
            ]);
        }
    }

    /**
     * Build the notification screen's data: one entry per event the tenant's
     * modules actually include, each carrying its current recipient rows.
     *
     * An event with no saved rows is shown with its default permission row
     * pre-filled, so the screen always reflects what would really happen
     * rather than appearing unconfigured.
     *
     * @return list<array<string,mixed>>
     */
    private function buildNotificationConfig(int $companyId): array
    {
        // A user row whose user no longer exists is dropped from the screen
        // rather than rendered as a nameless entry. The row itself is left in
        // place; the next save rewrites the event's rows from the payload and
        // clears it, and until then the dispatcher already skips it.
        $liveUserIds = User::internal()
            ->where('company_id', $companyId)
            ->pluck('id')
            ->map('strval')
            ->all();

        $saved = NotificationPreference::where('company_id', $companyId)
            ->get()
            ->reject(fn (NotificationPreference $p) => $p->recipient_type === NotificationPreference::TYPE_USER
                && ! in_array($p->recipient_value, $liveUserIds, true))
            ->groupBy(fn (NotificationPreference $p) => $p->event->value);

        $config = [];

        foreach (NotificationEvent::availableToCompany() as $event) {
            $rows = $saved->get($event->value);

            $recipients = $rows
                ? $rows->map(fn (NotificationPreference $p) => [
                    'type' => $p->recipient_type,
                    'value' => $p->recipient_value,
                    'channels' => $p->channels ?? [],
                ])->values()->all()
                : [[
                    'type' => NotificationPreference::TYPE_PERMISSION,
                    'value' => $event->defaultPermission(),
                    'channels' => $event->defaultChannels(),
                ]];

            $config[] = [
                'event' => $event->value,
                'label' => $event->label(),
                'description' => $event->description(),
                'icon' => $event->icon(),
                'defaultPermission' => $event->defaultPermission(),
                'defaultPermissionLabel' => $event->defaultPermissionLabel(),
                'recipients' => $recipients,
            ];
        }

        return $config;
    }

    // ════════════════════════════════════════════════════
    //  UPDATE
    // ════════════════════════════════════════════════════
    public function update(Request $request): JsonResponse
    {
        $companyId = Auth::user()->company_id;

        // ── Basic validation ──
        $request->validate([
            'company_slug' => 'required|string|alpha_dash|max:255|unique:companies,slug,' . $companyId,
            'gst_number' => 'nullable|string|max:15',
            'pan_number' => 'nullable|string|max:10',
            'company_email' => 'nullable|email|max:255',
            'company_phone' => 'nullable|digits:10',
            
            'logo' => 'nullable|file|image|mimes:jpg,jpeg,png,svg,webp|max:2048',
            'icon' => 'nullable|file|image|mimes:jpg,jpeg,png,svg,webp|max:2048',
            'favicon' => 'nullable|file|image|mimes:png,ico,svg,webp|max:512',
            ]);

        DB::beginTransaction();

        try {
            $company = Company::findOrFail($companyId);
            $allInput = $request->except(self::SKIP_FIELDS);
            $companyData = [];
            $settingsData = [];

            // ── Ensure boolean fields default to 0 when unchecked ──
            foreach (self::BOOLEAN_FIELDS as $boolField) {
                $allInput[$boolField] = $request->has($boolField) ? 1 : 0;
            }

            // Drop anything a store owns. Done after the boolean pass so that
            // store-level checkboxes like round_off are removed too, and done
            // server side because hiding the fields in Blade would still leave
            // a hand-crafted POST able to write them.
            $allInput = array_diff_key($allInput, array_flip(self::STORE_OWNED_FIELDS));

            foreach ($allInput as $key => $value) {

                // ── Handle file uploads ──
                if ($request->hasFile($key) && isset(self::FILE_FIELDS[$key])) {
                    try {
                        $config = self::FILE_FIELDS[$key];
                        $oldValue = Setting::where('company_id', $companyId)
                            ->where('store_id', Setting::COMPANY_LEVEL)
                            ->where('key', $key)
                            ->value('value');

                        $value = $this->imageService->upload(
                            file: $request->file($key),
                            path: $config['path'],
                            options: [
                                'old_file' => $oldValue,
                                'width' => $config['width'],
                                'format' => $config['format'],
                                'quality' => $config['quality'],
                            ]
                        );

                        Log::info("[Settings] File uploaded for key '{$key}'", [
                            'company_id' => $companyId,
                            'path' => $value,
                        ]);

                    } catch (\Throwable $e) {
                        Log::error("[Settings] File upload failed for key '{$key}'", [
                            'company_id' => $companyId,
                            'error' => $e->getMessage(),
                        ]);

                        // Skip this field — don't overwrite existing file with null
                        continue;
                    }
                }

                // ── Route to company table or settings table ──
                if (in_array($key, self::COMPANY_FIELDS)) {
                    // Map blade field name → company column name
                    $column = match ($key) {
                        'company_name' => 'name',
                        'company_email' => 'email',
                        'company_phone' => 'phone',
                        'company_slug' => 'slug',
                        default => $key,
                    };
                    $companyData[$column] = $value ?: null;
                } else {
                    // Everything else → settings key-value table
                    $settingsData[$key] = $value;
                }
            }

            // ── Update company table ──
            if (! empty($companyData)) {
                $company->update($companyData);

                Log::info('[Settings] Company record updated', [
                    'company_id' => $companyId,
                    'fields' => array_keys($companyData),
                ]);
            }

            // ── Bulk upsert settings ──
            if (! empty($settingsData)) {
                $this->upsertSettings($companyId, $settingsData);
            }

            // ── Track last saved timestamp ──
            $this->upsertSettings($companyId, [
                '_last_saved' => now()->format('d M Y, h:i A'),
            ]);

            DB::commit();

            // ── Clear settings cache ──
            Cache::forget("company_settings_{$companyId}");

            Log::info('[Settings] Settings saved successfully', [
                'company_id' => $companyId,
                'user_id' => Auth::id(),
                'setting_count' => count($settingsData),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Settings saved successfully!',
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('[Settings] Update failed', [
                'company_id' => $companyId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while saving. Please try again.',
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  UPDATE NOTIFICATION SETTINGS
    // ════════════════════════════════════════════════════
    /**
     * Replace this company's notification preferences with what was submitted.
     *
     * Delete-then-insert per event rather than a diff: the payload is the
     * complete desired state, and a removed recipient must leave no row behind.
     * Only events the tenant's modules include are touched, so posting a
     * tampered event key cannot write rows for a module they do not have.
     */
    public function updateNotifications(Request $request): JsonResponse
    {
        $companyId = Auth::user()->company_id;

        $allowedEvents = collect(NotificationEvent::availableToCompany())
            ->keyBy(fn (NotificationEvent $e) => $e->value);

        $validUserIds = User::internal()
            ->where('company_id', $companyId)
            ->pluck('id')
            ->map('strval')
            ->all();

        $submitted = (array) $request->input('events', []);

        DB::transaction(function () use ($submitted, $allowedEvents, $validUserIds, $companyId) {
            foreach ($submitted as $eventKey => $recipients) {
                if (! $allowedEvents->has($eventKey)) {
                    continue;
                }

                NotificationPreference::where('company_id', $companyId)
                    ->where('event', $eventKey)
                    ->delete();

                foreach ((array) $recipients as $recipient) {
                    $type = $recipient['type'] ?? null;
                    $value = (string) ($recipient['value'] ?? '');

                    // Intersected against what this recipient type may use, so
                    // a hand-crafted payload cannot give a permission row the
                    // mail channel that the UI disables.
                    $channels = array_values(array_intersect(
                        (array) ($recipient['channels'] ?? []),
                        NotificationPreference::allowedChannelsFor((string) $type),
                    ));

                    // The permission row is always written, even with no
                    // channels ticked. "No rows at all" is what tells the
                    // dispatcher this event was never configured and to fall
                    // back to its default — so switching everything off has to
                    // leave a row behind, or it would silently switch back on.
                    if ($channels === [] && $type !== NotificationPreference::TYPE_PERMISSION) {
                        continue;
                    }

                    if ($type === NotificationPreference::TYPE_USER) {
                        // Scoped to this company so a foreign user id cannot be injected.
                        if (! in_array($value, $validUserIds, true)) {
                            continue;
                        }
                    } elseif ($type === NotificationPreference::TYPE_PERMISSION) {
                        if ($value === '') {
                            continue;
                        }
                    } else {
                        continue;
                    }

                    NotificationPreference::create([
                        'company_id' => $companyId,
                        'event' => $eventKey,
                        'recipient_type' => $type,
                        'recipient_value' => $value,
                        'channels' => $channels,
                    ]);
                }
            }
        });

        return response()->json(['success' => true, 'message' => 'Notification preferences saved.']);
    }

    // ════════════════════════════════════════════════════
    //  CLEAR CACHE
    // ════════════════════════════════════════════════════
    public function clearCache(): JsonResponse
    {
        $companyId = Auth::user()->company_id;

        try {
            // Clear company-specific settings cache
            Cache::forget("company_settings_{$companyId}");

            Cache::forget("storefront_nav_categories_{$companyId}");

            // Clear Laravel view + config cache if needed
            // \Artisan::call('view:clear');
            // \Artisan::call('config:clear');

            Log::info('[Settings] Cache cleared', [
                'company_id' => $companyId,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cache purged! Changes are now live.',
            ]);

        } catch (\Throwable $e) {
            Log::error('[Settings] Cache clear failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache. Please try again.',
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ════════════════════════════════════════════════════

    /**
     * Bulk upsert settings rows — one query per key to respect updateOrCreate logic.
     * For very large sets consider chunked inserts, but settings are small.
     */
    /**
     * Write company-level settings.
     *
     * store_id is pinned explicitly on every query. The unique index is now
     * (company_id, store_id, key), so matching on company_id and key alone
     * would happily pick up a row belonging to some store and overwrite it.
     */
    private function upsertSettings(int $companyId, array $data): void
    {
        foreach ($data as $key => $value) {
            // Don't save null/empty for non-boolean fields to avoid wiping existing values
            if ($value === null || $value === '') {
                // Only wipe if key already exists (user intentionally cleared it)
                Setting::where('company_id', $companyId)
                    ->where('store_id', Setting::COMPANY_LEVEL)
                    ->where('key', $key)
                    ->update(['value' => null]);

                continue;
            }

            Setting::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'store_id' => Setting::COMPANY_LEVEL,
                    'key' => $key,
                ],
                ['value' => $value]
            );
        }
    }

    // ════════════════════════════════════════════════════
    //  RESET ALL SETTINGS
    // ════════════════════════════════════════════════════
    public function resetAll(): JsonResponse
    {
        $companyId = Auth::user()->company_id;

        try {
            // Company-level rows only. Without the store_id filter this would
            // also wipe every store's billing and bank configuration, which is
            // far beyond what "reset settings" means to the user.
            Setting::where('company_id', $companyId)
                ->where('store_id', Setting::COMPANY_LEVEL)
                ->delete();

            // Re-seed default settings
            $defaults = [
                'primary_color' => '#008a62',
                'primary_hover_color' => '#007050',
                'storefront_online' => '1',
                'enable_batch_tracking' => '0',
                'currency' => 'INR',
                'fy_start' => 'april',
                'registration_type' => 'regular',
                '_last_saved' => now()->format('d M Y, h:i A'),
            ];

            $this->upsertSettings($companyId, $defaults);

            // Clear cache
            Cache::forget("company_settings_{$companyId}");

            Log::warning('[Settings] All settings reset to defaults', [
                'company_id' => $companyId,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'All settings reset to factory defaults.',
            ]);

        } catch (\Throwable $e) {
            Log::error('[Settings] Reset failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Reset failed. Please try again.',
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  SETTINGS AUDIT TRAIL
    // ════════════════════════════════════════════════════
    public function auditTrail(Request $request): View
    {
        $companyId = Auth::user()->company_id;

        try {
            $logs = Activity::with('causer')
                ->where('subject_type', Setting::class)
                ->whereHasMorph('subject', [Setting::class], function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->latest()
                ->paginate(30);

            return view('admin.audit-logs.audit', compact('logs'));

        } catch (\Throwable $e) {
            Log::error('[Settings] Audit trail load failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return view('admin.audit-logs.audit', ['logs' => new LengthAwarePaginator([], 0, 30)]);
        }
    }
}
