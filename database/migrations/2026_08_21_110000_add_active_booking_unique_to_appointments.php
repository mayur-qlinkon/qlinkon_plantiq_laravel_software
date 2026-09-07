<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The original migration commented that one booking per slot/date/company
     * was UNIQUE, but only created a plain index. The application check alone
     * cannot hold the line: it reads through the transaction's snapshot, so
     * two concurrent bookings both see a free slot.
     *
     * The key lives in a plain column that Appointment's saving hook keeps in
     * step, and is NULL for cancelled rows. A unique index ignores NULLs, so a
     * cancelled slot is free to rebook while any non-cancelled row holds it.
     *
     * A STORED generated column was the first attempt, but MySQL rebuilds the
     * whole table for one (ALGORITHM=COPY) and re-creates this table's foreign
     * keys while doing it, which fails with a misleading errno 1215. Each step
     * below is its own statement for the same reason.
     */
    public function up(): void
    {
        // Enforcing the rule on data that already breaks it would fail halfway.
        $duplicates = DB::table('appointments')
            ->select('company_id', 'appointment_date', 'slot_id')
            ->where('status', '<>', 'cancelled')
            ->groupBy('company_id', 'appointment_date', 'slot_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot add the booking uniqueness index: ' . $duplicates->count() .
                ' slot/date combination(s) already hold more than one active appointment. ' .
                'Cancel the surplus bookings, then run this migration again.'
            );
        }

        Schema::table('appointments', function (Blueprint $table) {
            $table->string('active_booking_key', 80)->nullable()->after('status');
        });

        // Backfill before the index exists, so existing rows are covered.
        DB::statement("
            UPDATE `appointments`
            SET `active_booking_key` = CONCAT(`company_id`, '-', `appointment_date`, '-', `slot_id`)
            WHERE `status` <> 'cancelled'
        ");

        Schema::table('appointments', function (Blueprint $table) {
            $table->unique('active_booking_key', 'appointments_active_booking_unique');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique('appointments_active_booking_unique');
            $table->dropColumn('active_booking_key');
        });
    }
};