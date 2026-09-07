<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The unique index on (company_id, slug) knows nothing about soft deletes,
     * so a deleted category permanently reserved its slug.
     * 
     * We drop the foreign key first to release the index, swap the unique
     * constraints, and then restore the foreign key.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // 1. Drop the foreign key that relies on the index
            $table->dropForeign(['company_id']);
            
            // 2. Drop the old unique index
            $table->dropUnique(['company_id', 'slug']);
            
            // 3. Create the new unique index
            $table->unique(['company_id', 'slug', 'deleted_at']);
            
            // 4. Recreate the foreign key
            // Note: Adjust the 'onDelete' behavior if your original setup was different
            $table->foreign('company_id')
                  ->references('id')
                  ->on('companies')
                  ->cascadeOnDelete(); 
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Reverse the process for rolling back
            $table->dropForeign(['company_id']);
            
            $table->dropUnique(['company_id', 'slug', 'deleted_at']);
            $table->unique(['company_id', 'slug']);
            
            $table->foreign('company_id')
                  ->references('id')
                  ->on('companies')
                  ->cascadeOnDelete();
        });
    }
};