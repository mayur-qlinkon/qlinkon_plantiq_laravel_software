<?php

use App\Models\Hrm\Attendance;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The weekly off was a constant in MarkAbsentEmployeesCommand, fixed to
        // Sunday. Payroll now derives its divisor from the roster, so that
        // constant decides everyone's pay — and a six-day company would have
        // every salary overstated by roughly a day a week.
        Schema::table('shifts', function (Blueprint $table) {
            // Carbon dayOfWeek numbers: 0 = Sunday ... 6 = Saturday.
            $table->json('weekly_off_days')->nullable()->after('is_night_shift');

            // Which Saturdays of the month are off, e.g. [2, 4] for the second
            // and fourth. Kept separate from weekly_off_days because it is a
            // week-of-month rule, not a day-of-week one.
            $table->json('alternate_saturday_offs')->nullable()->after('weekly_off_days');
        });

        // Existing shifts keep the behaviour they had before this column
        // existed, so no company's payroll changes on deploy.
        DB::table('shifts')->update([
            'weekly_off_days' => json_encode(Attendance::WEEKLY_OFF_DAYS),
        ]);
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['weekly_off_days', 'alternate_saturday_offs']);
        });
    }
};