<?php

namespace App\Services\Appointment;

use App\Models\Appointment\Appointment;
use App\Models\Appointment\AppointmentService as AppointmentServiceModel;
use App\Models\Appointment\AppointmentSlot;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * AppointmentService
 * ══════════════════════════════════════════════════════════════
 * Single entry point for all appointment business logic.
 *
 * Responsibilities:
 *   1. Book appointments (storefront guest — no Auth required)
 *   2. Admin status transitions (confirm / complete / cancel)
 *   3. Admin CRUD for Services and Slots
 *   4. Slot availability guard (cancelled bookings never block)
 *   5. Filtered list queries for admin index pages
 *
 * Design rules followed:
 *   - Controllers stay thin — all logic lives here.
 *   - DB::transaction() wraps every write operation.
 *   - Log every meaningful action (info) and every failure (error).
 *   - Throw typed exceptions — controllers catch and return user-friendly responses.
 *   - withoutGlobalScope('tenant') only where needed (public storefront calls).
 *   - Never trust companyId from frontend — always passed explicitly or from Auth.
 * ══════════════════════════════════════════════════════════════
 */
class AppointmentService
{
    /** Live (today or later, non-cancelled) bookings allowed per phone number. */
    private const MAX_ACTIVE_BOOKINGS_PER_PHONE = 5;
    // ══════════════════════════════════════════════════════════
    //  SECTION 1 — PUBLIC BOOKING (Storefront, no Auth)
    // ══════════════════════════════════════════════════════════

    /**
     * Book an appointment from the storefront.
     *
     * Guards:
     *   - Service must belong to company and be active.
     *   - Slot must belong to company and be active.
     *   - Slot must not already be booked for the date (cancelled = free).
     *
     * @param  array  $data        Validated fields from the booking form.
     * @param  int    $companyId   Resolved from storefront slug — never from request input.
     *
     * @throws InvalidArgumentException  If slot is already taken or service/slot is inactive.
     */
    public function book(array $data, int $companyId): Appointment
    {
        // Serialises booking per tenant. Two separate races need this: the
        // appointment number comes from an unlocked MAX(), and the slot
        // conflict check reads the transaction's snapshot, which was taken
        // before the slot row was locked and therefore cannot see a booking
        // that committed in between. Bookings are low-volume, so holding a
        // per-company lock costs nothing in practice.
        $lock = Cache::lock("appointment_book_{$companyId}", 15);

        try {
            return $lock->block(10, fn () => $this->createBooking($data, $companyId));
        } catch (LockTimeoutException $e) {
            throw new InvalidArgumentException(
                'The booking system is busy right now. Please try again in a moment.'
            );
        }
    }

