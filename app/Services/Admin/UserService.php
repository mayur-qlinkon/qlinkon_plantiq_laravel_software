<?php

namespace App\Services\Admin;
use App\Models\Store;
use App\Models\User;
use App\Enums\Auth\UserType;
use App\Models\Role;
use App\Models\UserModuleAccess;

use App\Services\Platform\ImageUploadService;
use App\Services\Admin\ModuleAssignmentService;

use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UserService
{
    public function __construct(
        protected ImageUploadService $imageService,
        protected ModuleAssignmentService $moduleAssignmentService
    ) {}

    /**
     * Get a paginated list of internal admin users for the tenant.
     *
     * @param  array  $filters  (e.g., search queries)
     */
    public function getPaginatedUsers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::internal()            
            ->with(['roles', 'stores', 'modules']); // Eager load relationships to prevent N+1

        // Apply Search Filter if provided
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Apply Status Filter if provided
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Store awareness: the Users list is scoped to the currently active
        // store (the one in the store switcher), mirroring HRM Employees.
        // Company admins are exempt — they are often not attached to any
        // store, and scoping them out would hide the tenant owner from
        // their own user list.
        $activeStore = active_store();

        if ($activeStore) {
            $query->where(function ($q) use ($activeStore) {
                $q->whereHas('stores', fn ($sq) => $sq->where('stores.id', $activeStore->id))
                    ->orWhere('user_type', UserType::COMPANY_ADMIN);
            });
        }

        // Manual store filter narrows further, inside the active-store boundary.
        if (! empty($filters['store_id'])) {
            $query->whereHas('stores', function ($q) use ($filters) {
                $q->where('stores.id', $filters['store_id']);
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Create a new user with roles, stores, and image.
     *
     * @throws \Exception
     */
    public function createUser(int $companyId, array $data): User
    {
        try {
            return DB::transaction(function () use ($companyId, $data) {

                // 1. Process Image Upload
                $imagePath = $this->handleImageUpload($data['image'] ?? null);

                // 2. Resolve the assigned role. Identity no longer depends on it —
                // every user created here is an internal team member. Seat cost is
                // decided by module assignment, not by role or user type.
                $assignedRole = ! empty($data['role_id'])
                    ? Role::find($data['role_id'])
                    : null;

                // 3. Create the User Record
                $user = User::create([
                    'company_id' => $companyId,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'password' => Hash::make($data['password']),                    
                    'address' => $data['address'] ?? null,
                    'country' => $data['country'] ?? 'India',
                    'state_id' => $data['state_id'] ?? null,
                    'zip_code' => $data['zip_code'] ?? null,
                    'status' => $data['status'] ?? 'active',
                    'image' => $imagePath,
                    'user_type' => $data['user_type'] ?? UserType::INTERNAL,
                ]);

                // 4. Assign Role
                if ($assignedRole) {
                    $user->roles()->attach($assignedRole->id);
                }

                // 5. Assign Modules (per-user module licensing)
                if (array_key_exists('module_ids', $data) && is_array($data['module_ids'])) {
                    $this->moduleAssignmentService->syncForUser(
                        $user,
                        $data['module_ids'],
                       Auth::user()
                    );
                }

                // 4. Assign to Multiple Stores
                if (! empty($data['store_ids']) && is_array($data['store_ids'])) {
                    $user->stores()->attach($this->ownStoreIds($companyId, $data['store_ids']));
                }

                Log::info('[UserService] User created successfully.', [
                    'user_id' => $user->id,
                    'company_id' => $companyId,
                ]);

                return $user;
            });
        } catch (\Exception $e) {
            Log::error('[UserService] Failed to create user.', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // Rethrow to be caught by the controller
        }
    }

    /**
     * Update an existing user, their roles, stores, and image.
     *
     * @throws \Exception
     */
    /**
     * Keep only the store ids that actually belong to this company.
     *
     * The pivot written here is what auth_store_ids() reads, so a foreign id
     * surviving to this point does not merely corrupt a row — it widens every
     * store filter in the application for that user.
     *
     * Filtering rather than throwing: ids arrive from a multi-select, and a
     * stale option should drop out quietly rather than block the whole save.
     *
     * @param  array<int|string>  $storeIds
     * @return array<int>
     */
    private function ownStoreIds(?int $companyId, array $storeIds): array
    {
        if (! $companyId || empty($storeIds)) {
            return [];
        }

        return Store::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereIn('id', array_map('intval', $storeIds))
            ->pluck('id')
            ->all();
    }

    public function updateUser(User $user, array $data): User
    {
        try {
            return DB::transaction(function () use ($user, $data) {

                // 1. Handle Password Update (Only hash if a new one is provided)
                if (! empty($data['password'])) {
                    $data['password'] = Hash::make($data['password']);
                } else {
                    unset($data['password']); // Remove from array to prevent overwriting with null
                }

                // 2. Handle Image Upload & Cleanup Old Image
                if (array_key_exists('image', $data)) {
                    $data['image'] = $this->handleImageUpload($data['image'], $user->image);
                }

                // 3. Update User Core Data
                // The user type selector is hidden for now, so nothing should be
                // able to change it through this form — a crafted request must
                // not be able to promote a team member to company admin.
                unset($data['user_type']);

                $user->update($data);

                // 4. Sync Role
                if (! empty($data['role_id'])) {
                    $user->roles()->sync([$data['role_id']]);
                }

                // 5. Sync Stores (Replaces existing store assignments with the new array)
                if (isset($data['store_ids']) && ! empty($data['store_ids']) && is_array($data['store_ids'])) {
                    $user->stores()->sync($this->ownStoreIds($user->company_id, $data['store_ids']));
                } elseif (isset($data['store_ids']) && empty($data['store_ids'])) {
                    // If store_ids is explicitly passed as empty, clear all stores
                    $user->stores()->sync([]);
                }

                // 6. Sync Modules (per-user module licensing — replaces existing assignments)
                if (array_key_exists('module_ids', $data)) {
                    $this->moduleAssignmentService->syncForUser(
                        $user,
                        $data['module_ids'] ?? [],
                        Auth::user()
                    );
                }

                Log::info('[UserService] User updated successfully.', ['user_id' => $user->id]);

                return $user;
            });
        } catch (\Exception $e) {
            Log::error('[UserService] Failed to update user.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Soft delete a user.
     *
     * @throws \Exception
     */
    public function deleteUser(User $user): bool
    {
        try {
            // Because your migration has softDeletes(), this safely flags them
            // without breaking foreign key constraints on past orders/audits.
            $user->delete();
            
            // Release any module license seats held by this user — otherwise
            // seatsUsed()/seatsUsedForCompany() keep counting a deleted user's
            // rows and the seat never becomes available for re-assignment.
            UserModuleAccess::where('company_id', $user->company_id)
                ->where('user_id', $user->id)
                ->delete();

            Log::info('[UserService] User soft-deleted.', ['user_id' => $user->id]);

            return true;
        } catch (\Exception $e) {
            Log::error('[UserService] Failed to delete user.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Optimized Avatar Upload using ImageUploadService
     */
    protected function handleImageUpload(?UploadedFile $file, ?string $oldPath = null): ?string
    {
        if (! $file) {
            return $oldPath;
        }

        // We use the service to handle validation, resizing, and cleanup in one go
        return $this->imageService->upload($file, 'users/avatars', [
            'old_file' => $oldPath,   // Automatically deletes the old one on success
            'width' => 400,        // Standard profile size
            'height' => 400,        // Standard profile size
            'crop' => true,       // Force square aspect ratio
            'format' => 'webp',     // Highly optimized for web
            'quality' => 80,          // Good balance of size and clarity
        ]);
    }
}
