<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\User;
use App\Models\Production\ZoneAssignment;
use App\Services\Admin\ModuleAssignmentService;
use App\Services\Admin\UserService;
use App\Services\Platform\ImageUploadService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeService
{
    public function __construct(
        protected ImageUploadService $imageService,
        protected UserService $userService,
        protected ModuleAssignmentService $moduleAssignmentService,
    ) {}

    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            $data['created_by'] = Auth::id();
            $data['company_id'] = Auth::user()->company_id;
            $storeIds = $this->normalizeStoreIds($data);

            if (isset($data['photo'])) {
                $data['photo'] = $this->imageService->upload($data['photo'], 'employees/photos', [
                    'width' => 400,
                    'height' => 400,
                    'crop' => true,
                ]);
            }

            $data['id_proof'] = $this->uploadDocument($data['id_proof'] ?? null, 'employees/documents');
            $data['address_proof'] = $this->uploadDocument($data['address_proof'] ?? null, 'employees/documents');

            $mode = $data['account_mode'] ?? 'new';

            // Resolve the login first, then build the HR profile from whatever
            // is left. Account fields never reach Employee::create().
            $user = $mode === 'existing'
                ? $this->linkExistingUser($data, $storeIds)
                : $this->createLoginForEmployee($data, $storeIds);

            $profileData = $this->stripAccountFields($data);
            $profileData['user_id'] = $user->id;

            // Re-hire. Attendance, salary slips, work logs and tasks all point
            // at the archived employee id, so a second row would leave that
            // history orphaned and split one person into two.
            $archived = Employee::onlyTrashed()
                ->where('user_id', $user->id)
                ->first();

            if ($archived) {
                return $this->reinstate($archived, $profileData);
            }

            return Employee::create($profileData)->fresh(['user.stores', 'shift']);
        });
    }

    public function update(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data) {
            $storeIds = $this->normalizeStoreIds($data, $employee);

            if (isset($data['photo'])) {
                $this->imageService->delete($employee->photo);
                $data['photo'] = $this->imageService->upload($data['photo'], 'employees/photos', [
                    'width' => 400,
                    'height' => 400,
                    'crop' => true,
                ]);
            }

            if (isset($data['id_proof'])) {
                $this->deleteDocument($employee->id_proof);
                $data['id_proof'] = $this->uploadDocument($data['id_proof'], 'employees/documents');
            }

            if (isset($data['address_proof'])) {
                $this->deleteDocument($employee->address_proof);
                $data['address_proof'] = $this->uploadDocument($data['address_proof'], 'employees/documents');
            }
            // The linked login is never modified here — name, email, password,
            // role and module seats are owned by the Users screen. Editing them
            // in two places would let the two records drift apart.
            $employee->update($this->stripAccountFields($data));

            $employee->user?->stores()->sync($storeIds);

            return $employee->fresh(['user.stores', 'shift']);
        });
    }

    public function delete(Employee $employee): void
    {
        DB::transaction(function () use ($employee) {
            // Capture user_id before soft-delete removes the relationship.
            $userId = $employee->user_id;

            // Operational assignments do not survive archiving. A zone needs a
            // person actually working it, and leaving the row behind would both
            // orphan the listing and silently reinstate the assignment if this
            // employee is ever re-hired.
            if (class_exists(ZoneAssignment::class)) {
                ZoneAssignment::where('employee_id', $employee->id)->delete();
            }

            $employee->delete();

            if (! $userId) {
                return;
            }

            $user = User::find($userId);

            if (! $user) {
                return;
            }

            // Removing an HR record is not the same as removing a login. The
            // account is kept so past attendance, invoices and audit rows keep
            // their author, but every module seat it held is released straight
            // away so the company can re-assign that licence.
            $this->moduleAssignmentService->syncForUser($user, [], Auth::user());

            // The company admin keeps their access — they may hold an employee
            // profile only to track their own attendance, and deactivating it
            // would lock the tenant out of its own panel.
            if (! $user->isCompanyAdmin()) {
                $user->update(['status' => 'inactive']);
            }
        });
    }

    /**
     * Bring an archived employee back, keeping their original record so every
     * historical row stays attached to the same person.
     *
     * The submitted form values win, since HR has just re-entered the current
     * department, salary and joining date. Exit fields are cleared because the
     * person is no longer gone.
     */
    protected function reinstate(Employee $archived, array $profileData): Employee
    {
        // An employee code the form left blank keeps the one they had before.
        if (empty($profileData['employee_code'])) {
            unset($profileData['employee_code']);
        }

        $archived->restore();

        $archived->fill($profileData);
        $archived->status = 'active';
        $archived->date_of_leaving = null;
        $archived->exit_reason = null;
        $archived->save();

        // Their login was deactivated when the profile was archived.
        $archived->user?->update(['status' => 'active']);

        return $archived->fresh(['user.stores', 'shift']);
    }

    /**
     * Reuse a login that already exists — the company admin enabling their own
     * attendance, or a team member hired through the Users screen earlier.
     * No second account is created, and their access is left untouched.
     */
    protected function linkExistingUser(array $data, array $storeIds): User
    {
        $user = User::findOrFail($data['existing_user_id']);

        $user->stores()->syncWithoutDetaching($storeIds);

        return $user;
    }

    /**
     * Create a bare login for a brand new hire: name, email and password only.
     * Role and module seats are granted from the Users screen afterwards, so
     * HR never controls what the company is billed for.
     */
    protected function createLoginForEmployee(array $data, array $storeIds): User
    {
        return $this->userService->createUser($data['company_id'], [
            'name'      => $data['name'],
            'email'     => $this->resolveLoginEmail($data),
            'phone'     => $data['phone'] ?? null,
            'password'  => $data['password'],
            'status'    => 'active',
            'store_ids' => $storeIds,
        ]);
    }

    /**
     * Remove every field that belongs to the login rather than the HR profile.
     */
    protected function stripAccountFields(array $data): array
    {
        unset(
            $data['account_mode'],
            $data['existing_user_id'],
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['password'],
            $data['store_ids'],
        );

        return $data;
    }

    protected function normalizeStoreIds(array &$data, ?Employee $employee = null): array
    {
        $existingStoreIds = $employee?->user?->stores()->pluck('stores.id')->all() ?? [];
        $storeIds = collect($data['store_ids'] ?? $existingStoreIds)
            ->filter()
            ->map(fn ($storeId) => (int) $storeId);

        if (! empty($data['store_id'])) {
            $storeIds->prepend((int) $data['store_id']);
        }

        $storeIds = $storeIds->unique()->values()->all();

        if (empty($data['store_id']) && ! empty($storeIds)) {
            $data['store_id'] = $storeIds[0];
        }

        return $storeIds;
    }

    private function uploadDocument($file, string $path): ?string
    {
        if (! $file) {
            return null;
        }

        // 'local' (storage/app/private). uploadDocument() handles only id_proof
        // and address_proof — Aadhaar and PAN scans. Employee photos go through
        // ImageService and stay public, since they render as avatars in lists.
        return $file->store($path, 'local');
    }

    private function deleteDocument(?string $path): void
    {
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    /**
     * Office staff normally have a real email. Field workers often do not,
     * so fall back to a company-scoped placeholder — they sign in with their
     * employee code instead (see TenantAdminAuthController).
     */
    private function resolveLoginEmail(array $data): string
    {
        if (! empty($data['email'])) {
            return $data['email'];
        }

        $companySlug = Auth::user()->company?->slug ?? 'tenant';

        return 'emp-'.Str::lower(Str::random(8)).'@'.$companySlug.'.local';
    }

}
