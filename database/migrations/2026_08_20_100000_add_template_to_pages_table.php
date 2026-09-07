<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Move pages from a single blob of tenant-authored HTML to a chosen template
 * plus a set of named text fields.
 *
 * The content column stays for now. Existing HTML cannot be split into fields
 * by any parser that would be trusted to get it right, so those rows are
 * marked 'custom' and keep rendering from content until their owners move
 * them across by hand. A later migration drops the column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('template', 50)->nullable()->after('type')->index();
            $table->json('data')->nullable()->after('template');
        });

        // Every existing row predates templates by definition.
        DB::table('pages')->whereNull('template')->update(['template' => 'custom']);
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex(['template']);
            $table->dropColumn(['template', 'data']);
        });
    }
};