    /**
     * The transactional half of book(), always called while the company lock
     * is held.
     */
    private function createBooking(array $data, int $companyId): Appointment
    {
        return DB::transaction(function () use ($data, $companyId) {

            // ── Step 1: Validate service belongs to company and is active ──
            $service = AppointmentServiceModel::withoutGlobalScope('tenant')
                ->where('id', $data['service_id'])
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->first();

            if (! $service) {
                Log::warning('[AppointmentService] Invalid or inactive service on booking attempt', [
                    'service_id' => $data['service_id'],
                    'company_id' => $companyId,
                ]);
                throw new InvalidArgumentException('The selected service is not available.');
            }

            // ── Step 2: Validate slot belongs to company and is active ──
            // lockForUpdate() is kept for row stability, but it does NOT close
            // the double-booking window on its own: the conflict check below is
            // a plain read and answers from the snapshot taken at Step 1, before
            // this lock existed. The real guarantees are the per-company cache
            // lock in book() and the appointments_active_booking_unique index.
            $slot = AppointmentSlot::withoutGlobalScope('tenant')
                ->where('id', $data['slot_id'])
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $slot) {
                Log::warning('[AppointmentService] Invalid or inactive slot on booking attempt', [
                    'slot_id' => $data['slot_id'],
                    'company_id' => $companyId,
                ]);
                throw new InvalidArgumentException('The selected time slot is not available.');
            }

            // ── Step 2.5: Reject if the slot has already fully elapsed today ──
            if ($this->isSlotTimeInPast($slot->end_time, $data['appointment_date'])) {
                Log::info('[AppointmentService] Slot time already passed — rejected', [
                    'slot_id'          => $slot->id,
                    'appointment_date' => $data['appointment_date'],
                    'company_id'       => $companyId,
                ]);
                throw new InvalidArgumentException(
                    'This time slot has already passed for today. Please choose a later slot or a different date.'
                );
            }

            // ── Step 3: THE CORE GUARD — slot conflict check ──
            if (Appointment::isSlotBooked($companyId, $slot->id, $data['appointment_date'])) {
                Log::info('[AppointmentService] Slot already booked — rejected', [
                    'slot_id'          => $slot->id,
                    'appointment_date' => $data['appointment_date'],
                    'company_id'       => $companyId,
                ]);
                throw new InvalidArgumentException(
                    'This slot is already booked for ' . $data['appointment_date'] . '. Please choose a different slot or date.'
                );
            }

            // ── Step 3.5: Cap concurrent bookings per phone number ──
            $this->assertPhoneWithinBookingCap($companyId, $data['customer_phone']);

            // ── Step 4: Build and persist appointment ──
            // The unique index on active_booking_key is the real guarantee;
            // the check above is only a fast path with a friendlier message.
            try {
                $appointment = Appointment::create([
                    'company_id'       => $companyId,
                    'service_id'       => $service->id,
                    'slot_id'          => $slot->id,
                    'appointment_date' => $data['appointment_date'],
                    'customer_name'    => $data['customer_name'],
                    'customer_phone'   => $data['customer_phone'],
                    'customer_email'   => $data['customer_email'] ?? null,
                    'address'          => $data['address'] ?? null,
                    'notes'            => $data['notes'] ?? null,
                    'status'           => Appointment::STATUS_PENDING,
                ]);
            } catch (QueryException $e) {
                if ((int) ($e->errorInfo[1] ?? 0) !== 1062) {
                    throw $e;
                }

                Log::warning('[AppointmentService] Booking rejected by unique index', [
                    'company_id'       => $companyId,
                    'slot_id'          => $slot->id,
                    'appointment_date' => $data['appointment_date'],
                ]);

                throw new InvalidArgumentException(
                    str_contains($e->getMessage(), 'active_booking_unique')
                        ? 'Someone booked this slot moments ago. Please choose another slot or date.'
                        : 'Could not complete the booking. Please try again.'
                );
            }

            Log::info('[AppointmentService] Appointment booked', [
                'appointment_no'   => $appointment->appointment_no,
                'company_id'       => $companyId,
                'service'          => $service->name,
                'slot'             => $slot->display_label,
                'date'             => $appointment->appointment_date,
                'customer'         => $appointment->customer_name,
            ]);

            // company is eager loaded because the customer confirmation email
            // needs the store name; lazy loading it there would cost an extra
            // query on every booking.
            return $appointment->load(['service', 'slot', 'company']);
        });
    }

    // ══════════════════════════════════════════════════════════
    //  SECTION 2 — ADMIN STATUS TRANSITIONS
    //  All transitions guard against invalid from-states.
    //  Auth::id() is used — these are admin-only actions.
    // ══════════════════════════════════════════════════════════

    /**
     * Confirm a pending appointment.
     *
     * @throws InvalidArgumentException
     */
    public function confirm(Appointment $appointment, ?string $adminNotes = null): Appointment
    {
        $this->guardTransition($appointment, [Appointment::STATUS_PENDING], 'confirm');

        return $this->transition($appointment, Appointment::STATUS_CONFIRMED, $adminNotes);
    }

    /**
     * Mark an appointment as completed.
     *
     * @throws InvalidArgumentException
     */
    public function complete(Appointment $appointment, ?string $adminNotes = null): Appointment
    {
        $this->guardTransition($appointment, [Appointment::STATUS_CONFIRMED], 'complete');

        return $this->transition($appointment, Appointment::STATUS_COMPLETED, $adminNotes);
    }

    /**
     * Cancel an appointment.
     * Both pending and confirmed appointments can be cancelled.
     *
     * @throws InvalidArgumentException
     */
    public function cancel(Appointment $appointment, ?string $adminNotes = null): Appointment
    {
        $this->guardTransition(
            $appointment,
            [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED],
            'cancel'
        );

        return $this->transition($appointment, Appointment::STATUS_CANCELLED, $adminNotes);
    }

    // ══════════════════════════════════════════════════════════
    //  SECTION 3 — ADMIN: APPOINTMENT SERVICE CRUD
    // ══════════════════════════════════════════════════════════

    /**
     * Create a new appointment service for the authenticated company.
     */
    public function createService(array $data): AppointmentServiceModel
    {
        return DB::transaction(function () use ($data) {
            $service = AppointmentServiceModel::create([
                'company_id'       => Auth::user()->company_id,
                'name'             => $data['name'],
                'description'      => $data['description'] ?? null,
                'price'            => $data['price'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'is_active'        => $data['is_active'] ?? true,
                'sort_order'       => $data['sort_order'] ?? 0,
            ]);

            Log::info('[AppointmentService] Service created', [
                'service_id' => $service->id,
                'name'       => $service->name,
                'company_id' => $service->company_id,
                'by'         => Auth::id(),
            ]);

            return $service;
        });
    }

    /**
     * Update an existing appointment service.
     */
    public function updateService(AppointmentServiceModel $service, array $data): AppointmentServiceModel
    {
        return DB::transaction(function () use ($service, $data) {
            $service->update([
                'name'             => $data['name'],
                'description'      => $data['description'] ?? null,
                'price'            => $data['price'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'is_active'        => $data['is_active'] ?? $service->is_active,
                'sort_order'       => $data['sort_order'] ?? $service->sort_order,
            ]);

            Log::info('[AppointmentService] Service updated', [
                'service_id' => $service->id,
                'name'       => $service->name,
                'by'         => Auth::id(),
            ]);

            return $service->fresh();
        });
    }

    /**
     * Delete an appointment service.
     * Guard: Cannot delete if active (non-cancelled) appointments exist.
     *
     * @throws InvalidArgumentException
     */
    public function deleteService(AppointmentServiceModel $service): void
    {
        $base = fn () => Appointment::withoutGlobalScope('tenant')
            ->where('company_id', $service->company_id)
            ->where('service_id', $service->id);

        $activeCount = $base()
            ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
            ->count();

        if ($activeCount > 0) {
            throw new InvalidArgumentException(
                "Cannot delete \"{$service->name}\" — it has {$activeCount} active appointment(s). Cancel them first."
            );
        }

        // appointments.service_id is restrictOnDelete, so completed and
        // cancelled rows block the delete just as hard as active ones. Without
        // this check the guard passed and MySQL rejected the DELETE, which the
        // controller reported as a generic "please try again".
        $historyCount = $base()->count();

        if ($historyCount > 0) {
            throw new InvalidArgumentException(
                "Cannot delete \"{$service->name}\" — {$historyCount} past appointment(s) reference it. " .
                'Mark it inactive instead; it will disappear from the storefront and keep its history intact.'
            );
        }

        DB::transaction(function () use ($service) {
            Log::info('[AppointmentService] Service deleted', [
                'service_id' => $service->id,
                'name'       => $service->name,
                'by'         => Auth::id(),
            ]);
            $service->delete();
        });
    }

    // ══════════════════════════════════════════════════════════
    //  SECTION 4 — ADMIN: SLOT CRUD
    // ══════════════════════════════════════════════════════════

    /**
     * Create a new appointment slot.
     */
    public function createSlot(array $data): AppointmentSlot
    {
        return DB::transaction(function () use ($data) {
            $this->assertNoSlotOverlap(
                Auth::user()->company_id,
                $data['start_time'],
                $data['end_time']
            );

            $slot = AppointmentSlot::create([
                'company_id' => Auth::user()->company_id,
                'slot_name'  => $data['slot_name'],
                'start_time' => $data['start_time'],
                'end_time'   => $data['end_time'],
                'is_active'  => $data['is_active'] ?? true,
            ]);

            Log::info('[AppointmentService] Slot created', [
                'slot_id'    => $slot->id,
                'slot_name'  => $slot->slot_name,
                'company_id' => $slot->company_id,
                'by'         => Auth::id(),
            ]);

            return $slot;
        });
    }

    /**
     * Update an existing appointment slot.
     */
    public function updateSlot(AppointmentSlot $slot, array $data): AppointmentSlot
    {
        return DB::transaction(function () use ($slot, $data) {
            $this->assertNoSlotOverlap(
                $slot->company_id,
                $data['start_time'],
                $data['end_time'],
                $slot->id
            );

            $slot->update([
                'slot_name'  => $data['slot_name'],
                'start_time' => $data['start_time'],
                'end_time'   => $data['end_time'],
                'is_active'  => $data['is_active'] ?? $slot->is_active,
            ]);

            Log::info('[AppointmentService] Slot updated', [
                'slot_id'   => $slot->id,
                'slot_name' => $slot->slot_name,
                'by'        => Auth::id(),
            ]);

            return $slot->fresh();
        });
    }

    /**
     * Delete a slot.
     * Guard: Cannot delete if active appointments exist on this slot.
     *
     * @throws InvalidArgumentException
     */
    public function deleteSlot(AppointmentSlot $slot): void
    {
        $base = fn () => Appointment::withoutGlobalScope('tenant')
            ->where('company_id', $slot->company_id)
            ->where('slot_id', $slot->id);

        $activeCount = $base()
            ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
            ->count();

        if ($activeCount > 0) {
            throw new InvalidArgumentException(
                "Cannot delete \"{$slot->slot_name}\" — it has {$activeCount} active appointment(s) using this slot."
            );
        }

        // Same restrictOnDelete story as services: any historical row blocks
        // the DELETE at the database level.
        $historyCount = $base()->count();

        if ($historyCount > 0) {
            throw new InvalidArgumentException(
                "Cannot delete \"{$slot->slot_name}\" — {$historyCount} past appointment(s) use this slot. " .
                'Mark it inactive instead; it will stop appearing on the storefront.'
            );
        }

        DB::transaction(function () use ($slot) {
            Log::info('[AppointmentService] Slot deleted', [
                'slot_id'   => $slot->id,
                'slot_name' => $slot->slot_name,
                'by'        => Auth::id(),
            ]);
            $slot->delete();
        });
    }

    // ══════════════════════════════════════════════════════════
    //  SECTION 5 — ADMIN: FILTERED LIST QUERIES
    //  Used by admin index controllers.
    //  Tenantable trait handles company_id scoping automatically.
    // ══════════════════════════════════════════════════════════

    /**
     * Paginated, filterable list of appointments for the admin panel.
     *
     * Supported filters:
     *   status          — pending | confirmed | completed | cancelled
     *   service_id      — integer
     *   appointment_date — Y-m-d string
     *   search          — customer_name | customer_phone | appointment_no
     */
    public function getAppointmentList(array $filters = []): LengthAwarePaginator
    {
        $query = Appointment::with(['service', 'slot'])
            ->orderBy('appointment_date', 'desc')
            ->orderBy('created_at', 'desc');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['service_id'])) {
            $query->where('service_id', $filters['service_id']);
        }

        if (! empty($filters['appointment_date'])) {
            $query->where('appointment_date', $filters['appointment_date']);
        }

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('customer_name', 'like', "%{$term}%")
                  ->orWhere('customer_phone', 'like', "%{$term}%")
                  ->orWhere('appointment_no', 'like', "%{$term}%");
            });
        }

        return $query->paginate($this->resolvePerPage($filters))->withQueryString();
    }

    /**
     * Paginated list of appointment services for the admin panel.
     */
    public function getServiceList(array $filters = []): LengthAwarePaginator
    {
        $query = AppointmentServiceModel::ordered();

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->paginate($this->resolvePerPage($filters))->withQueryString();
    }

    /**
     * Paginated list of appointment slots for the admin panel.
     */
    public function getSlotList(array $filters = []): LengthAwarePaginator
    {
        $query = AppointmentSlot::ordered();

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->paginate($this->resolvePerPage($filters))->withQueryString();
    }

    /**
     * Clamp a user-supplied per_page value into a safe range.
     *
     * Guards the admin list URLs against hand-edited query strings:
     *   ?per_page=abc  → TypeError inside the query builder
     *   ?per_page=-1   → SQL syntax error on the OFFSET clause
     *   ?per_page=0    → pagination silently disabled, every row returned
     * Anything invalid falls back to the default instead of erroring.
     */
    private function resolvePerPage(array $filters, int $default = 20): int
    {
        $perPage = $filters['per_page'] ?? $default;

        if (! is_numeric($perPage)) {
            return $default;
        }

        return max(1, min(100, (int) $perPage));
    }

    // ══════════════════════════════════════════════════════════
    //  SECTION 6 — STOREFRONT DATA HELPERS
    //  Used by StorefrontController — no Auth context.
    //  withoutGlobalScope('tenant') required.
    // ══════════════════════════════════════════════════════════

    /**
     * Get all active services for a company — for storefront booking form dropdown.
     * Returns lightweight collection (id, name, description, price, duration_minutes).
     */
    public function getActiveServicesForStorefront(int $companyId): \Illuminate\Database\Eloquent\Collection
    {
        return AppointmentServiceModel::withoutGlobalScope('tenant')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->ordered()
            ->get(['id', 'name', 'description', 'price', 'duration_minutes']);
    }

    /**
     * Get all active slots for a company — for storefront booking form dropdown.
     * Returns lightweight collection (id, slot_name, start_time, end_time).
     */
    public function getActiveSlotsForStorefront(int $companyId): \Illuminate\Database\Eloquent\Collection
    {
        return AppointmentSlot::withoutGlobalScope('tenant')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->ordered()
            ->get(['id', 'slot_name', 'start_time', 'end_time']);
    }

    /**
     * AJAX: Check if a specific slot is available for a given date.
     * Called by storefront JS before showing "Confirm Booking" button.
     *
     * Returns true = slot is FREE, false = slot is TAKEN.
     */
    public function isSlotAvailable(int $companyId, int $slotId, string $date): bool
    {
        // company_id and is_active were not checked, so another tenant's slot
        // id reported "available" here and was then rejected by book() — the
        // two endpoints disagreed, and the gap doubled as an id oracle.
        $slot = AppointmentSlot::withoutGlobalScope('tenant')
            ->where('id', $slotId)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (! $slot || $this->isSlotTimeInPast($slot->end_time, $date)) {
            return false;
        }

        return ! Appointment::isSlotBooked($companyId, $slotId, $date);
    }

    // ══════════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════

        /**
     * Reject a slot whose time range overlaps an existing active slot.
     *
     * Two overlapping slots are booked independently, so a customer in
     * 09:00–12:00 and another in 10:00–11:00 both hold a valid appointment for
     * the same physical hour. Ranges touching end-to-end (10:00 end, 10:00
     * start) are fine and stay allowed.
     */
    private function assertNoSlotOverlap(int $companyId, string $start, string $end, ?int $ignoreId = null): void
    {
        $clash = AppointmentSlot::withoutGlobalScope('tenant')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->when($ignoreId, fn ($q) => $q->where('id', '<>', $ignoreId))
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->first();

        if ($clash) {
            throw new InvalidArgumentException(
                "This time range overlaps \"{$clash->display_label}\" ({$clash->time_range}). " .
                'Overlapping slots can be booked by two different customers for the same hour.'
            );
        }
    }

    /**
     * Cap how many live bookings one phone number may hold at a tenant.
     * Without it a single caller can take every slot on the calendar.
     */
    private function assertPhoneWithinBookingCap(int $companyId, string $phone): void
    {
        $active = Appointment::withoutGlobalScope('tenant')
            ->where('company_id', $companyId)
            ->where('customer_phone', $phone)
            ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
            ->where('appointment_date', '>=', now()->toDateString())
            ->count();

        if ($active >= self::MAX_ACTIVE_BOOKINGS_PER_PHONE) {
            throw new InvalidArgumentException(
                'This phone number already has ' . self::MAX_ACTIVE_BOOKINGS_PER_PHONE .
                ' upcoming appointments. Please contact us directly to book another.'
            );
        }
    }

    /**
     * Every slot id that cannot be booked on a date: already taken, or already
     * elapsed today. One query pair replaces the per-slot round trip the
     * storefront used to make.
     */
    public function getUnavailableSlotIds(int $companyId, string $date): array
    {
        $booked = Appointment::withoutGlobalScope('tenant')
            ->where('company_id', $companyId)
            ->where('appointment_date', $date)
            ->where('status', '<>', Appointment::STATUS_CANCELLED)
            ->pluck('slot_id')
            ->all();

        $elapsed = AppointmentSlot::withoutGlobalScope('tenant')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->get(['id', 'end_time'])
            ->filter(fn ($slot) => $this->isSlotTimeInPast($slot->end_time, $date))
            ->pluck('id')
            ->all();

        return array_values(array_unique(array_merge($booked, $elapsed)));
    }
    /**
     * True when $date is today AND the slot's END time (server time) has already
     * passed — i.e. the slot has fully elapsed, not just started. A slot like
     * "Full Day 09:00–18:00" stays valid all day until 18:00, even though it
     * "started" at 09:00. Future dates are never "in the past" regardless of time.
     */
    private function isSlotTimeInPast(string $endTime, string $date): bool
    {
        return $date === now()->toDateString() && now()->format('H:i:s') >= $endTime;
    }

    /**
     * Guard a status transition — throws if current status is not in allowed list.
     *
     * @param  array   $allowedFromStatuses   e.g. ['pending', 'confirmed']
     * @param  string  $action                Human label for the error message e.g. 'confirm'
     *
     * @throws InvalidArgumentException
     */
    private function guardTransition(Appointment $appointment, array $allowedFromStatuses, string $action): void
    {
        if (! in_array($appointment->status, $allowedFromStatuses, true)) {
            throw new InvalidArgumentException(
                "Cannot {$action} appointment #{$appointment->appointment_no}. " .
                "Current status is \"{$appointment->status_label}\". " .
                "Expected: " . implode(' or ', array_map(
                    fn ($s) => Appointment::STATUS_LABELS[$s] ?? $s,
                    $allowedFromStatuses
                )) . "."
            );
        }
    }

    /**
     * Perform the actual status update inside a transaction.
     * Updates status and optionally logs admin notes.
     * Logs the transition for audit trail.
     */
    private function transition(Appointment $appointment, string $newStatus, ?string $adminNotes): Appointment
    {
        return DB::transaction(function () use ($appointment, $newStatus, $adminNotes) {
            $oldStatus = $appointment->status;

            $updateData = ['status' => $newStatus];

            if (! is_null($adminNotes)) {
                $updateData['admin_notes'] = $adminNotes;
            }

            $appointment->update($updateData);

            Log::info('[AppointmentService] Status transitioned', [
                'appointment_no' => $appointment->appointment_no,
                'from'           => $oldStatus,
                'to'             => $newStatus,
                'by'             => Auth::id(),
                'admin_notes'    => $adminNotes,
            ]);

            // company comes along for the customer status emails — same reason
            // as in book().
            return $appointment->fresh(['service', 'slot', 'company']);
        });
    }
}