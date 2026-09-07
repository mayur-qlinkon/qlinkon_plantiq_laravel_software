<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the first-generation projects module.
 *
 * The legacy design stored work, entitlement and money in a single `projects`
 * row (a `type` column switching between project/service semantics), and kept
 * renewals in a log table with no link to payments. Both are replaced by the
 * new project_* tables created in the migrations that follow.
 *
 * Verified before writing this: no other table holds a foreign key to
 * `services`, `projects` or `project_renewals`. The appointments module points
 * at `appointment_services`, which is a different table and is untouched.
 *
 * NOTE: this drops data. Only run it if the legacy records are disposable.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Child first — project_renewals holds a foreign key to projects.
        Schema::dropIfExists('project_renewals');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('services');
    }

    public function down(): void
    {
        // Intentionally irreversible. The legacy schema is superseded, and
        // recreating empty tables would give a false impression of recovery.
    }
};