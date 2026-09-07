<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tenant decisions about who is notified for each broadcast event.
 *
 * A table rather than a settings JSON blob so that deleting a user takes
 * their rows with it — a JSON list would keep a dangling id forever — and so
 * a future "my notifications" screen can query by user_id directly.
 *
 * Absence of rows is meaningful: an event with no rows falls back to the
 * default permission declared on NotificationEvent, so existing tenants keep
 * working with nothing seeded.
 */
return new class extends Migration
{
    public function up(): void
    {        
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // NotificationEvent enum value, e.g. 'order.placed'.
            $table->string('event', 60);

            // 'permission' → recipient_value holds a permission slug and
            //                resolves to whoever holds it at send time.
            // 'user'       → recipient_value holds a users.id.
            $table->string('recipient_type', 20);
            $table->string('recipient_value', 100);

            // ['database', 'mail'] today; a new channel adds a string here
            // rather than a migration, which is why this is not two booleans.
            $table->json('channels');

            $table->timestamps();

            $table->unique(['company_id', 'event', 'recipient_type', 'recipient_value'], 'notif_pref_unique');
            $table->index(['company_id', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');        
    }
};