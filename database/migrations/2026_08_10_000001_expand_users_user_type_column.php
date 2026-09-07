<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Widen the legacy enum('full','employee') into a plain string first.
        // Nothing can be backfilled until the column physically accepts the
        // new UserType values, so this must run before any data update.
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_type', 30)->default('internal')->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('country', 100)->nullable()->default('India')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('user_type', ['full', 'employee'])->default('full')->change();
        });
    }
};