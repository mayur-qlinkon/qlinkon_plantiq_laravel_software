<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('modules', 'depends_on')) {
            Schema::table('modules', function (Blueprint $table) {
                $table->json('depends_on')->nullable()->after('slug');
            });
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->string('registration_type')
                ->nullable()
                ->default('unregistered')
                ->change();
        });

        if (Schema::hasColumn('company_module_licenses', 'seat_used')) {
            Schema::table('company_module_licenses', function (Blueprint $table) {
                $table->dropColumn('seat_used');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('modules', 'depends_on')) {
            Schema::table('modules', function (Blueprint $table) {
                $table->dropColumn('depends_on');
            });
        }
    }
};