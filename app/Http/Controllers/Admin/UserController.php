<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Role;
use App\Models\State;
use App\Models\Store;
use App\Models\User;

use App\Services\Admin\ModuleAssignmentService;
use App\Services\Admin\UserService;
use App\Enums\Auth\UserType;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected ModuleAssignmentService $moduleAssignmentService
    ) {}

    /**
     * Display a listing of the users.
     */
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;

        // Added 'store_id' to pass both the search text and the store dropdown to your service
        $filters = $request->only(['search', 'status', 'store_id']);

        $users = $this->userService->getPaginatedUsers($filters, 25);
        $roles = Role::where('company_id', $companyId)->orWhereNull('company_id')->get();
        $stores = auth_stores()->get();

        $licenses = $this->moduleAssignmentService->licensesForCompany($companyId);
        $seatsUsed = $this->moduleAssignmentService->seatsUsedForCompany($companyId);

        return view('admin.users.index', compact('users', 'filters', 'roles', 'stores', 'licenses', 'seatsUsed'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        // if (! check_plan_limit('users')) {
        //     return redirect()->route('admin.users.index')
        //         ->with('error', 'You have reached your subscription limit for users. Please upgrade your plan to add more staff.');
        // }

        $companyId = Auth::user()->company_id;

        $roles = Role::where(function ($query) use ($companyId) {
            $query->where('company_id', $companyId)
                ->orWhereNull('company_id');
        })
            ->where('slug', '!=', 'owner')
            ->get();
        $stores = auth_stores()->get();
        $states = State::orderBy('name')->get();

        $assignableModules = $this->moduleAssignmentService->assignableModulesForCompany($companyId);
        $licenses = $this->moduleAssignmentService->licensesForCompany($companyId);
        $seatsUsed = $this->moduleAssignmentService->seatsUsedForCompany($companyId);

        return view('admin.users.create', compact('roles', 'stores', 'states', 'assignableModules', 'licenses', 'seatsUsed'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request)
    {
        // Seat cost is decided by module assignment, not by role.
        if (! check_plan_limit('users')) {
            return back()->withInput()
                ->with('error', 'User limit reached for your current plan. Please upgrade.');
        }

        try {
            $companyId = Auth::user()->company_id;

            $this->userService->createUser($companyId, $request->validated());

            return redirect()->route('admin.users.index')
                ->with('success', 'User created successfully.');

        } catch (\RuntimeException $e) {
            // Seat-limit / license validation error — message is safe to show as-is.
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            // Service already logged the detailed error, we just notify the user gracefully
            return back()->withInput()->with('error', 'Failed to create user. Please try again.');
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $companyId = Auth::user()->company_id;
        // Security check: Ensure the user belongs to the current tenant
        if ($user->company_id !== $companyId) {
            abort(403, 'Unauthorized access.');
        }

        if ($user->client()->exists()) {
            abort(404);
        }
        
        $assignableModules = $this->moduleAssignmentService->assignableModulesForCompany($companyId);
        $licenses = $this->moduleAssignmentService->licensesForCompany($companyId);
        $seatsUsed = $this->moduleAssignmentService->seatsUsedForCompany($companyId);

        $user->load(['roles', 'stores', 'employee']);

        return view('admin.users.show', compact('user','assignableModules', 'licenses', 'seatsUsed'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        if ($user->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access.');
        }

        if ($user->client()->exists()) {
            abort(404);
        }

        $companyId = Auth::user()->company_id;

        // Load existing relationships so the form can auto-select them
        $user->load(['roles', 'stores', 'modules']);

        $roles = Role::where(function ($query) use ($companyId) {
            $query->where('company_id', $companyId)
                ->orWhereNull('company_id');
        })
            ->where('slug', '!=', 'owner')
            ->get();
        $stores = auth_stores()->get();
        $states = State::orderBy('name')->get();

        $assignableModules = $this->moduleAssignmentService->assignableModulesForCompany($companyId);
        $licenses = $this->moduleAssignmentService->licensesForCompany($companyId);
        $seatsUsed = $this->moduleAssignmentService->seatsUsedForCompany($companyId);

        return view('admin.users.edit', compact('user', 'roles', 'stores', 'states', 'assignableModules', 'licenses', 'seatsUsed'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        if ($user->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access.');
        }

        if ($user->client()->exists()) {
            abort(404);
        }

        // Demoting the last company admin would leave the tenant with nobody
        // who can manage users, roles or billing — and no way back in.
        //
        // The user_type selector is currently hidden from the edit form, so the
        // field is usually absent rather than null. An absent field means the
        // type is unchanged, which is not a demotion — reading the key directly
        // threw here instead.
        $validated = $request->validated();

        $isDemotingAdmin = $user->isCompanyAdmin()
            && array_key_exists('user_type', $validated)
            && $validated['user_type'] !== null
            && $validated['user_type'] !== UserType::COMPANY_ADMIN->value;

        if ($isDemotingAdmin) {
            $remainingAdmins = User::where('company_id', $user->company_id)
                ->where('user_type', UserType::COMPANY_ADMIN)
                ->where('id', '!=', $user->id)
                ->count();

            if ($remainingAdmins === 0) {
                return back()->withInput()->with(
                    'error',
                    'This is the only company admin. Promote another user first.'
                );
            }
        }

        try {
            $data = $validated;

            // 🌟 THE FIX: If no boxes are checked, HTML sends nothing. We force an empty array so stores are cleared!
            if (! $request->has('store_ids')) {
                $data['store_ids'] = [];
            }

            // Same issue for module license checkboxes — if none are checked,
            // HTML omits the field entirely, so module_ids never reaches
            // updateUser() and stale UserModuleAccess rows never get removed.
            if (! $request->has('module_ids')) {
                $data['module_ids'] = [];
            }

            $this->userService->updateUser($user, $data);

            return redirect()->route('admin.users.index')
                ->with('success', 'User updated successfully.');

        } catch (\RuntimeException $e) {
            // Seat-limit / license validation error — message is safe to show as-is.
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update user: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified user from storage.
     * Responds with JSON because deletes are usually triggered via AJAX/SweetAlert.
     */
    public function destroy(User $user)
    {
        if ($user->company_id !== Auth::user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($user->client()->exists()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Prevent users from deleting themselves
        if ($user->id === Auth::id()) {
            return response()->json(['success' => false, 'message' => 'You cannot delete your own account.'], 400);
        }

        try {
            $this->userService->deleteUser($user);

            return redirect()->route('admin.users.index')
                ->with('success', 'User deleted successfully.');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user.',
            ], 500);
        }
    }
}
