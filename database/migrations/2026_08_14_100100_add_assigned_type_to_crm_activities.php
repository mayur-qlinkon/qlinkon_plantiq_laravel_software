<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Handing a lead to someone is a real event on its timeline, but there was
     * no type for it — so reassignments left no trace at all.
     *
     * Raw ALTER because Doctrine cannot modify a MySQL enum in place.
     */
    private const TYPES_AFTER = "'note','call','whatsapp','email','meeting','stage_change','lead_created','converted','task_completed','score_changed','assigned'";

    private const TYPES_BEFORE = "'note','call','whatsapp','email','meeting','stage_change','lead_created','converted','task_completed','score_changed'";

    public function up(): void
    {
        DB::statement('ALTER TABLE crm_activities MODIFY COLUMN type ENUM('.self::TYPES_AFTER.') NOT NULL');
    }

    public function down(): void
    {
        // Rows using the new value would break the narrowed enum.
        DB::table('crm_activities')->where('type', 'assigned')->delete();

        DB::statement('ALTER TABLE crm_activities MODIFY COLUMN type ENUM('.self::TYPES_BEFORE.') NOT NULL');
    }
};