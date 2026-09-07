<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The notification bell polls with:
     *
     *   SELECT ... FROM notifications
     *   WHERE notifiable_type = ? AND notifiable_id = ? AND read_at IS NULL
     *   ORDER BY created_at DESC
     *   LIMIT 10
     *
     * The stock Laravel schema only indexes (notifiable_type, notifiable_id)
     * via $table->morphs(). That satisfies the two equality predicates but
     * leaves read_at as a post-scan filter and created_at as a filesort.
     *
     * This composite index covers all four columns in predicate order, so
     * MySQL can seek straight to the user's unread rows and walk them in
     * created_at order — no filesort, no temporary table, and the LIMIT stops
     * the scan early.
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(
                ['notifiable_type', 'notifiable_id', 'read_at', 'created_at'],
                'notifications_polling_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_polling_index');
        });
    }
};