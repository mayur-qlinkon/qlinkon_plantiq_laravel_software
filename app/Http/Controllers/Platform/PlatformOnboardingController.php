<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\CompanyOnboardRequest;
use App\Http\Requests\Platform\CompanyOnboardUpdateRequest;

use App\Models\Company;
use App\Models\CompanyModuleLicense;
use App\Models\Module;
use App\Models\State;

use App\Services\Platform\CompanyOnboardingService;

use App\Enums\Auth\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class PlatformOnboardingController extends Controller
{
    public function __construct(
        private CompanyOnboardingService $service
    ) {}

    // ──────────────────────────────────────────────────────────────
    // INDEX — merged listing: company + plan + subscription
    // ──────────────────────────────────────────────────────────────

    public function index()
    {
        $companies = Company::withCount(['users', 'stores'])
            ->with([
                'users' => fn ($q) => $q
                    ->where('user_type', UserType::COMPANY_ADMIN)
                    ->limit(1),
                'subscription.plan.modules',
                'state',
            ])
            ->latest()
            ->get();

        return view('platform.onboard.index', compact('companies'));
    }

    // ──────────────────────────────────────────────────────────────
    // CREATE — show unified form
    // ──────────────────────────────────────────────────────────────

    public function create()
    {
        $states  = State::where('is_active', true)->orderBy('name')->get();
        $modules = Module::where('is_active', true)->orderBy('sort_order')->get();

        [$moduleDependencies, $moduleNames] = $this->moduleDependencyMap($modules);

        return view('platform.onboard.create', compact(
            'states',
            'modules',
            'moduleDependencies',
            'moduleNames'
        ));
    }

    /**
     * Dependency graph in a shape the browser can act on.
     *
     * depends_on stores slugs, but the form works in IDs, so the translation
     * happens once here rather than in every Alpine comparison. Slugs that no
     * longer resolve to an active module are dropped — a stale dependency must
     * not be able to block licensing.
     *
     * @return array{0: array<int, array<int>>, 1: array<int, string>}
     */
    private function moduleDependencyMap($modules): array
    {
        $dependencies = $modules->mapWithKeys(function (Module $module) use ($modules) {
            $requiredIds = collect($module->depends_on ?? [])
                ->map(fn (string $slug) => $modules->firstWhere('slug', $slug)?->id)
                ->filter()
                ->values()
                ->all();

            return [$module->id => $requiredIds];
        })->all();

        return [$dependencies, $modules->pluck('name', 'id')->all()];
    }

    // ──────────────────────────────────────────────────────────────
    // STORE — save company + plan + subscription
    // ──────────────────────────────────────────────────────────────

    public function store(CompanyOnboardRequest $request)
    {
        try {
            $data = $request->validated();
            $data['is_active']     = $request->boolean('is_active', true);
            $data['sub_is_active'] = $request->boolean('sub_is_active', true);

            $company = $this->service->onboard($data);

            $redirect = redirect()
                ->route('platform.tenants.show', $company)
                ->with('success', 'Company onboarded successfully!');

            // Shown once. The plaintext password is stored nowhere, so if this
            // screen is missed the password must be reset from user management.
            if ($this->service->defaultUserCredentials) {
                $redirect->with('default_user', $this->service->defaultUserCredentials);
            }

            return $redirect;

        } catch (RuntimeException $e) {
            // Business rules — module dependencies and the like. These messages
            // are written for the person reading them, so they pass through.
            return back()->withInput()->with('error', $e->getMessage());

        } catch (\Throwable $e) {
            // Anything else is infrastructure. Its message can carry table and
            // column names, so it goes to the log and the user gets a generic
            // line instead.
            Log::error('[Onboarding] Company create failed', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);

            return back()->withInput()->with(
                'error',
                'Onboarding could not be completed. The error has been logged — please check the logs or try again.'
            );
        }
    }

    // ──────────────────────────────────────────────────────────────
    // SHOW — full detail: company + owner + plan + subscription + stores
    // ──────────────────────────────────────────────────────────────

    public function show(Company $tenant)
    {
        $tenant->load([
            'users.roles',
            'stores',
            'subscription.plan.modules',
            'state',
        ]);

        $owner = $tenant->users->first(fn ($u) => $u->isCompanyAdmin());

        $licenses = CompanyModuleLicense::where('company_id', $tenant->id)
            ->get()
            ->keyBy('module_id');

        return view('platform.onboard.show', compact('tenant', 'owner', 'licenses'));
    }

    // ──────────────────────────────────────────────────────────────
    // EDIT — show unified edit form
    // ──────────────────────────────────────────────────────────────

    public function edit(Company $tenant)
    {
        $tenant->load(['subscription.plan.modules', 'state']);

        $states  = State::where('is_active', true)->orderBy('name')->get();
        $modules = Module::where('is_active', true)->orderBy('sort_order')->get();

        $licenses = CompanyModuleLicense::where('company_id', $tenant->id)->get();

        $licensedModuleIds = $licenses->where('is_active', true)->pluck('module_id')->all();

        // Existing seat_limit per module, keyed by module_id
        // (null seats = unlimited, omitted so the placeholder shows)
        $moduleSeats = $licenses->whereNotNull('seat_limit')->pluck('seat_limit', 'module_id')->all();

        [$moduleDependencies, $moduleNames] = $this->moduleDependencyMap($modules);

        return view('platform.onboard.edit', compact(
            'tenant',
            'states',
            'modules',
            'licensedModuleIds',
            'moduleSeats',
            'moduleDependencies',
            'moduleNames'
        ));
    }

    // ──────────────────────────────────────────────────────────────
    // UPDATE — update company + plan + subscription
    // ──────────────────────────────────────────────────────────────

    public function update(CompanyOnboardUpdateRequest $request, Company $tenant)
    {
        try {
            $data = $request->validated();
            $data['is_active']     = $request->boolean('is_active', true);
            $data['sub_is_active'] = $request->boolean('sub_is_active', true);

            $this->service->update($tenant, $data);

            return redirect()
                ->route('platform.tenants.show', $tenant)
                ->with('success', 'Company updated successfully!');

        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());

        } catch (\Throwable $e) {
            Log::error('[Onboarding] Company update failed', [
                'company_id' => $tenant->id,
                'error'      => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
            ]);

            return back()->withInput()->with(
                'error',
                'Update could not be completed. The error has been logged — please check the logs or try again.'
            );
        }
    }

    // ──────────────────────────────────────────────────────────────
    // DESTROY — terminate company
    // ──────────────────────────────────────────────────────────────

    public function destroy(Company $tenant)
    {
        try {
            // The super admin guard lives in the service, not here. It used to
            // sit in both places with different conditions, so whether a
            // company could be deleted depended on which one you hit.
            $this->service->delete($tenant);

            return request()->wantsJson()
                ? response()->json(['success' => true, 'message' => 'Company terminated.'])
                : redirect()->route('platform.tenants.index')
                    ->with('success', 'Company terminated successfully!');

        } catch (RuntimeException $e) {
            // Business rule — written to be read by a person.
            return request()->wantsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 422)
                : back()->with('error', $e->getMessage());

        } catch (\Throwable $e) {
            Log::error('[Onboarding] Company delete failed', [
                'company_id' => $tenant->id,
                'error'      => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
            ]);

            $msg = 'Termination could not be completed. The error has been logged — please check the logs or try again.';

            return request()->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 500)
                : back()->with('error', $msg);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // SLUG CHECK — AJAX
    // ──────────────────────────────────────────────────────────────

    public function slugCheck(Request $request, ?Company $tenant = null)
    {
        $slug = Str::slug($request->query('slug', ''));

        if (empty($slug)) {
            return response()->json(['available' => false, 'message' => 'Slug cannot be empty.']);
        }

        $query = Company::where('slug', $slug);

        if ($tenant && $tenant->exists) {
            $query->where('id', '!=', $tenant->id);
        }

        $taken = $query->exists();

        return response()->json([
            'available' => ! $taken,
            'slug'      => $slug,
            'message'   => $taken ? 'This slug is already taken.' : 'Slug is available.',
        ]);
    }
}


